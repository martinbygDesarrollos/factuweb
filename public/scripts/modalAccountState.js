function prepareModalAccountState(prepareFor){
	$('#modalAccountState').modal();

	$('#buttonInputCheckDateEnding').html('Utilizar fecha hasta: ' + getFormatDateHTML(getNextMonth()));

	let response = sendPost('getLastAccountStateInfo', {prepareFor: prepareFor});
	console.log(response);
	if(response.result == 2){
		$('#inputPerson').val(response.information.document);
		$('#inputDateInit').val(response.information.dateInit);
		$('#inputDateEnding').val(response.information.dateEnding);
		if ($('#inputDateInit').val() == "2000-01-01"){
			$('#checkDateInitFromBeginning').prop("checked",true)
		}else $('#checkDateInitFromBeginning').prop("checked",false)

		if(response.information.selectedCoin == "USD")
			document.getElementById('inputTypeCoinUSD').checked = true;

	}else cleanInputsAccountState();

	if(response.result != 0 ){
		$('#containerClashCollectionClient').css('visibility', 'hidden');
		if(prepareFor == "CLIENT"){
			if(response.showCheckBoxCash == "SI"){
				$('#containerClashCollectionClient').css('visibility', 'visible');
				let checkShowCashCollection = document.getElementById('inputIncludeCashCollection');
				if(response.result == 2){
					if(response.information.includeCashCollection == "SI")
						checkShowCashCollection.checked = true;
				}
			}
		}
	}

	let placeholder = "Ingrese el Proveedor...";
	if(prepareFor == "CLIENT")
		placeholder = "Ingrese el Cliente...";
	$('#inputPerson').attr('placeholder', placeholder);

	$('#inputPerson').off('change');
	$('#inputPerson').change(function(){
		loadPoeple(prepareFor);
	});

	$('#inputPerson').off('keyup');
	$('#inputPerson').keyup(function(){
		loadPoeple(prepareFor);
	})

	$('#btnConfirmModalAccountState').off('click');
	$('#btnConfirmModalAccountState').click(function(){
		getAccountState(prepareFor);
	});
}

$('#modalAccountState').on('shown.bs.modal', function () {
	$('#inputPerson').focus();
});

function cleanInputsAccountState(){
	document.querySelector('#inputPerson').value = "";

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

	document.getElementById('inputTypeCoinUYU').checked = true;
	$('#inputPerson').focus();
}

function keyPressModal(eventEnter, value, size){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputPerson")
			$('#inputDateInit').focus();
		else if(eventEnter.srcElement.id == "inputDateInit")
			$('#inputDateEnding').focus();
		else if(eventEnter.srcElement.id == "inputDateEnding")
			$('#inputTypeCoinUYU').focus();
		else if(eventEnter.srcElement.id == "inputTypeCoinUYU")
			$('#btnConfirmModalAccountState').focus();
		else if(eventEnter.srcElement.id == "inputTypeCoinUSD")
			$('#btnConfirmModalAccountState').focus();
		else if(eventEnter.srcElement.id == "btnConfirmModalAccountState")
			$('#btnConfirmModalAccountState').click();
	}else if(value != null && value.length == size) return false;
}

function getAccountState(prepareFor){
	const regularIsNumeric = /^[0-9]*$/;
	let insertedDocument = document.querySelector('#inputPerson').value || null;
	let selectedDateInit = document.getElementById('inputDateInit').value || null;
	let selectedDateEnding = document.getElementById('inputDateEnding').value || null;
	let selectedTypeCoin = document.getElementById('inputTypeCoinUYU').checked;

	let showCashCollection = "NO";
	if($('#containerClashCollectionClient').is(':visible')){
		if(document.getElementById('inputIncludeCashCollection').checked)
			showCashCollection = "SI";
	}

	if(insertedDocument){
		let resultFindDocument = sendPost("findWithDocument",{document: insertedDocument, prepareFor: prepareFor});
		if(resultFindDocument.result == 2){
			let idSelected = null;
			if(prepareFor == "CLIENT") idSelected = resultFindDocument.client.id;
			else idSelected = resultFindDocument.provider.idProveedor;
			if(selectedDateInit){
				if(selectedDateEnding){
					let dateInit = new Date(selectedDateInit);
					let dateEnding = new Date(selectedDateEnding);
					if(dateInit <= dateEnding){
						let typeCoin = "USD";
						if(selectedTypeCoin)
							typeCoin = "UYU";

						let url = getSiteURL() + 'generar-estado-cuenta/' + idSelected + '/' + selectedDateInit  + '/' + selectedDateEnding + '/' + typeCoin + '/' + prepareFor + '/' + showCashCollection;
						window.location.href = url;
					}else showReplyMessage(1, "La fecha de comienzo no puede ser posterior a la fecha final.", "Estado de cuenta", "modalAccountState");
				}else showReplyMessage(1, "La fecha 'hasta' no puede ser ingresada vacia.", "Fecha 'hasta' campo requerido", "modalAccountState");
			}else showReplyMessage(1, "La fecha 'desde' no puede ser ingresada vacia.", "Fecha 'desde' campo requerido", "modalAccountState");
		}else showReplyMessage(resultFindDocument.result, resultFindDocument.message, "Estado de cuenta", "modalAccountState");
	}else showReplyMessage(0, "Debe ingresar un rut de cliente para continuar.", "Estado de cuenta", "modalAccountState");
	$('#inputPerson').focus();
}

function loadPoeple(prepareFor){
	let valuePerson = document.getElementById('inputPerson').value;
	let listPeople = null;
	if(valuePerson.length >= 3){

		sendAsyncPost("getBusinessForModal",{suggestionPerson: valuePerson, prepareFor: prepareFor})
		.then((response)=>{
			if(response.result == 2){
				listPeople = document.getElementById("listPeople");
				clearListPeople(listPeople);
				for (let i = response.listPeople.length - 1; i >= 0; i--) {
					let option = document.createElement("option");
					option.label = response.listPeople[i].name;
					option.value = response.listPeople[i].document;
					listPeople.append(option);
				}
			}else if(response.result == 1){
				clearListPeople(listPeople);
			}
		})
	}

	if(valuePerson.length > 10)
		clearListPeople(listPeople);
}

function clearListPeople(listPeople){
	if(listPeople){
		for (let i = listPeople.children.length -1; i >= 0; i--) {
			listPeople.children[i].remove();
		}
	}
}

function signOut(){
	let response = sendPost('signOut',null);
	window.location.replace(getSiteURL());
}

function changeDateInitFromBeginning(){

	console.log($('#inputDateInit').val());
	console.log($('#checkDateInitFromBeginning').prop("checked"));
	if ( $('#checkDateInitFromBeginning').prop("checked") ){
		$('#inputDateInit').val("2000-01-01");
	}
	else{
		let responseGetConfigDate = sendPost('getConfiguration', {nameConfiguration: "INTERVALO_FECHA_ACCOUNT_SATE"});
		if(responseGetConfigDate.result == 2)
			onLoadInputDate(document.getElementById('inputDateInit'), responseGetConfigDate.configValue);
		else
			onLoadInputDate(document.getElementById('inputDateInit'), 30);
	}
}