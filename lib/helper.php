<?php

namespace ko\Exportimport;
use \Bitrix\Highloadblock as HL;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;

// содержит вспомогательные методы доступные во всем модуле
class Helper {
	
	public static function UseModuleHL() {
		if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
			throw new SystemException(Loc::getMessage('KO_EXPORTIMPORT_ERROR_HIGHLOADBLOCK'));
		} 
	}
	
	// возвращает имена полей и их параметры HL
	public static function GetUserEntity($ID) {
		self::UseModuleHL();
		$rows = [];
		$res = \CUserTypeEntity::GetList(
				array(), array(
				'ENTITY_ID' => 'HLBLOCK_' . $ID
				)
		);
		$connection = \Bitrix\Main\Application::getConnection();
		while ($row = $res->fetch()) {
			$langs = $connection->query('SELECT * FROM `b_user_field_lang` WHERE `USER_FIELD_ID` = ' . $row['ID'] . '');
			while ($lang = $langs->fetch()) {
				$row['langs'][$lang['LANGUAGE_ID']] = $lang;
			}
			$rows[$row['ID']] = $row;
		}
		return $rows;
	}
	
	// возвращает список highload блоков
	public static function GetAllHL() {
		self::UseModuleHL();
		// init data
		$hls = array();
		$res = HL\HighloadBlockTable::getList(array(
				'select' => array(
					'*', 'NAME_LANG' => 'LANG.NAME'
				),
				'order' => array(
					'NAME_LANG' => 'ASC', 'NAME' => 'ASC'
				)
		));
		while ($row = $res->fetch()) {
			$row['NAME_LANG'] = $row['NAME_LANG'] != '' ? $row['NAME_LANG'] : $row['NAME'];
			$hls[$row['ID']] = $row;
		}
		return $hls;
	}
	
	// возвращает размер в байтах
	public static function GetBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last) {
			// Модификатор 'G' доступен, начиная с PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	// возвращает сущность highload  блока по его hlId
	public static function GetHLentity($hlId){
		self::UseModuleHL();
		$hlblock = HL\HighloadBlockTable::getById($hlId)->fetch();   
		$entity = HL\HighloadBlockTable::compileEntity($hlblock);
		return $entity;
	}

	public static function GetEntityByEvent($event){
		// HL блок
	    $entity = $event->getEntity();
	    $hlname = $entity->getName(); // имя HL блока

        // список полей HL блока
	    $fields = $entity->getFields();
	    $hlfields = [];
	    foreach ($fields as $key => $value) {
	    	$hlfields[] = $key;
	    }

	    return $result = [
	    	'entity'=>$entity,
	    	'hlname'=>$hlname, // имя HL блока
	    	'hlfields'=>$hlfields
	    ];
	}


	// возвращает список элементов инфоблока
	public static function GetHLelements($hlId) {
		self::UseModuleHL();
		$elements = [];
		$hlblock = HL\HighloadBlockTable::getById($hlId)->fetch();   

        // получаем список элементов блока
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $rsData = $entity_data_class::getList(array(
           'select' => array('*')
        ));

        while($el = $rsData->fetch()){
            $elements[] = $el;
        }
        return $elements;
	}

	// public static function SetEventOnBeforeAdd($hlName){
	// 	$eventManager = \Bitrix\Main\EventManager::getInstance();
	// 	$eventName = $hlName."OnBeforeAdd";
	// 	$eventManager->registerEventHandler("", $eventName, "ko.exportimport", "\ko\Exportimport\Helper","OnBeforeAdd");
	// 	return $eventName;
	// }

	// --- ОБРАБОТЧИКИ СОБЫТИЙ HIGHLOAD БЛОКОВ
	static public  function OnAfterUpdate(\Bitrix\Main\Entity\Event $event){

		$eventType = $event->getEventType();
	    $elFields = $event->getParameter("fields"); // получаем массив полей хайлоад блока
	    $arParameters = $event->getParameters(); //получить все доступные в этом событие данные
	    $result = new \Bitrix\Main\Entity\EventResult();

	    // получаем у HL имя и список полей 
	    $hlentity = self::GetEntityByEvent($event);

	    $id = $event->getParameter("id"); //id обновляемого элемента
	    $elFields['ID'] = $id["ID"];
	    $elFields['HLNAME'] = $hlentity['hlname'];
	    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($elFields['ID'])."\r\n", FILE_APPEND);

	    // записываем в таблицу 
	    $element = ORMhelper::UpdateElement($elFields, $hlentity['hlfields']);

	    return $result;
	}

	static public function OnAfterDelete(\Bitrix\Main\Entity\Event $event){
	
	    $arParameters = $event->getParameters(); //получить все доступные в этом событие данные
	    $result = new \Bitrix\Main\Entity\EventResult();

	    // получаем у HL имя и список полей 
	    $hlentity = self::GetEntityByEvent($event);

	    $id = $event->getParameter("id"); // id  элемента
	    $hlname = $hlentity['hlname'];
	    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($id)."\r\n", FILE_APPEND);

	    // записываем в таблицу 
	    $element = ORMhelper::DeleteElement($id["ID"], $hlname);
	    return $result;
	}


	static public  function OnAfterAdd(\Bitrix\Main\Entity\Event $event){
		
    	// получаем массив полей хайлоад блока
	    $elFields = $event->getParameter("fields");
		
		//получить все доступные в этом событие данные
	    $arParameters = $event->getParameters();
	    $result = new \Bitrix\Main\Entity\EventResult();

	    // получаем у HL имя и список полей 
	    $hlentity = self::GetEntityByEvent($event); 

	    $elFields['ID'] = $event->getParameter("id"); // ID элемента
	    
	    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($elFields['ID'])."\r\n", FILE_APPEND);

	    $element = ORMhelper::AddElement($elFields, $hlentity['hlname'], $hlentity['hlfields']);
	    return $result;
	}


	// устанавливаем обработчики событий на изменения элементов highloab блоков
	public static function InstallHLEvents($hlId, $handler){
        
        $entity = self::GetHLentity($hlId);
        $hlname = $entity->getName(); // получаем имя highload блока
        $eventName = $hlname.$handler; // конкатинируем имя highload блока b название обработчика получаем имя события
        // регистрируем событие
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("", $eventName, "ko.exportimport", "\ko\Exportimport\Helper",$handler);
        return $eventName; 
	}
	
	// удаляем обработчики событий на изменения элементов highloab блоков
	public static function UnInstallHLEvents($hlId, $handler){

        $entity = self::GetHLentity($hlId);
        $hlname = $entity->getName();

        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventName = $hlname.$handler;
        $eventManager->unRegisterEventHandler(" ", $eventName, "ko.exportimport", "\ko\Exportimport\Helper", $handler);
        return $eventName;
    }


}
