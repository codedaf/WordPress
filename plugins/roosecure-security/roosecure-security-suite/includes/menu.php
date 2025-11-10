<?php
// ROOSECURE SECURITY SUITE v8.4.1 - Menú con solapas, funciones encapsuladas y modo oscuro global seguro

if (!defined('ABSPATH')) exit;

$base_path = plugin_dir_path(__FILE__) . '../';
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

echo '<div class="wrap"><h1>RooSecure Security Suite</h1>';
echo '<nav class="nav-tab-wrapper" style="text-align:center;">';

$tabs = [
    'dashboard' => 'Dashboard',
    'firewall' => 'Firewall',
    'email-alert' => 'Email Alert',
    'hardening' => 'Hardening',
    'fast-scanner' => 'Fast Scanner',
    'multiusuario' => 'Multiusuario',
    'rendimiento' => 'Rendimiento',
    'configuracion' => 'Configuración',
    'login-protection' => 'Login Protection'
];

foreach ($tabs as $key => $name) {
    $active = ($tab === $key) ? ' nav-tab-active' : '';
    echo '<a class="nav-tab' . $active . '" href="?page=roosecure-security-suite&tab=' . esc_attr($key) . '">' . esc_html($name) . '</a>';
}
echo '</nav>';

$includes = [
    'dashboard' => ['file' => 'dashboard.php', 'func' => 'roosecure_dashboard_page'],
    'firewall' => ['file' => 'firewall.php', 'func' => 'roosecure_firewall_page'],
    'email-alert' => ['file' => 'email-alert.php', 'func' => 'roosecure_email_alert_page'],
    'hardening' => ['file' => 'hardening.php', 'func' => 'roosecure_hardening_page'],
    'fast-scanner' => ['file' => 'fast-scanner.php', 'func' => 'roosecure_fast_scanner_page'],
    'multiusuario' => ['file' => 'multiusuario.php', 'func' => 'roosecure_multiusuario_page'],
    'rendimiento' => ['file' => 'rendimiento.php', 'func' => 'roosecure_rendimiento_page'],
    'configuracion' => ['file' => 'configuracion.php', 'func' => 'roosecure_configuracion_page'],
    'login-protection' => ['file' => 'login-protection.php', 'func' => 'roosecure_login_protection_page']
];

if (isset($includes[$tab])) {
    include_once $base_path . $includes[$tab]['file'];
    if (function_exists($includes[$tab]['func'])) {
        call_user_func($includes[$tab]['func']);
    }
}

echo '</div>';

// Inyectar CSS/JS de modo oscuro de forma segura (sin cerrar PHP con ?>)
echo '<style>
body.dark-mode {
    background-color: #121212 !important;
    color: #f0f0f0 !important;
    transition: all 0.4s ease;
}
body.dark-mode input, body.dark-mode select, body.dark-mode textarea {
    background-color: #1e1e1e !important;
    color: #ffffff !important;
    border: 1px solid #333 !important;
}
body.dark-mode .card {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #ffffff !important;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.1) !important;
}
body.dark-mode h1, body.dark-mode h2, body.dark-mode p, body.dark-mode label, body.dark-mode th, body.dark-mode td {
    color: #ffffff !important;
}
</style>';

echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const mode = localStorage.getItem("darkMode");
    if (mode === "enabled") {
        document.body.classList.add("dark-mode");
    }
});
</script>';
