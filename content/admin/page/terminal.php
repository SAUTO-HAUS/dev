<?php defined('_DOIT') or die('Restricted access'); ?>

<style>
body {
    background: #000;
    margin: 0;
    padding: 0;
}

#terminal-container {
    background: #000;
    color: #0f0;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 14px;
    padding: 20px;
    min-height: 100vh;
    line-height: 1.4;
}

#terminal-header {
    margin-bottom: 20px;
    border-bottom: 1px solid #0f0;
    padding-bottom: 10px;
}

#terminal-header h1 {
    color: #0f0;
    font-size: 16px;
    font-weight: normal;
    margin: 0;
    font-family: 'Consolas', 'Courier New', monospace;
}

.clear-cache-btn {
    background: transparent;
    color: #0f0;
    border: 1px solid #0f0;
    padding: 5px 15px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 12px;
    cursor: pointer;
    margin-top: 10px;
}

.clear-cache-btn:hover {
    background: #0f0;
    color: #000;
}

.clear-cache-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#terminal-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.terminal-line {
    margin: 2px 0;
}

.terminal-section {
    margin: 15px 0;
}

.terminal-separator {
    color: #0f0;
    margin: 10px 0;
}

.terminal-footer {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #0f0;
    color: #0f0;
    font-size: 12px;
}

.monitor-unavailable {
    color: #0f0;
    padding: 20px 0;
}

/* Mobile responsive styles */
@media screen and (max-width: 768px) {
    #terminal-container {
        padding: 10px;
        font-size: 12px;
    }
    
    #terminal-header {
        margin-bottom: 15px;
        padding-bottom: 8px;
    }
    
    #terminal-header h1 {
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .clear-cache-btn {
        font-size: 11px;
        padding: 4px 10px;
        margin-top: 8px;
        width: 100%;
        display: block;
    }
    
    #terminal-content {
        font-size: 11px;
        overflow-x: auto;
    }
    
    .terminal-footer {
        font-size: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
}

@media screen and (max-width: 480px) {
    #terminal-container {
        padding: 8px;
        font-size: 11px;
    }
    
    #terminal-header h1 {
        font-size: 13px;
    }
    
    .clear-cache-btn {
        font-size: 10px;
        padding: 3px 8px;
    }
    
    #terminal-content {
        font-size: 10px;
    }
    
    .terminal-footer {
        font-size: 9px;
    }
}
</style>

<div id="terminal-container">
    <div id="terminal-header">
        <h1>SAUTO Монитор Системы</h1>
        <button id="clear-cache-btn" class="clear-cache-btn">[ОЧИСТИТЬ CACHE]</button>
    </div>

    <div id="terminal-content">
        <div class="monitor-unavailable">
&gt; Загрузка монитора системы...
&gt; Подключение к сервису мониторинга...
        </div>
    </div>

    <div class="terminal-footer">
        <div id="last-update">Последнее обновление: --:--:--</div>
        <div id="next-refresh">Следующее обновление: 10 сек</div>
    </div>
</div>

<script>
(function() {
    let refreshInterval = null;
    let countdownInterval = null;
    let secondsUntilRefresh = 10;
    
    function updateTerminal() {
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
            secondsUntilRefresh = 10;
        })
        .catch(error => {
            console.error('Monitor API Error:', error);
            renderError(error.message);
        });
    }
    
    function renderTerminal(data) {
        const content = document.getElementById('terminal-content');
        content.innerHTML = '';
        
        // Header
        addGreenLine(content, '> Проверка Состояния Системы [' + data.server_time + ']');
        addGreenLine(content, '> ' + '='.repeat(70));
        addLine(content, '');
        
        // Database
        addGreenLine(content, '[БАЗА ДАННЫХ]');
        addStatusLine(content, '  Статус: ' + data.database.toUpperCase(), data.database);
        if (data.details.database.message) {
            addWhiteLine(content, '  ' + data.details.database.message);
        }
        if (data.details.database.response_time) {
            addWhiteLine(content, '  Время отклика: ' + data.details.database.response_time);
        }
        addLine(content, '');
        addGreenLine(content, '  ' + '-'.repeat(68));
        addLine(content, '');
        
        // Cars
        addGreenLine(content, '[КАТАЛОГ АВТОМОБИЛЕЙ]');
        addStatusLine(content, '  Статус: ' + data.cars.toUpperCase(), data.cars);
        if (data.details.cars.message) {
            addWhiteLine(content, '  ' + data.details.cars.message);
        }
        if (data.details.cars.last_car) {
            addWhiteLine(content, '  Последняя машина: #' + data.details.cars.last_car.id + ' - ' + data.details.cars.last_car.brand + ' ' + data.details.cars.last_car.model);
            addWhiteLine(content, '  Добавлена: ' + data.details.cars.last_date + ' (' + data.details.cars.days_ago + ' дней назад)');
        }
        addLine(content, '');
        addGreenLine(content, '  ' + '-'.repeat(68));
        addLine(content, '');
        
        // Leads
        addGreenLine(content, '[ЛИДЫ / СООБЩЕНИЯ]');
        addStatusLine(content, '  Статус: ' + data.leads.toUpperCase(), data.leads);
        if (data.details.leads.message) {
            addWhiteLine(content, '  ' + data.details.leads.message);
        }
        if (data.details.leads.last_lead) {
            addWhiteLine(content, '  Последний лид: #' + data.details.leads.last_lead.id + ' - ' + data.details.leads.last_lead.folder);
            addWhiteLine(content, '  Получен: ' + data.details.leads.last_date + ' (' + data.details.leads.hours_ago + ' часов назад)');
        }
        addLine(content, '');
        addGreenLine(content, '  ' + '-'.repeat(68));
        addLine(content, '');
        
        // Disk
        addGreenLine(content, '[МЕСТО НА ДИСКЕ]');
        addStatusLine(content, '  Статус: ' + data.disk.toUpperCase(), data.disk);
        if (data.details.disk.message) {
            addWhiteLine(content, '  ' + data.details.disk.message);
        }
        if (data.details.disk.total) {
            addWhiteLine(content, '  Всего: ' + data.details.disk.total);
            addWhiteLine(content, '  Использовано: ' + data.details.disk.used + ' (' + data.details.disk.percent_used + '%)');
            addWhiteLine(content, '  Свободно: ' + data.details.disk.free + ' (' + data.details.disk.percent_free + '%)');
        }
        addLine(content, '');
        addGreenLine(content, '  ' + '-'.repeat(68));
        addLine(content, '');
        
        // Publications
        addGreenLine(content, '[ОШИБКИ ПУБЛИКАЦИИ - За последние 24 часа]');
        addStatusLine(content, '  Статус: ' + data.publications.toUpperCase(), data.publications);
        if (data.details.publications.message) {
            addWhiteLine(content, '  ' + data.details.publications.message);
        }
        if (data.details.publications.details) {
            addWhiteLine(content, '  999.md: Failed=' + data.details.publications.details['999'].failed + ' Postponed=' + data.details.publications.details['999'].postponed);
            if (data.details.publications.details['999'].errors && data.details.publications.details['999'].errors.length > 0) {
                data.details.publications.details['999'].errors.forEach(function(err) {
                    addWhiteLine(content, '    - Car #' + err.car_id + ': ' + err.message.substring(0, 60) + '... [' + err.time + ']');
                });
            }
            addWhiteLine(content, '  Facebook: Failed=' + data.details.publications.details.facebook.failed + ' Pending=' + data.details.publications.details.facebook.pending);
            if (data.details.publications.details.facebook.errors && data.details.publications.details.facebook.errors.length > 0) {
                data.details.publications.details.facebook.errors.forEach(function(err) {
                    addWhiteLine(content, '    - Car #' + err.car_id + ': ' + err.message.substring(0, 60) + '... [' + err.time + ']');
                });
            }
            addWhiteLine(content, '  Telegram: Failed=' + data.details.publications.details.telegram.failed + ' Pending=' + data.details.publications.details.telegram.pending);
            if (data.details.publications.details.telegram.errors && data.details.publications.details.telegram.errors.length > 0) {
                data.details.publications.details.telegram.errors.forEach(function(err) {
                    addWhiteLine(content, '    - Car #' + err.car_id + ': ' + err.message.substring(0, 60) + '... [' + err.time + ']');
                });
            }
            addWhiteLine(content, '  Всего ошибок: ' + data.details.publications.total_failed);
        }
        addLine(content, '');
        addGreenLine(content, '  ' + '-'.repeat(68));
        addLine(content, '');
        
        // Last error
        if (data.last_error && Array.isArray(data.last_error) && data.last_error.length > 0) {
            addGreenLine(content, '[ПОСЛЕДНЯЯ ОШИБКА]');
            var error = data.last_error[0];
            addWhiteLine(content, '  ' + error.message);
            addWhiteLine(content, '  Файл: ' + (error.file || 'N/A') + ' | Время: ' + (error.time || 'N/A'));
            addLine(content, '');
            addGreenLine(content, '  ' + '-'.repeat(68));
            addLine(content, '');
        }
        
        // Server info
        addGreenLine(content, '[СЕРВЕР]');
        addWhiteLine(content, '  Время: ' + data.server_time);
        addWhiteLine(content, '  PHP: <?php echo PHP_VERSION; ?>');
        addLine(content, '');
        
        addGreenLine(content, '> ' + '='.repeat(70));
        addGreenLine(content, '> Проверка мониторинга завершена.');
    }
    
    function addGreenLine(container, text) {
        const div = document.createElement('div');
        div.style.color = '#0f0';
        div.textContent = text;
        container.appendChild(div);
    }
    
    function addWhiteLine(container, text) {
        const div = document.createElement('div');
        div.style.color = '#fff';
        div.textContent = text;
        container.appendChild(div);
    }
    
    function addStatusLine(container, text, status) {
        const div = document.createElement('div');
        if (status === 'ok') {
            div.style.color = '#0f0';
        } else if (status === 'warning' || status === 'error') {
            div.style.color = '#f00';
        } else {
            div.style.color = '#fff';
        }
        div.textContent = text;
        container.appendChild(div);
    }
    
    function addLine(container, text) {
        const div = document.createElement('div');
        div.textContent = text;
        container.appendChild(div);
    }
    
    function renderError(message) {
        const content = document.getElementById('terminal-content');
        let output = '';
        output += '> ERROR: Monitor API unavailable\n';
        output += '> Failed to connect to monitoring service\n';
        output += '> ' + message + '\n';
        content.textContent = output;
    }
    
    
    function updateLastRefreshTime() {
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0') + ':' + 
                       now.getSeconds().toString().padStart(2, '0');
        document.getElementById('last-update').textContent = 'Последнее обновление: ' + timeStr;
    }
    
    function updateCountdown() {
        document.getElementById('next-refresh').textContent = 'Следующее обновление: ' + secondsUntilRefresh + ' сек';
        secondsUntilRefresh--;
        if (secondsUntilRefresh < 0) {
            secondsUntilRefresh = 10;
        }
    }
    
    // Clear Cache button functionality
    document.getElementById('clear-cache-btn').addEventListener('click', function() {
        if (!confirm('Очистить все файлы cache?')) {
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        btn.textContent = '[ОЧИСТКА...]';
        
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
                btn.textContent = '[OK: ' + data.deleted + ' файлов]';
                
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = '[ОЧИСТИТЬ CACHE]';
                }, 3000);
                
                alert('Cache успешно очищен!\nУдалено файлов: ' + data.deleted);
            } else {
                btn.disabled = false;
                btn.textContent = '[ОШИБКА]';
                
                setTimeout(function() {
                    btn.textContent = '[ОЧИСТИТЬ CACHE]';
                }, 3000);
                
                alert('Ошибка очистки cache: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Clear cache error:', error);
            btn.disabled = false;
            btn.textContent = '[ОШИБКА]';
            
            setTimeout(function() {
                btn.textContent = '[ОЧИСТИТЬ CACHE]';
            }, 3000);
            
            alert('Ошибка подключения');
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
