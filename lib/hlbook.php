<?php

namespace ko\Exportimport;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

// ORM где хранятся лог добавленных/измененных/удаленных элементов
class HlbookTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'hlbook_exportimport';
    }

    public static function getUfId()
    {
        return 'HLBOOK_EXPORTIMPORT';
    }

    /*public static function getConnectionName()
    {
        return 'default';
    }*/

    public static function getMap()
    {
        return array(
            //ID
            new Entity\IntegerField('ID', array( 
                'primary' => true,
                'autocomplete' => true 
            )),
            new Entity\IntegerField('ELEMENT_ID'), // ELEMENT_ID
            // new Entity\StringField('UF_XML_ID'), // UF_XML_ID
            // new Entity\StringField('ELEMENT_FIELDS'), // ELEMENT_FIELDS
            // new Entity\StringField('HL_FIELDS'), // HL_FIELDS
            new Entity\StringField('UF_XML_ID', array('default_value' => 0)),
            new Entity\StringField('ELEMENT_FIELDS', array('default_value' => 0)),
            new Entity\StringField('HL_FIELDS', array('default_value' => 0)),
            new Entity\StringField('HLNAME'), // HLNAME
            new Entity\IntegerField('ADDED'),
            new Entity\IntegerField('UPDATED'),
            new Entity\IntegerField('DELETED'),
            new Entity\DatetimeField('CREATED_AT'),
            new Entity\DatetimeField('UPDATED_AT'),
            new Entity\DatetimeField('DELETED_AT')
           
         
        );
    }
   
}