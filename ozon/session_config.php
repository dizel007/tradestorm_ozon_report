<?php
// session_config.php

// Путь для сохранения сессий (опционально, если нужно изменить)
// session_save_path('/path/to/secure/sessions');

// Устанавливаем параметры cookie сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);      // Требует HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);    // Защита от фиксации сессии
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0);    // Сессионная cookie (до закрытия браузера)
ini_set('session.gc_maxlifetime', 1800);  // Время жизни сессии на сервере (30 мин)