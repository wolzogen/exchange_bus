<?php
/**
 * @var boolean $synchronization
 * @var ExtendedCsvImport $extendedCsvImport
 * @var array $operations
 */
?>

<h2>CSV Extended Import</h2>
<?= $extendedCsvImport->getCharacterization() ?>

<!-- Выбор склада или Синхронизация -->
<?php if ($synchronization) : ?>
    <h2>Результат импортирования</h2>

<?php else : ?>
    <h2>Выберите синхронизируемые склады</h2>
    <form action="<?= ExchangeBusHelper::WP_ADMIN_URL . ExtendedCsvImport::CUR_PAGE ?>" method="post">
    <form action="/wp-admin/admin.php?page=exchange_bus_csv_extended_page" method="post">
        <?php foreach (ExtendedCsvImport::getWarehouses() as $key => $value) : ?>
            <div>
                <input type="checkbox" id="warehouse_<?= $key ?>" name="warehouses[<?= $key ?>]" value="<?= $key ?>"/>
                <label for="warehouse_<?= $key ?>"><?= $value ?></label>
            </div>
        <?php endforeach; ?>
        <div style="margin-top: 15px;">
            <input type="submit" class="button button-primary" value="Синхронизация" />
        </div>
    </form>
<?php endif; ?>
