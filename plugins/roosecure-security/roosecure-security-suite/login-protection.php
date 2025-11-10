<?php
// Evita acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Página de configuración: Login Protection
 */
function roosecure_login_protection_page() {
    // Valores actuales
    $attempts   = get_option('roosecure_attempts', 3);
    $lock_time  = get_option('roosecure_lock_time', 10);
    $alert_email = get_option('roosecure_alert_email', get_option('admin_email'));
    $blocked_ips = get_option('roosecure_blocked_ips', '');

    echo '<div class="wrap">';
    echo '<h1>Login Protection</h1>';
    echo '<form method="post" action="options.php">';

    // Campos ocultos de seguridad/settings
    settings_fields('roosecure_login_options');
    do_settings_sections('roosecure_login_options');

    echo '<table class="form-table">';

    // Intentos fallidos permitidos
    echo '<tr valign="top">';
    echo '  <th scope="row">Intentos fallidos permitidos</th>';
    echo '  <td><input type="number" name="roosecure_attempts" min="1" value="' . esc_attr($attempts) . '" /></td>';
    echo '</tr>';

    // Tiempo de bloqueo (minutos)
    echo '<tr valign="top">';
    echo '  <th scope="row">Tiempo de bloqueo (minutos)</th>';
    echo '  <td><input type="number" name="roosecure_lock_time" min="1" value="' . esc_attr($lock_time) . '" /></td>';
    echo '</tr>';

    // Email de alerta
    echo '<tr valign="top">';
    echo '  <th scope="row">Email de alerta</th>';
    echo '  <td><input type="email" name="roosecure_alert_email" value="' . esc_attr($alert_email) . '" /></td>';
    echo '</tr>';

    // IPs bloqueadas permanentemente
    echo '<tr valign="top">';
    echo '  <th scope="row">IPs bloqueadas permanentemente (máx 3)</th>';
    echo '  <td>';
    echo '    <textarea name="roosecure_blocked_ips" rows="3" cols="50" placeholder="203.0.113.4,198.51.100.10">' . esc_textarea($blocked_ips) . '</textarea>';
    echo '    <p class="description">Introduce hasta 3 IPs separadas por comas. Ej: <code>203.0.113.4,198.51.100.10,192.0.2.5</code></p>';
    echo '  </td>';
    echo '</tr>';

    echo '</table>';

    submit_button('Guardar cambios');

    echo '</form>';

    if (isset($_GET['settings-updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada correctamente.</p></div>';
    }

    echo '</div>';
}
/**
 * Registro y sanitización de opciones
 */
function roosecure_register_login_settings() {
    // Grupo de opciones personalizado
    global $new_allowed_options;
    $new_allowed_options['roosecure_login_options'] = array(
        'roosecure_attempts',
        'roosecure_lock_time',
        'roosecure_alert_email',
        'roosecure_blocked_ips'
    );

    // Intentos fallidos permitidos
    register_setting('roosecure_login_options', 'roosecure_attempts', array(
        'type' => 'integer',
        'sanitize_callback' => function($val) {
            $v = intval($val);
            return ($v < 1) ? 3 : $v;
        }
    ));

    // Tiempo de bloqueo en minutos
    register_setting('roosecure_login_options', 'roosecure_lock_time', array(
        'type' => 'integer',
        'sanitize_callback' => function($val) {
            $v = intval($val);
            return ($v < 1) ? 10 : $v;
        }
    ));

    // Email de alerta
    register_setting('roosecure_login_options', 'roosecure_alert_email', array(
        'type' => 'string',
        'sanitize_callback' => function($val) {
            $val = trim($val);
            if (empty($val)) return get_option('admin_email');
            return (filter_var($val, FILTER_VALIDATE_EMAIL)) ? $val : get_option('admin_email');
        }
    ));

    // IPs bloqueadas (hasta 3)
    register_setting('roosecure_login_options', 'roosecure_blocked_ips', array(
        'type' => 'string',
        'sanitize_callback' => function($val) {
            $raw = trim($val);
            if ($raw === '') return '';
            $parts = array_filter(array_map('trim', explode(',', $raw)));
            $valid = array();
            foreach ($parts as $p) {
                if (filter_var($p, FILTER_VALIDATE_IP)) {
                    $valid[] = $p;
                    if (count($valid) >= 3) break;
                }
            }
            return implode(',', $valid);
        }
    ));
}
add_action('admin_init', 'roosecure_register_login_settings');
