<?php
// SettingsModel.php
namespace ACG\Models;

class SettingsModel
{
    public function getSettings()
    {
        $defaults = [
            'acg_setting_field_1' => '',
            'acg_setting_field_2' => 'option1',
            // Add other default settings
        ];
        $settings = get_option('acg_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }

    public function updateSettings($settings)
    {
        update_option('acg_settings', $settings);
    }

    public function getApiKey()
    {
        $settings = $this->getSettings();
        return isset($settings['acg_api_key']) ? $settings['acg_api_key'] : '';
    }
    public static function hideApiKey($api_key) {
        $truncated_length = 6;
        $start_part = substr($api_key, 6, $truncated_length);
        $end_part = substr($api_key, -$truncated_length);
        return $start_part . '...' . $end_part;
    }

}
?>