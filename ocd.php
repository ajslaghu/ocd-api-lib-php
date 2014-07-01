<?php

class Ocd implements Iterator {

    private $size = 5; // #results per API call; impact on memory
    private $query; // hash for input
    private $page; // the result (page)hash we provide during iteration n=0,..,n
    private $current; // pointer to array n=0,1,...,n
    private $limit;

    public function __construct() {
        $this->api_url = 'http://api.opencultuurdata.nl';
        $this->api_version = '/v0';
    }

// not tested yet
// returns version of the object that is not iteratable
// GET /(source_id)/(object_id)/source
    public function object($id) {
// but source && similar is required
        unset($this->query['query']);
        unset($this->query['similar']);

        $this->query['object_id'] = $id;


        return $this;
    }

// GET /similar/object-id / not tested yet
    public function similar($id) {
// source is allowed
        unset($this->query['query']);
        unset($this->query['object_id']);

        $this->query['similar'] = $id;

        return $this;


//return $this;
    }

// sets the search query string
    public function search($search_str) {
// source is allowed
        unset($this->query['similar']);

        $this->query['query'] = $search_str;
        return $this;
    }

// sets the (sole) source of the Query (Rijksmuseum, Stedelijk). Null == Everything
    public function source($source) {
        $this->query['source'] = $source;
        return $this;
    }

// (re)sets sort based on array of sort hashes
// { "date":   { "order": "desc" }},{ "_score": { "order": "desc" }}
// meta.source, meta.processing_started, meta.processing_finished, date, date_granularity, authors, _score
    public function sort($sort) {
        $this->query['sort'] = $sort;
        return $this;
    }

// adds array of facets to current query which are added to stack
    public function add_facets($facets) {
        foreach ($facets as $key => $value) {
            $this->query['facets'][$key] = $value;
        } return $this;
    }

// assumes array of filters key values which are added to stack
    public function add_filters($filters) {
        foreach ($filters as $key => $value) {
            $this->query['filters'][$key] = $value;
        }
        return $this;
    }

// (re)sets facets with array of facets
    public function facets($facets) {
        $this->query['facets'] = $facets;
        return $this;
    }

// (re)sets facets with array of facets
    public function filters($filters) {
        $this->query['filters'] = $filters;
        return $this;
    }

// sets the maximum results
    public function limit($results) {
        $this->limit = $results;
        return $this;
    }

// Loads Query So Iteration can take place 
// Flushes pointer 
    public function query() {
        $this->current = null;
        if (!$this->get_results() || !$this->validate($this->current)) {
            return FALSE;
        }
        $this->current = 0;
        return $this;
    }

    /* Sets the file position indicator for handle to the beginning of the file stream.
     * Returns TRUE on success or FALSE on failure.
     */

    public function rewind() {
//       echo "rewind\n";
        if (!isset($this->current)) {
            throw new Exception('$this->current is null. Meaning something failed');
            //return FALSE;
        }

        if ($this->current > $this->size) {// we are not in the first page.
            $this->current = 0;
            $this->get_results();
        }
        $this->current = 0;
        if (!$this->validate($this->current)) {
            throw new Exception('!$this->validate($this->current)\n');
            // return FALSE;
        }
        return TRUE;
    }

    /* The current() function simply returns the value of the array element 
     * that's currently being pointed to by the internal pointer. 
     * It does not move the pointer in any way. If the internal pointer points 
     * beyond the end of the elements list or the array is empty, current() returns FALSE.
     */

    public function current() {
//  echo "current\n";
        if ($this->validate($this->current)) {
            return $this->page['hits']['hits'][($this->current % $this->size)];
        }
        return FALSE;
    }

    /* The key() function simply returns the key of the array element 
     * that's currently being pointed to by the internal pointer. 
     * It does not move the pointer in any way. If the internal pointer points 
     * beyond the end of the elements list or the array is empty, key() returns NULL.
     */

    public function key() {
        echo "key\n";
        if ($this->validate($this->current)) {
            return $this->current;
        }
        return FALSE;
    }

    /* Returns the array value in the next place that's pointed to 
     * by the internal array pointer, or FALSE if there are no more elements. */

    public function next() {
//   echo "next\n";
        $this->current++;
        if (!$this->validate($this->current)) {
            return FALSE;
        }
        if ($this->current % $this->size == 0 && $this->current / $this->size > 0 &&
                !$this->get_results()) {
            return FALSE;
        }
        return $this->page['hits']['hits'][($this->current % $this->size)];
    }

    /* Checks if current position is valid
     * The return value will be casted to boolean and then evaluated. 
     * Returns TRUE on success or FALSE on failure.
     */

    public function valid() {
//    echo "valid\n";
        return $this->validate($this->current);
    }

// private validation function
    private function validate($pos) {
        if (!isset($this->page['hits']['total'])) {
            return FALSE;
        }
        if ($pos > ($this->page['hits']['total'] - 1)) {
            return FALSE;
        }
        if (isset($this->limit) &&
                $pos >= $this->limit) {
            return FALSE;
        }
        return TRUE;
    }

    private function get_results() {
        if (isset($this->query['object_id'])) {// handle get object_id (also handles src from object ID
            assert(!isset($this->query['query']) && !isset($this->query['similar']));
            $op = @$this->query['source'] . "/" . $this->query['object_id'];
        } else {
            assert($this->page_num() >= 0 && !isset($this->query['object_id']));
            if (isset($this->query['query'])) {//query
                assert(!isset($this->query['object_id']) && assert(!isset($this->query['similar'])));
                $op = @$this->query['source'] . "/search";
            } else if (isset($this->query['similar'])) {//handle similar
                assert(!isset($this->query['query']));
                $op = @$this->query['source'] . "/similar/" . $this->query['similar'];
            }
            $data['from'] = $this->page_num() * $this->size;
            $data['size'] = isset($this->limit) ? (
                    $this->limit < $data['from'] + $this->size ?
                            $this->limit % $this->size : $this->size ) : $this->size;
            foreach ($this->query as $key => $value) {
                $data[$key] = $value;
            }
        }

        $json = $this->rest($op, json_encode($data));
        if (@$json['status'] == "error") {
            throw new Exception($json['error']);
            //return FALSE;
        }
        $this->page = $json;

        return TRUE;
    }

    private function page_num() {
        return (int) $this->current == 0 ? $this->current : $this->current / $this->size;
    }

// performs the actual curl request    
    private function rest($op, $post_fields) {
        echo $op . "\n";
        var_dump($post_fields);
        $ch = curl_init($this->api_uri() . $op);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_fields)));
        $result = curl_exec($ch);
        return json_decode($result, TRUE);
    }

// returns the uri consisting of the url and api version
    public function api_uri() {
        return $this->api_url . $this->api_version;
    }

// Returns total number of hits. We assume they are identical on every page (might be bold, ahem)
    public function total() {
        if (isset($this->page)) {
            return $this->page['hits']['total'];
        }
        return NULL;
    }

// Returns max_score. We assume they are identical on every page (might be bold, ahem)
    public function max_score() {
        if (isset($this->page)) {
            return $this->page['hits']['max_score'];
        }
        return NULL;
    }

// temporary function for debugging
    public function get_page() {
        if (isset($this->page)) {
            return $this->page;
        }
        return NULL;
    }

// Returns facets. We assume they are identical on every page (might be bold, ahem)
    public function get_facets() {
        if (isset($this->page)) {
            return $this->page['facets'];
        }
        return NULL;
    }

// sets the api url like 'http:server.org' no trailing slash
    public function api($url) {
        $this->query['api_url'] = $url;
        return $this;
    }

// sets the api version like '/v0'. no trailing slash
    public function api_version($version) {
        $this->query['api_version'] = $version;
        return $this;
    }

}
