function openModalAddProduct(){
	console.log("openModalAddProduct")
	if(configrAllowProductsNotEntered){
		if(productsInCart.length >= 80){
			showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
			return;
		}
		clearModalAddProduct();
		$('#inputCountProduct').val(1);
		$('#inputDescriptionProduct').val(""); //se limpia el buscador
		if (IndFactDefault) {
			$('#inputTaxProduct').val( IndFactDefault );
		}else{
			$('#inputTaxProduct').val( 3 );
		}
		$('#modalAddProduct').modal();
	}
}

function openModalGetPrices(mode, quantity){ // SI ES POR CODEBAR RESPETAR LA CANTIDAD INGRESADA......... HERE HERE HERE HERE
	console.log("openModalGetPrices - " + mode + ' - ' + quantity)

	$('#inputTextToSearchPrice').val("");
	let valueToSearch = $('#inputTextToSearchPrice').val();
	
	if(mode == "normal"){
		$('#modalListPrice').off('shown.bs.modal').on('shown.bs.modal', function () {
			$('#inputTextToSearchPrice').prop( "readOnly", false );
			$('#modalListPrice .modal-title').text('Lista de precios');
			$('#inputTextToSearchPrice').focus();
		});
		let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
		$('#tbodyListPrice').empty();
		if(response.result == 2){
			let list = response.listResult;
			firstRow = true;
			for (var i = 0; i < list.length; i++) {
				let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda, quantity);
				$('#tbodyListPrice').append(row);
				if(firstRow){
					$('#tbodyListPrice tr:first').addClass('selected')
					firstRow = false
				}
			}
		}
	} else {
		$('#modalListPrice').off('shown.bs.modal').on('shown.bs.modal', function () {
			$('#inputTextToSearchPrice').prop( "readOnly", true );	
			$('#modalListPrice .modal-title').text('Selecccionar producto');
		});		
	}
	$('#modalListPrice').modal("show");
}

function openModalConfigMethodPayment(amount, method){ // CONFIGURA EL MODAL DEL CONFIGURACION Y LO ABRE
	console.log("openModalConfigMethodPayment - " + amount + " - " + method)

	
	$('#configMethod-fecha').addClass('d-none')
	$('#configMethod-observaciones').addClass('d-none')
	$('#configMethod-banco').addClass('d-none')
	$('#configMethod-fechaDiferido').addClass('d-none')
	$('#configMethod-titular').addClass('d-none')

	$('#input-configMethod-banco').val('')
	$('#input-configMethod-fecha').val('')
	$('#input-configMethod-fechaDiferido').val('')
	$('#input-configMethod-titular').val('')
	$('#input-configMethod-observaciones').val('')

	$('#modalConfigMethodPayment').data('method', method)
	$('#modalConfigMethodPayment').data('amount', amount)
	$('#modalConfigMethodPayment-title').text(method + " - " + (caja.moneda == "UYU" ? "$" : "U$D") + amount)

	switch (method) {
		case 'Tarjeta':
			$('#configMethod-banco label').text('Tarjeta')
			
			$('#configMethod-fecha').removeClass('d-none')
			$('#configMethod-banco').removeClass('d-none')
			$('#configMethod-observaciones').removeClass('d-none')
			break;
		
		case 'Tarjeta Offline':
			$('#configMethod-banco label').text('Tarjeta Offline')
			
			$('#configMethod-fecha').removeClass('d-none')
			$('#configMethod-banco').removeClass('d-none')
			$('#configMethod-observaciones').removeClass('d-none')
			break;
		
		case 'Cheque':
			$('#configMethod-banco label').text('Banco')

			$('#configMethod-fecha').removeClass('d-none')
			$('#configMethod-observaciones').removeClass('d-none')
			$('#configMethod-banco').removeClass('d-none')
			$('#configMethod-fechaDiferido').removeClass('d-none')
			$('#configMethod-titular').removeClass('d-none')
			break;
		
		case 'Depósito':
			$('#configMethod-banco label').text('Banco')

			$('#configMethod-fecha').removeClass('d-none')
			$('#configMethod-observaciones').removeClass('d-none')

			$('#configMethod-banco').removeClass('d-none')

			break;

		case 'Giro':
			$('#configMethod-banco label').text('Red de cobranza')

			$('#configMethod-fecha').removeClass('d-none')
			$('#configMethod-observaciones').removeClass('d-none')
			$('#configMethod-banco').removeClass('d-none')

			break;
	
		default:
			$('#configMethod-observaciones').removeClass('d-none')
			break;
	}
	$('#modalConfigMethodPayment').modal('show')
}

// POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS #######################################################
function openModalPOS(amount, consumidorFinal, CFE_reservado_num){
	console.log(`openModalPOS ${amount} ${consumidorFinal} ${CFE_reservado_num}`)
	const icon = $('#cardIcon');
	const text = $('#statusText');
	const button = $('#btnConfirmPOSPayment');
	$('#logText').text('');

	$('#paymentAmount').text(getFormatValue(amount))
	// VISUAL
	icon.removeClass().addClass('fas fa-credit-card fa-5x card-pulse text-primary');
	text.text('Esperando conexión con POS...').removeClass().addClass('status-text text-processing');
	button.prop('disabled', true).find('.maintext').text('Procesando...');
	// END

	$('#modalPOSPayment').modal('show')
	
	console.log(parseFloat(amount))
	const data = {
		monto: amount,
		consumidorFinal: consumidorFinal,
		numeroFactura: CFE_reservado_num
	};
	
	// Enviar la petición al backend
	sendAsyncPost("postearTransaccion", data)
		.then(function(response) {  
			// Procesar primera respuesta (token)
			if (response.result == 2) {
				// Si la respuesta es exitosa, obtener el token y consultar el estado
				const tokenNro = response.objectResult.TokenNro;
				console.log(tokenNro)
				// VISUAL
				icon.removeClass().addClass('fas fa-circle-notch fa-5x loading-spinner text-info');
				text.text('Procesando pago...').removeClass().addClass('status-text text-processing');
				// END
				consultarEstadoTransaccion(tokenNro, 0, 30, amount);
				return
			} else {
				// Error al obtener el token
				// VISUAL
				icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
				text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
				button.prop('disabled', true).find('.maintext').text('Error...');
				// END

				console.log("ERROR AL OBTENER EL TOKEN")
			}
		})
		.catch(function(error) {
			// VISUAL
			icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
			text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
			button.prop('disabled', true).find('.maintext').text('Error...');
			// END

			console.log("ERROR EN LA PETICION")
		});
}

function openModalDEVPOS(amount, banco, fecha, obs){ // DEVOLUCION HACER HACER
	const icon = $('#cardIcon');
	const text = $('#statusText');
	const button = $('#btnConfirmPOSPayment');
	const buttonCancel = $('#btnCancelPOSPayment');
	$('#logText').text('');

	$('#paymentAmount').text(getFormatValue(amount))
	// VISUAL
	icon.removeClass().addClass('fas fa-credit-card fa-5x card-pulse text-primary');
	text.text('Esperando conexión con POS...').removeClass().addClass('status-text text-processing');
	button.prop('disabled', true).find('.maintext').text('Procesando...');
	// END
	$('#modalPOSPayment').modal('show')
	
	console.log(parseFloat(amount))
	const data = {
		monto: amount,
		consumidorFinal: true
	};
	
	// Enviar la petición al backend
	sendAsyncPost("postearTransaccion", data)

		.then(function(response) {  
			// Procesar primera respuesta (token)
			if (response.result == 2) {
				// Si la respuesta es exitosa, obtener el token y consultar el estado
				const tokenNro = response.objectResult.TokenNro;
				console.log(tokenNro)
				// VISUAL
				icon.removeClass().addClass('fas fa-circle-notch fa-5x loading-spinner text-info');
				text.text('Procesando pago...').removeClass().addClass('status-text text-processing');
				// END
				consultarEstadoTransaccion(tokenNro, 0, 30);
				return
			} else {
				// Error al obtener el token
				// VISUAL
				icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
				text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
				button.prop('disabled', true).find('.maintext').text('Error...');
				// END

				console.log("ERROR AL OBTENER EL TOKEN")
			}
		})
		.catch(function(error) {
			// VISUAL
			icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
			text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
			button.prop('disabled', true).find('.maintext').text('Error...');
			// END

			console.log("ERROR EN LA PETICION")
		});
}


// POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS POS #######################################################

function calcelSale(){
	if($('#containerPayments button.btn-dev').length > 0){// SI se puede cancelar la venta (si tiene pago son tarjeta se deben devolver primero)
			showReplyMessage(1, "Devolver las transacciones con tarjetas para cancelar la venta", "Importante", "modalSetPayments");
	} else {
		$('#modalSetPayments').modal('hide')
	}
}

function confirmInsertPaymentMethod(){ // OBTIENE LOS DATOS CORRESPONDIENTES DEL MODAL DE CONFIGURAR EL METODO DE PAGO Y VUELVE A LLAMAR A LA FUNCION DE InsertPaymentMethod pero con el objeto ya creado
	let method = $('#modalConfigMethodPayment').data('method')
	let amount = $('#modalConfigMethodPayment').data('amount')
	let object = {
		banco: !$('#configMethod-banco').hasClass('d-none') ? $('#input-configMethod-banco').val().trim() : "",
		fecha: !$('#configMethod-fecha').hasClass('d-none') ? $('#input-configMethod-fecha').val().trim() : "",
		fecha_diferido: !$('#configMethod-fechaDiferido').hasClass('d-none') ? $('#input-configMethod-fechaDiferido').val().trim() : "",
		titular: !$('#configMethod-titular').hasClass('d-none') ? $('#input-configMethod-titular').val().trim() : "",
		obs: !$('#configMethod-observaciones').hasClass('d-none') ? $('#input-configMethod-observaciones').val().trim() : ""
	}
	// console.log(object)
	insertPaymentMethod(method, amount, object)
}

$('#modalSetClientByButton').on('shown.bs.modal', function () {
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal sino que aprete el boton de consumidor final
	$(this).data('bs.modal')._config.backdrop = 'static';
	$('#inputTextToSearchClient_SetClientByButton').trigger('focus')
})

$('#modalConfigMethodPayment').on('shown.bs.modal', function () {
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal sino que aprete el boton de consumidor final
	$(this).data('bs.modal')._config.backdrop = 'static';
	document.getElementById("input-configMethod-fecha").valueAsDate = new Date();
	document.getElementById("input-configMethod-fechaDiferido").valueAsDate = new Date();
	$(this).find('.modal-body input, .modal-body textarea, .modal-body select').filter(':visible').first().focus();
})

$('#modalDeleteDetail').off('shown.bs.modal').on('shown.bs.modal', function () {
	$('#btnConfirmDeleteDetail').trigger('focus')
});

$('#modalSetClient').off('shown.bs.modal').on('shown.bs.modal', function(){
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal sino que aprete el boton de consumidor final
	$(this).data('bs.modal')._config.backdrop = 'static';
	$('#inputTextToSearchClient').trigger('focus')
})

$('#modalSetTypeVoucher').off('shown.bs.modal').on('shown.bs.modal', function(){
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal
	$(this).data('bs.modal')._config.backdrop = 'static';
	$('#inputNotUseExpirationDate').prop("checked", false)
	typeClient = verifyClient()
	if(typeClient == "persona"){
		$('#selectTypeVoucher option[value="111_contado"]').hide();
		$('#selectTypeVoucher option[value="111_credito"]').hide();

		$('#selectTypeVoucher option[value="101_contado"]').show();
		$('#selectTypeVoucher option[value="101_credito"]').show();
	} else {
		$('#selectTypeVoucher option[value="101_contado"]').hide();
		$('#selectTypeVoucher option[value="101_credito"]').hide();

		$('#selectTypeVoucher option[value="111_contado"]').show();
		$('#selectTypeVoucher option[value="111_credito"]').show();
	}
	$('#selectTypeVoucher option').each(function(index) {
		if ($(this).is(':visible')) {
			$('#selectTypeVoucher')[0].selectedIndex = index;
			$('#selectTypeVoucher').change(); // o .trigger('change');
			return false; // corta el .each después del primero visible
		}
	});

	$('#selectTypeVoucher').trigger('focus')
})

$('#modalSetPayments').off('shown.bs.modal').on('shown.bs.modal', function () {
	$(this).data('bs.modal')._config.backdrop = 'static';
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal
});

$('#modalPOSDEV').off('shown.bs.modal').on('shown.bs.modal', function () {
	$(this).data('bs.modal')._config.backdrop = 'static';
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal
});

$('#modalInsertNewPaymentMethod').off('shown.bs.modal').on('shown.bs.modal', function () {
	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal
	$('#modalInsertNewPaymentMethodOptions')[0].selectedIndex = 0;
	$('#modalInsertNewPaymentMethodOptions').trigger('focus')
	$(this).data('bs.modal')._config.backdrop = 'static'; // Para que el click fuera no cierre el modal
});

$('#modalPOSPayment').off('shown.bs.modal').on('shown.bs.modal', function () {
	$('#btnCancelPOSPayment').prop('disabled', true);
	$('#btnCancelPOSPayment').find('.maintext').text('Cancelar');

	$(this).data('bs.modal')._config.keyboard = false; // Para que el escace no cierre el modal
	$(this).data('bs.modal')._config.backdrop = 'static'; // Para que el click fuera no cierre el modal
});

$('#modalDeleteArticleFromCart').off('shown.bs.modal').on('shown.bs.modal', function () {
	$('#btnConfirmDelete').trigger('focus')
});

$('#modalAddProduct').off('shown.bs.modal').on('shown.bs.modal', function () {
	if (configIncludeIva) {
		$('#titleModalCreateModifyService').text("Agregar artículo (IVA incluido)");
	} else {
		$('#titleModalCreateModifyService').text("Agregar artículo");
	}

	if (configDesc) {
		$('#inputDiscountProductLabel').text("Descuento(%)")
	} else {
		$('#inputDiscountProductLabel').text("Descuento($)")
	}
	$('#inputDescriptionProduct').focus();
});

function clearModalAddProduct(){ // Limpia el modal de agregar producto
	$('#inputDetailProduct').val("");
	$('#inputPriceProduct').val("");
	$('#inputDiscountProduct').val("");
	$('#inputSubtotalProduct').val(0);
	$('#inputUnidadVentaProduct').val('Unidad');
	let allIndicatorsInvoice = [];
	$("#inputTaxProduct option").each(function(){
		allIndicatorsInvoice.push($(this).val());
	});
	$('#inputTaxProduct').val( allIndicatorsInvoice[0] );// se agrega un impuesto por defecto para que se muestre en caso de que el impuesto del producto ingresado no esté habilitado
	$('#inputIVAProduct').val(0);
	$('#inputTotalProduct').val(0);
}

