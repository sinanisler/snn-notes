<?php
/**
 * Templates Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_Notes_Templates {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_template_post_type'));
        add_action('init', array($this, 'register_default_templates'));
    }
    
    public function register_template_post_type() {
        register_post_type('snn_template', array(
            'labels' => array(
                'name' => __('Templates', 'snn-notes'),
                'singular_name' => __('Template', 'snn-notes'),
            ),
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'author'),
        ));
    }
    
    public function register_default_templates() {
        $installed = get_option('snn_notes_default_templates_installed');
        
        if ($installed) {
            return;
        }
        
        $templates = array(
            array(
                'title' => __('Meeting Notes', 'snn-notes'),
                'content' => '<h2>Meeting Title</h2><p><strong>Date:</strong> </p><p><strong>Attendees:</strong> </p><p><strong>Agenda:</strong></p><ul><li></li></ul><p><strong>Notes:</strong></p><p></p><p><strong>Action Items:</strong></p><ul><li></li></ul>',
            ),
            array(
                'title' => __('Project Plan', 'snn-notes'),
                'content' => '<h2>Project Name</h2><p><strong>Objective:</strong> </p><p><strong>Timeline:</strong> </p><p><strong>Tasks:</strong></p><ul><li></li></ul><p><strong>Resources:</strong></p><p></p><p><strong>Milestones:</strong></p><ul><li></li></ul>',
            ),
            array(
                'title' => __('To-Do List', 'snn-notes'),
                'content' => '<h2>To-Do</h2><ul><li>Task 1</li><li>Task 2</li><li>Task 3</li></ul>',
            ),
            array(
                'title' => __('Daily Journal', 'snn-notes'),
                'content' => '<h2>Daily Journal</h2><p><strong>Date:</strong> </p><p><strong>Mood:</strong> </p><p><strong>Today I:</strong></p><p></p><p><strong>Grateful for:</strong></p><ul><li></li></ul><p><strong>Tomorrow\'s goals:</strong></p><ul><li></li></ul>',
            ),
        );
        
        foreach ($templates as $template) {
            wp_insert_post(array(
                'post_type' => 'snn_template',
                'post_title' => $template['title'],
                'post_content' => $template['content'],
                'post_status' => 'publish',
                'post_author' => 1,
            ));
        }
        
        update_option('snn_notes_default_templates_installed', true);
    }
}
