<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap roosecure-dashboard">
  <h1>Roosecure Security Suite</h1>
  <div class="tabs">
    <a href="?page=roosecure_dashboard" class="active">Dashboard</a>
    <a href="?page=roosecure_login_protection">Login Protection</a>
    <a href="?page=roosecure_firewall">Firewall</a>
    <a href="?page=roosecure_email_alert">Email Alert</a>
    <a href="?page=roosecure_hardening">Hardening</a>
    <a href="?page=roosecure_fast_scanner">Fast Scanner</a>
    <a href="?page=roosecure_multiusuario">Multiusuario</a>
    <a href="?page=roosecure_rendimiento">Rendimiento</a>
    <a href="?page=roosecure_configuracion">Configuración</a>
  </div>

  <button id="darkModeToggle" class="switch-btn">🌙 Modo oscuro</button>

  <div class="cards">
    <div class="card"><h3>Protección de Login</h3><p>80%</p></div>
    <div class="card"><h3>Firewall</h3><p>60%</p></div>
    <div class="card"><h3>Hardening</h3><p>50%</p></div>
  </div>
</div>
<script src="<?php echo ROOSECURE_LITE_URL . 'assets/js/darkmode.js'; ?>"></script>
<link rel="stylesheet" href="<?php echo ROOSECURE_LITE_URL . 'assets/css/style.css'; ?>">