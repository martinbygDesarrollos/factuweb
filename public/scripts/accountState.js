var discountPercentage = null;

function showItemModalModifyAccountState(btnDate){
	$('#modalModifyAccountState').modal();
	let arrayDate = btnDate.id.split('/');
	let formatDate = arrayDate[2] + "-" + arrayDate[1] + "-" + arrayDate[0];
	$('#inputDateFrom').val(formatDate)
}

function showModalModifyAccountState(btnDate){
	$('#modalModifyAccountState').modal();
	$('#inputDateFrom').val(btnDate.name)
}

function openModalVoucher(button, prepareFor, view){
	mostrarLoader(true)
	let idVoucher = button.id;
	let responseGetCFE = sendPost('getVoucherCFE', {idVoucher: idVoucher, prepareFor: prepareFor});
	if(responseGetCFE.result == 2){
		mostrarLoader(false)
		let iFrame = document.getElementById("frameSeeVoucher");
		let screenHeight = screen.height - 100;
		//iFrame.style.height = screenHeight + "px";
		var dstDoc = iFrame.contentDocument || iFrame.contentWindow.document;
		dstDoc.write(responseGetCFE.voucherCFE.representacionImpresa);
		dstDoc.close();

		$('#buttonExportVoucher').off('click');
		$('#buttonExportVoucher').click(function(){
			// exportVoucher(idVoucher, prepareFor);
			exportVoucherNew(idVoucher, prepareFor);
		});
		
		$('#buttonDownloadVoucher').off('click');
		$('#buttonDownloadVoucher').click(function(){
			downloadVoucher(idVoucher, prepareFor);
		});

		if ( responseGetCFE.voucherCFE.isAnulado ){
			$("#seeVoucherIsAnuladoMotivo").empty();
			$("#seeVoucherIsAnuladoMotivo").append("<strong>Motivo:</strong> "+responseGetCFE.voucherCFE.motivoRechazo);
			$("#seeVoucherIsAnulado").removeAttr("hidden");
			$("#seeVoucherIsAnulado").removeClass("fade");
		}else{
			$("#seeVoucherIsAnuladoMotivo").empty();

			$("#seeVoucherIsAnulado").attr("hidden",true);
			$("#seeVoucherIsAnulado").addClass("fade");
		}



		if(prepareFor === "CLIENT"){
			console.log("open modal voucher para clientes");
			let responseGetConfig = sendPost('getConfiguration', {nameConfiguration: "PERMITIR_NOTAS_DE_DEBITO"});
			console.log(responseGetConfig);
			console.log(responseGetCFE.voucherCFE.tipoCFE);
			if(responseGetConfig.result == 2){
				if(responseGetConfig.configValue == "NO" && responseGetCFE.voucherCFE.tipoCFE == 112)
					$('#buttonCancelVoucher').css('visibility', 'hidden');
				else{
					$('#buttonCancelVoucher').css('visibility', 'visible');
					let responseConsultCaes = sendPost('consultCaes', {typeCFE: responseGetCFE.voucherCFE.tipoCFE});
					console.log(responseConsultCaes);
					console.log("vista "+view);
					if(responseConsultCaes.result == 2){
						if( view != "sale"){
							if (view == 'accountState'){
								if (($('#'+idVoucher).text().includes("Cobranza")) && (responseGetCFE.voucherCFE.total < 0)){
									$('#buttonCancelVoucher').attr("disabled", true);
								}else{
									$('#buttonCancelVoucher').removeAttr("disabled");
								}
							}else {
								if (($('#'+idVoucher).children()[0].innerText.includes("Recibo")) && $('#'+idVoucher).children()[3].innerText.includes("-")){
									$('#buttonCancelVoucher').attr("disabled", true);
								}else{
									$('#buttonCancelVoucher').removeAttr("disabled");
								}
							}
						}
						$('#buttonCancelVoucher').off('click');
						$('#buttonCancelVoucher').click(function(){
							$('#modalSeeVoucher').modal('hide');
							$('#modalCancelVoucher').modal();
							$('#inputDateCancelVoucher').val(getDateIntToHTML(responseGetCFE.voucherCFE.fecha));
							$('#btnCancelVoucher').off('click');
							$('#btnCancelVoucher').click(function(){
								cancelVoucher(idVoucher);
							});
						});
					}else{
						$('#buttonCancelVoucher').off('click');
						$('#buttonCancelVoucher').click(function(){
							$('#modalSeeVoucher').modal('hide');
							showReplyMessage(responseConsultCaes.result, responseConsultCaes.message, "Anular comprobante", "modalSeeVoucher");

						});
					}
				}
			}
		}else{
			$('#buttonCancelVoucher').css('visibility', 'hidden');
		}

		if(responseGetCFE.voucherCFE.isAnulado){
			if (view == 'vouchersEmitted' && !$('#' + idVoucher).hasClass('voucherDgiAnulado')){ // Esta anulado pero en nuestra base no
				sendAsyncPost("cancelVoucherById", {idVoucher: idVoucher})
				.then(( response )=>{
					// console.log(response)
					if ( response.result == 2 ){
						$('#' + idVoucher).addClass('voucherDgiAnulado')
					}
				});
				console.log(idVoucher)
			}
			$('#buttonCancelVoucher').css('visibility', 'hidden');
		}

		$('#modalSeeVoucher').modal();
	}else {
		mostrarLoader(false)
		if ( !responseGetCFE.message || responseGetCFE.message == "" ){
			showReplyMessage(responseGetCFE.result, "No se encontró el comprobante. Intente nuevamente.", "Ver comprobante", null);
		}
		else showReplyMessage(responseGetCFE.result, responseGetCFE.message, "Ver comprobante", null);
	}
}

function cancelVoucher(idVoucher){
	$('#modalCancelVoucher').modal('hide')
	mostrarLoader(true)
	let dateCancelVoucher = $('#inputDateCancelVoucher').val() || null;
	let appendix = $('#inputCancelAppendix').val() || null;
	sendAsyncPost("cancelVoucherEmitted", {idVoucher: idVoucher, dateCancelVoucher: dateCancelVoucher, appendix: appendix})
		.then(( response )=>{
				console.log(response)
				mostrarLoader(false)
				showReplyMessage(response.result, response.message, "Cancelar comprobante", null);
			})
		.catch(function(response){
			mostrarLoader(false)
			console.log("este es el catch", response);
		});
	// let dateCancelVoucher = $('#inputDateCancelVoucher').val() || null;
	// let appendix = $('#inputCancelAppendix').val() || null;
	// let response = sendPost("cancelVoucherEmitted", {idVoucher: idVoucher, dateCancelVoucher: dateCancelVoucher, appendix: appendix});
	// showReplyMessage(response.result, response.message, "Cancelar comprobante",  "modalCancelVoucher");
	// if(response.result == 2){
	// 	console.log(response)
	// }
}
// --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
function downloadVoucher(idVoucher, prepareFor) {
	let response = sendPost('getVoucherToExportCFE', {idVoucher: idVoucher, prepareFor: prepareFor});
	if(response.result == 2){
		let linkSource = `data:application/pdf;base64,${response.voucherCFE.representacionImpresa}`;
		let downloadLink = document.createElement("a");
		let fileName = response.voucherCFE.tipoCFE + "-" + response.voucherCFE.serieCFE + "-" + response.voucherCFE.numeroCFE + ".pdf";
		downloadLink.href = linkSource;
		downloadLink.download = fileName;
		downloadLink.click();
	}else showReplyMessage(response.result, response.message, "Descargar comprobante", null);
}
function exportVoucherNew(idVoucher, prepareFor) {
	let response = sendPost('getVoucherToExportCFE', { idVoucher: idVoucher, prepareFor: prepareFor });
	if (response.result == 2) {
	  	// The response contains a base64-encoded PDF
	  	let pdfBase64 = response.voucherCFE.representacionImpresa;
	  	const base64 = 'data:application/pdf;base64,' + pdfBase64;
	
		// Crear un objeto Blob a partir de la cadena base64
		const byteCharacters = atob(base64.split(',')[1]);
		const byteNumbers = new Array(byteCharacters.length);
		for (let i = 0; i < byteCharacters.length; i++) {
		  byteNumbers[i] = byteCharacters.charCodeAt(i);
		}
		const byteArray = new Uint8Array(byteNumbers);
		const blob = new Blob([byteArray], { type: 'application/pdf' });
	  
		// Crear una URL temporal para el Blob
		const blobUrl = URL.createObjectURL(blob);
	  
		// Crear un iframe de forma dinámica y agregarlo al documento
		const iframe = document.createElement('iframe');
		iframe.style.display = 'none'; // Ocultar el iframe
		iframe.src = blobUrl;
	  
		// Añadir el iframe al documento
		document.body.appendChild(iframe);
	  
		// Imprimir el PDF cuando el iframe esté cargado
		iframe.onload = function () {
		  iframe.contentWindow.print();
		};
  	} else {
		showReplyMessage(response.result, response.message, "Exportar comprobante", null);
	}
}
  // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


function getIdsVouchersSelected(){
	let idsSelecteds = "";
	$("#tbodyAccountState input:checkbox:checked").each(function() {
		if($(this).attr('id') != "inputSelectedAll")
			idsSelecteds += $(this).attr('id') + ",";
	});
	return idsSelecteds.substring(0, idsSelecteds.length - 1);
}

function getCantVouchersSelected(){
	let cantVouchers = 0;
	$("#tbodyAccountState input:checkbox:checked").each(function() {
		if($(this).attr('id') != "inputSelectedAll")
			cantVouchers++;
	});
	return cantVouchers;
}

function refreshCurrentQuote(){
	if($('#inputUSDValueNewManualReceiptClient').is(':visible')){
		let inputQuote = document.getElementById('inputUSDValueNewManualReceiptClient');
		let dateVoucher = document.getElementById("inputDateNewManualReceiptClient").value || null;
		if(dateVoucher){
			let response = sendPost("getQuote", {typeCoin: "USD", dateQuote: dateVoucher});
			if(response.result == 2){
				inputQuote.value = response.currentQuote;
				tempCurrentQuote = response.currentQuote;
				return;
			}else showReplyMessage(response.result, response.message, "Error cotización", "modalNewManualReceipt");
		}else showReplyMessage(1, "Debe seleccionar una fecha para obtener una cotización correcta.", "Campo fecha requerido", "modalNewManualReceipt");
	}
}

function createManualReceiptEmitted(btnModal){

	let documentClient = btnModal.name;
	let reasonReference = document.getElementById("inputReasonNewManualReceiptClient") || null;
	let vouchersSelected = getIdsVouchersSelected();
	let dateVoucher = document.getElementById("inputDateNewManualReceiptClient").value || null;
	let total = document.getElementById("inputTotalNewManualReceiptClient").value || null;
	let checkedOfficial = document.getElementById('radioButtonOfficialReceiptClient').checked;
	let addDiscount = $("#checkDiscountManualReceiptClient").prop("checked");
	let valueDiscount = null;
	let typeCoin = null;

	let textReason = null;
	let address = null;
	let city = null;
	if(checkedOfficial){ //controles que se hacen si el recibo a crear es eRecibo oficial
		if ($('#containerBusinessInfo').is(':visible')) {
			address = document.getElementById("inputAddressNewManualReceiptClient").value || null;
			city = document.getElementById("inputCityNewManualReceiptClient").value|| null;
			if(address == null || address.length < 5){
				showReplyMessage(1, "Para recibos a empresas la dirección es un campo obligatorio (mínimo 5 caracteres)", "Campo dirección requerido", "modalNewManualReceipt");
				$('#inputAddressNewManualReceiptClient').focus();
				return;
			}else if(city == null || city.length < 5){
				showReplyMessage(1, "Para recibos a empresas la ciudad es un campo obligatorio (mínimo 5 caracteres)", "Campo ciudad requerido", "modalNewManualReceipt");
				$('#inputCityNewManualReceiptClient').focus();
				return;
			}
		}

		if ($('#inputReasonNewManualReceiptClient').is(':visible')) {
			if(reasonReference){
				if(reasonReference.value.length > 80){
					showReplyMessage(1, "La razón del recibo no puede contener mas de 80 caracteres.", "Campo razón requerido", "modalNewManualReceipt");
					return;
				}else textReason = reasonReference.value;
			}
		}
	}


	let USDValue = null;
	if($('#inputUSDValueNewManualReceiptClient').is(':visible')){
		////console.log("significa que el nuevo recibo es de dolar")
		let tempValueUSD = document.getElementById('inputUSDValueNewManualReceiptClient').value || null;
		if(tempValueUSD){
			let responseCurrentQuote = sendPost("getQuote", {typeCoin: "USD", dateQuote: dateVoucher});
			if(responseCurrentQuote.result == 2){
				if((responseCurrentQuote.currentQuote * 0.10) >= Math.abs(responseCurrentQuote.currentQuote - tempValueUSD))
					USDValue = tempValueUSD;
				else{
					showReplyMessage(1, "La cotización ingresada difiere mucho de la cotización a la fecha seleccionada.", "Cotización no valido", "modalNewManualReceipt");
					return;
				}
			}else{
				showReplyMessage(responseCurrentQuote.result, responseCurrentQuote.message, "Error de cotización");
				return;
			}
		}else{
			showReplyMessage(1, "Debe ingresar una cotización para el comprobante o mantener la proporcionada.", "Campo cambio requerido", "modalNewManualReceipt");
			return;
		}
		typeCoin= "USD"
	}else{
		////console.log("significa que eligio pesos")
		typeCoin= "UYU"
	}

	if(total == null || total.length < 2){
		showReplyMessage(1, "El importe del recibo no puede ser nulo.", "Campo importe requerido", "modalNewManualReceipt");
		return;
	}

	let valueChecked = 0;
	if(checkedOfficial)
		valueChecked = 1;

	let data = {
		documentClient: documentClient,
		address: address,
		city: city,
		idsSelected: vouchersSelected,
		dateVoucher: dateVoucher,
		reasonReference: textReason,
		USDValue: USDValue,
		total: total,
		checkedOfficial: valueChecked,
		typeCoin: typeCoin
	};
	console.log(data)
	//en el if se verifica si se agregó descuento al crear el recibo o no
	// depende si hay descuento cuales comprobantes se crean
	if( addDiscount && checkedOfficial){
		valueDiscount = $("#inputDiscountNewManualReceiptClient").val();
		if( valueDiscount < 1 ){
			showReplyMessage(1, "El descuento debe de ser mayor a cero.", "Recibo", "modalNewManualReceipt", true);
		}
		data.discount = valueDiscount;

		let resultNewCreditNote = creditNoteForVoucherDiscount(data);
		console.log(resultNewCreditNote);
		if ( resultNewCreditNote.result == 2){
			valueDiscount = (data.total * valueDiscount)/100;
			//acá se crea el recibo nuevo con el descuento ya realizado.
			data.total = data.total - valueDiscount;
			let response = sendPost("createVoucherReceiptEmitted", data);
			showReplyMessage(response.result, response.message, "Recibo", "modalNewManualReceipt");
			$("#modalButtonResponse").click(function(){
				if(response.result == 2)
					window.location.reload();
			});
		}else return resultNewCreditNote;
	}
	else{
		let response = sendPost("createVoucherReceiptEmitted", data);

		showReplyMessage(response.result, response.message, "Recibo", "modalNewManualReceipt");
		$("#modalButtonResponse").click(function(){
			if(response.result == 2)
				window.location.reload();
		});
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
//TENER EN CUENTA QUE LA NOTA DE CREDITO SE TIENE QUE AGREGAR CADA INDICADOR DE FACTURA CON SU RESPECTIVO VALOR
//segun el cliente es el tipo de nota de credito que se hace
function creditNoteForVoucherDiscount(data){
	console.log(data);
	let result = sendPost("createNewVoucher2", data);
	return result;
}

$("#radioButtonManualReceiptClient").change(function() {
	if(this.checked){
		$('#containerInputReason').hide();
		$('#containerDiscountManualReceiptClient').hide();
	}
});

$('#radioButtonOfficialReceiptClient').change(function() {
	if(this.checked){
		let cantVouchers = getCantVouchersSelected();
		if(cantVouchers == 0)
			$('#containerInputReason').show();
		$('#containerDiscountManualReceiptClient').show();
	}
});


function keyPressManualReceipt(eventEnter, value, size){
	if(eventEnter.keyCode == 13 && !eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "radioButtonOfficialReceiptClient"){
			$('#radioButtonManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "radioButtonManualReceiptClient"){
			if($('#inputReasonNewManualReceiptClient').is(':visible')){
				$('#inputReasonNewManualReceiptClient').focus();
			}else{
				$('#inputDateNewManualReceiptClient').focus();
			}
		}
		else if(eventEnter.srcElement.id == "inputReasonNewManualReceiptClient"){
			$('#inputDateNewManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "inputDateNewManualReceiptClient"){
			if($('#inputUSDValueNewManualReceiptClient').is(':visible')){
				$('#inputUSDValueNewManualReceiptClient').focus();
			}else{
				$('#inputTotalNewManualReceiptClient').focus();
			}
		}else if(eventEnter.srcElement.id == "inputUSDValueNewManualReceiptClient"){
			$('#inputTotalNewManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "inputTotalNewManualReceiptClient"){
			$('#checkDiscountManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "checkDiscountManualReceiptClient"){
			if( $('#inputDiscountNewManualReceiptClient').is(':visible') ){
				$('#inputDiscountNewManualReceiptClient').focus();
			}
		}else if(eventEnter.srcElement.id == "inputDiscountNewManualReceiptClient"){
			$('#amountToCancelManualReceipt').focus();
		}
	}else if(eventEnter.keyCode == 13 && eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "amountToCancelManualReceipt"){
			$('#inputDiscountNewManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "inputDiscountNewManualReceiptClient"){
			$('#checkDiscountManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "checkDiscountManualReceiptClient"){
			$('#inputTotalNewManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "inputTotalNewManualReceiptClient"){
			$('#inputDateNewManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "inputDateNewManualReceiptClient"){
			if( $('#inputReasonNewManualReceiptClient').is(':visible') ){
				$('#inputReasonNewManualReceiptClient').focus();
			}else{
				$('#radioButtonManualReceiptClient').focus();
			}
		}else if(eventEnter.srcElement.id == "inputReasonNewManualReceiptClient"){
			$('#radioButtonManualReceiptClient').focus();
		}else if(eventEnter.srcElement.id == "radioButtonManualReceiptClient"){
			$('#radioButtonOfficialReceiptClient').focus();
		}
	}
	if(value != null && value.length == size) {
		return false;
	}
}

//cuando se hace click en el boton recibo en el estado de cuenta
function showModalReceipt(){
	let documentClient = document.getElementById("idDocumentBusiness").value || null;
	if(documentClient != null && documentClient.length > 10){
		let responseGetClient = sendPost("findWithDocument", {document: documentClient, prepareFor: "CLIENT"});
		if(responseGetClient.result == 2){
			if(!responseGetClient.client.direccion || !responseGetClient.client.localidad)
				$("#containerBusinessInfo").show();
		}else $("#containerBusinessInfo").hide();
	}

	let cantVouchers = getCantVouchersSelected();
	console.log("cantidad de documentos seleccionados: " + cantVouchers)
	if(cantVouchers >= 1)
		$("#containerInputReason").hide();
	else
		$("#containerInputReason").show();

	if(cantVouchers >= 1){
		let vouchersSelected = getIdsVouchersSelected();
		console.log("IDs: " + vouchersSelected)
		let responseTotal = sendPost("calculateTotalVoucherSelected", {idsSelected: vouchersSelected});
		if(responseTotal.result == 2){
			console.log(responseTotal);
			document.getElementById("inputTotalNewManualReceiptClient").value = parseFloat(responseTotal.total.balance).toFixed(2);
		}else showReplyMessage(responseTotal.result, responseTotal.message, "Error al calcular total", null);
	}else document.getElementById("inputTotalNewManualReceiptClient").value = "";
	onLoadInputDate(document.getElementById('inputDateNewManualReceiptClient'), 0);

	$('#radioButtonOfficialReceiptClient').prop('checked', true).change();
	$("#checkDiscountManualReceiptClient").prop("checked", false);
	$("#inputDiscountNewManualReceiptClient").val("");
	changeDiscManualReceipt();

	refreshCurrentQuote();
	$('#modalNewManualReceipt').modal();
}

function modalManualReceiptFocus(){
	//console.log("modalManualReceiptFocus");
	if($('#containerBusinessInfo').is(':visible'))
		$('#inputAddressNewManualReceiptClient').focus();
	else if($('#inputReasonNewManualReceiptClient').is(':visible'))
		$('#inputReasonNewManualReceiptClient').focus();
	else
		$('#inputTotalNewManualReceiptClient').focus();
}

function selectAllVouchers(inputCheckAll){
	$("input:checkbox").each(function(){
		if(inputCheckAll.checked)
			$(this).prop("checked", true);
		else
			$(this).prop("checked", false);
	});
}

function checkVoucher(inputCheck){
	let allChecked = true;
	if(inputCheck.checked){
		$("input:checkbox").each(function(){
			if($(this).attr('id') != "inputSelectedAll"){
				if(!$(this).is(":checked")){
					allChecked = false;
				}
			}
		});
		document.getElementById('inputSelectedAll').checked = allChecked;
	}else document.getElementById('inputSelectedAll').checked = false;
}


$('#modalModifyAccountState').on('shown.bs.modal', function () {
	$('#inputDateFrom').focus();
});

function keyPressUpdateAccountSate(eventEnter){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputDateFrom")
			$('#inputDateTo').focus();
		else if(eventEnter.srcElement.id == "inputDateTo"){
			$('#buttonConfirmUpdateAccountState').focus();
		}else if(eventEnter.srcElement.id == "buttonConfirmUpdateAccountState"){
			$('#buttonConfirmUpdateAccountState').click();
		}
	}
}

function createManualReceiptProvider(){
	let dateManualReceipt = document.getElementById('inputDateManualReceiptProvider').value || null;
	let amountManualReceipt = document.getElementById('inputTotalManualReceiptProvider').value || null;

	if(dateManualReceipt){
		if(amountManualReceipt){
			let data = {dateMaked: dateManualReceipt, amount: amountManualReceipt};
			let response = sendPost("createManualReceiptReceived", data);
			showReplyMessage(response.result, response.message, "Recibo Manual", "modalModifyDeleteManualReceipt");
			$("#modalButtonResponse").click(function(){
				if(response.result == 2)
					window.location.reload();
			});
		}else showReplyMessage(1, "Debe ingresar el importe para el recibo manual.", "Importe campo requerido", "modalModifyDeleteManualReceipt");
	}else showReplyMessage(1, "Debe ingresar la fecha para el recibo manual.", "Fecha campo requerido", "modalModifyDeleteManualReceipt");
}

$('#modalModifyDeleteManualReceipt').on('shown.bs.modal', function () {
	onLoadInputDate(document.getElementById('inputDateManualReceiptProvider'), 0);
	$('#inputDateManualReceiptProvider').focus();
});

function keyPressManualReceiptProvider(eventEnter){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputDateManualReceiptProvider")
			$('#inputTotalManualReceiptProvider').focus();
		else if(eventEnter.srcElement.id == "inputTotalManualReceiptProvider"){
			$('#buttonConfirmManualReceiptProvider').click();
		}
	}
}

function updateAccountState(buttonUpdate){
	let dateFrom = document.getElementById('inputDateFrom').value || null;
	let dateTo = document.getElementById('inputDateTo').value || null;

	if(dateFrom){
		if(dateTo){
			let formatDateFrom = new Date(dateFrom);
			let formatDateTo = new Date(dateTo);
			if(formatDateFrom.getTime() < formatDateTo.getTime()){
				let response = sendPost('getLastAccountStateInfo', { prepareFor: buttonUpdate.name });
				if(response.result == 2){
					let info = response.information;
					let valueIncludeCollection = "NO";
					if(info.includeCashCollection == "SI")
						valueIncludeCollection = "SI";
					var url = getSiteURL() + 'generar-estado-cuenta/' + info.idPerson + '/' + dateFrom  + '/' + dateTo + '/' + info.selectedCoin + '/' + buttonUpdate.name + '/' + valueIncludeCollection;
					window.location.href = url;
				}
			}else showReplyMessage(1, "La fecha de inicio no puede ser mayor a la fecha final", "Actualizar estado de cuenta", "modalModifyAccountState");
		}else showReplyMessage(1, "Debe ingresar la nueva fecha final para el estado de cuenta", "Actualizar estado de cuenta", "modalModifyAccountState");
	}else showReplyMessage(1, "Debe ingresar la nueva fecha de inicio para el estado de cuenta", "Actualizar estado de cuenta", "modalModifyAccountState");
}


function changeDiscManualReceipt(){

	let checkValue = $("#checkDiscountManualReceiptClient").prop("checked");
	if( checkValue ){
		//console.log(" changeDiscManualReceipt function ");
		$("#divDiscManualReceiptClient").removeAttr("hidden");
		$("#divAmountToCancelClient").removeAttr("hidden");
	}else{
		$("#divDiscManualReceiptClient").attr("hidden", true);
		$("#divAmountToCancelClient").attr("hidden", true);
		$("#divDiscManualReceiptClient input").val("");
		$("#divAmountToCancelClient input").val("");
	}

}

function calculateDiscontToNoteCredit(){

	let checkValue = $("#checkDiscountManualReceiptClient").prop("checked");
	if( checkValue ){
		//console.log(" changeDiscManualReceipt function ");
		let total = $("#inputTotalNewManualReceiptClient").val();
		let discount = $("#inputDiscountNewManualReceiptClient").val();

		let valueDiscount = ((total * discount) / 100);

		$("#amountToCancelManualReceipt").val( total - valueDiscount );
	}
}

function calculateDiscontToNoteCredit2(){

	let checkValue = $("#checkDiscountManualReceiptClient").prop("checked");
	if( checkValue ){
		//console.log(" changeDiscManualReceipt function ");

		discount = $("#inputDiscountNewManualReceiptClient").val();
		toPay = $("#amountToCancelManualReceipt").val();

		x = (1 - (discount / 100));
		console.log(discount);
		console.log(toPay);
		toCancel = parseFloat(toPay / x ).toFixed(2);
		console.log(toCancel);
		$("#inputTotalNewManualReceiptClient").val(toCancel);
	}
}

function exportAccountStateExcel( entity, id, init, finish, coin, config){
	sendAsyncPost("exportAccountStateExcel", {entity: entity, idEntity:id, init:init, finish:finish, coin:coin, config:config})
	.then(( response )=>{
		if ( response.result == 2 ){
			window.location.href = getSiteURL() + 'downloadExcel.php?n='+response.name;
		}
		else
			showReplyMessage( response.result, response.message, "Exportar estado de cuenta", null, true );
	});
}