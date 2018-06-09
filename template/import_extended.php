<?php
/**
 * @var ExtendedCsvImport $extendedCsvImport
 * @var array $operations
 */
?>

<h2>CSV Extended Import</h2>
<?= $extendedCsvImport->getCharacterization() ?>

<h2>Выберите синхронизируемые склады</h2>
<form action="/wp-admin/admin.php?page=exchange_bus_csv_extended_page" method="post">
    <?php foreach(ExtendedCsvImport::getWarehouses() as $key => $value) : ?>

    <?php endforeach; ?>
</form>