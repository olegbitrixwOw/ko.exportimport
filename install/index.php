<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;
Loc::loadMessages(__FILE__);

Class KO_exportimport extends CModule {

	var $MODULE_ID = 'ko.exportimport';	// идентификатор модуля
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $NEED_MAIN_VERSION = '15.0.0';
	public $NEED_MODULES = array('main');
	public $COMPONENT_NAME = 'layroutecardyago'; 

	function __construct() {
		$arModuleVersion = array();
		include(__DIR__ . "/version.php");

		$this->MODULE_ID = 'ko.exportimport';
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::GetMessage("KO_EXPORTIMPORT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::GetMessage("KO_EXPORTIMPORT_MODULE_DESC");

		$this->PARTNER_NAME = Loc::GetMessage("KO_EXPORTIMPORT_PARTNER_NAME");
		$this->PARTNER_URI = Loc::GetMessage("KO_EXPORTIMPORT_PARTNER_URI");

		$this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
		$this->MODULE_GROUP_RIGHTS = 'Y';
	}
 
	/**
	 * Определяем место размещения модуля
	 * @param boolean $notDocumentRoot
	 * @return string
	 */
	public function GetPath($notDocumentRoot = false) {
		if ($notDocumentRoot) {
			return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
		} else {
			return dirname(__DIR__);
		}
	}

	function InstallFiles($arParams = array()) {
		// Bitrix\Main\Diag\Debug::writeToFile($this->GetPath(), '', 'logs.txt');

		// Компоненты
		if (is_dir($p = $this->GetPath() . '/install/components')) {
			if ($dir = opendir($p)) {
				while (false !== $item = readdir($dir)) {
					if ($item == '..' || $item == '.')
						continue;
					CopyDirFiles($p . '/' . $item, $_SERVER['DOCUMENT_ROOT'] . '/local/components/' . $item, $ReWrite = True, $Recursive = True);
				}
				closedir($dir);
			}
		}
		// Админка
		if (is_dir($p = $this->GetPath() . '/install/admin')) {
			CopyDirFiles($p, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', $ReWrite = True, $Recursive = True);
		}
		// JS
		if (is_dir($p = $this->GetPath() . '/install/js')) {
			CopyDirFiles($p, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', $ReWrite = True, $Recursive = True);
		}
		// CSS
		if (is_dir($p = $this->GetPath() . '/install/panel')) {
			CopyDirFiles($p, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/panel', $ReWrite = True, $Recursive = True);
		}
		
		return true;
	}

	function UnInstallFiles() {
		// Компоненты
		if (is_dir($p = $this->GetPath() . '/install/components')) {
			if ($dir = opendir($p)) {
				while (false !== $item = readdir($dir)) {
					if ($item == '..' || $item == '.' || !is_dir($p0 = $p . '/' . $item))
						continue;

					\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/components/' . $item . '/' . $this->COMPONENT_NAME);
				}
				closedir($dir);
			}
		}
		// Админка

		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ExportHighLoadBlock.php');
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ExportReport.php');

		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ImportJSON.php');
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ImportCSV.php');
		
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ImportReport.php');

		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/ko_exportimport_ajax.php');

		// JS
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ko.exportimport');
		
		// CSS
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/panel/ko.exportimport');

		return true;
	}

	function InstallDB(){

		Loader::includeModule($this->MODULE_ID);

		if(!Application::getConnection(\ko\Exportimport\HlbookTable::getConnectionName())->isTableExists(Base::getInstance('\ko\Exportimport\HlbookTable')->getDBTableName()))
        {
            Base::getInstance('\ko\Exportimport\HlbookTable')->createDbTable();
        }


    }
	
	// function UnInstallDB($arParams = array()) {
	function UnInstallDB() {

		Loader::includeModule($this->MODULE_ID);

        Application::getConnection(\ko\Exportimport\HlbookTable::getConnectionName())->queryExecute('drop table if exists '.Base::getInstance('\ko\Exportimport\HlbookTable')->getDBTableName());

		// Удаляем настройки нашего модуля
		\Bitrix\Main\Config\Option::delete($this->MODULE_ID);
		return true;
	}

	function DoInstall() {

		global $APPLICATION;
		// Проверка установленных модулей и их версий
		if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES) && strlen($this->NEED_MAIN_VERSION) >= 0) {
			foreach ($this->NEED_MODULES as $module) {
				if (!ModuleManager::isModuleInstalled($module)) {
					$APPLICATION->ThrowException(Loc::GetMessage('KO_EXPORTIMPORT_NEED_MODULES', array('#MODULE#' => $module)));
					return false;
				}
			}
			if (CheckVersion(ModuleManager::getVersion('main'), $this->NEED_MAIN_VERSION)) {
				
				// регистрируем модуль
				\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

				// устанавливаем базу данных
				$this->InstallDB();

				// устанаваливаем файлы 
				$this->InstallFiles();



			} else {
				$APPLICATION->ThrowException(Loc::GetMessage('KO_EXPORTIMPORT_NEED_RIGHT_VER', array('#NEED#' => $this->NEED_MAIN_VERSION)));
				return false;
			}
		} else {
			$APPLICATION->ThrowException(Loc::GetMessage('KO_EXPORTIMPORT_NEED_ERROR'));
			return false;
		}
	}

	function DoUninstall() {
		global $APPLICATION;
		\Bitrix\Main\Loader::includeModule($this->MODULE_ID);
		$this->UnInstallDB();
		ModuleManager::unRegisterModule($this->MODULE_ID);
		$this->UnInstallFiles();
		
	} 

	

}
