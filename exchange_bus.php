<?php
/*
Plugin Name: ExchangeBus
Plugin URI: https://github.com/wolzogen/exchange_bus
Description: Шина синхронизации данных
Version: 2.0.2
Author: wolzogen
*/

add_action('admin_menu', 'exchange_bus_add_pages');

// Функция добавления страниц
function exchange_bus_add_pages()
{
    // Добавление меню главной страницы модуля:
    add_menu_page('ExchangeBus', 'ExchangeBus', 7, 'exchange_bus_module', 'module_page', 'dashicons-filter');
    // Добавление субменю страницы конфигурации модуля:
    add_submenu_page('exchange_bus_module', 'Configuration', 'Конфигурация', 8, 'exchange_bus_configuration', 'configuration_page');
}

// Функция контента для страниы exchange_bus_page
function module_page()
{
    echo "<h2>Шина синхронизации данных</h2>";
}

// Функция контента для страниы configuration_page
function configuration_page()
{
    echo "<h2>Конфигурация</h2>";
}
