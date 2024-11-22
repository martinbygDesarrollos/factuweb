let lastId = 0;
let textToSearch = null;
let withBalance = "YES";

if( document.getElementById("checkboxClientWithBalance").checked == false){
	withBalance = "NO";
}else{
	withBalance = "YES";
}



$('#myModal').on('hidden.bs.modal', function (e) {
	$('#progressbar h5').text("Descartando productos...");
})




function getListClientsView(){

	let response = sendPost("getListClients", {lastId: lastId, textToSearch: textToSearch, withBalance: withBalance});
	if(response.result == 2){
		if(lastId != response.lastId){
			lastId = response.lastId;
			for (let i = response.listResult.length - 1; i >= 0; i--) {
				let row = createRow(response.listResult[i].id, response.listResult[i].docReceptor, response.listResult[i].nombreReceptor, response.listResult[i].direccion, response.listResult[i].celular, response.listResult[i].correo, response.listResult[i].saldoUYU, response.listResult[i].saldoUSD);
				$('#tbodyClients').append(row);
			}
		}
	}else if(response.result == 0) showReplyMessage(0, response.message, "Listar clientes", null);
	resizeScreen();
}

// function createRow(idReceiver, docReceiver, nameReceiver, address, numberMobile, email, balanceUYU, balanceUSD){
// 	let row = "<tr id='" + idReceiver + "' >";

// 	row += "<td class='text-right toHidden1'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + docReceiver + "</td>";
// 	row += "<td class='text-right'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + nameReceiver + "</td>";
// 	if(!address)
// 		address = '';

// 	row += "<td class='text-right toHidden2 notShow'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + address + "</td>";

// 	if(numberMobile){
// 		let listNumber = numberMobile.split(';');
// 		if(listNumber.length > 1){
// 			let select = "<select>";
// 			for(let i = 0;  i < listNumber.length; i++){
// 				select += "<option value='" + listNumber[i] + "' >" + listNumber[i] + "</option>";
// 			}
// 			select += "</select>";
// 			row += "<td class='text-right toHidden2'>" + select + "</td>";
// 		}else{
// 			row += "<td class='text-right toHidden2' onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + listNumber + "</td>";
// 		}
// 	}else row += "<td class='text-right toHidden2'  onclick='openModalAccounStateForClient(" + idReceiver + ")'></td>";

// 	if(email){
// 		let listEmail = email.split(';');
// 		if(listEmail.length > 1){
// 			let select = "<select>";
// 			for (var i = 0; i < listEmail.length; i++) {
// 				select += "<option value='" + listEmail[i] + "'>" +  listEmail[i] + "</option>";
// 			}
// 			select += "</select>";
// 			row += "<td class='text-right toHidden2 notShow'>" + select + "</td>";
// 		}else row += "<td class='text-right toHidden2 notShow'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + email + "</td>";
// 	}else row += "<td class='text-right toHidden2 notShow'  onclick='openModalAccounStateForClient(" + idReceiver + ")'></td>";

// 	row += "<td class='text-right toHidden1'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + balanceUYU + "</td>";
// 	row += "<td class='text-right toHidden1'  onclick='openModalAccounStateForClient(" + idReceiver + ")'>" + balanceUSD + "</td>";
// 	row += "<td class='text-center p-1'><button class='btn btn-sm background-template-color2 text-template-background shadow-sm mr-1' onclick='openModalUpdateClient(" + idReceiver + ")'><i class='fas fa-user-edit text-mycolor'></i></button>";
// 	row += "<button class='btn btn-sm background-template-color2 text-template-background shadow-sm' onclick='openModalNewFeeClient(" + idReceiver + ")' data-toggle='tooltip' data-placement='left' title='Nueva cuota por servicio'>";
// 	row += "<i class='fas fa-plus-circle text-mycolor'></i></button></td></tr>";

// 	return row;
// }
function createRow(idReceiver, docReceiver, nameReceiver, address, numberMobile, email, balanceUYU, balanceUSD){
	let row = "<tr id='" + idReceiver + "' onclick='openModalAccounStateForClient(" + idReceiver + ")'>";

	row += "<td class='text-left'  >" + docReceiver + "</td>";
	row += "<td class='text-left' >" + nameReceiver + "</td>";
	if(!address)
		address = '';

	listNumber2 = ""
	if(numberMobile){
		let listNumber = numberMobile.split(';');
		listNumber2 = listNumber.join(', ');
	}
	listEmail2 = ""
	if(email){
		let listEmail = email.split(';');
		listEmail2 = listEmail.join(', ');
	}
	let newPadding = ""
	if(email && address && numberMobile && address != "" && numberMobile.trim() != "" && email.trim() != ""){
		newPadding = " pt-0 pb-0"
	}
	row += "<td class='text-left" + newPadding + "' ><p title=\"" + address + " \">" + address + "</p><p title=\"" + listNumber2 + " \">" + listNumber2 + "</p><p title=\"" + listEmail2 + " \">" + listEmail2 + "</p></td>";
	// if(numberMobile){
	// 	let listNumber = numberMobile.split(';');
	// 	if(listNumber.length > 1){
	// 		let select = "<select>";
	// 		for(let i = 0;  i < listNumber.length; i++){
	// 			select += "<option value='" + listNumber[i] + "' >" + listNumber[i] + "</option>";
	// 		}
	// 		select += "</select>";
	// 		row += "<td class='text-right toHidden2'>" + select + "</td>";
	// 	}else{
	// 		row += "<td class='text-right toHidden2'>" + listNumber + "</td>";
	// 	}
	// }else row += "<td class='text-right toHidden2' ></td>";

	// if(email){
	// 	let listEmail = email.split(';');
	// 	if(listEmail.length > 1){
	// 		let select = "<select>";
	// 		for (var i = 0; i < listEmail.length; i++) {
	// 			select += "<option value='" + listEmail[i] + "'>" +  listEmail[i] + "</option>";
	// 		}
	// 		select += "</select>";
	// 		row += "<td class='text-right toHidden2 notShow'>" + select + "</td>";
	// 	}else row += "<td class='text-right toHidden2 notShow' >" + email + "</td>";
	// }else row += "<td class='text-right toHidden2 notShow' ></td>";

	row += "<td class='text-right' > <p> $ " + balanceUYU + " </p> <p> U$S " + balanceUSD + " </p></td>";
	// row += "<td class='text-right' >" + balanceUSD + "</td>";
	row += "<td class='text-center p-1'><button class='btn btn-sm background-template-color2 text-template-background shadow-sm mr-1 update-btn' onclick='handleButtonClick(event," + idReceiver + ")' title='Editar información'><i class='fas fa-user-edit text-mycolor'></i></button>";
	row += "<button class='btn btn-sm background-template-color2 text-template-background shadow-sm new-fee-btn' onclick='handleButtonClick(event," + idReceiver + ")' data-toggle='tooltip' data-placement='left' title='Nueva cuota por servicio'>";
	row += "<i class='fas fa-plus-circle text-mycolor'></i></button></td></tr>";

	return row;
}

function handleButtonClick(event, clientId) {
	console.log(event.currentTarget)
	console.log(clientId)
	// Prevent the event from bubbling up to the table row
	event.stopPropagation();

	// Call the appropriate function based on the button clicked
	if (event.currentTarget.classList.contains('update-btn')) {
	  openModalUpdateClient(clientId);
	} else if (event.currentTarget.classList.contains('new-fee-btn')) {
	  openModalNewFeeClient(clientId);
	}
  }

function openModalNewFeeClient(idClient){
	let response = sendPost("getClientSelected",{idReceiver: idClient});
	if(response.result == 2){
		//comento esta llamada que se hace para ver los servicio por clientes para traer todos los servicios
		//let responseGetService = sendPost('getAllServiceForClient', {idClient: idClient});
		//ahora traer todos los servicios sin importar el cliente
		let responseGetService = sendPost('loadServices', {lastId:0, textToSearch: ""});
		if(responseGetService.result == 2){
			cleanNewFeeService();

			$.each(responseGetService.listResult, function(key, service){
				$('#selectNewFeeServices').append('<option value=' + service.idServicio + '>' + service.nombre + " " + service.simboloMoneda + " " + service.importe + '</option>');
				$('#textAreaServiceSelected').val(service.descripcion);
			});


			$('#inputNewFeeClient').val(response.client.nombreReceptor);
			$('#buttonNewFeeServiceConfirm').off('click');
			$('#buttonNewFeeServiceConfirm').click(function(){
				createNewFeeService(idClient);
			});
			$('#modalNewFeeService').modal();
		}else showReplyMessage(responseGetService.result, responseGetService.message, "Servicios", null);
	}else showReplyMessage(response.result, response.message, "Nueva cuota", null);
}

function loadDescriptionServiceSelected(){
	let idService = $('#selectNewFeeServices').val();
	let response = sendPost("getServiceSelected", {idService: idService});
	if(response.result == 2)
		$('#textAreaServiceSelected').val(response.objectResult.descripcion)
}

function createNewFeeService(idClient){
	let service = $('#selectNewFeeServices').val();
	let selectedMonth = $('#selectNewFeeMonth').val();

	if(service){
		let period = selectedMonth;
		let data = {idService: service, idClient: idClient, period: period};
		let response = sendPost("createNewFeeService", data);

		showReplyMessage(response.result, response.message, "Crear cuota", "modalNewFeeService");

		$("#modalButtonResponse").click(function(){
			if(response.result == 2){
				cleanNewFeeService();
				////console.log("ahora si")
				var url = getSiteURL() + 'ver-cuotas-servicios';
				window.location.href = url;
			}
		});

		return response;
	}else showReplyMessage(1, "Debe ingresar un servicio para crear una nueva cuota.", "Campo servicio requerido", "modalNewFeeService");



}

function cleanNewFeeService(){
	$('#inputNewFeeClient').val('');
	$('#selectNewFeeServices').empty();
	$('#selectNewFeeMonth').val(0);
}

function clientsWithBalance(checkboxWB){
	if(checkboxWB.checked) withBalance = "YES";
	else withBalance = "NO";
	textToSearch = null;
	lastId = 0;
	document.getElementById('inputToSearch').value = "";
	$('#tbodyClients').empty();
	getListClientsView();
}

function openModalAccounStateForClient(idReceiver){
	let responseGetClient = sendPost("getClientSelected",{idReceiver: idReceiver});
	if(responseGetClient.result == 2){
		$('#modalAccountState').modal();
		document.getElementById('inputPerson').value = responseGetClient.client.docReceptor;

		let responseGetConfigDate = sendPost('getConfiguration', {nameConfiguration: "INTERVALO_FECHA_ACCOUNT_SATE"});
		if(responseGetConfigDate.result == 2)
			onLoadInputDate(document.getElementById('inputDateInit'), responseGetConfigDate.configValue);
		else
			onLoadInputDate(document.getElementById('inputDateInit'), 30);

		let responseGetConfigFinalDate = sendPost('getConfiguration', {nameConfiguration: "FECHA_DESDE_ACCOUNT_SATE"});
		if(responseGetConfigFinalDate.result == 2){
			if(responseGetConfigFinalDate.configValue == "MES_SIGUIENTE")
				$('#inputDateEnding').val(getNextMonth());
			else if(responseGetConfigFinalDate.configValue == "MES_ACTUAL")
				onLoadInputDate(document.getElementById('inputDateEnding'),0);
		}

		$('#buttonInputCheckDateEnding').html('Utilizar fecha hasta: ' + getFormatDateHTML(getNextMonth()));

		let responseGetConfig = sendPost('getConfiguration', {nameConfiguration: "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA"});
		if(responseGetConfig.result == 2){
			if(responseGetConfig.configValue == "SI")
				$('#containerClashCollectionClient').css('visibility', 'visible');
			else
				$('#containerClashCollectionClient').css('visibility', 'hidden');
		}

		$('#modalAccountState').on('shown.bs.modal', function () {
			$('#inputDateInit').focus();
		})
		$('#btnConfirmModalAccountState').off('click');
		$('#btnConfirmModalAccountState').click(function(){
			getAccountState("CLIENT");
		});
	}else showReplyMessage(responseGetClient.result, responseGetClient.message, "Estado de cuenta", null);

	$('#inputPerson').off('change');
	$('#inputPerson').change(function(){
		loadPoeple("CLIENT");
	});

	$('#inputPerson').off('keyup');
	$('#inputPerson').keyup(function(){
		loadPoeple("CLIENT");
	})
}

//esta función busca cliente desde la vista de clientes
function searchClient(inputSearch){
	let textTemp = inputSearch.value;

	if (textTemp != null){
		if(textTemp.length >= 3){
			textToSearch = textTemp;
			lastId = 0;
			$('#tbodyClients').empty();
			getListClientsView();
		}else if(textTemp.length == 0){
			textToSearch = null;
			lastId = 0;
			$('#tbodyClients').empty();
			getListClientsView();
		}
	}
	else{
		textToSearch = null;
		lastId = 0;
		$('#tbodyClients').empty();
		getListClientsView();
	}
}

function openModalUpdateClient(idReceptor){
	let responseGetClient = sendPost("getClientSelected",{idReceiver: idReceptor});
	if(responseGetClient.result == 2){
		document.getElementById('buttonUpdateClient').name = idReceptor;
		document.getElementById('addressUpdateClient').value = responseGetClient.client.direccion;
		document.getElementById('nameUpdateClient').value = responseGetClient.client.nombreReceptor;
		document.getElementById('localityUpdateClient').value = responseGetClient.client.localidad;
		document.getElementById('departmentUpdateClient').value = responseGetClient.client.departamento;

		$('#emailListUpdateClient').empty();
		if(responseGetClient.client.correo){
			listEmail = responseGetClient.client.correo.split(';');
			if(listEmail.length >= 1){
				for(let i = 0; i < listEmail.length; i ++){
					$('#emailListUpdateClient').append($('<option>',{
						value: listEmail[i],
						text: listEmail[i]
					}));
				}
			}
		}

		$('#numberMobileListUpdateClient').empty();
		if(responseGetClient.client.celular){
			listNumberMobile = responseGetClient.client.celular.split(';');
			if(listNumberMobile.length >= 1){
				for(let i = 0; i < listNumberMobile.length; i ++){
					$('#numberMobileListUpdateClient').append($('<option>',{
						value: listNumberMobile[i],
						text: listNumberMobile[i]
					}));
				}
			}
		}

		$('#buttonUpdateClient').off('click');
		$('#buttonUpdateClient').click(function(){
			updateClient(responseGetClient.client.id, responseGetClient.client.docReceptor);
		});

		$('#modalUpdateClient').modal();
		$('#modalUpdateClient').on('shown.bs.modal', function () {
			$('#nameUpdateClient').focus();
		})
	}else showReplyMessage(responseGetClient.result, responseGetClient.message, "Modificar cliente", null);
}

function updateClient(idReceiver, docReceiver){
	$('#modalUpdateClient').modal('hide');

	let newNameClient = document.getElementById('nameUpdateClient').value || null;
	let addressClient = document.getElementById('addressUpdateClient').value || null;
	let newLocalityClient = document.getElementById('localityUpdateClient').value || null;
	let newDepartmentClient = document.getElementById('departmentUpdateClient').value || null;

	let emailsString = emailsToString();
	if(emailsString.length < 3) emailsString = null;

	let numberMobileString = numberMobileToString();
	if(numberMobileString.length < 3) numberMobileString = null;

	if(newNameClient){

		let data = {
			idReceiver: idReceiver,
			nameReceiver: newNameClient,
			addressReceiver: addressClient,
			locality: newLocalityClient,
			department: newDepartmentClient,
			email: emailsString,
			numberMobile: numberMobileString
		}

		let response = sendPost("updateClient", data);
		if(response.result == 2){
			// let td= $('#' + idReceiver).children();
			let tr= $('#' + idReceiver);
			// $('#'+ idReceiver).replaceWith(createRow(idReceiver, docReceiver, newNameClient, addressClient, numberMobileString, emailsString, getCellValue(td,5), getCellValue(td,6)));
			$('#'+ idReceiver).replaceWith(createRow(idReceiver, docReceiver, newNameClient, addressClient, numberMobileString, emailsString, getAmountValue(tr[0],1), getAmountValue(tr[0],2)));
		}
		showReplyMessage(response.result, response.message,"Modificar cliente", "modalUpdateClient");
	}else showReplyMessage(1,"Debe ingresar el nombre un nombre para la empresa o mantener el actual.", "Modificar cliente", "modalUpdateClient");
}

function getCellValue(td, position){
	return td[position].innerHTML || " ";
}

function getAmountValue(tr, index) {
    const text = tr.querySelector(`td:nth-child(4) p:nth-child(${index})`).textContent.trim();
    return text.split(' ')[1];
}

function addNumberMobileTemp(){
	let numTemp = document.getElementById('numberMobileUpdateClient').value || null;

	if(numTemp){
		if(numTemp.length == 9){
			if(!isNumberMobileDuplicate(numTemp)){
				$('#numberMobileListUpdateClient').append($('<option>',{
					value: numTemp,
					text: numTemp
				}));
				document.getElementById('numberMobileUpdateClient').value = '';
				return;
			}else document.getElementById('textErrorNumberMobile').innerHTML = "El número de celular ya fue ingresado";
		}else document.getElementById('textErrorNumberMobile').innerHTML = "El número de celular debe contar con 9 caracteres.";
	}else document.getElementById('textErrorNumberMobile').innerHTML = "Debe ingresar un número de celular para agregar.";

	$("#textErrorNumberMobile").fadeTo(2000, 800).slideUp(800, function(){ $("#alert-danger").slideUp(800);});
}

function isNumberMobileDuplicate(numberMobile){
	let listOption = $('#numberMobileListUpdateClient option');
	let isDuplicate = $.map(listOption, function(option){
		if(option.value == numberMobile) return true;
	});
	return isDuplicate[0];
}

function addEmailTemp(){
	let emailTemp = document.getElementById('emailUpdateClient').value || null;

	if(emailTemp){
		if(validateEmail(emailTemp)){
			if(!isEmailDuplicate(emailTemp)){
				$('#emailListUpdateClient').append($('<option>',{
					value: emailTemp,
					text: emailTemp
				}));
				document.getElementById('emailUpdateClient').value = '';
				return;
			}else document.getElementById('textErrorEmail').innerHTML = "Este correo ya fue agregado";
		}else document.getElementById('textErrorEmail').innerHTML = "Debe ingresar un correo valido agregar.";
	}else document.getElementById('textErrorEmail').innerHTML = "Debe ingresar un correo para agregar.";

	$("#textErrorEmail").fadeTo(2000, 800).slideUp(800, function(){ $("#alert-danger").slideUp(800);});
}

function validateEmail(email) {
	const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}

function isEmailDuplicate(emailTemp){
	let listOption = $('#emailListUpdateClient option');
	let isDuplicate = $.map(listOption, function(option){
		if(option.value == emailTemp) return true;
	});
	return isDuplicate[0];
}

function numberMobileToString(){
	let listOption = $('#numberMobileListUpdateClient option');
	let stringNumberMobile = '';
	$.map(listOption, function(option){
		stringNumberMobile += option.value + ";";
	});
	stringNumberMobile = stringNumberMobile.slice(0, -1);
	return stringNumberMobile;
}

function emailsToString(){
	let listOption = $('#emailListUpdateClient option');
	let stringEmails = '';
	$.map(listOption, function(option){
		stringEmails += option.value + ";";
	});
	stringEmails = stringEmails.slice(0, -1);
	return stringEmails;
}

function actionSelectNumberMobile(){
	$('#numberMobileListUpdateClient option:selected').remove();
}

function actionSelectEmails(){
	$('#emailListUpdateClient option:selected').remove();
}

function keyPressModalClient(eventEnter, input, size){
	if(eventEnter.keyCode == 13 && !eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "nameUpdateClient")
			$('#addressUpdateClient').focus();
		else if(eventEnter.srcElement.id =="addressUpdateClient")
			$('#localityUpdateClient').focus();
		else if(eventEnter.srcElement.id == "localityUpdateClient")
			$('#departmentUpdateClient').focus();
		else if(eventEnter.srcElement.id == "departmentUpdateClient")
			$('#numberMobileUpdateClient').focus();
		else if(eventEnter.srcElement.id == "numberMobileUpdateClient")
			$('#emailUpdateClient').focus();
		else if(eventEnter.srcElement.id == "emailUpdateClient")
			$('#buttonUpdateClient').click();
	}else if(eventEnter.keyCode == 13 && eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "emailUpdateClient")
			$('#numberMobileUpdateClient').focus();
		else if(eventEnter.srcElement.id =="numberMobileUpdateClient")
			$('#departmentUpdateClient').focus();
		else if(eventEnter.srcElement.id == "departmentUpdateClient")
			$('#localityUpdateClient').focus();
		else if(eventEnter.srcElement.id == "localityUpdateClient")
			$('#addressUpdateClient').focus();
		else if(eventEnter.srcElement.id == "addressUpdateClient")
			$('#nameUpdateClient').focus();
	}
	if(input != null && input.value.length == size) {
		return false;
	}
}

function exportClienteDeudores(){

	$("#modalExportClients").modal("hide");

	//bloquear el boton de exportar
	// progressBarIdProcess = loadPrograssBar();
	// $('#progressbar h5').text("Exportar clientes con saldo...");
	mostrarLoader(true)
	// $("#progressbar").modal("show");

	let dateTo = $("#idInputDateExportClients").val();
	dateTo = dateTo.replaceAll("-", "");
	//console.log("exportar a excel datos de los clientes deudores");
	sendAsyncPost("exportExcelDeudores" , {dateTo: dateTo})
	.then((response)=>{
		mostrarLoader(false)
		//console.log("respuesta del export");
		//console.log(response);
		//mostrar modal con progress bar

		// stopPrograssBar(progressBarIdProcess);
		// $('#progressbar').modal("hide");
		//cambiar el titulo del progressbar se hace cuando se cierra el modal por completo

		//habilitar boton de exportar

		if ( response.result != 2 ){
			showReplyMessage(response.result, response.message, "Exportar clientes con deuda", "modalExportClients");
		}else if ( response.result == 2 ){
			window.location.href = getSiteURL() + 'downloadExcel.php?n='+response.name;
		}
	})


}