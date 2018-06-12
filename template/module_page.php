<?php
/**
 * @var array $objects
 */
?>

<?php foreach ($objects as $object) : ?>
    <?php if ($object['error']) :
        echo $object['message'];
    else :
        /** @var CsvImport $object */
        $object = $object['object'];
        // Получаем описание файла
        echo $object->getCharacterization();
        // Формируем кнопку "Синхронизация"
        ?>
        <div style="margin-top: 15px;">
            <?=  ExchangeBusHelper::addActionButton(constant(get_class($object).'::CUR_PAGE')) ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
