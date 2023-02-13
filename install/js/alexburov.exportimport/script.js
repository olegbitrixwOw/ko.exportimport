$(document).ready(function () {
	// Отправка файла на email
	$('#ko_exportimport_result_AJAX').on('click', '#send_email', function(event) {
		// Остановим всплытие события вверх по дереву DOM
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		// Остановим действие браузера по умолчанию
		event.preventDefault ? event.preventDefault() : (event.returnValue = false);
		
		var email = $(this).prev('input').val(),
			file = $(this).attr('data-file'),
			hl_id = $(this).attr('data-hl_id');
		
		$.post('/bitrix/admin/ko_exportimport_ajax.php',
			{
				type: 1,
				email: email,
				file: file,
				hl_id: hl_id
			}
		).done(function (data) {
			var result = jQuery.parseJSON(data);
			// Если нет ошибки
			if (result.status) {
				$('#ko_exportimport_result').html(' <span class="notetext">' + result.text + '</span>')
			} else {
				console.log(data);
			}
		})
		.fail(function () {
			alert('error');
		});
		
	});
	
	// Отправка формы
	$('#ko_exportimport_submit').click(function(event) {
		// Остановим всплытие события вверх по дереву DOM
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		// Остановим действие браузера по умолчанию
		event.preventDefault ? event.preventDefault() : (event.returnValue = false);


		
		var form = $('#ko_exportimport_form');
		$.post(form.attr('action'), $(form).serialize()).done(function (data) {

			var result = jQuery.parseJSON(data);
			// Если нет ошибки
			if (result.status) {
				$('#ko_exportimport_result_AJAX').html(result.text);
				if (result.step_id) {
					$('#export_step_id').val(result.step_id);
				} else {
					$('#export_step_id').val(0);
				}
				$('#import_error_count').val(result.import_error_count);
				if (parseInt(result.fields_count) < parseInt(result.fields_all_count)) {
					$('#ko_exportimport_submit').prop('disabled',false);
					$('#ko_exportimport_submit').click();
					$('#ko_exportimport_submit').prop('disabled',true);
				} else {
					$('#import_error_count').val(0);
					$('#export_step_id').val(0);
					$('#ko_exportimport_submit').prop('disabled',false);
				}
			} else {
				console.log(data);
			}

		})
		.fail(function () {
			alert('error');
		});
	});
	
	// Смена HL
	$('#hl_id').change(function(event) {
		// Остановим всплытие события вверх по дереву DOM
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		// Остановим действие браузера по умолчанию
		event.preventDefault ? event.preventDefault() : (event.returnValue = false);

		if($(this).find('option:selected').attr('data-hlname')){
			var hlname = $(this).find('option:selected').attr('data-hlname');
			$('#hlname').val(hlname);
		}
		
		var hl_id = $(this).val();
		$.post('/bitrix/admin/ko_exportimport_ajax.php',
			{
				type: 3,
				hl_id: hl_id
			}
		).done(function (data) {

			var result = jQuery.parseJSON(data);
			// Если нет ошибки
			if (result.status) {
				var str = '';
				$.each(result.hl_id, function(key, value){
					str +='<input type="checkbox" id="export_userentity_' + key + '" value="' + key + '" name="export_userentity[' + key + ']" checked="checked" >'
					+ '<label for="export_userentity_' + key + '">' + value + '</label><br>';

				});
				$('#export_userentity').html(str);
				$('#export_step_id').val(0);		// Сбросим шаг на 0
			} else {
				console.log(data);
			}

		})
		.fail(function () {
			alert('error');
		});
	});
	
	// Смена HL при импорте данных
	$('#hl_id_importl_data').change(function(event) {
		// Остановим всплытие события вверх по дереву DOM
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		// Остановим действие браузера по умолчанию
		event.preventDefault ? event.preventDefault() : (event.returnValue = false);
			
		var hl_id = $(this).val();

		if($('#hl_id_data')){
			$('#hl_id_data').val(hl_id);
		}

		$('#ko_exportimport_result_AJAX').html('');
		$.post('/bitrix/admin/ko_exportimport_ajax.php',
			{
				type: 5,
				hl_id: hl_id,
				type_import: $('#ko_exportimport_form input[name=type]').val(),
				url_data_file: $('#url_data_file_exp').val()
			}
		).done(function (data) {


			var result = jQuery.parseJSON(data);

			// Если нет ошибки
			if (result.status) {
				$('#import_comparing').html(result.text);
				$('#export_step_id').val(0);		// Сбросим шаг на 0
			} else {
				$('#ko_exportimport_result_AJAX').html(result.text);
			}

		})
		.fail(function () {
			alert('error');
		});
	});
	
	// Выбор типа импорта
	$('#ko_exportimport_form input[name=export_type]').change(function(){
		if ($(this).val() === 'export_hl') {
			$('.import_hl_data').hide();
			$('.import_hl_structure').show();
			$('#url_data_file_exp').change();
		} else if ($(this).val() === 'export_data') {
			$('.import_hl_structure').hide();
			$('.import_hl_data').show();
			$('#url_data_file_exp').change();
		}
	});
	
	// Выбор файла импорта
	$('#url_data_file_exp').change(function(event){
		// Остановим всплытие события вверх по дереву DOM
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		// Остановим действие браузера по умолчанию
		event.preventDefault ? event.preventDefault() : (event.returnValue = false);
	
		var hl_id = $(this).val();
		$('#ko_exportimport_result_AJAX').html('');
		$('#ko_exportimport_submit').prop('disabled',false);
		
			console.log('hl_id_importl_data');
			console.log($('#export_edit_table input[name=export_type]:checked').val());
			console.log($('#ko_exportimport_form input[name=type]').val());
			console.log($('#url_data_file_exp').val());


			$.post('/bitrix/admin/ko_exportimport_ajax.php',
				{
					type: 7,
					export_type: $('#export_edit_table input[name=export_type]:checked').val(),
					type_import: $('#ko_exportimport_form input[name=type]').val(),
					url_data_file: $('#url_data_file_exp').val()
				}
			).done(function (data) {
				var result = jQuery.parseJSON(data);

			
			console.log(result.text);

			// Если нет ошибки
			if (result.status) {
				$('#import_cluch').html(result.text);
				$('#export_step_id').val(0);		// Сбросим шаг на 0
			} else {
				$('#ko_exportimport_result_AJAX').html(result.text);
				$('#ko_exportimport_submit').prop('disabled',true);
			}

		})
		.fail(function () { 
			alert('error');
		});
	});

});