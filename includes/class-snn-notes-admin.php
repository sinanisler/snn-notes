<?php
/**
 * Admin Page Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
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
        
        add_submenu_page(
            'snn-notes',
            __('Settings', 'snn-notes'),
            __('Settings', 'snn-notes'),
            'manage_options',
            'snn-notes-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'snn-notes',
            __('Statistics', 'snn-notes'),
            __('Statistics', 'snn-notes'),
            'edit_posts',
            'snn-notes-stats',
            array($this, 'render_stats_page')
        );
    }
    
    public function render_admin_page() {
        include SNN_NOTES_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    public function render_settings_page() {
        include SNN_NOTES_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    public function render_stats_page() {
        include SNN_NOTES_PLUGIN_DIR . 'templates/stats-page.php';
    }
}
