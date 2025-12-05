<?php
/**
 * Taxonomy Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Taxonomy {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    public function register_taxonomies() {
        // Tags taxonomy
        register_taxonomy('snn_tag', 'snn_note', array(
            'labels' => array(
                'name' => __('Tags', 'snn-notes'),
                'singular_name' => __('Tag', 'snn-notes'),
                'search_items' => __('Search Tags', 'snn-notes'),
                'all_items' => __('All Tags', 'snn-notes'),
                'edit_item' => __('Edit Tag', 'snn-notes'),
                'update_item' => __('Update Tag', 'snn-notes'),
                'add_new_item' => __('Add New Tag', 'snn-notes'),
                'new_item_name' => __('New Tag Name', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => true,
            'rest_base' => 'snn-tags',
            'hierarchical' => false,
            'rewrite' => false,
        ));
        
        // Folders taxonomy (hierarchical)
        register_taxonomy('snn_folder', 'snn_note', array(
            'labels' => array(
                'name' => __('Folders', 'snn-notes'),
                'singular_name' => __('Folder', 'snn-notes'),
                'search_items' => __('Search Folders', 'snn-notes'),
                'all_items' => __('All Folders', 'snn-notes'),
                'parent_item' => __('Parent Folder', 'snn-notes'),
                'parent_item_colon' => __('Parent Folder:', 'snn-notes'),
                'edit_item' => __('Edit Folder', 'snn-notes'),
                'update_item' => __('Update Folder', 'snn-notes'),
                'add_new_item' => __('Add New Folder', 'snn-notes'),
                'new_item_name' => __('New Folder Name', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => true,
            'rest_base' => 'snn-folders',
            'hierarchical' => true,
            'rewrite' => false,
        ));
    }
}
