////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			VARIABLES Y FUNCIONES SIN NOMBRE

// const { act } = require("react");

// NEW NEW NEW
var cliente = null; // EN CUALQUIER PUNTO DE LA VENTA SI SE INGRESA CLIENTE SE LLENA ESTA VARIABLE
var configDesc = null;
var configSuperFastSale = null;
var configSkipSelectClient = null;
var productsInCart = []; // array de productos en el carro, cada item tiene todos los datos que tiene el producto en la base
var configIncludeIva = null;
var configDiscountInPercentage = null;
var cotizacionDolar = null;
var configrAllowProductsNotEntered = null;
var IndFactDefault = null; // AL ABRIR EL MODAL DE ADD PRODUCT USA EL IVA POR DEFECTO
var caja = [];
var CFE_reservado = null; // SI LA CAJA TIENE POS AL MOMENTO DE VENDER CON POS RESERVO CFE

// Variable global para mantener el estado de ver los campos desglozados de importes
var detailsPricesVisible = false;


var idProductSelected = null;

// var moneda = "UYU";
// -----------

var quote = 1; // Cotizacion
var USD = 1; // Valor del dolar
var todayQuote = false; // Si se consulto hoy
// var idProductSelected = 0; 
// var arrayDetails=[]; //en este array se guardan todos los productos que se agregan al carro, los items de este array son productos y tienen todos los datos que tiene el producto en la base
var totalToShow = 0;
// var indexDetail = 0;

// var includeIva = null; // Configuracion traida desde la BD
// var discountPercentage = null; // Configuracion traida desde la BD

var btnAddDetailClickNumber = 0;

// Variable para almacenar el timestamp de la última ejecución
let lastExecutionTime = 0;
const MIN_EXECUTION_INTERVAL = 1500;

var config_value = null;
var headingval = null;

// UPDATED --------------------------------------------------------------------

async function getAllConfigurations(){
    console.log("getAllConfigurations");
    
    let responseDesc = await sendAsyncPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
    if(responseDesc.result == 2){
        if(responseDesc.configValue == "SI"){
            configDesc = true;
            $('#headerTdDesc').text("Descuento(%)")
        }
        else{
            configDesc = false;
            $('#headerTdDesc').text("Descuento($)")
            
        }
    }
    
    let responseSuperFastSale = await sendAsyncPost("getConfiguration", {nameConfiguration: "SUPERFAST_SALE"});
    if(responseSuperFastSale.result == 2){
        if(responseSuperFastSale.configValue == "SI"){
            configSuperFastSale = true;
            $('#fastSaleConfirm').removeClass("d-none")
        } else {
            configSuperFastSale = false;
            $('#fastSaleConfirm').addClass("d-none")
        }
    }
    
    let responseShowClient = await sendAsyncPost("getConfiguration", {nameConfiguration: "SKIP_SELECT_CLIENTE"});
    if(responseShowClient.result == 2){
        if(responseShowClient.configValue == "SI"){
            configSkipSelectClient = true;
        } else {
            configSkipSelectClient = false;
        }
    }

    let responseIncludeIva = await sendAsyncPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
    if(responseIncludeIva.result == 2){
        if(responseIncludeIva.configValue == "SI"){
            configIncludeIva = true;
        } else {
            configIncludeIva = false;
        }
    }

    let responseDiscountPercentage = await sendAsyncPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
    if(responseDiscountPercentage.result == 2){
        if(responseDiscountPercentage.configValue == "SI"){
            configDiscountInPercentage = true;
        } else {
            configDiscountInPercentage = false;
        }
    }
    let responseAllowProductsNotEntered = await sendAsyncPost("getConfiguration", {nameConfiguration: "PERMITIR_PRODUCTOS_NO_INGRESADOS"});
    if(responseAllowProductsNotEntered.result == 2){
        if(responseAllowProductsNotEntered.configValue == "SI"){
            configrAllowProductsNotEntered = true;
        } else {
            configrAllowProductsNotEntered = false;
        }
    }
	
    let responseIndFactDefault = await sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"});
    if(responseIndFactDefault.result == 2){
		IndFactDefault = responseIndFactDefault.configValue;
    }

    let responseCaja = await sendAsyncPost("getUserCaja", {});
	// console.log(responseCaja)
    if(responseCaja.result == 2){
		caja = responseCaja.caja;
    } else {
		caja = [];
	}
}

function configCaja(){
	let simbolo = caja.moneda === "UYU" ? "$" : "U$D";
	let texto = `Precio(${simbolo})`;
	if (configIncludeIva) {
		texto += " IVA Inc.";
	}
	$('#headerTdPrice').text(texto);
	$('#sectionCaja').find('h5').html(`Caja [${caja.nombre}]. Moneda [${caja.moneda}]`)
}

async function getCotizacionDolar() {
	console.log("getCotizacionDolar");
	let response = await sendAsyncPost("getQuote", {typeCoin: "USD", dateQuote: 1});
	cotizacionDolar = parseFloat(response.currentQuote).toFixed(2);
}

function continueSale(){
	console.log("continueSale")
	if (productsInCart.length == 0) {
		showReplyMessage(1, "Ningún producto ingresado", "Producto requerido", null);
		return;
	}
	if(configSkipSelectClient){ // Si hay que saltearlo paso a seleccionar tipo de voucher [Select TypeVoucher]
		console.log("ABRIR: modalSetTypeVoucher")
		$('#modalSetTypeVoucher').modal('show')
	} else {
		if(!cliente){ // Si no tiene cliente, hay que seleccionarlo [Selecccionar Cliente]
			cleanFields('modalSetClient')
			$('#modalSetClient').modal('show')
		} else { // Si ya tiene cliente, paso a seleccionar tipo de voucher [Select TypeVoucher]
			console.log("ABRIR: modalSetTypeVoucher")
			$('#modalSetTypeVoucher').modal('show')
		}
	}
}

function cleanFields(modal) {
	console.log("cleanFields - " + modal)
	$('#' + modal).find('input').each(function () {
		if ($(this).is('[type="checkbox"], [type="radio"]')) {
			$(this).prop('checked', false);
		} else {
			$(this).val('');
		}
	});
	$('#' + modal).find('textarea').val('');
	$('#' + modal).find('select').each(function () {
		$(this).prop('selectedIndex', 0).trigger('change');
	});
}

function setConsumidorFinalByButton(){ // DESDE EL MODAL DE SELECCIONAR CLIENTE [modalSetClientByButton]
	cliente = null;
	cleanFields('modalSetClientByButton')
	$('#buttonSetClient').find('span').html("Consumidor final");
}

function setConsumidorFinal(){ // DESDE EL MODAL DE SELECCIONAR CLIENTE PERO DEL BOTON DE CONTINUAR VENTA [modalSetClient]
	cliente = null;
	$('#buttonSetClient').find('span').html("Consumidor final");
	console.log("ABRIR: modalSetTypeVoucher")
	$('#modalSetTypeVoucher').modal('show')
}

function setClient(modal) { // Setea el cliente (y lo guarda) y dependiendo del modal no hace nada o abre el selector de voucher
    console.log("setClient");
    
    // Inicializar variables
    let document = null;
    let name = null;
    let address = null;
    let city = null;
    let department = null;
    let email = null;
    let phone = null;

    // Obtener datos según el modal
    if (modal == "modalSetClientByButton") {
        document = $('#inputDocumentClient_SetClientByButton').val().trim();
        name = $('#inputNameClient_SetClientByButton').val().trim();
        address = $('#inputAddressClient_SetClientByButton').val().trim();
        city = $('#inputCityClient_SetClientByButton').val().trim();
        department = $('#inputDepartmentClient_SetClientByButton').val().trim();
        email = $('#inputEmailClient_SetClientByButton').val().trim();
        phone = $('#inputPhoneClient_SetClientByButton').val().trim();
    } else if (modal == "modalSetClient") {
        document = $('#inputDocumentClient').val().trim();
        name = $('#inputNameClient').val().trim();
        address = $('#inputAddressClient').val().trim();
        city = $('#inputCityClient').val().trim();
        department = $('#inputDepartmentClient').val().trim();
        email = $('#inputEmailClient').val().trim();
        phone = $('#inputPhoneClient').val().trim();
    }
    
    // Validar cliente (esto ya no es async, por lo que no necesita await)
    const result = validarCliente(document, name, address, city, department, email, phone);
    
    // Manejar resultado de validación
    if (typeof result === 'string') {
        // Es un mensaje de error
        console.log(result);
        showReplyMessage(1, result, "Error", modal);
        return;
    }
    console.log('CARGANDO...')
	$('#' + modal).modal('hide') 
	mostrarLoader(true)
    // Si la validación fue exitosa, enviar los datos al servidor
	sendAsyncPost("createModifyClient", {documentReceiver: document, nameReceiver: name, numberMobile: phone, addressReceiver: address, locality: city, department: department, email: email})
		.then(( response )=>{
			console.log(response);
    		console.log('...FIN')
			console.log("Cliente guardado");
			mostrarLoader(false)
			// Crear objeto cliente
			cliente = {
				document: document,
				name: name,
				address: address,
				city: city,
				department: department,
				email: email,
				phone: phone
			};

			if(modal == "modalSetClientByButton"){// SI es por boton simplemente cierro el modal
				$('#buttonSetClient').find('span').html(name);
			} else { // Si es por continuar la compra abro el otro modal
				$('#buttonSetClient').find('span').html(name);
				console.log("ABRIR: modalSetTypeVoucher")
				$('#modalSetTypeVoucher').modal('show')
			}
		})
		.catch(()=>{
			mostrarLoader(false)
			console.log('...FIN')
       		console.log("Error al guardar el cliente");
		})
}

function onChangeTypeVoucher(selectTypeVoucher){ 
	console.log("onChangeTypeVoucher");
	if(selectTypeVoucher.value == "101_credito" || selectTypeVoucher.value == "111_credito"){ // CREDITOS
		$('#containerInfoCredito').removeClass("d-none");
		let futureDate = new Date();
		futureDate.setDate(futureDate.getDate() + 30);
		document.getElementById("inputDateExpirationVoucher").valueAsDate = futureDate;
	} else {
		$('#containerInfoCredito').removeClass("d-none").addClass("d-none");
	}
}

function verifyClient(){
	if(cliente){ // Si hay cliente verifico si es empresa o persona
		if (!validateRut(cliente.document))
			return "persona";
		else
			return "empresa";
    } else {
		return "persona";
	}
}

function validarCliente(document, name, address, city, department, email, phone) {
    console.log("validarCliente");
    if (!department || !city || !address || !name) {
        return "Campos obligatorios incompletos";
    }
    if (!validateRut(document) && !validateCI(document)) {
        return "Documento inválido";
    }
    return true;
}

function switchExpirationDate(element){
	console.log("switchExpirationDate")
	const expirationDateInput = document.getElementById("inputDateExpirationVoucher");
	if (element.checked) {
		expirationDateInput.disabled = true;
    } else {
		expirationDateInput.disabled = false;
    }
}

function confirmTypeVoucher(modal){
	console.log("confirmTypeVoucher")
	if($('#selectTypeVoucher').val() == "101_credito" || $('#selectTypeVoucher').val() == "111_credito"){// VENTA A CREDITO GENERAR CFE
		console.log('VENTA A CREDITO')
		
		console.log(cliente)
		let error = false;
		let tipoCod = null;
		let mediosPago = []
		let tipoMoneda = caja.moneda
		let adenda = $('#adenda').val() || null; // ADENDA
		let discountTipo = configDiscountInPercentage
		let dateValueExpiration = $('#inputDateExpirationVoucher').val();

		if(cliente){
			tipoCod = validateRut(cliente.document) ? 111 : 101;
			tipoCod == 101 && (!validateCI(cliente.document)) ? error = "CLIENTE FINAL PERO CI NO VALIDA" : error = false
		} else {
			tipoCod = 101;
		}

		if(!$('#inputNotUseExpirationDate').is(':checked')){
			if (dateValueExpiration) {
				// Convertir de YYYY-MM-DD a YYYYmmDD
				dateValueExpiration = dateValueExpiration.replace(/-/g, '');
			} else {
				error = "FECHA VENCIMIENTO NO VALIDA"
			}
		}

		if(error){
			showReplyMessage(1, error, "Error", "modalSetTypeVoucher");
			return;
		}

		let data = {
			client: JSON.stringify(cliente ? [cliente] : []),
			typeVoucher: tipoCod, // 101/111
			typeCoin: tipoMoneda,
			formaPago: 2, // formaPago 2 credito | 1 contado
			dateVoucher: null, // Hoy
			adenda: adenda,
			idBuy: null,
			detail: null, // Lista de articulos
			amount: null, // ESTO SE ENVIA AL PEDO
			discountTipo: (discountTipo == true) ? 2 : 1, // En procentaje | en importe
			mediosPago: null
		};
		if(!$('#inputNotUseExpirationDate').is(':checked'))
			data.dateExpiration = dateValueExpiration
		
		{ // LOGS
			console.log(cliente)
			console.log(error)
			console.log(tipoCod)
			console.log(mediosPago)
			console.log(tipoMoneda)
			console.log(data)
		}
		$('#modalSetTypeVoucher').modal('hide')
		mostrarLoader(true)
		sendAsyncPost("createNewVoucherPointSale", data)
		.then(function(response){
			mostrarLoader(false)
			console.log(response)

			// console.log(response)
			if (response.result == 2 ){
				let data = {id:response.info.ID}
				openModalVoucherFromPointSale(data, "CLIENT", "sale");
				$('#modalSeeVoucher button.close').on('click', function (e) {
					discardCart()
				})
			} else {
				showReplyMessage(response.result, response.message, "Nueva factura", null);
			}

		})
		.catch(function(response){
			mostrarLoader(false)
			console.log("este es el catch", response);
		});
	} else { // VENTA CONTADO ABRIR MODAL METODOS DE PAGO
		resetPayments()
		$('#modalSetTypeVoucher').modal('hide')
		console.log("ABRIR: modalSetPayments")
		insertRemainingAmount('inputRemainingAmount')
		$('#modalSetPayments').modal('show')
	}
}

function resetPayments(){ // Esta funcion resetea todos los medios de pago (Si existen... Limpia el modal por completo)
	console.log("resetPayments")
	$('#containerPayments .row').not('.payments-header').remove();
}

function insertNewPayment(){ // ABRE EL MODAL PARA SELECCIONAR EL MEDIO DE PAGO
	console.log("insertNewPayment")
	insertRemainingAmount('modalInsertNewPaymentMethodAmount')
	$('#modalSetPayments').modal('hide')
	$('#modalInsertNewPaymentMethod').modal('show')
}

function cancelInsertPaymentMethod(action = null, token = null){ // CANCELA LA INSERCION DE UN NUEVO MEDIO DE PAGO (CIERRA EL MODAL Y ABRE EL ANTERIOR) (STATUS ME INTERESA SOLO SI VIENE DE CANCELAR UNA TRANSACCION CON POS)
	console.log("cancelInsertPaymentMethod - " + action + " - " + token) // cierro todos porque puede venir de varios lugares
	$('#modalInsertNewPaymentMethod').modal('hide') // cierro todos porque puede venir de varios lugares
	$('#modalConfigMethodPayment').modal('hide') // cierro todos porque puede venir de varios lugares
	// $('#modalPOSPayment').modal('hide') // Este modal veo cuando lo cierro
	if($('#statusText').text().includes("CANCELADA") || $('#statusText').text().includes("EXPIRADA")){
		$('#modalPOSPayment').modal('hide')
		$('#modalSetPayments').modal('show')
		return;
	}
	if(action){ // SI tiene valor es porque un pago ya se realizo con tarjeta pero quiero cancelarlo
		console.log(action)
		//("Transaccion POS: Si viene 'CANCEL' deberia cancelar la transaccion (necesito el token), si viene 'ERROR' YA esta cancelada, si viene 'REV' debo hacer una devolucion porque ya se completo la transaccion")
		if(action == "CANCEL"){
			if(token){
				console.log(token)
				// Enviar la petición al backend
				$('#CancelPOSPaymentLoader').removeClass('d-none')
				$('#btnCancelPOSPayment').prop('disabled', true)
				sendAsyncPost("cancelarTransaccion", {tokenNro: token})
				.then(function(response) {
						$('#CancelPOSPaymentLoader').addClass('d-none')
						// $('#btnCancelPOSPayment').prop('disabled', false)
						console.log(response)
						// QUE HACER?
					})
					.catch(function(error) {
						$('#CancelPOSPaymentLoader').addClass('d-none')
						// QUE HACER?
						console.log(error)
						console.log("ERROR EN LA PETICION")
					});
			}
		} else if(action == "ERROR"){
			$('#modalPOSPayment').modal('hide')
			$('#modalSetPayments').modal('show')
		}
	} else {
		$('#modalSetPayments').modal('show')
	}
}

function cancelTypeVoucher(){
	$('#modalSetTypeVoucher').modal('hide')
}

function insertRemainingAmount(campo){ // INSERTA LA CANTIDAD QUE FALTA PARA COMPLETAR LA VENTA EN EL CAMPO DADO
	console.log("insertRemainingAmount")
	total = $('#inputPriceSale').val() || 0
	total = parseFloat(total.replace(/[$,]/g, ''))
	totalPayments = 0
	// Get all input values within containerPayments and sum them
    $('#containerPayments input[type="number"]').each(function() {
        let value = parseFloat($(this).val()) || 0;
    
		// Sumar al total
		totalPayments += value;
		// Corregir el formato del campo (agregar .00 si no tiene decimales)
	    $(this).val(value.toFixed(2));
    });
	console.log("PRECIO VENTA:" + total)
	console.log("SUMA DE TODOS LOS METODOS DE PAGO: " + totalPayments)
	if(campo)
		$('#' + campo).val(parseFloat(total - parseFloat(totalPayments).toFixed(2)).toFixed(2))
	$('#inputRemainingAmount').trigger('change')
}

function isValid(element){
	console.log("isValid - " + element.value)
	if(parseFloat(element.value) === 0.00){
		$(element).removeClass('bg-danger bg-success').addClass('bg-success')
	} else {
		$(element).removeClass('bg-danger bg-success').addClass('bg-danger')
	}
}

async function insertPaymentMethod(method, amount, skipMethod){ //INSERTA UNA NUEVA FILA (METODO DE PAGO) CON EL METODO QUE RECIBA Y LA CANTIDAD [skipMethod dice si ya se compoletaron los campos de ese metodo de pago y skipea el switch]
	console.log("insertPaymentMethod - " + method + " - " + amount + " - " + skipMethod)
	if(parseFloat($('#inputRemainingAmount').val()) < parseFloat(amount)){
		showReplyMessage(1, "El monto ingresado supera el importe total de la venta, por favor corregir", "Importe inválido", "modalInsertNewPaymentMethod");
		return;
	}
	console.log(skipMethod)
	if(skipMethod == null)
		skipMethod = false;
	if(!skipMethod && method != 'Efectivo'){ // SI no tiene que saltearse y si no es Efectivo
		switch (method) {
			case 'Tarjeta':
				if(caja.POS){
					$('#modalInsertNewPaymentMethod').modal('hide')
					if(parseFloat(amount || 0) <= 0) {
						showReplyMessage(1, "Imposible usar el método de pago 'tarjeta' con este importe", "Importe inválido", "modalInsertNewPaymentMethod");
						insertRemainingAmount('modalInsertNewPaymentMethodAmount')
						return;
					} else {
						let consumidorFinal = null;
						if(!CFE_reservado){
							let TipoCFE = null;
							if(cliente){
								TipoCFE = validateRut(cliente.document) ? 111 : 101;
							} else {
								TipoCFE = 101;
							}
							(TipoCFE == 111) ? consumidorFinal = false : consumidorFinal = true;
							let responseCFE = await sendAsyncPost("reserveCFE", {TipoCFE: TipoCFE});
							// console.log(responseCaja)
							if(responseCFE.result == 2){
								CFE_reservado = responseCFE.CFE
								openModalPOS(amount, consumidorFinal, CFE_reservado.numeroCFE)
							} else {
								CFE_reservado = null
								showReplyMessage(1, "Error al obtener el número de factura", "Error interno", "modalInsertNewPaymentMethod");
							}
						} else {
							let TipoCFE = null;
							if(cliente){
								TipoCFE = validateRut(cliente.document) ? 111 : 101;
							} else {
								TipoCFE = 101;
							}
							(TipoCFE == 111) ? consumidorFinal = false : consumidorFinal = true;

							openModalPOS(amount, consumidorFinal, CFE_reservado.numeroCFE)
						}
					}
				} else {
					$('#modalInsertNewPaymentMethod').modal('hide')
					openModalConfigMethodPayment(amount, method)
				}
				break;
			
			case 'Cheque':
				$('#modalInsertNewPaymentMethod').modal('hide')
				openModalConfigMethodPayment(amount, method)
				break;
			
			case 'Depósito':
				$('#modalInsertNewPaymentMethod').modal('hide')
				openModalConfigMethodPayment(amount, method)
				
				break;
			default:
				$('#modalInsertNewPaymentMethod').modal('hide')
				openModalConfigMethodPayment(amount, method)

				break;
		}
	} else {
		$('#modalConfigMethodPayment').modal('hide') // ESCENCIAL
		console.log(skipMethod)
		// Creating a new row with the selected payment method and amount
		let newRow = $('<div class="row mt-1" style="align-items: center; padding: .2rem;"></div>');
		
		let col8 = $('<div class="col-6 pr-1"></div>');
		let pTag = $('<p class="mt-0 mb-0 form-control method-val" data-banco="' + (skipMethod.banco ? skipMethod.banco : "") + '" style="font-size: .875rem;">' + method + '</p>');
		col8.append(pTag);
		
		let colAuto1 = $('<div class="col pl-1 pr-1" data-banco="' + (skipMethod.banco ? skipMethod.banco : "") + '"></div>');
		
		let disabledAttr = (method == 'Tarjeta' && caja.POS) ? 'disabled' : '';
		let input = $('<input type="number" min="0" value="' + amount + '" name="amount" style="font-size: .875rem;" class="form-control text-center amount-val" onchange="insertRemainingAmount(\'inputRemainingAmount\')" ' + disabledAttr + '>');
		
		// NEW [data medio_pago]
		let hidden_banco = $('<input type="hidden" value="' + (skipMethod.banco ? skipMethod.banco : "") + '" name="banco" >');
		let hidden_fecha = $('<input type="hidden" value="' + (skipMethod.fecha ? skipMethod.fecha : "") + '" name="fecha" >');
		let hidden_fecha_diferido = $('<input type="hidden" value="' + (skipMethod.fecha_diferido ? skipMethod.fecha_diferido : "") + '" name="fecha_diferido" >');
		let hidden_titular = $('<input type="hidden" value="' + (skipMethod.titular ? skipMethod.titular : "") + '" name="titular" >');
		let hidden_obs = $('<input type="hidden" value="' + (skipMethod.obs ? skipMethod.obs : "") + '" name="obs" >');
		// END [data medio_pago]
		
		colAuto1.append(input);

		skipMethod.banco && colAuto1.append(hidden_banco);
		skipMethod.fecha && colAuto1.append(hidden_fecha);
		skipMethod.fecha_diferido && colAuto1.append(hidden_fecha_diferido);
		skipMethod.titular && colAuto1.append(hidden_titular);
		skipMethod.obs && colAuto1.append(hidden_obs);
		
		let colAuto2 = $('<div class="col-1 pl-1" style="max-width: 54px; min-width: 54px;"></div>');
		if(method == 'Tarjeta' && caja.POS){
			let btnPayback = $(`<button onclick="payBackPaymentMethod(this,'${skipMethod.consumidor}', '${skipMethod.RUT}', '${skipMethod.monto}', '${skipMethod.gravado}', '${skipMethod.factura}', '${skipMethod.ticket}')" title="Hacer devolución" class="btn btn-warning p-1 pl-2 pr-2 btn-dev"> <i class="fas fa-undo-alt"></i> </button>`);
			colAuto2.append(btnPayback);
		} else {
			let btnDelete = $('<button onclick="deletePaymentMethod(this)" title="Eliminar método" class="btn btn-warning p-1 pl-2 pr-2 btn-supr"> <i class="fas fa-trash-alt"></i> </button>');
			colAuto2.append(btnDelete);
		}
		
		newRow.append(col8, colAuto1, colAuto2);
		
		$('#containerPayments').append(newRow);
		$('#modalInsertNewPaymentMethod').modal('hide')
		insertRemainingAmount('inputRemainingAmount')
		$('#modalSetPayments').modal('show')
	}
}

function payBackPaymentMethod(element, consumidor, RUT, monto, gravado, factura, ticket){
	console.log("payBackPaymentMethod")
	// console.log($(element).closest('.row.mt-1'))
	// console.log(consumidor)
	// consumidor = consumidor == "BOLETA CON RUT" ? false : true;
	// console.log(consumidor)
	// console.log(RUT)
	// monto = monto / 100
	// gravado = gravado / 100
	// console.log(monto / 100)
	// console.log(gravado / 100)
	// console.log(factura)
	// console.log(ticket)
	const icon = $('#POSDEV-cardIcon');
	const text = $('#POSDEV-statusText');
	const button = $('#btnConfirmPOSDEV');
	const buttonCancel = $('#btnCancelPOSDEV');
	$('#POSDEV-logText').text('');

	$('#POSDEV-amount').text(getFormatValue(monto))
	// VISUAL
	icon.removeClass().addClass('fas fa-credit-card fa-5x card-pulse text-primary');
	text.text('Esperando conexión con POS...').removeClass().addClass('status-text text-processing');
	button.prop('disabled', true).find('.maintext').text('Procesando...');
	// END
	$('#modalSetPayments').modal('hide');
	$('#modalPOSDEV').modal('show');
	
	console.log(parseFloat(monto))
	const data = {
		monto: monto,
		consumidorFinal: consumidor,
		gravado: gravado,
		factura: factura,
		ticket: ticket,
		tipo: "DEV"
	};
	// return;
	// Enviar la petición al backend
	sendAsyncPost("postearTransaccionDEV", data)

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
				consultarEstadoTransaccionDEV(tokenNro, 0, 30, element);
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

// function cancelPOSPayment(){
// 	console.log("cancelPOSPayment")
// 	$('#modalPOSPayment').modal('hide')
// 	$('#modalSetPayments').modal('show')
// }

function deletePaymentMethod(element){ // ELIMINA UN MEDIO DE PAGO
	console.log("deletePaymentMethod")
	console.log(element)
	const rowDiv = element.closest('div.row');
    if (rowDiv) {
        rowDiv.remove();
    }
	insertRemainingAmount('inputRemainingAmount')
}

// function calcTotal(){ // EL DESCUENTO SE HACE SOBRE EL PRODUCTO CON IVA (EN CASO DE IVA INC.) Y CADA PRODUCTO NO A LA SUMA DE TODOS ESTOS (EJ: 2 x $10 con Dcto: $8 => $4 porque el $8 se le aplica a cada producto)
// 	console.log('calcTotal')
// 	// VARIABLES DEL TOTAL DE LA VENTA
// 	let sale_total = 0;
// 	let sale_gravado_basico = 0;
// 	let sale_iva_basico = 0;
// 	let sale_gravado_minimo = 0;
// 	let sale_iva_minimo = 0;
// 	let sale_no_gravado = 0;
	
// 	for (var i = 0; i < productsInCart.length; i++){
// 		let subTotal = 0 // PRODUCTO SIN IVA
// 		let total = 0 // PRODUCTO CON IVA
// 		let iva = 0; // IVA
// 		let descuento = 0;	// DESCUENTO ($) // calcular porque puede estar en %

// 		if (productsInCart[i].includeIva){ // EL IMPORTE ES EL VALOR YA CON IVA
// 			subTotal = productsInCart[i].import / getIvaValue(productsInCart[i].idIva) // getIvaValue devuelve 1.22 o 1.1 o 1 dependiendo del iva (1 es excento = 1, 2 es minimo = 1.1, 3 es basico = 1.22, los demas = 1)
// 			total = productsInCart[i].import
// 		} else {
// 			subTotal = productsInCart[i].import
// 			total = subTotal * getIvaValue(productsInCart[i].idIva)
// 		}


// 		if(!configDiscountInPercentage){ // DESCUENTO $
// 			if(configIncludeIva){ // Punto de venta en modo IVA incluido | entonces el descuento es por sobre el valor total
// 				subTotal = productsInCart[i].import - productsInCart[i].discount / getIvaValue(productsInCart[i].idIva) // 
// 				total = productsInCart[i].import - productsInCart[i].discount
// 			} else { // entonces el descuento es por sobre el sub total
// 				subTotal = productsInCart[i].import - productsInCart[i].discount
// 				total = subTotal * getIvaValue(productsInCart[i].idIva)
// 			}
// 		} else if(configDiscountInPercentage){ // DESCUENTO %
// 			if(configIncludeIva){ // Punto de venta en modo IVA incluido | entonces el descuento es por sobre el valor total
// 				descuento = total * (productsInCart[i].discount / 100)
// 				subTotal = productsInCart[i].import - descuento / getIvaValue(productsInCart[i].idIva) // 
// 				total = productsInCart[i].import - descuento
// 			} else { // entonces el descuento es por sobre el sub total
// 				descuento = subTotal * (productsInCart[i].discount / 100)
// 				subTotal = productsInCart[i].import - descuento
// 				total = subTotal * getIvaValue(productsInCart[i].idIva)
// 			}
// 		}
// 		console.log(total)
// 		switch (productsInCart[i].idIva) {
// 			case 1:
// 				sale_total = sale_total + total
// 				sale_no_gravado = sale_no_gravado + subTotal
// 				break;
		
// 			case 2:
// 				sale_total = sale_total + total
// 				sale_gravado_minimo = sale_gravado_minimo + subTotal
// 				sale_iva_minimo = sale_iva_minimo + (total - subTotal)
// 				break;
			
// 			case 3:
// 				sale_total = sale_total + total
// 				sale_gravado_basico = sale_gravado_basico + subTotal
// 				sale_iva_basico = sale_iva_basico + (total - subTotal)
// 				break;
		
// 			default:
// 				sale_total = sale_total + total
// 				sale_no_gravado = sale_no_gravado + subTotal
// 				break;
// 		}

// 		sale_total = parseFloat(sale_total).toFixed(2)
// 		sale_gravado_basico = parseFloat(sale_gravado_basico).toFixed(2)
// 		sale_iva_basico = parseFloat(sale_iva_basico).toFixed(2)
// 		sale_gravado_minimo = parseFloat(sale_gravado_minimo).toFixed(2)
// 		sale_iva_minimo = parseFloat(sale_iva_minimo).toFixed(2)
// 		sale_no_gravado = parseFloat(sale_no_gravado).toFixed(2)
// 	}

// 	$('#inputPriceSale').val(getFormatValue(sale_total));
// 	$('#inputPriceSale-gravado-basica').val(getFormatValue(sale_gravado_basico));
// 	$('#inputPriceSale-iva-basico').val(getFormatValue(sale_iva_basico));
// 	$('#inputPriceSale-gravado-minima').val(getFormatValue(sale_gravado_minimo));
// 	$('#inputPriceSale-iva-minimo').val(getFormatValue(sale_iva_minimo));
// 	$('#inputPriceSale-nogravado').val(getFormatValue(sale_no_gravado));

// }

function calcTotal(){
    console.log('calcTotal')
    let sale_total = 0;
    let sale_gravado_basico = 0;
    let sale_iva_basico = 0;
    let sale_gravado_minimo = 0;
    let sale_iva_minimo = 0;
    let sale_no_gravado = 0;
    
    for (var i = 0; i < productsInCart.length; i++){
        let subTotal = 0;
        let total = 0;
        let descuento = 0;

        // Convertir a número por si es string
        let importValue = parseFloat(productsInCart[i].import) || 0;
        let discountValue = parseFloat(productsInCart[i].discount) || 0;
        let quantity = parseFloat(productsInCart[i].quantity) || 1; // ✅ parseFloat para decimales
        
        // Cálculo inicial
        if (productsInCart[i].ivaIncluded){
            total = importValue;
            subTotal = total / getIvaValue(productsInCart[i].idIva);
        } else {
            subTotal = importValue;
            total = subTotal * getIvaValue(productsInCart[i].idIva);
        }

        // Aplicar descuentos
        if(!configDiscountInPercentage){ // DESCUENTO $
            if(configIncludeIva){
                total = total - (discountValue / quantity);
                subTotal = total / getIvaValue(productsInCart[i].idIva);
            } else {
                subTotal = subTotal - (discountValue / quantity);
                total = subTotal * getIvaValue(productsInCart[i].idIva);
            }
        } else if(configDiscountInPercentage){ // DESCUENTO %
            if(configIncludeIva){
                descuento = total * (discountValue / 100);
                total = total - descuento;
                subTotal = total / getIvaValue(productsInCart[i].idIva);
            } else {
                descuento = subTotal * (discountValue / 100);
                subTotal = subTotal - descuento;
                total = subTotal * getIvaValue(productsInCart[i].idIva);
            }
        }

        // Multiplicar por cantidad (puede ser decimal)
        total = total * quantity;
        subTotal = subTotal * quantity;

        console.log('Producto', i, 
                   'Unidad:', productsInCart[i].unidad_venta, 
                   'Cantidad:', quantity, 
                   'Total unitario:', (total/quantity).toFixed(2), 
                   'Total línea:', total.toFixed(2));
        
        // Sumar a los totales
        switch (productsInCart[i].idIva) {
            case 1:
                sale_total += total;
                sale_no_gravado += subTotal;
                break;
            case 2:
                sale_total += total;
                sale_gravado_minimo += subTotal;
                sale_iva_minimo += (total - subTotal);
                break;
            case 3:
                sale_total += total;
                sale_gravado_basico += subTotal;
                sale_iva_basico += (total - subTotal);
                break;
            default:
                sale_total += total;
                sale_no_gravado += subTotal;
                break;
        }
    }

    // Actualizar los inputs
    $('#inputPriceSale').val(getFormatValue(sale_total));
    $('#inputPriceSaleModal').val(getFormatValue(sale_total));

    $('#inputPriceSale-gravado-basica').val(getFormatValue(sale_gravado_basico));
    $('#inputPriceSale-iva-basico').val(getFormatValue(sale_iva_basico));
    $('#inputPriceSale-gravado-minima').val(getFormatValue(sale_gravado_minimo));
    $('#inputPriceSale-iva-minimo').val(getFormatValue(sale_iva_minimo));
    $('#inputPriceSale-nogravado').val(getFormatValue(sale_no_gravado));
	showDetailsPricesFields() // Si los campos se tienen que mostrar
}

function getFormatValue(value){
	let formatter = new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'USD',
	});

	return formatter.format(value);
}

function getListPrice(element){ // INSERTA LOS ARTICULOS AL MODAL LISTA DE PRECIOS
	console.log("getListPrice")
	if($(element).val().length > 0 || $('#inputTextToSearchPrice').prop( "readOnly") == false){
		let valueToSearch = $('#inputTextToSearchPrice').val();
		let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
		$('#tbodyListPrice').empty();
		if(response.result == 2){
			let list = response.listResult;
			firstRow = true;
			for (var i = 0; i < list.length; i++) {
				let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda, 1);
				$('#tbodyListPrice').append(row);
				if(firstRow){
					$('#tbodyListPrice tr:first').addClass('selected')
					firstRow = false
				}
			}
		}
	}
}

function createRowListPrice(idProduct, description, heading, price, coin, cantidad){ // CREA UNA LINEA EN LA TABLA DE PRODUCTOS EN EL MODAL LISTA DE PRECIOS cantidad es la cuantos agrega el boton (si viene por codebar puede que ser distinto a 1)
	let row = "<tr>";
	let priceUYU = "";
	let priceUSD = "";

	if (coin == 'UYU'){
		priceUYU = "<td class='text-center align-middle col-2'> $  "+ price +"</td>";
	}
	else if (coin == 'USD'){
		priceUSD = "<td class='text-center align-middle col-2'> U$S  "+ price +"</td>";
	}

	row += "<td class='text-left align-middle col-6'>"+ description +"</td>";
	row += "<td class='text-left align-middle col-2'>"+ heading +"</td>";
	row += priceUYU;
	row += priceUSD
	row += "<td class='text-center align-middle col-2'><button onclick='addProductById(" + idProduct + ", " + cantidad + ")' class='btn btn-sm background-template-color2 text-template-background'><i class='fas fa-cart-plus text-mycolor'></i></button></td>";

	return row;
}

function getSuggestionProduct(inputToSearch){// CREA EL DATALIST DE PRODUCTOS QUE COINCIDAN CON LA DESCRIPCION ESCRITA
	console.log("getSuggestionProduct")
	let valueToSearch = inputToSearch.value;
	let response = "";
	if(valueToSearch.length == 0){
		$("#listProducts").empty();
	}
	else if (valueToSearch.length >= 3){
		response = 	sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
	}
	$("#listProducts").empty();
	if(response.result == 2){
		let list = response.listResult; //los productos encontrados
		let option = null;
		for (let i = list.length - 1; i >= 0; i--) {//si hay más de una coincidencia
			option = newOption(list[i].idArticulo, list[i].descripcion);
			$("#listProducts").append(option); //select de articulos por descripcion
		}
	}
}

function newOption(idArticulo, descripcion){
    // Las opciones del datalist deben mostrar la descripción y tener value
    return `<option value="${descripcion}" data-id="${idArticulo}">${descripcion}</option>`;
}

function selectItem(id, cantidad){
	console.log("selectItem - " + id + " - " + cantidad)
	addProductById(id, cantidad)
	$('#modalAddProduct').modal('hide')
}

function createNewFactura(){ // DESDE EL BOTON DE MEDIOS DE PAGOS
	console.log("Create New Factura")

	if(parseFloat($('#inputRemainingAmount').val().trim()) != 0){
		showReplyMessage(1, "El importe acreditado no coincide con el total de la venta", "Error", "modalSetPayments");
		return;
	}
	console.log(cliente)
	console.log(voucherData)
	let error = false;
	let tipoCod = null;
	let mediosPago = []
	let tipoMoneda = caja.moneda
	let adenda = $('#adenda').val() || null; // ADENDA
	let discountTipo = configDiscountInPercentage

	if(cliente){
		tipoCod = validateRut(cliente.document) ? 111 : 101;
		tipoCod == 101 && (!validateCI(cliente.document)) ? error = "CLIENTE FINAL PERO CI NO VALIDA" : error = false
	} else {
		tipoCod = 101;
	}
	
	$('#containerPayments .row').not('.payments-header').each(function() {
        let value = (parseFloat($(this).find('.amount-val').val()) || 0).toFixed(2);
		
		let medioPago = {
			'codigo': getCode($(this).find('.method-val').text()),
			'glosa': $(this).find('.form-control').text(),
			'valor': parseFloat(value)
		}
		
		// Agregar todos los input type="hidden" al objeto medioPago
		$(this).find('input[type="hidden"]').each(function() {
			let name = $(this).attr('name');
			let val = $(this).val();
			if (name) { // Solo agregar si tiene atributo name
				medioPago[name] = val;
			}
		});
		
		mediosPago.push(medioPago);
    });
	
	

	if(error){
		showReplyMessage(1, error, "Error", "modalInsertNewPaymentMethod");
		return;
	}

	let data = {
		client: JSON.stringify(cliente ? [cliente] : []),
		typeVoucher: tipoCod, // 101/111
		typeCoin: tipoMoneda,
		formaPago: 1, // formaPago 2 credito | 1 contado
		dateVoucher: null, // Hoy
		adenda: adenda,
		idBuy: null,
		detail: null, // Lista de articulos
		amount: null, // ESTO SE ENVIA AL PEDO
		discountTipo: (discountTipo == true) ? 2 : 1, // En procentaje | en importe
		mediosPago: JSON.stringify(mediosPago)
	};
	if(CFE_reservado)
		data.CFE_reservado = CFE_reservado;

	{ // LOGS
		console.log(cliente)
		console.log(error)
		console.log(tipoCod)
		console.log(mediosPago)
		console.log(tipoMoneda)
		console.log(CFE_reservado)
		console.log(data)
	}
	$('#modalSetPayments').modal('hide')
	mostrarLoader(true)
	sendAsyncPost("createNewVoucherPointSale", data)
	.then(function(response){
		mostrarLoader(false)
		console.log(response)

		// console.log(response)
		if (response.result == 2 ){
			let data = {id:response.info.ID}
			openModalVoucherFromPointSale(data, "CLIENT", "sale");
			$('#modalSeeVoucher button.close').on('click', function (e) {
				discardCart()
			})
		} else {
			showReplyMessage(response.result, response.message, "Nueva factura", null);
		}

	})
	.catch(function(response){
		mostrarLoader(false)
		console.log("este es el catch", response);
	});
}

function superFastSale(){
	console.log("superFastSale")
	if (productsInCart.length == 0) {
		showReplyMessage(1, "Ningún producto ingresado", "Producto requerido", null);
		return;
	}

	if (!configSuperFastSale) {
		showReplyMessage(1, "Venta rápida desactivada", "Opción desactivada", null);
		return;
	}

	let dateVoucher = getCurrentDate();
	let adenda = $('#adenda').val() || null; // ADENDA
	let tipoCod = 101;
	let tipoMoneda = caja.moneda
	let discountTipo = configDiscountInPercentage
	let mediosPago = []
	let total = $('#inputPriceSale').val() || 0
	let importe = parseFloat(total.replace(/[$,]/g, ''))
	let error = false

	let medioPago = {
		'codigo': 1,
		'glosa': 'Efectivo',
		'valor': importe
	}
	mediosPago.push(medioPago);

	let data = {
		client: JSON.stringify([]),
		typeVoucher: tipoCod, // 101/111
		typeCoin: tipoMoneda,
		formaPago: 1, // formaPago 2 credito | 1 contado
		dateVoucher: null, // Hoy
		adenda: adenda,
		idBuy: null,
		detail: null, // Lista de articulos
		amount: null, // ESTO SE ENVIA AL PEDO
		discountTipo: (discountTipo == true) ? 2 : 1, // En procentaje | en importe
		mediosPago: JSON.stringify(mediosPago)
	};
	
	// mostrarLoader(true)

	{ // LOGS
		console.log(cliente)
		console.log(error)
		console.log(tipoCod)
		console.log(mediosPago)
		console.log(tipoMoneda)
		console.log(CFE_reservado)
		console.log(data)
	}	
	// return; // LOCO
	mostrarLoader(true)
	sendAsyncPost("createNewVoucherPointSale", data)
	.then(function(response){
		mostrarLoader(false)
		console.log(response)

		// console.log(response)
		if (response.result == 2 ){
			// let responseVoucher = sendPost("getLastVoucherEmitted");
			let data = {id:response.info.ID}
			openModalVoucherFromPointSale(data, "CLIENT", "sale");
			$('#modalSeeVoucher button.close').on('click', function (e) {
				discardCart()
			})
		} else {
			showReplyMessage(response.result, response.message, "Nueva factura", null);
		}

	})
	.catch(function(response){
		mostrarLoader(false)
		console.log("este es el catch", response);
	});
}

// FUNCIONES REPLICADAS

function openModalVoucherFromPointSale(data, prepareFor, view){ // Se entiende que es 'CLIENT' y 'sale' siempre
	mostrarLoader(true)
	let idVoucher = data.id;
	let responseGetCFE = sendPost('getVoucherCFE', {idVoucher: idVoucher, prepareFor: prepareFor});
	if(responseGetCFE.result == 2){
		mostrarLoader(false)
		let iFrame = document.getElementById("frameSeeVoucher");
		var dstDoc = iFrame.contentDocument || iFrame.contentWindow.document;
		dstDoc.write(responseGetCFE.voucherCFE.representacionImpresa);
		dstDoc.close();

		$('#buttonExportVoucher').off('click');
		$('#buttonExportVoucher').click(function(){
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

		console.log("open modal voucher para clientes");
		$('#buttonCancelVoucher').css('visibility', 'visible');
		let responseConsultCaes = sendPost('consultCaes', {typeCFE: responseGetCFE.voucherCFE.tipoCFE});
		console.log(responseConsultCaes);
		console.log("vista "+view);
		if(responseConsultCaes.result == 2){
			$('#buttonCancelVoucher').off('click');
			$('#buttonCancelVoucher').click(function(){
				$('#modalSeeVoucher').modal('hide');
				$('#modalCancelVoucher').modal();
				$('#inputDateCancelVoucher').val(getDateIntToHTML(responseGetCFE.voucherCFE.fecha));
				$('#btnCancelVoucher').off('click');
				$('#btnCancelVoucher').click(function(){
					cancelVoucherFromPointSale(idVoucher);
				});
			});
		} else {
			$('#buttonCancelVoucher').off('click');
			$('#buttonCancelVoucher').click(function(){
				$('#modalSeeVoucher').modal('hide');
				showReplyMessage(responseConsultCaes.result, responseConsultCaes.message, "Anular comprobante", "modalSeeVoucher");
			});
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

		$('#modalSeeVoucher').modal({
            keyboard: false,
            backdrop: 'static'
        });
	}else {
		mostrarLoader(false)
		if ( !responseGetCFE.message || responseGetCFE.message == "" ){
			showReplyMessage(responseGetCFE.result, "No se encontró el comprobante. Intente nuevamente.", "Ver comprobante", null);
		}
		else showReplyMessage(responseGetCFE.result, responseGetCFE.message, "Ver comprobante", null);
	}
}

function cancelVoucherFromPointSale(idVoucher){
	let dateCancelVoucher = $('#inputDateCancelVoucher').val() || null;
	let appendix = $('#inputCancelAppendix').val() || null;
	let response = sendPost("cancelVoucherEmitted", {idVoucher: idVoucher, dateCancelVoucher: dateCancelVoucher, appendix: appendix});
	showReplyMessageWithFunction(
                response.result, 
                response.message, 
                "Cancelar comprobante", 
                "modalCancelVoucher",
				function() {
                    if(response.result == 2)
						discardCart()
                }
            );
}

// FUNCIONES REPLICADAS END

// ----------------------------------------------------------------------------

function extractPaymentMethods() {
	const container = document.getElementById('containerPayments');
	const rows = container.querySelectorAll('.row:not(:first-child)');
	
	const paymentMethods = Array.from(rows).map((row, index) => {
	  const glosa = row.querySelector('p').textContent.trim();
	  const valor = parseFloat(row.querySelector('input[type="number"]').value) || 0;
	  
	  return {
		codigo: getCode(glosa),
		glosa: glosa,
		valor: valor
	  };
	});
  
	return paymentMethods;
}

function getCode(glosa){
	respuesta = null
	switch (glosa) {
		case 'Efectivo':
			respuesta = 1
			break;
		case 'Tarjeta':
			respuesta = 2
			break;
		case 'Cheque':
			respuesta = 3
			break;
		case 'Giro':
			respuesta = 4
			break;
		case 'Depósito':
			respuesta = 5
			break;
		case 'Vale':
			respuesta = 6
			break;
		case 'Pendiente':
			respuesta = 7
			break;
		case 'Resguardo de IVA':
			respuesta = 8
			break;
		case 'Certificado de Crédito':
			respuesta = 9
			break;
		case 'Orden de Compra':
			respuesta = 10
			break;
		case 'Otros':
			respuesta = 11
			break;
		case 'Tarjeta Offline':
			respuesta = 12
			break;
			
		default:
			respuesta = 11
			break;
	}
	return respuesta;
}

function confirmSale(){
	// console.log("confirmSale")
	$('#confirmSaleBtn').click();
}

// function setNextStep(step){ // le settea el siguiente paso al boton de confirmar
// 	// console.log("setNextStep: " + step)
// 	switch (step) {
// 		case 'selectClient':
// 			$('#confirmSaleBtn').off('click').on('click', function () {
// 				$('#modalSetClient').modal({
// 					backdrop: 'static'
// 				});
// 			});
// 			break;
// 		case 'selectPaymentWay':
// 			$('#confirmSaleBtn').off('click').on('click', function () {
// 				// setClientFinal()
// 				let typeVoucher = $('#selectTypeVoucher').val() || null; // EFactura Contado/ETicket Contado / EFactura Credito/ETicket Credito
// 				if(typeVoucher == 211 || typeVoucher == 311){
// 					createNewFactura()
// 					return;
// 				}
// 				$('#inputPriceSale2').val($('#inputPriceSale').val())
// 				calculateRemainingAmount('inputPriceSale22');
// 				$('#modalSetPayments').off('shown.bs.modal').on('shown.bs.modal', function () {
// 					$('#modalSetPaymentsbtnConfirmSale').trigger('focus')
// 				});
// 				showModalPayment()
// 			});
// 			break;
// 		case 'selectTypeVoucher':
// 			$('#clientSelection').removeClass('d-none')
// 			$('#confirmSaleBtn').off('click').on('click', function () {
// 				if($('#buttonModalClientWithName').text() == "Consumidor final"){
// 					setClientFinal()
// 				}
// 				$('#selectTypeVoucher').focus()
// 				setNextStep('selectPaymentWay')
// 			});
// 			break;
// 		default:
// 			break;
// 	}
// }

function createNewProduct(producto, heading){ 
	if(producto.description && producto.description.length > 4){
		if( producto.amount > 0 ){
			let data = {
				idHeading: null, // 'VARIOS' POR DEFECTO
				idIva: producto.idIva,
				description: producto.description,
				detail: producto.detail,
				brand: producto.brand,
				typeCoin: "UYU",
				cost: producto.cost,
				coefficient: producto.coefficient,
				amount: producto.amount,
				barcode: null,
				discount: producto.discount,
				inventory: producto.count, // ES LA CANTIDAD QUE DEBE TENER EN STOCK
				minInventory: 0, //mìnima cantidad de articulos en stock
				unidadVenta: producto.unidadVenta
			}
			let response = sendPost('insertProduct',data);
			if(response.result != 2){
			}
			return response;
		}
	}

}

//esta funcion recibe el array que tiene todos los productos de la lista de compra.
//genera y devuelve un nuevo array solo con los elementos que tienen el campo removed como false
function prepareToCreateNewFactura(originalArray){ // VER VER VER
	var newArray = [];
	for (var i = 0; i<originalArray.length; i++) {
		if(!originalArray[i].removed || originalArray[i].removed == "false"){
			newArray.push(originalArray[i]);
		}
	}
	return newArray;
}



// function loadProductsInSession (){
// 	console.log("loadProductsInSession")
// 	let productsInSession = getDataSession("arrayProductsSales"); // ManagerDataSession.js
// 	productsInCart = productsInSession.data;
// 	indexDetail = productsInCart.length;
// 	if ( productsInSession.result == 2 ){
// 		insertAllElementsInDetail();
// 	}
// }

//se confirma y se agrega un nuevo articulo a la tabla de facturacion
function insertAllElementsInDetail(){ // En este punto de venta se vende en UYU y NO se toca el IVA, si tiene IVA se vende con él sino no
    console.log("insertAllElementsInDetail");
    // console.log("START COOKIES");
    // console.log(document.cookie);
    // console.log("END COOKIES");
	// let disabledTypeCoin = null;
	// let disabledIva = null;
	// let selectTypeCoinValue = null;
	if (productsInCart.length > 0){
		let cookieData = document.cookie.split("; ");
		for (var i = 0; i < cookieData.length; i++) {
            // console.log(cookieData[i]);
			// if (cookieData[i].includes("TYPECOIN")){ // SI no tiene TypeCoin lo tomo como UYU
			// 	selectTypeCoinValue = cookieData[i].split("=");
			// 	selectTypeCoinValue.value = selectTypeCoinValue[1];
			// }
		}
		// disabledTypeCoin = false;
		// disabledIva = false;
		// $('#selectTypeCoin').val(selectTypeCoinValue.value)
		// onChangeTypeCoin(selectTypeCoinValue.value);
	}

	for(var i = 0; i < productsInCart.length; i++) {
        if(productsInCart[i].typeCoin == ""){
            console.log("ESTE ARTICULO DEBERIA TENER COIN DE UYU");
        }
		let row = createDetailRow(productsInCart[i].idDetail, productsInCart[i].description, productsInCart[i].detail, productsInCart[i].count, productsInCart[i].price, productsInCart[i].discount, productsInCart[i].idIva, productsInCart[i].ivaValue, productsInCart[i].price, productsInCart[i].unidadVenta);
		$('#tbodyDetailProducts').prepend(row);
		if( productsInCart[i].removed == "true" ){
			$('#' + productsInCart[i].idDetail).addClass("removedElement");
		}
		if (productsInCart[i].removed == "false"){
			disabledTypeCoin = true;
			disabledIva = true;
		}
	}
	addTotal();
	// $('#selectTypeCoin').prop( "disabled", disabledTypeCoin );
	// $('#checkboxConfigIvaIncluido').prop( "disabled", disabledIva);
	$('#modalAddProduct').modal('hide');
}

//crea las lineas que se muestran en la tabla luego de agregarse los productos
// function createDetailRow(indexDetail, name, description, count, price, discount, idIva, ivaValue, total, unidadVenta){
// 	console.log(`createDetailRow [indexDetail: ${indexDetail}. name: ${name}. description: ${description}. count: ${count}. price: ${price}. discount: ${discount}. idIva: ${idIva}. ivaValue: ${ivaValue}. total: ${total}. unidadVenta: ${unidadVenta}.`)
// 	discount = discount ?? 0
// 	let row = "<tr id='" + indexDetail +"' >";

// 	row += "<td class='col-5 text-left overflow-example' title='"+ name +"'>"+ name;
// 	if(description){
// 		row += "<br>";
// 		row += "<p class='overflow-example' style='margin-bottom: 0;'> " + description + " </p>"

// 	} else {
// 		row += "</td>";
// 	}
// 	row += `<td class='col-1 text-left align-middle'>
// 				<button class='btn btn-danger btn-sm shadow-sm align-middle' style='width: 3em;' onclick='modalBorrarDetail(${indexDetail})'>
// 					<i class='fas fa-trash-alt'></i>
// 				</button>
// 			</td>`;
// 	let step = (unidadVenta === 'Peso' || unidadVenta === 'Litro') ? 0.1 : 1;
// 	let min = (unidadVenta === 'Peso' || unidadVenta === 'Litro') ? 0.001 : 1;	
// 	row += `<td class='col-2 text-right align-middle'>
// 				<input id='inputCount${indexDetail -1}' 
// 					type='number' 
// 					min='${min}' 
// 					step='${step}' 
// 					class='form-control form-control-sm text-right' 
// 					value='${count}' 
// 					oninput='handleCountChange(event, ${indexDetail - 1})'
// 					onblur='handleCountBlur(event, ${indexDetail - 1})'
// 					style='min-width: 100px;'>
// 			</td>`;
	
// 	row += `<td class='col-2 text-right align-middle'>
// 				<input id='inputDiscount${indexDetail -1}' 
// 					type='number' 
// 					min=0 
// 					max=100 
// 					class='form-control form-control-sm text-right' 
// 					value='${parseFloat(discount).toFixed(2)}' 
// 					oninput='handleDiscountChange(event, ${indexDetail - 1})'
// 					style='min-width: 100px;'>
// 			</td>`;
// 	row += `<td class='col-1 text-right align-middle' style='min-width: 100px;'>
// 				${getFormatValue(total)}
// 			</td>`;
// 	row += "</tr>";

// 	return row;
// }

function handleCountChange(e, itemDetail) {
	e.preventDefault();

	let input = e.target;
	let unidadVenta = productsInCart[itemDetail].unidadVenta || "Unidad";
	let rawValue = input.value;

	let parsedCount = parseFloat(rawValue);
	// let minCount = (unidadVenta === 'Peso' || unidadVenta === 'Litro') ? 0.001 : 1;
	let step = (unidadVenta === 'Peso' || unidadVenta === 'Litro') ? 0.1 : 1;

	// Establecer dinámicamente el step del input
	input.step = step;

	// No hacemos validaciones fuertes aquí, solo actualizamos si es número
	if (!isNaN(parsedCount)) {
		productsInCart[itemDetail]['count'] = parsedCount;
		updateProductsInSession(itemDetail, "count", parsedCount);
		addTotal();
	}
}

function handleCountBlur(e, itemDetail) {
	e.preventDefault();

	let input = e.target;
	let unidadVenta = productsInCart[itemDetail].unidadVenta || "Unidad";
	let rawValue = input.value;

	let parsedCount = parseFloat(rawValue) || 0;
	let minCount = (unidadVenta === 'Peso' || unidadVenta === 'Litro') ? 0.001 : 1;
	
	// Si no es número o es menor al mínimo → corregir
	if (isNaN(parsedCount) || parsedCount < minCount ) {
		parsedCount = minCount;
		input.value = parsedCount;
	}

	// Redondear si es unidad
	if (unidadVenta === 'Unidad') {
		parsedCount = Math.floor(parsedCount);
		input.value = parsedCount;
	}

	productsInCart[itemDetail]['count'] = parsedCount;
	updateProductsInSession(itemDetail, "count", parsedCount);
	addTotal();
}

function handleDiscountChange(e, itemDetail) {
	e.preventDefault();

	let input = e.target;
	let rawValue = input.value;

	let parsedDiscount = parseFloat(rawValue);

	// Validar y corregir
	if (isNaN(parsedDiscount) || parsedDiscount < 0) {
		parsedDiscount = 0;
	} else if (parsedDiscount > 100) {
		parsedDiscount = 100;
	}

	// Redondear a dos decimales (o entero si lo querés así)
	parsedDiscount = parseFloat(parsedDiscount.toFixed(2));
	input.value = parsedDiscount;

	// Actualizar
	productsInCart[itemDetail]['discount'] = parsedDiscount;
	updateProductsInSession(itemDetail, "discount", parsedDiscount);
	addTotal();
}


// function getFormatValue(value){
// 	let formatter = new Intl.NumberFormat('en-US', {
// 		style: 'currency',
// 		currency: 'USD',
// 	});

// 	return formatter.format(value);
// }

//suma todos los importes, ademas si la moneda del producto es distinta a UYU se calcula el importe segun la cotizacion.
// function addTotal(){
// 	let total = 0;
// 	let totalProduct = 0;
// 	// let typeCoinSelected = $('#selectTypeCoin').val(); // UYU
// 	// let valueQuote = quote;
// 	let quantity = 1;
// 	// if(!todayQuote)
// 	//     USD = cotizacion();
//     // console.log(USD)
// 	// if(USD == 1){
// 	// 	cotizacion();
// 	// 	USD = quote;
// 	// }
// 	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
// 	if(responseDesc.result == 2)
// 		discountPercentage = responseDesc.configValue;
// 	// console.log('DESC EN %: ' + discountPercentage )
// 	for (var i = 0; i < productsInCart.length; i++){
// 		if(!productsInCart[i].removed || productsInCart[i].removed == "false"){
// 			if(productsInCart[i].discount == undefined || productsInCart[i].discount == null || productsInCart[i].discount == NaN)
// 				productsInCart[i].discount = 0;
// 			discount = productsInCart[i].discount;
// 			quantity = productsInCart[i].count;
//             // if(productsInCart[i].typeCoin != "UYU"){ // SOLO manejo UYU y USD
// 			// 	console.log(productsInCart[i].amount);
//             //     totalProduct = parseFloat(productsInCart[i].amount) * USD * quantity * ((100 - discount)/ 100);
//             // } else {
// 				if(!discountPercentage || discountPercentage == "NO")
//                 	totalProduct =  parseFloat(productsInCart[i].amount) * quantity - discount;
// 				else if(discountPercentage == "SI")
// 	                totalProduct =  parseFloat(productsInCart[i].amount) * quantity * ((100 - discount)/ 100);
//             // }
// 			// console.log(productsInCart[i].amount)
// 			// console.log(totalProduct)
// 			// totalProduct =  parseFloat(productsInCart[i].amount) * quantity;
// 			total = totalProduct + total;
// 			parseFloat(total).toFixed(2)
// 		}
// 	}
// 	// $('#inputPriceSale').val(getFormatValue(total));
// 	$('#inputPriceSale').val(getFormatValue(total));
// }

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------
// function cotizacion(){
// 	let response = sendPost("getQuote", {typeCoin: "USD", dateQuote: 1});
// 	todayQuote = true;
// 	return parseFloat(response.currentQuote).toFixed(2);
// 	// return response;
// }

// async function cotizacion() {
//     let response = await sendAsyncPost("getQuote", {typeCoin: "USD", dateQuote: 1});
//     todayQuote = true;
//     return parseFloat(response.currentQuote).toFixed(2);
// }
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------

// function openModalAddProduct(){
// 	console.log("openModalAddProduct")

// 	$('#inputCountProduct').val(1);
// 	let response = sendPost("getConfiguration", {nameConfiguration: "PERMITIR_PRODUCTOS_NO_INGRESADOS"});
// 	if(response.result == 2){
// 		if (response.configValue == "SI"){
// 			var x = elementsNoRemoved();
// 			if(x < 80){

// 				clearModalDetail();
// 				$('#inputDescriptionProduct').val(""); //se limpia el buscador

// 				sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"}) // (CREO) busca si la empresa tiene IVA por defecto y lo pone, de lo contrario coloca el 22% por defecto
// 				.then(( response )=>{
// 					//console.log(response);
// 					if ( response.result == 2 ){
// 						$('#inputTaxProduct').val( response.configValue );
// 					}else{
// 						$('#inputTaxProduct').val( 3 );
// 					}
// 				})

// 				$('#modalAddProduct').modal();

// 			}else showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
// 		}
// 	}
// }
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------

// function openModalGetPrices(){
// 	console.log("openModalGetPrices")
// 	$('#inputTextToSearchPrice').val("");
// 	let valueToSearch = $('#inputTextToSearchPrice').val();
// 	let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
// 	$('#tbodyListPrice').empty();
// 	if(response.result == 2){
// 		let list = response.listResult;
// 		firstRow = true;
// 		for (var i = 0; i < list.length; i++) {
// 			let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda);
// 			$('#tbodyListPrice').append(row);
// 			if(firstRow){
// 				$('#tbodyListPrice tr:first').addClass('selected')
// 				firstRow = false
// 			}
// 		}
// 		$('#modalListPrice').off('shown.bs.modal').on('shown.bs.modal', function () {
// 			$('#inputTextToSearchPrice').prop( "readOnly", false );
// 			$('#inputTextToSearchPrice').focus();
// 		});
// 		$('#modalListPrice').modal("show");
// 	}
// }

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------


//agrega un nuevo producto al carro de compra
// function addToCar(element, idProduct){ //detalle, discount, iva, ivaValue, costo, coeficiente, moneda
// 	console.log("addToCar");
// 	mostrarLoader(true)
// 	console.log(element)
// 	$("#tbodyListPrice button").attr('disabled', "true");
// 	// $(element).attr('disabled', 'true')
// 	$('#modalListPrice').modal("hide")
// 	// $(element).off('click');
// 	// let response = sendPost('getProductById', { idProduct: idProduct});
// 	// if(response.result == 2){

// 	sendAsyncPost("getProductById", { idProduct: idProduct })
// 		.then(( response )=>{
// 			mostrarLoader(false)
// 			$('#modalListPrice').modal('hide');
// 			let newProduct = response.objectResult;
// 			// console.log(newProduct);
// 			if(newProduct.moneda == "USD")
// 				newProduct.importe = calculeQuote(newProduct.importe, USD, newProduct.moneda, "UYU");
// 			$('#inputDescriptionProduct').val(newProduct.descripcion);
// 			$('#inputDetailProduct').val(newProduct.detalle);
// 			$('#inputCountProduct').val(1)
// 			$('#inputDiscountProduct').val(newProduct.descuento);
// 			$('#inputTaxProduct').val(newProduct.idIva);
// 			$('#inputPriceProduct').val(newProduct.importe);
// 			$('#inputUnidadVentaProduct').val(newProduct.unidad_venta);
// 			idProductSelected = idProduct;
// 			calculateInverseByCost();
// 			insertNewDetail();
// 			$("#tbodyListPrice button").attr('disabled', "false");
// 			// $(element).on('click', function() {
// 			// 	addToCar(element, idProduct);
// 			// });
// 		})
// 		.catch(function(response){
// 			mostrarLoader(false)
// 			showReplyMessage(response.result, response.message, "Agregar artículo", "modalListPrice");
// 			$("#tbodyListPrice button").attr('disabled', "false");

// 			console.log("este es el catch", response);
// 		});
// 	// } else {
// 		// mostrarLoader(false)

// 		// showReplyMessage(response.result, response.message, "Agregar artículo", "modalListPrice");
// 		// $("#tbodyListPrice button").attr('disabled', "false");
// 		// $(element).on('click', function() {
// 		// 	addToCar(element, idProduct);
// 		// });

// 	// }
// }

// function elementsNoRemoved (){ // Devuelve la cantidad de productos en el carro
// 	count = 0
// 	for (var i = productsInCart.length - 1; i >= 0; i--) {
// 		if (productsInCart[i].removed == "false" || productsInCart[i].removed == false){
// 			count++;
// 		}
// 	}
// 	return count;
// }

// function clearModalDetail(){ // Limpia el modal de agregar producto
// 	// $('#inputTextToSearchDetail').val("");
// 	$('#inputDetailProduct').val("");
// 	$('#inputPriceProduct').val("");
// 	$('#inputDiscountProduct').val("");
// 	$('#inputSubtotalProduct').val(0);
// 	$('#inputUnidadVentaProduct').val('Unidad');
// 	//HERE
// 	let allIndicatorsInvoice = [];

// 	$("#inputTaxProduct option").each(function(){
// 		allIndicatorsInvoice.push($(this).val());
// 	});
// 	$('#inputTaxProduct').val( allIndicatorsInvoice[0] );// se agrega un impuesto por defecto para que se muestre en caso de que el impuesto del producto ingresado no esté habilitado
// 	$('#inputIVAProduct').val(0);
// 	$('#inputTotalProduct').val(0);
// }

//calcula precio sin iva, subtotal, importe, y el valor del iva a partir de cost, coefficient, iva, discount
//esta funcion se usa en el modal de agregar articulos
// function calculateInverseByCost(){ // FALTA EL TEMA DE EL COEFFICIENT 
// 	let count = $('#inputCountProduct').val() || 0;
// 	let price = $('#inputPriceProduct').val() || 0;
// 	let discount = $('#inputDiscountProduct').val() || 0;
// 	let subtotal = $('#inputSubtotalProduct');
// 	let total = $('#inputTotalProduct');
// 	let taxSelected = $('#inputTaxProduct option:selected').attr('name');
// 	let valueIVA = $('#inputIVAProduct');
// 	count = parseFloat(count);
// 	price = parseFloat(price);
// 	discount = parseFloat(discount);

// 	// if(!discountPercentage){
// 	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
// 	if(responseDesc.result == 2)
// 		discountPercentage = responseDesc.configValue;
// 	// }
// 	// if(!includeIva){
// 	let responseIVA = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
// 	if(responseIVA.result == 2)
// 		includeIva = responseIVA.configValue;
// 	// }


// 	if(discountPercentage == "SI"){
// 		if(discount > 0)
// 			discount = ((price * count) * discount)/100;
// 	}

// 	if(includeIva == "SI"){
// 		x = parseFloat(((price * count) - discount) - ((price * count) - discount) / (1+(parseFloat(taxSelected)/100))).toFixed(2)
// 		y = parseFloat(((price * count) - discount) / (1+(parseFloat(taxSelected)/100))).toFixed(2)// importe / (1+(iva/100))
// 		z = parseFloat(((price * count) - discount)).toFixed(2)
// 	}else{
// 		x = parseFloat(((price * count) - discount)*(parseFloat(taxSelected)/100)).toFixed(2);
// 		y = parseFloat(((price * count) - discount)).toFixed(2)
// 		z = (parseFloat(x) + parseFloat(y)).toFixed(2);
// 	}
// 	valueIVA.val(x);
// 	subtotal.val(y);
// 	total.val(z);
// }