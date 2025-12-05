<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('SNN Notes Settings', 'snn-notes'); ?></h1>
    
    <form method="post" action="options.php" id="snn-settings-form">
        <?php settings_fields('snn_notes_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Trash', 'snn-notes'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="snn_notes_settings[enable_trash]" value="1" <?php checked(get_option('snn_notes_settings')['enable_trash'] ?? true, true); ?>>
                        <?php _e('Move deleted notes to trash instead of permanently deleting', 'snn-notes'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Enable Revisions', 'snn-notes'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="snn_notes_settings[enable_revisions]" value="1" <?php checked(get_option('snn_notes_settings')['enable_revisions'] ?? true, true); ?>>
                        <?php _e('Save note history and allow restoring previous versions', 'snn-notes'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Auto-save Interval', 'snn-notes'); ?></th>
                <td>
                    <input type="number" name="snn_notes_settings[auto_save_interval]" value="<?php echo esc_attr(get_option('snn_notes_settings')['auto_save_interval'] ?? 2000); ?>" min="1000" max="10000" step="1000">
                    <p class="description"><?php _e('Milliseconds between auto-saves (1000 = 1 second)', 'snn-notes'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Notes Per Page', 'snn-notes'); ?></th>
                <td>
                    <input type="number" name="snn_notes_settings[notes_per_page]" value="<?php echo esc_attr(get_option('snn_notes_settings')['notes_per_page'] ?? 50); ?>" min="10" max="200">
                    <p class="description"><?php _e('Number of notes to load at once', 'snn-notes'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Theme', 'snn-notes'); ?></th>
                <td>
                    <select name="snn_notes_settings[theme]">
                        <option value="light" <?php selected((get_option('snn_notes_settings')['theme'] ?? 'light'), 'light'); ?>><?php _e('Light', 'snn-notes'); ?></option>
                        <option value="dark" <?php selected((get_option('snn_notes_settings')['theme'] ?? 'light'), 'dark'); ?>><?php _e('Dark', 'snn-notes'); ?></option>
                        <option value="auto" <?php selected((get_option('snn_notes_settings')['theme'] ?? 'light'), 'auto'); ?>><?php _e('Auto (System)', 'snn-notes'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('Import/Export', 'snn-notes'); ?></h2>
    
    <h3><?php _e('Export All Notes', 'snn-notes'); ?></h3>
    <p><?php _e('Download all your notes as a JSON file for backup or migration.', 'snn-notes'); ?></p>
    <button id="snn-export-all" class="button button-secondary">
        <?php _e('Export All Notes', 'snn-notes'); ?>
    </button>
    
    <h3><?php _e('Import Notes', 'snn-notes'); ?></h3>
    <p><?php _e('Import notes from a JSON file.', 'snn-notes'); ?></p>
    <input type="file" id="snn-import-file" accept=".json">
    <button id="snn-import-notes" class="button button-secondary">
        <?php _e('Import Notes', 'snn-notes'); ?>
    </button>
    
    <hr>
    
    <h2><?php _e('Danger Zone', 'snn-notes'); ?></h2>
    <p><?php _e('These actions cannot be undone.', 'snn-notes'); ?></p>
    <button id="snn-empty-trash" class="button button-secondary">
        <?php _e('Empty Trash', 'snn-notes'); ?>
    </button>
</div>

<script>
jQuery(document).ready(function($) {
    $('#snn-export-all').on('click', function() {
        $.ajax({
            url: snnNotes.ajaxUrl,
            type: 'POST',
            data: {
                action: 'snn_export_all_notes',
                nonce: snnNotes.nonce
            },
            success: function(response) {
                if (response.success) {
                    const dataStr = JSON.stringify(response.data, null, 2);
                    const dataBlob = new Blob([dataStr], { type: 'application/json' });
                    const url = URL.createObjectURL(dataBlob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'snn-notes-export-' + new Date().toISOString().split('T')[0] + '.json';
                    link.click();
                    URL.revokeObjectURL(url);
                }
            }
        });
    });
    
    $('#snn-import-notes').on('click', function() {
        const file = $('#snn-import-file')[0].files[0];
        if (!file) {
            alert('<?php _e('Please select a file to import', 'snn-notes'); ?>');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_import_notes',
                    nonce: snnNotes.nonce,
                    notes: e.target.result
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('Successfully imported', 'snn-notes'); ?> ' + response.data.imported + ' <?php _e('notes', 'snn-notes'); ?>');
                        location.reload();
                    }
                }
            });
        };
        reader.readAsText(file);
    });
});
</script>
