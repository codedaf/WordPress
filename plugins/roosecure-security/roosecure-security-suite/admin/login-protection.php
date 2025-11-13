 <?php
if (!defined('ABSPATH')) exit;

/**
 * Página de configuración: Login Protection
 */
function roosecure_login_protection_page() {

    // ✅ Guardar datos del formulario si se envía
    if (isset($_POST['roosecure_save_login_protection'])) {
        check_admin_referer('roosecure_save_login_protection_action', 'roosecure_save_login_protection_field');

        // Sanitizar y guardar opciones básicas
        update_option('roosecure_attempts', max(1, intval($_POST['roosecure_attempts'] ?? 3)));
        update_option('roosecure_lock_time', max(1, intval($_POST['roosecure_lock_time'] ?? 10)));

        // 🆕 Guardar usuarios bloqueados por nombre
        $raw_users = sanitize_textarea_field($_POST['roosecure_blocked_users'] ?? '');
        $users = array_filter(array_map('trim', explode(',', strtolower($raw_users))));
        update_option('roosecure_blocked_users', implode(',', $users));

        // Email de alerta
        $email = sanitize_email($_POST['roosecure_alert_email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = get_option('admin_email');
        }
        update_option('roosecure_alert_email', $email);

        // IPs bloqueadas (máx 3 válidas)
        $raw_ips = sanitize_textarea_field($_POST['roosecure_blocked_ips'] ?? '');
        $ips = array_filter(array_map('trim', explode(',', $raw_ips)));
        $valid = array();
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $valid[] = $ip;
                if (count($valid) >= 3) break;
            }
        }
        update_option('roosecure_blocked_ips', implode(',', $valid));

        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada correctamente.</p></div>';
    }

    // Cargar valores actuales
    $attempts       = get_option('roosecure_attempts', 3);
    $lock_time      = get_option('roosecure_lock_time', 10);
    $alert_email    = get_option('roosecure_alert_email', get_option('admin_email'));
    $blocked_ips    = get_option('roosecure_blocked_ips', '');
    $blocked_users  = get_option('roosecure_blocked_users', 'admin,root,test');

    // ===============================
    // FORMULARIO PRINCIPAL
    // ===============================
    echo '<div class="wrap">';
    echo '<h1>🔐 Login Protection</h1>';
    echo '<form method="post">';
    wp_nonce_field('roosecure_save_login_protection_action', 'roosecure_save_login_protection_field');

    echo '<table class="form-table">';

    echo '<tr valign="top">';
    echo '<th scope="row">Intentos fallidos permitidos</th>';
    echo '<td><input type="number" name="roosecure_attempts" min="1" value="' . esc_attr($attempts) . '" /></td>';
    echo '</tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">Tiempo de bloqueo (minutos)</th>';
    echo '<td><input type="number" name="roosecure_lock_time" min="1" value="' . esc_attr($lock_time) . '" /></td>';
    echo '</tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">Email de alerta</th>';
    echo '<td><input type="email" name="roosecure_alert_email" value="' . esc_attr($alert_email) . '" /></td>';
    echo '</tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">IPs bloqueadas permanentemente (máx 3)</th>';
    echo '<td>';
    echo '<textarea name="roosecure_blocked_ips" rows="3" cols="50" placeholder="203.0.113.4,198.51.100.10">' . esc_textarea($blocked_ips) . '</textarea>';
    echo '<p class="description">Introduce hasta 3 IPs separadas por comas. Ej: <code>203.0.113.4,198.51.100.10</code></p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">Usuarios bloqueados (por nombre)</th>';
    echo '<td>';
    echo '<textarea name="roosecure_blocked_users" rows="2" cols="50" placeholder="admin,root,test,guest">' . esc_textarea($blocked_users) . '</textarea>';
    echo '<p class="description">Evita nombres inseguros o comunes. Ej: <code>admin,root,test</code></p>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '<input type="submit" name="roosecure_save_login_protection" class="button-primary" value="Guardar cambios" />';
    echo '</form>';

    // ===============================
    // REGISTRO DE EVENTOS DE LOGIN
    // ===============================
    echo '<hr><h2>🧾 Registro de intentos de login (últimos 10)</h2>';

    // 🧹 Borrar logs si se solicita
    if (isset($_POST['roosecure_clear_logs'])) {
        check_admin_referer('roosecure_save_login_protection_action', 'roosecure_save_login_protection_field');
        roosecure_clear_logs();
        echo '<div class="updated notice is-dismissible"><p>✅ Registros de login eliminados correctamente.</p></div>';
    }

    // Obtener los últimos 10 registros
    $logs = roosecure_get_logs(10);

    if ($logs) {
        echo '<table class="widefat striped" style="margin-top:10px;">';
        echo '<thead><tr><th>Usuario</th><th>IP</th><th>Fecha / Hora</th><th>Estado</th><th>Mensaje</th></tr></thead><tbody>';
        foreach ($logs as $log) {
            $status_color = match ($log->status) {
                'success' => 'color:green;',
                'failed'  => 'color:red;',
                'blocked' => 'color:orange;',
                default   => '',
            };
            echo '<tr>';
            echo '<td>' . esc_html($log->user_login) . '</td>';
            echo '<td>' . esc_html($log->ip_address) . '</td>';
            echo '<td>' . esc_html($log->event_time) . '</td>';
            echo '<td style="' . esc_attr($status_color) . '">' . esc_html(ucfirst($log->status)) . '</td>';
            echo '<td>' . esc_html($log->message) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No hay registros de login aún.</p>';
    }

    // Botón para borrar logs
    echo '<form method="post" style="margin-top:10px;">';
    wp_nonce_field('roosecure_save_login_protection_action', 'roosecure_save_login_protection_field');
    echo '<input type="submit" class="button" name="roosecure_clear_logs" value="🧹 Borrar historial">';
    echo '</form>';

    echo '</div>';
}
