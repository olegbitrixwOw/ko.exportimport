<?
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ko.exportimport/admin/ImportCSV.php")) {
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ko.exportimport/admin/ImportCSV.php");
} else {
	require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/ko.exportimport/admin/ImportCSV.php");
}