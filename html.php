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

    public function rss_link($id, $name) {
        $output = <<<HTML
        <a href="/feed?id={$id}">
            <div class="rss-link">
                $name
            </div>
        </a>
        HTML;

        return $output;
    }

    public function add_feed_form() {
        $output = <<<HTML
        <form action="/configure" method="post">
            <input type="text" name="add" placeholder="Enter RSS feed URL">
            <input type="text" name="name" placeholder="Enter Feed Name">
            <button type="submit">Add</button>
        </form>
        HTML;

        return $output;
    }

    public function article($article) {
        $output = <<<HTML
        <div class="article-wrapper">
            $article
        </div>
        HTML;

        return $output;
    }

    public function configure_feed($name, $url, $id) {
        $output = <<<HTML
        <h2> Configure feed {$id} </h2>
        <form class="configure-form" action="/update" method="post">
            <input type="text" name="name" value="{$name}"><br>
            <input type="text" name="url" value="{$url}"><br>
            <input type="hidden" name="id" value="{$id}">
            <button type="submit">Update</button>
        </form>
        <br>
        <br>
        <a href="/delete?id={$id}"><button class="danger">Delete</button></a>
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

        img {
            max-width: 300px;
            max-height: 300px;
        }

        nav {
            display: flex;
            background-color: #333;
            color: #fff;
        }

        button {
            padding: 10px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .navlink {
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 20px;
            padding-right: 20px;
            text-decoration: none;
            color: white;
        }

        .navlink:hover {
            background-color: #555;
        }

        .rss-item {
            margin: 10px;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .rss-link {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .feed-name {
            margin: 10px;
            border-bottom: 1px solid #ccc;
            color: #333;
        }

        .article-wrapper {
            width: 80%;
        }

        .configure-form input {
            margin-bottom: 10px;
        }

        .danger {
            background-color:rgb(167, 0, 0);
        }
        CSS;

        return $output;
    }
}
