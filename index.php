<?php

include("html.php");
include("RSS1.php");
include("RSS2.php");

$uri = $_SERVER['REQUEST_URI'];

$html = new Html();
$navlinks = [
    'Home' => '/',
    'Feeds' => '/feed',
    'Configure' => '/configure',
];
$html->navlinks = $navlinks;

$params = parse_url($uri);

$output = '';

$routes = [
    '/' => function() {
        return '';
    },
    '/configure' => function() use ($html) {
        if(isset($_REQUEST['feed'])) {
            //- get feed
            $feed_id = $_REQUEST['feed'];
            $contents = file_get_contents("cache/{$feed_id}.xml");
            $rss = get_rss($contents);
            $name = $rss->get_attribute('name');
            if($name === false) {
                $name = $rss->get_title();
            }
            $url = $rss->get_attribute('url');
            $id = $rss->get_attribute('id');

            return $html->configure_feed($name, $url, $id);
        } else {
            if(isset($_POST['add']) > 0) {
                $feed = $_POST['add'];
                $feed = trim($feed);

                //- get feed
                $contents = file_get_contents($feed);

                $rss = get_rss($contents);
                $title = $rss->get_title();

                $rss->set_attribute('url', $feed);
                $rss->set_attribute('name', $_POST['name']);
                $rss->set_attribute('last_refresh', time());

                if(!file_exists("cache")) {
                    mkdir("cache");
                }

                store_feed($rss);
            }

            //- Form to add feed
            $form = $html->add_feed_form();

            //- Form to manage feeds
            $feeds = glob("cache/*.xml");
            $feeds_config_section = [];
            foreach($feeds as $feed) {
                $contents = file_get_contents($feed);
                $rss = get_rss($contents);

                $id = $rss->get_attribute('id');
                $name = $rss->get_attribute('name');
                if($name === false) {
                    $name = $rss->get_title();
                }
                $feeds_config_section[] = "<a href=\"/configure?feed={$id}\"><button>configure</button></a> {$name}";
            }

            if(!isset($output)) {
                $output = '';
            }
            return $output . $form . "<br><br>" . implode("<br><br>", $feeds_config_section);
        }
    },
    '/delete' => function() use ($html) {
        $feed = $_GET['id'];
        $filename = "cache/{$feed}.xml";
        if(file_exists($filename)) {
            unlink($filename);
            return "Feed deleted: {$feed}";
        } else {
            return "Feed not found: {$feed}";
        }
    },
    '/update' => function() use ($html) {
        echo '';

        //- get variables from $_POST
        $name = $_POST['name'];
        $url = $_POST['url'];
        $id = $_POST['id'];

        //- get feed
        $contents = file_get_contents("cache/{$id}.xml");
        $rss = get_rss($contents);
        $rss->set_attribute('name', $name);
        $rss->set_attribute('url', $url);
        store_feed($rss);

        header('Location: /configure');
    },
    '/feed' => function() use ($html) {
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $feed = "cache/{$id}.xml";
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

            $name = $rss->get_attribute('name');
            if($name === false) {
                $name = $rss->get_title();
            }

            $header = "<h1 class=\"feed-name\">{$name}</h1>";
            return $header . $list;
        } else {
            //- feeds
            $feeds = glob("cache/*.xml");

            $list = [];
            foreach ($feeds as $feed) {
                $contents = file_get_contents($feed);
                $rss = get_rss($contents);

                $name = $rss->get_attribute('name');
                if($name === false) {
                    $name = $rss->get_title();
                }

                $id = $rss->get_attribute('id');

                $list[] = $html->rss_link($id, $name);
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
    $id = $rss->get_attribute('id');
    return file_put_contents("cache/{$id}.xml", $output);
}

function get_rss($xmlstring) {
    $rss = new RSS2($xmlstring);

    if(!$rss->validate()) {
        $rss = new RSS1($xmlstring);
    }

    //- check if there is an id
    $id = $rss->get_attribute('id');
    if($id === false) {
        $rss->set_attribute('id', uniqid());
        
        //- save the feed
        store_feed($rss);
    }

    //- Check if we should refresh the feed
    // $last_refresh = $rss->get_attribute('last_refresh');
    // if($last_refresh < time() - 3600) {
    //     $url = $rss->get_attribute('url');

    //     $contents = file_get_contents($url);

    //     //- read the feed into an XML object
    //     $rss = get_rss($contents);
    // }

    return $rss;
}