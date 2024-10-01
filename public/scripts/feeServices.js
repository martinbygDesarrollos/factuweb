let lastId = 0;
//console.log(lastId);
let textToSearch = null;
//console.log(".."+textToSearch);

function loadFeeServices(){
	let response = sendPost('loadFeeServices', {lastId: lastId, textToSearch: textToSearch});
	if(response.result == 2){
		if(lastId != response.lastId){
			lastId = response.lastId;
			let list = response.services;
			//console.log("servicios encontrados: ");
			//console.log(list);
			for (let i = list.length - 1; i >= 0; i--) {
				let row = createRow(list[i].idCuota, list[i].idServicio, list[i].idCliente, list[i].nombreCliente, list[i].nombre, list[i].fechaUltimaFactura , list[i].periodo, list[i].montoIVA, list[i].simboloMoneda, list[i].costoFormat, list[i].importeFormat, list[i].importeCot, list[i].vigente);
				$('#tbodyFeeServices').append(row);
			}
		}
	}else if(response.result == 0) showReplyMessage(0, response.message, "Listar clientes", null);
	resizeScreen();
}

function createRow(idFee, idService, idClient, nameClient, nameService, date, period, valueIVA, coin, cost, amount, amountQuote, currentValue){
	var coinUi = coin == "UI" ? "$" : coin;
	var nullCell = "<td class='text-right toHidden2'> </td>";
	var row = "<tr id='" + idFee + "'>";
	row += "<td class='text-right pointerToHover'  onclick='showModifyModalFeeService(" + idFee + ")' >" + nameClient + "</td>";
	row += "<td id='service" + idFee + "' class='text-right pointerToHover' onclick='showModifyModalFeeService(" + idFee + ")'>" + nameService + "</td>";
	row += "<td id='period" + idFee + "' class='text-right toHidden2 pointerToHover notShow' onclick='showModifyModalFeeService(" + idFee + ")'>" + period + "</td>";
	row += "<td class='text-right toHidden2 notShow' >" + valueIVA + "</td>";
	//row += "<td class='text-right toHidden2' >" + coin + "</td>";
	row += "<td class='text-right toHidden2 notShow' >" + coinUi +"  "+ cost + "</td>";
	if(amountQuote){
		row += "<td class='text-right toHidden2' >" + coinUi  +"  "+ amountQuote + "</td>";
		row += "<td class='text-right toHidden2' >" + coin  +"  "+ amount + "</td>";
	}else{
		row += "<td class='text-right toHidden2' >" + coin  +"  "+ amount + "</td>";
		row += nullCell;
	}
	row += "<td class='text-right toHidden2' >" + date + "</td>";
	row += "<td class='text-center p-1'>";
	if(currentValue == 1)
		row += "<label class='switch'><input id='inputCB" + idFee + "' type='checkbox' checked onclick='changeCurrentValue(this)'><span class='slider round'></span></label></td>";
	else
		row += "<label class='switch'><input id='inputCB" + idFee + "' type='checkbox' onclick='changeCurrentValue(this)'><span class='slider round'></span></label></td>";
	row += "<td class='text-center'>";
	row += "<button class='btn btn-sm background-template-color2 text-template-background mr-1' onclick='showModalCheckInService(" + idFee + ")' data-toggle='tooltip' data-placement='bottom' title='Facturar la cuota seleccionada'><i class='fas fa-receipt text-mycolor'></i></button>";
	row += "<button class='btn btn-sm btn-danger' onclick='showModalDeleteFeeService(" + idFee + ")'><i class='fas fa-trash-alt text-mycolor'></i></button></td></tr>";

	return row;
}

function invoiceAllFeeService(){
	let dateEmitted = $('#inputDateEmittedInvoiceAllService').val() || null;
	let dateExpiration = $('#inputDateExpirationInvoiceAllService').val() || null;
	let progressBarIdProcess = null;
	if(dateEmitted){
		if(dateExpiration){

			$('#modalInvoiceAllFeeService').modal("hide");
			$('#progressbar').modal();
			progressBarIdProcess = loadPrograssBar();
			sendAsyncPost("invoiceAllFeeService", {dateEmitted: dateEmitted, dateExpiration: dateExpiration})
			.then(function(response){
				$('#progressbar').modal("hide");
				stopPrograssBar(progressBarIdProcess);
				showReplyMessage(response.result, response.message, "Facturar servicios", "modalInvoiceAllFeeService");
				$("#modalButtonResponse").click(function(){
					if(response.result == 2){
						window.location.reload();
					}
				});
			})
			.catch(function(response){
				$('#progressbar').modal("hide");
				stopPrograssBar(progressBarIdProcess);
				showReplyMessage(response.result, response.message, "Facturar servicios", "modalInvoiceAllFeeService");
			});
		}else showReplyMessage(1, "Debe ingresar una fecha de vencimietno para los comprobantes emitidos.", "Campo vencimiento requerido", "modalInvoiceAllFeeService");
	}else showReplyMessage(1, "Debe ingresar una fecha de emisión para el comprobante.", "Campo fecha emisión requerido", "modalInvoiceAllFeeService");
}

function showModalCheckInService(idFeeService){

	let response = sendPost('getFeeServiceWithDetail', {idFeeService: idFeeService});
	if(response.result == 2){
		if (response.feeService.vigente != 0){
			//obtener los datos para la facturacion
			let dateServiceEmitted = getCurrentDate();
			let nombreServicio = response.feeService.nombreServicio;
			let nombreReceptor = response.client.nombreReceptor;

			let responseGetExpirationSuggestion = sendPost("getConfiguration", {nameConfiguration: "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO"});
			if(responseGetExpirationSuggestion.result == 2){
				//en esta funcion se cargan los datos del nuevo modal para crear la factura y se muestra el modal
				if (responseGetExpirationSuggestion.configValue != "0"){
					////console.log("tiene configurada una fecha");
					let dateServiceExpired = calculateDateExpiration(dateServiceEmitted, responseGetExpirationSuggestion.configValue);
					loadDataInvoiceService(nombreServicio, nombreReceptor, dateServiceEmitted, dateServiceExpired);
				}else{
					////console.log("tiene configurada la fecha 0");
					loadDataInvoiceService(nombreServicio, nombreReceptor, dateServiceEmitted, null);
				}
			}

			$('#btnConfirmNewFeeService').off('click');
			$('#btnConfirmNewFeeService').click(function(){

				$('#btnConfirmNewFeeService').prop('disabled', true);
				//$('#modalCheckInFeeService').modal("hide");
				invoiceFeeService(idFeeService);
			});
		} else showReplyMessage(1, "La cuota seleccionada no se encuentra activa.", "Facturar servicios", null);
	}else showReplyMessage(1, "La cuota de este servicio no fue encontrado en la base de datos.", "Cuota no encontrada", null);
}

$('#modalInvoiceAllFeeService').on('shown.bs.modal', function(){
	countBillableFeeService();
});

function countBillableFeeService(){
	let responseGetExpirationSuggestion = sendPost("getConfiguration", {nameConfiguration: "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO"});
	if(responseGetExpirationSuggestion.result == 2){
		dateEmitted = null;
		dateExpiration = null;

		$('#inputDateEmittedInvoiceAllService').val(getCurrentDate());
		$('#inputDateExpirationInvoiceAllService').val(calculateDateExpiration($('#inputDateEmittedInvoiceAllService').val(), responseGetExpirationSuggestion.configValue));

		$('#inputDateEmittedInvoiceAllService').change(()=>{
			dateEmitted = $('#inputDateEmittedInvoiceAllService').val();
			dateExpiration = $('#inputDateExpirationInvoiceAllService').val();

			let response = sendPost("getCountBillableFeeService", {date:dateEmitted, date2:dateExpiration });
			console.log(response);
			if (response.countBillable != 0){
				if(response.result == 2)
					$('#messageInvoiceAllService').html("¿Seguro que desea emitir las " + response.countBillable + " cuotas por servicio facturables con fecha "+dateEmitted +"?");
				else
					$('#messageInvoiceAllService').html("¿Seguro que desea emitir todas las cuotas por servicio facturables con fecha "+dateEmitted +"?");

				//$('#inputDateEmittedInvoiceAllService').val(getCurrentDate());
				//$('#inputDateExpirationInvoiceAllService').val(calculateDateExpiration($('#inputDateEmittedInvoiceAllService').val(), responseGetExpirationSuggestion.configValue));

				/*$('#inputDateEmittedInvoiceAllService').off('change');
				$('#inputDateEmittedInvoiceAllService').on('change', function(){
					$('#inputDateExpirationInvoiceAllService').val(calculateDateExpiration($('#inputDateEmittedInvoiceAllService').val(), responseGetExpirationSuggestion.configValue));
				});*/
				$('#inputConfirmationButtonInvoiceAllService').prop( "disabled", false );
			}
			else{
				$('#messageInvoiceAllService').html("No hay coutas para emitir con fecha "+dateEmitted);
				$('#inputConfirmationButtonInvoiceAllService').prop( "disabled", true );
			}
		})
		$('#inputDateEmittedInvoiceAllService').trigger( "change" );

	}
}

function invoiceFeeService(idFeeService){
	let dateEmitted = $('#inputDateEmittedInvoiceService').val() || null;
	let dateExpiration = $('#inputDateExpInvoiceService').val() || null;

	if(dateEmitted){
		if(dateExpiration){
			let response = sendPost('invoiceFeeService', {idFeeService: idFeeService, dateEmitted: dateEmitted, dateExpiration: dateExpiration});
			if(response.result == 2){
				let responseVoucher = sendPost("getLastVoucherEmitted");
				if (responseVoucher.result == 2) {
					let data = {id:responseVoucher.objectResult.id}
					openModalVoucherFee(data, "CLIENT");
					$('#buttonCloseVoucherFee').click(function(){
						$('#modalCheckInFeeService').modal('hide');
						window.location.reload();
					});
				}
			}else{
				showReplyMessage(response.result, response.message, "Facturar servicios", "modalCheckInFeeService");
				$('#btnConfirmNewFeeService').prop('disabled', false);
			}

		}else showReplyMessage(1, "Debe ingresar una fecha de vencimietno para los comprobantes emitidos.", "Campo vencimiento requerido", "modalCheckInFeeService");
	}else showReplyMessage(1, "Debe ingresar una fecha de emisión para el comprobante.", "Campo fecha emisión requerido", "modalCheckInFeeService");
}

function searchClientFeeService(inputText){
	let textTemp = inputText.value || null;

	if (textTemp != null){
		if(textTemp.length >= 3){
			lastId = 0;
			textToSearch = textTemp;
			$('#tbodyFeeServices').empty();
			loadFeeServices();
			return;
		}else if(textTemp.length == 0){
			lastId = 0;
			textToSearch = null;
			$('#tbodyFeeServices').empty();
			loadFeeServices();
			return;
		}
	}
	else{
		lastId = 0;
		textToSearch = null;
		$('#tbodyFeeServices').empty();
		loadFeeServices();
		return;
	}
}

function showModalDeleteFeeService(idFeeService){
	let nameClient = getCellValue($('#' + idFeeService + '').children(), 0);
	$('#idParagraphFeeService').text("¿Desea borrar la cuota de " + nameClient + " ?")
	$('#modalDeleteFeeService').modal();
	$('#btnConfirmDeleteFeeService').off('click');
	$('#btnConfirmDeleteFeeService').click(function(){
		deleteFeeService(idFeeService);
	});
}

function deleteFeeService(idFeeService){
	let response = sendPost("deleteFeeService", {idFeeService: idFeeService});
	showReplyMessage(response.result, response.message, "Borrar cuota", "modalDeleteFeeService");
	if(response.result == 2)
		$('#' + idFeeService).remove();
}

function changeCurrentValue(rowAction){
	let idFeeService = rowAction.id;
	idFeeService = idFeeService.replace("inputCB", "");
	let response = sendPost("changeCurrentValueService", {idFeeService: idFeeService});
	let checkBox = document.getElementById(rowAction.id);
	let titleError = "Error desactivar cuota";
	if(checkBox.checked)
		titleError = "Error activar cuota";
	if(response.result != 2){
		showReplyMessage(response.result, response.message, titleError, null);
		if(checkBox.checked)
			checkBox.checked = false;
		else
			checkBox.checked = true;
	}
}

$("#modalChangeService").on('hidden.bs.modal', function () {
	lastIdModal = 0;
	serviceModal = null;
	$('#tbodyModalService').empty();
});

function showModifyModalFeeService(idFeeService){
	let response = sendPost('getSelectedFeeService', {idFeeService: idFeeService});
	if(response.result == 2){
		$('#inputModifyFeeClient').val(response.feeService.nombreCliente);
		$('#textAreaServiceSelected').html(response.feeService.descripcion);
		$('#selectModifyFeeMonth').val(response.feeService.periodo).change();
		let responseGetServices = sendPost("listServiceToChange", {idService: response.feeService.idServicio, idClient: response.feeService.idCliente});
		if(responseGetServices.result == 2){
			let list = responseGetServices.listResult;
			$('#selectModifyFeeServices').empty().append('whatever');
			for (let i = list.length - 1; i >= 0; i--) {
				var option = document.createElement("option");
				option.id = list[i].idServicio;
				option.name= list[i].nombre;
				option.value = list[i].idServicio;
				option.label = list[i].nombre + '  ' + list[i].importe;
				$('#selectModifyFeeServices').append(option);
			}
			$('#selectModifyFeeServices').val(response.feeService.idServicio).change();
			$('#modalModifyFeeService').modal();
			$('#buttonModifyFeeServiceConfirm').off('click');
			$('#buttonModifyFeeServiceConfirm').click(function(){
				modifyFeeService(idFeeService);
			})
		}else showReplyMessage(responseGetServices.result, responseGetServices.message, "Obtener servicios", null);
	}else showReplyMessage(response.result, response.message, "Obtener cuota", null);
}

function modifyFeeService(idFeeService){
	let service = $('#selectModifyFeeServices').val();
	let period = $('#selectModifyFeeMonth').val();
	let response = sendPost('modifyFeeService', {idFeeService, idService: service, period: period});
	showReplyMessage(response.result, response.message, "Modificar cuota", "modalModifyFeeService");
	if(response.result == 2){
		$("#modalButtonResponse").off('click');
		$('#modalButtonResponse').click(function(){
			window.location.reload();
		})
		//$('#service' + idFeeService).html(response.newService);
		//$('#period' + idFeeService).html(getTextPeriod(period));
	}
}

function downloadFeeService(){
	let response = sendPost("getFeeServiceToExport", null);
	if(response.result == 2){
		const linkSource = `data:application/vnd.ms-excel;base64,${ response.file }`;
		const downloadLink = document.createElement("a");
		const fileName = "cuotas_por_servicios.xlsx";
		downloadLink.href = linkSource;
		downloadLink.download = fileName;
		downloadLink.click();
	}
}

function getCellValue(td, position){
	return td[position].innerHTML || " ";
}


function loadDataInvoiceService (nombreServicio, nombreReceptor, dateServiceInvoiceEmitted, dateServiceInvoiceExpired){
	////console.log("datos que llegan")
	////console.log(dateServiceInvoiceEmitted);
	////console.log(dateServiceInvoiceExpired);

	//dateServiceInvoiceEmitted es la fecha en la que fue creada la factura
	//dateServiceInvoiceExpired fecha en la que expira la factura, calculada segun la configuracion

	$('#inputDateEmittedInvoiceService').val(dateServiceInvoiceEmitted);

	if(dateServiceInvoiceExpired != null){
		$('#inputDateExpInvoiceService').val(dateServiceInvoiceExpired);
	}

	$('#textCheckInFeeService').html("¿Desea facturar el servicio " + nombreServicio + " al cliente " + nombreReceptor + "?")
	$('#btnConfirmNewFeeService').prop('disabled', false);
	$('#modalCheckInFeeService').modal();
}


function goToClients(){
	var url = getSiteURL() + 'ver-clientes/unchecked';
	window.location.href = url;

	//document.getElementById("checkboxClientWithBalance").checked = false;
}

function openModalVoucherFee(button, prepareFor){
	let idVoucher = button.id;

	let responseGetCFE = sendPost('getVoucherCFE', {idVoucher: idVoucher, prepareFor: prepareFor});
	if(responseGetCFE.result == 2){
		let iFrame = document.getElementById("frameSeeVoucherFee");
		let screenHeight = screen.height - 200;
		//iFrame.style.height = screenHeight + "px";
		iFrame.style.height = screenHeight + "px";
		var dstDoc = iFrame.contentDocument || iFrame.contentWindow.document;
		dstDoc.write(responseGetCFE.voucherCFE.representacionImpresa);
		dstDoc.close();

		$('#buttonExportVoucherFee').off('click');
		$('#buttonExportVoucherFee').click(function(){
			exportVoucher(idVoucher, prepareFor);
		});

		console.log("Si el comprobante esta anulado ver el motivo y mostrar cartel",responseGetCFE);

		if(prepareFor === "CLIENT"){
			let responseGetConfig = sendPost('getConfiguration', {nameConfiguration: "PERMITIR_NOTAS_DE_DEBITO"});
			if(responseGetConfig.result == 2){
				if(responseGetConfig.configValue == "NO" && responseGetCFE.voucherCFE.tipoCFE == 112)
					$('#buttonCancelVoucherFee').css('visibility', 'hiddden');
				else{
					let responseConsultCaes = sendPost('consultCaes', {typeCFE: responseGetCFE.voucherCFE.tipoCFE});
					if(responseConsultCaes.result == 2){
						$('#buttonCancelVoucherFee').css('visibility', 'visible');
						$('#buttonCancelVoucherFee').off('click');
						$('#buttonCancelVoucherFee').click(function(){
							$('#modalSeeVoucherFee').modal('hide');
							$('#modalCancelVoucher').modal();
							$('#inputDateCancelVoucher').val(getDateIntToHTML(responseGetCFE.voucherCFE.fecha));
							$('#btnCancelVoucher').off('click');
							$('#btnCancelVoucher').click(function(){
								////console.log("anular");
								let cancelado = cancelVoucher(idVoucher);
								if (cancelado.result == 2)
									//console.log("se cancelo");
									window.location.reload();
							});
						});
					}
				}
			}
		}else{
			$('#buttonCancelVoucherFee').css('visibility', 'hiddden');
		}


		if ( responseGetCFE.voucherCFE.isAnulado ){
			$("#seeVoucherIsAnuladoMotivoFee").empty();
			$("#seeVoucherIsAnuladoMotivoFee").append("<strong>Motivo:</strong> "+responseGetCFE.voucherCFE.motivoRechazo);
			$("#seeVoucherIsAnuladoFee").removeAttr("hidden");
			$("#seeVoucherIsAnuladoFee").removeClass("fade");
		}else{
			$("#seeVoucherIsAnuladoMotivoFee").empty();

			$("#seeVoucherIsAnuladoFee").attr("hidden",true);
			$("#seeVoucherIsAnuladoFee").addClass("fade");
		}

		$('#modalSeeVoucherFee').modal();
	}else {
		if ( !responseGetCFE.message || responseGetCFE.message == "" ){
			showReplyMessage(responseGetCFE.result, "No se encontró el comprobante. Intente nuevamente.", "Ver comprobante", null);
		}
		else showReplyMessage(responseGetCFE.result, responseGetCFE.message, "Ver comprobante", null);
	}
}

//$("#inputDateEmittedInvoiceService").on('change',

	function calculateExpirationDate(){

	let dateServiceEmitted = $('#inputDateEmittedInvoiceService').val();
	//console.log(dateServiceEmitted);

	let responseGetExpirationSuggestion = sendPost("getConfiguration", {nameConfiguration: "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO"});
	if(responseGetExpirationSuggestion.result == 2){
		if (responseGetExpirationSuggestion.configValue != "0"){
			let dateServiceExpired = calculateDateExpiration(dateServiceEmitted, responseGetExpirationSuggestion.configValue);
			//console.log(dateServiceExpired);
			if(dateServiceExpired != null){
				$('#inputDateExpInvoiceService').val(dateServiceExpired);
				document.getElementById("inputDateExpInvoiceService").value = dateServiceExpired;
				//console.log("en el if");
				//console.log("valor " + $('#inputDateExpInvoiceService').val());
			}else{
				//console.log("en el else");
				document.getElementById("inputDateExpInvoiceService").value = dateServiceExpired;
				$('#inputDateExpInvoiceService').val(dateServiceExpired);
			}
		}
	}
}

//);


function calculateExpirationDateAllService(){

	let dateServiceEmitted = $('#inputDateEmittedInvoiceAllService').val();

	let responseGetExpirationSuggestion = sendPost("getConfiguration", {nameConfiguration: "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO"});
	if(responseGetExpirationSuggestion.result == 2){
		if (responseGetExpirationSuggestion.configValue != "0"){
			let dateServiceExpired = calculateDateExpiration(dateServiceEmitted, responseGetExpirationSuggestion.configValue);
			if(dateServiceExpired != null){
				$('#inputDateExpirationInvoiceAllService').val(dateServiceExpired);
			}
		}
	}

}

function keyPressModalCancelVoucher(eventEnter, input, size){
	if((eventEnter.keyCode == 13 || eventEnter.keyCode == 9)&& !eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputDateCancelVoucher")
			$('#inputCancelAppendix').focus();
		else if(eventEnter.srcElement.id =="inputCancelAppendix")
			$('#btnCancelVoucher').click();
	}else if((eventEnter.keyCode == 13 || eventEnter.keyCode == 9) && eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputCancelAppendix")
			$('#inputDateCancelVoucher').focus();
	}
	if(input != null && input.value.length == size) {
		return false;
	}
}