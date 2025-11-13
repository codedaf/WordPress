
==========================================================================
RooSecure Security Suite 
==========================================================================
Versión: 8.5.5
Autor: Diego
Estado: Estable
Compatibilidad: WordPress 6.x+, PHP 7.4+
==========================================================================
==========================================================================

1. Descripción General
	RooSecure Security Suite es una solución de seguridad integral para WordPress que combina 
	herramientas profesionales de defensa, monitoreo, bloqueo y auditoría en un único plugin optimizado y liviano.


Su objetivo es proteger sitios WordPress de:
Ataques de fuerza bruta
Accesos sospechosos
Usuarios con nombres inseguros
IPs bloqueadas temporal o permanentemente
Sesiones inactivas
Actividad sospechosa de múltiples usuarios
Cambios no autorizados en archivos (scanner básico)


Incluye además:
		Dashboard central
		Firewall básico
		Sistema de alertas por email
		Modo oscuro del panel
		Registro profesional de eventos en base de datos
		Configuración modular independiente

2. 🧩 Funcionalidades Principales
🔐 Protección de Login
	Límite de intentos fallidos configurables.
	Bloqueo temporal de IP.
	Bloqueo permanente de IP (hasta 3).
	Bloqueo de nombres de usuario peligrosos (admin, root, test, etc).
	Panel para ver y limpiar logs.
	Notificación por email cuando ocurre un bloqueo.


🛑 Firewall Básico

Bloqueo automático de IPs maliciosas.
Filtros comunes contra patrones sospechosos.
Protección contra bots en formularios.
(Implementación variable según el archivo firewall.php del usuario).

📨 Alertas por Email
	Notifica intentos de ataque.
	Envía alertas cuando un usuario o IP excede el límite de fallos.
	Email configurable desde el panel.

🧹 Hardening
	Opciones típicas de reforzamiento:
	Ocultar versiones de WP
	Bloquear XML-RPC
	Forzar contraseñas fuertes
	Desactivar editor de archivos
	(Dependiendo de contenido de hardening.php).

⚙️ Modo Oscuro UI
	Aplica estilo dark mode automáticamente en el panel del plugin.

🔍 Fast Scanner
	Escaneo rápido de archivos modificados o sospechosos.

👥 Multiusuario
	Panel para controlar actividad múltiple.
	Gestión de usuarios activos.

📊 Rendimiento
	Panel simple para detectar sobrecarga o configuraciones inseguras.

3. 🗂️ Estructura de Carpetas del Plugin
roosecure-security-suite/
│
├── roosecure-security-suite.php       → Archivo principal del plugin
├── readme.txt                          → Este documento
│
├── includes/
│   ├── menu.php                        → Sistema de pestañas del panel admin
│   ├── settings.php                    → Opciones generales del plugin
│   ├── security-hooks.php              → Protección: login, IP, inactividad
│   ├── logger.php                      → Registro y auditoría
│   ├── roles.php                       → Capacidades personalizadas (vacío)
│   ├── helpers.php                     → Funciones auxiliares (vacío)
│
├── admin/
│   ├── dashboard.php                   → Dashboard central con métricas
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   ├── js/
│       ├── darkmode.js
│
├── configuracion.php                   → Ajustes generales del plugin
├── login-protection.php                → Configuración de Login Protection
├── firewall.php                        → Firewall simple
├── email-alert.php                     → Configuración de alertas
├── fast-scanner.php                    → Escaneo rápido del sitio
├── hardening.php                       → Opciones de reforzamiento
├── multiusuario.php                    → Gestión multiusuario
├── rendimiento.php                     → Análisis de rendimiento
├── dashboard.php                       → Dashboard alternativo / heredado
│
└── uninstall.php                       → Limpieza de datos (pendiente)

4. 📘 Detalle de Cada Módulo
✔ roosecure-security-suite.php — Núcleo

Carga todos los módulos.
Crea tabla de logs al activar.
Registra menú principal.
Implementa uninstall.
Incluye hooks globales.

✔ includes/menu.php — Menú con pestañas

Genera navegación tipo tabs.
Carga la página correcta según la pestaña.
Aplica el modo oscuro.
✔ includes/settings.php — Preferencias globales

Opciones generales del plugin.

Manejo de parámetros como tiempos y configuraciones.

✔ includes/security-hooks.php — Seguridad principal

Incluye:

🔹 Protección de login:

Bloqueo por intentos fallidos.

Bloqueo temporal.

Bloqueo por nombres.

Bloqueo permanente de IPs.

🔹 Control de sesión:

Inactividad configurable.

Gracia de 2 minutos tras login.

Logout forzado.

Mensaje personalizado de cierre por inactividad.

🔹 Integración con logger.
✔ includes/logger.php — Auditoría

Inserta eventos en tabla personalizada.

Registra:

Éxitos de login

Fallos

Bloqueos

Logout por inactividad

Funciones para:

limpiar logs

obtener últimos eventos

✔ login-protection.php

Ajustes del módulo Login Protection.

Formulario para administrador:

Intentos permitidos

Tiempo de bloqueo

IPs bloqueadas

Usuarios bloqueados

Email de alerta

Mostrar tabla de eventos de login.

✔ firewall.php

(Implementación de usuario)

Bloqueo de patrones sospechosos

Protección anti-bot

Filtros comunes de seguridad

✔ email-alert.php

Configuración de email de alerta

Plantilla de notificaciones

Selector de destinatario

✔ fast-scanner.php

Escaneo básico de archivos modificados o sospechosos.

✔ hardening.php

Opciones típicas de fortificación:

desactivar XML-RPC

ocultar versión WP

bloquear edición de archivos

sanitizar cabeceras

✔ multiusuario.php

Control de usuarios activos

Identificación de actividad simultánea

✔ rendimiento.php

Revisa configuraciones inseguras

Muestra carga de recursos

Recomendaciones de optimización

5. 🧪 Tabla de Logs

El plugin crea:

wp_roosecure_login_log


Campos:

id

user_login

ip_address

event_time

status = (success | failed | blocked | logout)

message

6. 🗑️ Desinstalación

En uninstall.php (pendiente):

Eliminar tabla wp_roosecure_login_log

Borrar opciones:

roosecure_attempts

roosecure_blocked_ips

roosecure_blocked_users

roosecure_lock_time

roosecure_alert_email

etc.

7. 📌 Notas de Seguridad

No usa tablas externas.

No envía datos a terceros.

No modifica archivos del core.

No requiere servicios externos.

Bajo consumo de recursos.

8. 📦 Changelog (Resumen)
v8.5.5

Inclusión de ventana de gracia en login.

Sistema de logs profesional.

Refactor de hooks.

Limpieza de código duplicado.

Mejoras en seguridad y sanitización.