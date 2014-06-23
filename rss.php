<?php
require_once 'ocd.php';
$q = 'rijksmuseum';
$collection = null;

if (isset($_GET['q']) && $_GET['q'] != '') {
    $q = urldecode($_GET['q']);
}

if (isset($_GET['collection']) && $_GET['collection'] != '') {
    $collection = urldecode($_GET['collection']);
}

$ocd = new Ocd();

//$ocd->search('van gogh executie');
$result = $ocd->search($q)
        ->add_facets(array('collection' => array($collection)))
        ->add_filters(array('media_content_type' => array('terms' => array('image/jpeg', 'image/gif', 'image/png'))))
        ->limit(3)
        ->query();
?>
<xml version="1.0" encoding="utf-8"> 
    <rss version="2.0" xml:base="http://search.opencultuurdata.nl/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/"> <channel> 
            <title>Open Cultuur Data RSS</title>
            <description>A RSS feed based on a search from search.opencultuurdata.nl</description>
            <link>http://search.opencultuurdata.nl/</link>
            <atom:link rel="self" href="http://search.opencultuurdata.nl/rss.php" />
            <language>en-us</language>
            <docs>http://www.opencultuurdata.nl/</docs>
            <?php foreach ($result as $item) { ?>
                <item>
                    <title><?= $item['_source']['title'] ?></title>
                    <link><!-- provides the original JSON --><?= $item['_source']['meta']['ocd_url'] ?></link>
                    <description>Not really.<br/></description>
                    <enclosure url="<?= $item['_source']['meta']['ocd_url'] ?>" type="<?= $item['_source']['media_urls'][0]['content_type'] ?>" />          
                    <guid isPermaLink="false"><?= $item['_source']['meta']['ocd_url'] ?></guid>
                    <pubDate><?= $item['_source']['date'] ?></pubDate>
                    <source url="http://search.opencultuurdata.nl/rss.php">A RSS feed based on a search from search.opencultuurdata.nl</source>
                </item>
            <?php } ?> 
        </channel>
    </rss>
