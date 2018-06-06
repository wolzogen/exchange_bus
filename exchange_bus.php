<?php
/*
Plugin Name: exchange_bus
Plugin URI: https://github.com/wolzogen/exchange_bus
Description: Шина синхронизации данных
Version: 2.0.3
Author: wolzogen
*/

add_action('admin_menu', 'exchange_bus_add_pages');

// Функция добавления страниц
function exchange_bus_add_pages()
{
    // Добавление меню главной страницы модуля:
    add_menu_page('ExchangeBus', 'ExchangeBus', 7, 'exchange_bus_module', 'exchange_bus_module_page', 'dashicons-filter');
    // Добавление субменю страницы импорта csv файла:
    add_submenu_page('exchange_bus_module', 'CSV Standard Import', 'Standard Import', 1, 'exchange_bus_csv_standard_page', 'exchange_bus_csv_standard_page');
    // Добавление субменю страницы импорта csv файла:
    add_submenu_page('exchange_bus_module', 'CSV Extended Import', 'Extended Import', 2, 'exchange_bus_csv_extended_page', 'exchange_bus_csv_extended_page');
}

// Функция контента для страницы exchange_bus_module_page
function exchange_bus_module_page()
{
    require_once 'template/module_page.php';
}

// Функция контента для страницы exchange_bus_csv_standard_page
function exchange_bus_csv_standard_page()
{
    echo "<h2>CSV Standard Import</h2>";

    try {
        $stdClass = CsvImport::getFileStd(CsvStandardImport::FILENAME);

        if (!$stdClass) {
            $message = CsvImport::getFileNotFoundInformation(CsvStandardImport::FILENAME);
            throw new LogicException($message);
        }

        $class = new CsvStandardImport;
        $class->load($stdClass);
        /** @see exchange_bus/template/csv_standard_page.php */
        $history = $class->synchronization();

        require_once 'template/csv_standard_page.php';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

// Функция контента для страницы exchange_bus_csv_extended_page
function exchange_bus_csv_extended_page()
{
    echo "<h2>CSV Extended Import</h2>";

    try {
        $stdClass = CsvImport::getFileStd(CsvExtendedImport::FILENAME);

        if (!$stdClass) {
            $message = CsvImport::getFileNotFoundInformation(CsvStandardImport::FILENAME);
            throw new LogicException($message);
        }
        $class = new CsvExtendedImport;
        $warehouses = $class->getWarehouses($stdClass);

        require_once 'template/csv_extended_page.php';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

/**
 * Class ExchangeBusHelper
 */
class ExchangeBusHelper
{
    /**
     * @param string $page
     * @return string
     */
    public static function addActionButton($page)
    {
        return '<a href="/wp-admin/admin.php?page=' . $page . '" class="button button-primary" style="margin-top: 10px;">' . __("Импортировать") . '</a>';
    }
}

/**
 * Interface CsvImportInterface
 */
interface CsvImportInterface
{
    /**
     * @param string $filename
     * @return mixed
     */
    public static function getFileInformation($filename = '');

    /**
     * @param stdClass $stdClass
     * @return mixed
     */
    public function load($stdClass);
}

/**
 * Class CsvImport
 */
abstract class CsvImport implements CsvImportInterface
{
    protected $csvContent = null;
    protected $csvCount = null;

    /**
     * @param stdClass $stdClass
     * @return mixed|void
     */
    public function load($stdClass)
    {
        $this->csvContent = explode(PHP_EOL, file_get_contents($stdClass->filepath));
        $this->csvCount = count($this->csvContent);
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getFileInformation($filename = '')
    {
        $stdClass = CsvImport::getFileStd($filename);
        return $stdClass ?
            CsvImport::getFileFoundInformation($stdClass) :
            CsvImport::getFileNotFoundInformation($filename);
    }

    /**
     * @param stdClass $fileStd
     * @return string
     */
    public static function getFileFoundInformation($fileStd)
    {
        $createdAt = date("Y-d-m H:i:s", filemtime($fileStd->filepath));
        return $content = '<h4>Файл ' . $fileStd->filename . ' найден</h4><div>Дата создания файла: ' . $createdAt . '</div>';
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getFileNotFoundInformation($filename)
    {
        return '<h4>Файл ' . $filename . ' не найден</h4>';
    }

    /**
     * @param string $filename
     * @return stdClass
     */
    public static function getFileStd($filename)
    {
        $fileStd = new stdClass;
        $filepath = get_home_path() . $filename;

        if (!file_exists($filepath)) {
            return null;
        }

        $fileStd->filepath = $filepath;
        $fileStd->filename = $filename;

        return $fileStd;
    }
}

/**
 * Class CsvStandardImport
 */
class CsvStandardImport extends CsvImport implements CsvImportInterface
{
    const FILENAME = 'csv_standard.csv';

    /**
     * @param stdClass $stdClass
     * @return mixed|void
     */
    public function load($stdClass)
    {
        parent::load($stdClass);
    }

    /**
     * @return array
     */
    public function synchronization()
    {
        global $wpdb;

        $history = [];

        for ($i = 0; $i < $this->csvCount; $i++) {
            // Пропускаем итерацию, если строка является заголовком или пустой строкой
            if ($i === 0 || empty($this->csvContent[$i]))
                continue;

            $line = array_combine(['name', 'sku', 'stock', 'price'], str_getcsv($this->csvContent[$i], ';'));
            $sqlPrepare = $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
                '_sku', $line['sku']
            );
            $postmetaSkuResults = $wpdb->get_results($sqlPrepare);

            // Пропускаем итерацию, если количество записей с одинаковым _sku в поле meta_value более, чем 1
            if (empty($postmetaSkuResults) || count($postmetaSkuResults) !== 1)
                continue;

            // Устанавливаем указатель на первый элемент массива
            $postmetaSku = reset($postmetaSkuResults);

            $sqlPrepare = $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key in(%s, %s)",
                $postmetaSku->post_id, '_stock', '_price'
            );
            $postmetaProductResults = $wpdb->get_results($sqlPrepare);

            // Пропускаем итерацию, если не найдены записи текущего поста со значениями в meta_key = '_stock' и '_price'
            if (empty($postmetaProductResults) || count($postmetaProductResults) !== 2)
                continue;

            foreach ($postmetaProductResults as $postmetaProduct) {
                switch ($postmetaProduct->meta_key) {
                    case '_price':
                        // Форматирование стоимости по правилу кратности
                        $price = (int)preg_replace('/[^0-9]/', '', $line['price']);
                        $line['price'] = $price % 50 === 0 ?
                            $price : ($price + (50 - $price % 50));

                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaProduct->meta_value == $line['price'])
                            break;

                        $wpdb->update($wpdb->postmeta,
                            ['meta_value' => $line['price']],
                            ['post_id' => $postmetaProduct->post_id, 'meta_key' => $postmetaProduct->meta_key]
                        );
                        $history[$postmetaProduct->meta_id] = $this->_insertToHistory($line, $postmetaProduct);
                        break;
                    case '_stock':
                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaProduct->meta_value == $line['stock'])
                            break;

                        $wpdb->update($wpdb->postmeta,
                            ['meta_value' => $line['stock']],
                            ['post_id' => $postmetaProduct->post_id, 'meta_key' => $postmetaProduct->meta_key]
                        );
                        $history[$postmetaProduct->meta_id] = $this->_insertToHistory($line, $postmetaProduct);
                        break;
                }
            }
        }

        return $history;
    }

    /**
     * @param array $line
     * @param $postmetaProduct
     * @return array
     */
    private function _insertToHistory($line, $postmetaProduct)
    {
        $metaValueBefore = $line[str_replace('_', '', $postmetaProduct->meta_key)];
        return [
            'name' => $line['name'], 'sku' => $line['name'],
            'meta_key' => $postmetaProduct->meta_key,
            'meta_value_after' => $postmetaProduct->meta_value,
            'meta_value_before' => $metaValueBefore,
        ];
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getFileInformation($filename = '')
    {
        $filename = $filename ?: self::FILENAME;
        return parent::getFileInformation($filename);
    }
}

/**
 * Class CsvExtendedImport
 */
class CsvExtendedImport extends CsvImport implements CsvImportInterface
{
    const FILENAME = 'csv_extended.csv';

    /**
     * @param stdClass $stdClass
     */
    public function load($stdClass)
    {
        parent::load($stdClass);
    }

    /**
     * @param stdClass $stdClass
     */
    public function getWarehouses($stdClass)
    {
        $headers = $this->_getHeadersString($stdClass->filepath);
    }

    /**
     * @param string $filepath
     * @return array
     */
    private function _getHeadersString($filepath)
    {
        $file = fopen($filepath, 'r');
        $headers = [];
        $counter = 0;

        while (!feof($file)) {
            if ($counter === 1)
                break;
            $headers = fgetcsv($file);
            ++$counter;
        }

        fclose($file);

        return $headers;
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getFileInformation($filename = '')
    {
        $filename = $filename ?: self::FILENAME;
        return parent::getFileInformation($filename);
    }
}