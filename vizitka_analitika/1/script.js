document.addEventListener('DOMContentLoaded', function() {
    // Элементы DOM
    const authBtn = document.getElementById('authBtn');
    const authModal = document.getElementById('authModal');
    const closeModal = document.querySelector('.close-modal');
    const connectBtn = document.getElementById('connectBtn');
    const updateDataBtn = document.getElementById('updateDataBtn');
    const exportBtn = document.getElementById('exportBtn');
    const apiSettingsBtn = document.getElementById('apiSettingsBtn');
    const supportBtn = document.getElementById('supportBtn');
    
    // Данные для демонстрации (в реальном проекте будут приходить из API)
    const demoData = {
        monthlySales: '125,430 ₽',
        salesChange: '+12.5%',
        totalOrders: '342',
        ordersChange: '+8.2%',
        sellerRating: '4.7',
        availableBalance: '89,240 ₽',
        nextPayout: '15.01.2024'
    };
    
    // Инициализация модального окна
    authBtn.addEventListener('click', () => {
        authModal.style.display = 'flex';
    });
    
    closeModal.addEventListener('click', () => {
        authModal.style.display = 'none';
    });
    
    authModal.addEventListener('click', (e) => {
        if (e.target === authModal) {
            authModal.style.display = 'none';
        }
    });
    
    // Подключение к API (демо)
    connectBtn.addEventListener('click', () => {
        const apiKey = document.getElementById('apiKey').value;
        const clientId = document.getElementById('clientId').value;
        
        if (!apiKey || !clientId) {
            alert('Пожалуйста, заполните все поля');
            return;
        }
        
        // Имитация подключения к API
        connectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Подключение...';
        connectBtn.disabled = true;
        
        setTimeout(() => {
            connectBtn.innerHTML = '<i class="fas fa-check"></i> Успешно подключено';
            connectBtn.style.background = 'linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%)';
            
            // Обновляем данные
            updateDashboardData();
            
            // Закрываем модальное окно через 1.5 секунды
            setTimeout(() => {
                authModal.style.display = 'none';
                connectBtn.innerHTML = '<i class="fas fa-plug"></i> Подключиться к Ozon';
                connectBtn.disabled = false;
                connectBtn.style.background = 'linear-gradient(135deg, #005bff 0%, #00a8ff 100%)';
            }, 1500);
        }, 2000);
    });
    
    // Обновление данных на дашборде
    function updateDashboardData() {
        document.getElementById('monthlySales').textContent = demoData.monthlySales;
        document.getElementById('salesChange').textContent = demoData.salesChange;
        document.getElementById('totalOrders').textContent = demoData.totalOrders;
        document.getElementById('ordersChange').textContent = demoData.ordersChange;
        document.getElementById('sellerRating').textContent = demoData.sellerRating;
        document.getElementById('availableBalance').textContent = demoData.availableBalance;
        document.getElementById('nextPayout').textContent = demoData.nextPayout;
        
        // Обновляем время последнего обновления
        const now = new Date();
        const timeString = now.toLocaleTimeString('ru-RU', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        document.getElementById('lastUpdate').textContent = `сегодня, ${timeString}`;
        
        // Обновляем таблицу топ товаров
        updateTopProductsTable();
        
        // Показываем сообщение об успехе
        showNotification('Данные успешно обновлены!', 'success');
    }
    
    // Обновление таблицы топ товаров
    function updateTopProductsTable() {
        const demoProducts = [
            { name: 'Смартфон Apple iPhone 14',    sales: 45, revenue: '560,430 ₽', rating: 4.8 },
            { name: 'Наушники Sony WH-1000XM4',    sales: 32, revenue: '289,990 ₽', rating: 4.9 },
            { name: 'Ноутбук ASUS VivoBook',       sales: 28, revenue: '320,500 ₽', rating: 4.6 },
            { name: 'Умные часы Samsung Galaxy',   sales: 25, revenue: '187,250 ₽', rating: 4.7 },
            { name: 'Игровая мышь Logitech G Pro', sales: 41, revenue: '123,590 ₽', rating: 4.5 }
        ];
        
        const tableBody = document.getElementById('topProductsTable');
        tableBody.innerHTML = '';
        
        demoProducts.forEach(product => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${product.name}</td>
                <td>${product.sales}</td>
                <td>${product.revenue}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        ${product.rating.toFixed(1)}
                        <div style="color: #FFC107; font-size: 12px;">
                            ${'★'.repeat(Math.floor(product.rating))}${product.rating % 1 ? '☆' : ''}
                        </div>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    // Обработчики кнопок
    updateDataBtn.addEventListener('click', () => {
        updateDataBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обновление...';
        updateDataBtn.disabled = true;
        
        setTimeout(() => {
            updateDashboardData();
            updateDataBtn.innerHTML = '<i class="fas fa-sync"></i> Обновить данные';
            updateDataBtn.disabled = false;
        }, 1500);
    });
    
    exportBtn.addEventListener('click', () => {
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Экспорт...';
        
        setTimeout(() => {
            alert('Отчет успешно экспортирован в формате CSV');
            exportBtn.innerHTML = '<i class="fas fa-file-export"></i> Экспорт отчета';
        }, 1000);
    });
    
    apiSettingsBtn.addEventListener('click', () => {
        authModal.style.display = 'flex';
    });
    
    supportBtn.addEventListener('click', () => {
        alert('Для связи с поддержкой напишите на support@ozon-analytics.ru или позвоните по номеру +7 (999) 123-45-67');
    });
    
    // Обработчик изменения периода
    document.getElementById('periodSelect').addEventListener('change', function() {
        const chartPlaceholder = document.querySelector('.chart-placeholder');
        const periods = {
            week: 'за неделю',
            month: 'за месяц',
            quarter: 'за квартал'
        };
        
        chartPlaceholder.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-chart-line" style="font-size: 48px; color: #005bff; margin-bottom: 20px;"></i>
                <p>График продаж ${periods[this.value]}</p>
                <p style="font-size: 14px; color: #999; margin-top: 10px;">
                    Данные успешно загружены через Ozon API
                </p>
            </div>
        `;
    });
    
    // Функция показа уведомлений
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: ${type === 'success' ? '#4CAF50' : '#2196F3'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1001;
            animation: slideIn 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Добавляем стили для анимации уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Инициализация демо данных при загрузке
    setTimeout(() => {
        // Показываем приветственное сообщение
        showNotification('Добро пожаловать в TradeStorm Analytics! Подключите API для начала работы.', 'info');
    }, 1000);
});