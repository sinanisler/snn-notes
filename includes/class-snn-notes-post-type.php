<?php
/**
 * Post Type Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Post_Type {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_post_statuses'));
    }
    
    public function register_post_type() {
        register_post_type('snn_note', array(
            'labels' => array(
                'name' => __('SNN Notes', 'snn-notes'),
                'singular_name' => __('Note', 'snn-notes'),
                'add_new' => __('Add New', 'snn-notes'),
                'add_new_item' => __('Add New Note', 'snn-notes'),
                'edit_item' => __('Edit Note', 'snn-notes'),
                'new_item' => __('New Note', 'snn-notes'),
                'view_item' => __('View Note', 'snn-notes'),
                'search_items' => __('Search Notes', 'snn-notes'),
                'not_found' => __('No notes found', 'snn-notes'),
                'not_found_in_trash' => __('No notes found in Trash', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rest_base' => 'snn-notes',
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'author', 'revisions'),
            'taxonomies' => array('snn_tag', 'snn_folder'),
            'has_archive' => false,
            'rewrite' => false,
        ));
    }
    
    public function register_post_statuses() {
        // Archive status
        register_post_status('snn_archived', array(
            'label' => _x('Archived', 'post status', 'snn-notes'),
            'public' => false,
            'internal' => true,
            'protected' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'snn-notes'),
        ));
    }
}
