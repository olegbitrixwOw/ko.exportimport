<?php

namespace ko\Exportimport;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

use \ko\Exportimport\ORMhelper as ORMhelper;
use \ko\Exportimport\Helper as Helper;
use Bitrix\Main\Application;


class Ajax {
	
	public $request;
	public $response;
	public $module_id = 'ko.exportimport';
	
	public function __construct() {
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$this->request = $context->getRequest();
		$this->response = $context->getResponse();
	}
	
	public function ImportDataCSV() {
		$method = $this->request->getRequestMethod();
		$ob_import_CSV = new \ko\Exportimport\ImportCSV();
		$result = [];
 
		// Сохраняем POST
		$param = [];
		$param['url_data_file'] = iconv(mb_internal_encoding(), LANG_CHARSET . "//IGNORE", $this->request->getPost('url_data_file'));
		$param['hl_id'] = (int) $this->request->getPost('hl_id_data');
		$param['export_type'] = $this->request->getPost('export_type');
		$param['export_count_row'] = (int) $this->request->getPost('export_count_row');
		$param['import_error_count'] = (int) $this->request->getPost('import_error_count');
		$param['export_coding'] = $this->request->getPost('export_coding');
		$param['arr_step']['export_step_id'] = (int) $this->request->getPost('export_step_id');
		$param['arr_step']['FIELDS'] = (array) $this->request->getpost('FIELDS');
		$param['CSV']['delimiter'] = $this->request->getpost('delimiter');
		$param['CSV']['enclosure'] = $this->request->getpost('enclosure');
		$param['CSV']['delimiter_m'] = $this->request->getpost('delimiter_m');
		$param['import_key'] = $this->request->getpost('import_key');
		
		// Если все норм работаем
		if ($method == 'POST') {
			ob_start();
			if (!empty($param['url_data_file']) && $param['hl_id'] > 0 && !empty($param['export_type']) && check_bitrix_sessid() && $param['export_count_row'] > 0) {

				if ($param['export_type'] == 'export_hl') {
					?><div class="adm-info-message">
						<p><?=Loc::getMessage('KO_EXPORTIMPORT_ZAGLUSHKA');?></p>
					</div><?
				}

				if ($param['export_type'] == 'export_data') {
					// Сбор доп. данных
					$arr_import = $this->ProvFileCSV();

					foreach (\ko\Exportimport\Helper::GetUserEntity($param['hl_id']) as $field) {
						$param['arr_step']['FIELDS_TYPE'][$field['FIELD_NAME']]['USER_TYPE_ID'] = $field['USER_TYPE_ID'];
						$param['arr_step']['FIELDS_TYPE'][$field['FIELD_NAME']]['MULTIPLE'] = $field['MULTIPLE'];
					}

					if ($arr_import['status'] === true) {
						$param['data'] = $arr_import['arr'];
						$data = $ob_import_CSV->ImportDataCSV($param);

						// Запишем логи ошибок, и счетчик
						if (!empty($data['error'])) {
							file_put_contents(
								\Bitrix\Main\Application::getDocumentRoot() . '/upload/tmp_KO_exportimport/ImportLog.txt',
								print_r($data['error'], true),
								($param['arr_step']['export_step_id'] == 0 ? NULL : FILE_APPEND)
								);
							$result['import_error_count'] = $param['import_error_count']  + count($data['error']);

						} else {
							$result['import_error_count'] = $param['import_error_count'];
						}

					} 

					// сохраняем шаги
					$result['fields_all_count'] = $ob_import_CSV->GetAllItemsCount($param['url_data_file']);
					$result['fields_count'] = $data['fields_count'];
					$result['step_id'] = $data['step_id'];
					$result['status'] = true;
					
					// если не дошли до конца, прогресс бар
					if ($result['fields_count'] < $result['fields_all_count']) {
						\CAdminMessage::ShowMessage(array(
							"MESSAGE" => Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT') 
							. ' ' . $result['fields_count'] . ' ' . Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT_IZ') 
							. ' ' .  $result['fields_all_count'],
							"DETAILS" => "#PROGRESS_BAR#",
							"HTML" => true,
							"TYPE" => "PROGRESS",
							"PROGRESS_TOTAL" => $result['fields_all_count'],
							"PROGRESS_VALUE" => $result['fields_count'],
						));
					} else {
						if ($result['import_error_count'] > 0) {
							$result['status'] = false;
							\CAdminMessage::ShowMessage([
								'MESSAGE' => str_replace('#count#', $result['import_error_count'], Loc::getMessage('KO_EXPORTIMPORT_ERROR_IMPORT_FILE'))
								. '<a href="/upload/tmp_KO_exportimport/ImportLog.txt" target="_blank">ImportLog.txt</a>',
								'TYPE' => 'ERROR',
								'HTML' => true
							]);
						}
						\CAdminMessage::ShowMessage([
							'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_FINISH_IMPORT'), 
							'TYPE' => 'OK',
							'HTML' => true
						]);
					}

				}
			} else {
				\CAdminMessage::ShowMessage(Loc::getMessage('KO_EXPORTIMPORT_ERROR_EMPTY'));
			}
			$result['text'] = ob_get_clean();
		}
		return $result;
	}

	

	function GetUserEntity() {

		$method = $this->request->getRequestMethod();
		$result = [];

		// Сохраняем POST
		$hl_id = (int) $this->request->getPost('hl_id');
		if ($hl_id > 0) {
			$res = \ko\Exportimport\Helper::GetUserEntity($hl_id);
		}
		if (!empty($res)) {
			foreach ($res as $value) {
				$result[$value['FIELD_NAME']] = iconv(LANG_CHARSET, "UTF-8//IGNORE", $value['langs']['ru']['EDIT_FORM_LABEL'] . ' [<b>' . $value['FIELD_NAME'] . '</b>] (' . $value['ID'] . ')');
			}
		}
		return $result;
	}
	
	// смена HL при импорте данных,
	// соответствие полей из Highload-блока полям из файла
	function GetUserEntityImport() {

		if ($this->request->getPost('type_import') == 8 || $this->request->getPost('type_import') == 13) {
			$result = $this->ProvFileCSV();
		} 
		// elseif ($this->request->getPost('type_import') == 6) {
		// 	$result = $this->ProvFileJSON();
		// }
		
		if ($result['status'] === false) {
			return ['status' => false, 'text' => $result['text']];
		} else {
			$arr_import = $result['arr'];
		}
		
		if (is_array($arr_import)) {
			$str = '<table>';
			$str .= iconv(LANG_CHARSET, "UTF-8//IGNORE", GetMessage('KO_EXPORTIMPORT_GETUSERENTITYIMPORT_ZAG'));
			$arr = $this->GetUserEntity();
			foreach ($arr as $key => $value) {
				$str .= '<tr>';
				$str .= '<td class="adm-detail-content-cell-l">' . $value . ': </td>'
					. '<td><select name="FIELDS[' . $key . ']">'
					. '<option value=""></option>';
				foreach ($arr_import['fields_name'] as $v_imp) {
					$str .= '<option value="' . $v_imp . '" ' . ($key == $v_imp ? 'selected' : '') . '>' . $v_imp . '</option>';
				}
				$str .= '</select></td>';
				$str .= '</tr>';
			}
			$str .= '</table>';
		} else {
			$str = $arr_import;
		}

		return ['status' => true, 'text' => $str];
	}
	
	// проверяем наличие ключей
	function ProverkaFields($arr) {
		if (is_array($arr)) {
			if (!array_key_exists('fields_name', $arr)) {
				throw new SystemException(str_replace('#key#', 'fields_name', Loc::getMessage('KO_EXPORTIMPORT_ERROR_NOT_KEY')));
			}
			if (!array_key_exists('items_all_count', $arr)) {
				throw new SystemException(str_replace('#key#', 'items_all_count', Loc::getMessage('KO_EXPORTIMPORT_ERROR_NOT_KEY')));
			}
			if (!array_key_exists('items', $arr)) {
				throw new SystemException(str_replace('#key#', 'items', Loc::getMessage('KO_EXPORTIMPORT_ERROR_NOT_KEY')));
			}
			if ($arr['items_all_count'] != count($arr['items'])) {
				throw new SystemException(Loc::getMessage('KO_EXPORTIMPORT_ERROR_COUNT'));
			}
			return true;
		}
		return false;
	}
	
	// возвращает HTML
	function GetKey() {
		
		if ($this->request->getPost('export_type') == 'export_hl') {
			$str = '<div class="adm-info-message">';
			$str .= '<p>' . iconv(LANG_CHARSET, "UTF-8//IGNORE", Loc::getMessage('KO_EXPORTIMPORT_ZAGLUSHKA')) . '</p>';
			$str .= '</div>';
			return ['status' => false, 'text' => $str];
		}
		
		$str = '';
		if ($this->request->getPost('type_import') == 8 || $this->request->getPost('type_import') == 13) {
			$result = $this->ProvFileCSV();
		} 

		// elseif ($this->request->getPost('type_import') == 6 || $this->request->getPost('type_import') == 13) {
		// 	$result = $this->ProvFileJSON();
		// }

		if ($result['status'] == false) {
			return ['status' => false, 'text' => $result['text']];
		}
		
		if (is_array($result['arr'])) {
			$str =  '<select name="import_key">'
					. '<option value=""></option>';
			foreach ($result['arr']['fields_name'] as $v_imp) {
				$str .= '<option value="' . $v_imp . '" >' . $v_imp . '</option>';
			}
			$str .= '</select>';
		}
		return ['status' => true, 'text' => $str];
	}
	
	
	// проверяет наличие CSV и возврщает первую строку(поля)
	function ProvFileCSV() {

		$filename = iconv(mb_internal_encoding(), LANG_CHARSET . "//IGNORE", $this->request->getPost('url_data_file'));

		// Если нет файла для ипорта
		if (!file_exists(\Bitrix\Main\Application::getDocumentRoot() . $filename)) {
			ob_start();
			\CAdminMessage::ShowMessage(iconv(LANG_CHARSET, "UTF-8//IGNORE", GetMessage('KO_EXPORTIMPORT_ERROR_GETUSERENTITYIMPORT')));
			return ['status' => false, 'text' => ob_get_clean()];
		} else {
			$arr_import = [];
			if (($handle = fopen(\Bitrix\Main\Application::getDocumentRoot() . $filename, "r")) !== FALSE) {
				$data = fgetcsv($handle, 0, ";");
				$num = count($data);
				for ($c = 0; $c < $num; $c++) {
					$arr_import['fields_name'][$c] = $data[$c];
				}
				fclose($handle);
			}
			return ['status' => true, 'arr' => $arr_import];
		}

	}

	
	

	// экспорт CSV файла всех элемeнтов highload блока
    // function TestObrFormCSV() {
    function ExportHiloadBlockCSV() {

        $method = $this->request->getRequestMethod();
        $ob_ExportCSV = new \ko\Exportimport\ExportCSV();
        $result = [];

        $hl_id = $this->request->getPost('hl_id');
        $hlname = $this->request->getPost('hlname');
        $url_data_file = iconv(mb_internal_encoding(), LANG_CHARSET."//IGNORE", '/upload/'.$hlname.'.csv');

        $export_type = $this->request->getPost('export_type');
        $export_count_row = (int) $this->request->getPost('export_count_row'); // число строк кода
        $export_step_id = (int) $this->request->getPost('export_step_id');
        $export_select = (array) $this->request->getpost('export_userentity'); 

        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($url_data_file)."\r\n", FILE_APPEND);


        $config_csv = [
            'delimiter' => $this->request->getPost('delimiter'),
            'enclosure' => $this->request->getPost('enclosure'),
            'export_coding' => $this->request->getPost('export_coding'),
            'delimiter_m' => $this->request->getPost('delimiter_m')
        ];

        // Если все норм работаем
        if ($method == 'POST') {
            ob_start();
            if (!empty($url_data_file) && $hl_id > 0 && !empty($export_type) && check_bitrix_sessid() && $export_count_row > 0) {
                	
                if ($export_type == 'export_data') {

                    $arr_step['limit'] = $export_count_row;
                    $arr_step['step_id'] = $export_step_id;
                    $data = $ob_ExportCSV->GetHlData($hl_id, $arr_step, $export_select); 

                    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($data)."\r\n", FILE_APPEND);

                    // сохраняем шаги
                    $result['fields_all_count'] = $data['fields_all_count']; // общее число строк в таблице
                    $result['fields_count'] = $data['fields_count']; // скольк строк было экспортировано
                    $result['step_id'] = $data['step_id']; // ID последней записи

                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($result['fields_all_count'])."\r\n", FILE_APPEND);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($result['fields_count'])."\r\n", FILE_APPEND);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($result['step_id'])."\r\n", FILE_APPEND);

                    // если не дошли до конца, прогресс бар
                    if ($data['fields_count'] < $data['fields_all_count']) {
                        \CAdminMessage::ShowMessage(array(
                            "MESSAGE" => Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR'),
                            "DETAILS" => "#PROGRESS_BAR#",
                            "HTML" => true,
                            "TYPE" => "PROGRESS",
                            "PROGRESS_TOTAL" => $data['fields_all_count'],
                            "PROGRESS_VALUE" => $data['fields_count'],
                        ));
                    } else {
                        \CAdminMessage::ShowMessage([
                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_ERROR_FILE_SAVE') . '<a href="' . $url_data_file . '">' . $url_data_file . '</a>'
                            . '<br>' . Loc::getMessage('KO_EXPORTIMPORT_ERROR_FILE_SAVE_SEND')
                            // . '<input type="text" value="' . Option::get("ko.exportimport", "export_send_email") . '"> '
                            // . '<input type="submit" id="send_email" data-file="' . base64_encode($url_data_file) . '" data-hl_id="' . $hl_id . '"'
                            // . ' value="' . Loc::getMessage('KO_EXPORTIMPORT_ERROR_FILE_SAVE_SEND_BUTTON') . '" class="adm-btn-save" >'
                            . '<br><div id="KO_exportimport_result"></div>',
                            'TYPE' => 'OK',
                            'HTML' => true
                        ]);
                    }
                    // Сохраняем результат
                    $data['step_id'] = $export_step_id;
                    $res_save_file = $ob_ExportCSV->SaveCSV([
                        'type' => 'export_data',
                        'url_data_file' => $url_data_file,
                        'data' => $data,
                        'config_csv' => $config_csv
                    ]);
                    if ($res_save_file) {
                        
                    } else {
                        // Завершаем запросы
                        $result['step_id'] = $data['fields_all_count'];
                        \CAdminMessage::ShowMessage([
                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_ERROR_FILE_SAVE_ERROR'),
                            'TYPE' => 'ERROR',
                            'HTML' => true
                        ]);
                    }
                }


            } else {
                \CAdminMessage::ShowMessage(Loc::getMessage('KO_EXPORTIMPORT_ERROR_EMPTY'));

            }
            $result['text'] = ob_get_clean();
        }
        return $result;
    }



   

	// экспорт CSV файла с изменеными элементами и логом событий
    function ExportDataORM() {

        $method = $this->request->getRequestMethod();
        $ob_ExportCSV = new \ko\Exportimport\ExportCSV();
        $result = [];

        $hl_id = $this->request->getPost('hl_id');
        $hlname = $this->request->getPost('hlname');
        $url_data_file = iconv(mb_internal_encoding(), LANG_CHARSET."//IGNORE", '/upload/'.$hlname.'_log.csv');

        $export_type = $this->request->getPost('export_type');
        $export_count_row = (int) $this->request->getPost('export_count_row'); // число строк кода
        $export_step_id = (int) $this->request->getPost('export_step_id');
        $export_select = (array) $this->request->getpost('export_userentity'); 

        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($url_data_file)."\r\n", FILE_APPEND);


        $config_csv = [
            'delimiter' => $this->request->getPost('delimiter'),
            'enclosure' => $this->request->getPost('enclosure'),
            'export_coding' => $this->request->getPost('export_coding'),
            'delimiter_m' => $this->request->getPost('delimiter_m')
        ];

        // Если все норм работаем
        if ($method == 'POST') {
            ob_start();
            if (!empty($url_data_file) && $hl_id > 0 && !empty($export_type) && check_bitrix_sessid() && $export_count_row > 0) {
                	
            	if ($export_type == 'export_data') {

            		$arr_step['limit'] = $export_count_row;
			        // список полей HL блока
			        array_push($export_select, 'ADDED'); 
			        array_push($export_select, 'UPDETED'); 
			        array_push($export_select, 'DELETED'); 
			        array_push($export_select, 'LOG'); 

			        $data = $ob_ExportCSV->GetORMData($hlname, $export_select); // получаем списко записей
			        $result['fields_all_count'] = $data['fields_all_count']; // общее число строк в таблице логов highload блока
                    $result['step_id'] = $data['step_id']; // ID последней записи

                    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($data)."\r\n", FILE_APPEND);

			        $affected_elements = '';
			        if($data['deleted']) {
			        	$message = Loc::getMessage('KO_EXPORTIMPORT_DELETED_ELEMENTS');
			        	$affected_elements .= '<br>'.$message.' '.$hlname.': '.$data['deleted'];
			        }
			        if($data['updated']) {
			        	$message = Loc::getMessage('KO_EXPORTIMPORT_UPDATED_ELEMENTS');
			        	$affected_elements .= '<br>'.$message.' '.$hlname.': '.$data['updated'];
			        }
			        if($data['added']){
			        	$message = Loc::getMessage('KO_EXPORTIMPORT_ADDED_ELEMENTS');
			        	$affected_elements .= '<br>'.$message.' '.$hlname.': '.$data['added'];
			        }

                    if(!empty($data['fields_all_count'])){
                   		 \CAdminMessage::ShowMessage([
                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_FILE_SAVE') . '<a href="' . $url_data_file . '">' . $url_data_file . '</a>'
                            . '<br>' . Loc::getMessage('KO_EXPORTIMPORT_FILE_SAVE_SEND')
                            // . '<input type="text" value="' . Option::get("ko.exportimport", "export_send_email") . '"> '
                            // . '<input type="submit" id="send_email" data-file="' . base64_encode($url_data_file) . '" data-hl_id="' . $hl_id . '"'
                            // . ' value="' . Loc::getMessage('KO_EXPORTIMPORT_FILE_SAVE_SEND_BUTTON') . '" class="adm-btn-save" >'
                            . $affected_elements
                            . '<br><div id="KO_exportimport_result"></div>',
                            'TYPE' => 'OK',
                            'HTML' => true
                        ]);

                   		// параметры
						$param = [
						    'type' => 'export_data',
						    'url_data_file' => $url_data_file,
						    'step_id' => $export_step_id,
						    'config_csv' => $config_csv,
						    'hl_id'=>$hl_id,
						    'elements'=> $data['elements'],
						    'hlfields'=> $export_select,
						    'fields_all_count'=>$data['fields_all_count']
						];

	                    // Сохраняем результат в файл
		                $res_save_file = $ob_ExportCSV->SaveORMtoCSV($param);
		                if ($res_save_file) {
		                        
		                }else {
		                        // Завершаем запросы
		                        // $result['step_id'] = $data['fields_all_count'];
		                        \CAdminMessage::ShowMessage([
		                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_ERROR_FILE_SAVE_ERROR'),
		                            'TYPE' => 'ERROR',
		                            'HTML' => true
		                        ]);
		                }
                   		    
                   }else{
                   	 	// здесь выводим сообщение логов по данному highload блоку в базе нет подключите логирование
                   		$path = '/bitrix/admin/settings.php?mid='.$this->module_id.'&amp;lang=ru';
                   		\CAdminMessage::ShowMessage([
                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_NO_RECORDS')
                            . '<br>' . Loc::getMessage('KO_EXPORTIMPORT_RECOMMENDATION')
                            .' <a href="'.$path.'">Настройки</a>'
                            . '<br><div id="KO_exportimport_result"></div>',
                            'TYPE' => 'OK',
                            'HTML' => true
                        ]);
                   }
	           }

            } else {
                \CAdminMessage::ShowMessage(Loc::getMessage('KO_EXPORTIMPORT_ERROR_EMPTY'));

            }
            $result['text'] = ob_get_clean();
        }
        return $result;
    }


    // импорт CSV файла с изменеными элементами
    function ImportDataORM(){
    	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize('TestImportDataORM')."\r\n", FILE_APPEND);

    	$method = $this->request->getRequestMethod();
    	$ob_import_CSV = new \ko\Exportimport\ImportCSV();
        $result = [];

        // Сохраняем POST
        $param = [];
        $param['url_data_file'] = iconv(mb_internal_encoding(), LANG_CHARSET . "//IGNORE", $this->request->getPost('url_data_file'));
        $param['hl_id'] = (int) $this->request->getPost('hl_id_data');
        $param['hlname'] = $this->request->getPost('hlname');

        $param['export_type'] = $this->request->getPost('export_type');

        $param['export_count_row'] = (int) $this->request->getPost('export_count_row'); // число строк кода
        $param['import_error_count'] = (int) $this->request->getPost('import_error_count'); // Свойство "items_all_count" не совпадает с реальным количеством записей "items
        $param['export_coding'] = $this->request->getPost('export_coding');

        $param['arr_step']['export_step_id'] = (int) $this->request->getPost('export_step_id'); // шаг 
        $param['arr_step']['FIELDS'] = (array) $this->request->getpost('FIELDS');
        // $param['import_deleted'] = (int) $this->request->getPost('import_deleted');
        // $param['import_deleted'] = 0;

        $param['CSV']['delimiter'] = $this->request->getpost('delimiter');
        $param['CSV']['enclosure'] = $this->request->getpost('enclosure');
        $param['CSV']['delimiter_m'] = $this->request->getpost('delimiter_m');
        $param['import_key'] = $this->request->getpost('import_key'); // import_key import_cluch
        $param['add_missing'] = (int) $this->request->getpost('add_missing');

        // Если все норм работаем
        if ($method == 'POST') {
            ob_start();
            
            if (!empty($param['import_key']) && !empty($param['url_data_file']) && $param['hl_id'] > 0 && !empty($param['export_type']) && check_bitrix_sessid() && $param['export_count_row'] > 0) {

            		if ($param['export_type'] == 'export_data') {
                    
	                    // проверяет наличие CSV и возврщает первую строку(поля)
	                    $arr_import = $this->ProvFileCSV();  

	                    // возвращаем имена полей и их параметры HL
	                    foreach (\ko\Exportimport\Helper::GetUserEntity($param['hl_id']) as $field) {
	                        $param['arr_step']['FIELDS_TYPE'][$field['FIELD_NAME']]['USER_TYPE_ID'] = $field['USER_TYPE_ID'];
	                        $param['arr_step']['FIELDS_TYPE'][$field['FIELD_NAME']]['MULTIPLE'] = $field['MULTIPLE'];
	                    }


	                    if ($arr_import['status'] === true) {

	                        $param['data'] = $arr_import['arr'];
	                        $data = $ob_import_CSV->ParseDataCSVReport($param); 

	                        // сохраняем шаги
	                        $result['fields_all_count'] = $ob_import_CSV->GetAllItemsCount($param['url_data_file']);
	                        $result['fields_count'] = $data['fields_count'];
	                        $result['step_id'] = $data['step_id'];
	                        $result['status'] = true;

	                        // сообщение после загрузки файла с логами
	                        $affected_elements = '';

	                        if($data['reports']['deleted']) {
	                            $message = Loc::getMessage('KO_EXPORTIMPORT_DELETED_ELEMENTS');
	                            $affected_elements .= '<br>'.$message.' '.$param['hlname'].': '.$data['reports']['deleted'];
	                        }
	                        if($data['reports']['updated']) {
	                            $message = Loc::getMessage('KO_EXPORTIMPORT_UPDATED_ELEMENTS');
	                            $affected_elements .= '<br>'.$message.' '.$param['hlname'].': '.$data['reports']['updated'];
	                        }
	                        if($data['reports']['added']){
	                            $message = Loc::getMessage('KO_EXPORTIMPORT_ADDED_ELEMENTS');
	                            $affected_elements .= '<br>'.$message.' '.$param['hlname'].': '.$data['reports']['added'];
	                        }

	                       $added_elementes = '';

	                       file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($data['elements'])."\r\n", FILE_APPEND);
	                       if(!empty($data['elements'])){
	                       		if($param['add_missing']){
	                       			$message = ' был создан новый элемент, так как его не было в highload-блоке';
	                       		}else{
	                       			$message = ' был пропущен элемент, так как его не было в highload-блоке';
	                       		}
	                       		foreach($data['elements'] as $element) {
									if(isset($element['uf_xml_id'])){
	                       			 	$added_elementes .= '<br> во время импорта '.$message.' UF_XML_ID: '.$element['uf_xml_id'];
									}else{
										$added_elementes .= '<br> во время импорта '.$message.' LOG: '.$element['log'];
									}
	                       		}

	                       }

	                        // если не дошли до конца, прогресс бар
	                        if ($result['fields_count'] < $result['fields_all_count']) {
	                            \CAdminMessage::ShowMessage(array(
	                                "MESSAGE" => Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT') 
	                                . ' ' . $result['fields_count'] . ' ' . Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT_IZ') 
	                                . ' ' .  $result['fields_all_count'],
	                                "DETAILS" => "#PROGRESS_BAR#",
	                                "HTML" => true,
	                                "TYPE" => "PROGRESS",
	                                "PROGRESS_TOTAL" => $result['fields_all_count'],
	                                "PROGRESS_VALUE" => $result['fields_count'],
	                            ));

	                            \CAdminMessage::ShowMessage(array(
	                                "MESSAGE" => Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT') 
	                                . ' ' . $result['fields_count'] . ' ' . Loc::getMessage('KO_EXPORTIMPORT_PROGRESS_BAR_IMPORT_IZ') 
	                                . ' ' .  $result['fields_all_count'],
	                                "DETAILS" => "#PROGRESS_BAR#",
	                                "HTML" => true,
	                                "TYPE" => "PROGRESS",
	                                "PROGRESS_TOTAL" => $result['fields_all_count'],
	                                "PROGRESS_VALUE" => $result['fields_count'],
	                            ));
	                        
	                        } else {
	                            
	                            if ($result['import_error_count'] > 0) {
	                                $result['status'] = false;
	                                \CAdminMessage::ShowMessage([
	                                    'MESSAGE' => str_replace('#count#', $result['import_error_count'], Loc::getMessage('KO_EXPORTIMPORT_ERROR_IMPORT_FILE'))
	                                    . '<a href="/upload/tmp_KO_exportimport/ImportLog.txt" target="_blank">ImportLog.txt</a>'
	                                    .$affected_elements
	                                    .'<br><div id="KO_exportimport_result"></div>',
	                                    'TYPE' => 'ERROR',
	                                    'HTML' => true
	                                ]);
	                            }


	                         \CAdminMessage::ShowMessage([
	                            'MESSAGE' => Loc::getMessage('KO_EXPORTIMPORT_FINISH_IMPORT')
	                            . '<br>' . Loc::getMessage('KO_EXPORTIMPORT_FINISH_IMPORT')
	                            .$affected_elements
	                            .$added_elementes
	                            .'<br><div id="KO_exportimport_result"></div>',
	                            'TYPE' => 'OK',
	                            'HTML' => true
	                        ]);

                        }

                    }
                 }
            }else{
        	  \CAdminMessage::ShowMessage(Loc::getMessage('KO_EXPORTIMPORT_ERROR_EMPTY'));
        	}

        	$result['text'] = ob_get_clean();
        }

        return $result;
    }



}
