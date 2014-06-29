<?php

class Ocd implements Iterator {

    private $query; // hash for input
    private $page; // the result (page)hash we provide during iteration n=0,..,n
    private $size = 20; // impact on memory
    private $current; // pointer to array n=0,1,...,n

    public function __construct() {
        $this->query['api_url'] = 'http://api.opencultuurdata.nl';
        $this->query['api_version'] = '/v0';
        $this->query['limit'] = NULL;
        $this->query['source'] = NULL;
        $this->query['sort'] = '_score';
    }

    // not tested yet
    public function get_facets(){ 
        // return facets if they exist
         return $this->page['facets'];        
    }
    
    
    // not tested yet
    // returns version of the object that is not iteratable
    // GET /(source_id)/(object_id)/source
    public function object($id){
        // !search 
        // but source && similar is required
        $this->query['object_id'] = $object_id;
        return $this->page;        
        return ;//! $this;, but return HTML stub
    }
    
    // not tested yet
    public function similar($id) {
        // source is allowed
        $this->query['similar'] = $id;
        $this->query['query_str'] = null;
        return $this;
    }

    // sets the search query string
    public function search($search_str) {        
        // source is allowed
        $this->query['query_str'] = $search_str;
        $this->query['similar']= null;
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
        }
        return $this;
    }

    // assumes array of filters key values which are added to stack
    public function add_filters($filters) {
        foreach ($filters as  $key => $value) {
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
        $this->query['limit'] = $results;
        return $this;
    }

    // Finalizes Object for Iteration (does no loading yet!)
    public function query() {
        // retrieve full object other option?
        // now we assume a search query in our iterator class (Rewind and Next
        // but we need to check if a similar query is fired
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
        if (!$this->get_results(0)) {
            return FALSE;
        }
        if (!isset($this->page['hits']['total'])) {
            throw new Exception('No data found');
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
        if ($this->current % $this->size == 0 && $this->current / $this->size > 0 && !$this->get_results((int) ($this->current / $this->size ))) {
            return FALSE;
        }
        return $this->page['hits']['hits'][($this->current % $this->size)];
    }

    /* Checks if current position is valid
     * The return value will be casted to boolean and then evaluated. 
     * Returns TRUE on success or FALSE on failure.
     */

    public function valid() {
        return $this->validate($this->current);
    }

    // private validation function
    private function validate($pos) {
        if (!isset($this->total)) {
            return FALSE;
        }
        if ($pos > ($this->total - 1)) {
            return FALSE;
        }
        if (isset($this->query['limit']) &&
                $pos >= $this->query['limit']) {
            return FALSE;
        }
        return TRUE;
    }

    private function get_results($page_num) {
        assert($page_num >= 0);
        $from = $page_num * $this->size;

        $data = array('query' => @$this->query['query_str'],
            'filters' => @$this->query['filters'],
            'facets' => @$this->query['facets'],
            'sort' => @$this->query['sort'],
            'size' => $this->size,
            'from' => $from); //to implement sort

        $json = $this->rest($this->query['source'] . "/search", json_encode($data));
        if (@$json['status'] == "error") {
            throw new Exception($json['error']);
            return FALSE;
        }
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
