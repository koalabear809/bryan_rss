<?php

interface IRSS {
    public function validate();
    public function get_title();

    public function get_items();

    public function set_attribute($key, $value);

    public function get_attribute($key);

    public function get_xml_string();
}