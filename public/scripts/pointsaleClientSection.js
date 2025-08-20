


$('#modalResponse').off('shown.bs.modal').on('shown.bs.modal', function(){
	$(this).data('bs.modal')._config.keyboard = false;
	$(this).data('bs.modal')._config.backdrop = 'static';
	$('#modalResponse button').focus();
})

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