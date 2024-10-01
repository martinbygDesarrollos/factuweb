////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			VARIABLES Y FUNCIONES SIN NOMBRE

var initialTyeCoin = null;
var quote = 1;
var idProductSelected = 0;
var arrayDetails=[]; //en este array se guardan todos los productos que se agregan al carro, los items de este array son productos y tienen todos los datos que tiene el producto en la base
var totalToShow = 0;
var indexDetail = 0;
var includeIva = null;
var discountPercentage = null;
var btnAddDetailClickNumber = 0;

var config_value = null;
var headingval = null;


$(document).ready(()=>{
	sendAsyncPost("getHeadingByName", {heading: "Artículos"})
	.then((response)=>{
		if (response.result == 2)
			headingval = response.objectResult.idRubro;
	})

	sendAsyncPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"})
	.then((response)=>{
		if (response.result == 2){
			if (response.configValue == "SI")
				$("#checkboxConfigIvaIncluido").prop("checked",true)
			else if (response.configValue == "NO")
				$("#checkboxConfigIvaIncluido").prop("checked",false)
		}
	})
})


$('#selectTypeCoin').on('click', function(){
	initialTyeCoin = $('#selectTypeCoin').val();
});
$('#modalAddProduct').on('shown.bs.modal', function () {

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

	$('#inputDescriptionProduct').focus();
});
$('#modalAddProduct').on('shown.bs.modal', function(){
	$('#inputTaxProduct option:nth-child(1)').prop("selected", true);
});
$('#inputTextToSearchDetail').focusout(function(){
	let nameDetail = $('#inputTextToSearchDetail').val() || null;
	if(nameDetail)
		selectListItem(nameDetail);
	else
		clearModalDetail();
});

$('#inputTaxProduct').change(function(){
	calculateInverseByCost();
})

$('#inputCountProduct').change(function(){

	calculateInverseByCost();

})

$('#inputPriceProduct').change(function(){
	calculateInverseByCost();})

$('#inputDiscountProduct').change(function(){
	calculateInverseByCost();
});
////////////////////////////////////////////////////////////////////////////////////////////////////////////
function cotizacion(){
	let response = sendPost("getQuote", {typeCoin: "USD", dateQuote: 1});
	quote = parseFloat(response.currentQuote).toFixed(2);
	return response;
}

function getConfigIvaDefault(){
	config_value = 3;

	sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"});
	if(response.result == 2){
		config_value = response.configValue;
	}
}

function onChangeTypeVoucher(selectTypeVoucher){
	if(selectTypeVoucher.value == 111){
		$('#selectShapePayment').val(2).change();

		$('#containerInputIdBuy').removeClass("fade");
		$('#containerInputIdBuy').addClass("show");

		$('#containerInputIdBuy').removeAttr("hidden");

	}else if(selectTypeVoucher.value == 101){
		$('#selectShapePayment').val(1).change();

		$('#containerInputIdBuy').removeClass("show");
		$('#containerInputIdBuy').addClass("fade");

		$('#containerInputIdBuy').attr("hidden", true);
	}
}

function onChangeTypeCoin(selectTypeCoin){
	if(selectTypeCoin.value == "USD"){
		if(quote == 1){
			let response = cotizacion();
			if(response.result == 2){
				quote = parseFloat(response.currentQuote);
			}
		}
		$('#inputQuote').val(quote);
		$('#containerQuote').css("visibility", "visible");
	}else if(selectTypeCoin.value == "UYU"){
		$('#inputQuote').val("");
		$('#containerQuote').css("visibility", "hidden");
	}
}


//obtener la lista de articulos usando getSuggestionProductByDescription
function getListPrice(inputTextProduct){
	let coin = $('#selectTypeCoin').val();
	let valueToSearch = $('#inputTextToSearchPrice').val();
	let response = sendPost("getSuggestionProductByDescription", {textToSearch: valueToSearch});
	$('#tbodyListPrice').empty();
	if(response.result == 2){
		let list = response.listResult;
		for (var i = 0; i < list.length; i++) {
			let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda);
			$('#tbodyListPrice').append(row);
		}
	}
}

//crar una nueva linea para la tabla que muestra los articulos
function createRowListPrice(idProduct, description, heading, price, coin){
	let row = "<tr>";
	let priceUYU = "";
	let priceUSD = "";

	if(quote == 1){
		let response = cotizacion();
		if(response.result == 2){
			quote = parseFloat(response.currentQuote);
		}
	}

	let moneyToConvert = "";
	if (coin == 'UYU'){
		priceUYU = "<td class='text-center'> $  "+ price +"</td>";
	}
	else if (coin == 'USD'){
		priceUSD = "<td class='text-center'> U$S  "+ price +"</td>";
	}

	row += "<td class='text-left'>"+ description +"</td>";
	row += "<td class='text-left'>"+ heading +"</td>";
	row += priceUYU;
	row += priceUSD
	row += "<td class='text-center'><button onclick='addToCar(" + idProduct + ")' class='btn btn-sm background-template-color2 text-template-background'><i class='fas fa-cart-plus text-mycolor'></i></button></td>";

	return row;
}


//agrega un nuevo producto al carro de compra
function addToCar(idProduct){ //detalle, discount, iva, ivaValue, costo, coeficiente, moneda
	let response = sendPost('getProductById', { idProduct: idProduct});
	if(response.result == 2){
		$('#modalListPrice').modal('hide');
		let newProduct = response.objectResult;
		$('#inputDescriptionProduct').val(newProduct.descripcion);
		$('#inputDetailProduct').val(newProduct.detalle);
		$('#inputCountProduct').val(1)
		$('#inputDiscountProduct').val(newProduct.descuento);
		$('#inputTaxProduct').val(newProduct.idIva);
		$('#inputPriceProduct').val(newProduct.importe);
		idProductSelected = idProduct;
		calculateInverseByCost();
		insertNewDetail();
	}else showReplyMessage(response.result, response.message, "Agregar artículo", "modalListPrice");
}

//calcula precio sin iva, subtotal, importe, y el valor del iva a partir de cost, coefficient, iva, discount
//esta funcion se usa en el modal de agregar articulos
function calculateInverseByCost(){
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

	if(!discountPercentage){
		let response = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
		if(response.result == 2)
			discountPercentage = response.configValue;
	}
	if(!includeIva){
		let response = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
		if(response.result == 2)
			includeIva = response.configValue;
	}


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

//VL:si se ingresaron artìculos que no se encuentran guardados, se guardan
//si se agregan artìculos y se identifican cambios en los datos del artìculo se actualiza en la base
function createNewFactura(){

	document.getElementById("idButtonCreateNewFactura").disabled=true;
	document.getElementById("idButtonCreateNewFactura").innerText = "Confirmando...";


	let dateVoucher = $('#inputDateVoucher').val() || null;
	let typeVoucher = $('#selectTypeVoucher').val() || null;
	let shapePayment = $('#selectShapePayment').val() || null;
	let typeCoin = $('#selectTypeCoin').val() || null; //selectTypeCoin es el tipo de moneda que se utiliza para crear la nueva factura
	let dateExpiration = $('#inputDateExpirationVoucher').val() || null;
	let adenda = $('#inputAdenda').val() || null;
	let amount = $('#inputPriceSale').val() || null;
	let idBuy = $('#inputIdBuy').val() || null;
	let newArrayToInvoice = null;

	newArrayToInvoice = prepareToCreateNewFactura(arrayDetails);
	if(newArrayToInvoice.length != 0){
		if(dateVoucher){
			if(shapePayment == 2){
				let isChecked = $('#inputNotUseExpirationDate').is(':checked');
				if(!isChecked){
					if(!dateExpiration){
						showReplyMessage(1, "Debe ingresar una fecha de vencimiento para comprobante a crédito, de lo contrario seleccione 'Sin vencimiento'", "Fecha vencimiento", null);
						return;
					}
				}
			}
			for (var i = newArrayToInvoice.length - 1; i >= 0; i--) {
				if(newArrayToInvoice[i].idArticulo == 0){ //significa que es un articulo nuevo por crear
					if ( headingval ){
						createNewProduct(newArrayToInvoice[i], headingval);
					}
				}else{
					if ( typeCoin == newArrayToInvoice[i].typeCoin){
						//console.log("se va a actualizar el producto porque las monedas son iguales, sino no se actualiza");
						updateProduct(newArrayToInvoice[i]);
					}
				}
			}
			let data = {
				client: JSON.stringify(clientSelected),
				typeVoucher: typeVoucher,
				typeCoin: typeCoin,
				shapePayment: shapePayment,
				dateVoucher: dateVoucher,
				dateExpiration: dateExpiration,
				adenda: adenda,
				idBuy: idBuy,
				detail: JSON.stringify(newArrayToInvoice),
				amount: amount
			}
			sendAsyncPost("createNewVoucher", data)
			.then(function(response){
				if (response.result == 2 ){
					let responseVoucher = sendPost("getLastVoucherEmitted");
					if (responseVoucher.result == 2) {
						let data = {id:responseVoucher.objectResult.id}
						openModalVoucher(data, "CLIENT", "sale");
					}
					prepareToNewSale();
					removeAllElementsArrayDetail();
				}
				else {
					if ( response.message == "El comprobante fue emitido correctamente pero un error no permitio traerlo al sistema. Actualice los comprobantes almacenados para obtenerlo." ){
						prepareToNewSale();
						removeAllElementsArrayDetail();
						//ruta para cargar todos los comprobantes en la base local
						//updateVouchersById();
					}else{
						document.getElementById("idButtonCreateNewFactura").innerText = "Confirmar";
						document.getElementById("idButtonCreateNewFactura").disabled=false;
					}
					updateVouchersById();
					showReplyMessage(response.result, response.message, "Nueva factura", null);
				}
			})
			.catch(function(response){
				console.log("este es el catch", response);
			});
		}else showReplyMessage(1, "Debe seleccionar una fecha para el comprobante que quiere emitir.", "Fecha requerida", null);
	}
}

//esta funcion recibe el array que tiene todos los productos de la lista de compra.
//genera y devuelve un nuevo array solo con los elementos que tienen el campo removed como false
function prepareToCreateNewFactura(originalArray){
	var newArray = [];
	for (var i = 0; i<originalArray.length; i++) {
		if(!originalArray[i].removed || originalArray[i].removed == "false"){
			newArray.push(originalArray[i]);
		}
	}
	return newArray;
}

function onChangeShapePayment(inputCheck){
	if(inputCheck.value == 1)
		$('#containerInfoCredito').css('display', "none");
	else{
		$('#inputDateExpirationVoucher').val(calculateDateExpiration(getCurrentDate(), 30));
		$('#containerInfoCredito').css('display', "block");
	}
}

function prepareToNewSale(){
	cancelClinetSelected();
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

	document.getElementById("idButtonCreateNewFactura").innerText = "Confirmar";
	document.getElementById("idButtonCreateNewFactura").disabled=false;
}

function addProductByCodeBar(barcode){

	var x = elementsNoRemoved();
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
				for (var i = 0; i < list.length; i++) {
					let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, "", list[i].importe, list[i].moneda);
					$('#tbodyListPrice').append(row);
				}
				$('#modalListPrice .modal-title').text("Seleccionar producto");
				$('#modalListPrice input').val("");
				$('#modalListPrice input').prop( "disabled", true );
				$('#modalListPrice').modal('show');
			}
			else if( response.listResult.length == 1 ){
				objeto = response.listResult[0];
				addValuesModalDetail(objeto);
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
				let moneyToConvert = $('#selectTypeCoin').val();
				let precioUnitario = product.importe;
				if(product.moneda != moneyToConvert){
					if(quote == 1){
						cotizacion();
					}
					precioUnitario = calculeQuote(product.importe, quote, product.moneda, moneyToConvert);
				}
				$('#inputPriceProduct').val(precioUnitario);
				$('#inputTaxProduct').val(product.idIva);
				$('#inputDiscountProduct').val(product.descuento);
				calculateInverseByCost();
				insertNewDetail();
			}
		}
	}
	else if(arrayDetails.length == 80 || arrayDetails.length > 80){
		showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
	}
}

//se confirma y se agrega un nuevo articulo a la tabla de facturacion
function insertNewDetail(){
	btnAddDetailClickNumber++;//esto está porque cuando se daba enter varias veces seguidas en el confirmar de agregar un nuevo producto, se terminaba agregando muchas veces
	if(btnAddDetailClickNumber == 1){

		if( arrayDetails.length == 0){
			insertNewDetailProcess();
			btnAddDetailClickNumber = 0;
		}
		else if (arrayDetails.length > 0){
			const product = arrayDetails.find(prod => prod.idArticulo == idProductSelected && ( prod.removed == false || prod.removed == "false"));

			if ( product && ( product.removed == false || product.removed == "false") && idProductSelected != 0) { //significa que el producto que se quiere ingresar ya se encuentra en la lista, entonces solo se aumenta la cantidad y no se agrega otra fila por ese producto
				let newCount = $('#inputCountProduct').val();
				let position = product.idDetail -1;
				product.count = parseInt(product.count) + parseInt(newCount);
				$('#inputCount' + position).val(product.count);
				addTotal();
				$('#selectTypeCoin').prop( "disabled", true );
				$('#modalAddProduct').modal('hide');
				btnAddDetailClickNumber = 0;
				updateProductsInSession(position, "count", product.count);
			}else{
				insertNewDetailProcess();
				btnAddDetailClickNumber = 0;
			}
		}
	}
	else{
		btnAddDetailClickNumber = 0;
	}
}

function insertNewDetailProcess(){
	let description = $('#inputDescriptionProduct').val() || null;
	let detail = $('#inputDetailProduct').val() || null;
	let count = $('#inputCountProduct').val() || null;
	let price = $('#inputPriceProduct').val() || null;
	let discount = $('#inputDiscountProduct').val() || null;
	let idIva = $('#inputTaxProduct').val() || null;
	let total = $('#inputTotalProduct').val() || null;
	let ivaValue = $('#inputTaxProduct option:selected').attr('name');

	if(description){
		if(count || count < 1){
			if(price || price < 1){
				btnAddDetailClickNumber = 0;
				indexDetail++;
				createDetailArray(count);
				let row = createDetailRow(indexDetail, description, detail, count, price, discount, idIva, ivaValue, price);
				addTotal();
				$('#tbodyDetailProducts').prepend(row);
				$('#selectTypeCoin').prop( "disabled", true );
				$('#checkboxConfigIvaIncluido').prop( "disabled", true );
				$('#modalAddProduct').modal('hide');
			}else showReplyMessage(1, "El precio no puede ser ingresado vacio o menor a 1 para el articulo que intenta agregar", "Precio no valido", "modalAddProduct");
		}else showReplyMessage(1, "La cantidad no puede ingresarse vacia o menor a 1 para el articulo que intenta agregar", "Cantidad no valida", "modalAddProduct");
	}else showReplyMessage(1, "Debe ingresar el nombre del para el articulo que intenta agregar.", "Nombre requerido", "modalAddProduct");
}

//suma todos los importes, ademas si la moneda del producto es distinta a la moneda de selectTypeCoin el tipo de moneda de la factura se calcula el importe segun la cotizacion.
function addTotal(){
	let total = 0;
	let totalProduct = 0;
	let typeCoinSelected = $('#selectTypeCoin').val();
	let valueQuote = quote;
	let quantity = 1;

	if(quote == 1){
		cotizacion();
		valueQuote = quote;
	}
	for (var i = 0; i < arrayDetails.length; i++){
		if(!arrayDetails[i].removed || arrayDetails[i].removed == "false"){
			quantity = arrayDetails[i].count;
			totalProduct =  parseFloat(arrayDetails[i].amount) * quantity;
			total = totalProduct + total;
			parseFloat(total).toFixed(2)
		}
	}
	$('#inputPriceSale').val(getFormatValue(total));
}
function createDetailArray(cant){
	let count = cant || 1;
	let ivaValue = $('#inputTaxProduct option:selected').attr('name');
	let total = $('#inputTotalProduct').val() || null;
	total = total /count;
	let itemDetail = null;
	let description = $('#inputDescriptionProduct').val() || null;
	let detail = $('#inputDetailProduct').val() || null;
	let discount = parseFloat($('#inputDiscountProduct').val() || null).toFixed(2);
	let idIva = $('#inputTaxProduct').val() || null;
	let idEmpresa = 0;//traer el id que se usa en la funcion
	let idInventary = null;
	let brand = "";
	let cost = 0;
	let coefficient = 0;
	let idHeading = 0;
	let coin = $('#selectTypeCoin').val() || null;
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

	arrayDetails.push(itemDetail);
	document.cookie = 'TYPECOIN='+$('#selectTypeCoin').val();
	sendPost("saveProductsInSession", {product: itemDetail});
}

//crea las lineas que se muestran en la tabla luego de agregarse los productos
function createDetailRow(indexDetail, name, description, count, price, discount, idIva, ivaValue, total){
	let row = "<tr id='" + indexDetail +"' >";

	row += "<td class='text-left'>"+ name +"</td>";
	if(description)
		row += "<td class='text-left'>"+ description +"</td>";
	else
		row += "<td class='text-left'></td>";
	row += "<td class='text-left'><button class='btn btn-danger btn-sm shadow-sm' style='width: 3em;' onclick='modalBorrarDetail(" + indexDetail + ")'><i class='fas fa-trash-alt'></i></button></td>";
	row += "<td class='text-right'><input id='inputCount"+ (indexDetail -1) +"' type='number' min=1 class='form-control form-control-sm text-right' value='"+ count + "' onchange='changeItemDetail("+ (indexDetail -1) +")' onkeyup='this.onchange()'></td>";
	row += "<td class='text-right'>"+ getFormatValue(total) +"</td>";
	row += "</tr>";

	return row;
}

//cuando se modifica la cantidad del producto en el detalle de producto que estàn agregados al carro
function changeItemDetail(itemDetail){
	let newCount = $('#inputCount' + itemDetail).val() || 1;
	let total = 0;
	let totalProduct = 0;
	let valueQuote = quote;

	arrayDetails[itemDetail]['count'] = newCount;
	updateProductsInSession(itemDetail, "count", newCount);

	addTotal();
}

function getObjectArrayDetail(trItem){
	for (let i = 0; i < arrayDetails.length; i++) {
		if(arrayDetails[i].idDetail == trItem)
			return i;
	}
}

function modalBorrarDetail(trItem){
	let position = getObjectArrayDetail(trItem);
	let disabledTypeCoin = false;
	let disabledIva = false;
	$('#textDeleteDetail').html("¿Desea eliminar '"+ arrayDetails[position].description +"'?");
	$('#modalDeleteDetail').modal();
	$('#btnConfirmDeleteDetail').off('click');
	$('#btnConfirmDeleteDetail').click(function(){
		arrayDetails[position]["removed"] = true;
		//modificar en la sesion el producto removido
		updateProductsInSession(position, "removed", true);
		$('#' + trItem).addClass("removedElement");
		addTotal();
		for (let i = 0; i < arrayDetails.length; i++) {
			if ( !arrayDetails[i].removed || arrayDetails[i].removed == "false"){
				disabledTypeCoin = true;
				disabledIva = true;
				break;
			}
		}
		$('#selectTypeCoin').prop( "disabled", disabledTypeCoin );
		$('#checkboxConfigIvaIncluido').prop( "disabled", disabledIva );
		$('#modalDeleteDetail').modal('hide');
	});
}

function getFormatValue(value){
	let formatter = new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'USD',
	});

	return formatter.format(value);
}

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

function openModalAddProduct(){

	$('#inputCountProduct').val(1);
	let response = sendPost("getConfiguration", {nameConfiguration: "PERMITIR_PRODUCTOS_NO_INGRESADOS"});
	if(response.result == 2){
		if (response.configValue == "SI"){
			var x = elementsNoRemoved();
			if(x < 80){

				clearModalDetail();
				$('#inputDescriptionProduct').val(""); //se limpia el buscadors

				sendAsyncPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"})
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

function openModalGetPrices(){
	$('#inputTextToSearchPrice').val("");
	$('#inputTextToSearchPrice').focus();
	$('#modalListPrice input').prop( "disabled", false );
	getListPrice($('#inputTextToSearchPrice'));
	$('#modalListPrice').modal("show");
}

function getOption(idArticulo, descripcion){
	return "<option id='"+ idArticulo +"' onclick='selectListItem("+descripcion+")' value='"+ descripcion +"''></option>"
}

function selectListItem(itemSelected){
	addValuesModalDetail(itemSelected);
}

function addValuesModalDetail(articulo){
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

	let moneyToConvert = $('#selectTypeCoin').val();

	let ivaincluido = $('#checkboxConfigIvaIncluido').val();
	/*let response = sendPost("getConfiguration", {nameConfiguration: "IVA_INCLUIDO"});
	if (response.result == 2){
		ivaincluido = response.configValue;
	}*/


	let precioUnitario = articulo.importe;
	if ( ivaincluido === "NO" ){
		precioUnitario = articulo.costo;
		if ( precioUnitario < 1 ){
			precioUnitario = calcularCostoPorImporte(articulo.importe, articulo.idIva);
		}
	}
	else
		precioUnitario = articulo.importe;



	if(articulo.moneda != moneyToConvert){
		if(quote == 1){
			cotizacion();
		}
		precioUnitario = calculeQuote(articulo.importe, quote, articulo.moneda, moneyToConvert);
	}
	$('#inputPriceProduct').val(precioUnitario);
	calculateInverseByCost();
}

function clearModalDetail(){
	$('#inputTextToSearchDetail').val("");
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

function clearInputDescription(){
	$('#inputDescriptionProduct').val("");
}

function keyUpCalcultateValues(){
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

	if(discountPercentage == "SI"){
		discount = ((price * count) * discount)/100;
	}

	if(includeIva == "SI"){
		IVAProduct = (((price * count) - discount) * parseFloat(taxSelected))/100;
		SubtotalProduct = ((price * count) - discount) - parseFloat(IVAProduct);
		TotalProduct = ((price * count) - discount);
	}else{
		IVAProduct = (((price * count) - discount) * parseFloat(taxSelected))/100;
		SubtotalProduct = ((price * count) - discount);
		TotalProduct = (parseFloat(SubtotalProduct || 0) + parseFloat(IVAProduct || 0));
	}

	$('#inputTotalProduct').val(parseFloat(TotalProduct).toFixed(2)); //donde se muestra el importe
	$('#inputIVAProduct').val(parseFloat(IVAProduct).toFixed(2)); //el valor del iva
	$('#inputSubtotalProduct').val(parseFloat(SubtotalProduct).toFixed(2)); //donde se muestra el subtotal
}

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

function createNewProduct(producto, heading){
	if(producto.description && producto.description.length > 4){
		if( producto.amount > 0 ){
			let data = {
				idHeading: heading,
				idIva: producto.idIva,
				description: producto.description,
				detail: producto.detail,
				brand: producto.brand,
				typeCoin: producto.typeCoin,
				cost: producto.cost,
				coefficient: producto.coefficient,
				amount: producto.amount,
				barcode: null,
				discount: producto.discount,
				inventory: producto.idInventary,
				minInventory: 0, //mìnima cantidad de articulos en stock
			}
			let response = sendPost('insertProduct',data);
			if(response.result != 2){
			}
			return response;
		}
	}

}

function updateProduct(articulo){

	let response = sendPost('getProductById', { idProduct: articulo.idArticulo});
	let producto = response.objectResult;

	let idProduct = articulo.idArticulo
	let idHeading = producto.idRubro
	let idIva = producto.idIva
	let description = articulo.description
	let detail = articulo.detail
	let brand = producto.marca
	let typeCoin = articulo.typeCoin
	let cost = producto.costo
	let coefficient = producto.coeficiente
	let amount = producto.importe
	let barcode = producto.codigoBarra
	let discount = articulo.discount


	if(articulo.description && articulo.description.length > 4){
		if( amount > 0  ){
			let data = {
				idProduct: idProduct,
				idHeading: idHeading,
				idIva: idIva,
				description: description,
				detail: detail,
				brand: brand,
				typeCoin: typeCoin,
				cost: cost,
				coefficient: coefficient,
				amount: amount,
				barcode: barcode,
				discount: discount
			}
			let response = sendPost("updateProduct", data);
			return response;
		}
	}
}

function loadProductsInSession (){
	let productsInSession = getDataSession("arrayProductsSales");
	arrayDetails = productsInSession.data;
	indexDetail = arrayDetails.length;
	if ( productsInSession.result == 2 ){
		insertAllElementsInDetail();
	}
}

//se confirma y se agrega un nuevo articulo a la tabla de facturacion
function insertAllElementsInDetail(){
	let disabledTypeCoin = null;
	let disabledIva = null;
	let selectTypeCoinValue = null;
	if (arrayDetails.length > 0){
		let cookieData = document.cookie.split("; ");
		for (var i = 0; i < cookieData.length; i++) {
			if (cookieData[i].includes("TYPECOIN")){
				selectTypeCoinValue = cookieData[i].split("=");
				selectTypeCoinValue.value = selectTypeCoinValue[1];
			}
		}
		disabledTypeCoin = false;
		disabledIva = false;
		$('#selectTypeCoin').val(selectTypeCoinValue.value)
		onChangeTypeCoin(selectTypeCoinValue.value);
	}

	for(var i = 0; i < arrayDetails.length; i++) {
		let row = createDetailRow(arrayDetails[i].idDetail, arrayDetails[i].description, arrayDetails[i].detail, arrayDetails[i].count, arrayDetails[i].price, arrayDetails[i].discount, arrayDetails[i].idIva, arrayDetails[i].ivaValue, arrayDetails[i].price);
		$('#tbodyDetailProducts').prepend(row);
		if( arrayDetails[i].removed == "true" ){
			$('#' + arrayDetails[i].idDetail).addClass("removedElement");
		}
		if (arrayDetails[i].removed == "false"){
			disabledTypeCoin = true;
			disabledIva = true;
		}
	}
	addTotal();
	$('#selectTypeCoin').prop( "disabled", disabledTypeCoin );
	$('#checkboxConfigIvaIncluido').prop( "disabled", disabledIva);
	$('#modalAddProduct').modal('hide');
}

function updateProductsInSession(product, indexProduct, data){
	updateDataSession(product, indexProduct, data);
}

async function discardSalesProducts (){
	$('#progressbar').modal();
	progressBarIdProcess = loadPrograssBar();
	await removeAllElementsArrayDetail()
	.then((response) => {
		$('#progressbar h5').text("Descartando productos...");
		$('#progressbar').modal("hide");
		stopPrograssBar(progressBarIdProcess);
		document.cookie = "TYPECOIN=UYU";
		$('#selectTypeCoin').val("UYU");
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

	for (var i = 0; i < arrayDetails.length; i++) {
		if(arrayDetails[i].removed == false || arrayDetails[i].removed == "false"){
			arrayDetails[i].removed = "true";
		}
	}
	return new Promise(resolve => {
		resolve(removedAllProducts());
	});
}

function elementsNoRemoved (){
	count = 0
	for (var i = arrayDetails.length - 1; i >= 0; i--) {
		if (arrayDetails[i].removed == "false" || arrayDetails[i].removed == false){
			count++;
		}
	}
	return count;
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
	    return importe; break;
	}

	return (importe / percent).toFixed(2);


}


function updateConfigurationIva(inputVariable){
	let variable = "IVA_INCLUIDO"
	let value = inputVariable.checked;

	let booleanValue = "NO";
	if(value)
		booleanValue = "SI";

	sendAsyncPost("updateVariableConfiguration", {variable: variable, value: booleanValue})
	.then((response)=>{
		console.log(response);
		if(response.result != 2){
			if(value) inputVariable.checked = false;
			else inputVariable.checked = true;
		}else window.location.reload();

	})
}