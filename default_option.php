<?php
use Bitrix\Main\Config\Option;

$KO_exportimport_default_option = array(
	'export_send_email' => Option::get("main", "email_from"),
	'url_data_file' => '/upload'
);
