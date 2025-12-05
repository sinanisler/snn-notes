<?php
/**
 * Plugin Name: SNN Notes
 * Plugin URI: https://sinanisler.com
 * Description: A modern, feature-rich note-keeping system for WordPress admin with tags, folders, templates, search, export, revisions, and collaboration features.
 * Version: 1.0.0
 * Author: sinanisler
 * Author URI: https://sinanisler.com
 * License: GPL v2 or later
 * Text Domain: snn-notes
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('SNN_NOTES_VERSION', '1.0.0');
define('SNN_NOTES_PLUGIN_FILE', __FILE__);
define('SNN_NOTES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SNN_NOTES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SNN_NOTES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load Core
require_once SNN_NOTES_PLUGIN_DIR . 'includes/class-snn-notes-core.php';

// Initialize Plugin
SNN_Notes_Core::get_instance();
