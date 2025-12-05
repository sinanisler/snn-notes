<?php
/**
 * Settings Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_snn_save_settings', array($this, 'ajax_save_settings'));
    }
    
    public function register_settings() {
        register_setting('snn_notes_settings', 'snn_notes_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enable_trash'] = isset($input['enable_trash']) ? (bool)$input['enable_trash'] : true;
        $sanitized['enable_revisions'] = isset($input['enable_revisions']) ? (bool)$input['enable_revisions'] : true;
        $sanitized['auto_save_interval'] = isset($input['auto_save_interval']) ? absint($input['auto_save_interval']) : 2000;
        $sanitized['notes_per_page'] = isset($input['notes_per_page']) ? absint($input['notes_per_page']) : 50;
        $sanitized['default_view'] = isset($input['default_view']) ? sanitize_text_field($input['default_view']) : 'list';
        $sanitized['enable_encryption'] = isset($input['enable_encryption']) ? (bool)$input['enable_encryption'] : false;
        $sanitized['theme'] = isset($input['theme']) ? sanitize_text_field($input['theme']) : 'light';
        
        return $sanitized;
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_settings($settings);
        
        update_option('snn_notes_settings', $sanitized);
        
        wp_send_json_success($sanitized);
    }
}
