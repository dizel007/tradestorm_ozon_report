<?php

require_once("../main_info.php");
require_once("../_no_git/secret_info.php");
require_once("../vendor/autoload.php");

require_once("ozon_authorization.php");


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

//****************************************************************************************************
// ---------- Авторизация ----------
//****************************************************************************************************



$url_data = ($_GET['data']);
[$token , $client_id, $seller_id, $shop_id] = ozon_authorization ($pdo, '../registration');


if (isset($_GET['data'])) {
  $data = urlencode($_GET['data']);
} else {  
    header('Location: ../registration');
    exit();
}
//****************************************************************************************************
// ---------- Массив с плиткам - отчетами ----------
//****************************************************************************************************
// Массив отчётов:
//   title, image, description, url — как раньше
//   badge   — текст бейджа (необязательно)
//   disabled — true, если отчёт недоступен (ссылка не работает, плитка затемнена)
$reports = [
    'finance_report' => [
        'title'       => 'Разбор Финансового отчета',
        'image'       => 'img/finans_report.jpg',
        'description' => 'Разбор по SKU всех товаров. Учитываются все расходы, даже которые не привязаны к товарам',
        'url'         => 'ozon_report?data='.$data,
        'badge'       => 'Актуально',
    ],
    'orders_report' => [
        'title'       => 'Отчет по заказам',
        'image'       => 'img/shoping_orders.jpg',
        'description' => 'Отчет показывает все заказы (заказанные, отмененные, доставленные и т.д.) за выбранные даты.',
        'url'         => 'ozon_fbs_fbo_clusters?data='.$data,
    ],
    'logistika' => [
        'title'       => 'Расчет стоимости логистики',
        'image'       => 'img/raschet_logistiki.jpg',
        'description' => 'Калькулятор расчет стоимости лоставки товара. Как выгодней доставить по FBS или FBO + КроссДокинг',
        'url'         => 'ozon_cost_claster_logistik?data='.$data,
        'disabled'    =>  false,
        'badge'       => 'NEW',
    ],
    'returns' => [
        'title'       => 'Возвраты покупатлей',
        'image'       => 'img/_blank.jpg',
        'description' => 'Выборка всех возвратов на выбранный период.',
        'url'         => 'report_purchases.php',
        'disabled'    => true,
        'badge'       => 'В разработке',
    ],
    'FBO' => [
        'title'       => 'FBO продажи на кластерам',
        'image'       => 'img/_blank.jpg',
        'description' => 'Отчет - с какого склада, какие и сколько товаров заказали, за выбранный промежуток времени.',
        'url'         => 'report_finance.php',
        'disabled'    => true,
        'badge'       => 'В разработке',
    ],
    'analytics' => [
        'title'       => 'Аналитика',
        'image'       => 'img/_blank.jpg',
        'description' => 'Сводные дашборды, KPI и сравнение периодов в одном окне.',
        'url'         => 'report_analytics.php',
        'disabled'    => true,
        'badge'       => 'В разработке',
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Отчёты — выберите нужный</title>
<style>
/* ============================================================
   БАЗА
   ============================================================ */
:root {
  --blue: #2563eb;
  --blue-dark: #1d4ed8;
  --green: #10b981;
  --red: #ef4444;
  --slate-900: #0f172a;
  --slate-800: #1e293b;
  --slate-600: #475569;
  --slate-500: #64748b;
  --slate-400: #94a3b8;
  --slate-300: #cbd5e1;
  --slate-200: #e2e8f0;
  --slate-100: #f1f5f9;
  --slate-50: #f8fafc;
  --bg: #f4f6f9;
  --radius: 8px;
  --shadow-sm: 0 1px 4px rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.10);
}

*, *::before, *::after { box-sizing: border-box; }

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: var(--bg);
  color: #334155;
  margin: 0;
  padding: 24px 12px 40px;
  -webkit-font-smoothing: antialiased;
  font-size: 13px;
  line-height: 1.5;
}

/* ============================================================
   ШАПКА
   ============================================================ */
.page-header {
  max-width: 1200px;
  margin: 0 auto 24px;
  text-align: center;
}
.page-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--slate-900);
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}
.page-subtitle {
  font-size: 13px;
  color: var(--slate-500);
  margin: 0;
}

/* ============================================================
   СЕТКА ПЛИТОК
   ============================================================ */
.tiles-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

/* ============================================================
   ПЛИТКА (общее)
   ============================================================ */
.tile {
  position: relative;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid var(--slate-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  text-decoration: none;
  color: inherit;
  overflow: hidden;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  outline: none;
}
.tile:hover,
.tile:focus-visible {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
  border-color: var(--blue);
}
.tile:focus-visible {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25), var(--shadow-md);
}

/* Картинка / иконка */
.tile-thumb {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-bottom: 1px solid var(--slate-200);
  padding: 1px;
}
.tile-thumb img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  display: block;
}
.tile-thumb .thumb-fallback {
  font-size: 40px;
  line-height: 1;
  opacity: 0.85;
}

/* Тело плитки */
.tile-body {
  padding: 14px 16px 18px;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.tile-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--slate-900);
  margin: 0 0 6px;
}
.tile-desc {
  font-size: 12.5px;
  color: var(--slate-500);
  margin: 0;
  flex: 1;
}

/* Стрелка-приглашение */
.tile-arrow {
  margin-top: 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--blue);
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: gap 0.18s ease;
}
.tile:hover .tile-arrow,
.tile:focus-visible .tile-arrow {
  gap: 8px;
}

/* ============================================================
   БЕЙДЖ
   ============================================================ */
.tile-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 3px 8px;
  border-radius: 999px;
  color: #fff;
  background: var(--green);
  z-index: 2;
}
.tile-badge.new       { background: var(--blue); }
.tile-badge.disabled  { background: var(--slate-500); }

/* ============================================================
   ЗАБЛОКИРОВАННАЯ (НЕДОСТУПНАЯ) ПЛИТКА
   ============================================================ */
.tile.is-disabled {
  cursor: not-allowed;
  background: var(--slate-100);
  border-color: var(--slate-200);
  box-shadow: none;
  /* затемнение содержимого */
  filter: grayscale(0.9);
  opacity: 0.75;
}
/* Запрещаем "подъём" при наведении */
.tile.is-disabled:hover,
.tile.is-disabled:focus-visible {
  transform: none;
  box-shadow: none;
  border-color: var(--slate-200);
}
.tile.is-disabled .tile-thumb {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}
.tile.is-disabled .tile-title { color: var(--slate-500); }
.tile.is-disabled .tile-desc  { color: var(--slate-400); }
.tile.is-disabled .tile-arrow {
  color: var(--slate-400);
  gap: 4px !important; /* стрелка не "уезжает" */
}
.tile.is-disabled .tile-thumb img { opacity: 0.6; }
.tile.is-disabled .tile-thumb .thumb-fallback { opacity: 0.5; }

/* ============================================================
   АДАПТИВНОСТЬ
   ============================================================ */
@media (max-width: 640px) {
  body { padding: 16px 10px 32px; }
  .page-title { font-size: 19px; }
  .tiles-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .tile-thumb { height: 100px; }
  .tile-body { padding: 12px 14px 16px; }
}

@media (min-width: 641px) and (max-width: 900px) {
  .tiles-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Доступность */
@media (prefers-reduced-motion: reduce) {
  .tile, .tile-arrow { transition: none; }
  .tile:hover { transform: none; }
}
</style>
</head>
<body>

<header class="page-header">
  <h1 class="page-title">Отчёты</h1>
  <p class="page-subtitle">Выберите отчёт, который вам нужен</p>
</header>

<main class="tiles-grid" role="list">
  <?php foreach ($reports as $key => $report): ?>
    <?php
      $title    = htmlspecialchars($report['title'], ENT_QUOTES, 'UTF-8');
      $desc     = htmlspecialchars($report['description'], ENT_QUOTES, 'UTF-8');
      $url      = htmlspecialchars($report['url'], ENT_QUOTES, 'UTF-8');
      $img      = htmlspecialchars($report['image'], ENT_QUOTES, 'UTF-8');
      $badge    = isset($report['badge']) ? htmlspecialchars($report['badge'], ENT_QUOTES, 'UTF-8') : '';
      $disabled = !empty($report['disabled']);

      // Класс бейджа
      $badgeClass = 'tile-badge';
      if ($disabled) {
          $badgeClass .= ' disabled';
      } elseif ($badge !== '' && mb_strtolower($badge) === 'new') {
          $badgeClass .= ' new';
      }
    ?>

    <?php if ($disabled): ?>
      <!-- Заблокированная плитка: НЕ ссылка, клики не работают -->
      <div class="tile is-disabled"
           role="listitem"
           aria-disabled="true"
           title="Отчёт находится в разработке">
        <?php if ($badge !== ''): ?>
          <span class="<?= $badgeClass ?>"><?= $badge ?></span>
        <?php endif; ?>

        <div class="tile-thumb">
          <img src="<?= $img ?>" alt="" loading="lazy"
               onerror="this.style.display='none'; this.parentNode.innerHTML='<span class=&quot;thumb-fallback&quot; aria-hidden=&quot;true&quot;>📊</span>';">
        </div>

        <div class="tile-body">
          <h2 class="tile-title"><?= $title ?></h2>
          <p class="tile-desc"><?= $desc ?></p>
          <span class="tile-arrow">Недоступно</span>
        </div>
      </div>
    <?php else: ?>
      <!-- Активная плитка: обычная ссылка -->
      <a class="tile"
         href="<?= $url ?>"
         role="listitem"
         aria-label="Открыть отчёт: <?= $title ?>">
        <?php if ($badge !== ''): ?>
          <span class="<?= $badgeClass ?>"><?= $badge ?></span>
        <?php endif; ?>

        <div class="tile-thumb">
          <img src="<?= $img ?>" alt="" loading="lazy"
               onerror="this.style.display='none'; this.parentNode.innerHTML='<span class=&quot;thumb-fallback&quot; aria-hidden=&quot;true&quot;>📊</span>';">
        </div>

        <div class="tile-body">
          <h2 class="tile-title"><?= $title ?></h2>
          <p class="tile-desc"><?= $desc ?></p>
          <span class="tile-arrow">Открыть <span aria-hidden="true">→</span></span>
        </div>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
</main>

</body>
</html>