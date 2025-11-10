# 🛡️ RooSecure Security Suite (Lite)

**Current Version:** 8.5.4  
**Author:** Diego F.  
**License:** GPLv2 or later  
**Compatibility:** WordPress 6.x+

---

## 🧩 GENERAL OBJECTIVE

Develop a **modular, secure, and scalable** WordPress plugin that provides **cybersecurity and hardening features** with a **centralized dashboard**, **clean design**, and **future-ready architecture**, designed to evolve into a **Pro/Freemium** version.

Built with **PHP, HTML, JavaScript, and CSS**, following modern **best practices** in coding and security:

- Strong validation (`try/catch`, sanitization, nonces).  
- Clear separation between logic, presentation, and configuration.  
- Use of native WordPress hooks.  
- High modularity and maintainability.  
- Fully compliant with **WordPress Coding Standards (WPCS)**.

---

## ⚙️ PROJECT STRUCTURE

roosecure-security-suite/
├── roosecure-security-suite.php → Main plugin file.
├── includes/
│ ├── menu.php → Registers main and submenu pages.
│ ├── settings.php → Global configuration and options management.
│ ├── roles.php → Custom capability definitions.
│ ├── logger.php → Internal logging and auditing module.
│ ├── helpers.php → Common reusable functions.
│ └── security-hooks.php → Core security hooks (login, IP blocking, etc.)
├── admin/
│ ├── dashboard.php → Main dashboard with metrics and status.
│ ├── login-protection.php → Login protection configuration page.
│ ├── firewall.php → Firewall settings.
│ ├── email-alert.php → Email alert configuration.
│ ├── hardening.php → WordPress hardening options.
│ ├── fast-scanner.php → Quick security scan tool.
│ ├── multiusuario.php → User access and role management.
│ └── rendimiento.php → Performance and optimization.
├── assets/
│ ├── css/
│ │ ├── main.css
│ │ └── dashboard.css
│ └── js/
│ ├── main.js
│ └── dashboard.js
└── uninstall.php → Safe cleanup on plugin uninstall.




---

## 🧱 MAIN COMPONENTS

### 🔹 `roosecure-security-suite.php`
- Defines namespace and constant `ROOSECURE_VERSION`.  
- Registers activation and deactivation hooks.  
- Loads dependencies (`includes/*.php`).  
- Initializes menu, scripts, and global styles.

### 🔹 `includes/menu.php`
- Creates the **“RooSecure Security Suite”** sidebar menu in the WordPress Admin.  
- Dynamically loads tabs (Dashboard, Firewall, Login Protection, etc.).  
- Implements a **global Dark Mode** using `localStorage` with smooth transitions.

### 🔹 `includes/settings.php`
- Registers plugin options via WordPress Settings API (`register_setting`).  
- Stores and manages global security configurations:
  - Maximum failed login attempts.
  - Lockout duration.
  - Blocked IP list.
- Future support for export/import configuration.

### 🔹 `includes/security-hooks.php`
- Contains main security logic:
  - `wp_login_failed` → tracks failed login attempts.  
  - `wp_authenticate` → blocks suspicious users or IPs.  
  - `init` → loads firewall rules dynamically.  
- Handles temporary user/IP blocking and notification events.

### 🔹 `admin/login-protection.php`
A clean, modern admin UI for login protection:
- Fields for **failed login attempts**, **lockout time**, and **blocked IPs**.  
- Uses `update_option()` to save values securely.  
- Displays confirmation message (“✅ Settings saved successfully”).  
- Includes data validation and nonce protection.

### 🔹 `admin/dashboard.php`
Interactive visual dashboard:
- Displays **three modern info cards** with animated completion percentages (e.g., 80%, 60%, 50%).  
- Circular progress indicators using CSS + JS.  
- *Glassmorphism* design with dark-mode compatibility.

---

## 🌙 GLOBAL DARK MODE

- Toggle available under **Settings** tab.  
- State persistence using `localStorage`.  
- Smooth transitions (`fade` and color animation).  
- Applies globally to all plugin tabs (text, forms, cards, and buttons).  
- Fully synchronized across sessions.

---

## 🔒 SECURITY PRINCIPLES

- Sanitization: `sanitize_text_field()`, `esc_html()`, `wp_verify_nonce()`.  
- Custom roles & capabilities for granular control.  
- CSRF and XSS protection via nonces and escaping.  
- Modular architecture ready for OOP and REST API expansion.  
- Complete data cleanup via `uninstall.php`:
  - Removes options and transient logs safely.

---

## 🚀 FUTURE ROADMAP

**Pro/Freemium Version Plans:**
- 2FA (Two-Factor Authentication).  
- Advanced malware scanning.  
- GeoIP blocking by country.  
- Integration with external APIs (Slack, Telegram, Cloudflare).  
- Real-time notification center.  
- Remote monitoring via REST API.

---

## 🧑‍💻 DEVELOPER

**Author:** Diego F.  
**Contact:** 
 
**Project:** RooSecure Security Suite  

 

---

