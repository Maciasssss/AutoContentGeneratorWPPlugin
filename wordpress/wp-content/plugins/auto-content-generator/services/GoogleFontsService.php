<?php
namespace ACG\Services;
class GoogleFontsService 
{
    public static function getGoogleFontsList() {
        $fonts_list = get_transient('acg_google_fonts_list');

        if (false === $fonts_list) {
            $api_key = defined('GOOGLE_FONTS_API_KEY') ? GOOGLE_FONTS_API_KEY : '';

            if (empty($api_key)) {
                return [];
            }

            $response = wp_remote_get("https://www.googleapis.com/webfonts/v1/webfonts?key=$api_key");

            if (is_wp_error($response)) {
                return [];
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['items'])) {
                $fonts_list = array_column($data['items'], 'family');
                set_transient('acg_google_fonts_list', $fonts_list, 12 * HOUR_IN_SECONDS);
            } else {
                return [];
            }
        }

        return $fonts_list;
    }

    public static function enqueueDynamicGoogleFonts() {
        error_log('enqueue_dynamic_google_fonts function is called'); 
        $settings = get_option('acg_settings');
        $font_family = isset($settings['acg_setting_font']) ? esc_attr($settings['acg_setting_font']) : 'Roboto'; 
    
        $formatted_font = str_replace(' ', '+', $font_family);
    
        $google_fonts_url = 'https://fonts.googleapis.com/css2?family=' . $formatted_font . ':wght@400;700&display=swap';
    
        wp_enqueue_style('dynamic-google-fonts', $google_fonts_url, array(), null);
    }
    
}
?>
