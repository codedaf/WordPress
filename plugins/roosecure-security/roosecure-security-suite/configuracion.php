<?php
function roosecure_configuracion_page() {
    ?>
    <h1>Configuración</h1>
    <form style="display:flex;align-items:center;justify-content:space-between;max-width:400px;">
        <label for="darkModeToggle" style="font-size:18px;">🌙 Modo oscuro</label>
        <label class="switch">
            <input type="checkbox" id="darkModeToggle">
            <span class="slider"></span>
        </label>
    </form>
    <script>
    const toggle = document.getElementById('darkModeToggle');
    const mode = localStorage.getItem('darkMode');
    if (mode === 'enabled') document.body.classList.add('dark-mode');
    toggle.checked = mode === 'enabled';
    toggle.addEventListener('change', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
    });
    </script>
    <style>
    body.dark-mode {
        background-color: #121212 !important;
        color: #f0f0f0 !important;
        transition: all 0.4s ease;
    }
    .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
              background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ''; height: 18px; width: 18px; left: 4px; bottom: 3px;
                     background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #2196F3; }
    input:checked + .slider:before { transform: translateX(26px); }
    </style>
    <?php
}
