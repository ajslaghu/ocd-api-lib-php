<?php

class Ocd implements Iterator {

    private $query; // hash for input
    private $page; // the result (page)hash we provide during iteration n=0,..,n
    private $page_size = 20; // impact on memory
    private $current; // pointer to array n=0,1,...,n

    public function __construct() {
        $this->query['api_url'] = 'http://api.opencultuurdata.nl';
        $this->query['api_version'] = '/v0';
        $this->query['limit'] = NULL;
        $this->query['source'] = NULL;
    }

    // sets the maximum results
    public function limit($results) {
        $this->query['limit'] = $results;
        return $this;
    }

    // sets the search query string
    public function search($query_str) {
        $this->query['query_str'] = $query_str;
        return $this;
    }

    // sets the source of the Query (Rijksmuseum, Stedelijk). Null == Everything
    public function source($source) {
        $this->query['source'] = $source;
        return $this;
    }

    // adds array of facets to current query 
    public function add_facets($facets) {
        foreach ($facets as $item) {
            $this->query['facets'][key($facets)] = $facets[key($facets)];
        }
        return $this;
    }

    // assumes array of filters key values
    public function add_filters($filters) {
        foreach ($filters as $item) {
            $this->query['filters'][key($filters)] = $filters[key($filters)];
        }
        return $this;
    }

// Finalizes Object for Iteration (does no loading yet!)
    public function query($query_str = null) {
        // retrieve full object other option?
        if (isset($query_str))
            search($query_str);

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

    /* Sets the file position indicator for handle to the beginning of the file stream.
     * Returns TRUE on success or FALSE on failure.
     */

    public function rewind() {
        if (!$this->get_search(0)) {
            return FALSE;
        }
        if (!isset($this->page['hits']['total'])) {
            throw new Exception('no data found');
        }
        $this->total = $this->page['hits']['total'];
        $this->current = 0;
        return TRUE;
    }

    /* The current() function simply returns the value of the array element 
     * that's currently being pointed to by the internal pointer. 
     * It does not move the pointer in any way. If the internal pointer points 
     * beyond the end of the elements list or the array is empty, current() returns FALSE.
     */

    public function current() {
        if ($this->valid()) {
            return $this->page['hits']['hits'][($this->current % $this->page_size)];
        }
        return FALSE;
    }

    /* The key() function simply returns the key of the array element 
     * that's currently being pointed to by the internal pointer. 
     * It does not move the pointer in any way. If the internal pointer points 
     * beyond the end of the elements list or the array is empty, key() returns NULL.
     */

    public function key() {
        if ($this->valid()) {
            return $this->current;
        }
        return FALSE;
    }

    /* Returns the array value in the next place that's pointed to 
     * by the internal array pointer, or FALSE if there are no more elements. */

    public function next() {
        $this->current++;
        if (!$this->validate($this->current)) {
            return FALSE;
        }
        if ($this->current % $this->page_size == 0 && $this->current / $this->page_size > 0 && !$this->get_search((int) ($this->current / $this->page_size ))) {
            return FALSE;
        }
        return $this->page['hits']['hits'][($this->current % $this->page_size)];
    }

    /* Checks if current position is valid
     * The return value will be casted to boolean and then evaluated. 
     * Returns TRUE on success or FALSE on failure.
     */

    public function valid() {
        return $this->validate($this->current);
    }

    private function validate($pos) {
        if ($pos > ($this->total - 1)) {
            return FALSE;
        }
        if (isset($this->query['limit']) &&
                $pos >= $this->query['limit']) {
            return FALSE;
        }
        return TRUE;
    }

    private function get_search($page_num) {
        assert($page_num >= 0);
        $offset = $page_num * $this->page_size;
        $data = array('query' => $this->query['query_str'],
            'filters' => $this->query['filters'],
            'facets' => $this->query['facets'],
            'size' => $this->page_size,
            'from' => $offset); //to implement sort
        //print("Loading From $offset Size $this->page_size\n");
        $json = $this->rest($this->query['source'] . "/search", json_encode($data));
        if (@$json['status'] == "error")
            return FALSE;

        $this->page = $json;
        return TRUE;
    }

// performs the actual curl request    
    private function rest($command, $post_fields) {
        $ch = curl_init($this->api_uri() . $command);
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
        return $this->query['api_url'] . $this->query['api_version'];
    }

}
