<?php
/*
Plugin Name: RooSecure Security Suite
Description: Suite de seguridad con protección de login, firewall, modo oscuro y dashboard visual.
Version: 8.5.5
Author: Diego
License: GPL2
Text Domain: roosecure
*/

if (!defined('ABSPATH')) exit;

// Menú principal
add_action('admin_menu', 'roosecure_add_admin_menu');
function roosecure_add_admin_menu() {
    add_menu_page(
        'RooSecure Security Suite',
        'RooSecure',
        'manage_options',
        'roosecure-security-suite',
        'roosecure_load_panel',
        'dashicons-shield-alt',
        3
    );
}

// Crear tabla en activación
register_activation_hook(__FILE__, 'roosecure_create_login_log_table');
function roosecure_create_login_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'roosecure_login_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_login VARCHAR(60) DEFAULT '' NOT NULL,
        ip_address VARCHAR(100) DEFAULT '' NOT NULL,
        event_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status VARCHAR(20) DEFAULT '' NOT NULL,
        message TEXT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Panel principal
function roosecure_load_panel() {
    include_once plugin_dir_path(__FILE__) . 'includes/menu.php';
}

// Includes
$includes = ['settings.php', 'security-hooks.php', 'logger.php'];
foreach ($includes as $inc) {
    $path = plugin_dir_path(__FILE__) . 'includes/' . $inc;
    if (file_exists($path)) {
        include_once $path;
    }
}

// Limpieza al desinstalar
register_uninstall_hook(__FILE__, 'roosecure_uninstall_cleanup');
function roosecure_uninstall_cleanup() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'roosecure_login_log';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

