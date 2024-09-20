<?php
namespace ACG\Controllers;

use ACG\Models\SettingsModel;
use ACG\Views\SettingsView;
use ACG\Services\GoogleFontsService;
use ACG\Services\OpenAIService;
use ACG\Models\DatabaseModel;
use ACG\Controllers\ScheduleController;
use DateTime;
use DateTimeZone;

class SettingsController {
    private $settingsModel;
    private $settingsView;
    private $googleFontsService;
    private $databaseModel;  
    private $openAIService; 
    private $scheduleController;
    
    public function __construct
    (
        SettingsModel $settingsModel,
        SettingsView $settingsView,
        GoogleFontsService $googleFontsService,
        DatabaseModel $databaseModel,
        OpenAIService $openAIService,
        ScheduleController $scheduleController
    ) 
    {
        $this->settingsModel = $settingsModel;
        $this->settingsView = $settingsView;
        $this->googleFontsService = $googleFontsService;
        $this->databaseModel = $databaseModel; 
        $this->openAIService = $openAIService;
        $this->scheduleController = $scheduleController;
    }

    public function addAdminMenu() {
        add_menu_page(
            'AI Agent ACG',
            'AI Agent ACG',
            'manage_options',
            'acg-settings',
            [$this, 'renderOptionsPage'],
            'dashicons-admin-generic',
            100
        );
    }

    public function initializeSettings() {
        register_setting('acg_settings_group', 'acg_settings', [$this, 'validateSettings']);
        $this->addSettingsSections();
        $this->addSettingsFields();
    }

    private function addSettingsSections() {
        add_settings_section(
            'acg_settings_section',
            'General Settings',
            [$this, 'renderSettingsSectionCallback'],
            'acg-settings'
        );
    }

    private function addSettingsFields() {
        $fields = [
            'acg_setting_field_1' => 'Prompt',
            'acg_setting_category' => 'Select Category',
            'acg_setting_title' => 'Title',
            'acg_setting_title_size' => 'Title Size',
            'acg_setting_title_alignment' => 'Title Alignment',
            'acg_setting_category_size' => 'Category Size',
            'acg_setting_category_alignment' => 'Category Alignment',
            'acg_setting_prompt_size' => 'Prompt Size',
            'acg_setting_prompt_alignment' => 'Prompt Alignment',
            'acg_schedule_frequency' => 'Schedule Frequency',
            'acg_api_key' => 'API Key',
            'acg_schedule_date' => 'Schedule Date',
            'acg_schedule_time' => 'Schedule Time',
            'acg_setting_font' => 'Font Selection'
        ];

        foreach ($fields as $fieldName => $fieldLabel) {
            add_settings_field(
                $fieldName,
                $fieldLabel,
                [$this, 'renderField'],
                'acg-settings',
                'acg_settings_section',
                ['field_name' => $fieldName]
            );
        }
    }

    public function renderSettingsSectionCallback() {
        echo '<p>General settings for ACG plugin.</p>';
    }

    public function renderField($args) {
        $fieldName = $args['field_name'];
        $settings = $this->settingsModel->getSettings();
        $value = isset($settings[$fieldName]) ? $settings[$fieldName] : '';
    
        switch ($fieldName) {
            case 'acg_setting_category':
                $options = ['fruits' => 'Fruits', 'vegetables' => 'Vegetables', 'dairy' => 'Dairy'];
                $this->settingsView->renderSelectField($fieldName, $value, $options);
                break;
            case 'acg_schedule_frequency':
                $options = [
                    'minutely' => 'Every Minute',
                    'everyfive' => 'Every 5 Minutes',
                    'everyten' => 'Every 10 Minutes',
                    'everythirty' => 'Every 30 Minutes',
                    'hourly' => 'Hourly',
                    'twicedaily' => 'Twice Daily',
                    'daily' => 'Daily'
                ];
                $this->settingsView->renderSelectField($fieldName, $value, $options);
                break;
            case 'acg_api_key':
                $apiKeyHidden = $this->settingsModel->hideApiKey($value);
                $this->settingsView->renderApiKeyField($value, $apiKeyHidden);
                break;
            case 'acg_schedule_date':
                $this->settingsView->renderDateField($fieldName, $value);
                break;
            case 'acg_schedule_time':
                $this->settingsView->renderTimeField($fieldName, $value);
                break;
            case 'acg_setting_font':
                $fontsList = $this->googleFontsService->getGoogleFontsList();
                $this->settingsView->renderFontField($fieldName, $value, $fontsList);
                break;
            case 'acg_setting_title_alignment':
            case 'acg_setting_category_alignment':
            case 'acg_setting_prompt_alignment':
                $this->settingsView->renderAlignmentField($fieldName, $value);
                break;
            default:
                $this->settingsView->renderTextField($fieldName, $value);
                break;
        }
    }
    

    public function renderOptionsPage() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['acg_settings_submitted']) && check_admin_referer('acg_settings_save')) {
            $this->handleSaveSettings();
        }

        if (isset($_POST['generate_content']) && check_admin_referer('acg_generate_content')) {
            $this->handleGenerateContent();
            $this->googleFontsService->enqueueDynamicGoogleFonts();
        }

        if (isset($_POST['stop_scheduled_event']) && check_admin_referer('acg_stop_event')) {
            $this->handleStopScheduledEvent();
            $this->googleFontsService->enqueueDynamicGoogleFonts();
        }

        if (isset($_POST['generate_content_once']) && check_admin_referer('acg_generate_content_once')) {
            $this->handleGenerateContentOnce();
            $this->googleFontsService->enqueueDynamicGoogleFonts();
        }

        settings_errors('acg_messages');
        $this->settingsView->renderSettingsPage();
    }

    private function sanitizeSettings(array $settings): array {
        return [
            'acg_setting_field_1' => sanitize_text_field($settings['acg_setting_field_1'] ?? ''),
            'acg_schedule_frequency' => sanitize_text_field($settings['acg_schedule_frequency'] ?? 'daily'),
            'acg_schedule_date' => sanitize_text_field($settings['acg_schedule_date'] ?? ''),
            'acg_schedule_time' => sanitize_text_field($settings['acg_schedule_time'] ?? ''),
            'acg_setting_font' => sanitize_text_field($settings['acg_setting_font'] ?? ''),
            'acg_setting_title' => sanitize_text_field($settings['acg_setting_title'] ?? ''),
            'acg_setting_title_size' => sanitize_text_field($settings['acg_setting_title_size'] ?? ''),
            'acg_setting_title_alignment' => sanitize_text_field($settings['acg_setting_title_alignment'] ?? ''),
            'acg_setting_category_size' => sanitize_text_field($settings['acg_setting_category_size'] ?? ''),
            'acg_setting_category_alignment' => sanitize_text_field($settings['acg_setting_category_alignment'] ?? ''),
            'acg_setting_prompt_size' => sanitize_text_field($settings['acg_setting_prompt_size'] ?? ''),
            'acg_setting_prompt_alignment' => sanitize_text_field($settings['acg_setting_prompt_alignment'] ?? ''),
            'acg_setting_category' => sanitize_text_field($settings['acg_setting_category'] ?? ''),
            'acg_api_key' => sanitize_text_field($settings['acg_api_key'] ?? ''),
        ];
    }

    private function handleSaveSettings() {
        if (isset($_POST['acg_settings'])) {
            $settings = $_POST['acg_settings'];
            $sanitizedSettings = $this->sanitizeSettings($settings);
            $this->settingsModel->updateSettings($sanitizedSettings);
            $this->googleFontsService->enqueueDynamicGoogleFonts();
            $this->handleStopScheduledEvent();
            add_settings_error('acg_messages', 'acg_message', 'Settings Saved', 'updated');
        } else {
            add_settings_error('acg_messages', 'acg_message', 'No settings to save.', 'error');
        }
    }
    
    private function handleGenerateContent() {
        $settings = $this->settingsModel->getSettings();
        
        // Ensure settings are valid
        if (empty($settings['acg_schedule_date']) || empty($settings['acg_schedule_time'])) {
            add_settings_error('acg_messages', 'acg_message', 'Schedule date or time is missing.', 'error');
            return;
        }

        // Retrieve and set the user's timezone
        $user_timezone = isset($settings['acg_timezone']) ? new DateTimeZone($settings['acg_timezone']) : new DateTimeZone('UTC');
        
        // Combine date and time to create a DateTime object
        $scheduled_date_time = $settings['acg_schedule_date'] . ' ' . $settings['acg_schedule_time'];
        $scheduled_datetime = new DateTime($scheduled_date_time, $user_timezone);
        
        // Convert the scheduled time to UTC for accurate comparison
        $scheduled_datetime_utc = clone $scheduled_datetime;
        $scheduled_datetime_utc->setTimezone(new DateTimeZone('UTC'));
        $scheduled_timestamp = $scheduled_datetime_utc->getTimestamp();
        
        // Schedule the event
        if (!wp_next_scheduled('acg_generate_prompt_event')) {
            wp_schedule_event($scheduled_timestamp, $settings['acg_schedule_frequency'], 'acg_generate_prompt_event');
            error_log('Event scheduled with frequency: ' . $settings['acg_schedule_frequency'] . ' starting at: ' . date('Y-m-d H:i:s', $scheduled_timestamp));
        } else {
            error_log('Event already scheduled.');
        }
    
        // Trigger immediate execution of the scheduler
        $scheduler_url = 'http://wordpress/wp-content/plugins/auto-content-generator/includes/scheduler.php?doing_wp_cron';
        wp_remote_get($scheduler_url);
    }

    private function handleGenerateContentOnce() {
        $settings = $this->settingsModel->getSettings();
    
        $contentController = new ContentController(
            $this->settingsModel,  
            $this->openAIService,
            $this->databaseModel
        );
    
        $contentController->GenerateEvent($settings);
    
        add_settings_error('acg_messages', 'acg_message', 'Content generated successfully.', 'updated');
    }

    private function handleStopScheduledEvent() {
        if ($timestamp = wp_next_scheduled('acg_generate_prompt_event')) {
            wp_unschedule_event($timestamp, 'acg_generate_prompt_event');
            add_settings_error('acg_messages', 'acg_message', 'Scheduled event stopped.', 'updated');
        } else {
            add_settings_error('acg_messages', 'acg_message', 'No scheduled event found.', 'error');
        }
    }

    public function validateSettings(array $input): array {
        $sanitized = $this->sanitizeSettings($input);
        return $sanitized;
    }
}
