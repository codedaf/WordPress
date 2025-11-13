<<?php
if (!defined('ABSPATH')) exit;

/**
 * =======================================================
 * 🔹 RooSecure - Logger de eventos de seguridad
 * =======================================================
 * Registra los eventos de login, bloqueos, inactividad, etc.
 * en la tabla personalizada wp_roosecure_login_log.
 *
 * Campos:
 * - user_login
 * - ip_address
 * - event_time
 * - status (success | failed | blocked | logout)
 * - message (texto opcional)
 */

/**
 * Guarda un evento de seguridad en la tabla de logs
 */
function roosecure_log_event($user_login, $status, $message = '') {
    global $wpdb;

    $table_name = $wpdb->prefix . 'roosecure_login_log';

    // Obtener IP del cliente
    $ip = '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $ip = sanitize_text_field($ip);
    $user_login = sanitize_text_field($user_login);
    $status = sanitize_text_field($status);
    $message = sanitize_textarea_field($message);

    // Verificar que la tabla exista antes de insertar
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if (!$table_exists) {
        error_log("[RooSecure] Tabla no encontrada: $table_name");
        return;
    }

    try {
        $wpdb->insert(
            $table_name,
            [
                'user_login' => $user_login,
                'ip_address' => $ip,
                'status'     => $status,
                'message'    => $message,
            ],
            ['%s', '%s', '%s', '%s']
        );
    } catch (Exception $e) {
        error_log('⚠️ RooSecure Logger Error: ' . $e->getMessage());
    }
}

/**
 * Limpia todo el registro de logs
 */
function roosecure_clear_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'roosecure_login_log';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
        $wpdb->query("TRUNCATE TABLE $table_name");
    }
}

/**
 * Obtiene los últimos N registros de eventos
 */
function roosecure_get_logs($limit = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'roosecure_login_log';

    if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
        return [];
    }

    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name ORDER BY event_time DESC LIMIT %d", intval($limit))
    );
}
