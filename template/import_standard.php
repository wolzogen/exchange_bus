<?php
/**
 * @var StandardCsvImport $standardCsvImport
 * @var array $operations
 */
?>

<?= $standardCsvImport->getCharacterization() ?>

<h2>Результат импортирования</h2>
<?php
$operations = $standardCsvImport->synchronization();
if (empty($operations)) : ?>
    <h4>Обновление не требуется</h4>
<?php else : ?>
    <div style="margin-top: 10px;">
        <table style="text-align: center; width: 100%; border-style: double;">
            <tr>
                <th>ID</th>
                <th>Наименование</th>
                <th>Артикул</th>
                <th>Ключ</th>
                <th>Значение до</th>
                <th>Значение после</th>
            </tr>
        <?php foreach ($operations as $key => $value) : ?>
            <tr>
                <td style="width: 10%"><?= $key ?></td>
                <td style="width: 20%; text-align: left;"><?= $value['name'] ?></td>
                <td style="width: 20%"><?= $value['sku'] ?></td>
                <td style="width: 10%"><?= $value['key'] ?></td>
                <td style="width: 20%; color: #cd5c51;"><b><?= $value['old'] ?></b></td>
                <td style="width: 20%; color: #28cd89;"><b><?= $value['new'] ?></b></td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>