function loadCart (){
	console.log("loadCart")
    let response = sendPost('getCart', null);
    if(response.result == 2){
        productsInCart = fixTypes(response.data)
        updateListArticles()
    }
}

function deleteCart(){
	console.log("loadCart")
    let response = sendPost('deleteCart', null);
    if(response.result == 2){
        productsInCart = []
        updateListArticles()
    }
    return response;
}

function discardCart(){
    if(deleteCart().result == 2){
        window.location.reload();
    }
}

function fixTypes(array){
    console.log("fixTypes")
    array.forEach(element => {
        console.log(element)
        element.barcode = isNaN(parseInt(element.barcode)) ? null : parseInt(element.barcode);
        element.coefficient = isNaN(parseFloat(element.coefficient)) ? null : parseFloat(element.coefficient);
        element.cost = isNaN(parseFloat(element.cost)) ? null : parseFloat(element.cost);
        element.discount = isNaN(parseFloat(element.discount)) ? null : parseFloat(element.discount);
        element.idArticle = isNaN(parseInt(element.idArticle)) ? null : parseInt(element.idArticle);
        element.idHeading = isNaN(parseInt(element.idHeading)) ? null : parseInt(element.idHeading);
        element.idInventory = isNaN(parseInt(element.idInventory)) ? null : parseInt(element.idInventory);
        element.idIva = isNaN(parseInt(element.idIva)) ? null : parseInt(element.idIva);
        element.import = isNaN(parseFloat(element.import)) ? null : parseFloat(element.import);
        element.quantity = isNaN(parseFloat(element.quantity)) ? null : parseFloat(element.quantity);
        element.ivaIncluded = element.ivaIncluded == "true" ? true : false;
        if(element.ivaIncluded != configIncludeIva){ // El producto tiene IVA y la configuracion no incluye el iva o viceversa 
            //Fix import
            if(element.ivaIncluded){
                element.import = parseFloat(element.import / getIvaValue(element.idIva))
            } else {
                element.import = parseFloat(element.import * getIvaValue(element.idIva))
            }
            element.ivaIncluded = configIncludeIva
        }
    });
    return array;
}

function addArticleToCart(cantidad, objeto){ // POR CODEBAR
    console.log("addArticleToCart - " + cantidad + " - " + objeto.idArticulo)

    let importeAux = 0
    if(configIncludeIva){ // EN LA BASE SIEMPRE ESTA EL IMPORTE CON EL IVA
        importeAux = objeto.importe
    } else {
        importeAux = objeto.importe / getIvaValue(objeto.idIva)
    }
    
    let article = {
        barcode: objeto.codigoBarra,
        description: objeto.descripcion,
        coefficient: objeto.coeficiente,
        cost: objeto.costo,
        discount: objeto.descuento,
        detail: objeto.detalle,
        idArticle: objeto.idArticulo,
        idInventory: objeto.idInventario,
        idIva: objeto.idIva,
        idHeading: objeto.idRubro,
        import: importeAux,
        coin: objeto.moneda,
        brand: objeto.marca,
        unidad_venta: objeto.unidad_venta,
        ivaIncluded: configIncludeIva,
        // quantity: cantidad
        quantity: (objeto.unidad_venta == "Peso" || objeto.unidad_venta == "Litro") 
        ? parseFloat(cantidad) || 1.0
        : parseInt(cantidad) || 1
    }
    let articleAlreadyInCart = false
    let indice = productsInCart.length
    let nuevaCantidad = null;
    productsInCart.forEach((element, index) => {
        if(article.idArticle == element.idArticle){
            if(element.unidad_venta == "Peso" || element.unidad_venta == "Litro"){
                nuevaCantidad = element.quantity + parseFloat(cantidad);
            } else if(element.unidad_venta == "Unidad"){
                nuevaCantidad = element.quantity + parseInt(cantidad);
            } else {
                nuevaCantidad = element.quantity;
            }
            element.quantity = nuevaCantidad;
            // console.log(element.quantity)
            // console.log(cantidad)
            // console.log(element.quantity += parseInt(cantidad))
            // nuevaCantidad = element.quantity
            articleAlreadyInCart = true;
            indice = index
            article = element
        }
    });
    console.log(`updateArticleInCart [article: ${article.idArticle}, index: ${indice}, Nueva cantidad: ${nuevaCantidad}] `)
    let response = sendPost('updateArticleInCart', {article: article, index: indice});
    if(response.result == 2){
        productsInCart = fixTypes(response.data)
        updateListArticles()
    }
}

function insertNewArticleToCart(){ // DESDE EL BOTON DEL modal add product
	console.log("insertNewArticleToCart - ")

    if(productsInCart.length >= 80){
		showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Notificación", null);
		return;
	}

	// Obtener el tiempo actual
	// const currentTime = Date.now();

	// // Verificar si ha pasado suficiente tiempo desde la última ejecución
	// if (currentTime - lastExecutionTime < MIN_EXECUTION_INTERVAL) {
	// 	console.log("Demasiado rápido, espera un momento...");
	// 	return; // Salir de la función sin hacer nada
	// }
	
	// Actualizar el timestamp de la última ejecución
	// lastExecutionTime = currentTime;
    // console.log(configIncludeIva)
    let article = {
        barcode: null,
        description: $('#inputDescriptionProduct').val().trim() || null,
        coefficient: 0,
        cost: 0,
        // discount: parseFloat($('#inputDiscountProduct').val().trim()) || 0,
        discount: (() => {
            const discountValue = parseFloat($('#inputDiscountProduct').val().trim()) || 0;
            const priceValue = parseFloat($('#inputPriceProduct').val().trim()) || 0;
            if (configDesc) {
                // Descuento en porcentaje (0-100)
                return Math.min(Math.max(discountValue, 0), 100);
            } else {
                // Descuento en valor absoluto (0 hasta el precio del producto)
                return Math.min(Math.max(discountValue, 0), priceValue);
            }
        })(),
        detail: $('#inputDetailProduct').val().trim() || null,
        idArticle: null,
        idInventory: null,
        idIva: parseInt($('#inputTaxProduct').val().trim()) || 3,
        idHeading: null,
        import: parseFloat($('#inputPriceProduct').val().trim()) || null,
        coin: caja.moneda,
        brand: null,
        unidad_venta: $('#inputUnidadVentaProduct').val().trim() || null,
        ivaIncluded: configIncludeIva,
        quantity: (['Peso', 'Litro'].includes($('#inputUnidadVentaProduct').val().trim())) 
        ? parseFloat($('#inputCountProduct').val().trim()) || 1.0
        : parseInt($('#inputCountProduct').val().trim()) || 1
    }
    if(!article.description || !article.import){
        showReplyMessage(1, "Campos inválidos", "Notificación", "modalAddProduct");
		return;
    }
    let articleAlreadyInCart = false
    let indice = productsInCart.length
    productsInCart.forEach((element, index) => {
        if(element.idArticle == null)
            return;

        if(article.idArticle == element.idArticle){
            articleAlreadyInCart = true;
            indice = index
            article = element
        }
    });
    mostrarLoader(true)
    $('#modalAddProduct').modal('hide')
    sendAsyncPost("updateArticleInCart", {article: article, index: indice})
	.then(function(response){
		mostrarLoader(false)
		console.log(response)
		if (response.result == 2 ){
            productsInCart = fixTypes(response.data)
            updateListArticles()
		} else {
			showReplyMessage(response.result, response.message, "Nueva factura", "modalAddProduct");
		}
	})
	.catch(function(response){
		mostrarLoader(false)
		console.log("este es el catch", response);
	});

    // let response = sendPost('updateArticleInCart', {article: article, index: indice});
    // if(response.result == 2){
    //     productsInCart = fixTypes(response.data)
    //     updateListArticles()
    //     $('#modalAddProduct').modal('hide')
    // }
}

function addProductByCodeBar(barcode){
	console.log("addProductByCodeBar - " + barcode)

	if(productsInCart.length >= 80){
		showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
		return;
	}

	let data = null;
	let newBarcode = barcode;
	let newCantidad = 1;

	if (barcode.includes("*")) {
		data = barcode.split("*");
		console.log(data[0])
		console.log(data[1])
		data[0] >= 0.001 ? newCantidad = data[0] : newCantidad = 1;
		newBarcode = data[1];
	}

	let response = sendPost('addProductByCodeBar', {barcode: newBarcode});
	if(response.result == 2){
		$('#tbodyListPrice').empty();
		if( response.listResult.length > 1 ){ // SI hay mas de un producto con ese codigo de barra
			let list = response.listResult;
			firstRow = true;
			for (var i = 0; i < list.length; i++) {
				let row = createRowListPrice(list[i].idArticulo, list[i].descripcion, list[i].rubro, list[i].importe, list[i].moneda, newCantidad);
				$('#tbodyListPrice').append(row);
				if(firstRow){
					$('#tbodyListPrice tr:first').addClass('selected')
					firstRow = false
				}
			}
			openModalGetPrices("codebar", newCantidad)
		} else if( response.listResult.length == 1 ){ // Un solo producto coincide con mi codigo de barras
			let objeto = response.listResult[0];
            // console.log(objeto)
			objeto = convertImportToCorrectCoint(objeto)
			addArticleToCart(newCantidad, objeto)
		}
	}
}

function addProductById(id, quantity){ // DESDE EL BOTON DEL modalListPrice
	console.log("addProductById - " + id + " - " + quantity)
	if(productsInCart.length >= 80){
		showReplyMessage(1, "80 artículos es la cantidad máxima soportada", "Detalles", null);
		return;
	}
	let response = sendPost('getProductById', {idProduct: id});

	if(response.result == 2){
        let objeto = response.objectResult;
        // console.log(objeto)
        objeto = convertImportToCorrectCoint(objeto)
        addArticleToCart(quantity, objeto)
        $('#modalListPrice').modal('hide')
	}
}

function convertImportToCorrectCoint(article){
    console.log("convertImportToCorrectCoint")
    if (caja.moneda == article.moneda){
        return article;
    } else if(article.moneda == "USD"){
        article.importe = calculeQuote(article.importe, cotizacionDolar, "USD", "UYU")
    } else if(article.moneda == "UYU"){
        article.importe = calculeQuote(article.importe, cotizacionDolar, "UYU", "USD")
    }
    return article;
}

function updateListArticles(){ // Actualiza los articulos del carrito (VISUAL)
    console.log("updateListArticles")
    $('#tbodyDetailProducts').empty()
    productsInCart.forEach((element, index) => {
        productPrice = 0
        discountMax = 100;
        if(!configDiscountInPercentage){
            productPrice = parseFloat(element.import) - (element.discount / element.quantity);
            productPriceSummation =  parseFloat(productPrice * element.quantity)
            discountMax = productPriceSummation
        }
        else if(configDiscountInPercentage){
            productPrice =  parseFloat(element.import) * ((100 - element.discount)/ 100);
            productPriceSummation = parseFloat(productPrice * element.quantity)
        }

        let row = "<tr id='" + index +"'>";
        row += "<td class='col-4 text-left overflow-example' title='"+ element.description +"'>"+ element.description;
        if(element.detail){
            row += "<br>";
            row += "<p class='overflow-example' style='margin-bottom: 0; font-size: xx-small;'> " + element.detail + " </p>"
    
        } else {
            row += "</td>";
        }
        row += `<td class='col-1 text-left align-middle'>
                    <button class='btn btn-danger btn-sm shadow-sm align-middle' style='width: 3em;' onclick='modalBorrarArticleFromCart(${index})'>
                        <i class='fas fa-trash-alt'></i>
                    </button>
                </td>`;
        let step = (element.unidad_venta === 'Peso' || element.unidad_venta === 'Litro') ? 0.001 : 1;
        let min = (element.unidad_venta === 'Peso' || element.unidad_venta === 'Litro') ? 0.001 : 1;	
        row += `<td class='col-2 text-right align-middle'>
                    <input id='inputCount${index}' 
                        type='number' 
                        step='${step}' 
                        class='form-control form-control-sm text-right' 
                        value='${element.quantity}'
                        onblur='handleQuantityBlur(event, ${index})'
                        style='min-width: 100px;'>
                </td>`;
        
        row += `<td class='col-2 text-right align-middle'>
                    <input id='inputDiscount${index}' 
                        type='number' 
                        class='form-control form-control-sm text-right' 
                        value='${parseFloat(element.discount).toFixed(2)}' 
                        onblur='handleDiscountBlur(event, ${index})'

                        style='min-width: 100px;'>
                </td>`;
        row += `<td class='col-3 text-right align-middle' style='min-width:160px;padding-top:0;padding-bottom:0;'>
                    <span style="font-size:small;color:rgb(0 0 0 / 40%); display: block;"> ${element.quantity} x ${getFormatValue( productPrice )}</span>            
                    <span style="font-weight:700;">  ${getFormatValue( productPriceSummation )}</span>
                </td>`;
        row += "</tr>";
        $('#tbodyDetailProducts').append(row)
    });
    calcTotal()
}

function handleQuantityBlur(event, index){
    console.log("handleQuantityBlur")
    event.target.value = fixValue(event.target, index, 'quantity')
    productsInCart[index].quantity = event.target.value;
    let response = sendPost('updateArticleInCart', {article: productsInCart[index], index: index});
    if(response.result == 2){
        productsInCart = fixTypes(response.data)
        updateListArticles()
    }
}

function handleDiscountBlur(event, index){
    console.log("handleDiscountBlur")
    event.target.value = fixValue(event.target, index, 'discount')
    productsInCart[index].discount = event.target.value;
    let response = sendPost('updateArticleInCart', {article: productsInCart[index], index: index});
    if(response.result == 2){
        productsInCart = fixTypes(response.data)
        updateListArticles()
    }
}

function modalBorrarArticleFromCart(index){
    console.log("modalBorrarArticleFromCart")
	$('#textDeleteArticle').html("¿Desea eliminar '"+ productsInCart[index].description +"'?");
    $('#modalDeleteArticleFromCart').modal();
    $('#btnConfirmDelete').off('click').on('click', function(){
        let response = sendPost('deleteArticleFromCart', {index: index});
        if(response.result == 2){
            productsInCart = fixTypes(response.data)
            updateListArticles()
            $('#modalDeleteArticleFromCart').modal('hide')
        }
    })
}

function fixValue(element, index, item) {
    console.log(`NEW fixValue element: ${element.id}, value: ${element.value}, max: ${element.max}, min: ${element.min}, item: ${item}`)
    const value = parseFloat(element.value) || 0;
    let correctValue = value;
    
    if (item === 'quantity') {
        const min = productsInCart[index].unidad_venta === "Unidad" ? 1 : 0.001;
        const max = 9999;
        
        if (value <= 0) correctValue = min;
        else if (value > max) correctValue = max;
        else if (productsInCart[index].unidad_venta === "Unidad") correctValue = Math.round(value);
        
    } else if (item === 'discount') {
        const min = 0;
        const max = !configDiscountInPercentage 
            ? (productsInCart[index].import * productsInCart[index].quantity)
            : 100;
        if (value < min) correctValue = min;
        else if (value > max) correctValue = max;
        else correctValue = Math.round(value * 100) / 100; // 2 decimales
    }
    
    if (correctValue !== value) {
        console.log(`Corregido: ${value} → ${correctValue}`);
    }
    return correctValue;
}

// function showHideDetailsPrices(){
//     console.log("showHideDetailsPrices")
//     // Verificamos si está visible (usamos el primero como referencia)
//     const isVisible = !$('.detailsPrices:first').hasClass('d-none');

//     // Ocultamos o mostramos todos
//     if (isVisible) {
//         $('#detailsPricesButton').html(`Detalles <i class="ml-2 fas fa-angle-down d-flex align-items-center"></i>`);
//     } else {
//         $('#detailsPricesButton').html(`Detalles <i class="ml-2 fas fa-angle-up d-flex align-items-center"></i>`);
//     }

//     $('.detailsPrices').toggleClass('d-none');
//     $('.detailsPrices').toggleClass('d-flex');
// }

function showHideDetailsPrices(){
    console.log(`showHideDetailsPrices - Estado actual: ${detailsPricesVisible}`)
    
    // Cambiar el estado
    detailsPricesVisible = !detailsPricesVisible;
    
    if(detailsPricesVisible){
        // Usuario quiere ver detalles
        $('#detailsPricesButton').html(`Detalles <i class="ml-2 fas fa-angle-up d-flex align-items-center"></i>`);
        showDetailsPricesFields();
    } else {
        // Usuario quiere ocultar detalles
        $('#detailsPricesButton').html(`Detalles <i class="ml-2 fas fa-angle-down d-flex align-items-center"></i>`);
        $('.detailsPrices').addClass('d-none').removeClass('d-flex');
    }
}

function showDetailsPricesFields(){
    // Esta función se puede llamar desde calcTotal() también
    if(!detailsPricesVisible) return; // Solo mostrar si el usuario quiere ver detalles
    
    $('.detailsPrices').each(function() {
        const input = $(this).find('input');
        // Limpiar formato de moneda antes de parsear
        const rawValue = input.val().replace(/[$,\s]/g, '');
        const value = parseFloat(rawValue) || 0;
        
        if (value !== 0) {
            $(this).removeClass('d-none').addClass('d-flex');
        } else {
            $(this).addClass('d-none').removeClass('d-flex');
        }
    });
}