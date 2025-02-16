<?php

class Html {
    public $navlinks = [];

    public function output($html) {
        $output = <<<HTML
        <!DOCTYPE html>

        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>RSS Feed</title>
            <link rel="stylesheet" href="style.css">
        </head>

        <body>
            <nav>
        HTML;

        foreach ($this->navlinks as $name => $link) {
            $output .= <<<HTML
            <a class="navlink" href="{$link}">{$name}</a>
            HTML;
        }

        $output .= <<<HTML
            </nav>
            <main>
                $html
            </main>
            <style>
                {$this->css()}
            </style>
        </body>
        HTML;

        return $output;
    }

    public function rss_list_item($title, $description, $link) {
        //- only show first 50 words
        $description = explode(" ", $description);
        $description = array_slice($description, 0, 50);
        $description = implode(" ", $description);
        $description = $description . "...";

        $output = <<<HTML
        <div class="rss-item">
            <h2>{$title}</h2>
            <p>{$description}</p>
            <a href="{$link}">Full Article</a>
            <a href="/article?url={$link}">Read here (Experimental)</a>
        </div>
        HTML;

        return $output;
    }

    public function rss_link($title) {
        $titleurl = urlencode($title);
        $output = <<<HTML
        <a href="/feed?name={$titleurl}">$title</a>
        HTML;

        return $output;
    }

    public function add_feed_form() {
        $output = <<<HTML
        <form action="/add" method="post">
            <input type="text" name="feed" placeholder="Enter RSS feed URL">
            <button type="submit">Add</button>
        </form>
        HTML;

        return $output;
    }

    private function css() {
        $output = <<<CSS
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        main {
            padding: 10px;
        }

        .navlink {
            padding: 10px;
            margin: 10px;
            background-color: #f0f0f0;
            text-decoration: none;
            color: #333;
        }
        CSS;

        return $output;
    }
}
