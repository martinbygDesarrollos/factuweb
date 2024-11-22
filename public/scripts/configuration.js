function loadConfiguration(){
	let response = sendPost("loadConfiguration", null);
	if(response.result == 2){
		let list = response.listResult;
		for (let i = list.length - 1; i >= 0; i--) {
			if(list[i].tipo == "BOOLEAN"){
				if(list[i].valor == "SI"){
					$('#' + list[i].variable).prop("checked", true);
				}else if(list[i].valor == "NO"){
					$('#' + list[i].variable).prop("checked", false);
				}
			}else if(list[i].tipo == "LIST"){
				if(list[i].variable == "INDICADORES_FACTURACION_USABLES"){
					let arrayIva = list[i].valor.split(',');
					for(let j = 0; j < arrayIva.length; j++)
						$('#checkIVA' + arrayIva[j]).prop('checked', true);
				}else if(list[i].variable == "PERIODOS_FACTURACION_SERVICIOS"){
					let arrayPeriod = list[i].valor.split(',');
					for(let j = 0; j < arrayPeriod.length; j ++)
						$('#checkPeriod' + arrayPeriod[j]).prop('checked', true);
				}
			}else if(list[i].tipo == "VALUE"){
				$('#' + list[i].variable).val(list[i].valor);
			}
		}
	}
}

function updateEndDate(){
	let newValue = $('#FECHA_DESDE_ACCOUNT_SATE').val();
	let response = sendPost("updateVariableConfiguration", {variable: "FECHA_DESDE_ACCOUNT_SATE", value: newValue});
	showReplyMessage(response.result, response.message, "Modificar fecha 'desde'", null);
}

function updateDeadlineInDays(){
	let newValue = $('#INTERVALO_FECHA_ACCOUNT_SATE').val() || null;
	if(newValue){
		let response = sendPost('updateVariableConfiguration', {variable: "INTERVALO_FECHA_ACCOUNT_SATE", value: newValue});
		showReplyMessage(response.result, response.message, "Intervalo en estado de cuenta", null);
	}else{
		showReplyMessage(1, "El intervalo para las fechas en estado de cuenta no puede ser nulo", "Campo requerido", null);
		$('#INTERVALO_FECHA_ACCOUNT_SATE').val(30);
	}
}

function updateSuffixNameService(){
	let suffix = $('#SUFIJO_NOMBRE_SERVICIO_FACTURA').val();
	let response = sendPost("updateVariableConfiguration", {variable: "SUFIJO_NOMBRE_SERVICIO_FACTURA", value: suffix});
	showReplyMessage(response.result, response.message, "Sufijo nombre servicio", null);
}

function updateSuffixFormatService(){
	let suffix = $('#SUFIJO_FORMATO_SERVICIO_FACTURA').val();
	let response = sendPost("updateVariableConfiguration", {variable: "SUFIJO_FORMATO_SERVICIO_FACTURA", value: suffix});
	showReplyMessage(response.result, response.message, "Sufijo formato servicio", null);
}

function updateExpirationDateSuggestion(){
	let valueSuggestion = $('#SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO').val() || null;
	if(!valueSuggestion)
		valueSuggestion = 0;

	if(valueSuggestion >= 0 && valueSuggestion <= 180){
		let response = sendPost("updateVariableConfiguration", {variable: "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO", value: valueSuggestion});
		showReplyMessage(response.result, response.message, "Sugerencia fecha vencimiento", null);
	}else showReplyMessage(1, "La cantidad de días para calcular la fecha de vencimiento a sugerir debe estar entre 0-180", null);
}

function updateFormatRut(inputValue){
	let response = sendPost("updateVariableConfiguration", {variable: inputValue.id, value: inputValue.value});
	showReplyMessage(response.result, response.message, "Formato modificado", null);
}

function updateConfigurationBoolean(inputVariable){
	let variable = inputVariable.id;
	let value = inputVariable.checked;

	let booleanValue = "NO";
	if(value)
		booleanValue = "SI";

	let response = sendPost("updateVariableConfiguration", {variable: variable, value: booleanValue});
	if(response.result != 2){
		if(value) inputVariable.checked = false;
		else inputVariable.checked = true;
	}
}

function getChecksBillingIndicators(){
	let idsSelecteds = "";
	$("#tbodyBillingIndicators > tr > td > input[type=checkbox]:checked").each(function() {
		let idChecked = $(this).attr('id');
		idsSelecteds +=  idChecked.replace("checkIVA", '') + ",";
	});
	return idsSelecteds.substring(0, idsSelecteds.length - 1);
}

function getChecksPeriods(){
	let periods = "";
	$("#tbodyPeriodInvoice > tr > td > input[type=checkbox]:checked").each(function(){
		let idChecked = $(this).attr('id');
		periods += idChecked.replace("checkPeriod", '') + ",";
	});

	return periods.substring(0, periods.length - 1);
}

function updatePeriodInvoice(){
	let periods = getChecksPeriods();
	let response = sendPost('updateVariableConfiguration', {variable: "PERIODOS_FACTURACION_SERVICIOS", value: periods });
	showReplyMessage(response.result, response.message, "Modificar periodos de facturación", null);
}

function updateBillingIndicators(){
	let newIdsIVA = getChecksBillingIndicators();
	console.log("OPCIONES SELECCIONADAS: " + newIdsIVA)
	let response = sendPost("updateVariableConfiguration", {variable: "INDICADORES_FACTURACION_USABLES", value: newIdsIVA});
	showReplyMessage(response.result, response.message, "Modificar IVAs aceptados", null);
	$("#modalButtonResponse").click(function(){
		$("[name='IndicadoresFacturacion']").click();
	});
}

function showConfig(btnArrow){
	if($('#li' + btnArrow.name).is(':visible')){
		$('#i' +  btnArrow.name).removeClass('fa-chevron-up');
		$('#i' +  btnArrow.name).addClass('fa-chevron-down');
		$('#li' + btnArrow.name).hide();
	}else{
		$('#i' +  btnArrow.name).removeClass('fa-chevron-down');
		$('#i' +  btnArrow.name).addClass('fa-chevron-up');
		$('#li' + btnArrow.name).show();
	}
}

function loadProductsFromDetails(){
	// $('#iconButtonGetDetailSUPER').addClass('turnI');
	mostrarLoader(true)

	sendAsyncPost("loadProductsFromDetails", null)
	.then(function(response){
		showReplyMessage(response.result, response.message, "Obtener artículos", null);
		// $('#iconButtonGetDetailSUPER').removeClass('turnI');
		mostrarLoader(false)

	})
	.catch(function(response){
		// $('#iconButtonGetDetailSUPER').removeClass('turnI');
		mostrarLoader(false)

		showReplyMessage(0, "Ocurrió un error por lo que algunos artículos no fueron obtenidos desde los detalles de los comprobantes emitidos.", "Obtener artículos", null);
	});
}

function loadCustomers(){
	// $('#iconBtnLoadCustomersEfactura').addClass('turnI');
	mostrarLoader(true)

	sendAsyncPost("loadCustomersEfactura", null)
	.then(function(response){
		for (var i = 0; i < response.message.length; i++) {
			console.log(response.message[i]);
		}
		showReplyMessage(response.result, "Todos los resultados se imprimieron en la consola.", "Cargar clientes", null);
		// $('#iconBtnLoadCustomersEfactura').removeClass('turnI');
		mostrarLoader(false)

	})
	.catch(function(response){
		for (var i = 0; i < response.message.length; i++) {
			console.log(response.message[i]);
		}
		// $('#iconBtnLoadCustomersEfactura').addClass('turnI');
		mostrarLoader(false)

		showReplyMessage(0, "Todos los resultados se imprimieron en la consola.", "Cargar clientes", null);
	})
}

function loadDataFirstLogin(){
	// $('#iconButtonUpdateSUPER').addClass('turnI');
	mostrarLoader(true)

	sendAsyncPost("loadDataFirstLogin", null)
	.then(function(response){
		showReplyMessage(response.result, response.message, "Actualizar lista de comprobantes emitidos", null);
		// $('#iconButtonUpdateSUPER').removeClass('turnI');
		mostrarLoader(false)
	})
	.catch(function(response){
		// $('#iconButtonUpdateSUPER').removeClass('turnI');
		mostrarLoader(false)
		showReplyMessage(0, "Ocurrió un error por lo que algunos comprobantes no fueron actualizados", "Actualizar comprobantes", null);
	});
}

function updateSuperVouchers(){
	mostrarLoader(true)
	// $('#btnUpdateVouchersSuperUser').addClass('turnI');
	sendAsyncPost("updateDataVouchersAdmin", null)
	.then(function(response){
		showReplyMessage(response.result, response.message, "Actualizar lista de comprobantes emitidos", null);
		// $('#btnUpdateVouchersSuperUser').removeClass('turnI');
		mostrarLoader(false)
	})
	.catch(function(response){
		// $('#btnUpdateVouchersSuperUser').removeClass('turnI');
		mostrarLoader(false)
		showReplyMessage(0, "Ocurrió un error por lo que algunos comprobantes no fueron actualizados", "Actualizar comprobantes", null);
	});
}

function newInstallation(){
	$('#modalRestoreDataBase').modal({backdrop: 'static', keyboard: false});
	$('#progressBarRestoreFile').addClass('loadProgressBar');
	sendAsyncPost("loadDataFirstLogin", null)
	.then(function(response){
		$('#modalRestoreDataBase').modal('hide');
		$('#progressBarRestoreFile').removeClass('loadProgressBar');
		showReplyMessage(response.result, response.message, "Instalación limpia", null);
	})
	.catch(function(response){
		$('#modalRestoreDataBase').modal('hide');
		$('#progressBarRestoreFile').removeClass('loadProgressBar');
		showReplyMessage(0, "Ocurrió un error por lo que no se pudo finalizar con el proceso de Instalación.", "Instalación limpia", null);
	});

}

///////////////////////////////////////////////////////////////////////
// SUCURSALES

function loadBranchCompany(){
	let lista = null;
	let row  = "";
	let nombre = "";
	let codDGI = 0;
	let logo = null;
	let selectSucursal = $('#SUCURSAL')

	let response = sendPost("getBranchCompanyByRut", null); //trae todas las sucursales que tiene la empresa
	let response2 = sendPost("getConfiguration", {nameConfiguration: "SUCURSAL_IS_PRINCIPAL"}); //verifica cual es el valor que tiene la varible de la configuracion que guarda la sucursal predferida del usuario que ingreso
	if(response2.result == 2 && response2.configValue != 0){
		for (let i = 0; i < response.listResult.length; i++) {
			if (response.listResult[i].codDGI == response2.configValue){
				$('#imgLogoSucursal').attr('src', 'data:image/png;base64,'+response.listResult[i].logo);
				$('#nameSucursal').text(response.listResult[i].nombreComercial);
				$('#addressSucursal').text(response.listResult[i].direccion);
				$('#phoneSucursal').text(response.listResult[i].telephone1);
				$('#phone2Sucursal').text(response.listResult[i].telephone2);
				$('#emailSucursal').text(response.listResult[i].email);
			}
		}
	}
	else if(response2.result == 2 && response2.configValue == 0){
		for (let i = 0; i < response.listResult.length; i++) {
			if (response.listResult[i].isPrincipal){
				$('#imgLogoSucursal').attr('src', 'data:image/png;base64,'+response.listResult[i].logo);
				$('#nameSucursal').text(response.listResult[i].nombreComercial);
				$('#addressSucursal').text(response.listResult[i].direccion);
				$('#phoneSucursal').text(response.listResult[i].telephone1);
				$('#phone2Sucursal').text(response.listResult[i].telephone2);
				$('#emailSucursal').text(response.listResult[i].email);
				let responseUpdate = sendPost("updateVariableConfiguration", {variable: "SUCURSAL_IS_PRINCIPAL", value: response.listResult[i].codDGI});
				if(responseUpdate.result != 2){
					showReplyMessage(response.result, response.message, "Actualizar sucursal", null);
				}
			}
		}
	}

	if(response.result == 2 && response.listResult.length > 0){
		lista = response.listResult;
		//console.log(response);
		selectSucursal.empty();
		for (let i = 0; i < lista.length; i++) {
			if (lista[i].isPrincipal){
				row = '<option value="'+lista[i].codDGI+'" selected>'+lista[i].nombreComercial+'</option>';
			}
			else{
				row = '<option value="'+lista[i].codDGI+'">'+lista[i].nombreComercial+'</option>';
			}
			selectSucursal.append(row);
		}
	}
}

function updateDefaultBranchCompany(){

	let response = sendPost("getConfiguration", {nameConfiguration: "SUCURSAL_IS_PRINCIPAL"});
	let sucursal = $('#SUCURSAL').val();
	//console.log(response);
	////console.log($('#SUCURSAL').val());
	if(response.result == 2){
		if (response.configValue != sucursal){
			let response2 = sendPost("updateVariableConfiguration", {variable: "SUCURSAL_IS_PRINCIPAL", value: sucursal});
			showReplyMessage(response2.result, response2.message, "Actualizar sucursal", null);
			if(response2.result == 2)
				loadBranchCompany();
		}
	}
}

function updateEnabledSections(){

	let idChecked = null;
	let idsSelecteds = "";
	$("#tbodyEnabledSections > tr > td > input[type=checkbox]:checked").each(function() {
		let idChecked = $(this).attr('id');
		idsSelecteds +=  idChecked.replace("checkEnable", '') + ",";
	});
	let response = sendPost("updatePermissionSection", {idPermission: idsSelecteds});
	if(response.result == 2){
		showReplyMessage(response.result, "Su configuración fue modificada correctamente.", "Modificar permisos secciones", null);
		$("#modalButtonResponse").click(function(){
			window.location.reload();
		});
	} else {
		showReplyMessage(response.result, response.message, "Modificar permisos secciones", null);
	}
}

function changeFormatTicket(value){
	console.log("valor a cambiar "+value);
	sendAsyncPost('updateVariableConfiguration', {variable:'FORMATO_TICKET', value:value})
	.then(( response )=>{
		if ( response ){
			showReplyMessage(response.result, response.message, "Ticket", null);
		}
		console.log(response);
	})
}


function saveAdendaDefault(){
	let value = $("#idTextareaAdendaConfiguration").val()
	sendAsyncPost('updateVariableConfiguration', {variable:'ADENDA', value:value})
	.then(( response )=>{
		if ( response ){
			showReplyMessage(response.result, response.message, "Adenda", null);
		}
	})
}