<?php
// URL to the WordPress site and wp-cron.php
$wordpress_url = 'http://wordpress/wp-cron.php?doing_wp_cron';

// Load WordPress
require_once '/var/www/html/wp-load.php';

// Fetch settings from the database
$settings = get_option('acg_settings');
error_log('Current settings: ' . print_r($settings, true));

// Function to fetch user-defined frequency from WordPress
function get_user_frequency() {
    $settings = get_option('acg_settings');
    return isset($settings['acg_schedule_frequency']) ? $settings['acg_schedule_frequency'] : 'daily';
}

// Function to convert frequency to seconds
function frequency_to_seconds($frequency) {
    switch ($frequency) {
        case 'minutely': return 60;
        case 'everyfive': return 300;
        case 'everyten': return 600;
        case 'everythirty': return 1800;
        case 'hourly': return 3600;
        case 'twicedaily': return 43200;
        case 'daily': return 86400;
        default: return 86400; // Default to daily if unknown frequency
    }
}

// Get user-defined timezone from settings
function get_user_timezone() {
    $settings = get_option('acg_settings');
    return isset($settings['acg_timezone']) ? $settings['acg_timezone'] : 'UTC';
}

// Calculate the scheduled timestamp in user's timezone
$scheduled_date_time = $settings['acg_schedule_date'] . ' ' . $settings['acg_schedule_time'];
$user_timezone = get_user_timezone();
$scheduled_datetime = new DateTime($scheduled_date_time, new DateTimeZone($user_timezone));
$scheduled_timestamp = $scheduled_datetime->getTimestamp();
error_log('Scheduled timestamp (UTC): ' . gmdate('Y-m-d H:i:s', $scheduled_timestamp));

// Get user-defined frequency
$frequency = get_user_frequency();
$interval = frequency_to_seconds($frequency);

// Determine the current time
$current_datetime = new DateTime('now', new DateTimeZone($user_timezone)); // Get current time in user's timezone
$current_timestamp = $current_datetime->getTimestamp();

// Path to the last run time file
$lastRunFile = __DIR__ . '/last_run.txt';

// Check if the script has run before
if (file_exists($lastRunFile)) {
    $lastRun = (int)file_get_contents($lastRunFile);
} else {
    // Initialize lastRun to the scheduled timestamp if it's the first run
    $lastRun = $scheduled_timestamp;
    file_put_contents($lastRunFile, $lastRun); // Save the initial last run time
}
// Introduce a short delay to avoid conflicts with user-planned events
usleep(500000);
// Determine the next run time based on the fixed scheduled timestamp and interval
if ($lastRun < $scheduled_timestamp) {
    // If the last run was before the scheduled timestamp, set next run to the scheduled timestamp
    $nextRun = $scheduled_timestamp;
} else {
    // Otherwise, add the interval to the last run time
    $nextRun = $scheduled_timestamp + ((floor(($lastRun - $scheduled_timestamp) / $interval) + 1) * $interval);
}

// Convert next run time to the user's local timezone
$nextRun_datetime = new DateTime();
$nextRun_datetime->setTimestamp($nextRun);
$nextRun_datetime->setTimezone(new DateTimeZone($user_timezone));

// Check if it's time to run
if ($current_timestamp < $nextRun) {
    error_log('Not yet time to run. Last run was at: ' . date('Y-m-d H:i:s', $lastRun) . '. Next run should be at: ' . $nextRun_datetime->format('Y-m-d H:i:s'));
    exit('Not yet time to run.');
}

// Update last run time to the current time
file_put_contents($lastRunFile, $current_timestamp);

// Call WordPress cron script
$ch = curl_init($wordpress_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

// Check for curl errors
if (curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));
} else {
    error_log('Response: ' . $response);
}

curl_close($ch);

echo 'Task executed.';
?>
