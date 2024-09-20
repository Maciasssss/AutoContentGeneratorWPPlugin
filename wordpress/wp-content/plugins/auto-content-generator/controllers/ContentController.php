<?php
namespace ACG\Controllers;

use ACG\Models\SettingsModel;
use ACG\Models\DatabaseModel;
use ACG\Services\OpenAIService;
use ACG\Views\SettingsView;

class ContentController
{
    private $settingsModel;
    private $databaseModel;
    private $openAIService;

    public function __construct
    (
        SettingsModel $settingsModel,
        OpenAIService $openAIService,
        DatabaseModel $databaseModel,
    )
    {
        $this->settingsModel = $settingsModel;
        $this->openAIService = $openAIService;
        $this->databaseModel = $databaseModel;
    }
    public function getSettings()
    {
        return $this->settingsModel->getSettings();
    }

    public function generatePromptFromSettings()
    {
        $settings = $this->settingsModel->getSettings();
        return $this->generatePrompt($settings);
    }

    public function generatePrompt(array $settings)
    {
        $frequency = isset($settings['acg_schedule_frequency']) ? $settings['acg_schedule_frequency'] : 'daily';
        $last_prompt = get_transient('acg_last_generated_prompt');
        $settings_hash = md5(json_encode($settings));
        
        // Check if a recent prompt with the same settings exists
        if (is_array($last_prompt) && isset($last_prompt['date_generated']) && isset($last_prompt['settings_hash']) && isset($last_prompt['prompt'])) {
            if (strtotime($last_prompt['date_generated']) > strtotime('-7 days') && $last_prompt['settings_hash'] === $settings_hash) {
                return $last_prompt['prompt']; // Use previous prompt if conditions are met
            }
        }
        
        // Extract settings with default values
        $prompt = "Generate content based on the following specifications. Do not include this instruction in the output:\n";
        $prompt .= "1. **Prompt Text**: " . esc_html(isset($settings['acg_setting_field_1']) ? $settings['acg_setting_field_1'] : '') . "\n";
        $prompt .= "2. **Category/Topic**: " . esc_html(isset($settings['acg_setting_category']) ? $settings['acg_setting_category'] : 'default_category') . "\n";
        $prompt .= "3. **Title**: " . esc_html(isset($settings['acg_setting_title']) ? $settings['acg_setting_title'] : 'default_title') . "\n";
        $prompt .= "Ensure the content is formatted appropriately, with the title as a heading, and the category/topic clearly marked. For example, use markdown to format headings and subheadings.\n";
        $prompt .= "Example format:\n";
        $prompt .= "# Title\n";
        $prompt .= "## Category/Topic\n";
        $prompt .= "Content...\n";
        $prompt .= "Ensure to generate the content without including the prompt instructions or any metadata.";
    
        // Store the generated prompt as a transient
        $transient_data = [
            'prompt' => $prompt,
            'settings_hash' => md5(json_encode($settings)),
            'date_generated' => current_time('mysql')
        ];
        set_transient('acg_last_generated_prompt', $transient_data, 12 * HOUR_IN_SECONDS);
        return $prompt;
    }

    public function generateContent(array $settings)
    {
        $prompt = $this->generatePrompt($settings);
        $apiKey = $this->settingsModel->getApiKey();

        if (empty($apiKey)) {
            return ['content' => 'API key is missing.', 'prompt' => $prompt, 'logs' => ['API key is missing']];
        }

        $response = $this->openAIService->generateWithChatGPT($prompt);
        //May be here use transient just to store response for styling fuction, we dont want to sace un styled content anyways
        $transient_data = [
            'prompt' => $prompt,
            'settings_hash' => md5(json_encode($settings)),
            'date_generated' => current_time('mysql'),
            'content' => $response['content'], // Dodajemy content do transienta
        ];
        set_transient('last_event', $transient_data, 12 * HOUR_IN_SECONDS);
        //$this->databaseModel->saveGeneratedContent($response['content']);

        return [
            'content' => $response['content'],
            'prompt' => $prompt,
            'logs' => $response['logs'] ?? []
        ];
    }
    //May no be nessecery 
    public function GenerateEvent($settings = null)
    {
        if ($settings === null) {
            $settings = $this->getSettings();
        }
        error_log('generatePromptAndDisplay called with settings: ' . print_r($settings, true));
        $this->generateContent($settings);
    }
    
    public function createTable()
    {
        $this->databaseModel->createTable();
    }
}
?>
