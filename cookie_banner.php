<?php
/**
 * cookie_banner.php
 *
 * Подключается в index.php через: require_once 'cookie_banner.php';
 *
 * Файл также работает как AJAX-эндпоинт для серверного логирования согласия.
 * JS-код отправляет POST-запрос именно в этот файл, а не в index.php.
 *
 * Лог согласий сохраняется в файл cookie_consents.log в той же директории.
 * ВАЖНО: закройте доступ к логу снаружи (через .htaccess или вынесите за web-root).
 */

// ============================================================
// 1. AJAX-ЭНДПОИНТ: логирование согласия пользователя
// ============================================================
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_POST['action'] ?? '') === 'log_cookie_consent'
) {
    $consentType = $_POST['consent_type'] ?? 'unknown';
    if (!in_array($consentType, ['all', 'necessary', 'custom'], true)) {
        $consentType = 'unknown';
    }

    $entry = [
        'timestamp'    => date('Y-m-d H:i:s'),
        'ip'           => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'   => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'consent_type' => $consentType,
        'categories'   => [
            'necessary' => true,
            'analytics' => ($_POST['analytics'] ?? '') === '1',
            'marketing' => ($_POST['marketing'] ?? '') === '1',
        ],
        'referer'      => $_SERVER['HTTP_REFERER'] ?? '',
    ];

    @file_put_contents(
        '/var/www/logs/cookie_consents.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND | LOCK_EX
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok']);
    exit;
}
// ============================================================
// 2. РЕНДЕР БАННЕРА (при обычном GET / при подключении в index.php)
// ============================================================
?>

<style>
    /* ---------- Cookie-баннер ---------- */
    #cookie-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #ffffff;
        border-top: 1px solid #d1d9e0;
        box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.08);
        padding: 18px 24px;
        z-index: 99998;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 14px;
        line-height: 1.55;
        color: #1f2328;
        display: none;
    }
    #cookie-banner.visible { display: block; }

    #cookie-banner .cb-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    #cookie-banner .cb-text { flex: 1 1 500px; }
    #cookie-banner .cb-text a {
        color: #0b5fff;
        text-decoration: underline;
    }
    #cookie-banner .cb-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
        flex-wrap: wrap;
    }
    #cookie-banner button {
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid transparent;
        transition: background-color .15s, border-color .15s;
    }
    #cookie-banner .cb-btn-accept {
        background: #0b5fff;
        color: #fff;
    }
    #cookie-banner .cb-btn-accept:hover { background: #0a4ecc; }
    #cookie-banner .cb-btn-necessary {
        background: #fff;
        color: #1f2328;
        border-color: #d1d9e0;
    }
    #cookie-banner .cb-btn-necessary:hover { background: #f6f8fa; }
    #cookie-banner .cb-btn-settings {
        background: transparent;
        color: #0b5fff;
        text-decoration: underline;
        padding: 10px 4px;
    }

    /* ---------- Модальное окно настроек ---------- */
    #cookie-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    #cookie-modal-overlay.visible { display: flex; }

    #cookie-modal {
        background: #fff;
        border-radius: 10px;
        max-width: 560px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 28px;
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.25);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        color: #1f2328;
    }
    #cookie-modal h2 {
        margin: 0 0 8px;
        font-size: 20px;
    }
    #cookie-modal .cm-intro {
        font-size: 14px;
        color: #59636e;
        margin: 0 0 20px;
    }
    #cookie-modal .cm-item {
        display: flex;
        gap: 12px;
        padding: 14px;
        border: 1px solid #d1d9e0;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    #cookie-modal .cm-item.disabled {
        background: #f6f8fa;
        opacity: 0.85;
    }
    #cookie-modal .cm-item input[type="checkbox"] {
        margin-top: 4px;
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        cursor: pointer;
    }
    #cookie-modal .cm-item input[type="checkbox"]:disabled {
        cursor: not-allowed;
    }
    #cookie-modal .cm-item label {
        cursor: pointer;
        flex: 1;
    }
    #cookie-modal .cm-item strong {
        display: block;
        margin-bottom: 4px;
        font-size: 14px;
    }
    #cookie-modal .cm-item span {
        font-size: 13px;
        color: #59636e;
        line-height: 1.5;
    }
    #cookie-modal .cm-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    #cookie-modal .cm-actions button {
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid transparent;
    }
    #cookie-modal .cm-btn-save {
        background: #0b5fff;
        color: #fff;
    }
    #cookie-modal .cm-btn-save:hover { background: #0a4ecc; }
    #cookie-modal .cm-btn-cancel {
        background: #fff;
        color: #1f2328;
        border-color: #d1d9e0;
    }
    #cookie-modal .cm-btn-cancel:hover { background: #f6f8fa; }

    @media (max-width: 640px) {
        #cookie-banner .cb-inner { flex-direction: column; align-items: stretch; }
        #cookie-banner .cb-actions { flex-direction: column; }
        #cookie-banner button { width: 100%; }
    }
</style>

<!-- Cookie-баннер -->
<div id="cookie-banner" role="dialog" aria-live="polite" aria-label="Согласие на использование cookie">
    <div class="cb-inner">
        <div class="cb-text">
            Мы используем cookie для работы сайта, аналитики и улучшения сервиса.
            Необходимые cookie загружаются всегда, аналитические и маркетинговые — только с вашего согласия.
            Подробнее — в <a href="privacy.html" target="_blank" rel="noopener">Политике обработки персональных данных</a>.
        </div>
        <div class="cb-actions">
            <button type="button" class="cb-btn-necessary" id="cb-necessary">Только необходимые</button>
            <button type="button" class="cb-btn-accept" id="cb-accept">Принять все</button>
            <button type="button" class="cb-btn-settings" id="cb-settings">Настроить</button>
        </div>
    </div>
</div>

<!-- Модальное окно настроек -->
<div id="cookie-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="cm-title">
    <div id="cookie-modal">
        <h2 id="cm-title">Настройки cookie</h2>
        <p class="cm-intro">
            Выберите, какие категории cookie мы можем использовать. Необходимые cookie нельзя отключить —
            без них сайт не работает.
        </p>

        <div class="cm-item disabled">
            <input type="checkbox" id="cm-cat-necessary" checked disabled>
            <label for="cm-cat-necessary">
                <strong>Необходимые</strong>
                <span>Аутентификация, безопасность, базовая работа сайта. Всегда включены.</span>
            </label>
        </div>

        <div class="cm-item">
            <input type="checkbox" id="cm-cat-analytics">
            <label for="cm-cat-analytics">
                <strong>Аналитические</strong>
                <span>Помогают понять, как пользователи взаимодействуют с сайтом (например, Яндекс.Метрика, Google Analytics).</span>
            </label>
        </div>

        <div class="cm-item">
            <input type="checkbox" id="cm-cat-marketing">
            <label for="cm-cat-marketing">
                <strong>Маркетинговые</strong>
                <span>Используются для показа релевантной рекламы и ретаргетинга.</span>
            </label>
        </div>

        <div class="cm-actions">
            <button type="button" class="cm-btn-cancel" id="cm-cancel">Отмена</button>
            <button type="button" class="cm-btn-save" id="cm-save">Сохранить выбор</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ---------- НАСТРОЙКИ ----------
    var CONSENT_ENDPOINT = 'cookie_banner.php'; // путь к этому файлу относительно index.php
    var COOKIE_NAME = 'cookie_consent_v1';
    var COOKIE_EXPIRY_DAYS = 365;

    // ---------- УТИЛИТЫ ----------
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var d = new Date();
            d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + d.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var prefix = name + '=';
        var parts = document.cookie.split(';');
        for (var i = 0; i < parts.length; i++) {
            var c = parts[i].trim();
            if (c.indexOf(prefix) === 0) return c.substring(prefix.length);
        }
        return null;
    }

    function parseConsent(raw) {
        if (!raw) return null;
        try {
            return JSON.parse(decodeURIComponent(raw));
        } catch (e) {
            return null;
        }
    }

    // ---------- ЗАГРУЗКА СКРИПТОВ ----------
    // ⚠️ ЗАМЕНИТЕ СОДЕРЖИМОЕ ФУНКЦИЙ НА СВОИ РЕАЛЬНЫЕ СКРИПТЫ
    function loadAnalyticsScripts() {
        // Пример для Яндекс.Метрики:
        // (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        // m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
        // k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        // (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        // ym(XXXXXXXX, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true });
        console.log('[cookie] Аналитические скрипты загружены');
    }

    function loadMarketingScripts() {
        // Пример: VK Pixel, Facebook Pixel, Top.Mail.Ru и т.д.
        console.log('[cookie] Маркетинговые скрипты загружены');
    }

    function applyConsent(state) {
        if (!state) return;
        if (state.analytics) loadAnalyticsScripts();
        if (state.marketing) loadMarketingScripts();
    }

    // ---------- ЛОГИРОВАНИЕ НА СЕРВЕР ----------
    function logConsentToServer(state, type) {
        try {
            var body = new URLSearchParams();
            body.append('action', 'log_cookie_consent');
            body.append('consent_type', type);
            body.append('analytics', state.analytics ? '1' : '0');
            body.append('marketing', state.marketing ? '1' : '0');

            fetch(CONSENT_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () { /* игнорируем ошибки логирования */ });
        } catch (e) { /* игнорируем */ }
    }

    // ---------- СОХРАНЕНИЕ СОСТОЯНИЯ ----------
    function saveConsent(state, type) {
        var encoded = encodeURIComponent(JSON.stringify(state));
        setCookie(COOKIE_NAME, encoded, COOKIE_EXPIRY_DAYS);
        logConsentToServer(state, type);
    }

    // ---------- ОСНОВНАЯ ЛОГИКА ----------
    function init() {
        var banner = document.getElementById('cookie-banner');
        var modal = document.getElementById('cookie-modal-overlay');
        if (!banner || !modal) return;

        var stored = parseConsent(getCookie(COOKIE_NAME));
        if (stored) {
            // Согласие уже было дано — применяем и не показываем баннер
            applyConsent(stored);
            return;
        }

        // Согласия нет — показываем баннер
        banner.classList.add('visible');

        // --- Кнопка "Принять все" ---
        document.getElementById('cb-accept').addEventListener('click', function () {
            var state = { necessary: true, analytics: true, marketing: true };
            saveConsent(state, 'all');
            banner.classList.remove('visible');
            applyConsent(state);
        });

        // --- Кнопка "Только необходимые" ---
        document.getElementById('cb-necessary').addEventListener('click', function () {
            var state = { necessary: true, analytics: false, marketing: false };
            saveConsent(state, 'necessary');
            banner.classList.remove('visible');
        });

        // --- Кнопка "Настроить" ---
        document.getElementById('cb-settings').addEventListener('click', function () {
            modal.classList.add('visible');
        });

        // --- Кнопка "Отмена" в модалке ---
        document.getElementById('cm-cancel').addEventListener('click', function () {
            modal.classList.remove('visible');
        });

        // --- Кнопка "Сохранить выбор" в модалке ---
        document.getElementById('cm-save').addEventListener('click', function () {
            var state = {
                necessary: true,
                analytics: document.getElementById('cm-cat-analytics').checked,
                marketing: document.getElementById('cm-cat-marketing').checked
            };
            saveConsent(state, 'custom');
            banner.classList.remove('visible');
            modal.classList.remove('visible');
            applyConsent(state);
        });

        // --- Закрытие модалки по клику на оверлей ---
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.remove('visible');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>