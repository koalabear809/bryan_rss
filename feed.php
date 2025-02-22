<?php

include_once("IRSS.php");

class Feed {
    private $rss;

    public function __construct(IRSS $rss) {
        //- read feed
        // if($id !== null) {
        //     $this->id = $id;
        //     $xmlstring = file_get_contents("cache/{$this->id}.xml");
        //     $this->rss = $this->get_rss($xmlstring);
        // }
        $this->rss = $rss;
    }

    public function add_feed(string $url, string $name = null) {
        if(!file_exists("cache")) {
            mkdir("cache");
        }

        $this->rss->set_attribute('url', $url);
        $this->rss->set_attribute('name', $name);
        $this->rss->set_attribute('id', uniqid());
        $this->rss->set_attribute('last_refresh', time());

        $this->save_feed();
    }

    public function update_feed($values) {
        $this->rss->set_attribute('name', $values['name']);
        $this->rss->set_attribute('url', $values['url']);
        $this->save_feed();
    }

    private function get_rss($xmlstring) {
        //- read feed

        $rss = new RSS2($xmlstring);

        if(!$rss->validate()) {
            $rss = new RSS1($xmlstring);
        }

        return $rss;
    }

    public function get_feed() {
        $name = $this->rss->get_attribute('name');
        if($name === false) {
            $name = $this->rss->get_title();
        }
        return [
            'id' => $this->rss->get_attribute('id'),
            'title'=> $this->rss->get_attribute('title'),
            'name' => $name,
            'url' => $this->rss->get_attribute('url'),
            'last_refresh' => $this->rss->get_attribute('last_refresh'),
        ];
    }

    public function save_feed() {
        // $this->rss->set_attribute('last_refresh', time());
        $output = $this->rss->get_xml_string();
        $id = $this->rss->get_attribute('id');
        return file_put_contents("cache/{$id}.xml", $output);
    }

    public function delete() {
        $id = $this->rss->get_attribute('id');
        return unlink("cache/{$id}.xml");
    }

    public function refresh() {
        //- get feed
        $url = $this->rss->get_attribute('url');
        $name = $this->rss->get_attribute('name');
        $contents = file_get_contents($url);
        if($contents === false) {
            throw new Exception("Could not fetch feed");
        }

        $this->rss = $this->get_rss($contents);
        $this->rss->set_attribute('url', $url);
        $this->rss->set_attribute('name', $name);
        $this->rss->set_attribute('id', $this->id);
        $this->rss->set_attribute('last_refresh', time());
        $this->save_feed();
    }

    public function get_articles() {
        $articles = $this->rss->get_items();
        return $articles;
    }

    public static function list_feeds() {
        $feeds = glob("cache/*.xml");
        return $feeds;
    }
}