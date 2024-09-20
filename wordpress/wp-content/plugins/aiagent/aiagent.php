<?php
/*
Plugin Name: Ai Agent
Plugin URI: https://e-hadron.com
Description: Ai agent
Version: 1.0
Author: Michał Osak
Author URI:
License:
*/

// Zapobieganie bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

function ai_agent_menu() {
    add_menu_page(
        'AI Agent', // Tytuł strony
        'AI Agent', // Tytuł menu
        'manage_options',  // Zdolność użytkownika
        'ai-agent', // Slug strony
        'mainpage', // Funkcja wyświetlająca zawartość strony
        'dashicons-admin-generic', // Ikona menu
        100 // Pozycja menu
    );
}
add_action('admin_menu', 'ai_agent_menu');

// Funkcja wyświetlająca zawartość strony administracyjnej
function mainpage() {
    ?>
    <div class="wrap">
        <h1>Welcome to AI Agent</h1>
        <p>Wtyczka do generacji treści</p>
    </div>
    <?php
}
