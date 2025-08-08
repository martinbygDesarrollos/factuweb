


$('#modalResponse').off('shown.bs.modal').on('shown.bs.modal', function(){
	$('#modalResponse button').focus();
})

function selectClient(){
	console.log("NUEVO CLIENTE SETTEADO")
	let documentClient = $('#inputDocumentClient').val() || null;
	let nameClient = $('#inputNameClient').val() || null;
	let address = $('#inputAddressClient').val() || null;
	let city = $('#inputCityClient').val() || null;
	let department = $('#inputDepartmentClient').val() || null;
	let email = $('#inputEmailClient').val() || null;
	let phone = $('#inputPhoneClient').val() || null;
	console.log(department)
	console.log(city)
	console.log(address)
	// console.log(department.trim())
	// console.log(city.trim())
	// console.log(address.trim())
	if((!department || !city || !address)){
		showReplyMessage(1, "Para ingresar un cliente este debe contar con dirección, ciudad y departamento, de lo contrario no ingresar.", "Información incompleta", "modalSetClient")
		console.log("A")
		return;
	} else {
		if(department.trim() == "" || city.trim() == "" || address.trim() == ""){
			console.log("B")
			showReplyMessage(1, "Para ingresar un cliente este debe contar con dirección, ciudad y departamento, de lo contrario no ingresar.", "Información incompleta", "modalSetClient")
			return;
		} else {

		}
	}
	//  else {
	// 	if(department.trim() == "" || city.trim() == "" || address.trim() == ""){
	// 		showReplyMessage(1, "Para ingresar un cliente este debe contar dirección, ciudad y departamento, de lo contrario no ingresar.", "Información incompleta", "modalSetClient")
	// 		return;
	// 	}
	// }
	$("#selectTypeVoucher").empty();
	// $('#divComprobante').children().remove();
	if($('#inputDocumentClient').val().length > 8){
		// $('#divComprobante').append('<label for="selectTypeVoucher">Comprobante</label><select class="custom-select custom-select-sm shadow-sm" id="selectTypeVoucher" onchange="onChangeTypeVoucher(this)"><option value="301">EFactura Contado</option><option value="311">EFactura Crédito</option></select>')
		// $('#divComprobante').children().remove();
        $("#selectTypeVoucher").append('<option value="301">EFactura Contado</option>');
        $("#selectTypeVoucher").append('<option value="311">EFactura Crédito</option>');
		// $("#selectTypeVoucher").prop("selectedIndex", 0);
        console.log("EMPRESA")
    } else if($('#inputDocumentClient').val().length <= 8){
		// $('#divComprobante').append('<label for="selectTypeVoucher">Comprobante</label><select class="custom-select custom-select-sm shadow-sm" id="selectTypeVoucher" onchange="onChangeTypeVoucher(this)"><option value="201">ETicket Contado</option><option value="211">ETicket Crédito</option></select>')
		// $("#selectTypeVoucher").empty();
        $("#selectTypeVoucher").append('<option value="201">ETicket Contado</option>');
        $("#selectTypeVoucher").append('<option value="211">ETicket Crédito</option>');
        console.log("PERSONA")
    }
	$("#selectTypeVoucher").prop("selectedIndex", 0);
	
	if(nameClient && documentClient){
		if(validateRut(documentClient) || validateCI(documentClient)){
			//se envían todos los datos del cliente, si no se encuentra registrado el documento se registra en la base local y en ormen
			sendAsyncPost("createModifyClient", {documentReceiver: documentClient, nameReceiver: nameClient, numberMobile: phone, addressReceiver: address, locality: city, department: department, email: email})
			.then(( response )=>{
				console.log("se terminó el proceso de guardado del cliente");
				console.log(response);
			})
			.catch(()=>{
				console.log("proceso del cliente ");
			})
			createClient(documentClient, nameClient, address, city, department, email, phone);
			if(validateRut(documentClient)){
				console.log("QUE HACE ACA"); // NADA QUE PUEDA DETECTAR
				// $('#selectTypeVoucher').val(111).change();
			}
			$('#buttonModalClientWithName').html(nameClient + " " +  documentClient);
			$('#buttonCancelClient').css('display', 'block');
			$('#modalSetClient').off('hidden.bs.modal').on('hidden.bs.modal', function () {
				console.log("modalSetClient.onHidden")
				setNextStep('selectTypeVoucher')
			});

			$('#modalSetClient').modal('hide');
		}else showReplyMessage(1, "El documento ingresado no pudo validarse como RUT y tampoco como CI por favor vuelva a ingresarlo", "Documento no valido", "modalSetClient");
	}else showReplyMessage(1, "Para ingresar un cliente este debe contar con Nombre y Documento, de lo contrario no ingresar.", "Nombre y Documento requeridos", "modalSetClient");
}

let clientSelected = [];
function createClient(documentClient, nameClient, address, city, department, email, phone){
	clientSelected[0] = ({
		document: documentClient,
		name: nameClient,
		address: address,
		city: city,
		department: department,
		email: email,
		phone: phone
	});
}

function setClientValues(modal, name, document, address, city, department, email, phone){
	if (modal == 'modalSetClientByButton') {
		$('#inputDocumentClient_SetClientByButton').val(document)
		$('#inputNameClient_SetClientByButton').val(name)
		$('#inputAddressClient_SetClientByButton').val(address)
		$('#inputCityClient_SetClientByButton').val(city)
		$('#inputDepartmentClient_SetClientByButton').val(department)
		$('#inputEmailClient_SetClientByButton').val(email)
		$('#inputPhoneClient_SetClientByButton').val(phone)
	} else if (modal == "modalSetClient") {
		$('#inputDocumentClient').val(document)
		$('#inputNameClient').val(name)
		$('#inputAddressClient').val(address)
		$('#inputCityClient').val(city)
		$('#inputDepartmentClient').val(department)
		$('#inputEmailClient').val(email)
		$('#inputPhoneClient').val(phone)
	}
}

// function setClientValues(name, documentC, address, city, department, email, phone){
// 	$('#inputNameClient').val(name);
// 	$('#inputDocumentClient').val(documentC);
// 	$('#inputAddressClient').val(address);
// 	$('#inputCityClient').val(city);
// 	$('#inputDepartmentClient').val(department);
// 	$('#inputEmailClient').val(email);
// 	$('#inputPhoneClient').val(phone);
// }

// function cancelClientSelected(){
// 	clientSelected = [];
// 	$('#inputTextToSearchClient').val("");
// 	$('#buttonModalClientWithName').html("Agregar <u>C</u>liente <i class='fas fa-user-plus'></i>");
// 	setClientValues(null, null, null, null, null, null, null);
// 	$('#selectTypeVoucher').val(101).change();
// 	$('#buttonCancelClient').css('display', 'none');
// }

//esta función busca cliente desde la vista de ventas
function searchClient(inputToSearch, e, dataList, modal){ //cuando se ingresan numeros el el buscador de cliente por rut se llama a esta función para buscar los datos
	console.log("searchClient")
	e.preventDefault();
	if ( inputToSearch.value.length > 2 ){
		// const response = sendPost("searchClientsToSale",{textToSearch: inputToSearch.value });
		sendAsyncPost("searchClientsToSale", {textToSearch: inputToSearch.value })
		.then(function(response){
			if(response.result == 2){
				$('#' + dataList).empty();
				if(response.listResult.length > 0){
					for (var i = 0; i < response.listResult.length; i++) {
						let opt = document.getElementById("documentClient_"+response.listResult[i].document)
						if ( !opt ){
							let option = document.createElement("option");
							option.label = response.listResult[i].name;
							option.value = response.listResult[i].document;
							option.id = "documentClient_"+response.listResult[i].document;
							$('#' + dataList).append(option);
						}

					}
				}// else {
					// cleanFields(modal)
				//}
			}

		})
		.catch(function(response){
			console.log("este es el catch", response);
		});
	}else{
		console.log("limpiar tabla");
		$('#' + dataList).empty();
	}
}

function searchCompleteData(value, e, dataList, modal){
	console.log("searchCompleteData | buscando... para completar todos los datos");
	console.log("dato ingresado "+value);
	e.preventDefault();
	cleanFields(modal);
	let onlyNumber = /^\d+$/.test(value)
	if (onlyNumber){
		if ( value.length == 11 || value.length == 12 ){
			let validRut = validateRut(value);
			console.log(validRut);
			if ( validRut ){
				$('#' + dataList).empty();
				mostrarLoaderSearchClient(true, modal, e.srcElement.id); 
				sendAsyncPost("searchClientToSale", {documentClient: value})
				.then((response)=>{
					console.log(response);
					mostrarLoaderSearchClient(false, modal, e.srcElement.id); 
					if ( response.result == 2 ){
						let client = response.objectResult;
						setClientValues(modal, client.nombreReceptor, client.docReceptor, client.direccion, client.localidad, client.departamento, client.correo, client.celular);
						$('#' + modal).find('input[id^="inputDepartment"]').trigger('focus')
					}
				})
				.catch((error)=>{
					mostrarLoaderSearchClient(false, modal, e.srcElement.id); 
					console.log("catch :"+error);
				})
			}else showReplyMessage(1, "El rut ingresado no es válido.", "Buscar cliente", modal);
		}else if (value.length == 8){
			let validCi = validateCI(value);
			console.log(validCi);
			if ( validCi ){
				$('#' + dataList).empty();
				sendAsyncPost("searchClientToSale", {documentClient: value})
				.then((response)=>{
					console.log(response);
					if ( response.objectResult.docReceptor == value ){
						let client = response.objectResult;
						setClientValues(modal, client.nombreReceptor, client.docReceptor, client.direccion, client.localidad, client.departamento, client.correo, client.celular);
						$('#' + modal).find('input[id^="inputDepartment"]').trigger('focus')
					}else console.log("no se ha encontrado resultado");
				})
				.catch((error)=>{
					console.log("catch :"+error);
				})
			}else showReplyMessage(1, "La cédula ingresada no es válida.", "Buscar cliente", modal);
		}else if (value.length <= 0){
			$('#' + dataList).empty();
		}

	}
}