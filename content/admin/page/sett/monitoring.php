<?php
/**
 * Monitoring Dashboard
 * Central page with links to all monitoring tools
 */
?>
<style>
    .monitoring-dashboard {
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .monitoring-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .monitoring-header h1 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .monitoring-header p {
        font-size: 1.1rem;
        color: #7f8c8d;
    }
    
    .monitoring-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    
    .monitoring-card {
        background: white;
        border-radius: 12px;
        padding: 40px 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s;
        text-align: center;
        border-top: 5px solid #e2001a;
        cursor: pointer;
        text-decoration: none;
        display: block;
        color: inherit;
    }
    
    .monitoring-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
    
    .monitoring-card.facebook {
        border-top-color: #007bff;
    }
    
    .monitoring-card.sitemap-gen {
        border-top-color: #28a745;
    }
    
    .monitoring-card.sitemap-logs {
        border-top-color: #6f42c1;
    }
    
    .monitoring-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }
    
    .monitoring-card.facebook .monitoring-icon {
        color: #007bff;
    }
    
    .monitoring-card.sitemap-gen .monitoring-icon {
        color: #28a745;
    }
    
    .monitoring-card.sitemap-logs .monitoring-icon {
        color: #6f42c1;
    }
    
    .monitoring-card h2 {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 15px;
    }
    
    .monitoring-card p {
        font-size: 1rem;
        color: #7f8c8d;
        line-height: 1.6;
    }
    
    .monitoring-card .badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 15px;
    }
    
    .monitoring-card.facebook .badge {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .monitoring-card.sitemap-gen .badge {
        background: #d4edda;
        color: #155724;
    }
    
    .monitoring-card.sitemap-logs .badge {
        background: #f3e5f5;
        color: #7b1fa2;
    }
</style>

<div class="monitoring-dashboard">
    <div class="monitoring-header">
        <h1>📊 Мониторинг системы</h1>
        <p>Инструменты для отслеживания и управления каталогами и картой сайта</p>
    </div>
    
    <div class="monitoring-grid">
        <!-- Facebook Feeds -->
        <a href="/<?= $_COOKIE['lang'] ?>/<?= $admin_dir ?>/monitoring/feeds" class="monitoring-card facebook">
            <div class="monitoring-icon">📱</div>
            <h2>Facebook Фиды</h2>
            <p>Мониторинг генерации каталогов для Facebook рекламы. Отслеживание изменений, добавленных и удалённых автомобилей.</p>
            <span class="badge">3 активных каталога</span>
        </a>
        
        <!-- Sitemap Generator -->
        <a href="/<?= $_COOKIE['lang'] ?>/<?= $admin_dir ?>/monitoring/sitemap_gen" class="monitoring-card sitemap-gen">
            <div class="monitoring-icon">🗺️</div>
            <h2>Генератор Sitemap</h2>
            <p>Ручная генерация карты сайта. Запуск процесса обновления sitemap.xml с детальным выводом.</p>
            <span class="badge">Ручной запуск</span>
        </a>
        
        <!-- Sitemap Logs -->
        <a href="/<?= $_COOKIE['lang'] ?>/<?= $admin_dir ?>/monitoring/sitemap_logs" class="monitoring-card sitemap-logs">
            <div class="monitoring-icon">📋</div>
            <h2>Логи Sitemap</h2>
            <p>Просмотр логов генерации карты сайта. Мониторинг cron заданий и процесса обновления в реальном времени.</p>
            <span class="badge">Авто-обновление</span>
        </a>
    </div>
</div>
