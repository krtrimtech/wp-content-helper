(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, Button, TextareaControl, SelectControl, Spinner, Notice } = wp.components;
    const { createElement: el, Fragment, useState, useEffect } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { compose } = wp.compose;

    // Main component
    const AIWritingAssistantSidebar = () => {
        const [selectedText, setSelectedText] = useState('');
        const [isLoading, setIsLoading] = useState(false);
        const [result, setResult] = useState(null);
        const [error, setError] = useState(null);
        const [activeTab, setActiveTab] = useState('grammar');
        const [rewriteTone, setRewriteTone] = useState('professional');
        const [generatePrompt, setGeneratePrompt] = useState('');
        const [currentLanguage, setCurrentLanguage] = useState(aiwaData.preferredLanguage);

        // Get selected text from editor
        const getSelectedText = () => {
            const selection = window.getSelection();
            const text = selection.toString().trim();
            if (text) {
                setSelectedText(text);
                return text;
            }
            
            // If no selection, get all post content
            const editor = wp.data.select('core/editor');
            const blocks = editor.getBlocks();
            let allText = '';
            
            blocks.forEach(block => {
                if (block.attributes && block.attributes.content) {
                    const temp = document.createElement('div');
                    temp.innerHTML = block.attributes.content;
                    allText += temp.textContent + '\n';
                }
            });
            
            setSelectedText(allText.trim());
            return allText.trim();
        };

        // Check grammar
        const checkGrammar = async () => {
            const text = getSelectedText();
            if (!text) {
                setError('Please select some text or write content first.');
                return;
            }

            setIsLoading(true);
            setError(null);
            setResult(null);

            try {
                const response = await fetch(aiwaData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'aiwa_check_grammar',
                        nonce: aiwaData.nonce,
                        text: text,
                        language: currentLanguage
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    setResult(data.data);
                } else {
                    setError(data.data.message || 'An error occurred');
                }
            } catch (err) {
                setError('Network error. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        // Rewrite content
        const rewriteContent = async () => {
            const text = getSelectedText();
            if (!text) {
                setError('Please select some text first.');
                return;
            }

            setIsLoading(true);
            setError(null);
            setResult(null);

            try {
                const response = await fetch(aiwaData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'aiwa_rewrite_content',
                        nonce: aiwaData.nonce,
                        text: text,
                        tone: rewriteTone,
                        language: currentLanguage
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    setResult({ rewritten: data.data });
                } else {
                    setError(data.data.message || 'An error occurred');
                }
            } catch (err) {
                setError('Network error. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        // Generate content
        const generateContent = async () => {
            if (!generatePrompt.trim()) {
                setError('Please enter what you want to generate.');
                return;
            }

            setIsLoading(true);
            setError(null);
            setResult(null);

            const context = getSelectedText();

            try {
                const response = await fetch(aiwaData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'aiwa_generate_content',
                        nonce: aiwaData.nonce,
                        prompt: generatePrompt,
                        context: context,
                        language: currentLanguage
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    setResult({ generated: data.data });
                } else {
                    setError(data.data.message || 'An error occurred');
                }
            } catch (err) {
                setError('Network error. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        // Detect language
        const detectLanguage = async () => {
            const text = getSelectedText();
            if (!text) {
                setError('Please select some text first.');
                return;
            }

            setIsLoading(true);
            setError(null);

            try {
                const response = await fetch(aiwaData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'aiwa_detect_language',
                        nonce: aiwaData.nonce,
                        text: text
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    setCurrentLanguage(data.data.code);
                    setResult({ language: data.data });
                } else {
                    setError(data.data.message || 'An error occurred');
                }
            } catch (err) {
                setError('Network error. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        // Copy to clipboard
        const copyToClipboard = (text) => {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        };

        // Replace selected text
        const replaceText = (newText) => {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(newText));
            }
        };

        // Render tabs
        const renderTabs = () => {
            const tabs = [
                { id: 'grammar', label: '✓ Grammar Check' },
                { id: 'rewrite', label: '✏️ Rewrite' },
                { id: 'generate', label: '✨ Generate' },
                { id: 'language', label: '🌐 Language' }
            ];

            return el('div', { className: 'aiwa-tabs' },
                tabs.map(tab => 
                    el(Button, {
                        key: tab.id,
                        className: activeTab === tab.id ? 'aiwa-tab active' : 'aiwa-tab',
                        onClick: () => {
                            setActiveTab(tab.id);
                            setResult(null);
                            setError(null);
                        }
                    }, tab.label)
                )
            );
        };

        // Render grammar tab
        const renderGrammarTab = () => {
            return el(Fragment, {},
                el('p', { className: 'aiwa-description' },
                    'Check grammar, spelling, and get writing suggestions. Select text or analyze entire post.'
                ),
                el(Button, {
                    isPrimary: true,
                    onClick: checkGrammar,
                    disabled: isLoading,
                    className: 'aiwa-action-btn'
                }, isLoading ? 'Analyzing...' : '🔍 Check Grammar'),
                
                result && result.errors && el('div', { className: 'aiwa-results' },
                    el('div', { className: 'aiwa-score' },
                        el('h3', {}, `Score: ${result.overall_score}/100`),
                        el('p', {}, result.summary)
                    ),
                    result.errors.length > 0 ? 
                        el('div', { className: 'aiwa-errors' },
                            el('h4', {}, 'Suggestions:'),
                            result.errors.map((err, idx) => 
                                el('div', { key: idx, className: 'aiwa-error-item' },
                                    el('span', { className: 'aiwa-error-type' }, err.type),
                                    el('div', { className: 'aiwa-error-original' },
                                        el('strong', {}, 'Original: '),
                                        err.original
                                    ),
                                    el('div', { className: 'aiwa-error-suggestion' },
                                        el('strong', {}, 'Suggestion: '),
                                        err.suggestion
                                    ),
                                    el('p', { className: 'aiwa-error-explanation' }, err.explanation)
                                )
                            )
                        ) :
                        el('p', { style: { color: '#16a34a', fontWeight: '600' } }, 
                            '✓ No errors found! Your writing looks great.'
                        )
                )
            );
        };

        // Render rewrite tab
        const renderRewriteTab = () => {
            return el(Fragment, {},
                el('p', { className: 'aiwa-description' },
                    'Rewrite your content in different tones and styles.'
                ),
                el(SelectControl, {
                    label: 'Rewrite Tone',
                    value: rewriteTone,
                    options: [
                        { label: 'Professional', value: 'professional' },
                        { label: 'Casual', value: 'casual' },
                        { label: 'Friendly', value: 'friendly' },
                        { label: 'Academic', value: 'academic' },
                        { label: 'Creative', value: 'creative' },
                        { label: 'Simple', value: 'simple' },
                        { label: 'Persuasive', value: 'persuasive' }
                    ],
                    onChange: setRewriteTone
                }),
                el(Button, {
                    isPrimary: true,
                    onClick: rewriteContent,
                    disabled: isLoading,
                    className: 'aiwa-action-btn'
                }, isLoading ? 'Rewriting...' : '✏️ Rewrite Content'),
                
                result && result.rewritten && el('div', { className: 'aiwa-results' },
                    el('h4', {}, 'Rewritten Content:'),
                    el('div', { className: 'aiwa-rewritten-content' }, result.rewritten),
                    el('div', { className: 'aiwa-action-buttons' },
                        el(Button, {
                            isSecondary: true,
                            onClick: () => copyToClipboard(result.rewritten)
                        }, '📋 Copy'),
                        el(Button, {
                            isPrimary: true,
                            onClick: () => {
                                // Insert at cursor or replace selection
                                const editor = wp.data.select('core/block-editor');
                                const selectedBlock = editor.getSelectedBlock();
                                if (selectedBlock) {
                                    wp.data.dispatch('core/block-editor').updateBlockAttributes(
                                        selectedBlock.clientId,
                                        { content: result.rewritten }
                                    );
                                }
                            }
                        }, '✓ Use This')
                    )
                )
            );
        };

        // Render generate tab
        const renderGenerateTab = () => {
            return el(Fragment, {},
                el('p', { className: 'aiwa-description' },
                    'Generate new content based on your instructions. AI will consider your existing content as context.'
                ),
                el(TextareaControl, {
                    label: 'What do you want to write?',
                    value: generatePrompt,
                    onChange: setGeneratePrompt,
                    placeholder: 'e.g., "Write an introduction about...", "Create a list of...", "Expand on..."',
                    rows: 4
                }),
                el(Button, {
                    isPrimary: true,
                    onClick: generateContent,
                    disabled: isLoading,
                    className: 'aiwa-action-btn'
                }, isLoading ? 'Generating...' : '✨ Generate Content'),
                
                result && result.generated && el('div', { className: 'aiwa-results' },
                    el('h4', {}, 'Generated Content:'),
                    el('div', { className: 'aiwa-generated-content' }, result.generated),
                    el('div', { className: 'aiwa-action-buttons' },
                        el(Button, {
                            isSecondary: true,
                            onClick: () => copyToClipboard(result.generated)
                        }, '📋 Copy'),
                        el(Button, {
                            isPrimary: true,
                            onClick: () => {
                                const editor = wp.data.select('core/block-editor');
                                const selectedBlock = editor.getSelectedBlock();
                                if (selectedBlock) {
                                    wp.data.dispatch('core/block-editor').updateBlockAttributes(
                                        selectedBlock.clientId,
                                        { content: result.generated }
                                    );
                                }
                            }
                        }, '✓ Insert')
                    )
                )
            );
        };

        // Render language tab
        const renderLanguageTab = () => {
            return el(Fragment, {},
                el('p', { className: 'aiwa-description' },
                    'Detect the language of your text or change the working language.'
                ),
                el(SelectControl, {
                    label: 'Current Language',
                    value: currentLanguage,
                    options: [
                        { label: 'English', value: 'en' },
                        { label: 'Spanish', value: 'es' },
                        { label: 'French', value: 'fr' },
                        { label: 'German', value: 'de' },
                        { label: 'Italian', value: 'it' },
                        { label: 'Portuguese', value: 'pt' },
                        { label: 'Hindi', value: 'hi' },
                        { label: 'Bengali', value: 'bn' },
                        { label: 'Punjabi', value: 'pa' },
                        { label: 'Telugu', value: 'te' },
                        { label: 'Marathi', value: 'mr' },
                        { label: 'Tamil', value: 'ta' },
                        { label: 'Urdu', value: 'ur' },
                        { label: 'Gujarati', value: 'gu' },
                        { label: 'Arabic', value: 'ar' },
                        { label: 'Japanese', value: 'ja' },
                        { label: 'Korean', value: 'ko' },
                        { label: 'Chinese', value: 'zh' },
                        { label: 'Russian', value: 'ru' }
                    ],
                    onChange: setCurrentLanguage
                }),
                el(Button, {
                    isSecondary: true,
                    onClick: detectLanguage,
                    disabled: isLoading,
                    className: 'aiwa-action-btn'
                }, isLoading ? 'Detecting...' : '🔍 Auto-Detect Language'),
                
                result && result.language && el('div', { className: 'aiwa-results' },
                    el('p', { style: { fontSize: '16px', fontWeight: '600' } },
                        `Detected: ${result.language.name}`
                    )
                )
            );
        };

        // Main render
        return el(Fragment, {},
            el(PluginSidebarMoreMenuItem, {
                target: 'ai-writing-assistant-sidebar',
                icon: 'edit'
            }, 'AI Writing Assistant'),
            
            el(PluginSidebar, {
                name: 'ai-writing-assistant-sidebar',
                icon: 'edit',
                title: 'AI Writing Assistant'
            },
                el(PanelBody, {},
                    !aiwaData.hasApiKey ? 
                        el(Notice, {
                            status: 'warning',
                            isDismissible: false
                        },
                            el('p', {},
                                'Please add your Gemini API key in your ',
                                el('a', { 
                                    href: aiwaData.profileUrl,
                                    target: '_blank'
                                }, 'profile settings'),
                                ' to use AI features.'
                            )
                        ) :
                        el(Fragment, {},
                            renderTabs(),
                            el('div', { className: 'aiwa-tab-content' },
                                activeTab === 'grammar' && renderGrammarTab(),
                                activeTab === 'rewrite' && renderRewriteTab(),
                                activeTab === 'generate' && renderGenerateTab(),
                                activeTab === 'language' && renderLanguageTab()
                            ),
                            error && el(Notice, {
                                status: 'error',
                                isDismissible: true,
                                onRemove: () => setError(null)
                            }, error),
                            isLoading && el('div', { className: 'aiwa-loading' },
                                el(Spinner)
                            )
                        )
                )
            )
        );
    };

    // Register plugin
    registerPlugin('ai-writing-assistant', {
        render: AIWritingAssistantSidebar,
        icon: 'edit'
    });

})(window.wp);