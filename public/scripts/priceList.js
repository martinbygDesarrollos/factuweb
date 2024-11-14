var lastID = 0;
var textToSearch = null;
var headingValue = null;
var discountPercentage = null;

$('#modalCreateModifyProduct').on('shown.bs.modal', function () {
	if(!discountPercentage){
		let response = sendPost("getConfiguration", {nameConfiguration: "DESCUENTO_EN_PORCENTAJE"});
		if(response.result == 2)
			discountPercentage = response.configValue;
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
	headingValue = $('#selectHeadingPriceList').val() || null;
	let response = sendPost("loadPriceList", {lastId: lastID, textToSearch: textToSearch, heading: headingValue});
	//console.log(response);
	if(response.result == 2){
		if(lastID != response.lastId){
			lastID = response.lastId;
			let list = response.listResult;
			for (let i = 0; i < list.length; i++) {
				let row = createRowProduct(list[i].idArticulo, list[i].descripcion, list[i].detalle, list[i].marca, list[i].rubro, list[i].valor, list[i].costo, list[i].importe, list[i].descuento, list[i].monedaSimbol);
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

function searchProduct(){
	let textTemp = $('#inputToSearch').val();

	if (textTemp != null){
		if(textTemp.length >= 3){
			textToSearch = textTemp;
			lastID = 0;
			$('#tbodyProducts').empty();
			loadPriceList();
		}else if(textTemp.length == 0){
			textToSearch = null;
			lastID = 0;
			$('#tbodyProducts').empty();
			loadPriceList();
		}
	}
	else{
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

function createRowProduct(idProduct, description, detail, band, heading, valueIVA, cost, amount, discount, symbolCoin) {
    // Determine the appropriate class based on the presence of brand and detail
    let rowClass = '';
    if (band && detail) {
        rowClass = 'with-brand-and-detail';
    } else if (band || detail) {
        rowClass = 'with-brand-or-detail';
    } else {
        rowClass = 'with-nothing';
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

    row += `<td class='text-right notShowInPhone'>${valueIVA}</td>`;
    row += `<td class='text-right notShowInPhone'>${symbolCoin} ${cost}</td>`;
    row += `<td class='text-right'>${symbolCoin} ${amount}</td>`;
    row += `<td class='text-center'>
              <button class='btn btn-sm background-template-color2 text-template-background mr-2' 
                      onclick='openModalModify(${idProduct})'>
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

function openModalNewProduct(){
	$('#titleModalCreateModifyProduct').html('Agregar artículo');
	clearModalProduct();
	$('#modalCreateModifyProduct').modal();
	$('#btnConfirmProduct').off('click');
	$('#btnConfirmProduct').click(function(){
		createNewProduct();
	});
}

function openModalModify(idProduct){
	let response = sendPost('getProductById', {idProduct: idProduct});
	if(response.result == 2){
		$('#titleModalCreateModifyProduct').html('Modificar artículo')
		clearModalProduct();
		setValuesToEdit(response.objectResult);
		$('#modalCreateModifyProduct').modal();
		$('#btnConfirmProduct').off('click');
		$('#btnConfirmProduct').click(function(){
			updateProduct(idProduct);
		});
	}else showReplyMessage(response.result, response.message, "Artículo no obtenido", null);
}

function clearModalProduct(){
	$('#inputDescription').val("");
	$('#inputBrand').val("");
	$('#textAreaDetail').val("");
	$('#inputBarcode').val("");
	$('#typeCoinUYU').attr('checked',true).change();
	$('#inputCost').val(0);
	$('#inputCoefficient').val(0);
	$('#inputDiscount').val(0);
	$('#inputPriceNoIVA').val(0);
	$('#inputPriceFinal').val(0);
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
	$('#inputDiscount').val(productObject.descuento);
	$('#selectIVA').val(productObject.idIva);
	$('#inputPriceNoIVA').val();
	$('#inputPriceFinal').val(productObject.importe);
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
		}
	}else if(value != null && value.length == size) return false;
}

$('#inputCost').keyup(function(){
	keyUpProductValues();
});

$('#inputDiscount').keyup(function(){
	keyUpProductValues();
});

$('#inputCoefficient').keyup(function(){
	keyUpProductValues();
});

$('#inputPriceFinal').keyup(function(){
	//keyUpProductValues2();
});


$('#selectIVA').change(function(){
	keyUpProductValues();
});

function keyUpProductValues(inputSelect){
	let cost = parseFloat($('#inputCost').val() || 0);
	let discount = parseFloat($('#inputDiscount').val() || 0);
	let coefficient = parseFloat($('#inputCoefficient').val() || 0);
	let subTotal = parseFloat($('#inputPriceNoIVA').val() || 0);
	let total = parseFloat($('#inputPriceFinal').val() || 0);
	let iva = parseFloat($('#selectIVA option:selected').attr('name'));


	if(discountPercentage == "SI"){
		discount = ((cost + coefficient) * discount)/100;
	}

	let valueIVA = (((cost + coefficient) - discount) * iva) /100;
	let priceNOIVA = parseFloat((cost + coefficient) - discount).toFixed(2);
	let priceFinal = parseFloat(((cost + coefficient) - discount) + valueIVA).toFixed(2);

	$('#inputPriceNoIVA').val(priceNOIVA);
	$('#inputPriceFinal').val(priceFinal);
}

function keyUpProductValues2(){
	let price = parseFloat($('#inputPriceFinal').val() || 0);
	let cost = parseFloat($('#inputCost').val() || 0);
	let discount = parseFloat($('#inputDiscount').val() || 0);
	let coefficient = parseFloat($('#inputCoefficient').val() || 0);
	let iva = parseFloat($('#selectIVA option:selected').attr('name'));

	valueIVA = price / ((iva / 100) + 1);

	let priceNOIVA = parseFloat(price / ((iva / 100) + 1)).toFixed(2);
	let coeff = parseFloat((price / ((iva / 100) + 1)) - cost).toFixed(2);
	$('#inputPriceNoIVA').val(priceNOIVA);
	$('#inputCoefficient').val(coeff);
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

function createNewProduct(){
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
			inventory: null, //es el id de inventario que se encuentra como fk en la tabla de articulos
			minInventory: null, //mìnima cantidad de articulos
		}
		let response = sendPost('insertProduct',data);
		showReplyMessage(response.result, response.message, "Nuevo artículo", "modalCreateModifyProduct");

		$("#modalButtonResponse").click(function(){
			if(response.result == 2){
				clearModalProduct();
				window.location.reload();
			}
		});
	}else showReplyMessage(1,"La descripción no puede ingresarse vacia o contener menos de 5 caracteres.", "Descripción no valido");

}

function updateProduct(idProduct){
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
			discount: discount
		}
		let response = sendPost("updateProduct", data);
		//console.log(response);
		showReplyMessage(response.result, response.message, "Actualizar artículo", "modalCreateModifyProduct");
		$("#modalButtonResponse").click(function(){
			if(response.result == 2){
				clearModalProduct();
				window.location.reload();
			}
		});
	}else showReplyMessage(1,"La descripción no puede ingresarse vacia o contener menos de 5 caracteres.", "Descripción no valido");
}