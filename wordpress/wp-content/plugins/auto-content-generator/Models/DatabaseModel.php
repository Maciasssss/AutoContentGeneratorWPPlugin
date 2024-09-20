<?php
// DatabaseModel.php
namespace ACG\Models;

class DatabaseModel
{
    private $tableName;

    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'acg_generated_prompts';
    }

    public function createTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            content text NOT NULL,
            date_generated datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            settings_hash varchar(32) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function getLastGeneratedEvent()
    {
        global $wpdb;
        $query = "SELECT * FROM {$this->tableName} ORDER BY date_generated DESC LIMIT 1";
        return $wpdb->get_row($query, ARRAY_A);
    }

    public function saveGeneratedContent($content)
    {
        global $wpdb;
        $wpdb->insert($this->tableName, [
            'content' => $content,
            'date_generated' => current_time('mysql'),
            'settings_hash' => md5(json_encode(get_option('acg_settings')))
        ]);
    }

    public function getAllGeneratedContent() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->tableName} ORDER BY id DESC");
    }
    
    public function getContentById($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tableName} WHERE id = %d", $id));
    }
    
    public function updateContent($id, $content) {
        global $wpdb;
        $wpdb->update($this->tableName, ['content' => $content], ['id' => $id], ['%s'], ['%d']);
    }
    
    public function deleteContentById($id) {
        global $wpdb;
        $wpdb->delete($this->tableName, ['id' => $id], ['%d']);
    }

    public function repostContentById($id) {
        global $wpdb;

        $wpdb->update(
            $this->tableName, 
            ['date_generated' => current_time('mysql')], 
            ['id' => $id] 
        );
    }
    
}
?>