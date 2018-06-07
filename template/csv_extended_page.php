<? /** @var array $warehouses */ ?>

<?= CsvImport::getFileInformation(CsvExtendedImport::FILENAME) ?>

<h2>Выберите синхронизируемые склады</h2>
<form action="/wp-admin/admin.php?page=exchange_bus_csv_extended_page" method="post">
    <?php foreach ($warehouses as $key => $value) : ?>
            <input type="checkbox" id="<?= $key ?>" name="<?= $key ?>" value="<?= $key ?>" title="w">
            <label for="<?= $key ?>"><?=  $value ?></label>
    <?php endforeach; ?>
    <div style="margin-top: 10px;">
        <button type="submit" class="button button-primary">Обновить</button>
    </div>
</form>