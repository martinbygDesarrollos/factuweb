
$('#modalSetClient').on('shown.bs.modal', function(){
	$('#inputTextToSearchClient').focus();
})

$('#modalResponse').on('shown.bs.modal', function(){
	$('#modalResponse button').focus();
})

function selectClient(){
	let documentClient = $('#inputDocumentClient').val() || null;
	let nameClient = $('#inputNameClient').val() || null;
	let address = $('#inputAddressClient').val() || null;
	let city = $('#inputCityClient').val() || null;
	let department = $('#inputDepartmentClient').val() || null;
	let email = $('#inputEmailClient').val() || null;
	let phone = $('#inputPhoneClient').val() || null;

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
			if(validateRut(documentClient))
				$('#selectTypeVoucher').val(111).change();
			$('#buttonModalClientWithName').html(nameClient + " " +  documentClient);
			$('#buttonCancelClient').css('display', 'block');
			$('#modalSetClient').modal('hide');
		}else showReplyMessage(1, "El documento ingresado no pudo validarse como RUT y tampoco como CI por favor vuelva a ingresarlo", "Documento no valido", "modalSetClient");
	}else showReplyMessage(1, "Para ingresar un cliente este debe contar con Nombre y Documento, de lo contrario no ingresar.", "Nombre y Documento requeridos", "modalSetClient");
}

let clientSelected = [];
function createClient(documentClient, nameClient, address, city, department, email, phone){
	clientSelected.push({
		document: documentClient,
		name: nameClient,
		address: address,
		city: city,
		department: department,
		email: email,
		phone: phone
	});
}

//esta función busca cliente desde la vista de ventas
function searchClient(inputToSearch, e){ //cuando se ingresan numeros el el buscador de cliente por rut se llema a esta función para buscar los datos
	e.preventDefault();
	if ( inputToSearch.value.length > 2 ){
		//$('#listClient').empty();
		const response = sendPost("searchClientsToSale",{textToSearch: inputToSearch.value });
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
				}
				else setClientValues(null, null, null, null, null, null, null);
			}
		}
	}else{
		console.log("limpiar tabla");
		$('#listClient').empty();
		setClientValues(null, null, null, null, null, null, null);
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

function cancelClinetSelected(){
	clientSelected = [];
	$('#inputTextToSearchClient').val("");
	$('#buttonModalClientWithName').html("Agregar <u>C</u>liente <i class='fas fa-user-plus'></i>");
	setClientValues(null, null, null, null, null, null, null);
	$('#selectTypeVoucher').val(101).change();
	$('#buttonCancelClient').css('display', 'none');
}

function keyPressAddClient(keyPress, value, size){
	////console.log("keyPressAddClient");
	if(keyPress.keyCode == 13 && !keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputTextToSearchClient")
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
			$('#inputTextToSearchClient').focus();
	}
	else if(value != null && value.length == size) {
		return false;
	}
}

function searchCompleteData(value, e){
	console.log("buscando... para completar todos los datos");
	console.log("dato ingresado "+value);
	e.preventDefault();
	setClientValues(null, null, null, null, null, null, null);
	//console.log("largo rut "+value.length);

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
						$("#inputDepartmentClient").focus();
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
						$("#inputDepartmentClient").focus();
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