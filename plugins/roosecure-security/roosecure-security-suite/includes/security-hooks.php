<?php
if (!defined('ABSPATH')) exit;

/**
 * RooSecure - Login protection logic
 *
 * - Cuenta intentos fallidos por IP y por usuario.
 * - Si los intentos superan 'roosecure_attempts' (default 3), aplica bloqueo temporal a la IP por 'roosecure_lock_time' minutos (default 10).
 * - Envía email de alerta a 'roosecure_alert_email' (o admin_email) cuando se activa el bloqueo (una vez por ventana de bloqueo).
 * - Soporta lista de IPs bloqueadas permanentemente vía 'roosecure_blocked_ips' (se aplican hasta 3).
 */

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

/**
 * Bloqueo por IP (permanente o temporal) antes de autenticar.
 */
add_filter('authenticate', function($user, $username, $password) {
    $ip = roosecure_get_remote_ip();

    // 1) IPs permanentemente bloqueadas
    $blocked = get_option('roosecure_blocked_ips', '');
    if (!empty($blocked)) {
        $ips = array_filter(array_map('trim', explode(',', $blocked)));
        if (count($ips) > 3) {
            $ips = array_slice($ips, 0, 3);
        }
        foreach ($ips as $bip) {
            if ($bip === $ip) {
                return new WP_Error('roosecure_blocked_ip', __('<strong>ERROR</strong>: Acceso denegado desde su IP.' , 'roosecure'));
            }
        }
    }

    // 2) Bloqueo temporal (por intentos fallidos)
    $lock_key = 'roosecure_lock_ip_' . md5($ip);
    $locked_until = get_transient($lock_key);
    if ($locked_until) {
        $now = time();
        if ($locked_until > $now) {
            $minutes = ceil(($locked_until - $now)/60);
            return new WP_Error(
                'roosecure_temp_locked',
                sprintf(__('<strong>ERROR</strong>: Demasiados intentos fallidos. Intente de nuevo en %d minuto(s).', 'roosecure'), $minutes)
            );
        } else {
            delete_transient($lock_key);
        }
    }

    return $user;
}, 30, 3);

/**
 * Incrementa contadores y aplica bloqueo + email cuando corresponde.
 */
add_action('wp_login_failed', function($username) {
    $ip = roosecure_get_remote_ip();
    $attempts_allowed = intval(get_option('roosecure_attempts', 3));
    $lock_minutes = intval(get_option('roosecure_lock_time', 10));
    if ($attempts_allowed < 1) $attempts_allowed = 3;
    if ($lock_minutes < 1) $lock_minutes = 10;

    $ip_fail_key   = 'roosecure_fail_ip_'   . md5($ip);
    $user_fail_key = 'roosecure_fail_user_' . md5(strtolower($username));

    // Incremento de fallos por IP
    $current_ip_fails = intval(get_transient($ip_fail_key));
    $current_ip_fails++;
    set_transient($ip_fail_key, $current_ip_fails, $lock_minutes * 60);

    // Incremento de fallos por usuario
    $current_user_fails = intval(get_transient($user_fail_key));
    $current_user_fails++;
    set_transient($user_fail_key, $current_user_fails, $lock_minutes * 60);

    // ¿Superó el umbral?
    if ($current_ip_fails >= $attempts_allowed || $current_user_fails >= $attempts_allowed) {
        // Bloqueo temporal por IP
        $lock_key = 'roosecure_lock_ip_' . md5($ip);
        $locked_until = time() + ($lock_minutes * 60);
        set_transient($lock_key, $locked_until, $lock_minutes * 60);

        // Email de alerta (una vez por ventana de bloqueo)
        $alert_sent_key = 'roosecure_alert_sent_' . md5($ip);
        $already_sent = get_transient($alert_sent_key);
        if (!$already_sent) {
            $to = get_option('roosecure_alert_email', get_option('admin_email'));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $to = get_option('admin_email');
            }
            $subject = 'RooSecure: bloqueo temporal activado para IP ' . $ip;
            $body  = "Se detectó que la IP {$ip} o el usuario {$username} superó el umbral de intentos fallidos ({$attempts_allowed}).\n";
            $body .= "Se bloqueó temporalmente el acceso desde esa IP durante {$lock_minutes} minuto(s).\n\n";
            $body .= "Fecha/hora: " . date_i18n('Y-m-d H:i:s') . "\n";

            if (function_exists('wp_mail')) {
                @wp_mail($to, $subject, $body);
            }
            set_transient($alert_sent_key, 1, $lock_minutes * 60);
        }
    }
});
