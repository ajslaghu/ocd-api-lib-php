<?php

require_once 'ocd.php';

$ocd = new Ocd();

//$ocd->search('van gogh executie');
$result = $ocd->search('rijksmuseum')
        ->add_facets(array('collection' => array()))
        ->add_filters(array('media_content_type' => array('terms' => array('image/jpeg', 'image/gif', 'image/png'))))
        ->limit(21)
        ->query();
//$result = $ocd->query();
$i = 0;
foreach ($result as $item) {
    print("     ----->>>>>>  I $i " . $item['_id']. ' ' . $item['_source']['title'] . "\n"); 
    $i++;
}
