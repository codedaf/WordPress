<?php
if (!defined('ABSPATH')) exit;

/**
 * RooSecure - Security Hooks (Versión estable)
 * 
 * Controla intentos de login fallidos, bloqueo temporal, bloqueo permanente por IP,
 * bloqueo por inactividad y notificaciones por email.
 *  * Todas las funciones están protegidas con sanitización, try/catch y buenas prácticas.
 * Bloqueo por IP permanente y temporal
    *Bloqueo por nombres de usuario
    *Registro de intentos en la tabla del logger
    *Control de inactividad (con 2 min de gracia después del login)
    *Mensaje de cierre de sesión por inactividad
    *Notificación por email al detectar intentos fallidos excesivos
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
 * 🔹 BLOQUEO PREVIO POR IP (temporal o permanente)
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
                return new WP_Error(
                    'roosecure_temp_locked',
                    sprintf(__('<strong>ERROR</strong>: Demasiados intentos fallidos. Intente de nuevo en %d minuto(s).', 'roosecure'), $minutes)
                );
            } else {
                delete_transient($lock_key);
            }
        }


        // Bloqueo por nombres de usuario comunes (configurable en opciones)
        $blocked_users = get_option('roosecure_blocked_users', get_option('roosecure_restricted_usernames', 'admin,root,test'));
        $blocked_users_array = array_filter(array_map('trim', explode(',', strtolower($blocked_users))));
            if (!empty($username) && in_array(strtolower($username), $blocked_users_array, true)) {
                if (function_exists('roosecure_log_event')) {
                roosecure_log_event($username, 'blocked', 'Usuario bloqueado por política de nombres');
            }
            return new WP_Error('roosecure_blocked_user', __('<strong>ERROR</strong>: Este nombre de usuario no está permitido por razones de seguridad.', 'roosecure'));
            }




            // ✅ Éxito de login → registrar
            add_action('wp_login', function($user_login) {
                if (function_exists('roosecure_log_event')) {
                    roosecure_log_event($user_login, 'success', 'Inicio de sesión exitoso');
                }
            }, 10, 1);

            // ✅ Fallo de login → registrar (además de tu lógica de bloqueo existente)
            add_action('wp_login_failed', function($username) {
                if (function_exists('roosecure_log_event')) {
                    roosecure_log_event($username, 'failed', 'Intento fallido de inicio de sesión');
                }
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

        // Incremento de fallos
        $current_ip_fails = intval(get_transient($ip_fail_key));
        $current_ip_fails++;
        set_transient($ip_fail_key, $current_ip_fails, $lock_minutes * 60);

        $current_user_fails = intval(get_transient($user_fail_key));
        $current_user_fails++;
        set_transient($user_fail_key, $current_user_fails, $lock_minutes * 60);

        // Superó el límite
        if ($current_ip_fails >= $attempts_allowed || $current_user_fails >= $attempts_allowed) {
            $lock_key = 'roosecure_lock_ip_' . md5($ip);
            $locked_until = time() + ($lock_minutes * 60);
            set_transient($lock_key, $locked_until, $lock_minutes * 60);

            // Enviar email de alerta (una vez por bloqueo)
            $alert_sent_key = 'roosecure_alert_sent_' . md5($ip);
            if (!get_transient($alert_sent_key)) {
                $to = get_option('roosecure_alert_email', get_option('admin_email'));
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $to = get_option('admin_email');
                }
                $subject = 'RooSecure: bloqueo temporal activado para IP ' . $ip;
                $body  = "Se detectó que la IP {$ip} o el usuario {$username} superó el umbral de intentos fallidos ({$attempts_allowed}).\n";
                $body .= "Acceso bloqueado temporalmente durante {$lock_minutes} minuto(s).\n\n";
                $body .= "Fecha/hora: " . date_i18n('Y-m-d H:i:s') . "\n";

                if (function_exists('wp_mail')) {
                    @wp_mail($to, $subject, $body);
                }
                set_transient($alert_sent_key, 1, $lock_minutes * 60);
            }
        }
    } catch (Exception $e) {
        error_log('RooSecure login failed hook error: ' . $e->getMessage());
    }
});


/* =======================================================
 * 🔹 CONTROL DE INACTIVIDAD DE USUARIO LOGUEADO
 * ======================================================= */
add_action('init', 'roosecure_check_user_inactivity');

function roosecure_check_user_inactivity() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $now = time();

    // Tiempo de inactividad configurable (minutos) desde el panel
    $lock_minutes = intval(get_option('roosecure_lock_time', 10));
    if ($lock_minutes < 1) $lock_minutes = 10;
    $timeout = $lock_minutes * 60;

    // Evitar aplicar durante login o ajax
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($request_uri, 'wp-login.php') !== false ||
        strpos($request_uri, 'wp-admin/admin-ajax.php') !== false
    ) {
        return;
    }

    // Última actividad y hora de login
    $last_activity = get_user_meta($user_id, 'roosecure_last_activity', true);
    $login_time    = get_user_meta($user_id, 'roosecure_login_time', true);

    // 1) Ventana de gracia de 2 minutos después del login
    if (!$login_time) {
        update_user_meta($user_id, 'roosecure_login_time', $now);
    } elseif (($now - intval($login_time)) < 120) {
        // Dentro de la gracia: no desconectar, sólo refrescar actividad
        update_user_meta($user_id, 'roosecure_last_activity', $now);
        return;
    }

    // 2) Si la marca pertenece a un día anterior, reiniciar sin cerrar sesión
    if (!empty($last_activity)) {
        $last_date    = date('Y-m-d', intval($last_activity));
        $current_date = date('Y-m-d', $now);
        if ($last_date !== $current_date) {
            update_user_meta($user_id, 'roosecure_last_activity', $now);
            return;
        }
    }

    // 3) Cierre por inactividad si supera el límite configurado
    if ($last_activity && ($now - intval($last_activity)) > $timeout) {
        wp_logout();
        wp_safe_redirect(wp_login_url() . '?inactivity=1');
        exit;
    }

    // 4) Actualizar última actividad
    update_user_meta($user_id, 'roosecure_last_activity', $now);
}

/* =======================================================
 * 🔹 MENSAJE EN LOGIN SI SE DESLOGUEÓ POR INACTIVIDAD
 * ======================================================= */
add_action('login_message', function($message) {
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


/* =======================================================
 * 🔹 Cierre automático por inactividad con 1 min de gracia
 * ======================================================= */
add_action('init', 'roosecure_auto_logout_inactivity');
function roosecure_auto_logout_inactivity() {
    if (!is_user_logged_in()) return;

    // Tiempo base y de gracia (en segundos)
    $timeout = 15 * 60; // 15 minutos
    $grace   = 1 * 60;  // 1 minuto extra de gracia
    $limit   = $timeout + $grace;

    $last_activity = get_user_meta(get_current_user_id(), '_roosecure_last_activity', true);
    $current_time  = time();

    // Si ya pasó el tiempo máximo permitido (15 + 1 min) → cerrar sesión
    if (!empty($last_activity) && ($current_time - intval($last_activity)) > $limit) {
        wp_logout();
        wp_redirect(wp_login_url() . '?message=inactivity');
        exit;
    }

    // Actualiza el tiempo de última actividad
    update_user_meta(get_current_user_id(), '_roosecure_last_activity', $current_time);
}

/* =======================================================
 * 🔹 Mostrar mensaje en pantalla de login
 * ======================================================= */
add_action('login_message', 'roosecure_inactivity_message');
function roosecure_inactivity_message($message) {
    if (isset($_GET['message']) && $_GET['message'] === 'inactivity') {
        $message .= '<div class="notice notice-warning" style="
            padding:10px;
            margin-bottom:10px;
            background:#fff3cd;
            border-left:4px solid #ff9800;
            font-size:14px;
        ">
        🔒 <strong>Sesión finalizada por inactividad</strong> (15 + 1 min de gracia).<br>
        Por favor, vuelve a iniciar sesión.
        </div>';
    }
    return $message;
}