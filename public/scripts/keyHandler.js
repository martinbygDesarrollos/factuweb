let barcode = ""
$(document).keydown(function(e) {
    // Verificar si el cargador está activo, en ese caso no hacer nada
    if($('.loaderback').css('display') == 'block' && $('.loader').css('display') == 'block'){
        return;
    }
    
    // Obtener el elemento que tiene el foco actualmente
    const elementFocused = document.activeElement;
    const code = (e.keyCode ? e.keyCode : e.which);
    const character = e.key;
    
    // Verificar primero si el foco está en algún modal
    if (isElementInModal(elementFocused)) {
        handleModalKeyEvents(elementFocused, code, e);
    } else {
        handleBodyKeyEvents(code, character, e);
    }
    
    /* else if (elementFocused.tagName == "BODY") {
        // Si el foco está en el BODY, manejar eventos para el escáner de código de barras
        handleBodyKeyEvents(code, character, e);
    } else if (elementFocused.closest('#clientSelection')) {
        // Manejar eventos en la sección de selección de cliente
        handleClientSelectionKeyEvents(code, e);
    }*/
});

/**
 * Verifica si el elemento está dentro de algún modal Y si ese modal está visible
 * @param {HTMLElement} element - Elemento a verificar
 * @returns {boolean} - Verdadero si está en un modal visible
 */
function isElementInModal(element) {
    console.log("isElementInModal : " + element.id);

    const modals = [
        '#modalSetClientByButton',
        '#modalSetClient',
        '#modalListPrice',
        '#modalAddProduct',
        '#modalResponse',
        '#modalSetTypeVoucher',
        '#modalDeleteArticleFromCart',
        '#modalSetPayments',
        '#modalInsertNewPaymentMethod',
        '#modalConfigMethodPayment',
        '#modalPOSPayment',
        '#modalPOSDEV',
        '#modalCancelVoucher'
        // Agregar aquí otros modales que puedan existir
    ];

    return modals.some(modalSelector => {
        const modal = element.closest(modalSelector);
        if (modal === null) {
            return false; // El elemento no está dentro de este modal
        }
        
        // Verificar si el modal está visible usando Bootstrap
        return $(modal).hasClass('show') || $(modal).is(':visible');
    });
}

/**
* Maneja eventos de teclado cuando el foco está en el body principal
* @param {number} keyCode - Código de la tecla presionada
* @param {string} character - Carácter de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleBodyKeyEvents(keyCode, character, event) {
    console.log("handleBodyKeyEvents")

    if (keyCode == 13) { // Enter
        event.preventDefault();
        event.stopPropagation();
        
        if (barcode != "") {
            if (barcode.length > 0) {
                addProductByCodeBar(barcode);
            }
            barcode = "";
        } else {
            $('#continueSaleBtn').click();
        }
    } else if (keyCode == 107) { // Tecla +
        if (event.shiftKey) { // Shift + +
            openModalGetPrices("normal", 1);
        } else { // Solo +
            openModalAddProduct();
        }
    } else if (keyCode == 35) { // Tecla Fin
        if (event.ctrlKey) { // Ctrl + Fin
            $('#confirmSaleBtn').click();
        } else { // Solo Fin
            $('#fastSaleConfirm').click();
        }
    } else if (keyCode >= 37 && keyCode <= 40) { // Teclas de flecha
        // Check if any input element is focused
        const activeElement = document.activeElement;
        const isInputFocused = activeElement && (
            activeElement.tagName === 'INPUT' || 
            activeElement.tagName === 'TEXTAREA' || 
            activeElement.tagName === 'SELECT' ||
            activeElement.isContentEditable
        );
        // Only handle arrow keys if no input is focused
        if (!isInputFocused) {
            handleArrowKeysInProductTable(keyCode);
        }
        // handleArrowKeysInProductTable(keyCode);
    } else if (keyCode == 46) { // Tecla Supr/Delete
        if (event.ctrlKey) { // Ctrl + Supr
            $('#discardSales').click();
        } else { // Solo Supr
            let $row = $('#tbodyDetailProducts tr.selected');
            if ($row.length) {
                $row.find('.fa-trash-alt').parent().click();
            }
        }
    } else if (keyCode == 45) { // Tecla Insert
        $('#buttonSetClient').click()
    } else if (keyCode == 27) { // Escape
        event.preventDefault();
        barcode = ""
    } else if (character.length == 1 && keyCode != 32) { // Es un carácter (concatenar al código de barras) distinto de espacio
        barcode = barcode + character;
    }
}

/**
* Maneja eventos de teclado dentro de modales
* @param {HTMLElement} element - Elemento con foco
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalKeyEvents(element, keyCode, event) {
    // Determinar qué modal tiene el foco y manejar sus eventos específicos
    if (element.closest('#modalSetClientByButton')) {
        handleModalSetClientByButtonKeyEvents(keyCode, event);
    } else if (element.closest('#modalListPrice')) {
        handleModalListPriceKeyEvents(keyCode, event);
    } else if (element.closest('#modalAddProduct')) {
        handleModalAddProductKeyEvents(keyCode, event);
    } else if (element.closest('#modalResponse')) {
        handleModalResponseKeyEvents(keyCode, event);
    } else if (element.closest('#modalDeleteArticleFromCart')) {
        handleModalDeleteArticleFromCartKeyEvents(keyCode, event);
    } else if (element.closest('#modalSetClient')) {
        handleModalSetClientKeyEvents(keyCode, event);
    } else if (element.closest('#modalSetTypeVoucher')) {
        handleModalSetTypeVoucherKeyEvents(keyCode, event);
    } else if (element.closest('#modalSetPayments')) {
        handleModalSetPaymentsKeyEvents(keyCode, event);
    } else if (element.closest('#modalInsertNewPaymentMethod')) {
        handleModalInsertNewPaymentMethodKeyEvents(keyCode, event);
    } else if (element.closest('#modalConfigMethodPayment')) {
        handleModalConfigMethodPaymentKeyEvents(keyCode, event);
    } else if (element.closest('#modalPOSPayment')) {
        handleModalPOSPaymentKeyEvents(keyCode, event);
    } else if (element.closest('#modalPOSDEV')) {
        handleModalPOSDEVKeyEvents(keyCode, event);
    } else if (element.closest('#modalCancelVoucher')) {
        handleModalCancelVoucherKeyEvents(keyCode, event);
    }
    /*if (element.closest('#modalSetClient')) {
        handleModalSetClientKeyEvents(keyCode, event);
    } else if (element.closest('#modalSetPayments')) {
        handleModalSetPaymentsKeyEvents(keyCode, event);
    } else if (element.closest('#modalInsertNewPaymentMethod')) {
        handleModalInsertNewPaymentMethodKeyEvents(keyCode, event);
    } else if (element.closest('#modalListPrice')) {
        handleModalListPriceKeyEvents(keyCode, event);
    }*/
}

/**
* Maneja eventos de teclado en la tabla de productos usando las flechas
* @param {number} keyCode - Código de la tecla presionada
*/
function handleArrowKeysInProductTable(keyCode) {
    console.log("handleArrowKeysInProductTable")

    let $rows = $('#tbodyDetailProducts tr');
    let currentRowIndex = $rows.index($rows.filter('.selected'));
    
    switch(keyCode) {
        case 38: // Flecha arriba
            if (currentRowIndex > 0) {
                $rows.removeClass('selected');
                $rows.eq(currentRowIndex - 1).addClass('selected');
            }
            break;
        case 40: // Flecha abajo
            if (currentRowIndex < $rows.length - 1) {
                $rows.removeClass('selected');
                $rows.eq(currentRowIndex + 1).addClass('selected');
            }
            break;
        // Casos 37 (izquierda) y 39 (derecha) pueden implementarse si se necesitan
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalListPriceKeyEvents(keyCode, event) {
    console.log("handleModalListPriceKeyEvents")
    if (keyCode == 13) { // Enter
        let $selectedRow = $('#tbodyListPrice tr.selected');
        if ($selectedRow.length) {
            $selectedRow.find('button').click();
        }
    } else if (keyCode >= 37 && keyCode <= 40) { // Teclas de flecha
        let $rows = $('#tbodyListPrice tr');
        let currentRowIndex = $rows.index($rows.filter('.selected'));
        
        switch(keyCode) {
            case 38: // Flecha arriba
                if (currentRowIndex > 0) {
                    $rows.removeClass('selected');
                    $rows.eq(currentRowIndex - 1).addClass('selected');
                }
                break;
            case 40: // Flecha abajo
                if (currentRowIndex < $rows.length - 1) {
                    $rows.removeClass('selected');
                    $rows.eq(currentRowIndex + 1).addClass('selected');
                }
                break;
            // Casos 37 (izquierda) y 39 (derecha) pueden implementarse si se necesitan
        }
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalAddProductKeyEvents(keyCode, event) {
    console.log("handleModalAddProductKeyEvents")
    if (keyCode == 13 && event.target.id == "inputDescriptionProduct") { // Enter en Descripcion
        // event.preventDefault();
        let matchingOption = $(`#listProducts option[value="${event.target.value.trim()}"]`);
        if (matchingOption.length > 0 ){
            // Hay una coincidencia exacta del datalist
            let productId = matchingOption.attr('data-id');
            selectItem(productId, 1);   
            // event.preventDefault();
        }
        console.log(`'${event.target.value.trim()}'`)
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalResponseKeyEvents(keyCode, event) {
    console.log("handleModalResponseKeyEvents")
    
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalCancelVoucherKeyEvents(keyCode, event) {
    console.log("handleModalCancelVoucherKeyEvents")
    
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalConfigMethodPaymentKeyEvents(keyCode, event) {
    console.log("handleModalConfigMethodPaymentKeyEvents - " + event.target.type + " - " + keyCode)

    let matchingInputEditable = $(event.target).is('input[type="text"]') || $(event.target).is('input[type="date"]') || null
    if (keyCode == 27 && !matchingInputEditable) { // Escape
        // Check if any input element is focused
        const activeElement = document.activeElement;
        const isInputFocused = activeElement && (
            activeElement.tagName === 'INPUT' || 
            activeElement.tagName === 'TEXTAREA' || 
            activeElement.tagName === 'SELECT' ||
            activeElement.isContentEditable
        );
        // Only handle arrow keys if no input is focused
        if (!isInputFocused) {
            event.preventDefault();
            $('#btnCancelConfigMethodPayment').click()
        }
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalDeleteArticleFromCartKeyEvents(keyCode, event) {
    console.log("handleModalDeleteArticleFromCartKeyEvents")
    if (keyCode == 13) { // Enter
        event.preventDefault();
        $('#btnConfirmDelete').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalPOSPaymentKeyEvents(keyCode, event) {
    console.log("handleModalPOSPaymentKeyEvents")
    if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#btnCancelPOSPayment').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalPOSDEVKeyEvents(keyCode, event) {
    console.log("handleModalPOSDEVKeyEvents")
    
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalSetTypeVoucherKeyEvents(keyCode, event) {
    console.log("handleModalSetTypeVoucherKeyEvents")
    if (keyCode == 32 && ($('#selectTypeVoucher').val() == "101_credito" || $('#selectTypeVoucher').val() == "111_credito")) { // Espacio
        event.preventDefault();
        $('#inputNotUseExpirationDate').click()
    } else if (keyCode == 13) { // Enter
        event.preventDefault();
        $('#btnConfirmTypeVoucher').click()
    } else if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#btnCancelTypeVoucher').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalSetClientKeyEvents(keyCode, event) {
    console.log("handleModalSetClientKeyEvents")
    if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#setConsumidorFinal').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalSetPaymentsKeyEvents(keyCode, event) {
    console.log("handleModalSetPaymentsKeyEvents - " + event.target.type + " - " + keyCode)

    let matchingInputEditable = $(event.target).is('input[type="text"]') || null

    if (keyCode == 45 || (keyCode == 107)) { // Insert o +
        event.preventDefault();
        $('#btnAddPayment').click()
    } else if ((keyCode >= 37 && keyCode <= 40) && !matchingInputEditable ) { // Teclas de flecha
        let $rows = $('#containerPayments .row').not('.payments-header');
        let currentRowIndex = $rows.index($rows.filter('.selected'));
        
        // Check if any input element is focused
        const activeElement = document.activeElement;
        const isInputFocused = activeElement && (
            activeElement.tagName === 'INPUT' || 
            activeElement.tagName === 'TEXTAREA' || 
            activeElement.tagName === 'SELECT' ||
            activeElement.isContentEditable
        );
        // Only handle arrow keys if no input is focused
        if (!isInputFocused) {
            switch(keyCode) {
                case 38: // Flecha arriba
                    if (currentRowIndex > 0) {
                        $rows.removeClass('selected');
                        $rows.eq(currentRowIndex - 1).addClass('selected');
                    }
                    break;
                case 40: // Flecha abajo
                    if (currentRowIndex < $rows.length - 1) {
                        $rows.removeClass('selected');
                        $rows.eq(currentRowIndex + 1).addClass('selected');
                    }
                    break;
                // Casos 37 (izquierda) y 39 (derecha) pueden implementarse si se necesitan
            }
        }

    } else if (keyCode == 46) { // Tecla Supr/Delete
        let $row = $('#containerPayments .row.selected:not(.payments-header)');
        if ($row.length) {
            $row.find('.fa-trash-alt').parent().click();
        }
    } else if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#modalSetPaymentsbtnCancelSale').click()
    } else if (keyCode == 13) { // Enter
        event.preventDefault();
        $('#modalSetPaymentsbtnConfirmSale').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalInsertNewPaymentMethodKeyEvents(keyCode, event) {
    console.log("handleModalInsertNewPaymentMethodKeyEvents")
    if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#modalInsertNewPaymentMethodButtonCancel').click()
    } else if (keyCode == 13 && event.target.id != "modalInsertNewPaymentMethodOptions" && !event.shiftKey) { // Enter
        event.preventDefault();
        $('#modalInsertNewPaymentMethodButtonConfirm').click()
    }
}

/**
* Maneja eventos de teclado en el modal de lista de precios
* @param {number} keyCode - Código de la tecla presionada
* @param {KeyboardEvent} event - Evento original
*/
function handleModalSetClientByButtonKeyEvents(keyCode, event) {
    console.log("handleModalSetClientByButtonKeyEvents")
    if (keyCode == 27) { // Escape
        event.preventDefault();
        $('#setConsumidorFinalSetClientByButton').click()
    }
}

// const countNonRemovedItems = (items) => {
//     return items.filter(item => 
//         item.removed !== true && // checks for boolean true
//         item.removed !== "true" // checks for string "true"
//     ).length;
// };

$('#inputTextToSearchPrice').on('keyup', function(e) { // esto para evitar disparar el evento de change cuando apreto una flecha, ya que ahora las flechas mueven el selected
    let code = (e.keyCode ? e.keyCode : e.which);
    
    // Check if the key is an arrow key
    if(code >= 37 && code <= 40) {
        // Prevent getListPrice() for arrow keys
        return;
    }
    
    // Call original change logic
    this.onchange();
});

function keyPressAddClient(keyPress, value, size){
	console.log("keyPressAddClient");
	if(keyPress.keyCode == 13 && !keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputTextToSearchClient")
			$('#inputNameClient').focus();
		if(keyPress.srcElement.id == "inputNameClient")
			$('#inputDocumentClient').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient")
			$('#inputPhoneClient').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient")
			$('#inputEmailClient').focus();
		else if(keyPress.srcElement.id =="inputEmailClient")
			$('#inputAddressClient').focus();
		else if(keyPress.srcElement.id == "inputAddressClient")
			$('#inputCityClient').focus();
		else if(keyPress.srcElement.id == "inputCityClient")
			$('#inputDepartmentClient').focus();
		else if(keyPress.srcElement.id == "inputDepartmentClient")
			$('#btnConfirmSetClient').click();
		if(keyPress.srcElement.id == "inputTextToSearchClient_SetClientByButton")
			$('#inputNameClient_SetClientByButton').focus();
		if(keyPress.srcElement.id == "inputNameClient_SetClientByButton")
			$('#inputDocumentClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient_SetClientByButton")
			$('#inputPhoneClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient_SetClientByButton")
			$('#inputEmailClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputEmailClient_SetClientByButton")
			$('#inputAddressClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputAddressClient_SetClientByButton")
			$('#inputCityClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputCityClient_SetClientByButton")
			$('#inputDepartmentClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputDepartmentClient_SetClientByButton")
			$('#btnConfirmSetClientByButton').click();

	}
	else if(keyPress.keyCode == 13 && keyPress.shiftKey){
		if(keyPress.srcElement.id == "inputDepartmentClient")
			$('#inputCityClient').focus();
		else if(keyPress.srcElement.id == "inputCityClient")
			$('#inputAddressClient').focus();
		else if(keyPress.srcElement.id == "inputAddressClient")
			$('#inputEmailClient').focus();
		else if(keyPress.srcElement.id =="inputEmailClient")
			$('#inputPhoneClient').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient")
			$('#inputDocumentClient').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient")
			$('#inputNameClient').focus();
		else if(keyPress.srcElement.id == "inputNameClient")
			$('#inputTextToSearchClient').focus();
		if(keyPress.srcElement.id == "inputDepartmentClient_SetClientByButton")
			$('#inputCityClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputCityClient_SetClientByButton")
			$('#inputAddressClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputAddressClient_SetClientByButton")
			$('#inputEmailClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputEmailClient_SetClientByButton")
			$('#inputPhoneClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputPhoneClient_SetClientByButton")
			$('#inputDocumentClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id =="inputDocumentClient_SetClientByButton")
			$('#inputNameClient_SetClientByButton').focus();
		else if(keyPress.srcElement.id == "inputNameClient_SetClientByButton")
			$('#inputTextToSearchClient_SetClientByButton').focus();
	}
	else if(value != null && value.length == size) {
		return false;
	}
}

function keyPressAddDetail(event, value, size){
    console.log('keyPressAddDetail')
	if(event.keyCode === 13 && !event.shiftKey){
		if(event.target.id === "inputCountProduct")
			$('#inputDescriptionProduct').focus();
		else if(event.target.id === "inputDescriptionProduct"){
			$('#inputDiscountProduct').focus();
        } else if(event.target.id === "inputDiscountProduct"){
			$('#inputPriceProduct').focus();
        }
		else if(event.target.id === "inputPriceProduct")
			$('#inputDetailProduct').focus();
		else if(event.target.id === "inputDetailProduct")
			$('#inputUnidadVentaProduct').focus();
		else if(event.target.id === "inputUnidadVentaProduct"){
			if($('#btnConfirmAddDetail').prop('disabled', false))
                $('#btnConfirmAddDetail').click();
        }
	}

	else if(event.keyCode === 13 && event.shiftKey){
		if(event.target.id === "inputDetailProduct")
			$('#inputPriceProduct').focus();
		else if(event.target.id === "inputPriceProduct")
			$('#inputDiscountProduct').focus();
		else if(event.target.id === "inputDiscountProduct")
			$('#inputDescriptionProduct').focus();
		else if(event.target.id === "inputDescriptionProduct")
			$('#inputCountProduct').focus();
		else if(event.target.id === "inputUnidadVentaProduct")
			$('#inputDetailProduct').focus();
	}

	if(value != null && value.length === size) {
		return false;
	}
}

function keyPressConfigMethodPayment(event, value, size){
	console.log("keyPressConfigMethodPayment - " + event.target.id + " - " + event.keyCode)

    // Lista de IDs en el orden deseado
    const fieldIds = [
        'input-configMethod-banco',
        'input-configMethod-fecha', 
        'input-configMethod-fechaDiferido',
        'input-configMethod-titular',
        'input-configMethod-observaciones'
    ];

    // Filtrar solo los campos cuyo contenedor form-group esté visible
    const visibleFields = fieldIds.filter(id => {
        const $input = $('#' + id);
        return $input.length > 0 && !$input.closest('.form-group').hasClass('d-none');
    });
    console.log(visibleFields)
    const currentIndex = visibleFields.indexOf(event.target.id);
    console.log(visibleFields[currentIndex - 1])

	if(event.keyCode === 13 && !event.shiftKey){
        if (currentIndex < visibleFields.length - 1) {
            $('#' + visibleFields[currentIndex + 1]).trigger('focus');
        } else {
            // Si es el último campo, hacer click al botón
            // $('#modalConfigMethodPayment').modal('hide')
            $('#btnConfirmConfigMethodPayment').click();
        }
	}
	else if(event.keyCode === 13 && event.shiftKey){
        // Mover al campo anterior
        if (currentIndex > 0) {
            $('#' + visibleFields[currentIndex - 1]).focus();
        }
	}
	if(value != null && value.length === size) {
		return false;
	}
}

function keyPressSetPayment(event, value, size){
	console.log("keyPressSetPayment - " + event.target.id + " - " + event.keyCode)
	if(event.keyCode === 13 && !event.shiftKey){
		if(event.target.id === "modalInsertNewPaymentMethodOptions")
            $('#modalInsertNewPaymentMethodAmount').focus();
        else if(event.target.id === "modalInsertNewPaymentMethodAmount")
			$('#modalInsertNewPaymentMethodButtonConfirm').click();
	}

	else if(event.keyCode === 13 && event.shiftKey){
		if(event.target.id === "modalInsertNewPaymentMethodAmount")
			$('#modalInsertNewPaymentMethodOptions').focus();
	}
}

function keyPressSetTypeVoucher(event, value, size){
	console.log("keyPressSetTypeVoucher")
    if(event.keyCode === 13 && !event.shiftKey){
		if(event.target.id === "selectTypeVoucher"){
            console.log(event.target.options[event.target.selectedIndex].value)
            $('#btnConfirmTypeVoucher').click();
		}
	}

	else if(event.keyCode === 13 && event.shiftKey){
        if(event.target.id === "inputNotUseExpirationDate")
			$('#inputNotUseExpirationDate').focus();
		else if(event.target.id === "inputNotUseExpirationDate")
			$('#selectTypeVoucher').focus();
	}

	if(value != null && value.length === size) {
		return false;
	}
}