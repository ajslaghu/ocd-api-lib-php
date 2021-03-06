<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8" ?>' . "\n"); ?>
<?php
require_once 'ocd.php';

// 'van gogh executie' (only 3 results)
// 'rijksmuseum (about 5800 results)
define("DEF_QUERY", "rembrandt+olieverf");// gives nice results

$q = filter_input(INPUT_GET, 'q' ) ? filter_input(INPUT_GET, 'q' ) : DEF_QUERY ;
$collection = filter_input(INPUT_GET, 'collection') ? filter_input(INPUT_GET, 'collection') : null;
// probably no url decode required cus filter_input will already handle this?

$ocd = new Ocd();
$result = $ocd->search($q)
        ->add_facets(array('collection' => array($collection)))
        ->add_filters(array('media_content_type' => array('terms' => array('image/jpeg', 'image/gif', 'image/png'))))
        ->sort('meta.processing_finished')
        ->limit(100)
        ->query();
?>
<rss version="2.0" xml:base="http://search.opencultuurdata.nl/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel> 
        <title>Open Cultuur Data RSS</title>
        <description>A RSS feed based on a search from search.opencultuurdata.nl</description>
        <link>http://search.opencultuurdata.nl/</link>
        <atom:link rel="self" href="http://search.opencultuurdata.nl/rss/rss.php" />
        <language>en-us</language>
        <docs>http://www.opencultuurdata.nl/</docs>
        <?php foreach ($result as $item) { ?>
            <item>
                <title><?= $item['_source']['title'] ?></title>
                <link><?= $item['_source']['meta']['ocd_url'] ?></link>
                <description><?= $item['_source']['description'] ?></description>
                <enclosure url="<?= $item['_source']['media_urls'][0]['url'] ?>" type="<?= $item['_source']['media_urls'][0]['content_type'] ?>"  length="250000" />          
                <guid isPermaLink="false"><?= $item['_source']['meta']['ocd_url'] ?></guid>
                <pubDate><?= date_format(new DateTime(($item['_source']['meta']['processing_finished'])), DateTime::RFC2822) ?></pubDate>
                <source url="http://search.opencultuurdata.nl/">A search from search.opencultuurdata.nl</source>
            </item>
        <?php } ?> 
    </channel>
</rss>
