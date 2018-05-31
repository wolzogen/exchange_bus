<?php
/*
Plugin Name: ExchangeBus
Plugin URI: https://github.com/wolzogen/ExchangeBus
Description: Шина синхронизации данных
Version: 2.0.0
Author: wolzogen
*/

add_action('admin_menu', 'exchange_bus_add_pages');

// Функция добавления страниц
function exchange_bus_add_pages()
{
    // Добавление меню главногой страницы модуля:
    add_menu_page('ExchangeBus', 'ExchangeBus', 7, __FILE__, 'exchange_bus_page', 'dashicons-filter');
    // Добавление субменю страницы конфигурации модуля:
    add_submenu_page(__FILE__, 'Configuration', 'Конфигурация', 8, 'exchange_bus_configuration', 'configuration_page');
}

// Функция контента для страниы exchange_bus_page
function exchange_bus_page()
{
    echo "<h2>Шина синхронизации данных</h2>";
}

// Функция контента для страниы configuration_page
function configuration_page()
{
    echo "<h2>Конфигурация</h2>";
}
