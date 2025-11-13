<?php
if (!defined('ABSPATH')) exit;

/**
 * Archivo: includes/settings.php
 * Función: Registrar todas las opciones globales del plugin RooSecure
 */

add_action('admin_init', 'roosecure_register_settings');
function roosecure_register_settings() {

    // Grupo principal de configuración
    register_setting('roosecure_options', 'roosecure_attempts');
    register_setting('roosecure_options', 'roosecure_lock_time');
    register_setting('roosecure_options', 'roosecure_alert_email');
    register_setting('roosecure_options', 'roosecure_blocked_ips');

    // 🔒 Opciones futuras de seguridad avanzada
    register_setting('roosecure_options', 'roosecure_restricted_usernames'); // Ejemplo: admin, root, test
    register_setting('roosecure_options', 'roosecure_enable_login_log');     // Activar / desactivar registro de eventos
}
