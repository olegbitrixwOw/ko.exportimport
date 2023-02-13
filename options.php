<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

$module_id = 'ko.exportimport';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < "S") {
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

\Bitrix\Main\Loader::includeModule($module_id); 

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$hlblocks = array();
// $default_hlblock = false;
// создаем объект
try { 
	try {
		$hlsVisual = \ko\Exportimport\Helper::GetAllHL();	
		$hlblocks[0] = 'highloadblock не выбран';
		foreach ($hlsVisual as $row){
			$key = intval($row['ID']);
			$hlblocks[$key] = $row['NAME_LANG'];
		}
		$default_hlblock = $hlsVisual[0]['ID'];
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



// Описание опций
$aTabs = array(
	array(
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('KO_EXPORTIMPORT_TAB_SETTINGS'),
		"TITLE" => Loc::getMessage('KO_EXPORTIMPORT_TAB_SETTINGS'),
		'OPTIONS' => array(
			// Loc::getMessage('KO_EXPORTIMPORT_TAB_SETTINGS'),
			// array(
			// 	'export_send_email',
			// 	Loc::getMessage('KO_EXPORTIMPORT_EXPORT_SEND_EMAIL'),
			// 	Option::get("ko.exportimport", "export_send_email"),
			// 	array('text', 30)
			// ),
			Loc::getMessage('KO_EXPORTIMPORT_REGISTER_ADD'),
            array(
                'add_handler',                            
                Loc::getMessage('KO_EXPORTIMPORT_REGISTER_HL'), 
                0,  
                array(
                    'selectbox',
                    $hlblocks
                )		
            ),
            array(
                'add_handler_action', 
                Loc::getMessage('KO_EXPORTIMPORT_ADD_HANDLER_ACTION'), 
                'crud',  
                array(
                	'selectbox',
                	array(
                		'create' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_CREATE'),
	                    'update' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_UPDATE'),
	                    'delete' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_DELETE'),
	                    'crud' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_CRUD')
                	)
                )	
            ),

            Loc::getMessage('KO_EXPORTIMPORT_REGISTER_REMOVE'),
            array(
                'remove_handler',                                   // имя элемента формы
                Loc::getMessage('KO_EXPORTIMPORT_REGISTER_HL'), // поясняющий текст — «Скорость прокрутки»
                0,                                  // значение по умолчанию «normal»
                array(
                    'selectbox',                           // тип элемента формы — <select>
                    $hlblocks
                )		
            ),
            array(
                'remove_handler_action',                                   // имя элемента формы
                Loc::getMessage('KO_EXPORTIMPORT_REMOVE_HANDLER_ACTION'), // поясняющий текст 
                'crud',                                  // значение по умолчанию «normal»
                array(
                	'selectbox',
                	array(
                		'create' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_CREATE'),
	                    'update' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_UPDATE'),
	                    'delete' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_DELETE'),
	                    'crud' => Loc::getMessage('KO_EXPORTIMPORT_HANDLER_CRUD')
                	)
                )	
            )

		)
	),

	array(
		'DIV' => 'edit2',
		'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
		"TITLE" => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS')
	),
	
);

// Сохранение
if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {

	$add_hlId = false;
	$remove_hlId = false;
	$add_handler_action = 'crud';
	$remove_handler_action = 'crud';

	foreach ($aTabs as $aTab) {


		foreach ($aTab['OPTIONS'] as $arOption) {
			
			if (!is_array($arOption)) {//Строка с подсветкой. Используется для разделения настроек в одной вкладке
				continue;
			}

			if ($arOption['note']){ //Уведомление с подсветкой
				continue;
			}

			$optionName = $arOption[0];
			$optionValue = $request->getPost($optionName);
			Option::set(
				$module_id, 
				$optionName, 
				is_array($optionValue) ? implode(",", $optionValue) : $optionValue
			);

			if(in_array('add_handler', $arOption, true)){
				$add_hlId = intval($optionValue);
			}

			if(in_array('add_handler_action', $arOption, true)){
				$add_handler_action = $optionValue;
			}

			if(in_array('remove_handler', $arOption, true)){
				$remove_hlId = intval($optionValue);
			}

			if(in_array('remove_handler_action', $arOption, true)){
				$remove_handler_action = $optionValue;
			}

		}
	}

	// регистрируем обработчики событий
	if($add_hlId){
		
		if($add_hlId != $remove_hlId){
			$handlers = [];
			switch ($add_handler_action) {
					case 'create':
						$handlers[0] = 'OnAfterAdd';
						break;
					case 'update':
						$handlers[0] = 'OnAfterUpdate';
						break;
					case 'delete':
						$handlers[0] = 'OnAfterDelete';
						break;
					default:
						$handlers[0] = 'OnAfterAdd';
						$handlers[1] = 'OnAfterUpdate';
						$handlers[2] = 'OnAfterDelete';
					break;
			}

			foreach ($handlers as $handler) {
				$hlname = \ko\Exportimport\Helper::InstallHLEvents($add_hlId, $handler);
			}
		}
	}
	if($remove_hlId){
		$handlers = [];
			switch ($remove_handler_action) {
				case 'create':
					$handlers[0] = 'OnAfterAdd';
					break;
				case 'update':
					$handlers[0] = 'OnAfterUpdate';
					break;
				case 'delete':
					$handlers[0] = 'OnAfterDelete';
					break;
				default:
					$handlers[0] = 'OnAfterAdd';
					$handlers[1] = 'OnAfterUpdate';
					$handlers[2] = 'OnAfterDelete';
				break;
			}
		foreach ($handlers as $handler) {
			$eventName = \ko\Exportimport\Helper::UnInstallHLEvents($remove_hlId, $handler);
		}
	}

}

// Визуальный вывод
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<? $tabControl->Begin(); ?>
	<form method='post'
		  action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>'
		  name='KO_exportimport_settings'>
			  <? foreach ($aTabs as $aTab) {
				  if ($aTab['OPTIONS']) {
					  $tabControl->BeginNextTab();
					  __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
				 }
			} 
			// Для работы с файлами
			CAdminFileDialog::ShowScript
			(
				Array(
					'event' => 'BtnClick',
					'arResultDest' => array('FORM_NAME' => 'ko_exportimport_settings', 'FORM_ELEMENT_NAME' => 'url_data_file'),
					'arPath' => array('SITE' => SITE_ID, 'PATH' =>''),
					'select' => 'D',// F - file only, D - folder only
					'operation' => 'S',// O - open, S - save
					'showUploadTab' => true,
					'showAddToMenuTab' => false,
					//'fileFilter' => 'csv',
					'allowAllFiles' => true,
					'SaveConfig' => true,
				)
			);
			
		// Доступ к модулю
		$tabControl->BeginNextTab();
		require_once ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php');

		$tabControl->Buttons(); ?>

		<input type="submit" name="Update" class="adm-btn-save" value="<? echo GetMessage('MAIN_SAVE') ?>">
		<?= bitrix_sessid_post(); ?>
	</form>
<? $tabControl->End(); ?>
