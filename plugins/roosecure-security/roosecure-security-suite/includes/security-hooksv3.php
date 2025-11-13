<?php
if (!defined('ABSPATH')) exit;

/**
 * =======================================================
 * 🛡️ RooSecure - Security Hooks (Versión estable)
 * =======================================================
 * Controla intentos de login fallidos, bloqueo temporal, bloqueo permanente por IP,
 * bloqueo por inactividad y notificaciones por email.
 * Incluye registro de eventos en la tabla wp_roosecure_login_log.
 */

/* =======================================================
 * 🔹 Obtener IP del cliente (maneja proxy y Cloudflare)
 * ======================================================= */
function roosecure_get_remote_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return sanitize_text_field(trim($parts[0]));
    }
    return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
}

/* =======================================================
 * 🔹 Guardar evento de login en la tabla de logs (fallback)
 *    Si ya tienes includes/logger.php cargado con esta función,
 *    este bloque no se redefine (evita colisiones).
 * ======================================================= */

/*
if (!function_exists('roosecure_log_event')) {
    function roosecure_log_event($username, $status = 'info', $message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'roosecure_login_log';
        $ip = roosecure_get_remote_ip();

        // Asegurar tabla existente
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) return;

        $wpdb->insert(
            $table_name,
            array(
                'user_login' => sanitize_text_field($username),
                'ip_address' => sanitize_text_field($ip),
                'status'     => sanitize_text_field($status),
                'message'    => sanitize_text_field($message),
                'event_time' => current_time('mysql')
            ),
            array('%s','%s','%s','%s','%s')
        );
    }
}
*/
/* =======================================================
 * 🔹 BLOQUEO PREVIO POR IP o por nombre de usuario
 * ======================================================= */
add_filter('authenticate', function($user, $username, $password) {
    try {
        $ip = roosecure_get_remote_ip();

        // 1️⃣ IPs permanentemente bloqueadas
        $blocked = get_option('roosecure_blocked_ips', '');
        if (!empty($blocked)) {
            $ips = array_filter(array_map('trim', explode(',', $blocked)));
            if (count($ips) > 3) $ips = array_slice($ips, 0, 3);

            if (in_array($ip, $ips, true)) {
                roosecure_log_event($username, 'blocked', 'IP bloqueada permanentemente');
                return new WP_Error('roosecure_blocked_ip', __('<strong>ERROR</strong>: Acceso denegado desde su IP.', 'roosecure'));
            }
        }

        // 2️⃣ IP temporalmente bloqueadas (por intentos fallidos)
        $lock_key = 'roosecure_lock_ip_' . md5($ip);
        $locked_until = get_transient($lock_key);
        if ($locked_until) {
            $now = time();
            if ($locked_until > $now) {
                $minutes = ceil(($locked_until - $now) / 60);
                roosecure_log_event($username, 'blocked', "IP temporalmente bloqueada por {$minutes} minutos");
                return new WP_Error(
                    'roosecure_temp_locked',
                    sprintf(__('<strong>ERROR</strong>: Demasiados intentos fallidos. Intente de nuevo en %d minuto(s).', 'roosecure'), $minutes)
                );
            } else {
                delete_transient($lock_key);
            }
        }

        // 3️⃣ Bloqueo por nombres de usuario comunes
        $blocked_users = get_option('roosecure_blocked_users', 'admin,root,test');
        $blocked_users_array = array_filter(array_map('trim', explode(',', strtolower($blocked_users))));
        if (!empty($username) && in_array(strtolower($username), $blocked_users_array, true)) {
            roosecure_log_event($username, 'blocked', 'Usuario bloqueado por política de nombres');
            return new WP_Error('roosecure_blocked_user', __('<strong>ERROR</strong>: Este nombre de usuario no está permitido por razones de seguridad.', 'roosecure'));
        }

        // ✅ Éxito de login → registrar
        add_action('wp_login', function($user_login) {
            roosecure_log_event($user_login, 'success', 'Inicio de sesión exitoso');
        }, 10, 1);

        // ❌ Fallo de login → registrar
        add_action('wp_login_failed', function($username) {
            roosecure_log_event($username, 'failed', 'Intento fallido de inicio de sesión');
        }, 10, 1);

    } catch (Exception $e) {
        error_log('RooSecure authenticate check failed: ' . $e->getMessage());
    }

    return $user;
}, 30, 3);

/* =======================================================
 * 🔹 CONTAR INTENTOS FALLIDOS + BLOQUEAR IP
 * ======================================================= */
add_action('wp_login_failed', function($username) {
    try {
        $ip = roosecure_get_remote_ip();
        $attempts_allowed = intval(get_option('roosecure_attempts', 3));
        $lock_minutes = intval(get_option('roosecure_lock_time', 10));
        if ($attempts_allowed < 1) $attempts_allowed = 3;
        if ($lock_minutes < 1) $lock_minutes = 10;

        $ip_fail_key   = 'roosecure_fail_ip_'   . md5($ip);
        $user_fail_key = 'roosecure_fail_user_' . md5(strtolower($username));

        // Incrementar fallos
        $current_ip_fails   = intval(get_transient($ip_fail_key)) + 1;
        $current_user_fails = intval(get_transient($user_fail_key)) + 1;

        set_transient($ip_fail_key, $current_ip_fails,   $lock_minutes * 60);
        set_transient($user_fail_key, $current_user_fails, $lock_minutes * 60);

        // Superó el límite → bloquear
        if ($current_ip_fails >= $attempts_allowed || $current_user_fails >= $attempts_allowed) {
            $lock_key = 'roosecure_lock_ip_' . md5($ip);
            $locked_until = time() + ($lock_minutes * 60);
            set_transient($lock_key, $locked_until, $lock_minutes * 60);

            // Email de alerta (una vez por bloqueo)
            $alert_sent_key = 'roosecure_alert_sent_' . md5($ip);
            if (!get_transient($alert_sent_key)) {
                $to = get_option('roosecure_alert_email', get_option('admin_email'));
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $to = get_option('admin_email');

                $subject = 'RooSecure: bloqueo temporal activado para IP ' . $ip;
                $body  = "Se detectó que la IP {$ip} o el usuario {$username} superó el umbral de intentos fallidos ({$attempts_allowed}).\n";
                $body .= "Acceso bloqueado durante {$lock_minutes} minuto(s).\n\n";
                $body .= "Fecha/hora: " . date_i18n('Y-m-d H:i:s') . "\n";

                if (function_exists('wp_mail')) {
                    @wp_mail($to, $subject, $body);
                }
                set_transient($alert_sent_key, 1, $lock_minutes * 60);
            }

            roosecure_log_event($username, 'blocked', "IP bloqueada temporalmente por {$lock_minutes} minutos");
        }

    } catch (Exception $e) {
        error_log('RooSecure login failed hook error: ' . $e->getMessage());
    }
});

/* =======================================================
 * 🔹 CONTROL DE INACTIVIDAD DE USUARIO LOGUEADO (con 2 min de gracia post-login)
 * =======================================================
add_action('init', 'roosecure_check_user_inactivity');
function roosecure_check_user_inactivity() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $now = time();

    // Tiempo de inactividad configurable (minutos)
    $lock_minutes = intval(get_option('roosecure_lock_time', 10));
    if ($lock_minutes < 1) $lock_minutes = 10;
    $timeout = $lock_minutes * 60;

    // Evitar aplicar durante login o AJAX
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($request_uri, 'wp-login.php') !== false ||
        strpos($request_uri, 'wp-admin/admin-ajax.php') !== false ||
        strpos($request_uri, 'admin-ajax.php') !== false
    ) {
        return;
    }

    $last_activity = get_user_meta($user_id, 'roosecure_last_activity', true);
    $login_time    = get_user_meta($user_id, 'roosecure_login_time', true);

    // Ventana de gracia de 2 minutos tras el login
    if (!$login_time) {
        update_user_meta($user_id, 'roosecure_login_time', $now);
    } elseif (($now - intval($login_time)) < 120) {
        update_user_meta($user_id, 'roosecure_last_activity', $now);
        return;
    }

    // Reset si cambió el día
    if (!empty($last_activity)) {
        $last_date = date('Y-m-d', intval($last_activity));
        if ($last_date !== date('Y-m-d', $now)) {
            update_user_meta($user_id, 'roosecure_last_activity', $now);
            return;
        }
    }

    // Logout si supera inactividad
    if ($last_activity && ($now - intval($last_activity)) > $timeout) {
        $u = wp_get_current_user();
        if ($u && !empty($u->user_login)) {
            roosecure_log_event($u->user_login, 'logout', 'Sesión cerrada por inactividad');
        }
        wp_logout();
        wp_safe_redirect(wp_login_url() . '?inactivity=1');
        exit;
    }

    // Actualizar última actividad
    update_user_meta($user_id, 'roosecure_last_activity', $now);
}
*/



/* =======================================================
 * 🔹 MENSAJE EN LOGIN SI SE DESLOGUEÓ POR INACTIVIDAD
 * ======================================================= */
add_filter('login_message', function($message) {
    if (isset($_GET['inactivity'])) {
        $message .= '<div class="notice notice-warning"><p><strong>Sesión finalizada por inactividad.</strong> Por favor, vuelve a iniciar sesión.</p></div>';
    }
    return $message;
});

/* =======================================================
 * 🔹 BLOQUEO GLOBAL POR IPS PERMANENTES
 * ======================================================= */
add_action('init', function() {
    try {
        $ip = roosecure_get_remote_ip();
        $blocked = get_option('roosecure_blocked_ips', '');

        if (!empty($blocked)) {
            $ips = array_filter(array_map('trim', explode(',', $blocked)));
            if (count($ips) > 3) $ips = array_slice($ips, 0, 3);

            if (in_array($ip, $ips, true)) {
                wp_die(
                    '<h2>Acceso denegado</h2><p>Tu dirección IP ha sido bloqueada por el administrador.</p>',
                    'Acceso denegado',
                    array('response' => 403)
                );
            }
        }
    } catch (Exception $e) {
        error_log('RooSecure permanent IP block failed: ' . $e->getMessage());
    }
});
