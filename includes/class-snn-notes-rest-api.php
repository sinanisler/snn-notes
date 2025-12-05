<?php
/**
 * REST API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_REST_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        $namespace = 'snn-notes/v1';
        
        // Notes endpoints
        register_rest_route($namespace, '/notes', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_notes'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_note'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));
        
        register_rest_route($namespace, '/notes/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_note'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_note'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_note'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));
        
        // Export endpoint
        register_rest_route($namespace, '/notes/(?P<id>\d+)/export', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_note'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'format' => array(
                    'required' => false,
                    'default' => 'json',
                    'enum' => array('json', 'markdown', 'html', 'pdf'),
                ),
            ),
        ));
    }
    
    public function check_permission() {
        return current_user_can('edit_posts');
    }
    
    public function get_notes($request) {
        // Implementation similar to AJAX handler
        $notes = get_posts(array(
            'post_type' => 'snn_note',
            'post_status' => 'private',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
        ));
        
        return rest_ensure_response($notes);
    }
    
    public function get_note($request) {
        $note_id = $request['id'];
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            return new WP_Error('not_found', __('Note not found', 'snn-notes'), array('status' => 404));
        }

        // Security Check
        if ($note->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
             return new WP_Error('forbidden', __('You do not have permission to view this note', 'snn-notes'), array('status' => 403));
        }
        
        return rest_ensure_response($note);
    }
    
    public function create_note($request) {
        // Implementation
        return rest_ensure_response(array('success' => true));
    }
    
    public function update_note($request) {
        // Implementation
        return rest_ensure_response(array('success' => true));
    }
    
    public function delete_note($request) {
        // Implementation
        return rest_ensure_response(array('success' => true));
    }
    
    public function export_note($request) {
        $note_id = $request['id'];
        $format = $request['format'];
        
        require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-export.php';
        $exporter = SNN_Notes_Export::get_instance();
        
        return $exporter->export_note($note_id, $format);
    }
}
