<?php
/*
Plugin Name: Typograffit Plugin
Plugin URI: http://msty.jp/wp_typograffit
Description: Enables you to access the Typograffit functions. You can easily typograph-ize your post-items even if you use the multi-byte charactors such like Japanese. Usage: Simply　putting the words you want to convert to fine image-fonts between short-codes. Example : [typo]this is a test.これはテスト. [/typo]
Author: Masato Yamaguchi
Version: 1.0
Author URI: http://localhost/wp_typograffit
*/

define(TYPOGRAFFIT_BASE_URI,'http://centos.local/typograffit/');
define(TYPOGRAFFIT_REST_URI,TYPOGRAFFIT_BASE_URI.'rest_json/');
define(TYPOGRAFFIT_COMPOSE_URI,TYPOGRAFFIT_BASE_URI.'posts/compose/');

function typograffit_getImage($atts,$content=''){
	global $wpdb;
	$table_name = $wpdb->prefix . "typograffit";
	extract(shortcode_atts(array('post_id' => null,'maxWidth' => null,'maxHeight' => 20), $atts));
	if(empty($post_id)){
		$post_id = typograffit_generate($content);
	}
	$typo_html = '<a href="'.TYPOGRAFFIT_COMPOSE_URI.$post_id.'" style="display:inline">';
	$typo_html .= '<img style="display:inline" src = "'.TYPOGRAFFIT_REST_URI.'posts/getImage';
	if(!empty($maxWidth)){
		$typo_html .= '/max_width:'.$maxWidth;
	}
	if(!empty($maxHeight)){
		$typo_html .= '/max_height:'.$maxHeight;
	}
	$typo_html .= '/post_id:'.$post_id;
	$typo_html .= '" alt="'.$content.'"/></a>';
	return $typo_html;
}

function typograffit_generate($content){
	global $wpdb;
	$table_name = $wpdb->prefix . "typograffit";
	$res = $wpdb->get_var("SELECT post_id FROM $table_name WHERE phrase='$content' LIMIT 1");
	if(empty($res)){
		$api_res = file_get_contents(TYPOGRAFFIT_REST_URI.'posts/generate/no_wrap:1/body:'.rawurlencode($content));
		preg_match('/\{\"(.*)\":\"(.*)\"\}/i',$api_res,$res_var);
		if($res_var[1] == 'post_id'){
			$postid = $res_var[2];
			$result = $wpdb->query("INSERT INTO ".$table_name." (phrase,post_id) VALUES ('".$content."','".$postid."')");
		}
	}else{
		$postid = $res;
	}
	return $postid;
}


$typo_db_version = "1.0";
function typograffit_install () {
	global $wpdb;
	global $typo_db_version;
	$table_name = $wpdb->prefix . "typograffit";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		phrase VARCHAR(255) NOT NULL,
		post_id VARCHAR(20) NOT NULL,
		UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option("typo_db_version", $typo_db_version);
	}
}

register_activation_hook(__FILE__,'typograffit_install');
add_shortcode('typo','typograffit_getImage');


?>
