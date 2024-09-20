<?php
/*
Plugin Name: Ai Agent ACG
Plugin URI: https://e-hadron.com
Description: AI agent that generates content based on scheduled events.
Version: 1.0
*/

namespace ACG;

use ACG\ContentEditView as ACGContentEditView;
use ACG\ContentListView as ACGContentListView;
use ACG\Controllers\ContentController;
use ACG\Controllers\ScheduleController;
use ACG\Controllers\SettingsController;
use ACG\Controllers\BlockController;
use ACG\Controllers\DatabaseManagingController;
use ACG\Models\SettingsModel;
use ACG\Models\DatabaseModel;
use ACG\Services\GoogleFontsService;
use ACG\Services\OpenAIService;
use ACG\Views\SettingsView;
use ACG\Views\BlockView;
use ACG\Views\DocumentationView;
use ACG\Views\ContentListView;
use ACG\Views\ContentEditView;

if (!defined('ABSPATH')) {
    exit; 
}
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php'; 

class AutoContentGenerator
{
    private $contentController;
    private $scheduleController;
    private $settingsController;
    private $blockController;
    private $databaseManagingController;

    public function __construct()
    {
        $this->initializePlugin();
    }

    private function initializePlugin()
    {
        // Initialize models
        $settingsModel = new SettingsModel();
        $databaseModel = new DatabaseModel();

        // Initialize services
        $openAIService = new OpenAIService($settingsModel->getApiKey());
        $googleFontsService = new GoogleFontsService();
        // Initialize ScheduleController
        $this->scheduleController = new ScheduleController($settingsModel);

        // Initialize ContentController
        $this->contentController = new ContentController(
            $settingsModel,
            $openAIService,
            $databaseModel
        );

        // Initialize SettingsView with ContentController
        $settingsView = new SettingsView($settingsModel, $this->contentController, $databaseModel);
        // Initialize ContenViews with DatabaseManagingController
        $ContentListView = new ContentListView;
        $ContentEditView = new ContentEditView($googleFontsService);
        // Initialize SettingsController with all dependencies
        $this->settingsController = new SettingsController(
            $settingsModel,
            $settingsView,
            $googleFontsService,
            $databaseModel,
            $openAIService,
            $this->scheduleController
        );

        $this->databaseManagingController = new DatabaseManagingController($databaseModel,$settingsModel,$googleFontsService);
         // Initialize BlockView
         $blockView = new BlockView($databaseModel,$settingsModel);

         // Initialize BlockController
         $this->blockController = new BlockController($blockView, $databaseModel);
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Add actions and filters
        add_action('admin_menu', [$this->settingsController, 'addAdminMenu']);
        add_action('admin_menu', [$this->databaseManagingController, 'addAdminMenu']);
        add_action('admin_init', [$this->settingsController, 'initializeSettings']);
        add_action('acg_generate_prompt_event', [$this->contentController, 'GenerateEvent']);
        add_action('init', [$this, 'registerBlockAssets']);
        add_action('admin_menu', [$this, 'registerDocumentationPage']); 
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', ['ACG\Services\GoogleFontsService', 'enqueueDynamicGoogleFonts']);

        add_filter('cron_schedules', [$this->scheduleController, 'addCustomCronSchedules']);
    }

    public function activate()
    {
        $this->contentController->createTable();
        $this->scheduleController->scheduleEvent();
    }

    public function deactivate()
    {
        $this->scheduleController->clearScheduledEvent();
    }

    public function registerBlockAssets()
    {
        wp_register_script(
            'acg-block-editor-script',
            plugins_url('includes/js/index.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-hooks'),
            filemtime(plugin_dir_path(__FILE__) . 'includes/js/index.js'),
            true // Load script in footer
        );
    
        wp_register_style(
            'acg-block-editor-style',
            plugins_url('includes/css/editor.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'includes/css/editor.css')
        );
    
        wp_register_style(
            'acg-block-style',
            plugins_url('includes/css/style.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'includes/css/style.css')
        );
    
        // Enqueue script and styles
        register_block_type('acg/block', array(
            'editor_script' => 'acg-block-editor-script',
            'editor_style' => 'acg-block-editor-style',
            'style' => 'acg-block-style',
        ));
    }
    public function registerDocumentationPage()
    {
        add_menu_page(
            'ACG Documentation',                // Page title
            'ACG Documentation',                // Menu title
            'manage_options',                   // Capability
            'acg-documentation',                // Menu slug
            [DocumentationView::class, 'documentation_page_html'], // Callback function
            'dashicons-media-document',         // Icon
            100                                 // Position
        );
    }

    public function enqueue_admin_scripts() {
        // Enqueue the Notify.js script
        wp_enqueue_script('notify-js', plugin_dir_url(__FILE__) . 'includes/js/Notify.js', ['jquery'], null, true);
    }
    
}

// Initialize the plugin
new AutoContentGenerator();
//test