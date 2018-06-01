<?php
/*
Plugin Name: ExchangeBus
Plugin URI: https://github.com/wolzogen/exchange_bus
Description: Шина синхронизации данных
Version: 2.0.3
Author: wolzogen
*/

// Установление константы с названием файла для синхронизации
if (!defined('EXCHANGE_BUS_CSV_FILE'))
    define('EXCHANGE_BUS_CSV_FILE', 'catalog.csv');

add_action('admin_menu', 'exchange_bus_add_pages');

// Функция добавления страниц
function exchange_bus_add_pages()
{
    // Добавление меню главной страницы модуля:
    add_menu_page('ExchangeBus', 'ExchangeBus', 7, 'exchange_bus_module', 'exchange_bus_module_page', 'dashicons-filter');
    // Добавление субменю страницы импорта csv файла:
    add_submenu_page('exchange_bus_module', 'CSV Import', 'CSV Import', 1, 'exchange_bus_csv', 'exchange_bus_csv_page');
    // Добавление субменю страницы импорта xls файла:
    add_submenu_page('exchange_bus_module', 'XLS Import', 'XLS Import', 2, 'exchange_bus_xls', 'exchange_bus_xls_page');
}

// Функция контента для страниы exchange_bus_module_page
function exchange_bus_module_page()
{
    echo "<h2>Шина синхронизации данных</h2>";

    try {
        outputFileDetails(EXCHANGE_BUS_CSV_FILE);
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

/**
 * Информация о файле
 *
 * @param string $filename
 * @throws LogicException
 */
function outputFileDetails($filename)
{
    $filepath = get_home_path() . $filename;

    if (!file_exists($filepath)) {
        throw new LogicException('<h4>Файл ' . $filename . ' не найден</h4>');
    }

    echo '<h4>Файл ' . $filename . ' найден</h4>';
    echo 'Дата создания файла: ' . date("Y-d-m H:i:s.", filemtime($filepath));
}

// Функция контента для страниы exchange_bus_csv_page
function exchange_bus_csv_page()
{
    echo "<h2>CSV Import</h2>";
}

// Функция контента для страниы exchange_bus_xls_page
function exchange_bus_xls_page()
{
    echo "<h2>XLS Import</h2>";
}

