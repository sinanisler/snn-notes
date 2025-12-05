(function($) {
    'use strict';
    
    let quillEditor = null;
    let currentNoteId = null;
    let autoSaveTimer = null;
    let allNotes = [];
    let allTags = [];
    
    const SnnNotes = {
        
        init: function() {
            this.initQuillEditor();
            this.bindEvents();
            this.loadTags();
            this.loadNotes();
            this.initDragAndDrop();
        },
        
        initQuillEditor: function() {
            quillEditor = new Quill('#snn-quill-editor', {
                theme: 'snow',
                placeholder: 'Start writing your note...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link', 'image', 'code-block'],
                        ['clean']
                    ]
                }
            });
            
            // Auto-save on content change
            quillEditor.on('text-change', () => {
                if (currentNoteId) {
                    this.scheduleAutoSave();
                }
            });
        },
        
        bindEvents: function() {
            const self = this;
            
            // New Note Button
            $('#snn-new-note-btn').on('click', function() {
                self.createNote();
            });
            
            // New Tag Button
            $('#snn-new-tag-btn').on('click', function() {
                self.showTagModal();
            });
            
            // Save Note Button
            $('#snn-save-note-btn').on('click', function() {
                self.saveNote();
            });
            
            // Delete Note Button
            $('#snn-delete-note-btn').on('click', function() {
                if (confirm('Are you sure you want to delete this note?')) {
                    self.deleteNote(currentNoteId);
                }
            });
            
            // Title change auto-save
            $('#snn-note-title').on('input', function() {
                if (currentNoteId) {
                    self.scheduleAutoSave();
                }
            });
            
            // Collapsible sections
            $('.snn-section-header').on('click', function() {
                $(this).closest('.snn-section').toggleClass('collapsed');
            });
            
            // Toggle Sidebar
            $('#snn-toggle-sidebar').on('click', function() {
                $('#snn-sidebar').toggleClass('collapsed');
            });
            
            // Tag Modal
            $('#snn-tag-create-btn').on('click', function() {
                self.createTag();
            });
            
            $('#snn-tag-cancel-btn, .snn-modal-close').on('click', function() {
                self.hideTagModal();
            });
            
            $('#snn-tag-modal').on('click', function(e) {
                if ($(e.target).is('#snn-tag-modal')) {
                    self.hideTagModal();
                }
            });
            
            // Color picker preview
            $('#snn-tag-color').on('input', function() {
                $('.snn-color-preview').css('background-color', $(this).val());
            });
            
            // Tag name enter key
            $('#snn-tag-name').on('keypress', function(e) {
                if (e.which === 13) {
                    self.createTag();
                }
            });
            
            // Note tag drop zone
            $('#snn-note-tags').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            $('#snn-note-tags').on('dragleave', function() {
                $(this).removeClass('drag-over');
            });
            
            $('#snn-note-tags').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                const tagId = e.originalEvent.dataTransfer.getData('tagId');
                if (tagId && currentNoteId) {
                    self.addTagToNote(parseInt(tagId));
                }
            });
        },
        
        initDragAndDrop: function() {
            const self = this;
            
            // Make tags draggable
            $(document).on('dragstart', '.snn-tag-item', function(e) {
                const tagId = $(this).data('tag-id');
                e.originalEvent.dataTransfer.setData('tagId', tagId);
                $(this).addClass('dragging');
            });
            
            $(document).on('dragend', '.snn-tag-item', function() {
                $(this).removeClass('dragging');
            });
        },
        
        createNote: function() {
            const self = this;
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_create_note',
                    nonce: snnNotes.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.loadNotes();
                        self.loadNote(response.data.id);
                        self.showStatus('Note created', 'success');
                    } else {
                        self.showStatus('Error creating note', 'error');
                    }
                }
            });
        },
        
        saveNote: function() {
            if (!currentNoteId) return;
            
            const self = this;
            const title = $('#snn-note-title').val() || 'Untitled Note';
            const content = quillEditor.root.innerHTML;
            
            $('#snn-save-note-btn').prop('disabled', true);
            self.showStatus('Saving...', 'info');
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_save_note',
                    nonce: snnNotes.nonce,
                    note_id: currentNoteId,
                    title: title,
                    content: content
                },
                success: function(response) {
                    if (response.success) {
                        self.showStatus('Saved', 'success');
                        self.loadNotes();
                        
                        // Update note title in sidebar
                        $(`.snn-note-item[data-note-id="${currentNoteId}"] .snn-note-item-title`).text(title);
                    } else {
                        self.showStatus('Error saving note', 'error');
                    }
                },
                complete: function() {
                    $('#snn-save-note-btn').prop('disabled', false);
                }
            });
        },
        
        scheduleAutoSave: function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                this.saveNote();
            }, 2000);
        },
        
        loadNote: function(noteId) {
            const self = this;
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_get_note',
                    nonce: snnNotes.nonce,
                    note_id: noteId
                },
                success: function(response) {
                    if (response.success) {
                        currentNoteId = noteId;
                        const note = response.data;
                        
                        // Update UI
                        $('.snn-welcome-screen').hide();
                        $('#snn-note-editor').show();
                        
                        $('#snn-note-title').val(note.title);
                        $('#snn-note-date').text(note.date);
                        quillEditor.root.innerHTML = note.content;
                        
                        // Update tags
                        self.renderNoteTags(note.tags);
                        
                        // Update active state
                        $('.snn-note-item').removeClass('active');
                        $(`.snn-note-item[data-note-id="${noteId}"]`).addClass('active');
                    }
                }
            });
        },
        
        deleteNote: function(noteId) {
            const self = this;
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_delete_note',
                    nonce: snnNotes.nonce,
                    note_id: noteId
                },
                success: function(response) {
                    if (response.success) {
                        self.showStatus('Note deleted', 'success');
                        currentNoteId = null;
                        
                        // Show welcome screen
                        $('#snn-note-editor').hide();
                        $('.snn-welcome-screen').show();
                        
                        self.loadNotes();
                    } else {
                        self.showStatus('Error deleting note', 'error');
                    }
                }
            });
        },
        
        loadNotes: function() {
            const self = this;
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_get_notes',
                    nonce: snnNotes.nonce
                },
                success: function(response) {
                    if (response.success) {
                        allNotes = response.data;
                        self.renderNotes();
                    }
                }
            });
        },
        
        renderNotes: function() {
            const $notesList = $('#snn-notes-list');
            $notesList.empty();
            
            if (allNotes.length === 0) {
                $notesList.html('<p style="padding: 8px; font-size: 12px; color: var(--snn-text-secondary); text-align: center;">No notes yet</p>');
                return;
            }
            
            allNotes.forEach(note => {
                const $noteItem = $(`
                    <div class="snn-note-item" data-note-id="${note.id}">
                        <div class="snn-note-item-title">${this.escapeHtml(note.title)}</div>
                        <div class="snn-note-item-meta">
                            <span class="snn-note-item-excerpt">${this.escapeHtml(note.excerpt)}</span>
                            <span class="snn-note-item-date">${note.date}</span>
                        </div>
                        ${note.tags.length > 0 ? `<div class="snn-note-item-tags">${note.tags.map(tag => `<span class="snn-note-item-tag" style="background-color: ${tag.color}">${this.escapeHtml(tag.name)}</span>`).join('')}</div>` : ''}
                    </div>
                `);
                
                $noteItem.on('click', () => {
                    this.loadNote(note.id);
                });
                
                $notesList.append($noteItem);
            });
        },
        
        createTag: function() {
            const self = this;
            const name = $('#snn-tag-name').val().trim();
            const color = $('#snn-tag-color').val();
            
            if (!name) {
                alert('Please enter a tag name');
                return;
            }
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_create_tag',
                    nonce: snnNotes.nonce,
                    name: name,
                    color: color
                },
                success: function(response) {
                    if (response.success) {
                        self.hideTagModal();
                        self.loadTags();
                        self.showStatus('Tag created', 'success');
                    } else {
                        alert(response.data);
                    }
                }
            });
        },
        
        deleteTag: function(tagId) {
            const self = this;
            
            if (!confirm('Delete this tag? It will be removed from all notes.')) {
                return;
            }
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_delete_tag',
                    nonce: snnNotes.nonce,
                    tag_id: tagId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadTags();
                        self.loadNotes();
                        if (currentNoteId) {
                            self.loadNote(currentNoteId);
                        }
                        self.showStatus('Tag deleted', 'success');
                    }
                }
            });
        },
        
        loadTags: function() {
            const self = this;
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_get_tags',
                    nonce: snnNotes.nonce
                },
                success: function(response) {
                    if (response.success) {
                        allTags = response.data;
                        self.renderTags();
                    }
                }
            });
        },
        
        renderTags: function() {
            const $tagsList = $('#snn-tags-list');
            $tagsList.empty();
            
            if (allTags.length === 0) {
                $tagsList.html('<p style="padding: 8px; font-size: 12px; color: var(--snn-text-secondary); text-align: center;">No tags yet</p>');
                return;
            }
            
            allTags.forEach(tag => {
                const $tagItem = $(`
                    <div class="snn-tag-item" draggable="true" data-tag-id="${tag.id}">
                        <span class="snn-tag-color" style="background-color: ${tag.color}"></span>
                        <span class="snn-tag-name">${this.escapeHtml(tag.name)}</span>
                        <span class="snn-tag-count">(${tag.count})</span>
                        <button class="snn-tag-delete" data-tag-id="${tag.id}">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                `);
                
                $tagItem.find('.snn-tag-delete').on('click', (e) => {
                    e.stopPropagation();
                    this.deleteTag(tag.id);
                });
                
                $tagsList.append($tagItem);
            });
        },
        
        addTagToNote: function(tagId) {
            const self = this;
            
            // Get current tags
            const currentTags = [];
            $('#snn-note-tags .snn-note-tag').each(function() {
                currentTags.push($(this).data('tag-id'));
            });
            
            // Check if tag already exists
            if (currentTags.includes(tagId)) {
                return;
            }
            
            // Add new tag
            currentTags.push(tagId);
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_assign_tag',
                    nonce: snnNotes.nonce,
                    note_id: currentNoteId,
                    tag_ids: currentTags
                },
                success: function(response) {
                    if (response.success) {
                        self.loadNote(currentNoteId);
                        self.loadNotes();
                        self.loadTags();
                    }
                }
            });
        },
        
        removeTagFromNote: function(tagId) {
            const self = this;
            
            // Get current tags
            const currentTags = [];
            $('#snn-note-tags .snn-note-tag').each(function() {
                const tid = $(this).data('tag-id');
                if (tid !== tagId) {
                    currentTags.push(tid);
                }
            });
            
            $.ajax({
                url: snnNotes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_assign_tag',
                    nonce: snnNotes.nonce,
                    note_id: currentNoteId,
                    tag_ids: currentTags
                },
                success: function(response) {
                    if (response.success) {
                        self.loadNote(currentNoteId);
                        self.loadNotes();
                        self.loadTags();
                    }
                }
            });
        },
        
        renderNoteTags: function(tags) {
            const self = this;
            const $noteTags = $('#snn-note-tags');
            $noteTags.empty();
            
            tags.forEach(tag => {
                const $tag = $(`
                    <span class="snn-note-tag" style="background-color: ${tag.color}" data-tag-id="${tag.id}">
                        ${this.escapeHtml(tag.name)}
                        <span class="snn-note-tag-remove">&times;</span>
                    </span>
                `);
                
                $tag.find('.snn-note-tag-remove').on('click', () => {
                    self.removeTagFromNote(tag.id);
                });
                
                $noteTags.append($tag);
            });
        },
        
        showTagModal: function() {
            $('#snn-tag-name').val('');
            $('#snn-tag-color').val('#3b82f6');
            $('.snn-color-preview').css('background-color', '#3b82f6');
            $('#snn-tag-modal').fadeIn(200);
            $('#snn-tag-name').focus();
        },
        
        hideTagModal: function() {
            $('#snn-tag-modal').fadeOut(200);
        },
        
        showStatus: function(message, type) {
            const $status = $('#snn-save-status');
            $status.text(message).removeClass('snn-status-success snn-status-error snn-status-info');
            
            if (type === 'success') {
                $status.addClass('snn-status-success');
            } else if (type === 'error') {
                $status.addClass('snn-status-error');
            }
            
            setTimeout(() => {
                $status.text('');
            }, 3000);
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        SnnNotes.init();
    });
    
})(jQuery);
