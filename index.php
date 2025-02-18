<?php

include("html.php");
include("RSS1.php");
include("RSS2.php");

$uri = $_SERVER['REQUEST_URI'];

$html = new Html();
$navlinks = [
    'Home' => '/',
    'Feeds' => '/feed',
    'Add Feed' => '/add',
];
$html->navlinks = $navlinks;

$params = parse_url($uri);

$output = '';

$routes = [
    '/' => function() {
        return '';
    },
    '/add' => function() use ($html) {
        if(count($_POST) > 0) {
            $feed = $_POST['feed'];
            $feed = trim($feed);

            //- get feed
            $contents = file_get_contents($feed);

            $rss = get_rss($contents);
            $title = $rss->get_title();

            $rss->set_attribute('url', $_POST['feed']);
            $rss->set_attribute('last_refresh', time());

            $filename = "cache/{$title}.json";

            if(!file_exists("cache")) {
                mkdir("cache");
            }

            if(file_exists($filename)) {
                unlink($filename);
            }

            $retval = file_put_contents("cache/{$title}.xml", $rss->get_xml_string());
            if($retval === false) {
                $output = "Failed to add feed<br><br>";
            } else {
                $output = "Feed added: " . $title . "<br><br>";
            }
        }

        //- Form to add feed
        $form = $html->add_feed_form();

        return $output . $form;
    },
    '/feed' => function() use ($html) {
        if(isset($_GET['name'])) {
            $name = $_GET['name'];
            $feed = "cache/{$name}.xml";
            $feed = file_get_contents($feed);
            $rss = get_rss($feed);

            //- Check if we should refresh the feed, every hour
            $last_refresh = $rss->get_attribute('last_refresh');
            if($last_refresh < time() - 3600) {
                $url = $rss->get_attribute('url');

                $contents = file_get_contents($url);

                //- read the feed into an XML object
                $rss = get_rss($contents);
            }

            $items = $rss->get_items();

            $list = [];
            foreach ($items as $item) {
                $list[] = $html->rss_list_item($item['title'], $item['description'], $item['link']);
            }

            $list = implode("", $list);
            $header = "<h1 class=\"feed-name\">{$name}</h1>";
            return $header . $list;
        } else {
            //- feeds
            $feeds = glob("cache/*.xml");

            $list = [];
            foreach ($feeds as $feed) {
                $contents = file_get_contents($feed);
                $rss = get_rss($contents);

                $title = $rss->get_title();

                $list[] = $html->rss_link($title);
            }

            $list = implode("<br>", $list);
            return $list;
        }
    },
    '/article' => function() use ($html) {
        $url = $_GET['url'];
        $contents = @file_get_contents($url);
        if($contents === false) {
            $article = "<br><br>Unable to display article contents";
        } else {
            libxml_use_internal_errors(true);
            $domdoc = new DOMDocument();
            $domdoc->loadHTML($contents);

            //- get body
            $stripped_tags = [
                'style',
                'script',
                'noscript',
                'nav',
                'img',
                'footer',
                'link',
                'iframe',
                'section',
                'svg',
                'input',
                'textarea',
                'button',
                'head',
            ];

            foreach($stripped_tags as $tag) {
                $tags = $domdoc->getElementsByTagName($tag);
                $_tags = [];
                foreach($tags as $tag) {
                    $_tags[] = $tag;
                }

                foreach($_tags as $tag) {
                    $tag->parentNode->removeChild($tag);
                }
            }

            $article = $domdoc->saveHTML();
            $article = $html->article($article);
        }
        $title = "<br><a class=\"rss-item\" href={$url}>{$url}</a>";
        return $title . $article;
    }
];

//- Route
if(isset($routes[$params['path']])) {
    $output = $html->output($routes[$params['path']]());
} else {
    $output = $html->output("404 - Page not found");
}

echo $output;

function store_feed($rss) {
    $rss->set_attribute('last_refresh', time());

    $output = $rss->get_xml_string();
    $title = $rss->get_title();
    return file_put_contents("cache/{$title}.xml", $output);
}

function get_rss($xmlstring) {
    $rss = new RSS2($xmlstring);

    if($rss->validate()) {
        return $rss;
    }

    return new RSS1($xmlstring);
}