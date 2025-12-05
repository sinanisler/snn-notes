<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$user_id = get_current_user_id();

// Get statistics
$total_notes = wp_count_posts('snn_note');
$tags_count = wp_count_terms('snn_tag');
$folders_count = wp_count_terms('snn_folder');

$total_words = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(word_count) FROM {$wpdb->prefix}snn_note_stats ns 
    INNER JOIN {$wpdb->posts} p ON ns.note_id = p.ID 
    WHERE p.post_author = %d AND p.post_status = 'private'",
    $user_id
));

$most_viewed = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, ns.views 
    FROM {$wpdb->prefix}snn_note_stats ns
    INNER JOIN {$wpdb->posts} p ON ns.note_id = p.ID
    WHERE p.post_author = %d AND p.post_status = 'private'
    ORDER BY ns.views DESC
    LIMIT 5",
    $user_id
));
?>

<div class="wrap">
    <h1><?php _e('SNN Notes Statistics', 'snn-notes'); ?></h1>
    
    <div class="snn-stats-grid">
        <div class="snn-stat-card">
            <div class="snn-stat-icon dashicons dashicons-edit-page"></div>
            <div class="snn-stat-content">
                <h2><?php echo esc_html($total_notes->private ?? 0); ?></h2>
                <p><?php _e('Total Notes', 'snn-notes'); ?></p>
            </div>
        </div>
        
        <div class="snn-stat-card">
            <div class="snn-stat-icon dashicons dashicons-tag"></div>
            <div class="snn-stat-content">
                <h2><?php echo esc_html($tags_count); ?></h2>
                <p><?php _e('Tags', 'snn-notes'); ?></p>
            </div>
        </div>
        
        <div class="snn-stat-card">
            <div class="snn-stat-icon dashicons dashicons-category"></div>
            <div class="snn-stat-content">
                <h2><?php echo esc_html($folders_count); ?></h2>
                <p><?php _e('Folders', 'snn-notes'); ?></p>
            </div>
        </div>
        
        <div class="snn-stat-card">
            <div class="snn-stat-icon dashicons dashicons-book"></div>
            <div class="snn-stat-content">
                <h2><?php echo esc_html(number_format($total_words ?? 0)); ?></h2>
                <p><?php _e('Total Words', 'snn-notes'); ?></p>
            </div>
        </div>
    </div>
    
    <h2><?php _e('Most Viewed Notes', 'snn-notes'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Note Title', 'snn-notes'); ?></th>
                <th><?php _e('Views', 'snn-notes'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($most_viewed)) : ?>
                <?php foreach ($most_viewed as $note) : ?>
                    <tr>
                        <td><?php echo esc_html($note->post_title); ?></td>
                        <td><?php echo esc_html($note->views); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2"><?php _e('No data available yet', 'snn-notes'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.snn-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.snn-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.snn-stat-icon {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #3b82f6;
}

.snn-stat-content h2 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.snn-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}
</style>
