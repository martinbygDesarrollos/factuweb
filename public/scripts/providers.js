let lastId = 0;
let textToSearch = null;
let withBalance = "YES";

function getProviders(){
	let response = sendPost('getProviders', {lastId: lastId, textToSearch: textToSearch, withBalance: withBalance});
		//console.log(response)
	if(response.result == 2){

		lastId = response.lastId;
		listProviders = response.listResult;
		for (var i = listProviders.length - 1; i >= 0; i--) {
			var row = createRow(listProviders[i].idProveedor, listProviders[i].rut, listProviders[i].razonSocial, listProviders[i].direccion, listProviders[i].telefono, listProviders[i].email, listProviders[i].balance.balanceUYU, listProviders[i].balance.balanceUSD);
			$('#tbodyProviders').append(row);
		}
	}else if(response.result == 0) showReplyMessage(0, response.message, "Listar clientes", null);
	resizeScreen();
}

function createRow(idProvider, docProvider, nameBusiness, address, phoneNumber, email, balanceUYU, balanceUSD){
	var nullCell = "<td class='text-right toHidden2' onclick='openModalAccounStateForProvider(" + idProvider + ")'></td>";
	var row = "<tr id='" + idProvider + "'>";

	row += "<td class='text-right' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + docProvider + "</td>";
	row += "<td class='text-right' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + nameBusiness + "</td>";
	if(address)
		row += "<td class='text-right toHidden2' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + address + "</td>";
	else
		row += nullCell;
	if(phoneNumber)
		row += "<td class='text-right toHidden2' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + phoneNumber + "</td>";
	else
		row += nullCell;

	if(email)
		row += "<td class='text-right toHidden2' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + email + "</td>";
	else
		row += nullCell;

	row += "<td class='text-right toHidden1' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + balanceUYU + "</td>";
	row += "<td class='text-right toHidden1' onclick='openModalAccounStateForProvider(" + idProvider + ")'>" + balanceUSD + "</td>";
	row += "<td class='text-center p-1'><button class='btn btn-sm background-template-color2 text-template-background' onclick='openModalUpdateProvider(" + idProvider + ")'><i class='fas fa-user-edit text-mycolor'></i></button></td></tr>";

	return row;
}

function providersWithBalance(inputCheck){
	if(inputCheck.checked) withBalance = "YES";
	else withBalance = "NO";
	textToSearch = null;
	lastId = 0;
	document.getElementById('inputToSearch').value = "";
	$('#tbodyProviders').empty();
	getProviders();
}

function openModalUpdateProvider(idProvider){
	var response = sendPost('getProvider', {idProvider: idProvider});
	if(response.result == 2){
		$('#documentUpdateProvider').val(response.provider.rut);
		$('#nameBusinessUpdateProvider').val(response.provider.razonSocial);
		$('#addressUpdateProvider').val(response.provider.direccion);
		$('#phoneNumberUpdateProvider').val(response.provider.telefono);
		$('#emailUpdateProvider').val(response.provider.email);
		$('#modalUpdateProvider').modal();
		$('#buttonUpdateProvider').off('click');
		$('#buttonUpdateProvider').click(function(){
			updateProvider(idProvider);
		});
	}else showReplyMessage(response.result, response.message, "Obtener proveedor", null);
}

function updateProvider(idProvider){
	let nameBusiness = $('#nameBusinessUpdateProvider').val() || null;
	let address = $('#addressUpdateProvider').val() || null;
	let phoneNumber = $('#phoneNumberUpdateProvider').val() || null;
	let email = $('#emailUpdateProvider').val() || null;

	if(nameBusiness && nameBusiness.length > 4){
		var response = sendPost('modifyProvider', {idProvider: idProvider, nameBusiness: nameBusiness, address: address, phoneNumber: phoneNumber, email: email});
		if(response.result == 2)
			modifyRow(idProvider, nameBusiness, address, phoneNumber, email);
		showReplyMessage(response.result, response.message, "Modificar proveedor", "modalUpdateProvider");
	}else showReplyMessage(1, "La razon social de la empresa no puede estar vacia.", "Modificar proveedor", "modalUpdateProvider");
}

function modifyRow(idProvider, nameBusiness, address, phoneNumber, email){
	var td = $('#'+ idProvider).children();
	$('#'+ idProvider).replaceWith(createRow(idProvider, getCellValue(td, 0), nameBusiness, address, phoneNumber, email, getCellValue(td, 5), getCellValue(td, 6)));
}

function openModalAccounStateForProvider(idProvider){

	console.log("openModalAccounStateForProvider", idProvider);
	var responseGetProvider = sendPost("getProvider",{idProvider: idProvider});
	if(responseGetProvider.result == 2){
		$('#modalAccountState').modal();
		$('#inputPerson').val(responseGetProvider.provider.rut);
		onLoadInputDate(document.getElementById('inputDateInit'), 30);
		onLoadInputDate(document.getElementById('inputDateEnding'),0);
		$('#modalAccountState').on('shown.bs.modal', function () {
			$('#inputDateInit').focus();
		})
		$('#btnConfirmModalAccountState').off('click');
		$('#btnConfirmModalAccountState').click(function(){
			getAccountState("PROVIDER");
		});
	}else showReplyMessage(responseGetProvider.result, responseGetProvider.message, "Modificar cliente", null);


	$('#inputPerson').off('change');
	$('#inputPerson').change(function(){
		loadPoeple("PROVIDER");
	});

	$('#inputPerson').off('keyup');
	$('#inputPerson').keyup(function(){
		loadPoeple("PROVIDER");
	})
}

function searchProviders(inputToSearch){
	let textTemp = inputToSearch.value;
	if(textTemp.length > 2){
		lastId = 0;
		textToSearch = textTemp;
		$('#tbodyProviders').empty();
		getProviders();
	}else if(textTemp.length == 0){
		lastId = 0;
		textToSearch = null;
		$('#tbodyProviders').empty();
		getProviders();
	}

}

function getCellValue(item, position){
	return item[position].innerHTML || " ";
}

function keyPressModalProvider(eventEnter,value, size){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "nameBusinessUpdateProvider")
			$('#addressUpdateProvider').focus();
		else if(eventEnter.srcElement.id =="addressUpdateProvider")
			$('#phoneNumberUpdateProvider').focus();
		else if(eventEnter.srcElement.id == "phoneNumberUpdateProvider")
			$('#emailUpdateProvider').focus();
		else if(eventEnter.srcElement.id == "emailUpdateProvider")
			$('#buttonUpdateProvider').click();
	}else if(value != null && value.length == size) {
		return false;
	}
}









function exportProvidersDeudores(){


	$("#modalExportProviders").modal("hide");

	//bloquear el boton de exportar
	progressBarIdProcess = loadPrograssBar();
	$('#progressbar h5').text("Exportando proveedores con saldo...");
	$("#progressbar").modal("show");

	let dateTo = $("#idInputDateExportProviders").val();
	dateTo = dateTo.replaceAll("-", "");
	//console.log("exportar a excel datos de los clientes deudores");
	sendAsyncPost("exportExcelDeudoresProveedores" , {dateTo: dateTo})
	.then((response)=>{
		console.log("respuesta del export");
		console.log(response);
		//mostrar modal con progress bar

		stopPrograssBar(progressBarIdProcess);
		$('#progressbar').modal("hide");
		//cambiar el titulo del progressbar se hace cuando se cierra el modal por completo

		//habilitar boton de exportar
		if ( response.result != 2 ){
			showReplyMessage(response.result, response.message, "Exportar proveedores", "modalExportProviders");
		}else if ( response.result == 2 ){
			window.location.href = getSiteURL() + 'downloadExcel.php?n='+response.name;
		}
	})

}