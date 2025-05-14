var lastID = 0;
var textToSearch = null;
var headingValue = null;
var discountPercentage = null;

var defaultIva = null;

$('#modalCreateModifyProduct').on('shown.bs.modal', function () {
	if(!discountPercentage){
		let response = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
		if(response.result == 2)
			discountPercentage = response.configValue;
	}
	if(!defaultIva){
		let response = sendPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"});
		if(response.result == 2)
			defaultIva = response.configValue;
	}
});

function loadHeadings(){
	let response = sendPost("getHeadings");
	if(response.result == 2 && response.listResult.length > 0){
		lista = response.listResult;
		for (let i = 0; i < lista.length; i++) {
			if ( i == 0)
				row = '<option value="'+lista[i].idRubro+'" selected>'+lista[i].rubro+'</option>';
			else
				row = '<option value="'+lista[i].idRubro+'">'+lista[i].rubro+'</option>';
			$('#selectHeadingPriceList').append(row);
			$('#selectRubro').append(row);
		}
	}
}

function loadPriceList(){

	if ($('#inputToSearch').val() != null && $('#inputToSearch').val() != "" && $('#inputToSearch').val().length >= 3){
		textToSearch = $('#inputToSearch').val();
	}
	let stockManagement = 'NO'
	let responseManagementStock = sendPost("getConfiguration", {nameConfiguration: "MANEJO_DE_STOCK"});
	if(responseManagementStock.result == 2)
		stockManagement = responseManagementStock.configValue;

	headingValue = $('#selectHeadingPriceList').val() || null;
	let response = sendPost("loadPriceList", {lastId: lastID, textToSearch: textToSearch, heading: headingValue});
	//console.log(response);
	if(response.result == 2){
		if(lastID != response.lastId){
			lastID = response.lastId;
			let list = response.listResult;
			for (let i = 0; i < list.length; i++) {
				// console.log(list[i])
				let row = createRowProduct(stockManagement, list[i].idArticulo, list[i].descripcion, list[i].detalle, list[i].marca, list[i].rubro, list[i].valor, list[i].costo, list[i].importe, list[i].descuento, list[i].inventario, list[i].inventarioMinimo,  list[i].monedaSimbol);
				$('#tbodyProducts').append(row);
			}
		}
	}
}

function loadPriceListFromId($lastId){
		headingValue = $('#selectHeadingPriceList').val();

	let response = sendPost("loadPriceList", {lastId: $lastId, textToSearch: textToSearch, heading: headingValue});
	if(response.result == 2){
		if($lastId != response.lastId){
			$lastId = response.lastId;
			let list = response.listResult;
			for (let i = 0; i < list.length; i++) {

				let row = createRowProduct(list[i].idArticulo, list[i].descripcion, list[i].detalle, list[i].marca, list[i].rubro, list[i].valor, list[i].costo, list[i].importe, list[i].descuento, list[i].monedaSimbol);
				$('#tbodyProducts').append(row);
			}
		}
	}
}

// function searchProduct(){
// 	let textTemp = $('#inputToSearch').val();

// 	if (textTemp != null){
// 		if(textTemp.length >= 3){
// 			textToSearch = textTemp;
// 			lastID = 0;
// 			$('#tbodyProducts').empty();
// 			loadPriceList();
// 		}else if(textTemp.length == 0){
// 			textToSearch = null;
// 			lastID = 0;
// 			$('#tbodyProducts').empty();
// 			loadPriceList();
// 		}
// 	}
// 	else{
// 		textToSearch = null;
// 		lastID = 0;
// 		$('#tbodyProducts').empty();
// 		loadPriceList();
// 	}
// }

function searchProduct(event) {
    let textTemp = $('#inputToSearch').val();
    
    if (textTemp != null) {
        // Verificar si todos los caracteres son números
        let isAllNumbers = /^\d+$/.test(textTemp);
        
        if (isAllNumbers) {
            // Si son solo números, esperar a Enter (código 13) o Tab (código 9)
            if (event && (event.keyCode === 13 || event.which === 13 || 
                         event.keyCode === 9 || event.which === 9)) {
                textToSearch = textTemp;
                lastID = 0;
                $('#tbodyProducts').empty();
                loadPriceList();
            }
            // Si no es Enter/Tab, no hacer nada
        } else {
            // Si no son solo números, funcionar como antes (búsqueda normal)
            if (textTemp.length >= 3) {
                textToSearch = textTemp;
                lastID = 0;
                $('#tbodyProducts').empty();
                loadPriceList();
            } else if (textTemp.length == 0) {
                textToSearch = null;
                lastID = 0;
                $('#tbodyProducts').empty();
                loadPriceList();
            }
        }
    } else {
        textToSearch = null;
        lastID = 0;
        $('#tbodyProducts').empty();
        loadPriceList();
    }
}

function searchProductByHeading(){
	let selectHeading = $('#selectHeadingPriceList').val();

	if (selectHeading != 0){
		lastID = 0;
		$('#tbodyProducts').empty();
		loadPriceList();
	}
	else{
		textToSearch = null;
		lastID = 0;
		$('#tbodyProducts').empty();
		loadPriceList();
	}
}

// function createRowProduct(idProduct, description, detail, band, heading, valueIVA, cost, amount, discount, symbolCoin){
// 	let row = "<tr id='row"+ idProduct + "'>";
// 	row += "<td class='text-left'>"+ description +"</td>";
// 	row += "<td class='text-left notShowInPhone'>"+ detail +"</td>";
// 	row += "<td class='text-left notShowInPhone'>"+ band +"</td>";
// 	//row += "<td class='text-left notShowInPhone'>"+ heading +"</td>";
// 	//row += "<td class='text-center notShowInPhone'>"+ symbolCoin +"</td>";
// 	row += "<td class='text-right notShowInPhone'>"+ valueIVA +"</td>";
// 	row += "<td class='text-right notShowInPhone'>"+ symbolCoin+"  "+cost +"</td>";
// 	row += "<td class='text-right'>"+ symbolCoin+"  "+amount +"</td>";
// 	//row += "<td class='text-right notShowInPhone'>"+ discount +"</td>";
// 	row += "<td class='text-center'>";
// 	row += "<button class='btn btn-sm background-template-color2 text-template-background mr-2' onclick='openModalModify("+ idProduct +")'><i class='fas fa-edit'></i></button>";
// 	row += "<button id='"+ idProduct +"' name='"+ description +"' onclick='openModalDeleteProduct(this)' class='btn btn-sm btn-danger'><i class='fas fa-trash-alt'></i></button>";
// 	row += "</td>";
// 	row += "</tr>";
// 	return row;
// }

function createRowProduct(stockManagement, idProduct, description, detail, band, heading, valueIVA, cost, amount, discount, inventory, minInventory, symbolCoin) {
    // Determine the appropriate class based on the presence of brand and detail
    let rowClass = '';
    if (band && detail) {
        rowClass = 'with-brand-and-detail';
    } else if (band || detail) {
        rowClass = 'with-brand-or-detail';
    } else {
        rowClass = 'with-nothing';
    }

	if(stockManagement == 'SI'){
		rowClass += inventory > minInventory ? ' isStockAboveMinimum' : ' isStockBelowMinimum'
	}

    let row = `<tr id='row${idProduct}' class='${rowClass}'>`;
    
    row += `<td class='text-left'>
    <span class='cell-truncate mainText' title='${description}'>
        ${description}
    </span>
    ${detail ? `<span class='cell-truncate secondText' title='${detail}'>
        ${detail}
    </span>` : ''}
    ${band ? `<span class='cell-truncate brand' title='${band}'>[ 
        ${band} ]
    </span>` : ''}
    </td>`;
	if(stockManagement == 'SI'){
    	row += `<td class='text-right notShowInPhone stock ${inventory > minInventory ? `isStockAboveMinimum` : `isStockBelowMinimum`}  '>${inventory}</td>`;
	}

    row += `<td class='text-right notShowInPhone'>${valueIVA}</td>`;
    row += `<td class='text-right notShowInPhone'>${symbolCoin} ${cost}</td>`;
    row += `<td class='text-right'>${symbolCoin} ${amount}</td>`;
    row += `<td class='text-center'>
              <button class='btn btn-sm background-template-color2 text-template-background mr-1' 
                      onclick='openModalModify("${stockManagement}", ${idProduct})'>
                <i class='fas fa-edit'></i>
              </button>
              <button id='${idProduct}' 
                      name='${description}' 
                      onclick='openModalDeleteProduct(this)' 
                      class='btn btn-sm btn-danger'
                      style='width: 33.75px;'>
                <i class='fas fa-trash-alt'></i>
              </button>
            </td>`;
    
    row += "</tr>";
    return row;
}

function openModalNewProduct(stockManagement){
	$('#titleModalCreateModifyProduct').html('Agregar artículo');
	clearModalProduct();
	$('#modalCreateModifyProduct').modal();
	$('#btnConfirmProduct').off('click');
	$('#btnConfirmProduct').click(function(){
		createNewProduct(stockManagement);
	});
}

function openModalModify(stockManagement, idProduct){
	let response = sendPost('getProductById', {idProduct: idProduct});
	if(response.result == 2){
		$('#titleModalCreateModifyProduct').html('Modificar artículo')
		clearModalProduct();
		setValuesToEdit(response.objectResult);
		$('#modalCreateModifyProduct').modal();
		$('#btnConfirmProduct').off('click');
		$('#btnConfirmProduct').click(function(){
			updateProduct(stockManagement, idProduct);
		});
	}else showReplyMessage(response.result, response.message, "Artículo no obtenido", null);
}

function clearModalProduct(){
	if(!defaultIva){
		let response = sendPost("getConfiguration", {nameConfiguration: "INDICADORES_FACTURACION_DEFECTO"});
		if(response.result == 2)
			defaultIva = response.configValue;
	}
	$('#inputDescription').val("");
	$('#inputBrand').val("");
	$('#textAreaDetail').val("");
	$('#inputBarcode').val("");
	$('#typeCoinUYU').attr('checked',true).change();
	$('#inputCost').val(0);
	$('#inputCoefficient').val(0);
	$('#inputCoefficient').removeClass('alert-danger');
	$('#inputDiscount').val(0);
	$('#inputPriceNoIVA').val(0);
	$('#selectIVA').val(defaultIva);
	$('#inputPriceFinal').val(0);
	$('#inputInventory').val(1);
	$('#inputMinInventory').val(0);
}

function setValuesToEdit(productObject){
	$('#inputDescription').val(productObject.descripcion);
	$('#inputBrand').val(productObject.marca);
	$('#selectRubro').val(productObject.idRubro);
	$('#textAreaDetail').val(productObject.detalle);
	$('#inputBarcode').val(productObject.codigoBarra);
	if(productObject.moneda == "UYU")
		$('#typeCoinUYU').attr('checked',true).change();
	else
		$('#typeCoinUSD').attr('checked',true).change();
	$('#inputCost').val(productObject.costo);
	$('#inputCoefficient').val(productObject.coeficiente);

	if($('#inputCoefficient').val() < 0)
		$('#inputCoefficient').removeClass().addClass('form-control form-control-sm text-center shadow-sm text-center shadow-sm alert-danger')
	else
		$('#inputCoefficient').removeClass('alert-danger')

	$('#inputDiscount').val(productObject.descuento);
	$('#selectIVA').val(productObject.idIva);
	$('#inputPriceNoIVA').val();
	$('#inputPriceFinal').val(productObject.importe);
	$('#inputInventory').val(productObject.inventario);
	$('#inputMinInventory').val(productObject.inventarioMinimo);
}

function openModalNewRubro(){
	$('#modalCreateModifyProduct').modal('hide');
	$('#modalCreateNewRubro').modal();
	$('#btnConfirmNewRubro').off('click');
	$('#btnConfirmNewRubro').on("click",function(){
		createNewRubro();
	});
	$('#btnCancelNewRubro').off('click');
	$('#btnCancelNewRubro').on("click",function(){
		$('#modalCreateModifyProduct').modal();
	});
}

function createNewRubro(){
	console.log("createNewRubro")
	let rubro = $('#inputRubro').val() || null;

	if(rubro && rubro.length > 4){
		let response = sendPost('insertHeading', {nameHeading: rubro});
		showReplyMessage(response.result, response.message, "Nuevo Rubro", "modalCreateNewRubro");

		if(response.result == 2){
			setTimeout(function(){
				window.location.reload();
			}, 500);
		}
		// $('#modalResponse').on('hidden.bs.modal', function (e) {
		// })
	}else showReplyMessage(1,"El Rubro no puede ingresarse vacia o contener menos de 5 caracteres.", "Rubro no valido", "modalCreateNewRubro");
}

function keyPressProduct(keyPress, value, size){
	if(keyPress.keyCode == 13){
		if(keyPress.srcElement.id == "inputDescription"){
			$('#inputBrand').focus();
		}else if(keyPress.srcElement.id =="inputBrand"){
			if(keyPress.shiftKey)
				$('#inputDescription').focus();
			else
				$('#inputBarcode').focus();
		}else if(keyPress.srcElement.id == "inputBarcode"){
			if(keyPress.shiftKey)
				$('#inputBrand').focus();
			else
				$('#textAreaDetail').focus();
		}else if(keyPress.srcElement.id == "textAreaDetail"){
			if(keyPress.shiftKey)
				$('#inputBarcode').focus();
			else
				$('#inputCost').focus();
		}else if(keyPress.srcElement.id == "inputCost"){
			if(keyPress.shiftKey)
				$('#textAreaDetail').focus();
			else
				$('#inputCoefficient').focus();
		}else if(keyPress.srcElement.id == "inputCoefficient"){
			if(keyPress.shiftKey)
				$('#inputCost').focus();
			else
				$('#inputDiscount').focus();
		}else if(keyPress.srcElement.id == "inputDiscount"){
			if(keyPress.shiftKey)
				$('#inputCoefficient').focus();
			else
				$('#inputPriceFinal').focus();
		}else if(keyPress.srcElement.id == "inputPriceFinal"){
			if(keyPress.shiftKey)
				$('#inputDiscount').focus();
			else
				$('#btnConfirmProduct').click();
		}else if(keyPress.srcElement.id == "inputRubro"){
			$('#btnConfirmNewRubro').click();
		}
	}else if(value != null && value.length == size) return false;
}

$('#inputCost').keyup(function(){
	keyUpProductValues(); // SI QUIERO CAMBIAR EL PRECIO FINAL (AGREGAR ALGO PARA CONFIGRURAR ESO... UN CHECK O ALGO ASI QUE MODIFIQUE EL PRECIO FINAL O EL COEFICIENTE SEGUN SI ESTA CHECK O NO)
});

$('#inputDiscount').keyup(function(){
	keyUpProductValues();
});

$('#inputCoefficient').keyup(function(){
	keyUpProductValues();
});

$('#inputPriceFinal').keyup(function(){
	keyUpFromFinalPrice()
});


$('#selectIVA').change(function(){
	keyUpProductValues();
});

function keyUpProductValues(){
	let cost = parseFloat($('#inputCost').val() || 0);
	let rawDiscount = $('#inputDiscount').val() || '0';
	let discount = parseFloat(rawDiscount);
	let iva = parseFloat($('#selectIVA option:selected').attr('name'));
	let raw = $('#inputCoefficient').val() || '';

	// Corregir límites
	if (discount < 0) discount = 0;
	if (discount > 100) discount = 100;

	// Solo permitir números y un punto (decimal)
	// let numericString = raw.replace(/[^0-9.]/g, '');
	let isNegative = raw.startsWith('-');
	let numericString = raw.replace(/[^0-9.]/g, '');

	// Asegurarse de que haya **solo un punto decimal** (por si el usuario puso más)
	numericString = numericString.split('.').reduce((acc, part, index) => {
	return index === 0 ? part : acc + '.' + part;
	}, '');
	// Reañadir el signo menos si era negativo
	if (isNegative) {
		numericString = '-' + numericString;
		//VISUAL
		if($('#inputCoefficient').val() < 0)
			$('#inputCoefficient').removeClass().addClass('form-control form-control-sm text-center shadow-sm text-center shadow-sm alert-danger')
		else
			$('#inputCoefficient').removeClass('alert-danger')
	}

	let coefficient = parseFloat(numericString) || 0;

	// Transformar a porcentaje + 1
	let multiplier = 1 + (coefficient / 100);

	let costWithCoeff = cost * multiplier;
	console.log(costWithCoeff)

	if(discountPercentage == "SI"){
		discount = ((costWithCoeff) * discount)/100;
	}

	let valueIVA = (((costWithCoeff) - discount) * iva) /100;
	let priceNOIVA = parseFloat((costWithCoeff) - discount).toFixed(2);
	let priceFinal = parseFloat(((costWithCoeff) - discount) + valueIVA).toFixed(2);

	$('#inputPriceNoIVA').val(priceNOIVA);
	$('#inputPriceFinal').val(priceFinal);
}

function keyUpFromFinalPrice() {
	// Obtener los valores actuales
	let cost = parseFloat($('#inputCost').val() || 0);
	let rawDiscount = $('#inputDiscount').val() || '0';
	let discount = parseFloat(rawDiscount);
	let iva = parseFloat($('#selectIVA option:selected').attr('name'));
	let priceFinal = parseFloat($('#inputPriceFinal').val() || 0);

	// Verificar si el costo es diferente de 0 (condición principal)
	if (cost === 0) {
		return; // No calcular el coeficiente si no hay costo
	}

	// Corregir límites del descuento
	if (discount < 0) discount = 0;
	if (discount > 100) discount = 100;

	// Calcular el precio sin IVA primero
	let priceNoIVA = priceFinal / (1 + (iva / 100));

	// Establecer el valor del precio sin IVA
	$('#inputPriceNoIVA').val(parseFloat(priceNoIVA).toFixed(2));

	// Ahora calculamos el coeficiente según si hay descuento o no
	let costWithCoeff;

	if (discount === 0) {
		// Sin descuento, el cálculo es directo
		costWithCoeff = priceNoIVA;
	} else {
		// Con descuento
		if (discountPercentage === "SI") {
		// Si el descuento es porcentual
		costWithCoeff = priceNoIVA / (1 - (discount / 100));
		} else {
		// Si el descuento es un valor fijo
		costWithCoeff = priceNoIVA + discount;
		}
	}

	// Finalmente calculamos el coeficiente
	let coefficient = ((costWithCoeff / cost) - 1) * 100;

	// Formatear y establecer el coeficiente
	$('#inputCoefficient').val(parseFloat(coefficient).toFixed(2));
	if($('#inputCoefficient').val() < 0)
		$('#inputCoefficient').removeClass().addClass('form-control form-control-sm text-center shadow-sm text-center shadow-sm alert-danger')
	else
		$('#inputCoefficient').removeClass('alert-danger')

}

function openModalDeleteProduct(buttonDelete){
	$('#modalDeleteProduct').modal();
	$('#messageDeleteProduct').html("¿Desea borrar el artículo <b>" + buttonDelete.name + "</b> del sistema?");
	$('#btnConfirmDeleteProduct').off('click');
	$('#btnConfirmDeleteProduct').click(function(){
		deleteProduct(buttonDelete.id);
	});
}

function deleteProduct(idProduct){
	let response = sendPost('deleteProduct', { idProduct: idProduct});
	showReplyMessage(response.result, response.message, "Borrar artículo", "modalDeleteProduct");
	if(response.result == 2)
		$('#row' + idProduct).remove();
}

function createNewProduct(stockManagement){
	console.log("createNewProduct")
	let description = $('#inputDescription').val() || null;
	let brand = $('#inputBrand').val() || null;
	let idHeading= $('#selectRubro').val();
	let detail = $('#textAreaDetail').val() || null;
	let typeCoinUYU = $('#typeCoinUYU').is(':checked');
	let cost = $('#inputCost').val() || 0;
	let coefficient = $('#inputCoefficient').val() || 0;
	let discount = $('#inputDiscount').val() ||0;
	let iva = $('#selectIVA').val();
	let priceFinal = $('#inputPriceFinal').val() || 0;
	let barcode = $('#inputBarcode').val() || null;

	let inventory = $('#inputInventory').val() || null;
	let minInventory = $('#inputMinInventory').val() || null;

	if(stockManagement == "SI"){ // SI ESTAS MANEJANDO STOCK Y NO ESTA EL CAMPO PUESTO ENTONCES SE SETTEA A 0 A AMBOS
		inventory = inventory === null ? 0 : inventory
		minInventory = minInventory === null ? 0 : minInventory
	}

	let typeCoin = "UYU";
	if(!typeCoinUYU)
		typeCoin = "USD";

	if(description && description.length > 4){
		let data = {
			idHeading: idHeading,
			idIva: iva,
			description: description,
			detail: detail,
			brand: brand,
			typeCoin: typeCoin,
			cost: cost,
			coefficient: coefficient,
			amount: priceFinal,
			barcode: barcode,
			discount: discount,
			inventory: inventory, //es el id de inventario que se encuentra como fk en la tabla de articulos
			minInventory: minInventory //mìnima cantidad de articulos
		}
		let response = sendPost('insertProduct',data);
		showReplyMessage(response.result, response.message, "Nuevo artículo", "modalCreateModifyProduct");

		$('#selectHeadingPriceList').trigger('change')
	}else showReplyMessage(1,"La descripción no puede ingresarse vacia o contener menos de 5 caracteres.", "Descripción no valido", "modalCreateModifyProduct");

}

function updateProduct(stockManagement, idProduct){
	console.log("updateProduct pricelist");
	let description = $('#inputDescription').val() || null;
	let brand = $('#inputBrand').val() || null;
	let idHeading= $('#selectRubro').val();
	let detail = $('#textAreaDetail').val() || null;
	let typeCoinUYU = $('#typeCoinUYU').is(':checked');
	let cost = $('#inputCost').val() || 0;
	let coefficient = $('#inputCoefficient').val() || 0;
	let discount = $('#inputDiscount').val() ||0;
	let iva = $('#selectIVA').val();
	let priceFinal = $('#inputPriceFinal').val() || 0;
	let barcode = $('#inputBarcode').val() || null;

	let inventory = $('#inputInventory').val() || null;
	let minInventory = $('#inputMinInventory').val() || null;

	if(stockManagement == "SI"){ // SI ESTAS MANEJANDO STOCK Y NO ESTA EL CAMPO PUESTO ENTONCES SE SETTEA A 0 A AMBOS
		inventory = inventory === null ? 0 : inventory
		minInventory = minInventory === null ? 0 : minInventory
	}

	let typeCoin = "UYU";
	if(!typeCoinUYU)
		typeCoin = "USD";

	if(description && description.length > 4){
		let data = {
			idProduct: idProduct,
			idHeading: idHeading,
			idIva: iva,
			description: description,
			detail: detail,
			brand: brand,
			typeCoin: typeCoin,
			cost: cost,
			coefficient: coefficient,
			amount: priceFinal,
			barcode: barcode,
			discount: discount,
			inventory: inventory,
			minInventory: minInventory
		}
		let response = sendPost("updateProduct", data);
		showReplyMessage(response.result, response.message, "Actualizar artículo", "modalCreateModifyProduct");
		$('#selectHeadingPriceList').trigger('change')

	}else showReplyMessage(1,"La descripción no puede ingresarse vacia o contener menos de 5 caracteres.", "Descripción no valido");
}

// Manejar cambio en los radio buttons
$('#radioDBF').change(function() {
	if($(this).is(':checked')) {
		$('#dbfSection').show();
		$('#xlsxSection').hide();
	}
});

$('#radioXLSX').change(function() {
	if($(this).is(':checked')) {
		$('#xlsxSection').show();
		$('#dbfSection').hide();
	}
});

// Mostrar el nombre del archivo seleccionado
$('.custom-file-input').on('change', function() {
	var fileName = $(this).val().split('\\').pop();
	fileNameAux = fileName != "" ? fileName : $(this).attr('originalLabel');
	$(this).prev('.custom-file-label').html(fileNameAux);
});

// Lógica para los botones de subir archivo
$('#btnUploadDBF').click(function() {
	// Aquí iría la lógica para procesar el archivo DBF
	if ($('#dbfFileInput').val()) {
		console.log('Subiendo archivo DBF');
		// Implementar lógica de subida
	} else {
		alert('Por favor seleccione un archivo DBF primero');
	}
});

$('#btnUploadXLSX').click(function() {
	// Aquí iría la lógica para procesar el archivo XLSX
	if ($('#xlsxFileInput').val()) {
		console.log('Subiendo archivo XLSX');
		// Implementar lógica de subida
	} else {
		alert('Por favor seleccione un archivo XLSX primero');
	}
});

// Botón de confirmar en el footer
$('#btnConfirmImportArticles').click(function() {
	// Lógica para confirmar la importación
	var selectedType = $('input[name="importType"]:checked').attr('id');
	
	var myFile = null;

	if (selectedType === 'radioDBF' && !$('#dbfFileInput').val()) {
		alert('Por favor seleccione un archivo DBF primero');
		return;
	}
	
	if (selectedType === 'radioXLSX' && !$('#xlsxFileInput').val()) {
		alert('Por favor seleccione un archivo XLSX primero');
		return;
	}
	
	if (selectedType === 'radioDBF') {
		myFile = $('#dbfFileInput')[0].files[0];
	} else if (selectedType === 'radioXLSX') {
		myFile = $('#xlsxFileInput')[0].files[0];
	}

	console.log('Confirmando importación de ' + (selectedType === 'radioDBF' ? 'DBF' : 'XLSX'));
	// Implementar lógica de importación
	console.log('Archivo cargado en el input');
	console.log(myFile);
	// Create a FormData object and append the File object to it
	var formData = new FormData();
	formData.append('file', myFile);
	
	$("#modalImportArticles").modal("hide");

	importProductsWithProgress(myFile);

	// mostrarLoader(true)
	// sendFetch("importProducts", formData )
	// .then((response)=>{
	// 	console.log(response)
	// 	mostrarLoader(false)
	// 	if(response.result == 2){
	// 		showReplyMessage(response.result, response.message, "Detalle de importacion");
	// 	} else showReplyMessage(response.result, response.message, "Detalle de importacion");
	// })
	// .catch((error) => {
	// 	mostrarLoader(false)
	// 	console.error(error);
	// });
	// Cerrar el modal después de la confirmación
	// $('#modalImportArticles').modal('hide');
});

async function importProductsWithProgress(file) {
	let isImportCancelled = false; // Variable para controlar la cancelación

    const progressDiv = document.createElement('div');
	progressDiv.style = `position:absolute; background-color: #000000c4; height: -webkit-fill-available; width: -webkit-fill-available; z-index: 999;display: flex; justify-content: center; flex-wrap: nowrap; align-items: center;`
    progressDiv.innerHTML = `
        <div style="margin: 20px; width: 80%;">
            <div style="margin-bottom: 10px;">
                <strong style="color: white;">Importando productos...</strong>
                <span style="color: white;" id="progressText">0%</span>
            </div>
            <div style="width: 100%; background-color: #f0f0f0; border-radius: 5px; border: solid 2px #FFFFFF;">
                <div id="progressBar" style="width: 0%; height: 20px; background-color: #37A398; border-radius: 5px; transition: width 0.3s;"></div>
            </div>
            <div id="progressStatus" style="margin-top: 10px;color: white;">Procesando...</div>
			<button id="cancelImportButton" style="margin-top: 20px; padding: 10px 20px; background-color: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Detener Importación
            </button>
        </div>
    `;
    
    // Agregar el div de progreso al DOM
    document.body.appendChild(progressDiv);

	// Event listener botón de cancelar
    // document.getElementById('cancelImportButton').addEventListener('click', function() {
    //     isImportCancelled = true;
    //     document.getElementById('progressStatus').textContent = 'Cancelando importación...';
    //     this.disabled = true; // Deshabilitar el botón para evitar múltiples clicks
    //     this.style.backgroundColor = '#9e9e9e';
    //     this.textContent = 'Cancelando...';
    // });
	document.getElementById('cancelImportButton').addEventListener('click', function() {
		if (confirm('¿Estás seguro de que deseas detener la importación? Los productos ya procesados permanecerán en el sistema.')) {
			isImportCancelled = true;
			document.getElementById('progressStatus').textContent = 'Cancelando importación...';
			this.disabled = true;
			this.style.backgroundColor = '#9e9e9e';
			this.textContent = 'Cancelando...';
		}
	});
    
    let offset = 0;
    let limit = 500; // Procesar 500 productos por lote
    let allResponse = null;
    
    while (true) {
		// Verificar si la importación fue cancelada
        if (isImportCancelled) {
            progressDiv.remove();
            showReplyMessage(1, `Importación cancelada. Se procesaron ${totalProcessed} productos antes de detener.`, "Importación Cancelada");
            return;
        }

        const formData = new FormData();
        if (offset === 0) {
            formData.append('file', file);
        }
        formData.append('offset', offset);
        formData.append('limit', limit);
        
        try {
            // Tomar el tiempo antes de procesar el lote
            const batchStartTime = Date.now();
            
            const response = await sendFetch("importProducts", formData);
            
            // Calcular el tiempo que tomó este lote
            const batchTime = Date.now() - batchStartTime;
            
            console.log(`Lote procesado - Offset: ${offset}, Productos: ${response.products.length}`);
            
            if (response.result === 2) {
                offset = response.offset;
                totalProcessed = response.processed;
                
                // Actualizar progreso
                const progress = (response.processed / response.total) * 100;
                document.getElementById('progressBar').style.width = progress + '%';
                document.getElementById('progressText').textContent = Math.round(progress) + '%';
                
                // Calcular tiempo estimado basado en el último lote
                const productsInBatch = response.products.length;
                const remainingProducts = response.total - response.processed;
                
                if (productsInBatch > 0 && remainingProducts > 0) {
                    const timePerProduct = batchTime / productsInBatch;
                    const estimatedTimeRemaining = remainingProducts * timePerProduct;
                    
					const timeString = formatTimeRemaining(estimatedTimeRemaining);
					document.getElementById('progressStatus').innerHTML = 
						`Procesados ${response.processed} de ${response.total} productos<br>
						<span style="color: #37A398;">Tiempo estimado: ${timeString}</span>`;
                } else {
                    document.getElementById('progressStatus').textContent = 
                        `Procesados ${response.processed} de ${response.total} productos`;
                }
                
                if (response.isComplete) {
                    allResponse = response;
                    break;
                }
            } else {
                throw new Error(response.message || 'Error en la importación');
            }
        } catch (error) {
            console.error('Error:', error);
            showReplyMessage(1, 'Error durante la importación: ' + error.message, "Error de importación");
            progressDiv.remove();
            return;
        }
    }
    
    // Limpiar el progreso
    progressDiv.remove();
    
    // Mostrar resultado final
    showReplyMessage(2, `Importación completada. ${allResponse.total} productos procesados.`, "Detalle de importación");
}

// Para mostrar el tiempo de forma más amigable
function formatTimeRemaining(milliseconds) {
    if (milliseconds < 60000) { // Menos de 1 minuto
        const seconds = Math.ceil(milliseconds / 1000);
        return `${seconds} segundos`;
    } else if (milliseconds < 3600000) { // Menos de 1 hora
        const minutes = Math.floor(milliseconds / 60000);
        const seconds = Math.floor((milliseconds % 60000) / 1000);
        return `${minutes}m ${seconds}s`;
    } else { // Más de 1 hora
        const hours = Math.floor(milliseconds / 3600000);
        const minutes = Math.floor((milliseconds % 3600000) / 60000);
        return `${hours}h ${minutes}m`;
    }
}