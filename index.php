<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'ocd.php';

$ocd = new Ocd();

//$ocd->search('van gogh executie');
$result = $ocd->search('rijksmuseum')
        ->add_facets(array('collection' => array()))
        ->add_filters(array('media_content_type' => array('terms' => array('image/jpeg', 'image/gif', 'image/png'))))
        ->limit(150)
        ->query();
$i = 0;
foreach ($result as $item) {
    print("     ----->>>>>>  I $i " . $item['_id']. ' ' . $item['_source']['title'] . "\n"); 
    $i++;
    //  echo "memory " . memory_get_usage() . "\n";
//    if ($i > 4)
//        break;
}
