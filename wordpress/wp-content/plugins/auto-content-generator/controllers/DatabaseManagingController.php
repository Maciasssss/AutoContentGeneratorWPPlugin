<?php
namespace ACG\Controllers;

use ACG\Views\ContentEditView;
use ACG\Views\ContentListView;
use ACG\Models\DatabaseModel;
use ACG\Models\SettingsModel;
use ACG\Services\GoogleFontsService;

class DatabaseManagingController {
    private $databaseModel;
    private $settingsModel;
    private $googleFontsService;

    public function __construct(DatabaseModel $databaseModel, SettingsModel $settingsModel, GoogleFontsService $googleFontsService) {
        $this->databaseModel = $databaseModel;
        $this->settingsModel = $settingsModel;
        $this->googleFontsService = $googleFontsService;
    }

    public function addAdminMenu() {
        add_submenu_page(
            'acg-settings',
            'Manage Generated Content',
            'Manage Content',
            'manage_options',
            'acg-manage-content',
            [$this, 'renderManageContentPage']
        );
    }

    public function renderManageContentPage() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['delete_id'])) {
            $this->databaseModel->deleteContentById(intval($_GET['delete_id']));
            set_transient('acg_edit_success', 'Content deleted successfully', 30);
        }

        if (isset($_GET['edit_id'])) {
            $this->renderEditContentPage(intval($_GET['edit_id']));
        } elseif (isset($_GET['repost_id'])) {
            $this->repostContent(intval($_GET['repost_id']));
        } else {
            $contentList = $this->databaseModel->getAllGeneratedContent();
            $this->renderContentList($contentList);
        }
    }

    private function repostContent($id) {
        // Assuming there's a method to move the content to the top (or set its date to now)
        $this->databaseModel->repostContentById($id);
        set_transient('acg_edit_success', 'Content reposted successfully', 30);
        $contentList = $this->databaseModel->getAllGeneratedContent();
        $this->renderContentList($contentList, 1, 1); // Redirect to the first page after repost
    }

    private function renderEditContentPage($id) {
        $content = $this->databaseModel->getContentById($id);
        if (!$content) {
            echo '<p>Content not found.</p>';
            return;
        }
        $settings = $this->settingsModel->getSettings(); // Get the settings

        if (isset($_POST['acg_edit_content_submitted'])) {
            check_admin_referer('acg_edit_content_save');

            $updatedContent = wp_unslash($_POST['acg_content']);
            $this->databaseModel->updateContent($id, $updatedContent);
            // Save settings
            $settings = [
                'acg_setting_font' => sanitize_text_field($_POST['acg_setting_font']),
                'acg_setting_prompt_size' => sanitize_text_field($_POST['acg_setting_prompt_size']),
                'acg_setting_prompt_alignment' => sanitize_text_field($_POST['acg_setting_prompt_alignment']),
                'acg_setting_title_alignment' => sanitize_text_field($_POST['acg_setting_title_alignment']),
            ];
            update_option('acg_settings', $settings);
            set_transient('acg_edit_success', 'Content updated successfully', 30);

            $contentList = $this->databaseModel->getAllGeneratedContent();
            $this->renderContentList($contentList, 1, 1); // Redirect to the first page after edit
            return;
        }
        $googleFontsService = new GoogleFontsService();
        ContentEditView::renderEditContentPage($content, $settings, $googleFontsService);
    }

    private function renderContentList($contentList, $currentPage = 1, $totalPages = 1) {
        ContentListView::renderContentList($contentList, $currentPage, $totalPages);
    }
}


?>
