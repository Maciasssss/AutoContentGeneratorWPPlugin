<?php
namespace ACG\Views;

use ACG\Models\SettingsModel;
use ACG\Controllers\ContentController;
use ACG\Services\GoogleFontsService;
use ACG\Models\DatabaseModel;

use Parsedown;

class SettingsView {
    private $settingsModel;
    private $contentController;
    private $databaseModel;

    public function __construct
    (
        SettingsModel $settingsModel,
        ContentController $contentController,
        DatabaseModel $databaseModel
    ) 
    {
        $this->settingsModel = $settingsModel;
        $this->contentController = $contentController;
        $this->databaseModel = $databaseModel;
    }

    public function renderSettingsPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $settings = $this->settingsModel->getSettings();
        $prompt = $this->contentController->generatePrompt($settings);
        ?>
        <div class="wrap">
            <h1>AI Agent ACG</h1>
            <!-- Settings Form -->
            <form method="post" action="">
                <?php
                settings_fields('acg_settings_group');
                do_settings_sections('acg-settings');
                wp_nonce_field('acg_settings_save');
                submit_button('Save Settings', 'primary', 'acg_settings_submitted');
                ?>
            </form>
            <!-- Generate Content Form -->
            <form method="post" action="">
                <?php wp_nonce_field('acg_generate_content'); ?>
                <input type="hidden" name="generate_content" value="1">
                <?php submit_button('Generate Content', 'secondary', 'submit', false); ?>
            </form>
            <form method="post" action="">
                <?php wp_nonce_field('acg_generate_content_once'); ?>
                <input type="hidden" name="generate_content_once" value="1">
                <?php submit_button('Single Generate Content', 'secondary', 'submit', false); ?>
            </form>
            <!-- Stop Scheduled Event Form -->
            <form id="stop-scheduled-event-form" method="post" action="">
                <?php wp_nonce_field('acg_stop_event'); ?>
                <input type="hidden" name="stop_scheduled_event" value="1">
                <?php submit_button('Stop Scheduled Event', 'secondary', 'submit', false); ?>
            </form>
            <div id="acg-generated-content">
                <?php $this->displayGeneratedContent(); ?>
            </div>
            <div class="current-settings">
                <h3>Current Settings:</h3>
                <ul>
                    <li>Prompt: <?php echo esc_html($settings['acg_setting_field_1'] ?? ''); ?></li>
                    <li>Category: <?php echo esc_html($settings['acg_setting_category'] ?? ''); ?></li>
                    <li>Title: <?php echo esc_html($settings['acg_setting_title'] ?? ''); ?></li>
                    <li>Schedule Frequency: <?php echo esc_html($settings['acg_schedule_frequency'] ?? ''); ?></li>
                </ul>
            </div>
            <div class="generated-prompt">
                <h3>Generated Prompt to be Sent to AI:</h3>
                <p><?php echo esc_html($prompt); ?></p>
            </div>
        </div>
        <?php
    }

    public function displayGeneratedContent() {
        $latestContent = get_transient('last_event');
        $settings = $this->settingsModel->getSettings();
        GoogleFontsService::enqueueDynamicGoogleFonts();

        if ($latestContent && !empty($latestContent['content'])) {
            $content = $latestContent['content'];
            $settingsHash = $latestContent['settings_hash'];

            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

            $Parsedown = new \Parsedown();
            $htmlContent = $Parsedown->text($content);

            if (!empty($htmlContent)) {
                $htmlContent = $this->applyStyles($htmlContent, $settings);

                echo "<h2>Generated Content:</h2>";
                echo wp_kses_post($htmlContent);

                // Zapisz sformatowany HTML do bazy danych
                $this->databaseModel->saveGeneratedContent($htmlContent);
            } else {
                echo "<p>Error processing content.</p>";
            }
        } else {
            echo "<p>No content generated yet.</p>";
        }
    }

    private function applyStyles($htmlContent, $settings)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();

        $this->applyStylesToElements($dom, 'h1', 
            $settings['acg_setting_title_size'] ?? '', 
            $settings['acg_setting_title_alignment'] ?? '', 
            $settings['acg_setting_font'] ?? ''
        );
        $this->applyStylesToElements($dom, ['h2', 'h3', 'h4', 'h5', 'h6'], 
            $settings['acg_setting_category_size'] ?? '', 
            $settings['acg_setting_category_alignment'] ?? '', 
            $settings['acg_setting_font'] ?? ''
        );
        $this->applyStylesToElements($dom, ['p', 'ul', 'ol', 'li'], 
            $settings['acg_setting_prompt_size'] ?? '', 
            $settings['acg_setting_prompt_alignment'] ?? '', 
            $settings['acg_setting_font'] ?? ''
        );

        $htmlContent = $dom->saveHTML();
        $htmlContent = preg_replace('/<\/p>\s*<p>/', '</p><p>', $htmlContent);
        $htmlContent = preg_replace('/<p>\s*<\/p>/', '', $htmlContent);

        return '<div style="text-align: ' . esc_attr($settings['acg_setting_prompt_alignment'] ?? '') . '; font-family: ' . esc_attr($settings['acg_setting_font'] ?? '') . ';">' . $htmlContent . '</div>';
    }

    private function applyStylesToElements($dom, $tags, $size, $alignment, $font)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            foreach ($elements as $element) {
                $style = '';
                if ($size) {
                    $style .= 'font-size: ' . esc_attr($size) . '; ';
                }
                if ($alignment) {
                    $style .= 'text-align: ' . esc_attr($alignment) . '; ';
                }
                if ($font) {
                    $style .= 'font-family: ' . esc_attr($font) . '; ';
                }
                $element->setAttribute('style', $style);
            }
        }
    }


    public function renderTextField($name, $value) {
        ?>
        <input type="text" name="acg_settings[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>">
        <?php
    }

    public function renderSelectField($name, $value, $options) {
        ?>
        <select name="acg_settings[<?php echo esc_attr($name); ?>]">
            <?php foreach ($options as $optionValue => $optionLabel): ?>
                <option value="<?php echo esc_attr($optionValue); ?>" <?php selected($value, $optionValue); ?>><?php echo esc_html($optionLabel); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    public function renderAlignmentField($name, $value) {
        $options = [
            'left' => 'Left',
            'center' => 'Center',
            'right' => 'Right',
            'justify' => 'Justify',
        ];
        $this->renderSelectField($name, $value, $options);
    }
    
    public function renderApiKeyField($apiKey, $apiKeyHidden) {
        ?>
        <div id="api-key-container">
            <input type="text" name="acg_settings[acg_api_key]" value="<?php echo esc_attr($apiKey); ?>" style="display: none;">
            <input type="text" name="acg_settings[acg_api_key_hidden]" value="<?php echo esc_attr($apiKeyHidden); ?>" readonly>
            <button type="button" id="reveal-api-key">Reveal/Edit API Key</button>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const revealButton = document.getElementById('reveal-api-key');
            const fullApiKey = document.querySelector('input[name="acg_settings[acg_api_key]"]');
            const hiddenApiKey = document.querySelector('input[name="acg_settings[acg_api_key_hidden]"]');

            let isEditing = false;

            revealButton.addEventListener('click', function() {
                if (!isEditing) {
                    fullApiKey.style.display = 'inline-block';
                    hiddenApiKey.style.display = 'none';
                    fullApiKey.focus();
                    fullApiKey.setSelectionRange(0, fullApiKey.value.length);
                    revealButton.textContent = 'Hide API Key';
                } else {
                    fullApiKey.style.display = 'none';
                    hiddenApiKey.style.display = 'inline-block';
                    revealButton.textContent = 'Reveal/Edit API Key';
                }
                isEditing = !isEditing;
            });
        });
        </script>
        <?php
    }

    public function renderDateField($name, $value) {
        ?>
        <input type="date" name="acg_settings[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>">
        <?php
    }

    public function renderTimeField($name, $value) {
        ?>
        <input type="time" name="acg_settings[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>">
        <?php
    }

    public function renderFontField($name, $value, $fontsList) {
        ?>
        <select name="acg_settings[<?php echo esc_attr($name); ?>]" id="<?php echo esc_attr($name); ?>">
            <?php foreach ($fontsList as $font): ?>
                <option value="<?php echo esc_attr($font); ?>" <?php selected($value, $font); ?>><?php echo esc_html($font); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}
?>
