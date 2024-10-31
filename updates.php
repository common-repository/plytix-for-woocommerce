<?php

function update_plytix_database_075() {
    global $wpdb;

    $query  = "DELETE FROM `".$wpdb->prefix."postmeta` ";
    $query .= "WHERE " ;
    $query .= "meta_key LIKE 'plytix_cdn_%' ";
    $wpdb->get_results($query);

    $query  = "SELECT post_id from ".$wpdb->prefix."postmeta WHERE meta_key = 'plytix_product_id'";
    $rematched = $wpdb->get_results($query);
    $rematched_ids = array();
    foreach ($rematched as $post) {
        $rematched_ids[] = $post->post_id;
    }
    $rematched_ids = "(".implode(",", $rematched_ids).")";

    $query  = "UPDATE ".$wpdb->prefix."postmeta SET meta_key='plytix_product_id' WHERE meta_key='_plytixt_product_id' AND post_id NOT IN ".$rematched_ids;
    $wpdb->get_results($query);

    $query = "DELETE from ".$wpdb->prefix."postmeta WHERE meta_key = '_plytixt_product_id'";
    $wpdb->get_results($query);
}

function update_plytix_database_080() {
    global $wpdb;

    $query  = "DELETE FROM `".$wpdb->prefix."postmeta` ";
    $query .= "WHERE " ;
    $query .= "meta_key LIKE 'plytix%' ";
    $wpdb->get_results($query);
}
