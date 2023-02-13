<?php
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Localization\Loc;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

// Проверки
$prava = $APPLICATION->GetGroupRight("ko.exportimport");
$type = (int)filter_input(INPUT_POST, 'type', FILTER_SANITIZE_NUMBER_INT);
if ($type == 0 || !$prava >= "R") {
	throw new SystemException(Loc::getMessage('KO_EXPORTIMPORT_ERROR'));
}
if (!\Bitrix\Main\Loader::includeModule('ko.exportimport')) {
	CAdminMessage::ShowMessage(GetMessage('KO_EXPORTIMPORT_ERROR_MODULE'));
	return false;
}

$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();
		$response = $context->getResponse();

// Языковые файлы
Loc::loadMessages(__FILE__); 

$result = [];
$ob_ajax = new \ko\Exportimport\Ajax();

switch ($type) {
	case 3:
		$result['status'] = true;
		$result['hl_id'] = $ob_ajax->GetUserEntity();
		break;
	case 5:
		$res = $ob_ajax->GetUserEntityImport();
		$result['status'] = $res['status'];
		$result['text'] = $res['text'];
		// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($result['text'])."\r\n", FILE_APPEND); 
		break;
	case 7:
		$res = $ob_ajax->GetKey();
		$result['status'] = $res['status'];
		$result['text'] = $res['text'];
		break;
	case 8:
		$ob = $ob_ajax->ImportDataCSV();
		$result['status'] = $ob['status'];
		$result['text'] = $ob['text'];
		$result['text'] = iconv(LANG_CHARSET,  "UTF-8//IGNORE", $result['text']);
		$result['fields_all_count'] = $ob['fields_all_count'];
		$result['fields_count'] = $ob['fields_count'];
		$result['step_id'] = $ob['step_id'];
		$result['import_error_count'] = $ob['import_error_count'];
		break;

	case 10: 
		// тестовый экспорт
		$result['status'] = true;
		$ob = $ob_ajax->ExportHiloadBlockCSV();
		$result['text'] = $ob['text'];
		$result['text'] = iconv(LANG_CHARSET,  "UTF-8//IGNORE", $result['text']); // кодировка
		$result['fields_all_count'] = $ob['fields_all_count']; // все записи
		$result['step_id'] = $ob['step_id']; // шаги
		break;

	case 12:
		// тестовый экспорт
		$result['status'] = true;
		$ob = $ob_ajax->ExportDataORM();
		$result['text'] = $ob['text'];
		$result['text'] = iconv(LANG_CHARSET,  "UTF-8//IGNORE", $result['text']); // кодировка
		$result['fields_all_count'] = $ob['fields_all_count']; // все записи
		$result['step_id'] = $ob['step_id']; // шаги
		// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($type)."\r\n", FILE_APPEND);
		break;

	case 13:
		$result['status'] = true;
		$ob = $ob_ajax->ImportDataORM();
		$result['text'] = $ob['text'];
		$result['text'] = iconv(LANG_CHARSET,  "UTF-8//IGNORE", $result['text']); // кодировка
		$result['fields_all_count'] = $ob['fields_all_count']; // все записи
		$result['step_id'] = $ob['step_id']; // шаги
		$result['step_id'] = $ob['step_id'];
		$result['import_error_count'] = $ob['import_error_count'];

		break;

	default:

	  break;
}

// Выводим результат
echo json_encode($result,	JSON_FORCE_OBJECT);