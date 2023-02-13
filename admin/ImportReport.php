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
		'TAB' => Loc::getMessage('KO_EXPORTIMPORT_TITLE_IMPORT'),
		'TITLE' => Loc::getMessage('KO_EXPORTIMPORT_TITLE_IMPORT')
	)
);
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<div id="KO_exportimport_result_AJAX"></div>
<form name="form_tools" method="POST" action="/bitrix/admin/KO_exportimport_ajax.php" id="KO_exportimport_form">
	<input type="hidden" name="type" value="13">
	<input type="hidden" name="export_step_id" value="0" id="export_step_id">
	<input type="hidden" name="import_error_count" value="0" id="import_error_count">
	<input type="hidden" name="export_type" value="export_data">
	<input type="hidden" name="hl_id_data" value="0" id="hl_id_data">
	<?=bitrix_sessid_post()?>
	<?
	$tabControl->Begin();
	$tabControl->BeginNextTab(); 
	?>
	<tr class="heading">
		<td colspan="2"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_O_SET')?></td>
	</tr>
	<tr>
		<td width="40%"><?= Loc::getMessage('KO_EXPORTIMPORT_FIELD_IMPORT_FILE')?>:<sup><span class="required">1</span></sup></td>
		<td>
			<!-- <input type="text" id="url_data_file_exp" size="30" value="/upload/Test_log.csv" name="url_data_file" /> -->
			<input type="text" id="url_data_file_exp" size="30" value="" name="url_data_file" />
			<input type="button" value="..." OnClick="BtnClick()">
			<?
			CAdminFileDialog::ShowScript
			(
				Array(
					'event' => 'BtnClick',
					'arResultDest' => array('FORM_NAME' => 'form_tools', 'FORM_ELEMENT_NAME' => 'url_data_file_exp'),
					'arPath' => array('SITE' => SITE_ID, 'PATH' =>Option::get("ko.exportimport", "url_data_file")),
					'select' => 'F',// F - file only, D - folder only
					'operation' => 'O',// O - open, S - save
					'showUploadTab' => true,
					'showAddToMenuTab' => false,
					'fileFilter' => 'csv',
					'allowAllFiles' => true,
					'SaveConfig' => true,
				)
			);
			?>
		</td>
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
	<tr class="heading import_hl_data">
		<td colspan="2"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_HL_DATA')?></td>
	</tr>
	<tr class="import_hl_data">
		<td colspan="2">
			<table class="adm-detail-content-table edit-table">
				<tr>
					<td width="40%" class="adm-detail-content-cell-l"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_HL')?>:</td>
					<td class="adm-detail-content-cell-r">
						<!-- <select id="hl_id_importl_data" name="hl_id_data"> -->
							<select id="hl_id_importl_data"> 
							<option value="0"></option>
							<?foreach ($hlsVisual as $row):?>
							<option value="<?= intval($row['ID'])?>"><?= htmlspecialcharsbx($row['NAME_LANG'])?> [<?= $row['ID']?>]</option>
							<?endforeach;?>
						</select>
					</td>
				</tr>
				
				<tr> 
					<td class="adm-detail-content-cell-l"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_CLUCH')?>:<sup><span class="required">2</span></sup></td>
					<td class="adm-detail-content-cell-r" id="import_cluch"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l"><label for="export_userentity"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_COMPARING')?></label>:</td>
					<td id="import_comparing" class="adm-detail-content-cell-r"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">
						<label for="export_count_row"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_EXPORT_COUNT_ROW')?></label>:
					</td>
					<td class="adm-detail-content-cell-r">
						<input type="number" id="export_count_row" value="5000" name="export_count_row" checked="checked" min="1" />
					</td>
				</tr>
				<tr>
					<td width="40%" class="adm-detail-content-cell-l"><label for="export_coding">
						<?= Loc::getMessage('KO_EXPORTIMPORT_ADD_MISSING')?><sup><span class="required">3</span></sup></label>:</td>
						<td>
							<select id="add_missing" name="add_missing">
								<option value="1">Создавать</option>
								<option value="0">Не создавать</option>
							</select>
						</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr class="heading import_hl_structure">
		<td colspan="2"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_HL_STRUCTURE')?></td>
	</tr>
	<tr class="import_hl_structure">
		<td colspan="2">
			<table class="adm-detail-content-table edit-table">
				<tr>
					<td width="40%" class="adm-detail-content-cell-l"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_HL')?>:</td>
					<td class="adm-detail-content-cell-r">
						<select id="hl_id_import" name="hl_id">
							<option value="-1"></option>
							<option value="0"><?= Loc::getMessage('KO_EXPORTIMPORT_IMPORT_HL_NEW')?></option>
							<?foreach ($hlsVisual as $row):?>
							<option value="<?= intval($row['ID'])?>"><?= htmlspecialcharsbx($row['NAME_LANG'])?> [<?= $row['ID']?>]</option>
							<?endforeach;?>
						</select>
					</td>
				</tr>


			</table>
		</td>
	</tr>
	
	<?$tabControl->Buttons();?>
	<input type="submit" value="<?= Loc::getMessage('KO_EXPORTIMPORT_START_EXPORT')?>" class="adm-btn-save" id="KO_exportimport_submit">
	<?$tabControl->End();?>
</form>
<div class="adm-info-message">
	<p><span class="required">1</span> <?=Loc::getMessage('KO_EXPORTIMPORT_INFO');?></p>
	<p><span class="required">2</span> <?=Loc::getMessage('KO_EXPORTIMPORT_INFO_2');?></p>
	<p><span class="required">32</span> <?=Loc::getMessage('KO_EXPORTIMPORT_INFO_3');?></p>
</div>
<?

// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");