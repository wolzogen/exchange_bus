<? /** @var array $result */ ?>

<h2>Результат импортирования</h2>

<?php if (empty($result)) : ?>
    <h4>Обновление не требуется</h4>
<?php else : ?>
    <div style="margin-top: 10px; border-style: dotted;">
        <table style="text-align: center">
            <tr>
                <th>ID</th>
                <th>Наименование</th>
                <th>Артикул</th>
                <th>meta_key</th>
                <th>meta_value_after</th>
                <th>meta_value_before</th>
            </tr>
            <?php foreach ($result as $key => $value) : ?>
                <tr>
                    <td style="width: 10%"><?= $key ?></td>
                    <td style="width: 20%"><?= $value[CSV_NAME] ?></td>
                    <td style="width: 20%"><?= $value[CSV_SKU] ?></td>
                    <td style="width: 10%"><?= $value['meta_key'] ?></td>
                    <td style="width: 20%; color: #CD5C5C;"><b><?= $value['meta_value_after'] ?></b></td>
                    <td style="width: 20%; color: #3BCD78;"><b><?= $value['meta_value_before'] ?></b></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>