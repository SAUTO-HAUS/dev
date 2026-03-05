<?php defined('_DOIT') or die('Restricted access'); ?>

<style>
#terminal-container {
    padding: 20px;
    max-width: 1400px;
}

#terminal-header {
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#terminal-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

#terminal-header .subtitle {
    color: #666;
    font-size: 14px;
}

.clear-cache-btn {
    background: #F44336;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clear-cache-btn:hover {
    background: #D32F2F;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

.clear-cache-btn:active {
    transform: translateY(0);
}

.clear-cache-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.terminal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.terminal-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #ccc;
    transition: all 0.3s ease;
}

.terminal-card.status-ok {
    border-left-color: #4CAF50;
}

.terminal-card.status-warning {
    border-left-color: #FF9800;
}

.terminal-card.status-error {
    border-left-color: #F44336;
}

.terminal-card.status-unknown {
    border-left-color: #9E9E9E;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.card-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.card-status.ok {
    background: #E8F5E9;
    color: #4CAF50;
}

.card-status.warning {
    background: #FFF3E0;
    color: #FF9800;
}

.card-status.error {
    background: #FFEBEE;
    color: #F44336;
}

.card-status.unknown {
    background: #F5F5F5;
    color: #9E9E9E;
}

.card-content {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

.card-content .info-row {
    margin: 8px 0;
    display: flex;
    justify-content: space-between;
}

.card-content .info-label {
    font-weight: 500;
    color: #555;
}

.card-content .info-value {
    color: #333;
    font-weight: 600;
}

.terminal-footer {
    background: #fff;
    border-radius: 8px;
    padding: 15px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #666;
}

.terminal-footer .refresh-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.terminal-footer .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: none;
}

.terminal-footer .spinner.active {
    display: inline-block;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #F44336;
    margin-top: 20px;
}

.error-card .error-title {
    font-size: 16px;
    font-weight: 600;
    color: #F44336;
    margin-bottom: 10px;
}

.error-card .error-message {
    font-size: 13px;
    color: #666;
    font-family: monospace;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}

.monitor-unavailable {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.monitor-unavailable h2 {
    color: #F44336;
    font-size: 24px;
    margin-bottom: 10px;
}

.monitor-unavailable p {
    color: #666;
    font-size: 14px;
}
</style>

<div id="terminal-container">
    <div id="terminal-header">
        <h1>Терминал Мониторинга Системы</h1>
        <button id="clear-cache-btn" class="clear-cache-btn">🗑️ Очистить Cache</button>
    </div>

    <div id="terminal-content">
        <div class="monitor-unavailable">
            <h2>Загрузка...</h2>
            <p>Подключение к системе мониторинга</p>
        </div>
    </div>

    <div class="terminal-footer">
        <div class="refresh-info">
            <div class="spinner" id="refresh-spinner"></div>
            <span id="last-update">Последнее обновление: --:--:--</span>
        </div>
        <div id="next-refresh">Следующее обновление через: 10 сек</div>
    </div>
</div>

<script>
(function() {
    let refreshInterval = null;
    let countdownInterval = null;
    let secondsUntilRefresh = 10;
    
    function updateTerminal() {
        const spinner = document.getElementById('refresh-spinner');
        spinner.classList.add('active');
        
        fetch('/ajax.php?tp=adm&pg=monitor&fn=get_status', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            renderTerminal(data);
            updateLastRefreshTime();
            spinner.classList.remove('active');
            secondsUntilRefresh = 10;
        })
        .catch(error => {
            console.error('Monitor API Error:', error);
            renderError(error.message);
            spinner.classList.remove('active');
        });
    }
    
    function renderTerminal(data) {
        const content = document.getElementById('terminal-content');
        
        let html = '<div class="terminal-grid">';
        
        // Database card
        html += renderCard(
            'База Данных',
            data.database,
            data.details.database,
            'database.svg'
        );
        
        // Cars card
        html += renderCard(
            'Каталог Автомобилей',
            data.cars,
            data.details.cars,
            'car.svg'
        );
        
        // Leads card
        html += renderCard(
            'Лиды / Сообщения',
            data.leads,
            data.details.leads,
            'sms.svg'
        );
        
        // Disk space card
        html += renderDiskCard(
            'Место на Диске',
            data.disk,
            data.details.disk,
            'storage.svg'
        );
        
        // Publication errors card
        html += renderPublicationCard(
            'Ошибки Публикации',
            data.publications,
            data.details.publications,
            'announcement.svg'
        );
        
        // Server card
        html += renderServerCard(data.server_time, 'server.svg');
        
        html += '</div>';
        
        // Last error card (shows last 1)
        if (data.last_error && Array.isArray(data.last_error) && data.last_error.length > 0) {
            html += '<div class="error-card">';
            html += '<div class="error-title">⚠️ Последняя Ошибка</div>';
            
            var error = data.last_error[0];
            html += '<div class="error-message">';
            html += '<div>' + escapeHtml(error.message) + '</div>';
            html += '<div style="margin-top:10px;font-size:12px;color:#999;">Файл: ' + (error.file || 'N/A') + ' | Время: ' + (error.time || 'N/A') + '</div>';
            html += '</div>';
            
            html += '</div>';
        }
        
        content.innerHTML = html;
    }
    
    function renderCard(title, status, details, icon) {
        let html = '<div class="terminal-card status-' + status + '">';
        html += '<div class="card-header">';
        html += '<div class="card-title">';
        if (icon) {
            html += '<img src="/content/admin/icons/' + icon + '" style="width:20px;height:20px;margin-right:8px;vertical-align:middle;"> ';
        }
        html += title + '</div>';
        html += '<div class="card-status ' + status + '">' + getStatusText(status) + '</div>';
        html += '</div>';
        html += '<div class="card-content">';
        
        if (details.message) {
            html += '<div style="margin-bottom:10px;">' + escapeHtml(details.message) + '</div>';
        }
        
        if (details.response_time) {
            html += '<div class="info-row"><span class="info-label">Время отклика:</span><span class="info-value">' + details.response_time + '</span></div>';
        }
        
        if (details.last_date) {
            html += '<div class="info-row"><span class="info-label">Последняя запись:</span><span class="info-value">' + details.last_date + '</span></div>';
        }
        
        if (details.days_ago !== undefined) {
            html += '<div class="info-row"><span class="info-label">Дней назад:</span><span class="info-value">' + details.days_ago + '</span></div>';
        }
        
        if (details.hours_ago !== undefined) {
            html += '<div class="info-row"><span class="info-label">Часов назад:</span><span class="info-value">' + details.hours_ago + '</span></div>';
        }
        
        if (details.last_car) {
            html += '<div class="info-row"><span class="info-label">ID:</span><span class="info-value">#' + details.last_car.id + '</span></div>';
            html += '<div class="info-row"><span class="info-label">Модель:</span><span class="info-value">' + escapeHtml(details.last_car.brand + ' ' + details.last_car.model) + '</span></div>';
        }
        
        if (details.last_lead) {
            html += '<div class="info-row"><span class="info-label">ID:</span><span class="info-value">#' + details.last_lead.id + '</span></div>';
            html += '<div class="info-row"><span class="info-label">Папка:</span><span class="info-value">' + escapeHtml(details.last_lead.folder) + '</span></div>';
        }
        
        html += '</div>';
        html += '</div>';
        return html;
    }
    
    function renderDiskCard(title, status, details, icon) {
        let html = '<div class="terminal-card status-' + status + '">';
        html += '<div class="card-header">';
        html += '<div class="card-title">';
        if (icon) {
            html += '<img src="/content/admin/icons/' + icon + '" style="width:20px;height:20px;margin-right:8px;vertical-align:middle;"> ';
        }
        html += title + '</div>';
        html += '<div class="card-status ' + status + '">' + getStatusText(status) + '</div>';
        html += '</div>';
        html += '<div class="card-content">';
        
        if (details.message) {
            html += '<div style="margin-bottom:10px;">' + escapeHtml(details.message) + '</div>';
        }
        
        if (details.total) {
            html += '<div class="info-row"><span class="info-label">Всего:</span><span class="info-value">' + details.total + '</span></div>';
            html += '<div class="info-row"><span class="info-label">Использовано:</span><span class="info-value">' + details.used + ' (' + details.percent_used + '%)</span></div>';
            html += '<div class="info-row"><span class="info-label">Свободно:</span><span class="info-value">' + details.free + ' (' + details.percent_free + '%)</span></div>';
            
            // Progress bar
            let barColor = status === 'ok' ? '#4CAF50' : (status === 'warning' ? '#FF9800' : '#F44336');
            html += '<div style="margin-top:10px;">';
            html += '<div style="background:#f0f0f0;border-radius:4px;height:8px;overflow:hidden;">';
            html += '<div style="background:' + barColor + ';height:100%;width:' + details.percent_used + '%;transition:width 0.3s ease;"></div>';
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
        return html;
    }
    
    function renderPublicationCard(title, status, details, icon) {
        let html = '<div class="terminal-card status-' + status + '">';
        html += '<div class="card-header">';
        html += '<div class="card-title">';
        if (icon) {
            html += '<img src="/content/admin/icons/' + icon + '" style="width:20px;height:20px;margin-right:8px;vertical-align:middle;"> ';
        }
        html += title + '</div>';
        html += '<div class="card-status ' + status + '">' + getStatusText(status) + '</div>';
        html += '</div>';
        html += '<div class="card-content">';
        
        if (details.message) {
            html += '<div style="margin-bottom:10px;">' + escapeHtml(details.message) + '</div>';
        }
        
        if (details.details) {
            html += '<div style="margin-bottom:10px;font-weight:600;">За последние 24 часа:</div>';
            
            // 999.md errors
            if (details.details['999']) {
                let total999 = details.details['999'].failed + details.details['999'].postponed;
                let color999 = total999 > 0 ? '#FF9800' : '#4CAF50';
                html += '<div style="margin-bottom:15px;padding:10px;background:#f9f9f9;border-radius:4px;">';
                html += '<div class="info-row" style="margin-bottom:5px;">';
                html += '<span class="info-label" style="font-weight:600;">999.md:</span>';
                html += '<span class="info-value" style="color:' + color999 + '">Failed: ' + details.details['999'].failed + ' | Postponed: ' + details.details['999'].postponed + '</span>';
                html += '</div>';
                if (details.details['999'].errors && details.details['999'].errors.length > 0) {
                    details.details['999'].errors.forEach(function(err) {
                        html += '<div style="margin-top:8px;padding:8px;background:#fff;border-left:3px solid #FF9800;font-size:12px;">';
                        html += '<div style="color:#F44336;margin-bottom:3px;">Car ID: #' + err.car_id + '</div>';
                        html += '<div style="color:#666;">' + escapeHtml(err.message) + '</div>';
                        html += '<div style="color:#999;font-size:11px;margin-top:3px;">' + err.time + '</div>';
                        html += '</div>';
                    });
                }
                html += '</div>';
            }
            
            // Facebook errors
            if (details.details.facebook) {
                let totalFb = details.details.facebook.failed + details.details.facebook.pending;
                let colorFb = totalFb > 5 ? '#FF9800' : '#4CAF50';
                html += '<div style="margin-bottom:15px;padding:10px;background:#f9f9f9;border-radius:4px;">';
                html += '<div class="info-row" style="margin-bottom:5px;">';
                html += '<span class="info-label" style="font-weight:600;">Facebook:</span>';
                html += '<span class="info-value" style="color:' + colorFb + '">Failed: ' + details.details.facebook.failed + ' | Pending: ' + details.details.facebook.pending + '</span>';
                html += '</div>';
                if (details.details.facebook.errors && details.details.facebook.errors.length > 0) {
                    details.details.facebook.errors.forEach(function(err) {
                        html += '<div style="margin-top:8px;padding:8px;background:#fff;border-left:3px solid #FF9800;font-size:12px;">';
                        html += '<div style="color:#F44336;margin-bottom:3px;">Car ID: #' + err.car_id + '</div>';
                        html += '<div style="color:#666;">' + escapeHtml(err.message) + '</div>';
                        html += '<div style="color:#999;font-size:11px;margin-top:3px;">' + err.time + '</div>';
                        html += '</div>';
                    });
                }
                html += '</div>';
            }
            
            // Telegram errors
            if (details.details.telegram) {
                let totalTg = details.details.telegram.failed + details.details.telegram.pending;
                let colorTg = totalTg > 5 ? '#FF9800' : '#4CAF50';
                html += '<div style="margin-bottom:15px;padding:10px;background:#f9f9f9;border-radius:4px;">';
                html += '<div class="info-row" style="margin-bottom:5px;">';
                html += '<span class="info-label" style="font-weight:600;">Telegram:</span>';
                html += '<span class="info-value" style="color:' + colorTg + '">Failed: ' + details.details.telegram.failed + ' | Pending: ' + details.details.telegram.pending + '</span>';
                html += '</div>';
                if (details.details.telegram.errors && details.details.telegram.errors.length > 0) {
                    details.details.telegram.errors.forEach(function(err) {
                        html += '<div style="margin-top:8px;padding:8px;background:#fff;border-left:3px solid #FF9800;font-size:12px;">';
                        html += '<div style="color:#F44336;margin-bottom:3px;">Car ID: #' + err.car_id + '</div>';
                        html += '<div style="color:#666;">' + escapeHtml(err.message) + '</div>';
                        html += '<div style="color:#999;font-size:11px;margin-top:3px;">' + err.time + '</div>';
                        html += '</div>';
                    });
                }
                html += '</div>';
            }
            
            // Total summary
            if (details.total_failed !== null) {
                html += '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">';
                html += '<div class="info-row">';
                html += '<span class="info-label" style="font-weight:600;">Всего ошибок:</span>';
                html += '<span class="info-value" style="font-weight:600;color:' + (details.total_failed > 0 ? '#F44336' : '#4CAF50') + '">' + details.total_failed + '</span>';
                html += '</div>';
                html += '</div>';
            }
        }
        
        html += '</div>';
        html += '</div>';
        return html;
    }
    
    function renderServerCard(serverTime, icon) {
        let html = '<div class="terminal-card status-ok">';
        html += '<div class="card-header">';
        html += '<div class="card-title">';
        if (icon) {
            html += '<img src="/content/admin/icons/' + icon + '" style="width:20px;height:20px;margin-right:8px;vertical-align:middle;"> ';
        }
        html += 'Сервер</div>';
        html += '<div class="card-status ok">OK</div>';
        html += '</div>';
        html += '<div class="card-content">';
        html += '<div class="info-row"><span class="info-label">Время сервера:</span><span class="info-value">' + serverTime + '</span></div>';
        html += '<div class="info-row"><span class="info-label">PHP версия:</span><span class="info-value">' + '<?php echo PHP_VERSION; ?>' + '</span></div>';
        html += '</div>';
        html += '</div>';
        return html;
    }
    
    function renderError(message) {
        const content = document.getElementById('terminal-content');
        content.innerHTML = '<div class="monitor-unavailable">' +
            '<h2>Monitor API unavailable</h2>' +
            '<p>Не удалось подключиться к API мониторинга: ' + escapeHtml(message) + '</p>' +
            '</div>';
    }
    
    function getStatusText(status) {
        const texts = {
            'ok': 'OK',
            'warning': 'WARNING',
            'error': 'ERROR',
            'unknown': 'UNKNOWN'
        };
        return texts[status] || status.toUpperCase();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function updateLastRefreshTime() {
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0') + ':' + 
                       now.getSeconds().toString().padStart(2, '0');
        document.getElementById('last-update').textContent = 'Последнее обновление: ' + timeStr;
    }
    
    function updateCountdown() {
        document.getElementById('next-refresh').textContent = 'Следующее обновление через: ' + secondsUntilRefresh + ' сек';
        secondsUntilRefresh--;
        if (secondsUntilRefresh < 0) {
            secondsUntilRefresh = 10;
        }
    }
    
    // Clear Cache button functionality
    document.getElementById('clear-cache-btn').addEventListener('click', function() {
        if (!confirm('Вы уверены, что хотите очистить весь cache?')) {
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        btn.textContent = '⏳ Очистка...';
        
        fetch('/ajax.php?tp=adm&pg=monitor&fn=clear_cache', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.textContent = '✅ Готово! (' + data.deleted + ')';
                btn.style.background = '#4CAF50';
                
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = '🗑️ Очистить Cache';
                    btn.style.background = '#F44336';
                }, 3000);
                
                alert('Cache успешно очищен!\nУдалено файлов: ' + data.deleted);
            } else {
                btn.disabled = false;
                btn.textContent = '❌ Ошибка';
                btn.style.background = '#F44336';
                
                setTimeout(function() {
                    btn.textContent = '🗑️ Очистить Cache';
                    btn.style.background = '#F44336';
                }, 3000);
                
                alert('Ошибка очистки cache: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Clear cache error:', error);
            btn.disabled = false;
            btn.textContent = '❌ Ошибка';
            btn.style.background = '#F44336';
            
            setTimeout(function() {
                btn.textContent = '🗑️ Очистить Cache';
                btn.style.background = '#F44336';
            }, 3000);
            
            alert('Ошибка подключения к серверу');
        });
    });
    
    updateTerminal();
    
    refreshInterval = setInterval(updateTerminal, 10000);
    
    countdownInterval = setInterval(updateCountdown, 1000);
    
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) clearInterval(refreshInterval);
        if (countdownInterval) clearInterval(countdownInterval);
    });
})();
</script>
