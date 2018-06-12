<?php
/*
Plugin Name: exchange_bus
Plugin URI: https://github.com/wolzogen/exchange_bus
Description: Шина синхронизации данных
Version: 3.1.1
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
    echo <<<HTML
<div>
    <h2>Шина синхронизации данных</h2>
</div>
HTML;
    try {
        /** @see template/standard_csv_import.php */
        $standardCsvImport = new StandardCsvImport;
        $extendedCsvImport = new ExtendedCsvImport;
        require_once 'template/module_page.php';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

// Функция контента для страницы exchange_bus_csv_standard_page
function exchange_bus_csv_standard_page()
{
    echo '<h2>CSV Standard Import</h2>';
    try {
        /** @see template/import_standard.php */
        $standardCsvImport = new StandardCsvImport;
        // Загружаем в память файл для последующей синхронизации
        $standardCsvImport->load();
        require_once 'template/import_standard.php';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

// Функция контента для страницы exchange_bus_csv_extended_page
function exchange_bus_csv_extended_page()
{
    echo '<h2>CSV Extended Import</h2>';
    $synchronization = false;
    try {
        /** @see template/import_extended.php */
        $extendedCsvImport = new ExtendedCsvImport;
        // Проверяем режим синхронизации через параметр с формы
        if (isset($_POST['warehouses']) && !empty($_POST['warehouses'])) {
            $synchronization = true;
            // Загружаем в память файл для последующей синхронизации
            $extendedCsvImport->load();
        }
        require_once 'template/import_extended.php';
    } catch (LogicException $e) {
        echo $e->getMessage();
    }
}

/**
 * Class ExchangeBusHelper
 */
class ExchangeBusHelper
{
    const WP_ADMIN_URL = '/wp-admin/admin.php?page=';

    /**
     * Получение сформированной кнопки
     *
     * @param string $page
     * @return string
     */
    public static function addActionButton($page)
    {
        return '<a href="' . self::WP_ADMIN_URL . $page . '" class="button button-primary">' . __("Синхронизировать") . '</a>';
    }
}

/**
 * Interface CsvImportInterface
 */
interface CsvImportInterface
{
    /**
     * Получения информации о файле
     *
     * @return string
     */
    public function getCharacterization();

    /**
     * Загрузка csv-файла
     *
     * @throws LogicException
     * @return mixed
     */
    public function load();

    /**
     * Синхронизация данных
     *
     * @return array
     */
    public function synchronization();
}

/**
 * Class CsvImport
 */
abstract class CsvImport implements CsvImportInterface
{
    /**
     * @var array
     */
    protected $content = [];

    public function __construct()
    {
        // Если не существует текущего файла, то выбрасываем LogicException
        if (!file_exists(get_home_path() . static::FILENAME)) {
            throw new LogicException(sprintf('<h4>Файл %s не найден</h4>', static::FILENAME));
        }
    }

    /**
     * @return mixed|void
     */
    public function load()
    {
        // Получаем данные из файла и разбиваем их в массив по разделителю PHP_EOL
        $this->content = explode(PHP_EOL, file_get_contents(get_home_path() . static::FILENAME));
    }

    /**
     * Получение информации о файле
     *
     * @return string
     */
    public function getCharacterization()
    {
        $filepath = get_home_path() . static::FILENAME;
        $content = '<h4>Файл ' . static::FILENAME . ' найден</h4>';
        $content .= '<div>Последнее изменение: ' . date("Y-m-d H:i:s", filemtime($filepath)) . '</div>';

        return $content;
    }

    /**
     * Получение записи по Stock Keeping Unit
     *
     * @param string $sku
     * @return bool|mixed
     */
    protected function getPostmetaBySku($sku)
    {
        global $wpdb;
        // Подготавливаем и выполняем запрос, в котором ищем записи по полю _sku
        $sqlPrepare = $wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d", '_sku', $sku);
        $postmetaBySkuResults = $wpdb->get_results($sqlPrepare);
        // На уровне базы не гарантируется уникальность записи по sku
        if (count($postmetaBySkuResults) !== 1)
            return false;
        // Получаем первый элемент массива
        return array_shift($postmetaBySkuResults);
    }

    /**
     * Получение записи по post_id, где meta_value содержит _stock и _price
     *
     * @param $postId
     * @return array|bool|null|object
     */
    protected function getPostmetaById($postId)
    {
        global $wpdb;
        // Подготавливаем и выполняем запрос, в котором ищем записи по полю post_id и выбираем записи, где meta_value содержит _stock и _price
        $sqlPrepare = $wpdb->prepare(
            "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key in(%s, %s)",
            $postId, '_stock', '_price'
        );
        $postmetaStockAndPriceResults = $wpdb->get_results($sqlPrepare);
        // Пропускаем итерацию, если не найдены записи текущего поста со значениями в meta_key = '_stock' и '_price'
        if (count($postmetaStockAndPriceResults) !== 2)
            return false;

        return $postmetaStockAndPriceResults;
    }

    /**
     * Правило формирования стоимости
     *
     * @param integer $price
     * @return int
     */
    protected function applyPriceRule($price)
    {
        $price = (int)preg_replace('/[^0-9]/', '', $price);
        return $price % 50 === 0 ? $price : ($price + (50 - $price % 50));
    }
}

/**
 * Class StandardCsvImport
 */
class StandardCsvImport extends CsvImport
{
    const FILENAME = 'csv_standard.csv';
    const CUR_PAGE = 'exchange_bus_csv_standard_page';

    /**
     * Синхронизация данных
     *
     * @return array
     */
    public function synchronization()
    {
        $operations = [];
        // Зараннее просчитываем количество элементов в массиве, что бы не пересчитывать каждую итерацию в цикле
        $count = count($this->content);

        for ($i = 0; $i < $count; $i++) {
            // Пропускаем заголовок и пустые строки
            if ($i === 0 || empty($this->content[$i]))
                continue;
            // Формируем массив с ключами для более удобного обращения к массиву
            $line = array_combine(['name', 'sku', 'stock', 'price'], str_getcsv($this->content[$i], ';'));
            // Получение записи по Stock Keeping Unit
            if (!$postmetaSku = $this->getPostmetaBySku($line['sku']))
                continue;
            // Получение записи по post_id, где meta_value содержит _stock и _price
            if (!$postmetaStockAndPriceResults = $this->getPostmetaById($postmetaSku->post_id))
                continue;
            foreach ($postmetaStockAndPriceResults as $postmetaResult) {
                switch ($postmetaResult->meta_key) {
                    case '_price':
                        // Форматирование стоимости по правилу кратности
                        $line['price'] = $this->applyPriceRule($line['price']);
                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaResult->meta_value == $line['price'])
                            break;
                        // Обновляем запись в wp_postmeta
                        self::_updateRecord($line, $postmetaResult, 'price');
                        // Формируем операцию
                        $operations[$postmetaResult->meta_id] = self::_collectOperation($line, $postmetaResult);
                        break;
                    case '_stock':
                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaResult->meta_value == $line['stock'])
                            break;
                        // Обновляем запись в wp_postmeta
                        self::_updateRecord($line, $postmetaResult, 'stock');
                        // Формируем операцию
                        $operations[$postmetaResult->meta_id] = self::_collectOperation($line, $postmetaResult);
                        break;
                }
            }
        }
        return $operations;
    }

    /**
     * Обновление записи в wp_postmeta
     *
     * @param array $line
     * @param $postmetaResult
     * @param string $metaValue
     */
    private static function _updateRecord($line, $postmetaResult, $metaValue)
    {
        // Получение данных и условий для SQL-запроса
        $data = ['meta_value' => $line[$metaValue]];
        $where = ['post_id' => $postmetaResult->post_id, 'meta_key' => $postmetaResult->meta_key];

        global $wpdb;

        $wpdb->update($wpdb->postmeta, $data, $where);
    }

    /**
     * Формирование операции
     *
     * @param array $line
     * @param $postmetaResult
     * @return array
     */
    private static function _collectOperation($line, $postmetaResult)
    {
        return [
            'name' => $line['name'],
            'sku' => $line['sku'],
            'key' => $postmetaResult->meta_key,
            'old' => $postmetaResult->meta_value,
            'new' => $line[str_replace('_', '', $postmetaResult->meta_key)],
        ];
    }
}

/**
 * Class ExtendedCsvImport
 */
class ExtendedCsvImport extends CsvImport
{
    const FILENAME = 'csv_extended.csv';
    const CUR_PAGE = 'exchange_bus_csv_extended_page';

    public $warehouses = [];
    // Динмачиская позиция "код склада"
    private $_posSku = null;
    // Динамическая позиция "наименование"
    private $_posName = null;
    // Динамическая позиция "цена розница"
    private $_posPrice = null;


    // Получение списка складов из первой строки, разбитой в массив, по ключевому слову - "склад"
    public function getWarehouses($headers = null)
    {
        $headers = $headers
            ?: self::_getHeaders(get_home_path() . self::FILENAME);

        foreach ($headers as $key => $value) {
            if (preg_match('/склад$|склад[ ]/ui', $value))
                $this->warehouses[$key] = $value;
            // Определение позиции "код склада"
            if ($value === 'код склада')
                $this->_posSku = $key;
            // Определение позиции "наименование"
            if ($value === 'наименование')
                $this->_posName = $key;
            // Определение позиции "цена розница"
            if ($value === 'цена розница')
                $this->_posPrice = $key;
        }
        return $this->warehouses;
    }

    /**
     * Получение первой строки файла
     *
     * @param string $filepath
     * @return array
     */
    private static function _getHeaders($filepath)
    {
        $headers = [];
        // Если файл отсутствует, то возвращаем пустой массив
        if (!$file = fopen($filepath, 'r'))
            return $headers;
        $counter = 0;
        // Получаем первую строку и прерываем исполнение
        while (!feof($file)) {
            if ($counter++ === 1)
                break;
            $headers = fgetcsv($file);
        }
        // Закрываем файл и возвращаем результат выполнения
        fclose($file);
        return $headers;
    }

    /**
     * Синхронизация данных
     *
     * @return array
     */
    public function synchronization()
    {
        $operations = [];
        // Зараннее просчитываем количество элементов в массиве, что бы не пересчитывать каждую итерацию в цикле
        $count = count($this->content);

        for ($i = 0; $i < $count; $i++) {
            // Формируем массив с ключами для более удобного обращения к массиву
            $line = str_getcsv($this->content[$i]);
            // Получаем все склады из заголовка один раз при чтении заголовка, а после пропускаем итерацию
            if ($i === 0) {
                $this->getWarehouses($line);
                continue;
            }
            // Пропускаем пустые строки или если не получили запись по Stock Keeping Unit
            if (empty($this->content[$i]) || !$postmetaSku = $this->getPostmetaBySku($line[$this->_posSku]))
                continue;
            // Получение записи по post_id, где meta_value содержит _stock и _price
            if (!$postmetaStockAndPriceResults = $this->getPostmetaById($postmetaSku->post_id))
                continue;
            foreach ($postmetaStockAndPriceResults as $postmetaResult) {
                switch ($postmetaResult->meta_key) {
                    case '_price':
                        // Форматирование стоимости по правилу кратности
                        $line[11] = $this->applyPriceRule($line[$this->_posPrice]);
                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaResult->meta_value == $line[$this->_posPrice])
                            break;
                        // Обновляем запись в wp_postmeta
                        self::_updateRecord($postmetaResult, $line[$this->_posPrice]);
                        // Формируем операцию
                        $operations[$postmetaResult->meta_id] = self::_collectOperation($line, $postmetaResult);
                        break;
                    case '_stock':
                        // Получение суммы единиц товаров относительно выбранных складов
                        $stock = 0;
                        foreach ($_POST['warehouses'] as $position) {
                            // Пропускаем итерацию, если позиция склада отсутствует в первой строке загруженного в память файла
                            if (!isset($line[$position]))
                                continue;
                            $stock += $line[$position];
                        }
                        // Не обновлять запись, если значения до и после равны
                        if ($postmetaResult->meta_value == $stock)
                            break;
                        // Обновляем запись в wp_postmeta
                        self::_updateRecord($postmetaResult, $stock);
                        // Формируем операцию
                        $operations[$postmetaResult->meta_id] = $this->_collectOperation($line, $postmetaResult, $stock);
                        break;
                }
            }
        }
        return $operations;
    }

    /**
     * Обновление записи в wp_postmeta
     *
     * @param $postmetaResult
     * @param string $metaValue
     */
    private static function _updateRecord($postmetaResult, $metaValue)
    {
        // Получение данных и условий для SQL-запроса
        $data = ['meta_value' => $metaValue];
        $where = ['post_id' => $postmetaResult->post_id, 'meta_key' => $postmetaResult->meta_key];

        global $wpdb;

        $wpdb->update($wpdb->postmeta, $data, $where);
    }

    /**
     * Формирование операции
     *
     * @param array $line
     * @param stdClass $postmetaResult
     * @param integer $value
     * @return array
     */
    private function _collectOperation($line, $postmetaResult, $value = null)
    {
        return [
            'name' => $line[$this->_posName],
            'sku' => $line[$this->_posSku],
            'key' => $postmetaResult->meta_key,
            'old' => $postmetaResult->meta_value,
            // Если value существует, то передается stock, наче обращаемся к розничной цене, которая уже изменена в массиве line
            'new' => $value ?: $line[$this->_posPrice],
        ];
    }
}