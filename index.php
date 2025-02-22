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
        //- TODO: Show newest articles from all feeds
        return '';
    },
    '/configure' => function() use ($html) {
        if(isset($_REQUEST['feed'])) {
            $feed_id = $_REQUEST['feed'];
            $feed = new Feed($feed_id);
            return $html->configure_feed($feed);
        } else {
            if(isset($_POST['add']) > 0) {
                $url = trim($_POST['add']);
                $name = trim($_POST['name']);

                $feed = new Feed();
                $feed->add_feed($url, $name);
            }

            //- Form to add feed
            $form = $html->add_feed_form();

            //- Form to manage feeds
            $feeds = Feed::list_feeds();
            $feeds_config_section = [];
            foreach($feeds as $feed) {
                $id = basename($feed, '.xml');
                $feed = new Feed($id);
                $feed = $feed->get_feed();
                $feeds_config_section[] = "<a href=\"/configure?feed={$feed['id']}\"><button>configure</button></a> {$feed['name']}";
            }

            if(!isset($output)) {
                $output = '';
            }
            return $output . $form . "<br><br>" . implode("<br><br>", $feeds_config_section);
        }
    },
    '/delete' => function() use ($html) {
        $id = $_GET['id'];
        $feed = new Feed($id);
        $feed->delete_feed();
        
    },
    '/update' => function() use ($html) {
        $name = $_POST['name'];
        $url = $_POST['url'];
        $id = $_POST['id'];

        $feed = new Feed($id);
        $feed->update_feed(['name' => $name, 'url' => $url]);

        header('Location: /configure');
    },
    '/feed' => function() use ($html) {
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $feed = new Feed($id);

            $last_refresh = $feed->get_feed['last_refresh'];
            if($last_refresh < time() - 3600) {
                $feed->refresh();
            }

            // $items = $rss->get_items();
            $articles = $feed->get_articles();

            $list = [];
            foreach ($articles as $article) {
                $list[] = $html->rss_list_item($article['title'], $article['description'], $article['link']);
            }

            $list = implode("", $list);

            $name = $feed->get_feed()['name'];
            $header = "<h1 class=\"feed-name\">{$name}</h1>";
            return $header . $list;
        } else {
            $feeds = Feed::list_feeds();

            $list = [];
            foreach ($feeds as $feed) {
                $id = basename($feed, '.xml');
                $feed = new Feed($id);
                $name = $feed->get_feed()['name'];
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

// function get_rss($xmlstring) {
//     $rss = new RSS2($xmlstring);

//     if(!$rss->validate()) {
//         $rss = new RSS1($xmlstring);
//     }

//     return $rss;
// }