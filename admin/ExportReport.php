<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Config\Option; 

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

$prava = $APPLICATION->GetGroupRight("ko.exportimport");

if (!$prava >= "R") { // проверка уровн¤ доступа к модулю
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог
// установим заголовок страницы
$APPLICATION->SetTitle(GetMessage("KO_EXPORTIMPORT_TITLE"));

// языковые файлы
Loc::loadMessages(__FILE__); 

if (!\Bitrix\Main\Loader::includeModule('ko.exportimport')) {
	CAdminMessage::ShowMessage(GetMessage('KO_EXPORTIMPORT_ERROR_MODULE'));
	return false;
}

// создаем объект
try {
	try {
		$hlsVisual = \ko\Exportimport\Helper::GetAllHL();	
	} catch (SystemException $exception) {
		CAdminMessage::ShowMessage($exception->getMessage());
	}
} catch (SystemException $exception) {
	CAdminMessage::ShowMessage([
		'MESSAGE' => $exception->getMessage() . ' <a href="/bitrix/admin/module_admin.php?lang=ru">”правление модул¤ми</a>',
		'HTML' => true
		]);
	return false;
}

// визуальный вывод
$aTabs = array(
	array(
		'DIV' => 'export',
		'TAB' => Loc::getMessage('KO_EXPORTIMPORT_MENU_LOG'),
		'TITLE' => Loc::getMessage('KO_EXPORTIMPORT_MENU_LOG_TITLE')
	)
); 
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<div id="KO_exportimport_result_AJAX"></div>
<form name="form_tools" method="POST" action="/bitrix/admin/KO_exportimport_ajax.php" id="KO_exportimport_form">
	<input type="hidden" name="type" value="12">
	<input type="hidden" name="export_step_id" value="0" id="export_step_id">
	<input type="hidden" name="export_type" value="export_data" id="export_data">
	<input type="hidden" name="hlname" value="" id="hlname">
	<?=bitrix_sessid_post()?>
	<?
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	?>
	<tr class="heading">
		<td colspan="2"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_FILE_PATH')?></td>
	</tr>

	<tr>
		<td width="40%"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_HL')?>:</td>
		<td>
			<select id="hl_id" name="hl_id">
				<option value="0"></option>
				<?foreach ($hlsVisual as $row):?>
				<option value="<?= intval($row['ID'])?>" data-hlname="<?= htmlspecialcharsbx($row['NAME'])?>"><?= htmlspecialcharsbx($row['NAME_LANG'])?> [<?= $row['ID']?>]</option>
				<?endforeach;?>
			</select>
		</td>
	</tr>

	
	<tr>
		<td><label for="export_count_row"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_EXPORT_COUNT_ROW')?></label>:</td>
		<td>
			<input type="number" id="export_count_row" value="10000" name="export_count_row" checked="checked" min="1" />
		</td>
	</tr> 
	<tr>
		<td><label for="export_userentity"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_USERENTITY')?></label>:</td>
		<td id="export_userentity"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_O_SET_2')?></td>
	</tr>
	<tr>
		<td><label for="delimiter"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_DELIMITER')?></label>:</td>
		<td>
			<input type="text" id="delimiter" value=";" name="delimiter"  />
		</td>
	</tr>
	<tr>
		<td><label for="enclosure"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_ENCLOSURE')?></label>:</td>
		<td>
			<input type="text" id="enclosure" value='"' name="enclosure"  />
		</td>
	</tr>
	<tr>
		<td><label for="delimiter_m"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_DELIMITER_M')?></label>:</td>
		<td>
			<input type="text" id="delimiter_m" value='|' name="delimiter_m"  />
		</td>
	</tr>
	<tr>
		<td><label for="export_coding"><?= Loc::getMessage('KO_EXPORTIMPORT_EXPORT_CODING')?></label>:</td>
		<td>
			<select id="export_coding" name="export_coding">
				<option value="UTF-8">UTF-8</option>
				<option value="windows-1251">windows-1251(cp1251)</option>
			</select>
		</td>
	</tr>
	<?$tabControl->Buttons();?>
	<input type="submit" value="<?= Loc::getMessage('KO_EXPORTIMPORT_LOG_SAVE')?>" class="adm-btn-save" id="KO_exportimport_submit">
	<?$tabControl->End();?>
</form>
<?

// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");