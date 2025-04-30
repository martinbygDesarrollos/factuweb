////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			VARIABLES Y FUNCIONES SIN NOMBRE

var quote = 1; // Cotizacion
var USD = 1; // Valor del dolar
var todayQuote = false; // Si se consulto hoy
var idProductSelected = 0; 
// var arrayDetails=[]; //en este array se guardan todos los productos que se agregan al carro, los items de este array son productos y tienen todos los datos que tiene el producto en la base
var productsInCart=[]; // array de productos en el carro, cada item tiene todos los datos que tiene el producto en la base
var totalToShow = 0;
var indexDetail = 0;

var includeIva = null; // Configuracion traida desde la BD
var discountPercentage = null; // Configuracion traida desde la BD

var btnAddDetailClickNumber = 0;

// Variable para almacenar el timestamp de la última ejecución
let lastExecutionTime = 0;
const MIN_EXECUTION_INTERVAL = 3000;

var config_value = null;
var headingval = null;

$('#inputTaxProduct').change(function(){
	calculateInverseByCost();
})

$('#inputCountProduct').change(function(){
	calculateInverseByCost();
})

$('#inputPriceProduct').change(function(){
	calculateInverseByCost();
})

$('#inputDiscountProduct').change(function(){
	calculateInverseByCost();
});

// $('#selectTypeVoucher').on('focus', function() {
// 	$(this).addClass('focus-animation');
// });

// $('#selectTypeVoucher').on('blur', function() {
// 	$(this).removeClass('focus-animation');
// });

$('#modalAddProduct').off('shown.bs.modal').on('shown.bs.modal', function () {
	if(!includeIva){
		let response = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
		if(response.result == 2)
			includeIva = response.configValue;
	}
	if(!discountPercentage){
		let response = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
		if(response.result == 2)
			discountPercentage = response.configValue;
	}
	if(includeIva && includeIva == "SI")
		$('#titleModalCreateModifyService').text("Agregar artículo (IVA incluido)");
	else if (includeIva == "NO" || includeIva == null)
		$('#titleModalCreateModifyService').text("Agregar artículo");

	$('#inputDescriptionProduct').focus();
});

// $('#modalListPrice').on('shown.bs.modal', function () {
// 	console.log("Modal abierto LIST PRICES")
// 	$('#inputTextToSearchPrice').focus();
// });

$('#modalSetClient').off('hidden.bs.modal').on('hidden.bs.modal', function () {
	// console.log("modalSetClient.onHidden")
    // Check if the modalConfirm modal is open
    // if ($('#modalConfirm').hasClass('show')) {
	// 	console.log("Modal setClient cerrado pero Confirm esta abierto")
	// 	// console.log("Modal setClient Cerrado")
	// 	// $('#modalConfirmButtonSI').focus();
	// } else {
		// console.log("Modal setClient cerrado")
		setNextStep('selectTypeVoucher')
		// $('#selectTypeVoucher').focus();
	// }
});

// $('#modalSetPayments').off('hidden.bs.modal').on('hidden.bs.modal', function () {
// 	console.log("Modal setPayments cerrado")
// 	$('#selectTypeVoucher').focus();
// });

// function keyPressAddPaymentMethod(keyPress, value){
// 	if(keyPress.keyCode == 13 && !keyPress.shiftKey){
// 		if(keyPress.srcElement.id == "modalInsertNewPaymentMethodAmount")
// 			$('#modalInsertNewPaymentMethodButtonConfirm').on('click', function(){
// 		})
// 	}
// 	else if(keyPress.keyCode == 13 && keyPress.shiftKey){
// 		if(keyPress.srcElement.id == "modalInsertNewPaymentMethodAmount")
// 			$('#modalInsertNewPaymentMethodOptions').trigger('focus');
// 	}
// 	// if(value == null || value.length == 0) {
// 	// 	return false;
// 	// }
// }
function keyPressAddDetail(keyPress, value, size){
	if(keyPress.keyCode == 13 && !keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputCountProduct")
			$('#inputDescriptionProduct').focus();
		else if(keyPress.srcElement.id =="inputDescriptionProduct")
			$('#inputDiscountProduct').focus();
		else if(keyPress.srcElement.id =="inputDiscountProduct")
			$('#inputPriceProduct').focus();
		else if(keyPress.srcElement.id =="inputPriceProduct")
			$('#inputDetailProduct').focus();
		else if(keyPress.srcElement.id =="inputDetailProduct")
			$('#btnConfirmAddDetail').click(); 
	}
	else if(keyPress.keyCode == 13 && keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputDetailProduct")
			$('#inputPriceProduct').focus();
		else if(keyPress.srcElement.id =="inputPriceProduct")
			$('#inputDiscountProduct').focus();
		else if(keyPress.srcElement.id =="inputDiscountProduct")
			$('#inputDescriptionProduct').focus();
		else if(keyPress.srcElement.id =="inputDescriptionProduct")
			$('#inputCountProduct').focus();
	}
	if(value != null && value.length == size) {
		return false;
	}
}

// $('#modalInsertNewPaymentMethod').off('shown.bs.modal').on('shown.bs.modal', function () {
// 	console.log("Modal new payment method abierto")
// 	calculateRemainingAmount("modalInsertNewPaymentMethodAmount")
// 	// $('#').val();
// 	$('#modalInsertNewPaymentMethodOptions').trigger('focus');
// });

// $('#modalInsertNewPaymentMethod').off('hidden.bs.modal').on('hidden.bs.modal', function () {
// 	console.log("Modal new payment method cerrado")
// 	$('#modalSetPayments').modal();
// });

// $('#modalInsertNewPaymentMethodButtonConfirm').on('click', function(){ // Confirmacion de nuevo modo de pago añadido
// 	let selectedOption = $('#modalInsertNewPaymentMethodOptions').val();
// 	let amount = $('#modalInsertNewPaymentMethodAmount').val();
// 	newRowPaymentMethod(selectedOption, amount);
// 	$('#modalInsertNewPaymentMethod').modal('hide');
// })
function insertPaymentMethod(){
	// calculateRemainingAmount("modalInsertNewPaymentMethodAmount")
	let selectedOption = $('#modalInsertNewPaymentMethodOptions').val();
	let amount = $('#modalInsertNewPaymentMethodAmount').val();
	newRowPaymentMethod(selectedOption, amount);
	// $('#modalInsertNewPaymentMethod').modal('hide');
	$('#modalInsertNewPaymentMethod').off('hidden.bs.modal').on('hidden.bs.modal', function () {
		// $('#modalSetPayments').modal();
		$('#modalSetPayments').off('shown.bs.modal').on('shown.bs.modal', function () {
			// $('#inputPriceSale2').val($('#inputPriceSale').val())
			$('#modalSetPaymentsbtnConfirmSale').trigger('focus')
			calculateRemainingAmount('inputPriceSale22');
		}).modal();
	}).modal('hide');
}
function newRowPaymentMethod(method, amount){	
	console.log("newRowPaymentMethod")	
    // Creating a new row with the selected payment method and amount
    let newRow = $('<div class="row mt-1" style="align-items: center;"></div>');

    let col8 = $('<div class="col-6 pr-1"></div>');
    let pTag = $('<p class="mt-0 mb-0 form-control" style="font-size: .875rem;">' + method + '</p>');
    col8.append(pTag);

    let colAuto1 = $('<div class="col pl-1 pr-1"></div>');
    let input = $('<input type="number" value="' + amount + '" style="font-size: .875rem;" class="form-control text-center" onchange="calculateRemainingAmount(\'inputPriceSale22\')">');
    colAuto1.append(input);

    let colAuto2 = $('<div class="col-1 pl-1" style="max-width: 54px; min-width: 54px;"></div>');
    let btnDelete = $('<button onclick="deletePaymentMethod(this)" class="btn btn-warning p-1 pl-2 pr-2"> <i class="fas fa-trash-alt"></i> </button>');
    colAuto2.append(btnDelete);

    newRow.append(col8, colAuto1, colAuto2);

    $('#containerPayments').append(newRow);
}

function insertNewPayment(){
	console.log("inserNewPayment")
	calculateRemainingAmount("modalInsertNewPaymentMethodAmount")
	$('#modalSetPayments').off('hidden.bs.modal').on('hidden.bs.modal', function () {
		// $('#modalInsertNewPaymentMethod').modal();
		$('#modalInsertNewPaymentMethod').off('shown.bs.modal').on('shown.bs.modal', function () {
			$('#modalSetPayments').off('hidden.bs.modal')
			$('#modalInsertNewPaymentMethodOptions').trigger('focus');
		}).modal();
	}).modal('hide');

	// $('#modalSetPayments').modal('hide');
	// setTimeout(function() {
	// 	$('#modalInsertNewPaymentMethod').modal('show');
    // }, 150); // Delay of 0 milliseconds to ensure it's executed after current execution stack
	// $('#modalSetPayments').modal('hide', function(){
	// 	console.log("Modal setPayments cerrado");
	// 	$('#modalInsertNewPaymentMethod').modal('show');
	// 	// $('#selectTypeVoucher').focus();
	// });
	// $('#modalInsertNewPaymentMethod').modal();
}

function deletePaymentMethod(element){
	console.log("deletePaymentMethod")
	console.log(element)
	const rowDiv = element.closest('div.row');
    if (rowDiv) {
        rowDiv.remove();
    }
	calculateRemainingAmount('inputPriceSale22')
}
// Suma todos los payments methods y setea el faltante
function calculateRemainingAmount(campo){
	console.log("calculateRemainingAmount")
	total = $('#inputPriceSale').val() || 0
	total = parseFloat(total.replace(/[$,]/g, ''))
	totalPayments = 0
	// Get all input values within containerPayments and sum them
    $('#containerPayments input[type="number"]').each(function() {
        totalPayments += parseFloat($(this).val()) || 0;
    });
	console.log(total)
	console.log(totalPayments)
	$('#' + campo).val(parseFloat(total - parseFloat(totalPayments).toFixed(2)).toFixed(2))
	// $('#inputPriceSale2').val(total) inputPriceSale22
	$('#inputPriceSale22').trigger('change')
}

// $('#modalSetPayments').off('shown.bs.modal').on('shown.bs.modal', function () {
// 	$('#inputPriceSale2').val($('#inputPriceSale').val())
// 	calculateRemainingAmount('inputPriceSale22');
// });


//se llama esta funciòn cuando se ingresa texto para buscar un nuevo producto.
//no uso la moneda seleccionada en la factura para buscar los productos porque si se seleccionan productos en dolares se convierte el precio a pesos en la funcion calculateInverseByCost
function getSuggestionDetail(inputToSearch){
	let idIvaDefault = config_value;
	if ( !idIvaDefault ){
		sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"})
		.then(( response )=>{
			if ( response.result == 2 ){
				//console.log("----- se obtuvo respuesta", response);
				idIvaDefault = response.configValue;
				$('#inputTaxProduct').val(idIvaDefault);
			}
			else console.log( response );
		})
	}

	let valueToSearch = inputToSearch.value;
	let response = "";

	if(valueToSearch.length == 0){
		$("#listDetail").empty();
		idProductSelected = 0;
		clearModalDetail();
	}
	else if (valueToSearch.length >= 3){
		response = 	sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
		//console.log(response);
	}

	$("#listDetail").empty();

	if(response.result == 2){
		let list = response.listResult; //los productos encontrados
		let option = null;
		idProductSelected = 0;
		for (let i = list.length - 1; i >= 0; i--) {//si hay más de una coincidencia
			option = getOption(list[i].idArticulo, list[i].descripcion);
			$("#listDetail").append(option); //select de articulos por descripcion
			if(list[i].descripcion == inputToSearch.value){
				setTimeout(function(){
					if(list[i].descripcion == inputToSearch.value){
						idProductSelected = list[i].idArticulo;
						selectListItem(list[i]);
					}
				}, 250);
			}
			else{
				idProductSelected = 0;
				clearModalDetail();
			//console.log("1 - iva encontrado es "+idIvaDefault+" se setea al valor por defecto");

				$('#inputTaxProduct').val(idIvaDefault);
			}
		}
		if(inputToSearch.value == ""){
			idProductSelected = 0;
			clearModalDetail();
			//console.log("2 - iva encontrado es "+idIvaDefault+" se setea al valor por defecto");
			$('#inputTaxProduct').val(idIvaDefault);
		}
	}else{
		idProductSelected = 0;
		clearModalDetail();
		//console.log("3 - iva encontrado es "+idIvaDefault+" se setea al valor por defecto");

		$('#inputTaxProduct').val(idIvaDefault);
	}
}

function getOption(idArticulo, descripcion){
	return "<option id='"+ idArticulo +"' onclick='selectListItem("+descripcion+")' value='"+ descripcion +"''></option>"
}

function selectListItem(itemSelected){
	addValuesModalDetail(itemSelected);
}

function addValuesModalDetail(articulo){
	// console.log("ARTICULO");
	// console.log(articulo);
	// console.log("END ARTICULO");
	let allIndicatorsInvoice = [];
	$('#inputDetailProduct').val(articulo.detalle);
	$('#inputDiscountProduct').val(articulo.descuento);

	$("#inputTaxProduct option").each(function(){
		allIndicatorsInvoice.push($(this).val());
	});
	$('#inputTaxProduct').val( allIndicatorsInvoice[0] );// se agrega un impuesto por defecto para que se muestre en caso de que el impuesto del producto ingresado no esté habilitado
	for (var i = 0; i < allIndicatorsInvoice.length; i++) {
		if(allIndicatorsInvoice[i] == articulo.idIva){
			$('#inputTaxProduct').val(articulo.idIva);
		}
	}

	// let moneyToConvert = $('#selectTypeCoin').val();

	let ivaincluido = includeIva;
	// let ivaincluido = $('#checkboxConfigIvaIncluido').val();
	/*let response = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
	if (response.result == 2){
		ivaincluido = response.configValue;
	}*/


	let precioUnitario = articulo.importe;
	if ( ivaincluido === "NO" ){ // SI el IVA no esta incluido el precio unitario es el costo. y el importe final es costo + IVA (Al parecer en la BD no se esta usando la ganancia VER VER VER)
		precioUnitario = articulo.costo;
		if ( precioUnitario < 1 ){
			precioUnitario = calcularCostoPorImporte(articulo.importe, articulo.idIva);
		}
	}
	else
		precioUnitario = articulo.importe;



	if(articulo.moneda != "UYU"){ // Porque solo manejo USD y UYU
		if(!todayQuote)
			USD = cotizacion();
		// let USD = cotizacion();
		precioUnitario = calculeQuote(articulo.importe, USD, articulo.moneda, "UYU");
	}
	$('#inputPriceProduct').val(precioUnitario);
	calculateInverseByCost();
}

function calcularCostoPorImporte(importe, idiva){
	let percent = 0;
	switch (idiva) {
	  case 2:
	    percent = 1.1;
	    break;
	  case 3:
	  	percent = 1.22;
	  	break;
	  default:
	    return importe;break;
	}
	return (importe / percent).toFixed(2);
}

//se confirma y se agrega un nuevo articulo a la tabla de facturacion
function insertNewDetail(){

	// Obtener el tiempo actual
    const currentTime = Date.now();

	// Verificar si ha pasado suficiente tiempo desde la última ejecución
    if (currentTime - lastExecutionTime < MIN_EXECUTION_INTERVAL) {
        console.log("Demasiado rápido, espera un momento...");
        return; // Salir de la función sin hacer nada
    }
    
    // Actualizar el timestamp de la última ejecución
    lastExecutionTime = currentTime;

	// console.log("insertNewDetail")
	// btnAddDetailClickNumber++;//esto está porque cuando se daba enter varias veces seguidas en el confirmar de agregar un nuevo producto, se terminaba agregando muchas veces
	// if(btnAddDetailClickNumber == 1){

		if( productsInCart.length == 0){
			insertNewDetailProcess();
			// btnAddDetailClickNumber = 0;
		}
		else if (productsInCart.length > 0){
			const product = productsInCart.find(prod => prod.idArticulo == idProductSelected && ( prod.removed == false || prod.removed == "false"));

			if ( product && ( product.removed == false || product.removed == "false") && idProductSelected != 0) { //significa que el producto que se quiere ingresar ya se encuentra en la lista, entonces solo se aumenta la cantidad y no se agrega otra fila por ese producto
				let newCount = $('#inputCountProduct').val();
				let position = product.idDetail -1;
				product.count = parseInt(product.count) + parseInt(newCount);
				$('#inputCount' + position).val(product.count);
				addTotal();
				// $('#selectTypeCoin').prop( "disabled", true );
				$('#modalAddProduct').modal('hide');
				// btnAddDetailClickNumber = 0;
				updateProductsInSession(position, "count", product.count);
			}else{
				insertNewDetailProcess();
				// btnAddDetailClickNumber = 0;
			}
		}
	// }
	// else{
	// 	// btnAddDetailClickNumber = 0;
	// }
}

function updateProductsInSession(product, indexProduct, data){
	updateDataSession(product, indexProduct, data);
}

function insertNewDetailProcess(){
	// console.log("insertNewDetailProcess")

	let description = $('#inputDescriptionProduct').val() || null;
	let detail = $('#inputDetailProduct').val() || null;
	let count = $('#inputCountProduct').val() || null;
	let price = $('#inputPriceProduct').val() || null;
	let discount = $('#inputDiscountProduct').val() || null;
	let idIva = $('#inputTaxProduct').val() || null;
	let total = $('#inputTotalProduct').val() || null;
	let ivaValue = $('#inputTaxProduct option:selected').attr('name');

	if(total < 0){
		showReplyMessage(1, "El importe no puede ser negativo.", "Importe no válido", "modalAddProduct");
		return;
	}
	if(description){
		if(count && count >= 1){
			if(price && price > 0){
				// btnAddDetailClickNumber = 0;
				indexDetail++;
				createDetailArray(count); // productsInCart es creado
				let row = createDetailRow(indexDetail, description, detail, count, price, discount, idIva, ivaValue, price);
				addTotal();
				$('#tbodyDetailProducts').prepend(row);

				$('#tbodyDetailProducts tr').removeClass('selected')
                           .first()
                           .addClass('selected');

				// $('#selectTypeCoin').prop( "disabled", true );
				// $('#checkboxConfigIvaIncluido').prop( "disabled", true );
				$('#modalAddProduct').modal('hide');
			}else showReplyMessage(1, "El precio no puede ser ingresado vacio o cero para el articulo que intenta agregar", "Precio no valido", "modalAddProduct");
		}else showReplyMessage(1, "La cantidad no puede ingresarse vacia o menor a 1 para el articulo que intenta agregar", "Cantidad no valida", "modalAddProduct");
	}else showReplyMessage(1, "Debe ingresar el nombre para el articulo que intenta agregar.", "Nombre requerido", "modalAddProduct");
}

function createDetailArray(cant){
	let count = cant || 1;
	let ivaValue = $('#inputTaxProduct option:selected').attr('name');
	// let total = $('#inputTotalProduct').val() || null;
	includeIva = null;
	let response = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
		if(response.result == 2)
			includeIva = response.configValue;
	let total = 0;
	if (includeIva == "SI")
		total = $('#inputPriceProduct').val() || null;
	else if(includeIva == "NO")
		total = $('#inputTotalProduct').val() || null;
	// total = total / count;
	let itemDetail = null;
	let description = $('#inputDescriptionProduct').val() || null;
	let detail = $('#inputDetailProduct').val() || null;
	let discount = parseFloat($('#inputDiscountProduct').val() || 0).toFixed(2);
	let idIva = $('#inputTaxProduct').val() || null;
	let idEmpresa = 0;//traer el id que se usa en la funcion
	let idInventary = null;
	let brand = "";
	let cost = 0;
	let coefficient = 0;
	let idHeading = 0;
	let coin = "UYU" || null;
	let price = $('#inputPriceProduct').val() || null;

	if(idProductSelected != 0){
		let response = sendPost('getProductById', { idProduct: idProductSelected});

		if(response.result == 2){
			let product = response.objectResult;
			idEmpresa = product.idEmpresa;
			idInventary = product.idInventario;
			brand = product.marca;
			cost = product.costo;
			coefficient = product.coeficiente;
			idHeading = product.idRubro;
			coin = product.moneda;
		}
	}

	itemDetail = {
		idDetail: indexDetail,
		idArticulo: idProductSelected,
		idHeading: idHeading,//usar la funcion que te devuelve el id del rubro
		idInventary: idInventary,
		idBusiness: idEmpresa,
		brand: brand,
		typeCoin: coin,
		cost: cost,//calcular el costo
		coefficient: coefficient, //calcular el coeficiente
		price: price,
		amount: total,//importe es el precio unitario por la cantidad de productos ingresados
		description: description,
		detail: detail,
		count: count,
		discount: discount,
		idIva: idIva,
		ivaValue: ivaValue,
		removed: false
	};

	productsInCart.push(itemDetail);
	// document.cookie = 'TYPECOIN='+$('#selectTypeCoin').val();
	responseSaveProduct = sendPost("saveProductsInSession", {product: itemDetail});
	if (responseSaveProduct.result == 2){
		console.log("CAMBIO NUMERO DE CLICKS")
		// btnAddDetailClickNumber = 0;
	}
	// console.log(productsInCart)
}

function getObjectProductsInCart(trItem){
	for (let i = 0; i < productsInCart.length; i++) {
		if(productsInCart[i].idDetail == trItem)
			return i;
	}
}

function addProductByCodeBar(barcode){ // LA CANTIDAD DE ARTICULOS CON LIMITE EN 80 es de distintos? u 80 del mismo articulo tambien es el el limite? VER VER VER

	var x = elementsNoRemoved();
	// console.log(x);
	if(x < 80){
		let data = null;
		let newBarcode = barcode;
		let newCantidad = 1;

		if (barcode.includes("*")) {
			data = barcode.split("*");
			data[0] > 0 ? newCantidad = data[0] : newCantidad = 1;
			newBarcode = data[1];
		}

		let response = sendPost('addProductByCodeBar', {barcode: newBarcode});
		if(response.result == 2){
			$('#tbodyListPrice').empty();
			if( response.listResult.length > 1 ){
				let list = response.listResult;
				firstRow = true;
				for (var i = 0; i < list.length; i++) {
					// console.log(list[i].descripcion + " = " + list[i].codigoBarra)
					let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda);
					$('#tbodyListPrice').append(row);
					if(firstRow){
						$('#tbodyListPrice tr:first').addClass('selected')
						firstRow = false
					}
				}


				$('#modalListPrice .modal-title').text("Seleccionar producto");
				$('#inputTextToSearchPrice').val("");
				$('#inputTextToSearchPrice').prop( "readOnly", true );
				$('#modalListPrice').off('shown.bs.modal').on('shown.bs.modal', function () {
					// console.log("addProductByCodeBar")
					$('#inputTextToSearchPrice').focus();
				});
				$('#modalListPrice').modal('show');
			}
			else if( response.listResult.length == 1 ){
				objeto = response.listResult[0];
				addValuesModalDetail(objeto);
				// console.log(objeto);
				idProductSelected = objeto.idArticulo
				$('#modalListPrice').modal('hide');
				$('#modalDeleteDetail').modal('hide');
				$('#modalSeeVoucher').modal('hide');
				$('#modalSetClient').modal('hide');
				$('#modalAddProduct').modal('hide');
				let product = objeto;
				$('#inputDescriptionProduct').val(product.descripcion);
				$('#inputDetailProduct').val(product.detalle);
				$('#inputCountProduct').val(newCantidad)
				// let moneyToConvert = $('#selectTypeCoin').val();
				let precioUnitario = product.importe;
				if(product.moneda != "UYU"){
					// if(quote == 1){
					if(!todayQuote)
						cotizacion();
							//     USD = cotizacion();
					// }
					precioUnitario = calculeQuote(product.importe, USD, product.moneda, "UYU");
				}
				$('#inputPriceProduct').val(precioUnitario);
				$('#inputTaxProduct').val(product.idIva);
				$('#inputDiscountProduct').val(product.descuento);
				calculateInverseByCost();
				insertNewDetail();
			}
		}
	}
	else if(productsInCart.length == 80 || productsInCart.length > 80){
		showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
	}
}

function setClientFinal(){
	// console.log('Set cliente final');
	$('#inputTextToSearchClient').val("");
	// $('#buttonModalClientWithName').html("Agregar <u>C</u>liente <i class='fas fa-user-plus'></i>");
	$("#selectTypeVoucher").empty();
	$("#selectTypeVoucher").append('<option value="201">ETicket Contado</option>');
	$("#selectTypeVoucher").append('<option value="211">ETicket Crédito</option>');
	$("#selectTypeVoucher").prop("selectedIndex", 0);
	$('#buttonModalClientWithName').html("Consumidor final");
	// $("#selectTypeVoucher").focus();
}

function modalBorrarDetail(trItem){
	let position = getObjectProductsInCart(trItem);
	// let disabledTypeCoin = false;
	// let disabledIva = false;
	$('#textDeleteDetail').html("¿Desea eliminar '"+ productsInCart[position].description +"'?");
	
	$('#modalDeleteDetail').off('shown.bs.modal').on('shown.bs.modal', function () {
		$('#btnConfirmDeleteDetail').focus();
	});

	$('#modalDeleteDetail').modal();
	$('#btnConfirmDeleteDetail').off('click');
	$('#btnConfirmDeleteDetail').click(function(){
		productsInCart[position]["removed"] = true;
		//modificar en la sesion el producto removido
		updateProductsInSession(position, "removed", true);
		$('#' + trItem).addClass("removedElement");
		addTotal();
		for (let i = 0; i < productsInCart.length; i++) {
			if ( !productsInCart[i].removed || productsInCart[i].removed == "false"){
				// disabledTypeCoin = true;
				// disabledIva = true;
				break;
			}
		}
		// $('#selectTypeCoin').prop( "disabled", disabledTypeCoin );
		// $('#checkboxConfigIvaIncluido').prop( "disabled", disabledIva );
		$('#modalDeleteDetail').modal('hide');
	});
}

async function discardSalesProducts (){
	mostrarLoader(true)
	// $('#progressbar').modal();
	// progressBarIdProcess = loadPrograssBar();
	await removeAllElementsArrayDetail()
	.then((response) => {
		mostrarLoader(false)
		// $('#progressbar h5').text("Descartando productos...");
		// $('#progressbar').modal("hide");
		// stopPrograssBar(progressBarIdProcess);
		// document.cookie = "TYPECOIN=UYU";
		// $('#selectTypeCoin').val("UYU");
		$('#inputQuote').val("");
		$('#containerQuote').css("visibility", "hidden");
		addTotal();
		window.location.reload();
	})
	.catch((error) => {
		$('#progressbar').modal("hide");
		stopPrograssBar(progressBarIdProcess);
	})
}

async function removeAllElementsArrayDetail(){

	for (var i = 0; i < productsInCart.length; i++) {
		if(productsInCart[i].removed == false || productsInCart[i].removed == "false"){
			productsInCart[i].removed = "true";
		}
	}
	return new Promise(resolve => {
		resolve(removedAllProducts());
	});
}

function nextStep(){
    if($('#idButtonShowModalPayment').hasClass('d-none')){ // Primer Ctrl + Fin en siguiente (Agregar cliente y comprobante)
		if(productsInCart.filter(item => 
			item.removed !== true && 
			item.removed !== "true"
			).length > 0){

				// alert("darle a siguiente");
				$('#idButtonShowModalPayment').removeClass('d-none');
				$('#nextStep').addClass('d-none');
				$('#modalSetClient').modal({
					backdrop: 'static'
				});
				$('#clientSelection').removeClass('d-none'); // Seccion del cliente
		} else {
			showReplyMessage(1, "Ningun producto ingresado", "Productos requeridos", null);
		}

    } else {
		if($('#nextStep').hasClass('d-none')){ //Segundo Ctrl + Fin en siguiente (Continuar a medios de pagos)
			// alert("darle a siguiente");
			$('#idButtonShowModalPayment').removeClass('d-none'); // por las dudas
			$('#nextStep').addClass('d-none'); // por las dudas
			if(!$('#containerInfoCredito').hasClass("d-none")){ // Si se seleccionó CREDITO ya deberia generar el CFE
				console.log("OPCION CREDITO")
				console.log('CREAR FACTURA')
				createNewFactura()
			} else {
				console.log("OPCION CONTADO")
				$('#modalSetPayments').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
        // $('#discardSales').click(); 
        // $('#idButtonShowModalPayment').addClass('d-none');
    }
}

function switchExpirationDate(element){
	const expirationDateInput = document.getElementById("inputDateExpirationVoucher");
	if (element.checked) {
        // Checkbox is checked
        console.log("Checkbox is checked");
		expirationDateInput.disabled = true;
        // Add your code for when the checkbox is checked
    } else {
        // Checkbox is unchecked
        console.log("Checkbox is unchecked");
		expirationDateInput.disabled = false;
        // Add your code for when the checkbox is unchecked
    }
}
// 	// $('#step-2').removeClass('d-none'); // Seccion del cliente

function showModalPayment(){
	$('#modalSetPayments').modal({
		backdrop: 'static',
		keyboard: false
	});
}

function superFastSale(){
	const countNonRemovedItems = (items) => {
		return items.filter(item => 
			item.removed !== true && // checks for boolean true
			item.removed !== "true" // checks for string "true"
		).length;
	};

	if (countNonRemovedItems(productsInCart) <= 0){
		showReplyMessage(1, "Ningun producto ingresado", "Producto requerido", null);
		return;
	}

	let dateVoucher = getCurrentDate();
	let adenda = $('#inputAdenda').val() || null; // ADENDA
	let idBuy = $('#inputIdBuy').val() || null; // NO SE, NULL
	let amount = null; // TOTAL
	let discountPercentage = null
	
	let total = 0;
	let totalProduct = 0;
	let quantity = 1;
	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
	if(responseDesc.result == 2)
		discountPercentage = responseDesc.configValue;
	// console.log('DESC EN %: ' + discountPercentage )
	for (var i = 0; i < productsInCart.length; i++){
		if(!productsInCart[i].removed || productsInCart[i].removed == "false"){
			if(productsInCart[i].discount == undefined || productsInCart[i].discount == null || productsInCart[i].discount == NaN)
				productsInCart[i].discount = 0;
			discount = productsInCart[i].discount;
			quantity = productsInCart[i].count;
				if(!discountPercentage || discountPercentage == "NO")
                	totalProduct =  parseFloat(productsInCart[i].amount) * quantity - discount;
				else if(discountPercentage == "SI")
	                totalProduct =  parseFloat(productsInCart[i].amount) * quantity * ((100 - discount)/ 100);
			// console.log(productsInCart[i].amount)
			// console.log(totalProduct)
			total = totalProduct + total;
			parseFloat(total).toFixed(2)
		}
	}
	amount = parseFloat(total).toFixed(2);

	if(responseDesc.result == 2)
		discountPercentage = responseDesc.configValue;
	let mediosPago = [{
		codigo: 1,
		glosa: "Efectivo",
		valor: parseFloat(total).toFixed(2)
	}]
	// console.log(mediosPago)

	newArrayToInvoice = prepareToCreateNewFactura(productsInCart);
	// console.log(newArrayToInvoice)
	if(newArrayToInvoice.length != 0){
		for (var i = newArrayToInvoice.length - 1; i >= 0; i--) {
			if(newArrayToInvoice[i].idArticulo == 0){ //significa que es un articulo nuevo por crear
				console.log(createNewProduct(newArrayToInvoice[i], null)); // Creo un producto nuevo sin rubro
			}
		}
		let consumidorFinal = [];
		// consumidorFinal[0] = ({
		// 	document: null,
		// 	name: null,
		// 	address: null,
		// 	city: null,
		// 	department: null,
		// 	email: null,
		// 	phone: null
		// });

		let data = {
			client: JSON.stringify(consumidorFinal),
			typeVoucher: 101, // 101/111
			typeCoin: "UYU",
			shapePayment: 1,
			dateVoucher: dateVoucher,
			adenda: adenda,
			idBuy: idBuy,
			detail: JSON.stringify(newArrayToInvoice),
			amount: amount, // ESTO SE ENVIA AL PEDO
			discountTipo: discountPercentage == "SI" ? 2 : 1,
			mediosPago: JSON.stringify(mediosPago)
		};
		mostrarLoader(true)
		sendAsyncPost("createNewVoucher", data)
		.then(function(response){
			mostrarLoader(false)
			// console.log(response)
			if (response.result == 2 ){
				let responseVoucher = sendPost("getLastVoucherEmitted");
				if (responseVoucher.result == 2) {
					let data = {id:responseVoucher.objectResult.id}
					openModalVoucher(data, "CLIENT", "sale");
				}
				prepareToNewSale();
				removeAllElementsArrayDetail();
			} else {
				// console.log(response.message)
				prepareToNewSale();
				removeAllElementsArrayDetail();
				updateVouchersById();
				showReplyMessage(response.result, response.message, "Nueva factura", null);
			}
		})
		.catch(function(response){
			mostrarLoader(false)
			console.log("este es el catch", response);
		});
	} else {
		showReplyMessage(1, "Ningun producto cargado.", "Producto requerido", null);
	}
}

//VL:si se ingresaron artìculos que no se encuentran guardados, se guardan
//si se agregan artìculos y se identifican cambios en los datos del artìculo se actualiza en la base
function createNewFactura(){ // VER VER VER
	console.log("Create New Factura")
	// document.getElementById("idButtonCreateNewFactura").disabled=true;
	// document.getElementById("idButtonCreateNewFactura").innerText = "Confirmando...";
	let dateVoucher = $('#inputDateVoucher').val() || null; // FECHA DEL COMPROBANTE
	let typeVoucher = $('#selectTypeVoucher').val() || null; // EFactura Contado/ETicket Contado / EFactura Credito/ETicket Credito
	let tipoCod = typeVoucher == 211 || typeVoucher == 201 ? 101 : 111
	
	let mediosPago = null
	if(typeVoucher != 211 && typeVoucher != 311){ // NO es ninguno de los CREDITOS
		mediosPago = extractPaymentMethods()
		console.log(mediosPago)
		if(mediosPago.length == 0){ // NO HAY MEDIO DE PAGO INGRESADO
			showReplyMessage(1, "Debe ingresar un medio de pago", "Medio de pago requerido", "modalSetPayments");
			return;
		}
	}
	
	// let shapePayment = $('#selectShapePayment').val() || null; 
	// let typeCoin = $('#selectTypeCoin').val() || null; //selectTypeCoin es el tipo de moneda que se utiliza para crear la nueva factura 
	let dateExpiration = $('#inputDateExpirationVoucher').val() || null; // FECHA DE EXPIRACION DEL COMPROBANTE
	let adenda = $('#inputAdenda').val() || null; // ADENDA
	let amount = $('#inputPriceSale').val().replace(/[$,]/g, '') || null; // TOTAL
	let idBuy = $('#inputIdBuy').val() || null; // NO SE, NULL
	let newArrayToInvoice = null; 
	let discountPercentage = null
	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
	if(responseDesc.result == 2)
		discountPercentage = responseDesc.configValue;

	// newArrayToInvoice = prepareToCreateNewFactura(arrayDetails);
	newArrayToInvoice = prepareToCreateNewFactura(productsInCart);
	console.log(newArrayToInvoice)
	
	if(newArrayToInvoice.length != 0){
		if(dateVoucher){
			if(typeVoucher == 211 || typeVoucher == 311){ // los 2 CREDITOS
				let isChecked = $('#inputNotUseExpirationDate').is(':checked'); // Check de sin vencimiento
				if(!isChecked){
					if(!dateExpiration){
						showReplyMessage(1, "Debe ingresar una fecha de vencimiento para comprobante a crédito, de lo contrario seleccione 'Sin vencimiento'", "Fecha vencimiento", null);
						return;
					}
				}
			}
			for (var i = newArrayToInvoice.length - 1; i >= 0; i--) {
				if(newArrayToInvoice[i].idArticulo == 0){ //significa que es un articulo nuevo por crear
					// if ( headingval ){
						console.log(createNewProduct(newArrayToInvoice[i], null)); // Creo un producto nuevo sin rubro
					// }
				}else{ // NO VA A ACTUALIZAR EL PRODUCTO NI SU INVENTARIO ACA, SE HARA AL FINALIZAR EL CFE
					// if ( typeCoin == newArrayToInvoice[i].typeCoin){
					// 	//console.log("se va a actualizar el producto porque las monedas son iguales, sino no se actualiza");
					// console.log(updateProduct(newArrayToInvoice[i])); // Updateo el inventario de un producto
					// }
				}
			}
			// let data = {
			// 	client: JSON.stringify(clientSelected),
			// 	typeVoucher: tipoCod, // 101/111
			// 	typeCoin: "UYU",
			// 	shapePayment: (typeVoucher == 211 || typeVoucher == 311) ? 2 : 1,
			// 	dateVoucher: dateVoucher,
			// 	// dateExpiration: dateExpiration,
			// 	dateExpiration: ((typeVoucher == 211 || typeVoucher == 311) && $('#inputNotUseExpirationDate').is(':checked')) ? null : dateExpiration,
			// 	adenda: adenda,
			// 	idBuy: idBuy,
			// 	detail: JSON.stringify(newArrayToInvoice),
			// 	amount: amount
			// }
			let data = {
				client: JSON.stringify(clientSelected),
				typeVoucher: tipoCod, // 101/111
				typeCoin: "UYU",
				shapePayment: (typeVoucher == 211 || typeVoucher == 311) ? 2 : 1,
				dateVoucher: dateVoucher,
				adenda: adenda,
				idBuy: idBuy,
				detail: JSON.stringify(newArrayToInvoice),
				amount: amount, // ESTO SE ENVIA AL PEDO
				discountTipo: discountPercentage == "SI" ? 2 : 1,
				mediosPago: JSON.stringify(mediosPago)
			};
			
			// Add dateExpiration only if conditions are met
			if ((typeVoucher == 211 || typeVoucher == 311) && !$('#inputNotUseExpirationDate').is(':checked')) {
				data.dateExpiration = dateExpiration;
			}
			$('#modalSetPayments').modal('hide');
			mostrarLoader(true)
			sendAsyncPost("createNewVoucher", data)
			.then(function(response){
				mostrarLoader(false)
				console.log(response) // ACA ACA ACA ACA ACA
				if (response.result == 2 ){
					let responseVoucher = sendPost("getLastVoucherEmitted");
					if (responseVoucher.result == 2) {
						// if(typeVoucher != 211 && typeVoucher != 311)// NO ES CREDITO (ESTA ABIERTO EL MODAL DE MEDIOS DE PAGO)
						// 	$('#modalSetPayments').modal('hide');
						let data = {id:responseVoucher.objectResult.id}
						openModalVoucher(data, "CLIENT", "sale");
					}
					prepareToNewSale();
					removeAllElementsArrayDetail();
				} else {
					console.log(response.message)
					if ( response.message == "El comprobante fue emitido correctamente pero un error no permitio traerlo al sistema. Actualice los comprobantes almacenados para obtenerlo." ){
						prepareToNewSale();
						removeAllElementsArrayDetail();
						//ruta para cargar todos los comprobantes en la base local
						updateVouchersById();
					} else {
					// 	// document.getElementById("idButtonCreateNewFactura").innerText = "Confirmar";
					// 	// document.getElementById("idButtonCreateNewFactura").disabled=false;
					}
					// updateVouchersById();
					// $('#modalSetPayments').modal('hide');
					showReplyMessage(response.result, response.message, "Nueva factura", null);
				}
			})
			.catch(function(response){
				mostrarLoader(false)
				console.log("este es el catch", response);
			});
		}else showReplyMessage(1, "Debe seleccionar una fecha para el comprobante que quiere emitir.", "Fecha requerida", null);
	} else {
		if(typeVoucher == 211 || typeVoucher == 311) // CREDITO
			showReplyMessage(1, "Ningun producto cargado.", "Producto requerido", null);
		else
			showReplyMessage(1, "Ningun producto cargado.", "Producto requerido", "modalSetPayments");
	}
}

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
			
		default:
			respuesta = 11
			break;
	}
	return respuesta;
}

function prepareToNewSale(){
	console.log("prepareToNewSale")
	cancelClientSelected();
	$('#tbodyDetailProducts').empty();
	$('#inputDateVoucher').val(getCurrentDate());
	$('#selectTypeVoucher').val(101);
	$('#selectShapePayment').val(1);
	$('#selectTypeCoin').val("UYU");
	$('#selectTypeCoin').prop( "disabled", false );
	$('#checkboxConfigIvaIncluido').prop( "disabled", false );
	$('#inputPriceSale').val(parseFloat(0).toFixed(2));

	let adenda = sendPost("getConfiguration", {nameConfiguration: "ADENDA"});
	let adendaValue = "";
	if(adenda.result == 2)
		adendaValue = adenda.configValue;
	$('#inputAdenda').val(adendaValue);

	let responseShowClient = sendPost("getConfiguration", {nameConfiguration: "SKIP_SELECT_CLIENTE"});
	if(responseShowClient.result == 2){
		if(responseShowClient.configValue == "SI"){
			$('#clientSelection').removeClass('d-none')
		} else {
			$('#clientSelection').addClass('d-none')
		}
	}


	// Select all rows that contain input elements within the containerPayments div
	let $allPaymentsWay = $('#containerPayments .row:has(input)');
  
	// Remove the selected rows
	$allPaymentsWay.remove();
	
	let responseSkipClient = sendPost("getConfiguration", {nameConfiguration: "SKIP_SELECT_CLIENTE"});
	if(responseSkipClient.result == 2){
		if(responseSkipClient.configValue == "SI"){
			setClientFinal()
			setNextStep('selectTypeVoucher')
		} else {
			setNextStep('selectClient')
		}
	}
	// setNextStep('selectClient')
	// $('#idButtonShowModalPayment').addClass('d-none')
	// $('#nextStep').removeClass('d-none');

	// document.getElementById("idButtonCreateNewFactura").innerText = "Confirmar";
	// document.getElementById("idButtonCreateNewFactura").disabled=false;
}

function confirmSale(){
	// console.log("confirmSale")
	$('#confirmSaleBtn').click();
}

function setNextStep(step){ // le settea el siguiente paso al boton de confirmar
	// console.log("setNextStep: " + step)
	switch (step) {
		case 'selectClient':
			$('#confirmSaleBtn').off('click').on('click', function () {
				$('#modalSetClient').modal({
					backdrop: 'static'
				});
			});
			break;
		case 'selectPaymentWay':
			$('#confirmSaleBtn').off('click').on('click', function () {
				// setClientFinal()
				let typeVoucher = $('#selectTypeVoucher').val() || null; // EFactura Contado/ETicket Contado / EFactura Credito/ETicket Credito
				if(typeVoucher == 211 || typeVoucher == 311){
					createNewFactura()
					return;
				}
				$('#inputPriceSale2').val($('#inputPriceSale').val())
				calculateRemainingAmount('inputPriceSale22');
				$('#modalSetPayments').off('shown.bs.modal').on('shown.bs.modal', function () {
					$('#modalSetPaymentsbtnConfirmSale').trigger('focus')
				});
				showModalPayment()
			});
			break;
		case 'selectTypeVoucher':
			$('#clientSelection').removeClass('d-none')
			$('#confirmSaleBtn').off('click').on('click', function () {
				if($('#buttonModalClientWithName').text() == "Consumidor final"){
					setClientFinal()
				}
				$('#selectTypeVoucher').focus()
				setNextStep('selectPaymentWay')
			});
			break;
		default:
			break;
	}
}

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

function onChangeTypeVoucher(selectTypeVoucher){ 
	console.log("event onchange de selectTypeVoucher");
	if(selectTypeVoucher.value == 211 || selectTypeVoucher.value == 311){ // CREDITOS
		$('#containerInfoCredito').removeClass("d-none");
		// document.getElementById("inputDateExpirationVoucher").valueAsDate = new Date();
		let futureDate = new Date();
		futureDate.setDate(futureDate.getDate() + 30);
		document.getElementById("inputDateExpirationVoucher").valueAsDate = futureDate;
		// $('#selectShapePayment').val(2).change();
		// $('#containerInputIdBuy').addClass("show");
		
		// $('#containerInputIdBuy').removeAttr("hidden");
		
	} else {
		$('#containerInfoCredito').removeClass("d-none");
		$('#containerInfoCredito').addClass("d-none");
		$('#selectShapePayment').val(1).change();

		// $('#containerInputIdBuy').removeClass("show");
		// $('#containerInputIdBuy').addClass("fade");

		// $('#containerInputIdBuy').attr("hidden", true);
	}
}

function loadProductsInSession (){
	let productsInSession = getDataSession("arrayProductsSales"); // ManagerDataSession.js
	productsInCart = productsInSession.data;
	indexDetail = productsInCart.length;
	if ( productsInSession.result == 2 ){
		insertAllElementsInDetail();
	}
}

//se confirma y se agrega un nuevo articulo a la tabla de facturacion
function insertAllElementsInDetail(){ // En este punto de venta se vende en UYU y NO se toca el IVA, si tiene IVA se vende con él sino no
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
		let row = createDetailRow(productsInCart[i].idDetail, productsInCart[i].description, productsInCart[i].detail, productsInCart[i].count, productsInCart[i].price, productsInCart[i].discount, productsInCart[i].idIva, productsInCart[i].ivaValue, productsInCart[i].price);
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
function createDetailRow(indexDetail, name, description, count, price, discount, idIva, ivaValue, total){
	let row = "<tr id='" + indexDetail +"' >";

	row += "<td class='col-6 text-left overflow-example' title='"+ name +"'>"+ name;
	if(description){
		row += "<br>";
		row += "<p class='overflow-example' style='margin-bottom: 0;'> " + description + " </p>"
	//</br>row += "<td class='text-left overflow-example' title='"+ description +"'>"+ description +"</td>";

	} else {
		row += "</td>";
	// 	row += "<td class='text-left'></td>";
	}
	row += "<td class='col-1 text-left align-middle '><button class='btn btn-danger btn-sm shadow-sm align-middle' style='width: 3em;' onclick='modalBorrarDetail(" + indexDetail + ")'><i class='fas fa-trash-alt'></i></button></td>";
	row += "<td class='col-2 text-right align-middle'><input id='inputCount"+ (indexDetail -1) +"' type='number' min=1 class='form-control form-control-sm text-right' value='"+ count + "' onchange='changeItemDetail("+ (indexDetail -1) +")' onkeyup='this.onchange()'></td>";
	row += "<td class='col-2 text-right align-middle'><input id='inputDiscount"+ (indexDetail -1) +"' type='number' min=0 max=100 class='form-control form-control-sm text-right' value='"+ parseFloat(discount).toFixed(2) + "' onchange='changeItemDetail("+ (indexDetail -1) +")' onkeyup='this.onchange()'></td>";
	row += "<td class='col-1 text-right align-middle'>"+ getFormatValue(total) +"</td>";
	row += "</tr>";

	return row;
}

//cuando se modifica la cantidad del producto en el detalle de producto que estàn agregados al carro
function changeItemDetail(itemDetail){
	console.log(productsInCart);
	
	// let total = 0;
	// let totalProduct = 0;
	// let valueQuote = quote;
	if(!discountPercentage){
		let response = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
		if(response.result == 2)
			discountPercentage = response.configValue;
	}
	if(discountPercentage == "SI"){
		if($('#inputDiscount' + itemDetail).val() > 100)
			$('#inputDiscount' + itemDetail).val(100)
		else if($('#inputDiscount' + itemDetail).val() < 0)
			$('#inputDiscount' + itemDetail).val(0)
	}
	
	let newCount = $('#inputCount' + itemDetail).val() || 1;
	let newDiscount = $('#inputDiscount' + itemDetail).val() || 0;
	
	productsInCart[itemDetail]['discount'] = newDiscount;
	productsInCart[itemDetail]['count'] = newCount;
	updateProductsInSession(itemDetail, "count", newCount);
	updateProductsInSession(itemDetail, "discount", newDiscount);
	
	addTotal();
	console.log(productsInCart);
}

function getFormatValue(value){
	let formatter = new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'USD',
	});

	return formatter.format(value);
}

//suma todos los importes, ademas si la moneda del producto es distinta a UYU se calcula el importe segun la cotizacion.
function addTotal(){
	let total = 0;
	let totalProduct = 0;
	// let typeCoinSelected = $('#selectTypeCoin').val(); // UYU
	// let valueQuote = quote;
	let quantity = 1;
	// if(!todayQuote)
	//     USD = cotizacion();
    // console.log(USD)
	// if(USD == 1){
	// 	cotizacion();
	// 	USD = quote;
	// }
	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
	if(responseDesc.result == 2)
		discountPercentage = responseDesc.configValue;
	// console.log('DESC EN %: ' + discountPercentage )
	for (var i = 0; i < productsInCart.length; i++){
		if(!productsInCart[i].removed || productsInCart[i].removed == "false"){
			if(productsInCart[i].discount == undefined || productsInCart[i].discount == null || productsInCart[i].discount == NaN)
				productsInCart[i].discount = 0;
			discount = productsInCart[i].discount;
			quantity = productsInCart[i].count;
            // if(productsInCart[i].typeCoin != "UYU"){ // SOLO manejo UYU y USD
			// 	console.log(productsInCart[i].amount);
            //     totalProduct = parseFloat(productsInCart[i].amount) * USD * quantity * ((100 - discount)/ 100);
            // } else {
				if(!discountPercentage || discountPercentage == "NO")
                	totalProduct =  parseFloat(productsInCart[i].amount) * quantity - discount;
				else if(discountPercentage == "SI")
	                totalProduct =  parseFloat(productsInCart[i].amount) * quantity * ((100 - discount)/ 100);
            // }
			// console.log(productsInCart[i].amount)
			// console.log(totalProduct)
			// totalProduct =  parseFloat(productsInCart[i].amount) * quantity;
			total = totalProduct + total;
			parseFloat(total).toFixed(2)
		}
	}
	// $('#inputPriceSale').val(getFormatValue(total));
	$('#inputPriceSale').val(getFormatValue(total));
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------
function cotizacion(){
	let response = sendPost("getQuote", {typeCoin: "USD", dateQuote: 1});
	todayQuote = true;
	return parseFloat(response.currentQuote).toFixed(2);
	// return response;
}

// async function cotizacion() {
//     let response = await sendAsyncPost("getQuote", {typeCoin: "USD", dateQuote: 1});
//     todayQuote = true;
//     return parseFloat(response.currentQuote).toFixed(2);
// }
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------

function openModalAddProduct(){

	$('#inputCountProduct').val(1);
	let response = sendPost("getConfiguration", {nameConfiguration: "PERMITIR_PRODUCTOS_NO_INGRESADOS"});
	if(response.result == 2){
		if (response.configValue == "SI"){
			var x = elementsNoRemoved();
			if(x < 80){

				clearModalDetail();
				$('#inputDescriptionProduct').val(""); //se limpia el buscador

				sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"}) // (CREO) busca si la empresa tiene IVA por defecto y lo pone, de lo contrario coloca el 22% por defecto
				.then(( response )=>{
					//console.log(response);
					if ( response.result == 2 ){
						$('#inputTaxProduct').val( response.configValue );
					}else{
						$('#inputTaxProduct').val( 3 );
					}
				})

				$('#modalAddProduct').modal();

			}else showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
		}
	}
}
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------

function openModalGetPrices(){
	console.log("openModalGetPrices")
	$('#inputTextToSearchPrice').val("");
	let valueToSearch = $('#inputTextToSearchPrice').val();
	let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
	$('#tbodyListPrice').empty();
	if(response.result == 2){
		let list = response.listResult;
		firstRow = true;
		for (var i = 0; i < list.length; i++) {
			let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda);
			$('#tbodyListPrice').append(row);
			if(firstRow){
				$('#tbodyListPrice tr:first').addClass('selected')
				firstRow = false
			}
		}
		$('#modalListPrice').off('shown.bs.modal').on('shown.bs.modal', function () {
			$('#inputTextToSearchPrice').prop( "readOnly", false );
			$('#inputTextToSearchPrice').focus();
		});
		$('#modalListPrice').modal("show");
	}
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------
//obtener la lista de articulos usando getSuggestionProductByDescription
function getListPrice(element){
	console.log("getListPrice")
	if($(element).val().length > 0 || $('#inputTextToSearchPrice').prop( "readOnly") == false){
		let valueToSearch = $('#inputTextToSearchPrice').val();
		let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
		$('#tbodyListPrice').empty();
		if(response.result == 2){
			let list = response.listResult;
			firstRow = true;
			for (var i = 0; i < list.length; i++) {
				let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda);
				$('#tbodyListPrice').append(row);
				if(firstRow){
					$('#tbodyListPrice tr:first').addClass('selected')
					firstRow = false
				}
			}
		}
	}
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------



//crar una nueva linea para la tabla que muestra los articulos
function createRowListPrice(idProduct, description, heading, price, coin){
	let row = "<tr>";
	let priceUYU = "";
	let priceUSD = "";

	if(!todayQuote){
		USD = cotizacion();
	}

		// let response = cotizacion();
		// if(response.result == 2){
		// 	quote = parseFloat(response.currentQuote);
		// }
	// todayQuote = false;
	// quote = cotizacion();

	// let moneyToConvert = "";
	if (coin == 'UYU'){
		priceUYU = "<td class='text-center align-middle'> $  "+ price +"</td>";
	}
	else if (coin == 'USD'){
		priceUSD = "<td class='text-center align-middle'> U$S  "+ price +"</td>";
	}

	row += "<td class='text-left align-middle'>"+ description +"</td>";
	row += "<td class='text-left align-middle'>"+ heading +"</td>";
	row += priceUYU;
	row += priceUSD
	row += "<td class='text-center align-middle'><button onclick='addToCar(this, " + idProduct + ")' class='btn btn-sm background-template-color2 text-template-background'><i class='fas fa-cart-plus text-mycolor'></i></button></td>";

	return row;
}

//agrega un nuevo producto al carro de compra
function addToCar(element, idProduct){ //detalle, discount, iva, ivaValue, costo, coeficiente, moneda
	mostrarLoader(true)
	console.log(element)
	$("#tbodyListPrice button").attr('disabled', "true");
	// $(element).attr('disabled', 'true')
	$('#modalListPrice').modal("hide")
	// $(element).off('click');
	// let response = sendPost('getProductById', { idProduct: idProduct});
	// if(response.result == 2){

	sendAsyncPost("getProductById", { idProduct: idProduct })
		.then(( response )=>{
			mostrarLoader(false)
			$('#modalListPrice').modal('hide');
			let newProduct = response.objectResult;
			// console.log(newProduct);
			if(newProduct.moneda == "USD")
				newProduct.importe = calculeQuote(newProduct.importe, USD, newProduct.moneda, "UYU");
			$('#inputDescriptionProduct').val(newProduct.descripcion);
			$('#inputDetailProduct').val(newProduct.detalle);
			$('#inputCountProduct').val(1)
			$('#inputDiscountProduct').val(newProduct.descuento);
			$('#inputTaxProduct').val(newProduct.idIva);
			$('#inputPriceProduct').val(newProduct.importe);
			idProductSelected = idProduct;
			calculateInverseByCost();
			insertNewDetail();
			$("#tbodyListPrice button").attr('disabled', "false");
			// $(element).on('click', function() {
			// 	addToCar(element, idProduct);
			// });
		})
		.catch(function(response){
			mostrarLoader(false)
			showReplyMessage(response.result, response.message, "Agregar artículo", "modalListPrice");
			$("#tbodyListPrice button").attr('disabled', "false");

			console.log("este es el catch", response);
		});
	// } else {
		// mostrarLoader(false)

		// showReplyMessage(response.result, response.message, "Agregar artículo", "modalListPrice");
		// $("#tbodyListPrice button").attr('disabled', "false");
		// $(element).on('click', function() {
		// 	addToCar(element, idProduct);
		// });

	// }
}

function elementsNoRemoved (){ // Devuelve la cantidad de productos en el carro
	count = 0
	for (var i = productsInCart.length - 1; i >= 0; i--) {
		if (productsInCart[i].removed == "false" || productsInCart[i].removed == false){
			count++;
		}
	}
	return count;
}

function clearModalDetail(){ // Limpia el modal de agregar producto
	// $('#inputTextToSearchDetail').val("");
	$('#inputDetailProduct').val("");
	$('#inputPriceProduct').val("");
	$('#inputDiscountProduct').val("");
	$('#inputSubtotalProduct').val(0);

	let allIndicatorsInvoice = [];

	$("#inputTaxProduct option").each(function(){
		allIndicatorsInvoice.push($(this).val());
	});
	$('#inputTaxProduct').val( allIndicatorsInvoice[0] );// se agrega un impuesto por defecto para que se muestre en caso de que el impuesto del producto ingresado no esté habilitado
	$('#inputIVAProduct').val(0);
	$('#inputTotalProduct').val(0);
}

//calcula precio sin iva, subtotal, importe, y el valor del iva a partir de cost, coefficient, iva, discount
//esta funcion se usa en el modal de agregar articulos
function calculateInverseByCost(){ // FALTA EL TEMA DE EL COEFFICIENT 
	let count = $('#inputCountProduct').val() || 0;
	let price = $('#inputPriceProduct').val() || 0;
	let discount = $('#inputDiscountProduct').val() || 0;
	let subtotal = $('#inputSubtotalProduct');
	let total = $('#inputTotalProduct');
	let taxSelected = $('#inputTaxProduct option:selected').attr('name');
	let valueIVA = $('#inputIVAProduct');
	count = parseFloat(count);
	price = parseFloat(price);
	discount = parseFloat(discount);

	// if(!discountPercentage){
	let responseDesc = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
	if(responseDesc.result == 2)
		discountPercentage = responseDesc.configValue;
	// }
	// if(!includeIva){
	let responseIVA = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
	if(responseIVA.result == 2)
		includeIva = responseIVA.configValue;
	// }


	if(discountPercentage == "SI"){
		if(discount > 0)
			discount = ((price * count) * discount)/100;
	}

	if(includeIva == "SI"){
		x = parseFloat(((price * count) - discount) - ((price * count) - discount) / (1+(parseFloat(taxSelected)/100))).toFixed(2)
		y = parseFloat(((price * count) - discount) / (1+(parseFloat(taxSelected)/100))).toFixed(2)// importe / (1+(iva/100))
		z = parseFloat(((price * count) - discount)).toFixed(2)
	}else{
		x = parseFloat(((price * count) - discount)*(parseFloat(taxSelected)/100)).toFixed(2);
		y = parseFloat(((price * count) - discount)).toFixed(2)
		z = (parseFloat(x) + parseFloat(y)).toFixed(2);
	}
	valueIVA.val(x);
	subtotal.val(y);
	total.val(z);
}