<?php

namespace ko\Exportimport;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type; 
use Bitrix\Main\Entity;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Application;
use \ko\Exportimport\HlbookTable as HlbookTable;

use \Bitrix\Highloadblock as HL;

// Содержит вспомогательные методы для работы с HlbookTable
class ORMhelper {

	// получаем запись из ORM
	public static function GetElement($element_id) {
		return $element  = HLbookTable::getList(array(
		    'select'  => array('*'), // имена полей, которые необходимо получить в результате
		    'filter'  => array('ELEMENT_ID' => $element_id), // описание фильтра для WHERE и HAVING
		    'limit'   => 1, // количество записей
		))->fetch();
	}

	// получаем писок записей из ORM по названию highload блока
	public static function GetHLlog($hlname) {
		return $elements  = HLbookTable::getList(array(
		    'select'  => array('*'), // имена полей, которые необходимо получить в результате
		    'filter'  => array('HLNAME' => $hlname), // описание фильтра для WHERE и HAVING
		    // 'limit'   => 1, // количество записей
		))->fetchAll();
	}

	// добавляем запись в ORM
	public static function AddElement($elFields, $hlname, $hlfields) {
		return $element = HLbookTable::add(array(
		        'ELEMENT_ID' => (int)$elFields['ID'],
		        'UF_XML_ID'=> $elFields['UF_XML_ID'],
		        'ELEMENT_FIELDS'=>json_encode($elFields, true),
		        'HL_FIELDS'=>json_encode($hlfields, true),
		        'HLNAME' => $hlname,
		        'ADDED' => 1,
		        'CREATED_AT' => new Type\DateTime(),
		));
	}

	// указываем UPDATED в ORM
	public static function UpdateElement($elFields, $hlfields) {
		$element = self::GetElement($elFields['ID']);
		if(empty($element)){
			$element = HLbookTable::add(array(
			        'ELEMENT_ID' => (int)$elFields['ID'],
			        'UF_XML_ID'=> $elFields['UF_XML_ID'],
			        'ELEMENT_FIELDS'=>json_encode($elFields, true),
			        'HL_FIELDS'=>json_encode($hlfields, true),
			        'HLNAME' => $elFields['HLNAME'],
			        'UPDATED' => 1,
			        'UPDATED_AT' => new Type\DateTime()
			));
		}else{
			$result = HLbookTable::update($element['ID'], array(
				'UF_XML_ID'=> $elFields['UF_XML_ID'],
				'ELEMENT_FIELDS'=>json_encode($elFields, true),
				'HL_FIELDS'=>json_encode($hlfields, true),
			    'UPDATED' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'UPDATED'),
			    'UPDATED_AT' => new Type\DateTime()
			));
		}
		// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($elFields)."\r\n", FILE_APPEND);
		return $element;
	}

	// указываем DELETED в ORM
	public static function DeleteElement($element_id, $hlname) {
		$element = self::GetElement($element_id);
		if(empty($element)){
			$result = HLbookTable::add(array(
				'ELEMENT_ID' => $element_id,
			    'DELETED' => 1,
			    'DELETED_AT' => new Type\DateTime(),
			    'HLNAME' =>$hlname
			));
		}else{
			$result = HLbookTable::update($element['ID'], array(
			    'DELETED' => 1,
			    'DELETED_AT' => new Type\DateTime()
			));
		}
		return $result;
	}

	// удаляем все записи в ORM
	public static function DeleteRows(){
		$connection = Application::getConnection();
		return $result = $connection->truncateTable(HlbookTable::getTableName());
	}

	// считаем число записей в таблице 
	public static function CountRows($hlname){
		$result = HLbookTable::getList(array(
            'select' => array('ID'),
            'filter'=>array('HLNAME' => $hlname),
            'runtime' => array(
                new Entity\ExpressionField('ID', 'COUNT(*)')
            )
        ))->fetch();
        return $result['ID'];
	}

}
