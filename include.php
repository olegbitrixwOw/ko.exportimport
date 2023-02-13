<?
// конфиг JS и CSS модуля
$arJsConfig = array(
	'ko_exportimport' => array(
		'js' => '/bitrix/js/ko.exportimport/script.js',
		'css' => '/bitrix/panel/ko.exportimport/main_style.css',
		'rel' => array('jquery2'),
	)
);
// Регистрация JS и CSS модуля
foreach ($arJsConfig as $ext => $arExt) {
	\CJSCore::RegisterExt($ext, $arExt);
}
// подключение JS и CSS модуля
CUtil::InitJSCore(array('ko_exportimport')); 
