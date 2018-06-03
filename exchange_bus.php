<?php
/*
Plugin Name: exchange_bus
Plugin URI: https://github.com/wolzogen/exchange_bus
Description: Шина синхронизации данных
Version: 2.0.3
Author: wolzogen
*/

// Установление константы с названием файла для синхронизации
if (!defined('EXCHANGE_BUS_CSV_FILE'))
    define('EXCHANGE_BUS_CSV_FILE', 'catalog.csv');
// Определяем константу имени товара
if (!defined('CSV_NAME'))
    define('CSV_NAME', 'name');
// Определяем константу артикула товара
if (!defined('CSV_SKU'))
    define('CSV_SKU', 'sku');
// Определяем константу количества товара на складе
if (!defined('CSV_STOCK'))
    define('CSV_STOCK', 'stock');
// Определяем константу цены товара
if (!defined('CSV_PRICE'))
    define('CSV_PRICE', 'price');

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
        outputFileDetails(getFileDescriptor(EXCHANGE_BUS_CSV_FILE));
        echo '<br/><a href="/wp-admin/admin.php?page=exchange_bus_csv" class="button button-primary" style="margin-top: 10px;">' . __("Импортировать") . '</a>';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

/**
 * Формирование файлового дескриптора
 *
 * @param string $filename
 * @return stdClass
 */
function getFileDescriptor($filename)
{
    $fileDescriptor = new stdClass;
    $filepath = get_home_path() . $filename;

    if (!file_exists($filepath)) {
        throw new LogicException('<h4>Файл ' . $filename . ' не найден</h4>');
    }

    $fileDescriptor->filepath = $filepath;
    $fileDescriptor->filename = $filename;

    return $fileDescriptor;
}

// Функция контента для страниы exchange_bus_csv_page
function exchange_bus_csv_page()
{
    echo "<h2>CSV Import</h2>";
    try {
        $fileDescriptor = getFileDescriptor(EXCHANGE_BUS_CSV_FILE);
        outputFileDetails($fileDescriptor);
        importCsv($fileDescriptor->filepath);
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

// Функция контента для страниы exchange_bus_xls_page
function exchange_bus_xls_page()
{
    echo "<h2>XLS Import</h2>";
}

/**
 * Функция отображения информации о файле
 *
 * @param stdClass $fileDescriptor
 */
function outputFileDetails($fileDescriptor)
{
    echo '<h4>Файл ' . $fileDescriptor->filename . ' найден</h4>';
    echo 'Дата создания файла: ' . date("Y-d-m H:i:s", filemtime($fileDescriptor->filepath));
}

/**
 * @param string $filepath
 */
function importCsv($filepath)
{
    global $wpdb;

    $csv = explode(PHP_EOL, file_get_contents($filepath));
    $csvSize = count($csv);

    for ($i = 0; $i < $csvSize; $i++) {
        // Пропускаем итерацию, если строка является заголовком или пустой строкой
        if ($i === 0 || empty($csv[0])) {
            continue;
        }

        $csvLine = array_combine([CSV_NAME, CSV_SKU, CSV_STOCK, CSV_PRICE], str_getcsv($csv[$i], ';'));

        $sqlPrepare = $wpdb->prepare(
            "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
            '_sku', $csvLine[CSV_SKU]
        );
        $postmetaSkuResults = $wpdb->get_results($sqlPrepare);

        // Пропускаем итерацию, если количество записей с одинаковым _sku в поле meta_value более, чем 1
        if (empty($postmetaSkuResults) || count($postmetaSkuResults) !== 1) {
            continue;
        }

        // Устанавливаем указатель на первый элемент массива
        $postmetaSku = reset($postmetaSkuResults);

        $sqlPrepare = $wpdb->prepare(
            "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key in(%s, %s)",
            $postmetaSku->post_id, '_stock', '_price'
        );
        $postmetaProductResults = $wpdb->get_results($sqlPrepare);

        // Пропускаем итерацию, если не найдены записи текущего поста со значениями в meta_key = '_stock' и '_price'
        if (empty($postmetaProductResults) || count($postmetaProductResults) !== 2) {
            continue;
        }

        foreach ($postmetaProductResults as $postmetaProduct) {
            switch ($postmetaProduct->meta_key) {
                case '_price':
                    $price = (int)preg_replace('/[^0-9]/', '', $csvLine[CSV_PRICE]);
                    $wpdb->update($wpdb->postmeta,
                        ['meta_value' => $price], ['post_id' => $postmetaProduct->post_id, 'meta_key' => $postmetaProduct->meta_key]
                    );
                    break;
                case '_stock':
                    $wpdb->update($wpdb->postmeta,
                        ['meta_value' => $csvLine[CSV_STOCK]], ['post_id' => $postmetaProduct->post_id, 'meta_key' => $postmetaProduct->meta_key]
                    );
                    break;
            }
        }
    }
}