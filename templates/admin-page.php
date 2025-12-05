<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="snn-notes-app" class="snn-notes-wrapper">
    <!-- Left Sidebar -->
    <aside id="snn-sidebar" class="snn-sidebar">
        <div class="snn-sidebar-header">
            <button id="snn-new-note-btn" class="snn-btn snn-btn-primary snn-btn-block">
                <span class="dashicons dashicons-plus-alt2"></span> New Note
            </button>
            <button id="snn-new-tag-btn" class="snn-btn snn-btn-secondary snn-btn-block">
                <span class="dashicons dashicons-tag"></span> New Tag
            </button>
        </div>
        
        <!-- Tags Section -->
        <div class="snn-section">
            <div class="snn-section-header" id="tags-header">
                <h3 class="snn-section-title">
                    <span class="dashicons dashicons-arrow-down-alt2 snn-collapse-icon"></span>
                    Tags
                </h3>
            </div>
            <div class="snn-section-content" id="tags-content">
                <div id="snn-tags-list" class="snn-tags-list"></div>
            </div>
        </div>
        
        <!-- Notes Section -->
        <div class="snn-section">
            <div class="snn-section-header" id="notes-header">
                <h3 class="snn-section-title">
                    <span class="dashicons dashicons-arrow-down-alt2 snn-collapse-icon"></span>
                    Recent Notes
                </h3>
            </div>
            <div class="snn-section-content" id="notes-content">
                <div id="snn-notes-list" class="snn-notes-list"></div>
            </div>
        </div>
        
        <button id="snn-toggle-sidebar" class="snn-toggle-sidebar" title="Toggle Sidebar">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
    </aside>
    
    <!-- Main Editor Area -->
    <main id="snn-main" class="snn-main">
        <div id="snn-editor-container" class="snn-editor-container">
            <div class="snn-welcome-screen">
                <div class="snn-welcome-content">
                    <span class="dashicons dashicons-edit-page snn-welcome-icon"></span>
                    <h2>Welcome to SNN Notes</h2>
                    <p>Create a new note or select an existing one to get started.</p>
                </div>
            </div>
            
            <div id="snn-note-editor" class="snn-note-editor" style="display: none;">
                <div class="snn-editor-header">
                    <input type="text" id="snn-note-title" class="snn-note-title" placeholder="Untitled Note" />
                    <div class="snn-editor-actions">
                        <div id="snn-note-tags" class="snn-note-tags"></div>
                        <button id="snn-save-note-btn" class="snn-btn snn-btn-success">
                            <span class="dashicons dashicons-saved"></span> Save
                        </button>
                        <button id="snn-delete-note-btn" class="snn-btn snn-btn-danger">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                
                <div class="snn-editor-meta">
                    <span id="snn-note-date" class="snn-note-date"></span>
                    <span id="snn-save-status" class="snn-save-status"></span>
                </div>
                
                <div id="snn-quill-editor" class="snn-quill-editor"></div>
            </div>
        </div>
    </main>
</div>

<!-- Tag Creation Modal -->
<div id="snn-tag-modal" class="snn-modal" style="display: none;">
    <div class="snn-modal-content">
        <div class="snn-modal-header">
            <h3>Create New Tag</h3>
            <button class="snn-modal-close">&times;</button>
        </div>
        <div class="snn-modal-body">
            <div class="snn-form-group">
                <label for="snn-tag-name">Tag Name</label>
                <input type="text" id="snn-tag-name" class="snn-input" placeholder="Enter tag name" />
            </div>
            <div class="snn-form-group">
                <label for="snn-tag-color">Tag Color</label>
                <div class="snn-color-picker">
                    <input type="color" id="snn-tag-color" class="snn-color-input" value="#3b82f6" />
                    <span class="snn-color-preview"></span>
                </div>
            </div>
        </div>
        <div class="snn-modal-footer">
            <button id="snn-tag-cancel-btn" class="snn-btn snn-btn-secondary">Cancel</button>
            <button id="snn-tag-create-btn" class="snn-btn snn-btn-primary">Create Tag</button>
        </div>
    </div>
</div>
