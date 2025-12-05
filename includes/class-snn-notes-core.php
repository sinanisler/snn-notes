<?php
/**
 * Core Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Core {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-post-type.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-taxonomy.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-admin.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-ajax.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-rest-api.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-assets.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-settings.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-export.php';
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-templates.php';
    }
    
    private function init_hooks() {
        // Initialize components
        SNN_Notes_Post_Type::get_instance();
        SNN_Notes_Taxonomy::get_instance();
        SNN_Notes_Admin::get_instance();
        SNN_Notes_Ajax::get_instance();
        SNN_Notes_REST_API::get_instance();
        SNN_Notes_Assets::get_instance();
        SNN_Notes_Settings::get_instance();
        SNN_Notes_Export::get_instance();
        SNN_Notes_Templates::get_instance();
        
        // Activation/Deactivation hooks
        register_activation_hook(SNN_NOTES_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SNN_NOTES_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Add admin bar menu
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }
    
    public function activate() {
        // Create custom database tables if needed
        $this->create_tables();
        
        // Set default options
        if (!get_option('snn_notes_version')) {
            add_option('snn_notes_version', SNN_NOTES_VERSION);
            add_option('snn_notes_settings', array(
                'enable_trash' => true,
                'enable_revisions' => true,
                'auto_save_interval' => 2000,
                'notes_per_page' => 50,
                'default_view' => 'list',
                'enable_encryption' => false,
                'theme' => 'light',
            ));
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for note revisions
        $table_name = $wpdb->prefix . 'snn_note_revisions';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            note_id bigint(20) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY note_id (note_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Table for note statistics
        $table_name = $wpdb->prefix . 'snn_note_stats';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            note_id bigint(20) NOT NULL,
            views int(11) DEFAULT 0,
            last_viewed datetime DEFAULT NULL,
            word_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY note_id (note_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Table for shared notes
        $table_name = $wpdb->prefix . 'snn_note_shares';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            note_id bigint(20) NOT NULL,
            share_token varchar(64) NOT NULL,
            shared_by bigint(20) NOT NULL,
            shared_with bigint(20) DEFAULT NULL,
            permission varchar(20) DEFAULT 'view',
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY share_token (share_token),
            KEY note_id (note_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('snn-notes', false, dirname(plugin_basename(SNN_NOTES_PLUGIN_FILE)) . '/languages/');
    }
    
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $args = array(
            'id' => 'snn-notes-quick',
            'title' => '<span class="ab-icon dashicons dashicons-edit-page"></span><span class="ab-label">' . __('New Note', 'snn-notes') . '</span>',
            'href' => admin_url('admin.php?page=snn-notes&action=new'),
            'meta' => array(
                'class' => 'snn-notes-admin-bar',
                'title' => __('Create a new note', 'snn-notes'),
            ),
        );
        
        $wp_admin_bar->add_node($args);
    }
}
