# SNN Notes Plugin - Development Documentation

## Plugin Overview
**Plugin Name:** SNN Notes  
**Version:** 1.0.0  
**Description:** A modern, feature-rich note-keeping system for WordPress admin with tags, folders, templates, search, export, revisions, and collaboration features.  
**Text Domain:** snn-notes  
**Author:** sinanisler

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [File Structure](#file-structure)
3. [Core Classes & Functions](#core-classes--functions)
4. [AJAX Endpoints](#ajax-endpoints)
5. [Database Schema](#database-schema)
6. [Frontend JavaScript API](#frontend-javascript-api)
7. [Development Guidelines](#development-guidelines)
8. [Adding New Features](#adding-new-features)

---

## Architecture Overview

### Design Pattern
- **Singleton Pattern**: All classes use the singleton pattern for single instance management
- **WordPress Hooks System**: Extensively uses actions and filters
- **AJAX-Based**: Frontend communicates with backend via WordPress AJAX
- **REST API Ready**: Includes REST API endpoints for external integrations

### Plugin Flow
1. **Initialization**: `snn-notes.php` → `SNN_Notes_Core` → Load all dependencies
2. **Post Type & Taxonomy**: Registers `snn_note` post type with `snn_tag` and `snn_folder` taxonomies
3. **Admin Interface**: Custom admin page with Quill.js rich text editor
4. **AJAX Communication**: JavaScript (jQuery) communicates with PHP via `wp_ajax_*` hooks
5. **Data Storage**: Uses WordPress posts/terms + custom tables for revisions, stats, and shares

---

## File Structure

```
snn-notes/
├── snn-notes.php                    # Main plugin file (entry point)
├── AGENTS.md                        # This file - development documentation
│
├── includes/                        # PHP Backend Classes
│   ├── class-snn-notes-core.php         # Core initialization & plugin management
│   ├── class-snn-notes-post-type.php    # Custom post type registration
│   ├── class-snn-notes-taxonomy.php     # Taxonomies (tags & folders)
│   ├── class-snn-notes-admin.php        # Admin menu & pages
│   ├── class-snn-notes-ajax.php         # AJAX request handlers (MAIN LOGIC)
│   ├── class-snn-notes-rest-api.php     # REST API endpoints
│   ├── class-snn-notes-assets.php       # Asset enqueuing (CSS/JS)
│   ├── class-snn-notes-settings.php     # Plugin settings page
│   ├── class-snn-notes-export.php       # Export functionality (JSON, CSV, etc.)
│   └── class-snn-notes-templates.php    # Note templates system
│
├── templates/                       # PHP Template Files
│   ├── admin-page.php                   # Main notes interface
│   ├── settings-page.php                # Settings page template
│   └── stats-page.php                   # Statistics page template
│
└── assets/                          # Frontend Assets
    ├── css/
    │   └── admin-style.css              # Main stylesheet
    └── js/
        └── admin-script.js              # Main JavaScript (jQuery + Quill.js)
```

---

## Core Classes & Functions

### 1. SNN_Notes_Core (`class-snn-notes-core.php`)
**Purpose**: Central controller for plugin initialization

#### Key Methods:
| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `get_instance()` | - | `SNN_Notes_Core` | Singleton instance getter |
| `activate()` | - | `void` | Runs on plugin activation - creates tables & default options |
| `deactivate()` | - | `void` | Runs on plugin deactivation |
| `create_tables()` | - | `void` | Creates custom database tables |
| `load_dependencies()` | - | `void` | Includes all class files |
| `init_hooks()` | - | `void` | Initializes all component instances |
| `add_admin_bar_menu($wp_admin_bar)` | `WP_Admin_Bar` | `void` | Adds "New Note" to admin bar |

#### Custom Database Tables:
1. `{prefix}_snn_note_revisions` - Store note revision history
2. `{prefix}_snn_note_stats` - Track views, word count, last viewed
3. `{prefix}_snn_note_shares` - Manage shared notes with permissions

---

### 2. SNN_Notes_Post_Type (`class-snn-notes-post-type.php`)
**Purpose**: Register custom post type for notes

#### Post Type: `snn_note`
- **Labels**: Standard WordPress labels
- **Public**: `false` (admin-only)
- **Supports**: `title`, `editor`, `author`, `revisions`
- **Taxonomies**: `snn_tag`, `snn_folder`
- **REST**: Enabled (`show_in_rest`)

#### Custom Post Status: `snn_archived`
- Used for archived notes (not trash, not published)

---

### 3. SNN_Notes_Taxonomy (`class-snn-notes-taxonomy.php`)
**Purpose**: Register taxonomies for organization

#### Taxonomies:
| Taxonomy | Type | Hierarchical | Description |
|----------|------|--------------|-------------|
| `snn_tag` | Tag | No | Flat tag system with colors |
| `snn_folder` | Folder | Yes | Hierarchical folder structure |

#### Tag Meta:
- `color` (hex code) - Stored in term meta

---

### 4. SNN_Notes_Ajax (`class-snn-notes-ajax.php`)
**Purpose**: Handle ALL AJAX requests - THIS IS THE MAIN BUSINESS LOGIC CLASS

#### Security:
- All methods use `verify_request()` for nonce verification
- `sanitize_note_data($data)` sanitizes input
- Permission checks with `current_user_can('edit_posts')`

#### Key AJAX Methods:
See [AJAX Endpoints](#ajax-endpoints) section for complete list.

---

### 5. SNN_Notes_Assets (`class-snn-notes-assets.php`)
**Purpose**: Enqueue CSS/JS files

#### Enqueued Assets:
**CSS:**
- `admin-style.css` - Main styles
- Google Fonts (optionally)
- Quill.js CSS

**JavaScript:**
- jQuery (WordPress default)
- Quill.js - Rich text editor
- Quill Image Resize Module
- `admin-script.js` - Main application logic

**Localized Data (`snnNotes` object):**
```javascript
{
    ajaxUrl: admin-ajax.php URL,
    nonce: Security nonce,
    userId: Current user ID,
    settings: Plugin settings array
}
```

---

### 6. SNN_Notes_Admin (`class-snn-notes-admin.php`)
**Purpose**: Register admin menu pages

#### Admin Pages:
| Page | Slug | Capability | Description |
|------|------|-----------|-------------|
| SNN Notes | `snn-notes` | `edit_posts` | Main notes interface |
| Settings | `snn-notes-settings` | `manage_options` | Plugin settings |
| Statistics | `snn-notes-stats` | `edit_posts` | Note statistics |

---

### 7. SNN_Notes_REST_API (`class-snn-notes-rest-api.php`)
**Purpose**: Expose REST API endpoints for external access

#### REST Endpoints:
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/snn-notes/v1/notes` | GET | Get all notes |
| `/wp-json/snn-notes/v1/notes/{id}` | GET | Get single note |
| `/wp-json/snn-notes/v1/notes` | POST | Create note |
| `/wp-json/snn-notes/v1/notes/{id}` | PUT | Update note |
| `/wp-json/snn-notes/v1/notes/{id}` | DELETE | Delete note |

---

### 8. SNN_Notes_Export (`class-snn-notes-export.php`)
**Purpose**: Export notes in various formats

#### Export Formats:
- JSON
- HTML
- Plain Text
- Markdown
- CSV (metadata only)

---

### 9. SNN_Notes_Templates (`class-snn-notes-templates.php`)
**Purpose**: Manage reusable note templates

#### Template Operations:
- Save note as template
- Load template into new note
- Delete templates
- List all templates

---

### 10. SNN_Notes_Settings (`class-snn-notes-settings.php`)
**Purpose**: Manage plugin settings

#### Default Settings:
```php
[
    'enable_trash' => true,
    'enable_revisions' => true,
    'auto_save_interval' => 2000, // milliseconds
    'notes_per_page' => 50,
    'default_view' => 'list', // or 'grid'
    'enable_encryption' => false,
    'theme' => 'light', // or 'dark'
]
```

---

## AJAX Endpoints

All AJAX actions use the prefix `snn_` and are handled in `class-snn-notes-ajax.php`.

### Note Management

| Action | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `snn_create_note` | - | `{id, title}` | Creates new blank note |
| `snn_save_note` | `note_id`, `title`, `content` | `{success, message}` | Saves note (creates revision) |
| `snn_delete_note` | `note_id`, `permanent` | `{success}` | Deletes or trashes note |
| `snn_get_note` | `note_id` | `{id, title, content, date, tags, ...}` | Retrieves single note |
| `snn_get_notes` | `search`, `tag`, `folder`, `status`, `page` | `{notes[], total, pages}` | Lists notes with filters |
| `snn_duplicate_note` | `note_id` | `{id, title}` | Duplicates a note |
| `snn_restore_note` | `note_id` | `{success}` | Restores from trash |
| `snn_pin_note` | `note_id`, `pinned` | `{success}` | Pin/unpin note |
| `snn_archive_note` | `note_id`, `archived` | `{success}` | Archive/unarchive note |

### Tag Management

| Action | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `snn_create_tag` | `name`, `color` | `{id, name, color}` | Creates new tag |
| `snn_delete_tag` | `tag_id` | `{success}` | Deletes tag |
| `snn_get_tags` | - | `{tags[]}` | List all tags with count |
| `snn_assign_tag` | `note_id`, `tag_ids[]` | `{success}` | Assign tags to note |
| `snn_rename_tag` | `tag_id`, `name` | `{success}` | Rename tag |
| `snn_merge_tags` | `source_tag_id`, `target_tag_id` | `{success}` | Merge two tags |

### Folder Management

| Action | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `snn_create_folder` | `name`, `parent_id` | `{id, name}` | Creates new folder |
| `snn_delete_folder` | `folder_id` | `{success}` | Deletes folder |
| `snn_get_folders` | - | `{folders[]}` | List all folders (hierarchical) |
| `snn_move_to_folder` | `note_id`, `folder_id` | `{success}` | Move note to folder |

### Search & Stats

| Action | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `snn_search_notes` | `query` | `{notes[]}` | Search in title & content |
| `snn_get_stats` | - | `{total, today, week, ...}` | Get statistics |
| `snn_increment_view` | `note_id` | `{views}` | Track note view |

### Templates

| Action | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `snn_save_template` | `title`, `content` | `{id}` | Save as template |
| `snn_get_templates` | - | `{templates[]}` | List templates |
| `snn_delete_template` | `template_id` | `{success}` | Delete template |

---

## Database Schema

### Custom Tables

#### 1. `wp_snn_note_revisions`
```sql
CREATE TABLE wp_snn_note_revisions (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    note_id BIGINT(20) NOT NULL,
    title TEXT NOT NULL,
    content LONGTEXT NOT NULL,
    created_by BIGINT(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY note_id (note_id),
    KEY created_at (created_at)
);
```

#### 2. `wp_snn_note_stats`
```sql
CREATE TABLE wp_snn_note_stats (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    note_id BIGINT(20) NOT NULL UNIQUE,
    views INT(11) DEFAULT 0,
    last_viewed DATETIME DEFAULT NULL,
    word_count INT(11) DEFAULT 0
);
```

#### 3. `wp_snn_note_shares`
```sql
CREATE TABLE wp_snn_note_shares (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    note_id BIGINT(20) NOT NULL,
    share_token VARCHAR(64) NOT NULL UNIQUE,
    shared_by BIGINT(20) NOT NULL,
    shared_with BIGINT(20) DEFAULT NULL,
    permission VARCHAR(20) DEFAULT 'view', -- 'view' or 'edit'
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY note_id (note_id)
);
```

### WordPress Tables Used

#### Posts (`wp_posts`)
- `post_type = 'snn_note'`
- `post_title` = Note title
- `post_content` = Note content (HTML from Quill)
- `post_status` = `publish`, `draft`, `trash`, `snn_archived`
- `post_author` = User ID

#### Terms (`wp_terms` + `wp_term_taxonomy`)
- Taxonomy: `snn_tag` (tags with colors in term meta)
- Taxonomy: `snn_folder` (hierarchical folders)

#### Term Meta (`wp_termmeta`)
- `color` = Hex color for tags (e.g., `#3b82f6`)

#### Post Meta (`wp_postmeta`)
- `_snn_pinned` = `1` if note is pinned
- `_snn_archived` = `1` if note is archived (legacy, now uses post_status)

---

## Frontend JavaScript API

The main JavaScript object is `SnnNotes` (defined in `assets/js/admin-script.js`).

### Global Variables
```javascript
let quillEditor = null;      // Quill.js instance
let currentNoteId = null;    // Currently open note ID
let autoSaveTimer = null;    // Auto-save timer reference
let allNotes = [];           // Cached notes array
let allTags = [];            // Cached tags array
```

### Main Object: `SnnNotes`

#### Initialization Methods
| Method | Description |
|--------|-------------|
| `init()` | Initialize everything on document ready |
| `initQuillEditor()` | Initialize Quill.js with toolbar config |
| `bindEvents()` | Attach all event listeners |
| `initDragAndDrop()` | Setup drag & drop for tags |

#### Note Methods
| Method | Parameters | Description |
|--------|-----------|-------------|
| `createNote()` | - | Create new note via AJAX |
| `saveNote()` | - | Save current note |
| `loadNote(noteId)` | `number` | Load note into editor |
| `deleteNote(noteId)` | `number` | Delete note |
| `loadNotes()` | - | Fetch all notes from server |
| `renderNotes()` | - | Render notes list in sidebar |
| `scheduleAutoSave()` | - | Schedule auto-save in 2s |

#### Tag Methods
| Method | Parameters | Description |
|--------|-----------|-------------|
| `createTag()` | - | Create new tag from modal |
| `deleteTag(tagId)` | `number` | Delete tag |
| `loadTags()` | - | Fetch all tags |
| `renderTags()` | - | Render tags list in sidebar |
| `addTagToNote(tagId)` | `number` | Add tag to current note |
| `addTagToSpecificNote(noteId, tagId)` | `number, number` | Add tag to specific note (for drag & drop) |
| `removeTagFromNote(tagId)` | `number` | Remove tag from current note |
| `renderNoteTags(tags)` | `array` | Render tags in note editor |

#### UI Methods
| Method | Parameters | Description |
|--------|-----------|-------------|
| `showTagModal()` | - | Show tag creation modal |
| `hideTagModal()` | - | Hide tag modal |
| `showStatus(message, type)` | `string, string` | Show status message (success/error/info) |
| `escapeHtml(text)` | `string` | HTML escape utility |

### Quill.js Configuration

```javascript
quillEditor = new Quill('#snn-quill-editor', {
    theme: 'snow',
    placeholder: 'Start writing your note...',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            ['link', 'image', 'code-block'],
            ['clean']
        ],
        imageResize: {
            displaySize: true
        }
    }
});
```

### Drag & Drop System

**Tags are draggable:**
- Class: `.snn-tag-item`
- Attribute: `draggable="true"`
- Data: `data-tag-id="{id}"`

**Drop zones:**
1. `#snn-note-tags` - Current note tags area
2. `.snn-note-item` - Individual notes in sidebar

**Events:**
- `dragstart` - Sets `dataTransfer.setData('tagId', id)`
- `dragover` - Adds `.drag-over` class, prevents default
- `dragleave` - Removes `.drag-over` class
- `drop` - Calls `addTagToNote()` or `addTagToSpecificNote()`

---

## Development Guidelines

### When Adding New Features

#### 1. Backend (PHP) Changes

**Add new AJAX endpoint:**
```php
// In class-snn-notes-ajax.php __construct()
add_action('wp_ajax_snn_your_action', array($this, 'your_action'));

// Add method
public function your_action() {
    $this->verify_request();
    
    $param = isset($_POST['param']) ? sanitize_text_field($_POST['param']) : '';
    
    // Your logic here
    
    wp_send_json_success(array(
        'data' => $result
    ));
}
```

**Add new post meta:**
```php
// In save_note() or relevant method
update_post_meta($note_id, '_snn_your_meta', $value);
```

**Add new database table:**
```php
// In class-snn-notes-core.php create_tables()
$table_name = $wpdb->prefix . 'snn_your_table';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    // ... your columns
    PRIMARY KEY (id)
) $charset_collate;";
dbDelta($sql);
```

#### 2. Frontend (JavaScript) Changes

**Add new AJAX call:**
```javascript
// In SnnNotes object
yourMethod: function() {
    const self = this;
    
    $.ajax({
        url: snnNotes.ajaxUrl,
        type: 'POST',
        data: {
            action: 'snn_your_action',
            nonce: snnNotes.nonce,
            param: value
        },
        success: function(response) {
            if (response.success) {
                // Handle success
                self.showStatus('Success', 'success');
            }
        }
    });
}
```

**Add event listener:**
```javascript
// In bindEvents()
$('#your-button').on('click', function() {
    self.yourMethod();
});
```

#### 3. UI/Template Changes

**Edit HTML template:**
- Modify `templates/admin-page.php` for main interface
- Add new elements with proper classes and IDs
- Follow existing naming convention: `snn-*`

**Add new CSS:**
- Edit `assets/css/admin-style.css`
- Use CSS custom properties (variables) defined at `:root`
- Follow BEM-like naming: `.snn-component-element`

### Code Style Guidelines

**PHP:**
- Use WordPress coding standards
- Always escape output: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- Sanitize input: `sanitize_text_field()`, `wp_unslash()`
- Check capabilities: `current_user_can()`
- Verify nonces: `check_ajax_referer()`

**JavaScript:**
- Use jQuery (already loaded by WordPress)
- Use `const` and `let`, avoid `var`
- Use arrow functions for callbacks
- Always escape user data before inserting into DOM
- Use `escapeHtml()` utility function

**CSS:**
- Use CSS custom properties for theming
- Mobile-first responsive design
- Follow existing color scheme
- Use flexbox/grid for layouts

### Security Best Practices

1. **Nonce verification** on EVERY AJAX request
2. **Capability checks** - `edit_posts` for most operations
3. **Sanitize all input** from $_POST, $_GET
4. **Escape all output** in templates
5. **Prepared statements** for custom SQL queries
6. **Validate data types** before database operations

### Performance Optimization

1. **Cache queries** where possible (transients)
2. **Lazy load** notes (pagination)
3. **Debounce** auto-save (2 second delay)
4. **Use `wp_localize_script()`** for PHP→JS data
5. **Minimize DOM manipulation** - batch updates
6. **Index database** tables properly

---

## Adding New Features

### Example: Adding a "Favorite" Feature

#### Step 1: Backend (AJAX Handler)

**File: `includes/class-snn-notes-ajax.php`**

```php
// Add to __construct()
add_action('wp_ajax_snn_favorite_note', array($this, 'favorite_note'));

// Add method
public function favorite_note() {
    $this->verify_request();
    
    $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
    $favorite = isset($_POST['favorite']) ? (bool)$_POST['favorite'] : false;
    
    if (!$note_id) {
        wp_send_json_error(__('Invalid note ID', 'snn-notes'));
    }
    
    // Check permission
    if (!current_user_can('edit_post', $note_id)) {
        wp_send_json_error(__('Permission denied', 'snn-notes'));
    }
    
    // Save meta
    if ($favorite) {
        update_post_meta($note_id, '_snn_favorite', 1);
    } else {
        delete_post_meta($note_id, '_snn_favorite');
    }
    
    wp_send_json_success(array(
        'note_id' => $note_id,
        'favorite' => $favorite
    ));
}
```

#### Step 2: Frontend (JavaScript)

**File: `assets/js/admin-script.js`**

```javascript
// Add to SnnNotes object
favoriteNote: function(noteId, favorite) {
    const self = this;
    
    $.ajax({
        url: snnNotes.ajaxUrl,
        type: 'POST',
        data: {
            action: 'snn_favorite_note',
            nonce: snnNotes.nonce,
            note_id: noteId,
            favorite: favorite
        },
        success: function(response) {
            if (response.success) {
                self.showStatus('Favorite updated', 'success');
                self.loadNotes(); // Refresh list
            }
        }
    });
},

// Add to bindEvents()
$(document).on('click', '.snn-favorite-btn', function() {
    const noteId = $(this).data('note-id');
    const isFavorite = $(this).hasClass('favorited');
    self.favoriteNote(noteId, !isFavorite);
});
```

#### Step 3: Update get_notes() to include favorite status

**File: `includes/class-snn-notes-ajax.php`**

```php
// In get_notes() method, add to note data array:
'favorite' => (bool)get_post_meta($post->ID, '_snn_favorite', true),
```

#### Step 4: Add UI Button

**File: `templates/admin-page.php`** or modify `renderNotes()` in JS

```javascript
// In renderNotes(), add to note item:
<button class="snn-favorite-btn ${note.favorite ? 'favorited' : ''}" 
        data-note-id="${note.id}">
    <span class="dashicons dashicons-star-${note.favorite ? 'filled' : 'empty'}"></span>
</button>
```

#### Step 5: Add CSS

**File: `assets/css/admin-style.css`**

```css
.snn-favorite-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--snn-text-secondary);
}

.snn-favorite-btn.favorited {
    color: gold;
}
```

---

## Common Development Tasks

### Task: Add new filter to notes list

1. **Backend**: Add parameter to `get_notes()` in AJAX handler
2. **Process**: Modify `WP_Query` args based on filter
3. **Frontend**: Add UI control (dropdown/button)
4. **JS**: Call `loadNotes()` with new parameter

### Task: Add new metadata to notes

1. **Backend**: Use `update_post_meta()` in `save_note()`
2. **Retrieve**: Add to data returned in `get_note()`
3. **Frontend**: Add input field in editor area
4. **Save**: Include in `saveNote()` AJAX call

### Task: Create new sidebar section

1. **Template**: Add HTML in `templates/admin-page.php`
2. **CSS**: Style in `assets/css/admin-style.css`
3. **JS**: Add `.snn-section-header` click handler (already exists)
4. **Populate**: Fetch data via AJAX, render in JS

### Task: Export new format

1. **Backend**: Add method in `class-snn-notes-export.php`
2. **Format**: Generate file content (PDF, DOCX, etc.)
3. **Download**: Use `wp_send_json()` or direct file download
4. **Frontend**: Add export button with new format option

---

## Debugging Tips

### Enable WordPress Debug Mode
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### AJAX Debugging
```javascript
// Add to AJAX calls
error: function(xhr, status, error) {
    console.error('AJAX Error:', error);
    console.log('Response:', xhr.responseText);
}
```

### PHP Debugging
```php
error_log(print_r($variable, true)); // Check wp-content/debug.log
```

### Database Queries
```php
global $wpdb;
$wpdb->show_errors();
echo $wpdb->last_query; // Show last query
print_r($wpdb->last_error); // Show last error
```

### JavaScript Console
```javascript
console.log('AllNotes:', allNotes);
console.log('Current Note ID:', currentNoteId);
console.log('Quill Content:', quillEditor.root.innerHTML);
```

---

## Plugin Constants

Defined in `snn-notes.php`:

| Constant | Value | Description |
|----------|-------|-------------|
| `SNN_NOTES_VERSION` | `1.0.0` | Plugin version |
| `SNN_NOTES_PLUGIN_FILE` | `__FILE__` | Main plugin file path |
| `SNN_NOTES_PLUGIN_DIR` | `plugin_dir_path(__FILE__)` | Plugin directory path |
| `SNN_NOTES_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | Plugin URL |
| `SNN_NOTES_PLUGIN_BASENAME` | `plugin_basename(__FILE__)` | Plugin basename |

---

## Hooks & Filters Reference

### Actions

| Hook | Location | Description |
|------|----------|-------------|
| `plugins_loaded` | Core | Load text domain |
| `init` | Post Type, Taxonomy | Register CPT & taxonomies |
| `admin_menu` | Admin | Register admin pages |
| `admin_enqueue_scripts` | Assets | Enqueue CSS/JS |
| `wp_ajax_snn_*` | Ajax | All AJAX handlers |
| `admin_bar_menu` | Core | Add admin bar item |

### Filters

| Hook | Location | Parameters | Description |
|------|----------|-----------|-------------|
| `snn_notes_query_args` | Ajax | `$args, $filters` | Modify WP_Query args |
| `snn_notes_export_data` | Export | `$data, $format` | Modify export data |
| `snn_notes_settings` | Settings | `$settings` | Modify default settings |

*(Add more custom filters as needed)*

---

## Testing Checklist

When adding new features, test:

- [ ] Nonce verification works
- [ ] Permission checks work
- [ ] Data saves correctly to database
- [ ] Data retrieves correctly
- [ ] UI updates properly
- [ ] Auto-save doesn't break
- [ ] Drag & drop still works
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log
- [ ] Works with multiple users
- [ ] Works in different browsers
- [ ] Mobile responsive (if UI change)
- [ ] Doesn't break existing features

---

## Future Enhancement Ideas

- [ ] Real-time collaboration (WebSocket)
- [ ] Note encryption
- [ ] AI-powered note suggestions
- [ ] Markdown support (alternative to Quill)
- [ ] Note linking/backlinking
- [ ] Global search with preview
- [ ] Dark mode toggle
- [ ] Custom CSS themes
- [ ] Import from Evernote/Notion
- [ ] Keyboard shortcuts
- [ ] Note versions diff viewer
- [ ] Bulk operations
- [ ] Advanced filtering
- [ ] Note scheduling/reminders
- [ ] Audio/video notes

---

## Support & Resources

- **WordPress Codex**: https://codex.wordpress.org/
- **Quill.js Docs**: https://quilljs.com/docs/
- **jQuery Docs**: https://api.jquery.com/
- **Plugin Handbook**: https://developer.wordpress.org/plugins/

---

## Version History

### 1.0.0 (Current)
- Initial release
- Core note functionality
- Tags & folders
- Search & export
- Templates system
- Revisions & statistics
- Drag & drop tags

---

## License

GPL v2 or later

---

**Last Updated**: 2025-12-05  
**Document Version**: 1.0  
**Maintained By**: sinanisler

---

## Quick Reference Card

### Most Used Files When Developing:
1. `includes/class-snn-notes-ajax.php` - Add AJAX endpoints
2. `assets/js/admin-script.js` - Add JavaScript functionality
3. `templates/admin-page.php` - Modify UI
4. `assets/css/admin-style.css` - Update styles

### Most Common Operations:
- **Add AJAX endpoint**: Edit `class-snn-notes-ajax.php`
- **Add JS function**: Edit `admin-script.js` → `SnnNotes` object
- **Change layout**: Edit `templates/admin-page.php`
- **Add metadata**: Use `update_post_meta()` in AJAX handler
- **New database table**: Edit `create_tables()` in core

### Critical Files NOT to Break:
- `class-snn-notes-core.php` - Plugin initialization
- `snn-notes.php` - Main file
- Database structure (careful with `create_tables()`)

---

**END OF DOCUMENTATION**
