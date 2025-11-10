<?php
function roosecure_dashboard_page() {
    ?>
    <h1>Dashboard</h1>
    <div class="roosecure-dashboard">
        <div class="card">
            <div class="circle" data-percent="80"><span>80%</span></div>
            <p>Sistema</p>
        </div>
        <div class="card">
            <div class="circle" data-percent="60"><span>60%</span></div>
            <p>Firewall</p>
        </div>
        <div class="card">
            <div class="circle" data-percent="50"><span>50%</span></div>
            <p>Protección</p>
        </div>
    </div>
    <script>
    document.querySelectorAll('.circle').forEach(circle => {
        const percent = circle.getAttribute('data-percent');
        circle.style.background = `conic-gradient(#4CAF50 ${percent * 3.6}deg, #ddd ${percent * 3.6}deg)`;
    });
    </script>
    <style>
    .roosecure-dashboard {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
        padding: 20px;
    }
    .card {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 20px;
        width: 150px;
        text-align: center;
        transition: transform 0.3s ease;
    }
    .card:hover { transform: translateY(-5px); }
    .circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        background: conic-gradient(#4CAF50 0deg, #ddd 0deg);
        color: #333;
        font-weight: bold;
        transition: background 1s ease;
    }
    .card p { font-weight: 600; margin-top: 8px; }
    body.dark-mode .card {
        background: rgba(255, 255, 255, 0.05);
        color: white;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }
    body.dark-mode .circle { color: white; }
    </style>
    <?php
}
