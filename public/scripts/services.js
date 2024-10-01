let lastId = 0;
let textToSearch = null;

function loadServices(){
	let response = sendPost("loadServices", {lastId: lastId, textToSearch: textToSearch});
	if(response.result == 2){
		if(lastId != response.lastId){
			lastId = response.lastId;
			let list = response.listResult;
			for (let i = list.length - 1; i >= 0; i--) {
				let row = createRow(list[i].idServicio, list[i].nombre, list[i].descripcion, list[i].iva, list[i].simboloMoneda, list[i].costoFormat, list[i].importeFormat, list[i].importeCot, list[i].activo);
				$('#tbodyServices').append(row);
			}
		}
	}else if(response.result == 0) showReplyMessage(0, response.message, "Listar clientes", null);
	resizeScreen();
}

function createRow(idService, name, description, iva, coin, cost, amount, amountQuote, active){
	var coinUi = coin == "UI" ? "$" : coin;
	var nullCell = "<td class='text-right toHidden1'> </td>";
	var row = "<tr id='" + idService + "'>";
	row += "<td class='text-right'>" + name + "</td>";
	row += "<td class='text-right toHidden2'>" + description + "</td>";
	row += "<td class='text-right toHidden2'>" + iva + "</td>";
	row += "<td class='text-right toHidden2'>" + coinUi + "  "+ cost + "</td>";
	if(amountQuote){
		row += "<td class='text-right'>" + coinUi + "  "+ amountQuote + "</td>";
		row += "<td class='text-right toHidden1'>" + coin + "  "+ amount + "</td>";
	}else{
		row += "<td class='text-right'>" + coin + "  "+ amount + "</td>";
		row += nullCell;
	}
	row += "<td class='text-center'>";
	if(active == 1)
		row += "<label class='switch'><input type='checkbox' checked onclick='changeCurrentValue(" + idService + ")'><span class='slider round'></span></label></td>";
	else
		row += "<label class='switch'><input type='checkbox' onclick='changeCurrentValue(" + idService + ")'><span class='slider round'></span></label></td>";
	row += "<td class='text-center toHidden1'><button onclick='showModifyService("+ idService +")' class='btn btn-sm background-template-color2 text-template-background mr-2'><i class='fas fa-edit text-mycolor'></i></button>";
	row += "<button onclick='showDeleteService("+ idService +")' class='btn btn-sm btn-danger'><i class='fas fa-trash-alt text-mycolor'></i></button></td></tr>";

	return row;
}

function changeCurrentValue(idService){
	let response = sendPost("activeService", {idService: idService});
	showReplyMessage(response.result, response.message, "Estado servicio", null);
}

function showCreateService(){
	clearModalCreateModifyService();
	$('#modalCreateModifyService').modal();
	$('#titleModalCreateModifyService').html("Nuevo servicio");
	$('#btnConfirmModifyService').off('click');
	$('#btnConfirmModifyService').click(function(){
		createService();
	});
}

function clearModalCreateModifyService(){
	$('#textAreaDescriptionService').val('');
	$('#inputNameService').val('');
	$('#inputCostService').val('');
	$('#inputAmountService').val('');
	$("#selectListIva > option:selected").removeAttr("selected");
	$('#typeCoinUYI').prop('checked', false);
	$('#typeCoinUSD').prop('checked', false);
	$('#typeCoinUYU').prop('checked', true);
}

function createService(){
	let name = $('#inputNameService').val() || null;
	let description = $('#textAreaDescriptionService').val() || null;
	let amount = $('#inputAmountService').val() || null;
	let cost = $('#inputCostService').val() || null;
	let selectIVA = $('#selectListIva > option:selected').attr('id');
	let checkCoinUSD = $('#typeCoinUSD').is(':checked');
	let checkCoinUYI = $('#typeCoinUYI').is(':checked');

	if(name){
		if(cost > 0){
			if(amount > 0){
				let typeCoin = "UYU";
				if(checkCoinUSD)
					typeCoin = "USD";
				else if(checkCoinUYI)
					typeCoin = "UYI";

				let data = {name: name, description: description, cost: cost, amount: amount, idIVA: selectIVA, typeCoin: typeCoin};
				let response = sendPost("createService", data);
				showReplyMessage(response.result, response.message, "Crear servicio", "modalCreateModifyService");
				$('#modalButtonResponse').click(function(){
					if(response.result == 2)
						window.location.reload();
				});
			}else showReplyMessage(1, "Debe ingresar un importe mayor a cero al servicio.", "Importe campo requerido", "modalCreateModifyService");
		}else showReplyMessage(1, "Debe ingresar un costo mayor a cero al servicio.", "Costo campo requerido", "modalCreateModifyService");
	}else showReplyMessage(1, "Debe ingresar un nombre para modificar el servicio.", "Nombre campo requerido", "modalCreateModifyService");
}


function showModifyService(idService){
	let response = sendPost('getServiceSelected', {idService: idService});
	if(response.result == 2){
		let service = response.objectResult;
		$('#inputNameService').val(service.nombre);
		$('#textAreaDescriptionService').val(service.descripcion);
		$('#inputAmountService').val(service.importe);
		$('#inputCostService').val( parseFloat(service.costo).toFixed(2));

		$("#selectListIva > option:selected").removeAttr("selected");
		$("#selectListIva option[id='" + service.idIVA + "']").attr('selected', 'selected');

		if(service.moneda == "UYU"){
			$('#typeCoinUYI').prop('checked', false);
			$('#typeCoinUSD').prop('checked', false);
			$('#typeCoinUYU').prop('checked', true);
		}else if(service.moneda == "USD"){
			$('#typeCoinUYI').prop('checked', false);
			$('#typeCoinUYU').prop('checked', false);
			$('#typeCoinUSD').prop('checked', true);
		}else{
			$('#typeCoinUSD').prop('checked', false);
			$('#typeCoinUYU').prop('checked', false);
			$('#typeCoinUYI').prop('checked', true);
		}

		$('#modalCreateModifyService').modal();

		$('#titleModalCreateModifyService').html("Modificar servicio");
		$('#btnConfirmModifyService').off('click');
		$('#btnConfirmModifyService').click(function(){
			modifyService(idService);
		});
	}else showReplyMessage(response.result, response.message, "Modificar servicio", null);
}

$('#modalCreateModifyService').on('shown.bs.modal', function(){
	$('#inputNameService').focus();
});

function modifyService(idService){
	let name = $('#inputNameService').val() || null;
	let description = $('#textAreaDescriptionService').val() || null;
	let amount = $('#inputAmountService').val() || null;
	let cost = $('#inputCostService').val() || null;
	let selectIVA = $('#selectListIva > option:selected').attr('id');
	let checkCoinUSD = $('#typeCoinUSD').is(':checked');
	let checkCoinUYI = $('#typeCoinUYI').is(':checked');

	if(name){
		if(cost > 0){
			if(amount > 0){
				let typeCoin = "UYU";
				if(checkCoinUSD)
					typeCoin = "USD";
				else if(checkCoinUYI)
					typeCoin = "UYI";

				let data = {idService: idService, name: name, description: description, cost: cost, amount: amount, idIVA: selectIVA, typeCoin: typeCoin};
				let response = sendPost("modifyService", data);
				showReplyMessage(response.result, response.message, "Modificar servicio", "modalCreateModifyService");
				if(response.result == 2){
					clearModalCreateModifyService();
					service = response.service;
					$('#' + idService).replaceWith(createRow(idService, service.nombre, service.descripcion, service.valorIVA, service.simboloMoneda, service.costoFormat, service.importeFormat, service.importeCot, service.activo));
				}
			}else showReplyMessage(1, "Debe ingresar un importe para modificar el servicio.", "Importe campo requerido", "modalCreateModifyService");
		}else showReplyMessage(1, "Debe ingresar un costo para modificar el servicio.", "Costo campo requerido");
	}else showReplyMessage(1, "Debe ingresar un nombre para modificar el servicio.", "Nombre campo requerido", "modalCreateModifyService")
}

function showDeleteService(idService){
	$('#modalDeleteService').modal();
	$('#btnConfirmDeleteService').off('click');
	$('#btnConfirmDeleteService').click(function(){
		deleteService(idService);
	});
}

function deleteService(idService){
	let response = sendPost("deleteService", {idService: idService});
	showReplyMessage(response.result, response.message, "Borrar servicio", "modalDeleteService");
	if(response.result == 2)
		$('#' + idService).remove();
}

function writeCost(inputCost){
	let iva = parseFloat($('#selectListIva > option:selected').attr('value'));
	let valueCost = inputCost.value || null;
	if(valueCost){
		if(iva > 0){
			valueCost = parseFloat(valueCost);
			let finalValue = parseFloat(valueCost + ((iva / 100) * valueCost)).toFixed(2);
			$('#inputAmountService').val(finalValue);
		}else{
			let finalValue = parseFloat(valueCost).toFixed(2);
			$('#inputAmountService').val(finalValue);
		}
	}else $('#inputAmountService').val('');
}

function writeAmount(inputAmount){
	let iva = parseFloat($('#selectListIva > option:selected').attr('value'));
	let valueAmount = inputAmount.value || null;

	if(valueAmount){
		if(iva > 0){
			valueAmount = parseFloat(valueAmount);
			let finalValue = parseFloat(valueAmount/ (1+iva/100)).toFixed(2);
			$('#inputCostService').val(finalValue);
		}else{
			let finalValue = parseFloat(valueAmount).toFixed(2);
			$('#inputCostService').val(finalValue);
		}
	}else $('#inputCostService').val('');
}

function changeIVA(selectIVA){
	let iva = parseFloat(selectIVA.value);
	let amount = parseFloat($('#inputAmountService').val()) || null;
	let cost = parseFloat($('#inputCostService').val()) || null;

	if(amount){
		let finalValue = parseFloat(amount/ (1+iva/100)).toFixed(2);
		$('#inputCostService').val(finalValue);
	}
	else if(cost){
		let finalValue = parseFloat(cost + (cost*(iva/100))).toFixed(2);
		$('#inputAmountService').val(finalValue);
	}
}

function keyPressCreateService(eventEnter, value, size){
	////console.log(eventEnter.keyCode);
	if(eventEnter.keyCode == 13 && !eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputNameService")
			$('#textAreaDescriptionService').focus();
		else if(eventEnter.srcElement.id == "textAreaDescriptionService")
			$('#inputCostService').focus();
		else if(eventEnter.srcElement.id == "inputCostService")
			$('#typeCoinUYU').focus();
		else if(eventEnter.srcElement.id == "typeCoinUYU")
			$('#inputAmountService').focus();
		else if(eventEnter.srcElement.id == "inputAmountService")
			$('#btnConfirmModifyService').click();
	}
	else if(eventEnter.keyCode == 13 && eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputAmountService")
			$('#typeCoinUYU').focus();
		else if(eventEnter.srcElement.id == "typeCoinUYU")
			$('#inputCostService').focus();
		else if(eventEnter.srcElement.id == "inputCostService")
			$('#textAreaDescriptionService').focus();
		else if(eventEnter.srcElement.id == "textAreaDescriptionService")
			$('#inputNameService').focus();
	}
	else if(value != null && value.length >= size) return false;
}

function searchService(inputText){

	let textTemp = inputText.value || null;

	if (textTemp != null){
		if(textTemp.length >= 3){
			lastId = 0;
			textToSearch = textTemp;
			$('#tbodyServices').empty();
			loadServices();
			return;
		}else{
			lastId = 0;
			textToSearch = null;
			$('#tbodyServices').empty();
			loadServices();
			return;
		}
	}else{
		lastId = 0;
		textToSearch = null;
		$('#tbodyServices').empty();
		loadServices();
		return;
	}
}
