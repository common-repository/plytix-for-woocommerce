<?php
// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once(ABSPATH . 'wp-admin/includes/image.php');

class Plytix_Admin_Functions {
    /**
     * Filter URL (No HTTP, no subfolders e.g:/wp)
     *
     * @param $url
     * @return mixed|string
     */
    static public function filter_url($url) {
        $search = array(
            'http://',
            'https://',
        );
        $url = str_replace($search, '', $url);
        if (strpos($url, '/')) {
            $url = substr($url, 0, strpos($url, '/'));
        }
        return $url;
    }

    /**
     * returns only the protocol and host of an url
     *
     * @param $url
     * @return mixed|string
     */
    static private function get_protocol_and_host($url) {
        $pattern = "#(https?://)([^/]*)(.*)#i";
        $replace = '$1$2';
        return preg_replace($pattern,$replace, $url);
    }
}
