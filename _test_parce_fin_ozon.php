<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

function getOzonPageWithSelenium($url) {
    // Настройка Selenium WebDriver
    $host = 'http://localhost:4444/wd/hub'; // или удаленный сервер
    $capabilities = DesiredCapabilities::chrome();
    
    $driver = RemoteWebDriver::create($host, $capabilities);
    
    try {
        // Переходим на страницу
        $driver->get($url);
        
        // Ждем загрузки
        sleep(5);
        
        // Получаем HTML
        $html = $driver->getPageSource();
        
        $driver->quit();
        
        return $html;
    } catch (Exception $e) {
        $driver->quit();
        echo "Ошибка Selenium: " . $e->getMessage() . "\n";
        return false;
    }
}

// Использование
$url = 'https://seller.ozon.ru/app/finances/accruals?search=0142062678-0042-1&tab=ACCRUALS_DETAILS';
$html = getOzonPageWithSelenium($url);
?>