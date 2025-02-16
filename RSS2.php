<?php

include_once("IRSS.php");

class RSS2 implements IRSS{
    private $xml;
    public function __construct($xml) {
        //- read xml
        $this->xml = simplexml_load_string($xml);
    }

    public function validate() {
        //- Check version number in attributes
        $version = $this->get_attribute('version');

        if($version === '2.0') {
            return true;
        }

        return false;
    }

    public function get_title() {
        $title = (string) $this->xml->channel->title;

        return $title;
    }

    public function get_items() {
        echo '';
        $items = [];

        foreach( $this->xml->channel->item as $item ) {
            $new_item = [];
            foreach($item as $key => $value) {
                $new_item[$key] = (string) $value;
            }

            $items[] = $new_item;
        }
        return $items;
    }

    public function set_attribute($key, $value) {
        $this->xml->addAttribute($key, $value);
    }

    public function get_attribute($key) {
        $attributes = $this->xml->attributes();

        foreach($attributes as $att_key => $val) {
            if($att_key === $key) {
                return (string) $val;
            }
        }
    }

    public function get_xml_string() {
        return $this->xml->asXML();
    }
}