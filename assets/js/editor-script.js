/**
 * WP Content Helper - Editor JavaScript
 * Handles all frontend interactions for the AI assistant
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Get data from PHP
    const hasKey = aiwaData.hasKey;
    const settingsUrl = aiwaData.settingsUrl;
    const ajaxUrl = aiwaData.ajaxUrl;
    const nonce = aiwaData.nonce;
    
    let savedSelection = null;
    let savedRange = null;
    
    console.log('🤖 AI Assistant loaded');
    
    /**
     * Save current text selection
     */
    function saveSelection() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            savedRange = sel.getRangeAt(0);
            savedSelection = sel.toString().trim();
            return savedSelection;
        }
        return '';
    }
    
    /**
     * Replace text at saved selection (like Grammarly!)
     */
    function replaceAtSelection(newText) {
        if (!savedRange) {
            alert('No text selected. Please select text first.');
            return false;
        }
        
        // Restore selection
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
        
        // Delete old content
        savedRange.deleteContents();
        
        // Insert new text
        const textNode = document.createTextNode(newText);
        savedRange.insertNode(textNode);
        
        // Move cursor to end
        savedRange.setStartAfter(textNode);
        savedRange.setEndAfter(textNode);
        sel.removeAllRanges();
        sel.addRange(savedRange);
        
        console.log('✓ Text replaced');
        return true;
    }
    
    /**
     * Toggle modal
     */
    $('#aiwa-green-btn').click(function() {
        const selected = saveSelection();
        $('#aiwa-modal, #aiwa-overlay').addClass('show');
        
        if (selected) {
            const activeTab = $('.aiwa-tab.active').data('tab');
            $('#text-' + activeTab).val(selected);
        }
    });
    
    $('#aiwa-overlay, .aiwa-close').click(function() {
        $('#aiwa-modal, #aiwa-overlay').removeClass('show');
    });
    
    /**
     * Tab switching
     */
    $('.aiwa-tab').click(function() {
        const tab = $(this).data('tab');
        $('.aiwa-tab').removeClass('active');
        $('.aiwa-content').removeClass('active');
        $(this).addClass('active');
        $('#tab-' + tab).addClass('active');
        
        if (savedSelection) {
            $('#text-' + tab).val(savedSelection);
        }
    });
    
    /**
     * Auto-fill on text selection
     */
    $(document).on('mouseup keyup', function() {
        if ($('#aiwa-modal').hasClass('show')) {
            const selected = window.getSelection().toString().trim();
            if (selected) {
                saveSelection();
                const activeTab = $('.aiwa-tab.active').data('tab');
                $('#text-' + activeTab).val(selected);
            }
        }
    });
    
    /**
     * Show apply button
     */
    function showApplyButton(resultId, improvedText) {
        const html = `<button class="aiwa-btn aiwa-btn-apply" data-text="${improvedText.replace(/"/g, '&quot;')}">📝 Replace Selected Text</button>`;
        $(resultId).append(html);
        
        $('.aiwa-btn-apply').off('click').on('click', function() {
            const newText = $(this).data('text');
            if (replaceAtSelection(newText)) {
                alert('✓ Text replaced!');
                $('#aiwa-modal, #aiwa-overlay').removeClass('show');
            }
        });
    }
    
    /**
     * Improve Text
     */
    $('#btn-improve').click(function() {
        if (!hasKey) {
            alert('Please add API key first!');
            window.open(settingsUrl, '_blank');
            return;
        }
        
        const text = $('#text-improve').val().trim();
        if (!text) {
            alert('Please select text first');
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('Improving...');
        $('#result-improve').html('<div style="text-align:center;color:#16a34a;">🔄 Processing...</div>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiwa_improve',
                nonce: nonce,
                text: text
            },
            success: function(res) {
                console.log('✓ Improve response:', res);
                if (res.success) {
                    $('#result-improve').html('<div class="aiwa-result">' + res.data + '</div>');
                    showApplyButton('#result-improve', res.data);
                } else {
                    $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error: ' + (res.data || 'Failed') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', error);
                $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
            },
            complete: function() {
                btn.prop('disabled', false).text('✨ Improve Text');
            }
        });
    });
    
    /**
     * Grammar Check
     */
    $('#btn-grammar').click(function() {
        if (!hasKey) {
            alert('Please add API key first!');
            window.open(settingsUrl, '_blank');
            return;
        }
        
        const text = $('#text-grammar').val().trim();
        if (!text) {
            alert('Please enter text');
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('Checking...');
        $('#result-grammar').html('<div style="text-align:center;color:#16a34a;">🔄 Analyzing...</div>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiwa_grammar',
                nonce: nonce,
                text: text
            },
            success: function(res) {
                console.log('✓ Grammar response:', res);
                if (res.success) {
                    const lang = res.data.language || 'Unknown';
                    let html = '<div class="aiwa-result">';
                    html += '<strong>Language: ' + lang + ' | Score: ' + (res.data.score || 90) + '/100</strong><hr style="margin:10px 0;">';
                    
                    if (res.data.errors && res.data.errors.length > 0) {
                        res.data.errors.forEach(function(err) {
                            html += '<div style="margin:10px 0;padding:8px;background:#fff;border-left:3px solid #f59e0b;border-radius:4px;">';
                            html += '<strong style="color:#f59e0b;">' + err.type + '</strong><br>';
                            html += '<div><strong>Original:</strong> ' + err.original + '</div>';
                            html += '<div style="color:#16a34a;"><strong>Fix:</strong> ' + err.suggestion + '</div>';
                            html += '</div>';
                        });
                    } else {
                        html += '<div style="color:#16a34a;">✓ No errors found!</div>';
                    }
                    
                    html += '</div>';
                    $('#result-grammar').html(html);
                } else {
                    $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error</div>');
                }
            },
            error: function() {
                $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
            },
            complete: function() {
                btn.prop('disabled', false).text('✓ Check Grammar');
            }
        });
    });
    
    /**
     * Rewrite Content
     */
    $('#btn-rewrite').click(function() {
        if (!hasKey) {
            alert('Please add API key first!');
            window.open(settingsUrl, '_blank');
            return;
        }
        
        const text = $('#text-rewrite').val().trim();
        const tone = $('#tone').val();
        if (!text) {
            alert('Please select text first');
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('Rewriting...');
        $('#result-rewrite').html('<div style="text-align:center;color:#16a34a;">🔄 Rewriting...</div>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiwa_rewrite',
                nonce: nonce,
                text: text,
                tone: tone
            },
            success: function(res) {
                console.log('✓ Rewrite response:', res);
                if (res.success) {
                    $('#result-rewrite').html('<div class="aiwa-result">' + res.data + '</div>');
                    showApplyButton('#result-rewrite', res.data);
                } else {
                    $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error</div>');
                }
            },
            error: function() {
                $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
            },
            complete: function() {
                btn.prop('disabled', false).text('✏️ Rewrite');
            }
        });
    });
});
