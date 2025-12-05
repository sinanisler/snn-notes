<?php
/**
 * Export/Import Functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Export {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_snn_export_note', array($this, 'ajax_export_note'));
        add_action('wp_ajax_snn_export_all_notes', array($this, 'ajax_export_all_notes'));
        add_action('wp_ajax_snn_import_notes', array($this, 'ajax_import_notes'));
    }
    
    public function ajax_export_note() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        
        $export_data = $this->export_note($note_id, $format);
        
        wp_send_json_success($export_data);
    }
    
    public function export_note($note_id, $format = 'json') {
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            return new WP_Error('not_found', __('Note not found', 'snn-notes'));
        }

        // Security Check
        if ($note->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
             return new WP_Error('forbidden', __('You do not have permission to export this note', 'snn-notes'));
        }
        
        $tags = wp_get_post_terms($note_id, 'snn_tag', array('fields' => 'names'));
        
        switch ($format) {
            case 'markdown':
                return $this->export_as_markdown($note, $tags);
            
            case 'html':
                return $this->export_as_html($note, $tags);
            
            case 'pdf':
                return $this->export_as_pdf($note, $tags);
            
            default:
                return $this->export_as_json($note, $tags);
        }
    }
    
    private function export_as_json($note, $tags) {
        return array(
            'title' => $note->post_title,
            'content' => $note->post_content,
            'date' => $note->post_date,
            'modified' => $note->post_modified,
            'tags' => $tags,
        );
    }
    
    private function export_as_markdown($note, $tags) {
        $markdown = "# {$note->post_title}\n\n";
        
        if (!empty($tags)) {
            $markdown .= "**Tags:** " . implode(', ', $tags) . "\n\n";
        }
        
        $markdown .= "**Date:** " . get_the_date('F j, Y', $note->ID) . "\n\n";
        $markdown .= "---\n\n";
        
        // Convert HTML to Markdown (basic conversion)
        $content = strip_tags($note->post_content, '<strong><em><a><ul><ol><li><h1><h2><h3><code><pre>');
        $content = str_replace('<strong>', '**', $content);
        $content = str_replace('</strong>', '**', $content);
        $content = str_replace('<em>', '*', $content);
        $content = str_replace('</em>', '*', $content);
        
        $markdown .= $content;
        
        return $markdown;
    }
    
    private function export_as_html($note, $tags) {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<meta charset='UTF-8'>\n";
        $html .= "<title>{$note->post_title}</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}h1{color:#333;}.meta{color:#666;font-size:14px;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>{$note->post_title}</h1>\n";
        
        if (!empty($tags)) {
            $html .= "<p class='meta'><strong>Tags:</strong> " . implode(', ', $tags) . "</p>\n";
        }
        
        $html .= "<p class='meta'><strong>Date:</strong> " . get_the_date('F j, Y', $note->ID) . "</p>\n";
        $html .= "<hr>\n";
        $html .= $note->post_content;
        $html .= "\n</body>\n</html>";
        
        return $html;
    }
    
    private function export_as_pdf($note, $tags) {
        // For PDF export, we'll return HTML that can be converted
        // In a real implementation, you'd use a library like TCPDF or Dompdf
        return array(
            'format' => 'pdf',
            'html' => $this->export_as_html($note, $tags),
            'message' => __('PDF export requires additional libraries. Returning HTML instead.', 'snn-notes'),
        );
    }
    
    public function ajax_export_all_notes() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $notes = get_posts(array(
            'post_type' => 'snn_note',
            'post_status' => 'private',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
        ));
        
        $export_data = array();
        
        foreach ($notes as $note) {
            $tags = wp_get_post_terms($note->ID, 'snn_tag', array('fields' => 'names'));
            $export_data[] = $this->export_as_json($note, $tags);
        }
        
        wp_send_json_success($export_data);
    }
    
    public function ajax_import_notes() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $notes_data = isset($_POST['notes']) ? json_decode(stripslashes($_POST['notes']), true) : array();
        
        if (empty($notes_data) || !is_array($notes_data)) {
            wp_send_json_error(__('Invalid import data', 'snn-notes'));
        }
        
        $imported = 0;
        
        foreach ($notes_data as $note_data) {
            $note_id = wp_insert_post(array(
                'post_type' => 'snn_note',
                'post_title' => sanitize_text_field($note_data['title']),
                'post_content' => wp_kses_post($note_data['content']),
                'post_status' => 'private',
                'post_author' => get_current_user_id(),
            ));
            
            if (!is_wp_error($note_id) && isset($note_data['tags']) && is_array($note_data['tags'])) {
                wp_set_post_terms($note_id, $note_data['tags'], 'snn_tag');
                $imported++;
            }
        }
        
        wp_send_json_success(array(
            'imported' => $imported,
            'total' => count($notes_data),
        ));
    }
}
