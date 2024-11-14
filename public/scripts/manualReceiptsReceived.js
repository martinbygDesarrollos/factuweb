let lastId = 0;
let filterNameReceiver = null;

function loadCleanTable(){
	lastId = 0;
	filterNameReceiver = null;
	$('#tbodyManualReceipt').empty();
	getManualReceiptReceived();
}

function getManualReceiptReceived(){
	let data = {lastId: lastId, filterNameReceiver: filterNameReceiver};
	let response = sendPost('getManualReceiptsReceived', data);
	if(response.result == 2){
		lastId = response.lastId;
		let list = response.listResult;
		console.log(list)
		console.log(lastId)
		// for(var i = list.length - 1; i >= 0; i--){ // ESTE FOR LO INVIERTE (IDK WHY)
		// for(var i = 0; i <= list.length; i++){
		// 	let newRow = createNewRow(list[i].indice, list[i].fecha, list[i].rut, list[i].razonSocial, list[i].moneda, list[i].total);
		// 	console.log(list[i].indice + " " + list[i].fecha)
		// 	$('#tbodyManualReceipt').append(newRow);
		// }
		for (const item of list) {
			let newRow = createNewRow(
			  item.indice,
			  item.fecha,
			  item.rut,
			  item.razonSocial,
			  item.moneda,
			  item.total
			);
			// console.log(item.hasOwnProperty('indice') ? item.indice : null, item.fecha);
			$('#tbodyManualReceipt').append(newRow);
		}

	}else if(response.result == 0) showReplyMessage(response.result, response.message, "Ocurrió un error", null);
	resizeScreen();
}

function createNewRow(index, dateMaked, docClient, nameClient, typeCoin, total){
	let row = "<tr id='" + index + "'>";
	row += "<td class='text-right'>" + dateMaked + "</td>";
	row += "<td class='text-right toHidden1'>" + docClient + "</td>";
	row += "<td class='text-right'>" + nameClient + "</td>";
	row += "<td class='text-right toHidden1'>" + typeCoin + "</td>";
	row += "<td class='text-right'>" + total + "</td>";
	row += "<td class='text-center'>";
	row += "<button onclick='showModifyManualReceiptReceived("+ index +")' class='btn btn-sm background-template-color2 text-template-background mr-1'><i class='fas fa-edit text-mycolor'></i></button>";
	row += "<button onclick='showDeleteManualReceiptReceived("+ index +")' class='btn btn-sm background-template-color2 text-template-background'><i class='fas fa-trash-alt text-mycolor'></i></button></td></tr>";

	return row;
}

function searchManualReceiptReceived(inputToSearch){
	let textToSearch = inputToSearch.value;
	if(textToSearch.length > 3){
		lastId = 0;
		filterNameReceiver = textToSearch;
		$('#tbodyManualReceipt').empty();
		getManualReceiptReceived();
	}else if(textToSearch.length == 0){
		lastId = 0;
		filterNameReceiver = null;
		$('#tbodyManualReceipt').empty();
		getManualReceiptReceived();
	}
}

function loadProviderSuggestion(inputDocumentProvider){
	var documentProvider = inputDocumentProvider.value;
	clearAndHideBalance();
	if(documentProvider.length > 2){
		var response = sendPost('getBusinessForModal', {suggestionPerson: documentProvider, prepareFor: 'PROVIDER'});
		clearListProviders();
		if(response.result == 2){
			listProviders = document.getElementById("listProviders");
			for (var i = response.listPeople.length - 1; i >= 0; i--) {
				var option = document.createElement("option");
				option.label = response.listPeople[i].name;
				option.value = response.listPeople[i].document;
				listProviders.append(option);
			}
		}
	}
	if(documentProvider.length > 10)
		clearListProviders();
}

function clearAndHideBalance(){
	document.getElementById('inputBalaceDateUYU').value = "";
	document.getElementById('inputBalaceDateUSD').value = "";
	document.getElementById('containerBalanceProviders').style.visibility = "hidden";
}

function clearListProviders(){
	listProviders = document.getElementById("listProviders");
	if(listProviders){
		for (var i = listProviders.children.length -1; i >= 0; i--) {
			listProviders.children[i].remove();
		}
	}
}

function showModifyManualReceiptReceived(itemIndex){
	document.getElementById('inputDateManualReceipt').value = parceDateForInput(getCellValue(itemIndex, 0));
	document.getElementById('inputTotalManualReceipt').value = reverseNumberFormat(getCellValue(itemIndex, 4));

	$('#modalModifyManualReceipt').modal();
	$("#buttonConfirmModifyManualReceipt").off('click');
	$('#buttonConfirmModifyManualReceipt').click(function(){
		modifyManualReceiptReceived(itemIndex);
	});
}

function modifyManualReceiptReceived(itemIndex){
	let dateMaked = document.getElementById('inputDateManualReceipt').value || null;
	let total = document.getElementById('inputTotalManualReceipt').value || null;

	if(dateMaked){
		if(total){
			let response = sendPost('modifyManualReceiptReceived', {index: itemIndex, dateMaked: dateMaked, total: total});
			showReplyMessage(response.result, response.message, "Modificar recibo manual", "modalModifyManualReceipt");
			if(response.result == 2)
				loadCleanTable();
			return;
		}else showReplyMessage(1, "El recibo manual debe tener un monto total para ser modificado", "Campo total requerido", "modalModifyManualReceipt");
	}else showReplyMessage(1, "El recibo manual debe contar con una fecha para ser modificado.", "Campo fecha requerido", "modalModifyManualReceipt");
}

function showDeleteManualReceiptReceived(itemIndex){
	document.getElementById('textDeleteManualReceipt').innerHTML = "¿Desea elimiar el recibo al proveedor " + getCellValue(itemIndex, 2) + "?"
	$('#modalDeleteManualReceipt').modal();
	$("#buttonConfirmDeleteManualReceipt").off('click');
	$('#buttonConfirmDeleteManualReceipt').click(function(){
		deleteManualReceiptReceived(itemIndex);
	});
}

function deleteManualReceiptReceived(itemIndex){
	let response = sendPost('deleteManualReceiptReceived', { index: itemIndex});
	showReplyMessage(response.result, response.message, "Borrar recibo", "modalDeleteManualReceipt");
	if(response.result == 2){
		lastId = 0;
		filterNameReceiver = null;
		$('#tbodyManualReceipt').empty();
		getManualReceiptReceived();
	}
}

function getSaldoDocument(){
	var documentProvider = document.getElementById('inputProviderCreateManualReceipt').value || null;
	if(documentProvider){
		if(documentProvider.length == 8 || documentProvider.length == 11 || documentProvider.length == 12){
			var response = sendPost("getBalanceProvider", {documentProvider: documentProvider});
			if(response.result == 2){
				document.getElementById('inputBalaceDateUYU').value = '$ ' + response.balanceUYU;
				document.getElementById('inputBalaceDateUSD').value = 'U$S ' + response.balanceUSD;
				document.getElementById('containerBalanceProviders').style.visibility = "visible";
				return;
			}else clearAndHideBalance();
		}
	}else clearAndHideBalance();
}

$('#modalModifyManualReceipt').on('shown.bs.modal', function(){
	$('#inputDateManualReceipt').focus();
});

function keyPressModifyManualReceipt(eventEnter){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputDateManualReceipt")
			$('#inputTotalManualReceipt').focus();
		else if(eventEnter.srcElement.id == "inputTotalManualReceipt")
			$('#buttonConfirmManualReceipt').click();
	}
}

function getCellValue(itemIndex, position){
	var td= $('#'+ itemIndex).children();
	return td[position].innerHTML || " ";
}

function reverseNumberFormat(value){
	let valueArray = value.split(",");
	value = valueArray[0].replace(/\./g,"");
	return value;
}