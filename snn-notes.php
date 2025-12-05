<?php
/**
 * Plugin Name: SNN Notes
 * Plugin URI: https://sinanisler.com
 * Description: A modern, compact note-keeping system for WordPress admin with tags, sidebar navigation, and a clean editor interface.
 * Version: 0.1
 * Author: sinanisler
 * Author URI: https://sinanisler.com
 * License: GPL v2 or later
 * Text Domain: snn
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SNN_NOTES_VERSION', '0.1');
define('SNN_NOTES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SNN_NOTES_PLUGIN_URL', plugin_dir_url(__FILE__));

class SNN_Notes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_snn_create_note', array($this, 'ajax_create_note'));
        add_action('wp_ajax_snn_save_note', array($this, 'ajax_save_note'));
        add_action('wp_ajax_snn_delete_note', array($this, 'ajax_delete_note'));
        add_action('wp_ajax_snn_get_note', array($this, 'ajax_get_note'));
        add_action('wp_ajax_snn_get_notes', array($this, 'ajax_get_notes'));
        add_action('wp_ajax_snn_create_tag', array($this, 'ajax_create_tag'));
        add_action('wp_ajax_snn_delete_tag', array($this, 'ajax_delete_tag'));
        add_action('wp_ajax_snn_get_tags', array($this, 'ajax_get_tags'));
        add_action('wp_ajax_snn_assign_tag', array($this, 'ajax_assign_tag'));
    }
    
    public function register_post_type() {
        register_post_type('snn_note', array(
            'labels' => array(
                'name' => __('SNN Notes', 'snn-notes'),
                'singular_name' => __('Note', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'author'),
            'taxonomies' => array('snn_tag'),
        ));
    }
    
    public function register_taxonomy() {
        register_taxonomy('snn_tag', 'snn_note', array(
            'labels' => array(
                'name' => __('Tags', 'snn-notes'),
                'singular_name' => __('Tag', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'hierarchical' => false,
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SNN Notes', 'snn-notes'),
            __('SNN Notes', 'snn-notes'),
            'edit_posts',
            'snn-notes',
            array($this, 'render_admin_page'),
            'dashicons-edit-page',
            30
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_snn-notes') {
            return;
        }
        
        // Quill Editor (modern, simple WYSIWYG)
        wp_enqueue_style('quill-css', 'https://cdn.quilljs.com/1.3.7/quill.snow.css', array(), '1.3.7');
        wp_enqueue_script('quill-js', 'https://cdn.quilljs.com/1.3.7/quill.min.js', array(), '1.3.7', true);
        
        // SortableJS for drag and drop
        wp_enqueue_script('sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true);
        
        // Custom styles
        wp_enqueue_style('snn-notes-css', SNN_NOTES_PLUGIN_URL . 'assets/css/admin-style.css', array(), SNN_NOTES_VERSION);
        
        // Custom scripts
        wp_enqueue_script('snn-notes-js', SNN_NOTES_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'quill-js', 'sortable-js'), SNN_NOTES_VERSION, true);
        
        wp_localize_script('snn-notes-js', 'snnNotes', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('snn_notes_nonce'),
        ));
    }
    
    public function render_admin_page() {
        include SNN_NOTES_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    // AJAX Handlers
    
    public function ajax_create_note() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $note_id = wp_insert_post(array(
            'post_type' => 'snn_note',
            'post_title' => __('Untitled Note', 'snn-notes'),
            'post_content' => '',
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($note_id)) {
            wp_send_json_error($note_id->get_error_message());
        }
        
        wp_send_json_success(array(
            'id' => $note_id,
            'title' => get_the_title($note_id),
            'content' => '',
            'date' => get_the_date('M j, Y', $note_id),
            'tags' => array(),
        ));
    }
    
    public function ajax_save_note() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $note_id = intval($_POST['note_id']);
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        
        $result = wp_update_post(array(
            'ID' => $note_id,
            'post_title' => $title,
            'post_content' => $content,
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'id' => $note_id,
            'title' => $title,
            'date' => get_the_date('M j, Y', $note_id),
        ));
    }
    
    public function ajax_delete_note() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $note_id = intval($_POST['note_id']);
        $result = wp_delete_post($note_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete note');
        }
        
        wp_send_json_success();
    }
    
    public function ajax_get_note() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $note_id = intval($_POST['note_id']);
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            wp_send_json_error('Note not found');
        }
        
        $tags = wp_get_post_terms($note_id, 'snn_tag');
        $tag_data = array();
        foreach ($tags as $tag) {
            $tag_data[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'color' => get_term_meta($tag->term_id, 'color', true) ?: '#3b82f6',
            );
        }
        
        wp_send_json_success(array(
            'id' => $note->ID,
            'title' => $note->post_title,
            'content' => $note->post_content,
            'date' => get_the_date('M j, Y', $note_id),
            'tags' => $tag_data,
        ));
    }
    
    public function ajax_get_notes() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $args = array(
            'post_type' => 'snn_note',
            'post_status' => 'private',
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
        );
        
        $notes = get_posts($args);
        $notes_data = array();
        
        foreach ($notes as $note) {
            $tags = wp_get_post_terms($note->ID, 'snn_tag');
            $tag_data = array();
            foreach ($tags as $tag) {
                $tag_data[] = array(
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'color' => get_term_meta($tag->term_id, 'color', true) ?: '#3b82f6',
                );
            }
            
            $notes_data[] = array(
                'id' => $note->ID,
                'title' => $note->post_title,
                'date' => get_the_date('M j, Y', $note->ID),
                'excerpt' => wp_trim_words(strip_tags($note->post_content), 10),
                'tags' => $tag_data,
            );
        }
        
        wp_send_json_success($notes_data);
    }
    
    public function ajax_create_tag() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $name = sanitize_text_field($_POST['name']);
        $color = sanitize_hex_color($_POST['color']);
        
        if (empty($name)) {
            wp_send_json_error('Tag name is required');
        }
        
        $term = wp_insert_term($name, 'snn_tag');
        
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }
        
        if ($color) {
            update_term_meta($term['term_id'], 'color', $color);
        }
        
        wp_send_json_success(array(
            'id' => $term['term_id'],
            'name' => $name,
            'color' => $color ?: '#3b82f6',
        ));
    }
    
    public function ajax_delete_tag() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $tag_id = intval($_POST['tag_id']);
        $result = wp_delete_term($tag_id, 'snn_tag');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
    
    public function ajax_get_tags() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $tags = get_terms(array(
            'taxonomy' => 'snn_tag',
            'hide_empty' => false,
        ));
        
        $tags_data = array();
        foreach ($tags as $tag) {
            $tags_data[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'color' => get_term_meta($tag->term_id, 'color', true) ?: '#3b82f6',
                'count' => $tag->count,
            );
        }
        
        wp_send_json_success($tags_data);
    }
    
    public function ajax_assign_tag() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $note_id = intval($_POST['note_id']);
        $tag_ids = array_map('intval', $_POST['tag_ids']);
        
        $result = wp_set_post_terms($note_id, $tag_ids, 'snn_tag');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
}

// Initialize plugin
SNN_Notes::get_instance();
