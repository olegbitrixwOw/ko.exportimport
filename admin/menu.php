<?

IncludeModuleLangFile(__FILE__); // в menu.php точно так же можно использовать языковые файлы

if ($APPLICATION->GetGroupRight("ko.exportimport") >= "R") { // проверка уровня доступа к модулю

	$aMenu = [
		"parent_menu" => "global_menu_content",
		"sort" => 100,
		"text" => GetMessage("KO_EXPORTIMPORT_MENU_TITLE"),
		"title"=> GetMessage("KO_EXPORTIMPORT_MENU_TITLE"),
		"icon" => "highloadblock_menu_icon",
		"page_icon" => "highloadblock_page_icon",
		"items_id" => "menu_ko",
		"items" => [ 
			[
				"sort" => 1, // выгрузка CSV файла всех данных highload блоков
				"text"=> GetMessage("KO_EXPORTIMPORT_MENU_EXPORT"),
				"title"=> GetMessage("KO_EXPORTIMPORT_MENU_EXPORT"),
				"items_id" => "menu_KO_export_test",
				"items" => [
					[
						"sort" => 1,
						"text"=> GetMessage("KO_EXPORTIMPORT_MENU_EXPORT_CSV"),
						"title"=> GetMessage("KO_EXPORTIMPORT_MENU_EXPORT_CSV"),
						"url"=>"/bitrix/admin/ko_exportimport_ExportHighLoadBlock.php",
					]
				]
			],
			[
				"sort" => 2, // выгрузка лога файла highload блоков
				"text"=> GetMessage("KO_EXPORTIMPORT_MENU_LOG"),
				"title"=> GetMessage("KO_EXPORTIMPORT_MENU_LOG"),
				"items_id" => "menu_KO_register_test",
				"items" => [
					[
						"sort" => 1,
						"text"=> GetMessage("KO_EXPORTIMPORT_MENU_LOG_HLEVENTS"),
						"title"=> GetMessage("KO_EXPORTIMPORT_MENU_LOG_HLEVENTS"),
						"url"=>"/bitrix/admin/ko_exportimport_ExportReport.php", 
					]
				]
			],

			[
				"sort" => 3, // импорт данных в highload блоки
				"text"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT"),
				"title"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT"),
				"items_id" => "menu_KO_import",
				"items" => [
					// [
					// 	"sort" => 1,
					// 	"text"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT_CSV"),
					// 	"title"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT_CSV"),
					// 	"url"=>"/bitrix/admin/KO_exportimport_ImportCSV.php",
					// ],

					[
						"sort" => 1,
						"text"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT_REPORT"),
						"title"=> GetMessage("KO_EXPORTIMPORT_MENU_IMPORT_REPORT"),
						"url"=>"/bitrix/admin/ko_exportimport_ImportReport.php", 
					],
				]
			]
		]
	];

	// вернем полученный список
	return $aMenu;
}
// если нет доступа, вернем false
return false;
