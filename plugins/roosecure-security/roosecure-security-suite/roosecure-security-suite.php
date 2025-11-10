<?php
/*
Plugin Name: RooSecure Security Suite
Description: Suite de seguridad con protección de login, firewall, modo oscuro y dashboard visual.
Version: 8.3.8
Author: Diego
*/

// Bloquear acceso directo
if (!defined('ABSPATH')) exit;

// Registrar menú en el panel de administración
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

// Cargar panel principal
function roosecure_load_panel() {
    include_once plugin_dir_path(__FILE__) . 'includes/menu.php';
}

// Cargar hooks de seguridad (login protection, etc.)
if ( file_exists( plugin_dir_path(__FILE__) . 'includes/security-hooks.php' ) ) {
    include_once plugin_dir_path(__FILE__) . 'includes/security-hooks.php';
}