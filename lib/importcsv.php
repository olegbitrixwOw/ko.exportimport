<?php

namespace ko\Exportimport;
use \Bitrix\Highloadblock as HL;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;

// класс для импорта CSV файлов
class ImportCSV extends Import {
	
	protected $handler = null;
	protected $firststrung = [];

	public function ImportDataCSV(array $param) {
		
		$hldata = HL\HighloadBlockTable::getById($param['hl_id'])->fetch();
		$entity = HL\HighloadBlockTable::compileEntity($hldata);
		$ob_hldata = $entity->getDataClass();
		$res = [];
		
		$this->handler = $this->OpenFileCSV($param['url_data_file']);
		$this->FirstString($this->handler, $param);
		$this->SetOffset($param['arr_step']['export_step_id']);
		
		$final = $param['arr_step']['export_step_id'] + $param['export_count_row'];
		$res['fields_count'] = $final;
		for ($c = $param['arr_step']['export_step_id']; $c < $final; $c++) {

			if (feof($this->handler)) { break; }	// если конец файла прерываемся
			
			if ($data = fgetcsv($this->handler, 0, $param['CSV']['delimiter'], $param['CSV']['enclosure'])) {

				$prep_arr = $this->DataPreparation($data, $param);
				
				// Есть ошибки, запишим
				if (!empty($prep_arr['error'])) {
					$res['error'][$c]['text_error'] = str_replace(
						['#key#', '#prop#'],
						[$c, implode(', ', $prep_arr['error'])], 
						Loc::getMessage('KO_EXPORTIMPORT_ERROR_IMPORT_FILE_FIELD'));
					$res['error'][$c]['item'] = $item;
				}
				
				// Если нет ключа добавляем запись, иначе обновим
				if (empty($param['import_key'])) {
					$result = $ob_hldata::add($prep_arr['item']);
				} else {
					// Пытаемся найти запись
					$row = $ob_hldata::getRow(array(
						'select' => array('ID'),
						'filter' => array('=' . $param['import_key'] => (int)$prep_arr['item'][$param['import_key']])
					));
					if ($row) {
						$result = $ob_hldata::update($row['ID'], $prep_arr['item']);
					} else {
						$result = $ob_hldata::add($prep_arr['item']);
					}
				}
				// Запись результатов
				if (!$result->isSuccess()) {
					$res['error'][$c]['text_error'] = $result->getErrorMessages();
					$res['error'][$c]['item'] = $prep_arr['item'];
				}
			}
			
			// +1 так как заголовки считали мимо цикла
			$res['step_id'] = $c + 1;
			
		}
		
		fclose($this->handler);
		return $res;
			
	}
	
	// открывает CSV
	public function OpenFileCSV($filename) {
		
		if (!file_exists(\Bitrix\Main\Application::getDocumentRoot() . $filename)) {
			throw new SystemException( Loc::getMessage('KO_EXPORTIMPORT_ERROR_GETUSERENTITYIMPORT'));
		} else {
			if (($handle = fopen(\Bitrix\Main\Application::getDocumentRoot() . $filename, "r")) !== FALSE) {
				return $handle;
			}
		}
		
	}
	
	// пропускает нужное кол-во строк
	public function SetOffset($line) {
		if (!$this->handler) { throw new SystemException("Invalid file pointer"); }

		while (!feof($this->handler) && $line--) {
			fgets($this->handler);
		}
	}
	
	// получаем ключи(колонки) из 1 строки CSV
	protected function FirstString($handle, array $param) {
		if ($data = fgetcsv($handle, 0, $param['CSV']['delimiter'], $param['CSV']['enclosure'])) {
			foreach ($data as $key => $value) {
				$this->firststrung[$value] = $key;
			}
		} else {
			throw new SystemException("Invalid file pointer FirstString");
		}
		
	}
	
	// cчитает кол-во строк в CSV
	public function GetAllItemsCount($filename) {
		$handle = $this->OpenFileCSV($filename);
		$i = -1;	// первая строка заголовки
		while (!feof($handle)) {
			fgets($handle);
			$i++;
		}
		return $i;
	}

	// готовит строку для записи в Битрикс
	protected function DataPreparation(array $item, array $param) {
		
		$error = [];
		foreach ($item as $k_item => $v_item) {
			// Обработка файла
			if (!empty($item[$k_item]) && $param['arr_step']['FIELDS_TYPE'][$k_item]['USER_TYPE_ID'] == 'file') {
				if (is_array($item[$k_item])) {
					$vr_arr = [];
					foreach ($item[$k_item] as $v_file) {
						$vr_mak = \CFile::MakeFileArray($v_file);
						if ($vr_mak == NULL) {
							$error[] = $v_file;
						} else {
							$vr_arr[] = $vr_mak;
						}
					}
					$item[$k_item] = $vr_arr;
				} else {
					$item[$k_item] = \CFile::MakeFileArray($v_item);
					if ($item[$k_item] == NULL) {
						$error[] = $v_item;
					}
				}
			}
		}
		// добавляем ID, если есть ключ для обновления
		if (!empty($param['import_key'])) {
			$param['arr_step']['FIELDS']['ID'] = $param['import_key'];
		}
		
		// Возвращаем нужные поля
		foreach ($param['arr_step']['FIELDS'] as $key => $field) {
			$value = $item[$this->firststrung[$field]];
			if(is_string($value)) {
				$value = $this->SetCoding($value, $param);
				if (strpos($value, $param['CSV']['delimiter_m']) !== false) {
					$value = $this->StringToArray($value, $param);
					
				}
			}
			// Если у поля тип множественное, нужен массив
			if (is_string($value) && $param['arr_step']['FIELDS_TYPE'][$field]['MULTIPLE'] == 'Y') {
				$new_item[$key][] = $value;
			} else {
				$new_item[$key] = $value;
			}
			
		}
		
		return ['item' => $new_item, 'error' => $error];
	}
	
	// ставит кодировку
	protected function SetCoding($string, array $param) {
		$str = iconv($param['export_coding'], LANG_CHARSET . "//IGNORE", $string);
		if ($str === false) {
			throw new SystemException("Error iconv to " . $string);
		} else {
			return $str;
		}
	} 
	
	protected function StringToArray($string, array $param) {
		return explode($param['CSV']['delimiter_m'], $string);
	}


	// вернет массив готовый к записи в HL из файла Report CSV
	// поле LOG пойдет в сообщение
	public function ParseDataCSVReport($param){

		$hldata = HL\HighloadBlockTable::getById($param['hl_id'])->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hldata);
        $ob_hldata = $entity->getDataClass();

		$res = [];
		$elements = [];
		$reports = [ 
			'added' => 0, 
			'updated '=> 0, 
			'deleted' => 0
		];


		$this->handler = $this->OpenFileCSV($param['url_data_file']);
        $this->FirstString($this->handler, $param);
        $this->SetOffset($param['arr_step']['export_step_id']);

        $final = $param['arr_step']['export_step_id'] + $param['export_count_row'];
        $res['fields_count'] = $final;
        
        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($param['data']['fields_name'])."\r\n", FILE_APPEND);
        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($param['arr_step']['FIELDS'])."\r\n", FILE_APPEND);

        for ($c = $param['arr_step']['export_step_id']; $c < $final; $c++) {

        	if (feof($this->handler)) { break; }    // если конец файла прерываемся

        	if ($data = fgetcsv($this->handler, 0, $param['CSV']['delimiter'], $param['CSV']['enclosure'])) {

        		// поля ADDED DELETED UPDATED
                $added = $data[count($data)-4]; 
                $updated = $data[count($data)-3];
                $deleted = $data[count($data)-2];

                if(!empty($added)){$reports['added']++;}
                if(!empty($updated)){$reports['updated']++;}
                if(!empty($deleted)){$reports['deleted']++;}

                 // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($element)."\r\n", FILE_APPEND);
                 // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($prep_arr['item'])."\r\n", FILE_APPEND);

        		// если выставлен параметр не сохранять удаленные элементы 
                // пропускаем сохранение элемента
                if($deleted == 0){
		               
		                // получаем данные для сохранения в highload блоке
		                $prep_arr = $this->DataReportPreparation($data, $param);

		              	// когда ключ не указан просто создаем записи
		                if (empty($param['import_key'])) {
			                if($param['add_missing']){
			                    $result = $ob_hldata::add($prep_arr['item']);
			                    $element = $this->AddElement($data, $prep_arr, 1);
			                    $elements[] = $element;
			                }else{
								$element = $this->AddElement($data, $prep_arr, 0);
								$elements[] = $element;
		                    }
		                } 
		                else {
		                    // ищем запись
		                    $row = $ob_hldata::getRow(array(
		                        'select' => array('ID'),
		                        'filter' => array('=' . $param['import_key'] => $prep_arr['item'][$param['import_key']])
		                    ));
		                    if ($row) { // если нет записи с таким ключом добавляем запись, иначе обновляем существующий элемент
		                        $result = $ob_hldata::update($row['ID'], $prep_arr['item']);
		                    } else {
		                    	if($param['add_missing']){
		                     	   $result = $ob_hldata::add($prep_arr['item']);
		                     	   $element = $this->AddElement($data, $prep_arr, 1);
		                     	   $elements[] = $element;
		                        }else{
			                        $element = $this->AddElement($data, $prep_arr, 0);
			                        $elements[] = $element;
			                    }
		                    }
		                }
		                // запись результатов
		                if(isset($result)){
		                	if (!$result->isSuccess()) {
			                    $res['error'][$c]['text_error'] = $result->getErrorMessages();
			                    $res['error'][$c]['item'] = $prep_arr['item'];
			                }
		                }
		            	// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($prep_arr['item'])."\r\n", FILE_APPEND);
           		 }
            }

            // +1 так как  заголовки считали мимо цикла
            $res['step_id'] = $c + 1;
        }
        $res['elements'] = $elements;
        $res['reports'] = $reports;
        fclose($this->handler);
        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log_iblock_setlog.txt', serialize($res['elements'])."\r\n", FILE_APPEND);
       return $res;
	}
	protected function AddElement($data, $prep_arr, $add_missing){
		$element = [];
		$element['add_missing'] = $add_missing;
		$element['log'] = $data[count($data)-1];
		if(isset($prep_arr['item']['UF_XML_ID'])){
			$element['uf_xml_id'] = $prep_arr['item']['UF_XML_ID'];
		}
		return $element;
	}

	protected function DataReportPreparation(array $item, array $param) {
	        $error = [];
	        // foreach ($item as $k_item => $v_item) {
	        //   Обработка файла
	        // if (!empty($item[$k_item]) && $param['arr_step']['FIELDS_TYPE'][$k_item]['USER_TYPE_ID'] == 'file') {
	        //         if (is_array($item[$k_item])) {
	        //             $vr_arr = [];
	        //             foreach ($item[$k_item] as $v_file) {
	        //                 $vr_mak = \CFile::MakeFileArray($v_file);
	        //                 if ($vr_mak == NULL) {
	        //                     $error[] = $v_file;
	        //                 } else {
	        //                     $vr_arr[] = $vr_mak;
	        //                 }
	        //             }
	        //             $item[$k_item] = $vr_arr;
	        //         } else {
	        //             $item[$k_item] = \CFile::MakeFileArray($v_item);
	        //             if ($item[$k_item] == NULL) {
	        //                 $error[] = $v_item;
	        //             }
	        //         }
	        //     }
	        // }
	        // добавляем уникальный ключ для обновления
	        if (!empty($param['import_key'])) {
	            $param['arr_step']['FIELDS'][$param['import_key']] = $param['import_key'];
	        }
        
		 	// Возвращаем нужные поля
	        foreach ($param['arr_step']['FIELDS'] as $key => $field) {

	            $value = $item[$this->firststrung[$field]];
	            if(is_string($value)) {
	                $value = $this->SetCoding($value, $param);
	                if (strpos($value, $param['CSV']['delimiter_m']) !== false) {
	                    $value = $this->StringToArray($value, $param);
	                    
	                }
	            }
	            // Если у поля тип множественное, нужен массив
	            if (is_string($value) && $param['arr_step']['FIELDS_TYPE'][$field]['MULTIPLE'] == 'Y') {
	                $new_item[$key][] = $value;
	            } else {
	                $new_item[$key] = $value;
	            }
	            
	        }
	        return ['item' => $new_item, 'error' => $error];
	 }
	
}
