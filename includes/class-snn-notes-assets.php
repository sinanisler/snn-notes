<?php
/**
 * Asset Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Assets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_snn-notes') {
            return;
        }
        
        // Quill Editor
        wp_enqueue_style('quill-css', 'https://cdn.quilljs.com/1.3.7/quill.snow.css', array(), '1.3.7');
        wp_enqueue_script('quill-js', 'https://cdn.quilljs.com/1.3.7/quill.min.js', array(), '1.3.7', true);
        
        // SortableJS
        wp_enqueue_script('sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true);
        
        // Quill Image Resize
        wp_enqueue_script('quill-image-resize', 'https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js', array('quill-js'), '3.0.0', true);
        
        // Custom styles
        wp_enqueue_style('snn-notes-css', SNN_NOTES_PLUGIN_URL . 'assets/css/admin-style.css', array(), SNN_NOTES_VERSION);
        
        // Custom scripts
        wp_enqueue_script('snn-notes-js', SNN_NOTES_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'quill-js', 'sortable-js'), SNN_NOTES_VERSION, true);
        
        // Localize script
        $settings = get_option('snn_notes_settings', array());
        
        wp_localize_script('snn-notes-js', 'snnNotes', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('snn_notes_nonce'),
            'restUrl' => rest_url('snn-notes/v1'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'settings' => $settings,
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this note?', 'snn-notes'),
                'confirmDeleteTag' => __('Delete this tag? It will be removed from all notes.', 'snn-notes'),
                'confirmDeleteFolder' => __('Delete this folder? Notes will not be deleted.', 'snn-notes'),
                'saving' => __('Saving...', 'snn-notes'),
                'saved' => __('Saved', 'snn-notes'),
                'error' => __('Error', 'snn-notes'),
                'noNotesFound' => __('No notes found', 'snn-notes'),
                'searchPlaceholder' => __('Search notes...', 'snn-notes'),
                'newNote' => __('New Note', 'snn-notes'),
                'newTag' => __('New Tag', 'snn-notes'),
                'newFolder' => __('New Folder', 'snn-notes'),
            ),
            'keyboard' => array(
                'enabled' => true,
                'shortcuts' => array(
                    'newNote' => 'ctrl+n',
                    'save' => 'ctrl+s',
                    'search' => 'ctrl+k',
                    'toggleSidebar' => 'ctrl+/',
                ),
            ),
        ));
    }
}
