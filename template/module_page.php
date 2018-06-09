<?php
/**
 * @var StandardCsvImport $standardCsvImport
 * @var ExtendedCsvImport $extendedCsvImport
 */
?>

<div>
    <h2>Шина синхронизации данных</h2>
</div>

<?= $standardCsvImport->getCharacterization() ?>
<div style="margin-top: 15px;">
    <?=  ExchangeBusHelper::addActionButton(StandardCsvImport::CUR_PAGE) ?>
</div>

<?= $extendedCsvImport->getCharacterization() ?>
<div style="margin-top: 15px;">
    <?=  ExchangeBusHelper::addActionButton(ExtendedCsvImport::CUR_PAGE) ?>
</div>