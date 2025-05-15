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
	row += "<td class='text-right' > <p> $ " + balanceUYU + " </p> <p> U$S " + balanceUSD + " </p></td>";
	row += "<td class='text-center align-middle p-1'><button class='btn btn-sm background-template-color2 text-template-background shadow-sm mr-1 update-btn' onclick='handleButtonClick(event," + idReceiver + ")' title='Editar información'><i class='fas fa-user-edit text-mycolor'></i></button>";
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
function searchClientFromSearchBar(inputSearch){
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
		console.log(responseGetClient.client);
		document.getElementById('buttonUpdateClient').name = idReceptor;
		document.getElementById('addressUpdateClient').value = responseGetClient.client.direccion;
		document.getElementById('nameUpdateClient').value = responseGetClient.client.nombreReceptor;
		document.getElementById('localityUpdateClient').value = responseGetClient.client.localidad;
		document.getElementById('departmentUpdateClient').value = responseGetClient.client.departamento;

		// Limpiar el contenedor
		$('#emailListContainer').empty();

		if(responseGetClient.client.correo){
			listEmail = responseGetClient.client.correo.split(';');
			if(listEmail.length >= 1){
				for(let i = 0; i < listEmail.length; i++){
					// Crear elementos con jQuery
					const $emailItem = $('<div>', {
						class: 'email-item',
						'data-email': listEmail[i]
					});
					
					const $emailText = $('<span>', {
						class: 'email-text',
						text: listEmail[i]
					});
					
					const $deleteBtn = $('<span>', {
						class: 'delete-btn',
						text: '×',
						onclick: `removeEmail(event, '${listEmail[i]}')`
					});
					
					// Ensamblar y agregar al contenedor
					$emailItem.append($emailText).append($deleteBtn);
					$('#emailListContainer').append($emailItem);
				}
			}
		}

		// Limpiar el contenedor
		$('#mobileNumbersContainer').empty();

		if(responseGetClient.client.celular){
			listNumberMobile = responseGetClient.client.celular.split(';');
			if(listNumberMobile.length >= 1){
				for(let i = 0; i < listNumberMobile.length; i++){
					// Crear elementos con jQuery
					const $mobileItem = $('<div>', {
						class: 'mobile-number-item',
						'data-number': listNumberMobile[i]
					});
					
					const $numberText = $('<span>', {
						class: 'number-text',
						text: listNumberMobile[i]
					});
					
					const $deleteBtn = $('<span>', {
						class: 'delete-btn',
						text: '×',
						onclick: `removeNumber(event, '${listNumberMobile[i]}')`
					});
					
					// Ensamblar y agregar al contenedor
					$mobileItem.append($numberText).append($deleteBtn);
					$('#mobileNumbersContainer').append($mobileItem);
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

// NEW NEW NEW NEW NEW NEW NEW NEW NEW NEW

function openModalNewClient(){
	cleanModalNewClient()
	$('#modalNewClient').on('shown.bs.modal', function () {
		$('#inputTextToSearchClientModal').trigger('focus')
	}).modal('show')
}

function cleanModalNewClient(){
	$('#modalNewClient').find('input[type="text"], input[type="email"], input[type="number"], input[type="tel"], input[type="password"], textarea').val('');
}

function searchCompleteData(value, e){
	console.log("buscando... para completar todos los datos");
	console.log("dato ingresado "+value);
	e.preventDefault();

	let onlyNumber = /^\d+$/.test(value)
	if (onlyNumber){


		if ( value.length == 11 || value.length == 12 ){
			//chequear rut valido
			let validRut = validateRut(value);
			console.log(validRut);
			if ( validRut ){
				$('#listClient').empty();
				//e.preventDefault();
				sendAsyncPost("searchClientToSale", {documentClient: value})
				.then((response)=>{
					console.log(response);

					if ( response.result == 2 ){
						let client = response.objectResult;
						setClientValues(client.nombreReceptor, client.docReceptor, client.direccion, client.localidad, client.departamento, client.correo, client.celular);
						$("#inputAddressClient").focus();
					}
				})
				.catch((error)=>{
					console.log("catch :"+error);
				})
			}else showReplyMessage(1, "El rut ingresado no es válido.", "Buscar cliente", "modalSetClient");
		}else if (value.length == 8){
			let validCi = validateCI(value);
			console.log(validCi);
			if ( validCi ){
				$('#listClient').empty();
				sendAsyncPost("searchClientToSale", {documentClient: value})
				.then((response)=>{
					console.log(response);
					if ( response.objectResult.docReceptor == value ){
						let client = response.objectResult;
						setClientValues(client.nombreReceptor, client.docReceptor, client.direccion, client.localidad, client.departamento, client.correo, client.celular);
						$("#inputAddressClient").focus();
					}else console.log("no se ha encontrado resultado");
				})
				.catch((error)=>{
					console.log("catch :"+error);
				})
			}else showReplyMessage(1, "La cédula ingresada no es válida.", "Buscar cliente", "modalSetClient");
		}else if (value.length <= 0){
			$('#listClient').empty();
		}

	}
}

function setClientValues(name, documentC, address, city, department, email, phone){
	$('#inputNameClient').val(name);
	$('#inputDocumentClient').val(documentC);
	$('#inputAddressClient').val(address);
	$('#inputCityClient').val(city);
	$('#inputDepartmentClient').val(department);
	$('#inputEmailClient').val(email);
	$('#inputPhoneClient').val(phone);
}

function insertClient(){
	let documentClient = $('#inputDocumentClient').val() || null;
	let nameClient = $('#inputNameClient').val() || null;
	let address = $('#inputAddressClient').val() || null;
	let city = $('#inputCityClient').val() || null;
	let department = $('#inputDepartmentClient').val() || null;
	let email = $('#inputEmailClient').val() || null;
	let phone = $('#inputPhoneClient').val() || null;
	console.log('Ingresar el cliente: ' + "Document: '" + documentClient +  + "' NameClient: '" + nameClient + "' Address: '" + address + "' City: '" + city + "' Department: '" + department + "' Email: '" + email + "' Phone: '" + phone) + "'"
	
	if((!department || !city || !address)){
		showReplyMessage(1, "Para ingresar un cliente este debe contar con dirección, ciudad y departamento", "Información incompleta", "modalNewClient")
		return;
	} else {
		if(department.trim() == "" || city.trim() == "" || address.trim() == ""){
			showReplyMessage(1, "Para ingresar un cliente este debe contar con dirección, ciudad y departamento", "Información incompleta", "modalNewClient")
			return;
		} else {

		}
	}
	if(nameClient && documentClient){
		if(validateRut(documentClient) || validateCI(documentClient)){
			//se envían todos los datos del cliente, si no se encuentra registrado el documento se registra en la base local y en ormen
			$('#modalNewClient').modal('hide')
			mostrarLoader(true)
			sendAsyncPost("createModifyClient", {documentReceiver: documentClient, nameReceiver: nameClient, numberMobile: phone, addressReceiver: address, locality: city, department: department, email: email})
			.then(( response )=>{
				mostrarLoader(false)
				console.log("se terminó el proceso de guardado del cliente");
				console.log(response);
				$('#modalNewClient').modal('hide');
				$('#inputToSearch').trigger('change')
			})
			.catch(()=>{
				mostrarLoader(false)
				console.log("proceso del cliente ");
			})
		}else showReplyMessage(1, "El documento ingresado no pudo validarse como RUT y tampoco como CI por favor vuelva a ingresarlo", "Documento no valido", "modalNewClient");
	}else showReplyMessage(1, "Para ingresar un cliente este debe contar con Nombre y Documento.", "Nombre y Documento requeridos", "modalNewClient");
}

//esta función busca cliente desde la vista de ventas
function searchClient(inputToSearch, e){ //cuando se ingresan numeros el el buscador de cliente por rut se llama a esta función para buscar los datos
	console.log("searchClient")
	e.preventDefault();
	if ( inputToSearch.value.length > 2 ){
		//$('#listClient').empty();
		const response = sendPost("searchClientsToSale", {textToSearch: inputToSearch.value });
		if ( response ){
			console.log(response);
			//setClientValues(null, null, null, null, null, null);
			if(response.result == 2){
				$('#listClient').empty();
				if(response.listResult.length > 0){
					for (var i = 0; i < response.listResult.length; i++) {
						//console.log(response.listResult[i].document);
						let opt = document.getElementById("documentClient_"+response.listResult[i].document)

						if ( !opt ){
							let option = document.createElement("option");
							option.label = response.listResult[i].name;
							option.value = response.listResult[i].document;
							option.id = "documentClient_"+response.listResult[i].document;
							$('#listClient').append(option);
						}

					}
				} else {} /*setClientValues(null, null, null, null, null, null, null);*/
			}
		}
	}else{
		console.log("limpiar tabla");
		$('#listClient').empty();
	}
}

function keyPressAddClient(keyPress, value, size){
	////console.log("keyPressAddClient");
	if(keyPress.keyCode == 13 && !keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputTextToSearchClientModal")
			$('#inputNameClient').focus();
		if(keyPress.srcElement.id == "inputNameClient")
			$('#inputDocumentClient').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient")
			$('#inputPhoneClient').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient")
			$('#inputEmailClient').focus();
		else if(keyPress.srcElement.id =="inputEmailClient")
			$('#inputAddressClient').focus();
		else if(keyPress.srcElement.id == "inputAddressClient")
			$('#inputCityClient').focus();
		else if(keyPress.srcElement.id == "inputCityClient")
			$('#inputDepartmentClient').focus();
		else if(keyPress.srcElement.id == "inputDepartmentClient")
			$('#btnConfirmSetClient').click();
	}
	else if(keyPress.keyCode == 13 && keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputDepartmentClient")
			$('#inputCityClient').focus();
		else if(keyPress.srcElement.id == "inputCityClient")
			$('#inputAddressClient').focus();
		else if(keyPress.srcElement.id == "inputAddressClient")
			$('#inputEmailClient').focus();
		else if(keyPress.srcElement.id =="inputEmailClient")
			$('#inputPhoneClient').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient")
			$('#inputDocumentClient').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient")
			$('#inputNameClient').focus();
		else if(keyPress.srcElement.id == "inputNameClient")
			$('#inputTextToSearchClientModal').focus();
	}
	else if(value != null && value.length == size) {
		return false;
	}
}

// Event listener para el clic en los números
$(document).on('click', '.mobile-number-item', function(e) {
	if (!$(e.target).hasClass('delete-btn')) {
		const number = $(this).data('number');
		copyToClipboard(number);
	}
});
$(document).on('click', '.email-item', function(e) {
	if (!$(e.target).hasClass('delete-btn')) {
		const email = $(this).data('email');
		copyToClipboard(email);
	}
});

// Función para copiar al portapapeles
function copyToClipboard(text) {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(text).then(function() {
			showCopyNotification('Texto copiado: ' + text);
		}).catch(function(err) {
			// Fallback para navegadores antiguos
			fallbackCopyToClipboard(text);
		});
	} else {
		// Fallback para navegadores antiguos
		fallbackCopyToClipboard(text);
	}
}

// Fallback para navegadores que no soportan la API clipboard
function fallbackCopyToClipboard(text) {
	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.style.position = 'fixed';
	textarea.style.opacity = '0';
	document.body.appendChild(textarea);
	textarea.select();
	document.execCommand('copy');
	document.body.removeChild(textarea);
	showCopyNotification('Texto copiado: ' + text);
}

// Mostrar notificación de copiado
function showCopyNotification(message) {
	const notification = document.createElement('div');
	notification.className = 'copy-notification';
	notification.textContent = message;
	notification.style = "z-index: 9999";
	document.body.appendChild(notification);
	
	setTimeout(function() {
		notification.remove();
	}, 2000);
}

// Función para eliminar un número
function removeNumber(event, number) {
	event.stopPropagation(); // Evitar que se active el evento de copiar
	if (confirm('¿Estás seguro de que deseas eliminar el número ' + number + '?')) {
		const element = document.querySelector(`[data-number="${number}"]`);
		element.remove();
	}
}

// Función para eliminar un número
function removeEmail(event, email) {
	event.stopPropagation(); // Evitar que se active el evento de copiar
	if (confirm('¿Estás seguro de que deseas eliminar el correo ' + email + '?')) {
		const element = document.querySelector(`[data-email="${email}"]`);
		element.remove();
	}
}

// NEW NEW NEW NEW NEW NEW NEW NEW NEW NEW

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

		// let response = sendAsyncPost("updateClient", data);
		mostrarLoader(true)
		sendAsyncPost("updateClient" , data)
		.then((response)=>{
			mostrarLoader(false)
			if ( response.result != 2 ){
				showReplyMessage(response.result, response.message, "Modificar cliente", "modalUpdateClient");
			}else if ( response.result == 2 ){
				let tr= $('#' + idReceiver);
				$('#'+ idReceiver).replaceWith(createRow(idReceiver, docReceiver, newNameClient, addressClient, numberMobileString, emailsString, getAmountValue(tr[0],1), getAmountValue(tr[0],2)));
				// window.location.href = getSiteURL() + 'downloadExcel.php?n='+response.name;
			}
		})
		.catch((error) => {
			mostrarLoader(false)
			console.error(error);
		});
		// if(response.result == 2){
		// 	// let td= $('#' + idReceiver).children();
		// 	let tr= $('#' + idReceiver);
		// 	// $('#'+ idReceiver).replaceWith(createRow(idReceiver, docReceiver, newNameClient, addressClient, numberMobileString, emailsString, getCellValue(td,5), getCellValue(td,6)));
		// 	$('#'+ idReceiver).replaceWith(createRow(idReceiver, docReceiver, newNameClient, addressClient, numberMobileString, emailsString, getAmountValue(tr[0],1), getAmountValue(tr[0],2)));
		// }
		// showReplyMessage(response.result, response.message, "Modificar cliente", "modalUpdateClient");
	}else showReplyMessage(1,"Debe ingresar el nombre un nombre para la empresa o mantener el actual.", "Modificar cliente", "modalUpdateClient");
}

function getCellValue(td, position){
	return td[position].innerHTML || " ";
}

function getAmountValue(tr, index) {
    const text = tr.querySelector(`td:nth-child(4) p:nth-child(${index})`).textContent.trim();
    return text.split(' ')[1];
}

// function addNumberMobileTemp(){
// 	let numTemp = document.getElementById('numberMobileUpdateClient').value || null;

// 	if(numTemp){
// 		if(numTemp.length == 9){
// 			if(!isNumberMobileDuplicate(numTemp)){
// 				$('#numberMobileListUpdateClient').append($('<option>',{
// 					value: numTemp,
// 					text: numTemp
// 				}));
// 				document.getElementById('numberMobileUpdateClient').value = '';
// 				return;
// 			}else document.getElementById('textErrorNumberMobile').innerHTML = "El número de celular ya fue ingresado";
// 		}else document.getElementById('textErrorNumberMobile').innerHTML = "El número de celular debe contar con 9 caracteres.";
// 	}else document.getElementById('textErrorNumberMobile').innerHTML = "Debe ingresar un número de celular para agregar.";

// 	$("#textErrorNumberMobile").fadeTo(2000, 800).slideUp(800, function(){ $("#alert-danger").slideUp(800);});
// }

function addNumberMobileTemp(){
    const numTemp = $('#numberMobileUpdateClient').val() || null;
    const $errorText = $('#textErrorNumberMobile');

    if(numTemp){
        if(numTemp.length == 9){
            if(!isNumberMobileDuplicate(numTemp)){
                // Crear elemento con jQuery
                const $mobileItem = $('<div>', {
                    class: 'mobile-number-item',
                    'data-number': numTemp
                });
                
                $mobileItem.append(
                    $('<span>', { class: 'number-text', text: numTemp }),
                    $('<span>', { 
                        class: 'delete-btn', 
                        text: '×',
                        click: function(e) { removeNumber(e, numTemp); }
                    })
                );
                
                $('#mobileNumbersContainer').append($mobileItem);
                $('#numberMobileUpdateClient').val('');
                return;
            } else {
                $errorText.html("El número de celular ya fue ingresado");
            }
        } else {
            $errorText.html("El número de celular debe contar con 9 caracteres.");
        }
    } else {
        $errorText.html("Debe ingresar un número de celular para agregar.");
    }

    $errorText.fadeTo(2000, 800).slideUp(800);
}

// function isNumberMobileDuplicate(numberMobile){
// 	let listOption = $('#numberMobileListUpdateClient option');
// 	let isDuplicate = $.map(listOption, function(option){
// 		if(option.value == numberMobile) return true;
// 	});
// 	return isDuplicate[0];
// }

function isNumberMobileDuplicate(numberMobile){
    // Obtener todos los elementos con la clase mobile-number-item
    let listItems = $('#mobileNumbersContainer .mobile-number-item');
    let isDuplicate = false;
    
    // Verificar si el número ya existe
    listItems.each(function(){
        if($(this).data('number') == numberMobile) {
            isDuplicate = true;
            return false; // Salir del each
        }
    });
    
    return isDuplicate;
}

// function addEmailTemp(){
// 	let emailTemp = document.getElementById('emailUpdateClient').value || null;

// 	if(emailTemp){
// 		if(validateEmail(emailTemp)){
// 			if(!isEmailDuplicate(emailTemp)){
// 				$('#emailListUpdateClient').append($('<option>',{
// 					value: emailTemp,
// 					text: emailTemp
// 				}));
// 				document.getElementById('emailUpdateClient').value = '';
// 				return;
// 			}else document.getElementById('textErrorEmail').innerHTML = "Este correo ya fue agregado";
// 		}else document.getElementById('textErrorEmail').innerHTML = "Debe ingresar un correo valido agregar.";
// 	}else document.getElementById('textErrorEmail').innerHTML = "Debe ingresar un correo para agregar.";

// 	$("#textErrorEmail").fadeTo(2000, 800).slideUp(800, function(){ $("#alert-danger").slideUp(800);});
// }

function addEmailTemp(){
	const emailTemp = $('#emailUpdateClient').val() || null;
    const $errorText = $('#textErrorEmail');

    if(emailTemp){
        if(validateEmail(emailTemp)){
            if(!isEmailDuplicate(emailTemp)){
                // Crear elemento con jQuery
                const $emailItem = $('<div>', {
                    class: 'email-item',
                    'data-email': emailTemp
                });
                
                $emailItem.append(
                    $('<span>', { class: 'email-text', text: emailTemp }),
                    $('<span>', { 
                        class: 'delete-btn', 
                        text: '×',
                        click: function(e) { removeEmail(e, emailTemp); }
                    })
                );
                
                $('#emailListContainer').append($emailItem);
                $('#emailUpdateClient').val('');
                return;
            } else {
                $errorText.html("El correo ya fue ingresado");
            }
        } else {
            $errorText.html("El correo debe ser válido.");
        }
    } else {
        $errorText.html("Debe ingresar un correo para agregar.");
    }

    $errorText.fadeTo(2000, 800).slideUp(800);
}

function validateEmail(email) {
	const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}

// function isEmailDuplicate(emailTemp){
// 	let listOption = $('#emailListUpdateClient option');
// 	let isDuplicate = $.map(listOption, function(option){
// 		if(option.value == emailTemp) return true;
// 	});
// 	return isDuplicate[0];
// }

function isEmailDuplicate(emailTemp){
    // Obtener todos los elementos con la clase mobile-number-item
    let listItems = $('#emailListContainer .email-item');
    let isDuplicate = false;
    
    // Verificar si el número ya existe
    listItems.each(function(){
        if($(this).data('email') == emailTemp) {
            isDuplicate = true;
            return false; // Salir del each
        }
    });
    
    return isDuplicate;
}


function numberMobileToString(){
	// let listOption = $('#numberMobileListUpdateClient option');
	// let stringNumberMobile = '';
	// $.map(listOption, function(option){
	// 	stringNumberMobile += option.value + ";";
	// });
	// stringNumberMobile = stringNumberMobile.slice(0, -1);
	// return stringNumberMobile;

	const numbers = [];
    $('#mobileNumbersContainer .mobile-number-item').each(function() {
        numbers.push($(this).data('number'));
    });

	// Crear string con los números separados por punto y coma
    const numbersString = numbers.join(';');
    
    // Aquí puedes enviar la actualización al servidor
    console.log('Números actualizados:', numbersString);
	return numbersString
}

function emailsToString(){
	// let listOption = $('#emailListUpdateClient option');
	// let stringEmails = '';
	// $.map(listOption, function(option){
	// 	stringEmails += option.value + ";";
	// });
	// stringEmails = stringEmails.slice(0, -1);
	// return stringEmails;

	const emails = [];
    $('#emailListContainer .email-item').each(function() {
        emails.push($(this).data('email'));
    });

	// Crear string con los números separados por punto y coma
    const emailString = emails.join(';');
    
    // Aquí puedes enviar la actualización al servidor
    console.log('Correos actualizados:', emailString);
	return emailString
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