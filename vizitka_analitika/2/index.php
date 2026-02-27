<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm | Лучшая аналитика маркетплейсов</title>
    <link rel="stylesheet" href="style.css">




</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="#" class="logo">Trade<span>Storm</span></a>
            <nav class="nav">
                <a href="#projects">Разработки</a>
                <a href="#partners">Интеграции</a>
                <a href="#articles">Блог</a>
                <a href="#faq">FAQ</a>
                <a href="#contacts">Контакты</a>
            </nav>
            <div class="phone">+7 (950) 540-40-24</div>
        </div>
    </header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-background">
        <img src="pics/hero_banner.png" alt="Marketplace Automation" class="hero-image">
        <div class="hero-overlay"></div>
    </div>
    <div class="container">
        <div class="hero-content">
            <h1>Автоматизация работы на маркетплейсах</h1>
            <p>API интеграция, аналитика FBS заказов, умные отчеты и полный контроль вашего бизнеса</p>
            <a href="#projects" class="btn">Запустить автоматизацию</a>
        </div>
    </div>
</section>

    <!-- Projects Section -->
    <section id="projects" class="projects">
        <div class="container">
            <h2 class="section-title">Наши разработки</h2>
            <div class="projects-grid">
                <div class="project-card">
                    <div class="project-image" style="background-image: url('pics/project1.png')"></div>
                    <div class="project-info">
                        <h3>Финансовый отчет OZON</h3>
                        <p>Поартикульный разбор затрат на товары. Выделяем комиссии, логистику, эквайринг и все возможные затраты</p>
                    </div>
                </div>
                <div class="project-card">
                    <div class="project-image" style="background-image: url('pics/project1.png')"></div>
                    <div class="project-info">
                        <h3>Финансовый отчет WB</h3>
                        <p>(в разработке)</p>
                    </div>
                </div>
                <div class="project-card">
                    <div class="project-image" style="background-image: url('pics/project1.png')"></div>
                    <div class="project-info">
                        <h3>Финансовый отчет Яндекс Маркет</h3>
                        <p>(в разработке)</p>
                    </div>
                </div>
            

            </div>
        </div>
    </section>

    <!-- Video Section -->
    <section class="video-section">
        <div class="container">
            <h2 class="section-title">Как автоматизировать маркетплейсы?</h2>
            <p style="text-align: center; margin-bottom: 40px;">Смотрите видео о наших решениях</p>
            <div class="video-grid">
                <div class="video-item">
                      <div class="video-thumb">
                        <video class="video_time" id="myVideo" width="240" height="170" 
                            poster="video/212.jpg" 
                            controls preload="none" playsinline>
                        <source src="video/212.mp4" type="video/mp4">
                        Ваш браузер не поддерживает видео.
                        </video>
                    <p>Как подключить аналитику финансовых отчетов для OZON за 5 минут</p>
                </div>
                </div>

                <div class="video-item">
                    <div class="video-thumb"></div>
                    <p>Как получить отчеты, и как работать с ними</p>
                </div>
                <div class="video-item">
                    <div class="video-thumb"></div>
                    <p>Создание отчетов в реальном времени</p>
                </div>
                <div class="video-item">
                    <div class="video-thumb"></div>
                    <p>Разбор FBS: ошибки и оптимизация</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature">
                    <!-- <div class="feature-icon"></div> -->
                    <div ><img class="unit_economica" src="pics/features/unit_economica.jpg" alt="Юнит экономика по маркетплейсам"></div>
                    <h3>Реальная юнит-экономика</h3>
                    <p>Мы автоматически рассчитываем чистую прибыль с каждого проданного товара. Система вычитает из выручки все фактические расходы: комиссии маркетплейсов, логистику, хранение, рекламу и себестоимость закупки.</p>
                </div>
                <div class="feature">
                    <!-- <div class="feature-icon"></div> -->
                    <div><img class="unit_economica" src="pics/features/zatrati_po_stat.jpg" alt="Детализация затрат по статьям маркетплейса"></div>
                    <h3>Детализация затрат по статьям</h3>
                    <p>Вы видите полную структуру расходов: комиссии Ozon/WB, логистика (FBO/FBS), хранение на складе, возвраты, рекламные кампании, упаковка, брак и прочие издержки. Никаких скрытых списаний.</p>
                </div>
                <div class="feature">
                    <!-- <div class="feature-icon"></div> -->
                    <div><img class="unit_economica" src="pics/features/get_data_api.jpg" alt="Автоматический сбор через API Ozon и Wildberries"></div>

                    <h3>Автоматический сбор через API Ozon и Wildberries</h3>
                    <p>Данные подгружаются напрямую с маркетплейсов. Вам не нужно вручную сводить отчёты — всё уже разложено по полкам в личном кабинете.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <!-- <section id="team" class="team">
        <div class="container">
            <h2 class="section-title">Команда разработки</h2>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-photo"></div>
                    <h3>Дмитрий</h3>
                    <div class="position">Lead API Developer</div>
                    <p>5 лет интеграций с маркетплейсами, эксперт по Ozon и Wildberries API</p>
                </div>
                <div class="team-card">
                    <div class="team-photo"></div>
                    <h3>Екатерина</h3>
                    <div class="position">Data Analyst</div>
                    <p>Разработка алгоритмов прогнозирования и аналитических отчетов</p>
                </div>
                <div class="team-card">
                    <div class="team-photo"></div>
                    <h3>Андрей</h3>
                    <div class="position">DevOps Engineer</div>
                    <p>Обеспечение стабильной работы API и безопасности данных</p>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Partners -->
    <section id ="partners" class="partners">
        <div class="container">
            <h2 class="section-title">Интеграции с площадками</h2>
            <div class="partners-grid">
                <span class="partner-item">Wildberries API</span>
                <span class="partner-item">Ozon Seller API</span>
                <span class="partner-item">Yandex Market</span>
    
            </div>
        </div>
    </section>

    <!-- Online Control -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature">
                    <!-- <div class="feature-icon"></div> -->
                    <div>
                        <img class="technical_support" src="pics/teh_podderzhka.jpg" alt="Детализация затрат по статьям маркетплейса">
                    </div>

                    <h3>Мониторинг 24/7</h3>
                    <p>Отслеживайте работу своей магазина через API в реальном времени. Мы можете моментально увидить, что товар перестал давать прибыль и вытягивает с Вас деньги</p>
                    <br>
                   <a href="#projects" class="btn">Запустить автоматизацию</a> 
                </div>
                
            </div>
        </div>
    </section>

    <!-- Articles Section -->
    <section id="articles" class="articles">
        <div class="container">
            <h2 class="section-title">Блог об автоматизации</h2>
            <div class="articles-grid">
                <div class="article-card">
                    <h3>Чем сервис отличается от других</h3>
                    <p>Скорость работы, простой и удобный интерфейс, и мы не просто выдаем Вам расчеты и цифры, а позволяем сравнить их с данными в личном кабинете</p>
                </div>
                <div class="article-card">
                    <h3>Сколько кабинетов можно подключить</h3>
                    <p>На данный момент не ограничения по кабинетам. Пока всю бесплатно, но в дальнейшем планируется плата в размере 500руб/месяц за каждый кабинет</p>
                </div>
                <div class="article-card">
                    <h3>Для чего сервис аналитики маркетплейсов нужен</h3>
                    <p>Мы можете в ежедненом редиме отслеживать динамику не только продаж, но и динамику прибыли, учитывая все траты, комиссии и себестоимости товаров</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq">
        <div class="container">
            <h2 class="section-title">Часто задаваемые вопросы</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h3>Сколько времени занимает интеграция API?</h3>
                    <p>Базовая интеграция занимает от 1 до 7 дней. Сложные проекты с кастомной аналитикой — по согласованию.</p>
                </div>
                <div class="faq-item">
                    <h3>Какие маркетплейсы вы поддерживаете?</h3>
                    <p>Wildberries, Ozon, Yandex Market</p>
                </div>
                <div class="faq-item">
                    <h3>Как происходит автоматизация FBS заказов?</h3>
                    <p>Система автоматически получает заказы по API, формирует этикетки, обновляет остатки и отправляет уведомления.</p>
                </div>
                <div class="faq-item">
                    <h3>Можно ли настроить кастомные отчеты?</h3>
                    <p>Да, мы разрабатываем любые отчеты под ваши задачи: по продажам, остаткам, рейтингам, комиссиям и т.д.</p>
                </div>
                <div class="faq-item">
                    <h3>Как обеспечивается безопасность?</h3>
                    <p>Все ключи API хранятся в зашифрованном виде, используется двухфакторная аутентификация и HTTPS протоколы.</p>
                </div>
                <div class="faq-item">
                    <h3>Есть ли техническая поддержка?</h3>
                    <p>Круглосуточная поддержка через email. Среднее время ответа — 60 минут.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacts" class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>TradeStorm</h3>
                    <p>Автоматизация маркетплейсов<br>и аналитика FBS заказов</p>
                </div>
                <div>
                    <h3>Контакты</h3>
                    <!-- <a href="tel:+74994033216">+7 (499) 403-32-16</a> -->
                    <a href="mailto:info@tradestorm.ru">info@tradestorm.ru</a>
                    <p>г. Москва, ул. Льва Толстого, 16<br>БЦ "Морозов", офис 405</p>
                </div>
                <div>
                    <h3>Реквизиты</h3>
                    <p>ООО "Торговые Системы"</p>
                    <p>ИНН 9705143827</p>
                    <p>ОГРН 1237700123456</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 TradeStorm. Все права защищены.</p>
                <a href="#">Политика конфиденциальности</a>
                <a href="#">Договор оферты</a>
            </div>
        </div>
    </footer>

    <?php
    // PHP код для динамических элементов
    $current_year = date('Y');
    echo "<script>console.log('TradeStorm - автоматизация маркетплейсов, текущий год: " . $current_year . "');</script>";
    
    // Пример функции для получения данных по API
    /*
    function getMarketplaceStats() {
        // Подключение к API маркетплейсов
        return [
            'total_orders' => 15000,
            'avg_rating' => 4.8,
            'revenue' => 25000000
        ];
    }
    */
    ?>
</body>

    <script type="text/javascript" src="script_video.js"></script>

</html>




