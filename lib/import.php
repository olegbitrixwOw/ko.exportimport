<?php

namespace ko\Exportimport;
use \Bitrix\Main\Localization\Loc;

//импорт файлов
class Import {
	
	public function __construct() {
		Loc::loadMessages(__FILE__); 
		if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
			throw new SystemException(Loc::getMessage('KO_EXPORTIMPORT_ERROR_HIGHLOADBLOCK'));
		}
		
	}
	
}
