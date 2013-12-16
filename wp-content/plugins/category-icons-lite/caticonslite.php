<?php
/*
Plugin Name: Category Icons Lite
Plugin URI: http://www.category-icons.com/
Description: Easily assign icons to your categories, Lite version.
Version: 1.1.4
Author: Brahim Machkouri
Author URI: http://www.category-icons.com/
*/

/**
* This function removes all traces of the plugin in the postmeta table
*/
function category_icons_lite_uninstall() {
	global $wpdb;
	$metas = $wpdb->get_results( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wp_attachment_category'" );	
}

include_once('caticons-lite.class.php');

if(class_exists("CategoryIconsLite")) {
	$CategoryIconsLite = new CategoryIconsLite;
}
    
if (isset($CategoryIconsLite)) {
	if (function_exists('register_uninstall_hook')) register_uninstall_hook(__FILE__,'category_icons_lite_uninstall');
	
    $caticonslite_icons = $CategoryIconsLite->get_all();
    $post_titles_icon = $CategoryIconsLite->are_icons_post_title();
    $sidebar_icons = $CategoryIconsLite->are_icons_sidebar();
    
    if ($sidebar_icons) { // if the option is checked, display the icon in the sidebar
        add_filter('wp_list_categories',array($CategoryIconsLite,'list_cats'), 10, 2);
    }
    
    if ($post_titles_icon) { // if the option is checked, display the icon in front of the post titles
        add_filter('the_title', array($CategoryIconsLite,'page_filter'), 10,2); 
        
        //--- The automagic mechanic
        add_action('the_content',array($CategoryIconsLite,'reset_flags'));
        add_action('the_post',array($CategoryIconsLite,'reset_flags'));
        add_filter('the_excerpt',array($CategoryIconsLite,'reset_flags'));
        add_filter('get_comments_number',array($CategoryIconsLite,'reset_flags'),10,2);
        add_filter('the_time',array($CategoryIconsLite,'reset_flags'),10,2);
        add_filter('previous_post_link',array($CategoryIconsLite,'permalink_flag_on'),10,2); // for the single.php page
        add_filter('the_permalink',array($CategoryIconsLite,'permalink_flag_on'),10,2);
        
        // for the theme like Canvas or Prototype
        //add_filter('post_link',array($CategoryIconsLite,'permalink_flag_on'),10,2);
        //add_filter('post_type_link',array($CategoryIconsLite,'permalink_flag_on'),10,2);
	}
	
	// WP Admin GUI    	
	
	if (is_admin() /*&& current_user_can( 'manage_categories' )*/ ) {
	
		add_action('admin_menu',array($CategoryIconsLite,'add_menu'),1);
		add_action('admin_init', array($CategoryIconsLite,'set_options'),1);
		
		//--- Media Library 
        add_filter('attachment_fields_to_edit', array($CategoryIconsLite,'form_fields'), 10, 2);
		add_filter('attachment_fields_to_save', array($CategoryIconsLite,'form_fields_save'), 10, 2);
		add_filter('media_row_actions',array($CategoryIconsLite,'catagory_name'),10,2);
		add_action('admin_init', array($CategoryIconsLite,'load_scripts'));
		add_filter('adminmenu', array($CategoryIconsLite,'delete_warning'));    		
		add_action('restrict_manage_posts',array($CategoryIconsLite,'show_caticons_dropdown'));
		add_filter('parse_query', array($CategoryIconsLite,'caticons_media_filter' ));    		
		
		if ('edit-tags.php' == $pagenow) {
    		//--- Categories Panel
    		add_filter('manage_edit-category_columns',array($CategoryIconsLite,'categories_header'));
    		add_filter('manage_category_custom_column',array($CategoryIconsLite,'categories_custom_column'), 10, 3);
    		add_filter('deleted_term_taxonomy', array($CategoryIconsLite,'category_removed'));
		}
	}
}

if (!function_exists('get_cat_icon_lite')) {

	function get_cat_icon_lite($cat_id=0) {
        return caticonslite_html_tag(caticonslite_get_icon($cat_id));
	}
	
	function caticonslite_html_tag($url,$title='') {
		$html = '';
		$alt = 'caticonslite_bm_alt';
		$alt = apply_filters('caticonslite_alt', $alt);
		if (!empty($title))  $title = esc_attr($title);
		$title = apply_filters('caticonslite_title', $title);
		if (!empty($url)) $html = "<img class='caticonslite_bm' alt=\"$alt\" src=\"".esc_url($url)."\" title=\"$title\" />";
		$html = apply_filters('caticonslite_htmltag', $html);
		return $html;
	}
		
    function caticonslite_get_icon($cat_id=0) {
		global $caticonslite_icons;
		$result = '';
		$cat_id = (int) $cat_id;
		if ( 0 == $cat_id ) {	
			$catlist = get_the_category(get_the_ID());
			if (is_array($catlist)) {// If there are several categories,
				if (count($catlist) > 0) {
					$cat_id = (int) $catlist[0]->term_id; // I take only the first one
					$cat_id = apply_filters('caticonslite_cat_priority', $cat_id, $catlist);
				}	
				else {
					return $result;
				} 	
			}
			else 
				$cat_id = (int) $catlist->term_id;
		}
		
		$cat_id = apply_filters('caticonslite_cat_id', $cat_id);
		
		if ($cat_id > 0 AND isset($caticonslite_icons[$cat_id]['url'])) {			
			$result = $caticonslite_icons[$cat_id]['url'];
		}
					
		return $result;
	}
}