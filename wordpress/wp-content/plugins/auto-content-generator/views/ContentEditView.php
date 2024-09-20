<?php
namespace ACG\Views;

use ACG\Services\GoogleFontsService;

class ContentEditView
{
    private $googleFontsService;
    private $googleFontsList;

    public function __construct(GoogleFontsService $googleFontsService) 
    {
        $this->googleFontsService = $googleFontsService;
        $this->googleFontsList = $this->googleFontsService->getGoogleFontsList();
    }

    public static function renderEditContentPage($content, $settings, GoogleFontsService $googleFontsService)
    {
        $googleFontsList = $googleFontsService->getGoogleFontsList();
        $googleFontsString = '';

        $fontFormats = array();
        foreach ($googleFontsList as $font) {
            $formattedFontName = str_replace(' ', '+', $font);
            $fontFormats[] = "{$font}={$formattedFontName}";
        }
        $googleFontsString = implode(';', $fontFormats);

        // Enqueue the initially selected Google Font using the provided enqueue function
        GoogleFontsService::enqueueDynamicGoogleFonts();

        ?>
        <div class="wrap">
            <h1>Edit Content</h1>
            <form method="post" action="">
                <?php wp_nonce_field('acg_edit_content_save'); ?>

                <?php
                // Render wp_editor with content
                wp_editor(
                    $content->content, // Content to display
                    'acg_content_editor', // ID of the editor
                    array(
                        'textarea_name' => 'acg_content',
                        'media_buttons' => false,
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'editor_class' => 'acg-editor-class',
                        'editor_height' => 300,
                        'tinymce' => array(
                            'toolbar1' => 'formatselect fontselect fontsizeselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link unlink',
                            'fontsize_formats' => '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt',
                            'font_formats' => $googleFontsString, // Ensure this is a string
                            'setup' => "function(editor) {
                                function loadGoogleFont(fontName) {
                                    // Remove existing font link if present
                                    var existingLink = document.querySelector('link[data-font]');
                                    if (existingLink) {
                                        existingLink.remove();
                                    }

                                    // Return early if fontName is empty
                                    if (!fontName || fontName.trim() === '') {
                                        return;
                                    }

                                    // Properly format the font name for the URL
                                    var formattedFontName = fontName.replace(/\s+/g, '+');
                                    
                                    // Load the selected Google Font dynamically
                                    var link = document.createElement('link');
                                    link.rel = 'stylesheet';
                                    link.href = 'https://fonts.googleapis.com/css2?family=' + formattedFontName + ':wght@400;700&display=swap';
                                    link.setAttribute('data-font', fontName);
                                    document.head.appendChild(link);
                                }

                                function updateEditorStyles() {
                                    var selectedFont = editor.queryCommandValue('FontName');
                                    var selectedFontSize = editor.queryCommandValue('FontSize');

                                    // Apply the font to the editor's content
                                    editor.getBody().style.fontFamily = selectedFont;
                                    editor.getBody().style.fontSize = selectedFontSize;

                                    // Load the selected font
                                    loadGoogleFont(selectedFont);
                                }

                                // Listen for font and font size changes
                                editor.on('init', function() {
                                    editor.on('FontNameChanged', updateEditorStyles);
                                    editor.on('FontSizeChanged', updateEditorStyles);

                                    // Initial style update
                                    updateEditorStyles();
                                });

                                // Add custom event listeners for font and font size changes
                                editor.on('change', function(e) {
                                    if (e.command === 'FontName' || e.command === 'FontSize') {
                                        editor.fire(e.command + 'Changed');
                                    }
                                });
                            }"
                        )
                    )
                );
                ?>

                <?php submit_button('Save Content', 'primary', 'acg_edit_content_submitted'); ?>
            </form>
        </div>
        <?php
    }
}
?>
