<?php
$MESS['KO_EXPORTIMPORT_TITLE'] = 'Импорт логов Highload-блока из CSV';
$MESS['KO_EXPORTIMPORT_TITLE_IMPORT'] = 'Импорт логов Highload-блока';
$MESS['KO_EXPORTIMPORT_FIELD_IMPORT_FILE'] = 'Файл для импорта';
$MESS['KO_EXPORTIMPORT_EXPORT_DELIMITER'] = 'Разделитель полей';
$MESS['KO_EXPORTIMPORT_EXPORT_ENCLOSURE'] = 'Ограничитель полей';
$MESS['KO_EXPORTIMPORT_EXPORT_DELIMITER_M'] = 'Разделитель множественных полей';
$MESS['KO_EXPORTIMPORT_EXPORT_CODING'] = 'Кодировка файла CSV';
$MESS['KO_EXPORTIMPORT_IMPORT_O_SET'] = 'Основные настройки';
$MESS['KO_EXPORTIMPORT_IMPORT_HL_DATA'] = 'Настройки импорта данных';
$MESS['KO_EXPORTIMPORT_IMPORT_HL_STRUCTURE'] = 'Настройки импорта структуры Highload-блока';
$MESS['KO_EXPORTIMPORT_IMPORT_HL'] = 'Highload-блок';
$MESS['KO_EXPORTIMPORT_IMPORT_HL_NEW'] = '--Создать новый Highload-блок--';
$MESS['KO_EXPORTIMPORT_IMPORT_HLS'] = 'Импортировать структуру';
$MESS['KO_EXPORTIMPORT_IMPORT_DATA'] = 'Импортировать данные';
$MESS['KO_EXPORTIMPORT_IMPORT_CLUCH'] = 'Поле внешнего ключа';
$MESS['KO_EXPORTIMPORT_IMPORT_COMPARING'] = 'Соответствие полей из Highload-блока полям из файла';
$MESS['KO_EXPORTIMPORT_IMPORT_EXPORT_COUNT_ROW'] = 'Количество строк импортируемых на одном шаге';
$MESS['KO_EXPORTIMPORT_IMPORT_USERENTITY'] = 'Выгружать поля Highload-блока<br>(только для экспорта данных)';
$MESS['KO_EXPORTIMPORT_START_EXPORT'] = 'Импортировать';
$MESS['KO_EXPORTIMPORT_ERROR_HIGHLOADBLOCK'] = 'Для работы данного решения необходимо наличие модуля "highloadblock"';
$MESS['KO_EXPORTIMPORT_ERROR_MODULE'] = 'Модуль Экспорт/Импорт Highload-блоков в CSV, JSON не подключен';
$MESS['KO_EXPORTIMPORT_ERROR_NOT_ID'] = 'Не найден Highload-блок с ID=#ID#';
$MESS['KO_EXPORTIMPORT_ERROR_FILE'] = 'Ошибка записи файла';
$MESS['KO_EXPORTIMPORT_ERROR_GETUSERENTITYIMPORT'] = 'Файл для импорта не был указан или не существует на сервере';
$MESS['KO_EXPORTIMPORT_INFO'] = 'Первая строка CSV файла, должна <b>обязательно</b> содержать названия импортируемых полей.';
$MESS['KO_EXPORTIMPORT_INFO_2'] = 'В параметре указывается поле из импортируемого файла,'
	. ' по значениям которого будет выполняться поиск записей, по полю <b>ID</b> в highload-блоке. Если запись найдена, '
	. 'то ее поля будут обновлены в соответствии со значениями в файле. Если запись отсутствует, '
	. 'то она будет добавлена в highload-блок. Если в параметре поле не выбрано, '
	. 'то все записи из файла будут добавлены в highload-блок как новые (даже если они полностью дублируются).';