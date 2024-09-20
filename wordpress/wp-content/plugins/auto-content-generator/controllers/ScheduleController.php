<?php
// ScheduleController.php
namespace ACG\Controllers;

use ACG\Models\SettingsModel;

class ScheduleController
{
    private $settingsModel;

    public function __construct(SettingsModel $settingsModel)
    {
        $this->settingsModel = $settingsModel;
    }

    public function scheduleEvent()
    {
        $settings = $this->settingsModel->getSettings();
        $timestamp = $this->getEventTimestamp($settings);
        $frequency = $settings['acg_schedule_frequency'];

        if (!wp_next_scheduled('acg_generate_prompt_event')) {
            wp_schedule_event($timestamp, $frequency, 'acg_generate_prompt_event');
        }
    }

    public function clearScheduledEvent()
    {
        $timestamp = wp_next_scheduled('acg_generate_prompt_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'acg_generate_prompt_event');
        }
    }

    public function addCustomCronSchedules($schedules)
    {
        $schedules['minutely'] = [
            'interval' => 60,
            'display' => __('Every Minute')
        ];
        $schedules['everyfive'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes')
        ];
        $schedules['everyten'] = [
            'interval' => 600,
            'display' => __('Every 10 Minutes')
        ];
        $schedules['everythirty'] = [
            'interval' => 1800,
            'display' => __('Every 30 Minutes')
        ];
        $schedules['hourly'] = [
            'interval' => 3600,
            'display' => __('Hourly')
        ];
        $schedules['twicedaily'] = [
            'interval' => 43200,
            'display' => __('Twice Daily')
        ];
        $schedules['daily'] = [
            'interval' => 86400,
            'display' => __('Daily')
        ];
        return $schedules;
    }

    private function getEventTimestamp($settings)
    {
        $date = $settings['acg_schedule_date'];
        $time = $settings['acg_schedule_time'];
        return strtotime("$date $time");
    }
}
?>