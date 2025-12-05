<?php
/**
 * AJAX Handler Class - Improved Security
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Note operations
        add_action('wp_ajax_snn_create_note', array($this, 'create_note'));
        add_action('wp_ajax_snn_save_note', array($this, 'save_note'));
        add_action('wp_ajax_snn_delete_note', array($this, 'delete_note'));
        add_action('wp_ajax_snn_get_note', array($this, 'get_note'));
        add_action('wp_ajax_snn_get_notes', array($this, 'get_notes'));
        add_action('wp_ajax_snn_duplicate_note', array($this, 'duplicate_note'));
        add_action('wp_ajax_snn_restore_note', array($this, 'restore_note'));
        add_action('wp_ajax_snn_pin_note', array($this, 'pin_note'));
        add_action('wp_ajax_snn_archive_note', array($this, 'archive_note'));
        
        // Tag operations
        add_action('wp_ajax_snn_create_tag', array($this, 'create_tag'));
        add_action('wp_ajax_snn_delete_tag', array($this, 'delete_tag'));
        add_action('wp_ajax_snn_get_tags', array($this, 'get_tags'));
        add_action('wp_ajax_snn_assign_tag', array($this, 'assign_tag'));
        add_action('wp_ajax_snn_rename_tag', array($this, 'rename_tag'));
        add_action('wp_ajax_snn_merge_tags', array($this, 'merge_tags'));
        
        // Folder operations
        add_action('wp_ajax_snn_create_folder', array($this, 'create_folder'));
        add_action('wp_ajax_snn_delete_folder', array($this, 'delete_folder'));
        add_action('wp_ajax_snn_get_folders', array($this, 'get_folders'));
        add_action('wp_ajax_snn_move_to_folder', array($this, 'move_to_folder'));
        
        // Search and stats
        add_action('wp_ajax_snn_search_notes', array($this, 'search_notes'));
        add_action('wp_ajax_snn_get_stats', array($this, 'get_stats'));
        add_action('wp_ajax_snn_increment_view', array($this, 'increment_view'));
        
        // Templates
        add_action('wp_ajax_snn_save_template', array($this, 'save_template'));
        add_action('wp_ajax_snn_get_templates', array($this, 'get_templates'));
        add_action('wp_ajax_snn_delete_template', array($this, 'delete_template'));
    }
    
    private function verify_request() {
        check_ajax_referer('snn_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
            exit;
        }
    }

    private function check_ownership($note_id) {
        $note = get_post($note_id);
        if (!$note || $note->post_type !== 'snn_note') {
            wp_send_json_error(__('Note not found', 'snn-notes'), 404);
            exit;
        }

        if ($note->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
             // Check shared access if implemented, otherwise strictly deny for "private" note keeping check
             // The user requirement is "complete private wp-admin only note keeping PER USER"
             // So we strictly deny unless they are admin editing others
            wp_send_json_error(__('You do not have permission to access this note', 'snn-notes'), 403);
            exit;
        }
        return $note;
    }
    
    private function sanitize_note_data($data) {
        return array(
            'title' => isset($data['title']) ? sanitize_text_field($data['title']) : '',
            'content' => isset($data['content']) ? wp_kses_post($data['content']) : '',
        );
    }
    
    public function create_note() {
        $this->verify_request();
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        
        $note_data = array(
            'post_type' => 'snn_note',
            'post_title' => __('Untitled Note', 'snn-notes'),
            'post_content' => '',
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        );
        
        // Use template if provided
        if ($template_id) {
            $template = get_post($template_id);
            if ($template && $template->post_type === 'snn_template') {
                $note_data['post_title'] = $template->post_title;
                $note_data['post_content'] = $template->post_content;
            }
        }
        
        $note_id = wp_insert_post($note_data);
        
        if (is_wp_error($note_id)) {
            wp_send_json_error($note_id->get_error_message());
        }
        
        // Assign to folder
        if ($folder_id) {
            wp_set_post_terms($note_id, array($folder_id), 'snn_folder');
        }
        
        // Initialize stats
        $this->init_note_stats($note_id);
        
        wp_send_json_success(array(
            'id' => $note_id,
            'title' => get_the_title($note_id),
            'content' => get_post_field('post_content', $note_id),
            'date' => get_the_date('M j, Y', $note_id),
            'tags' => array(),
            'folder_id' => $folder_id,
        ));
    }
    
    public function save_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(__('Invalid note ID', 'snn-notes'));
        }
        
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            wp_send_json_error(__('Note not found', 'snn-notes'));
        }
        
        // Check ownership
        if ($note->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('You do not have permission to edit this note', 'snn-notes'), 403);
        }
        
        $sanitized = $this->sanitize_note_data($_POST);
        
        // Save revision before update
        $this->save_revision($note_id, $note->post_title, $note->post_content);
        
        $result = wp_update_post(array(
            'ID' => $note_id,
            'post_title' => $sanitized['title'],
            'post_content' => $sanitized['content'],
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update word count
        $this->update_word_count($note_id, $sanitized['content']);
        
        // Clear cache
        $this->clear_notes_cache();
        
        wp_send_json_success(array(
            'id' => $note_id,
            'title' => $sanitized['title'],
            'date' => get_the_date('M j, Y', $note_id),
        ));
    }
    
    public function delete_note() {
        $this->verify_request();
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $permanent = isset($_POST['permanent']) ? (bool)$_POST['permanent'] : false;
        
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            wp_send_json_error(__('Note not found', 'snn-notes'));
        }
        
        // Check ownership
        if ($note->post_author != get_current_user_id() && !current_user_can('delete_others_posts')) {
            wp_send_json_error(__('You do not have permission to delete this note', 'snn-notes'), 403);
        }
        
        if ($permanent) {
            $result = wp_delete_post($note_id, true);
        } else {
            $result = wp_trash_post($note_id);
        }
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete note', 'snn-notes'));
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    public function get_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $note = get_post($note_id);
        
        if (!$note || $note->post_type !== 'snn_note') {
            wp_send_json_error(__('Note not found', 'snn-notes'));
        }
        
        // Check permissions
        if ($note->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            // Check if shared
            if (!$this->has_shared_access($note_id, get_current_user_id())) {
                wp_send_json_error(__('Access denied', 'snn-notes'), 403);
            }
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
        
        $folders = wp_get_post_terms($note_id, 'snn_folder');
        $folder_id = !empty($folders) ? $folders[0]->term_id : 0;
        
        $is_pinned = get_post_meta($note_id, '_snn_pinned', true);
        $is_favorite = get_post_meta($note_id, '_snn_favorite', true);
        
        wp_send_json_success(array(
            'id' => $note->ID,
            'title' => $note->post_title,
            'content' => $note->post_content,
            'date' => get_the_date('M j, Y', $note_id),
            'modified' => get_the_modified_date('M j, Y g:i a', $note_id),
            'tags' => $tag_data,
            'folder_id' => $folder_id,
            'is_pinned' => (bool)$is_pinned,
            'is_favorite' => (bool)$is_favorite,
            'word_count' => $this->get_word_count($note_id),
        ));
    }
    
    public function get_notes() {
        $this->verify_request();
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'private';
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 50;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $tag_filter = isset($_POST['tag_id']) ? absint($_POST['tag_id']) : 0;
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'modified';
        $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'DESC';
        
        // Try cache first
        $cache_key = 'snn_notes_' . get_current_user_id() . '_' . md5(serialize($_POST));
        $cached = get_transient($cache_key);
        
        if (false !== $cached && empty($search)) {
            wp_send_json_success($cached);
            return;
        }
        
        $args = array(
            'post_type' => 'snn_note',
            'post_status' => $status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'author' => get_current_user_id(),
        );
        
        // Sorting
        switch ($sort_by) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'created':
                $args['orderby'] = 'date';
                break;
            default:
                $args['orderby'] = 'modified';
        }
        $args['order'] = in_array($sort_order, array('ASC', 'DESC')) ? $sort_order : 'DESC';
        
        // Search
        if ($search) {
            $args['s'] = $search;
        }
        
        // Folder filter
        if ($folder_id) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'snn_folder',
                    'field' => 'term_id',
                    'terms' => $folder_id,
                ),
            );
        }
        
        // Tag filter
        if ($tag_filter) {
            if (isset($args['tax_query'])) {
                $args['tax_query']['relation'] = 'AND';
            } else {
                $args['tax_query'] = array();
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'snn_tag',
                'field' => 'term_id',
                'terms' => $tag_filter,
            );
        }
        
        // Pinned notes first
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_snn_pinned',
                'compare' => 'EXISTS',
            ),
            array(
                'key' => '_snn_pinned',
                'compare' => 'NOT EXISTS',
            ),
        );
        $args['orderby'] = array(
            'meta_value' => 'DESC',
            $args['orderby'] => $args['order'],
        );
        
        $notes_query = new WP_Query($args);
        $notes_data = array();
        
        foreach ($notes_query->posts as $note) {
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
                'modified' => get_the_modified_date('M j, Y g:i a', $note->ID),
                'excerpt' => wp_trim_words(strip_tags($note->post_content), 10),
                'tags' => $tag_data,
                'is_pinned' => (bool)get_post_meta($note->ID, '_snn_pinned', true),
                'is_favorite' => (bool)get_post_meta($note->ID, '_snn_favorite', true),
                'word_count' => $this->get_word_count($note->ID),
            );
        }
        
        $result = array(
            'notes' => $notes_data,
            'total' => $notes_query->found_posts,
            'pages' => $notes_query->max_num_pages,
        );
        
        // Cache for 5 minutes
        if (empty($search)) {
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        }
        
        wp_send_json_success($result);
    }
    
    public function duplicate_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $note = $this->check_ownership($note_id);
        
        $new_note_data = array(
            'post_type' => 'snn_note',
            'post_title' => $note->post_title . ' (Copy)',
            'post_content' => $note->post_content,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        );
        
        $new_note_id = wp_insert_post($new_note_data);
        
        if (is_wp_error($new_note_id)) {
            wp_send_json_error($new_note_id->get_error_message());
        }
        
        // Copy tags
        $tags = wp_get_post_terms($note_id, 'snn_tag', array('fields' => 'ids'));
        if (!empty($tags)) {
            wp_set_post_terms($new_note_id, $tags, 'snn_tag');
        }
        
        // Copy folder
        $folders = wp_get_post_terms($note_id, 'snn_folder', array('fields' => 'ids'));
        if (!empty($folders)) {
            wp_set_post_terms($new_note_id, $folders, 'snn_folder');
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success(array(
            'id' => $new_note_id,
            'title' => $new_note_data['post_title'],
        ));
    }
    
    public function restore_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $this->check_ownership($note_id);
        
        $result = wp_untrash_post($note_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to restore note', 'snn-notes'));
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    public function pin_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $this->check_ownership($note_id);
        
        $pinned = isset($_POST['pinned']) ? (bool)$_POST['pinned'] : true;
        
        if ($pinned) {
            update_post_meta($note_id, '_snn_pinned', 1);
        } else {
            delete_post_meta($note_id, '_snn_pinned');
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success(array('pinned' => $pinned));
    }
    
    public function archive_note() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $this->check_ownership($note_id);

        $archived = isset($_POST['archived']) ? (bool)$_POST['archived'] : true;
        
        $new_status = $archived ? 'snn_archived' : 'private';
        
        wp_update_post(array(
            'ID' => $note_id,
            'post_status' => $new_status,
        ));
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    // Tag methods
    public function create_tag() {
        $this->verify_request();
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '';
        
        if (empty($name)) {
            wp_send_json_error(__('Tag name is required', 'snn-notes'));
        }
        
        $term = wp_insert_term($name, 'snn_tag');
        
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }
        
        if ($color) {
            update_term_meta($term['term_id'], 'color', $color);
        }
        update_term_meta($term['term_id'], 'created_by', get_current_user_id());
        
        wp_send_json_success(array(
            'id' => $term['term_id'],
            'name' => $name,
            'color' => $color ?: '#3b82f6',
        ));
    }
    
    public function delete_tag() {
        $this->verify_request();
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }
        
        $tag_id = isset($_POST['tag_id']) ? absint($_POST['tag_id']) : 0;
        
        // Check ownership
        $owner_id = get_term_meta($tag_id, 'created_by', true);
        if ($owner_id && $owner_id != get_current_user_id() && !current_user_can('delete_others_posts')) {
             wp_send_json_error(__('You do not have permission to delete this tag', 'snn-notes'), 403);
        }
        
        $result = wp_delete_term($tag_id, 'snn_tag');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
    
    public function get_tags() {
        $this->verify_request();
        
        $tags = get_terms(array(
            'taxonomy' => 'snn_tag',
            'hide_empty' => false,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'created_by',
                    'value' => get_current_user_id(),
                    'compare' => '='
                ),
                array(
                    'key' => 'created_by',
                    'compare' => 'NOT EXISTS' // Backward compatibility or global
                )
            )
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
    
    public function assign_tag() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $this->check_ownership($note_id);
        $tag_ids = isset($_POST['tag_ids']) ? array_map('absint', (array)$_POST['tag_ids']) : array();
        
        $result = wp_set_post_terms($note_id, $tag_ids, 'snn_tag');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    public function rename_tag() {
        $this->verify_request();
        
        $tag_id = isset($_POST['tag_id']) ? absint($_POST['tag_id']) : 0;
        $new_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($new_name)) {
            wp_send_json_error(__('Tag name is required', 'snn-notes'));
        }
        
        $result = wp_update_term($tag_id, 'snn_tag', array('name' => $new_name));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
    
    public function merge_tags() {
        $this->verify_request();
        
        $source_tag_id = isset($_POST['source_tag_id']) ? absint($_POST['source_tag_id']) : 0;
        $target_tag_id = isset($_POST['target_tag_id']) ? absint($_POST['target_tag_id']) : 0;
        
        // Get all notes with source tag
        $notes = get_posts(array(
            'post_type' => 'snn_note',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'snn_tag',
                    'field' => 'term_id',
                    'terms' => $source_tag_id,
                ),
            ),
        ));
        
        // Reassign to target tag
        foreach ($notes as $note) {
            $current_tags = wp_get_post_terms($note->ID, 'snn_tag', array('fields' => 'ids'));
            $current_tags = array_diff($current_tags, array($source_tag_id));
            $current_tags[] = $target_tag_id;
            wp_set_post_terms($note->ID, array_unique($current_tags), 'snn_tag');
        }
        
        // Delete source tag
        wp_delete_term($source_tag_id, 'snn_tag');
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    // Folder methods
    public function create_folder() {
        $this->verify_request();
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '';
        $parent = isset($_POST['parent_id']) ? absint($_POST['parent_id']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(__('Folder name is required', 'snn-notes'));
        }
        
        $term_args = array('parent' => $parent);
        $term = wp_insert_term($name, 'snn_folder', $term_args);
        
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }
        
        if ($color) {
            update_term_meta($term['term_id'], 'color', $color);
        }
        update_term_meta($term['term_id'], 'created_by', get_current_user_id());
        
        wp_send_json_success(array(
            'id' => $term['term_id'],
            'name' => $name,
            'color' => $color ?: '#6b7280',
            'parent' => $parent,
        ));
    }
    
    public function delete_folder() {
        $this->verify_request();
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        
        // Check ownership
        $owner_id = get_term_meta($folder_id, 'created_by', true);
        if ($owner_id && $owner_id != get_current_user_id() && !current_user_can('delete_others_posts')) {
             wp_send_json_error(__('You do not have permission to delete this folder', 'snn-notes'), 403);
        }

        $result = wp_delete_term($folder_id, 'snn_folder');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
    
    public function get_folders() {
        $this->verify_request();
        
        $folders = get_terms(array(
            'taxonomy' => 'snn_folder',
            'hide_empty' => false,
            'orderby' => 'name',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'created_by',
                    'value' => get_current_user_id(),
                    'compare' => '='
                ),
                array(
                    'key' => 'created_by',
                    'compare' => 'NOT EXISTS' // Backward compatibility or global
                )
            )
        ));
        
        $folders_data = array();
        foreach ($folders as $folder) {
            $folders_data[] = array(
                'id' => $folder->term_id,
                'name' => $folder->name,
                'color' => get_term_meta($folder->term_id, 'color', true) ?: '#6b7280',
                'count' => $folder->count,
                'parent' => $folder->parent,
            );
        }
        
        wp_send_json_success($folders_data);
    }
    
    public function move_to_folder() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        $this->check_ownership($note_id);
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        
        if ($folder_id) {
            $result = wp_set_post_terms($note_id, array($folder_id), 'snn_folder');
        } else {
            $result = wp_set_post_terms($note_id, array(), 'snn_folder');
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $this->clear_notes_cache();
        
        wp_send_json_success();
    }
    
    // Search
    public function search_notes() {
        $this->verify_request();
        
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (empty($search_term)) {
            wp_send_json_error(__('Search term required', 'snn-notes'));
        }
        
        $args = array(
            'post_type' => 'snn_note',
            'post_status' => 'private',
            'posts_per_page' => 20,
            's' => $search_term,
            'author' => get_current_user_id(),
        );
        
        $notes = get_posts($args);
        $results = array();
        
        foreach ($notes as $note) {
            $results[] = array(
                'id' => $note->ID,
                'title' => $note->post_title,
                'excerpt' => wp_trim_words(strip_tags($note->post_content), 20),
                'date' => get_the_date('M j, Y', $note->ID),
            );
        }
        
        wp_send_json_success($results);
    }
    
    // Stats
    public function get_stats() {
        $this->verify_request();
        
        $user_id = get_current_user_id();
        
        $total_notes = wp_count_posts('snn_note');
        $tags = get_terms(array('taxonomy' => 'snn_tag', 'hide_empty' => false));
        
        global $wpdb;
        $total_words = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(word_count) FROM {$wpdb->prefix}snn_note_stats ns 
            INNER JOIN {$wpdb->posts} p ON ns.note_id = p.ID 
            WHERE p.post_author = %d AND p.post_status = 'private'",
            $user_id
        ));
        
        wp_send_json_success(array(
            'total_notes' => $total_notes->private,
            'total_tags' => count($tags),
            'total_words' => (int)$total_words,
            'archived' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'snn_note' AND post_status = 'snn_archived' AND post_author = %d",
                $user_id
            )),
        ));
    }
    
    public function increment_view() {
        $this->verify_request();
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        global $wpdb;
        $table = $wpdb->prefix . 'snn_note_stats';
        
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (note_id, views, last_viewed) VALUES (%d, 1, NOW()) 
            ON DUPLICATE KEY UPDATE views = views + 1, last_viewed = NOW()",
            $note_id
        ));
        
        wp_send_json_success();
    }
    
    // Templates
    public function save_template() {
        $this->verify_request();
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        
        if (empty($name)) {
            wp_send_json_error(__('Template name is required', 'snn-notes'));
        }
        
        $template_id = wp_insert_post(array(
            'post_type' => 'snn_template',
            'post_title' => $name,
            'post_content' => $content,
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($template_id)) {
            wp_send_json_error($template_id->get_error_message());
        }
        
        wp_send_json_success(array('id' => $template_id));
    }
    
    public function get_templates() {
        $this->verify_request();
        
        $templates = get_posts(array(
            'post_type' => 'snn_template',
            'post_status' => 'private',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
        ));
        
        $templates_data = array();
        foreach ($templates as $template) {
            $templates_data[] = array(
                'id' => $template->ID,
                'name' => $template->post_title,
                'excerpt' => wp_trim_words(strip_tags($template->post_content), 10),
            );
        }
        
        wp_send_json_success($templates_data);
    }
    
    public function delete_template() {
        $this->verify_request();
        
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $template = get_post($template_id);
        
        if (!$template || $template->post_type !== 'snn_template') {
            wp_send_json_error(__('Template not found', 'snn-notes'));
        }
        
        if ($template->post_author != get_current_user_id() && !current_user_can('delete_others_posts')) {
            wp_send_json_error(__('Unauthorized', 'snn-notes'), 403);
        }

        $result = wp_delete_post($template_id, true);
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete template', 'snn-notes'));
        }
        
        wp_send_json_success();
    }
    
    // Helper methods
    private function save_revision($note_id, $title, $content) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'snn_note_revisions';
        
        $wpdb->insert($table, array(
            'note_id' => $note_id,
            'title' => $title,
            'content' => $content,
            'created_by' => get_current_user_id(),
        ));
    }
    
    private function init_note_stats($note_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'snn_note_stats';
        
        $wpdb->insert($table, array(
            'note_id' => $note_id,
            'views' => 0,
            'word_count' => 0,
        ));
    }
    
    private function update_word_count($note_id, $content) {
        global $wpdb;
        
        $word_count = str_word_count(strip_tags($content));
        
        $table = $wpdb->prefix . 'snn_note_stats';
        
        $wpdb->update(
            $table,
            array('word_count' => $word_count),
            array('note_id' => $note_id)
        );
    }
    
    private function get_word_count($note_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'snn_note_stats';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT word_count FROM $table WHERE note_id = %d",
            $note_id
        ));
        
        return (int)$count;
    }
    
    private function has_shared_access($note_id, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'snn_note_shares';
        
        $share = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE note_id = %d AND (shared_with = %d OR shared_with IS NULL) 
            AND (expires_at IS NULL OR expires_at > NOW())",
            $note_id,
            $user_id
        ));
        
        return !empty($share);
    }
    
    private function clear_notes_cache() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_snn_notes_%' 
            OR option_name LIKE '_transient_timeout_snn_notes_%'"
        );
    }
}
