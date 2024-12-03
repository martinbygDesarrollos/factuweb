let lastId = 0;
let filterNameReceiver = null;

function loadCleanTable(){
	lastId = 0;
	filterNameReceiver = null;
	$('#tbodyVauchers').empty();
	getManualReceiptsEmitted();
}

function getManualReceiptsEmitted(){
	console.log("getManualReceiptsEmitted")
	var data = {
		lastId: lastId,
		filterNameReceiver: filterNameReceiver
	};

	var response = sendPost("getManualReceiptsEmitted", data);
	console.log(response)
	if(response.result == 2){
		var list = response.vouchers;
		if(lastId !=  response.lastId)
			lastId = response.lastId;
		console.log(list)
		console.log(lastId)
		// for (var i = list.length - 1; i >= 0; i--) {
		// for (var i = 0; i < list.length; i++) {
		// 	var row = getNewRow(list[i].index, list[i].dateReceipt, list[i].docClient, list[i].nameClient, list[i].typeCoin, list[i].total);
		// 	$('#tbodyVauchers').append(row);
		// }
		for (const item of list) {
			let row = getNewRow(
			  item.index,
			  item.dateReceipt,
			  item.docClient,
			  item.nameClient,
			  item.typeCoin,
			  item.total
			);
			$('#tbodyVauchers').append(row);
		}
	}
	resizeScreen();
}

function searchManualReceipt(inputToSearch){
	let textToSearch = inputToSearch.value;
	if(textToSearch.length > 2){
		filterNameReceiver = textToSearch;
		lastId = 0;
		$('#tbodyVauchers').empty();
		getManualReceiptsEmitted();
	}else if(textToSearch.length == 0){
		filterNameReceiver = null;
		lastId = 0;
		$('#tbodyVauchers').empty();
		getManualReceiptsEmitted();
	}
}

function getNewRow(index, dateReceipt, docClient, nameClient, typeCoin, total){
	var row = "<tr id='" + index + "'>";
	row += "<td class='text-right'>" + dateReceipt + "</td>";
	row += "<td class='text-right toHidden1'>" + docClient + "</td>";
	row += "<td class='text-left'>" + nameClient + "</td>";
	row += "<td class='text-right toHidden1'>" + typeCoin + "</td>";
	row += "<td class='text-right'>" + total + "</td>";
	row += "<td class='text-center'>";
	row += "<button onclick='showModifyManualReceipts("+ index +")' class='btn btn-sm background-template-color2 text-template-background mr-1' data-toggle='tooltip' data-placement='bottom' title='Modificar recibo manual'><i class='fas fa-edit text-mycolor'></i></button>";
	row += "<button onclick='showDeleteManualReceipts("+ index +")' class='btn btn-sm btn-danger'><i class='fas fa-trash-alt text-mycolor'></i></button></td></tr>";

	return row;
}

function loadClientSuggestion(inputDocumentClient){
	var documentClient = inputDocumentClient.value;
	clearAndHideBalance();
	if(documentClient.length > 2){
		var response = sendPost('getBusinessForModal', {suggestionPerson: documentClient, prepareFor: 'CLIENT'});
		if(response.result == 2){
			listClients = document.getElementById("listClients");
			clearListClients();
			for (var i = response.listPeople.length - 1; i >= 0; i--) {
				var option = document.createElement("option");
				option.label = response.listPeople[i].name;
				option.value = response.listPeople[i].document;
				listClients.append(option);
			}
		}else if(response.result == 1){
			clearListClients();
		}
	}
	if(documentClient.length > 10)
		clearListClients();
}

function clearListClients(){
	listClients = document.getElementById("listClients");
	if(listClients){
		for (var i = listClients.children.length -1; i >= 0; i--) {
			listClients.children[i].remove();
		}
	}
}

$('#inputClientCreateManualReceipt').focusout(function(){
	getSaldoDocument();
});

function getSaldoDocument(){
	var documentClient = document.getElementById('inputClientCreateManualReceipt').value || null;
	if(documentClient){
		if(documentClient.length == 8 || documentClient.length == 11 || documentClient.length == 12){
			var response = sendPost("getBalanceClient", {documentClient: documentClient});
			if(response.result == 2){
				document.getElementById('inputBalaceDateUYU').value = '$ ' + response.balanceUYU;
				document.getElementById('inputBalaceDateUSD').value = 'U$S ' + response.balanceUSD;
				document.getElementById('containerBalanceClient').style.visibility = "visible";
				return;
			}else clearAndHideBalance();
		}
	}else clearAndHideBalance();

}

function clearAndHideBalance(){
	document.getElementById('inputBalaceDateUYU').value = "";
	document.getElementById('inputBalaceDateUSD').value = "";
	document.getElementById('containerBalanceClient').style.visibility = "hidden";
}

function showDeleteManualReceipts(itemIndex){
	document.getElementById('modalBodyDelete').style.display = "block";
	document.getElementById('modalBodyModify').style.display = "none";
	document.getElementById('titleModifyManualReceipts').innerHTML = "Borrar recibo manual";

	$("#buttonConfirmModifyManualReceipt").off('click');
	$("#buttonConfirmModifyManualReceipt").click(function(){
		deleteManualReceipts(itemIndex);
	});
	$('#modalDeleteModifyManualReceipt').modal();
}

function showModifyManualReceipts(itemIndex){
	document.getElementById('modalBodyDelete').style.display = "none";
	document.getElementById('modalBodyModify').style.display = "block";
	document.getElementById('titleModifyManualReceipts').innerHTML = "Modificar recibo manual";

	document.getElementById('inputDateModifyManualReceipt').value = parceDateForInput(getCellValue(itemIndex, 0));
	document.getElementById('inputTotalModifyManualReceipt').value = reverseNumberFormat(getCellValue(itemIndex, 4));

	if(getCellValue(itemIndex, 3) == '$') $('#inputCoinUYUModifyManualReceipt').prop('checked',true);
	else if(getCellValue(itemIndex, 3) == 'U$S') $('#inputCoinUSDModifyManualReceipt').prop('checked', true);

	$("#buttonConfirmModifyManualReceipt").off('click');
	$("#buttonConfirmModifyManualReceipt").click(function(){
		modifyManualReceipt(itemIndex);
	});
	$('#modalDeleteModifyManualReceipt').modal();
	$('#modalDeleteModifyManualReceipt').on('shown.bs.modal', function () {
		$('#inputDateModifyManualReceipt').focus();
	});
}

function reverseNumberFormat(value){
	let valueArray = value.split(",");
	value = valueArray[0].replace(/\./g,"");
	return value;
}

function getCellValue(itemIndex, position){
	var td= $('#'+ itemIndex).children();
	return td[position].innerHTML || " ";
}

function modifyManualReceipt(itemIndex){
	var dateReceipt = document.getElementById('inputDateModifyManualReceipt').value;
	var typeCoinUYU = document.getElementById('inputCoinUYUModifyManualReceipt').checked;
	var total = document.getElementById('inputTotalModifyManualReceipt').value;
	var typeCoin = "USD";
	if(typeCoinUYU) typeCoin = "UYU";

	var response = sendPost('modifyManualReceipt', {indexVoucher: itemIndex, dateReceipt: dateReceipt, total: total, typeCoin: typeCoin});
	if(response.result == 2)
		loadCleanTable();
	showReplyMessage(response.result, response.message, "Modificar recibo manual", "modalDeleteModifyManualReceipt");
}

function deleteManualReceipts(itemIndex){
	var response = sendPost("deleteManualReceipt", { indexVoucher: itemIndex});
	showReplyMessage(response.result, response.message, "Borrar recibo manual", "modalDeleteModifyManualReceipt");
	if(response.result == 2)
		$('#' + itemIndex).remove();
}

function keyPressDeleteModifyManualReceipt(eventEnter){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputDateModifyManualReceipt")
			$('#inputTotalModifyManualReceipt').focus();
		else if(eventEnter.srcElement.id == "inputTotalModifyManualReceipt")
			$('#inputCoinUYUModifyManualReceipt').focus();
		else if(eventEnter.srcElement.id == "inputCoinUYUModifyManualReceipt")
			$('#buttonConfirmModifyManualReceipt').focus();
		else if(eventEnter.srcElement.id == "inputCoinUSDModifyManualReceipt")
			$('#buttonConfirmModifyManualReceipt').focus();
		else if(eventEnter.srcElement.id == "buttonConfirmModifyManualReceipt")
			$('#buttonConfirmModifyManualReceipt').click();
	}
}
