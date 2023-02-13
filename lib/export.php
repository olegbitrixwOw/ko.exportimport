<?php

namespace ko\Exportimport;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use \ko\Exportimport\ORMhelper as ORMhelper;
use \ko\Exportimport\HlbookTable as HlbookTable;

class Export {
	
	public function __construct() {
		Loc::loadMessages(__FILE__); 
		if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
			throw new SystemException(Loc::getMessage('KO_EXPORTIMPORT_ERROR_HIGHLOADBLOCK'));
		}
	}
	
	
	 // возвращает структуру HL
	public function GetHlStructure($ID) {
		$result = [];
		// Сам highloadblock
		$row = HL\HighloadBlockTable::getRow(array(
				'filter' => ['ID' => $ID]
		));
		if ($row) {
			$result['hiblock'] = $row;
			// Языки
			$res = HL\HighloadBlockLangTable::getList(array(
				'filter' => ['ID' => $ID]
			));
			while ($row = $res->fetch()) {
				$result['langs'][$row['LID']] = $row['NAME'];
			}
			// Записываем поля
			$result['fields'] = \ko\Exportimport\Helper::GetUserEntity($ID);
		} else {
			throw new SystemException(str_replace('#ID#', $ID, Loc::getMessage('KO_EXPORTIMPORT_ERROR_NOT_ID')));
		}

		return $result;
	}
	
	// возвращает структуру HL
	// проверка на int работает начиная с PHP 7.0.0
	public function GetHlData($ID, array $arr_step, array $select = []) {

		$result = [];
		// Сам highloadblock
		$row = HL\HighloadBlockTable::getRow(array(
				'filter' => ['ID' => $ID]
		));
		if ($row) {
			$hldata = HL\HighloadBlockTable::getById($ID)->fetch();
			$entity = HL\HighloadBlockTable::compileEntity($hldata);
			$ob_hldata = $entity->getDataClass();
			
			// Общее число строк в таблице
			$count = $ob_hldata::getList(array(
				'select' => array('CNT'),
				'runtime' => array(
					new Entity\ExpressionField('CNT', 'COUNT(*)')
				)
			))->fetch();
			$result['fields_all_count'] = $count['CNT']; 
			//$connection = \Bitrix\Main\Application::getConnection();
			//$tracker = $connection->startTracker();
			// Сама выборка
			array_unshift($select, 'ID');
			$result['fields'] = $ob_hldata::getList([
				'select' => $select,
				'order' => ['ID'],
				'filter' => ['>ID' => $arr_step['step_id']],
				'limit' => $arr_step['limit']
				])->fetchAll();
		
			// Узнаем последний ID
			$end_row = end($result['fields']); 
			$result['step_id'] = $end_row['ID'];
			
			// экспортировали строк
			$result['fields_count'] = $ob_hldata::getList(array(
				'select' => array('CNT'),
				'filter' => ['<=ID' => [$end_row['ID']]],
				'runtime' => array(
					new Entity\ExpressionField('CNT', 'COUNT(*)')
				)
			))->fetch()['CNT'];			

			//$connection->stopTracker();
			
		} else {
			throw new SystemException(str_replace('#ID#', $ID, Loc::getMessage('KO_EXPORTIMPORT_ERROR_NOT_ID')));
		}

		return $result;
	}

	// возвращает данные ORM где хранятся лог измененных элементов
	public function GetORMData($hlname, array $hlfields = []) {

        $result = [];
        $result['elements'] = [];
        $fields_all_count = ORMhelper::CountRows($hlname); // общее число строк в таблице
        $elements = ORMhelper::GetHLlog($hlname);
        $result['error'] = false;
        $result['updated'] = 0;
        $result['added'] = 0;
        $result['deleted'] = 0;

        if(!empty($fields_all_count) && (!empty($elements))){

            $step_id = false;
            $fields_count = count($elements);
            
            foreach ($elements as $key => $element) {
                $element_fields = json_decode($element['ELEMENT_FIELDS'], true);
                if(is_array($element_fields)){
                	// убираем поля которых уже нет в highload блоке
	                foreach ($element_fields as $keyField => $valueField) {
	                    if(!in_array($keyField, $hlfields)){
	                        unset($element_fields[$keyField]);
	                    }
	                }
	                $element_fields['ADDED'] = 0;
	                $element_fields['UPDATED'] = 0;
	                $element_fields['DELETED'] = 0;
                }else{
                	$element_fields = [];
                	foreach ($hlfields as  $hlfield) {

                		$element_fields[$hlfield] = 0;
                	}
                }

                $element_fields['LOG'] = '';

                if($element['ADDED']){
                    $element_fields['LOG'] .= 'Элемент был добавлен, дата создания:'.$element['CREATED_AT'];
                    $element_fields['ADDED'] = $element['ADDED'];
                    $result['added']++;
                }

                if($element['UPDATED']){
                    $element_fields['LOG'] .= 'Элемент был отредактирован, количество раз:'.$element['UPDATED'].', дата последнего редактирования:'.$element['UPDATED_AT'].' ';

                    $element_fields['UPDATED'] = $element['UPDATED'];
                    $result['updated']++;
                }
                
                if($element['DELETED']){
                    $element_fields['LOG'] .= 'Элемент был удален, дата удаления:'.$element['DELETED_AT'].' ';
                    $element_fields['DELETED'] = $element['DELETED'];
                    $result['deleted']++;
                }

                $result['elements'][$key] = $element_fields;
                $step_id = $element['ELEMENT_ID'];

     //            if($element['ELEMENT_ID'] == 28){
					// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element['ADDED'])."\r\n", FILE_APPEND);
					// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element['UPDATED'])."\r\n", FILE_APPEND);
					// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element['DELETED'])."\r\n", FILE_APPEND);
     //            	// foreach ($element_fields as $key => $value) {
     //            	// 	 file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($value)."\r\n", FILE_APPEND);
     //            	// }
     //            }
                // if($element['ELEMENT_ID'] == 27){
                // 	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element)."\r\n", FILE_APPEND);
                // 	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element_fields['UPDATED'])."\r\n", FILE_APPEND);
                // 	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element_fields['LOG'])."\r\n", FILE_APPEND);
                // 	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($hlfields)."\r\n", FILE_APPEND);
                // 	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element['UPDATED'])."\r\n", FILE_APPEND);

                // }

            }

            $result['fields_all_count'] = $fields_all_count;
            $result['step_id']=$step_id;
            $result['fields_count']=$fields_count;
            
        }else{
            // throw new SystemException(str_replace('#hlname#', $hlname, 'Нет логов по этому highload блоку (возможно не было подключено логирование, укажите в настройках модуля этот highload блок)')));

            $result['error'] = str_replace('#hlname#', $hlname, 'Нет логов по этому highload блоку (возможно не было подключено логирование, укажите в настройках модуля этот highload блок)');
        }

        return $result;

    }
	
}
