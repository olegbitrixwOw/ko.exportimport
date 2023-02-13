<?php
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ko.exportimport/admin/ExportReport.php")) {
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ko.exportimport/admin/ExportReport.php");
} else {
	require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/ko.exportimport/admin/ExportReport.php");
}