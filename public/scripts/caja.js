// FILTROS
let selectedCoin = "NOTHING";
let selectedTipo = "NOTHING";
let showNotRelevant = false
let searchText = "";

// Variables globales para almacenar los saldos actuales
let saldoActualUYU = 0;
let saldoActualUSD = 0;


function showHideRelevants(check){
    console.log("showHideRelevants " + check.checked);
    showNotRelevant = check.checked;
    applyFilters();
}

function filterByTipo(option) {
    console.log("filterByTipo " + option.value);
    selectedTipo = option.value;
    applyFilters();
}

function filterByCoin(option) {
    console.log("filterByCoin " + option.value);
    selectedCoin = option.value;
    applyFilters();
}

function cleanSearchBar(){
    console.log("clean")
    $('#searchBarCleaner').addClass('d-none')
    $('#inputToSearch').val('')
    searchText = ""; // Limpiar la variable de búsqueda
    applyFilters(); // Volver a aplicar los filtros sin búsqueda
    $('#inputToSearch').focus()
}

function searchMovement(event) {
    console.log("searchMovement")
    
    let textTemp = $('#inputToSearch').val() || "";
    searchText = textTemp.toLowerCase().trim();
    
    if (searchText !== '') {
        $('#searchBarCleaner').removeClass('d-none')
        console.log("algo para buscar: " + searchText)
    } else {
        $('#searchBarCleaner').addClass('d-none')
    }
    
    // Aplicar todos los filtros incluyendo la búsqueda
    applyFilters();
}

function applyFilters() {
    // Primero mostrar todas las filas
    $('#tbodyProducts tr').removeClass('d-none');

    $('#tbodyProducts tr').each(function() {
        let row = $(this);
        let shouldHide = false;

        // Filtro por moneda
        if (selectedCoin === "UYU" && !row.hasClass('coin_UYU')) {
            shouldHide = true;
        } else if (selectedCoin === "USD" && !row.hasClass('coin_USD')) {
            shouldHide = true;
        }

        // Filtro por tipo
        if (selectedTipo === "ingreso" && !row.hasClass('movement-ingreso')) {
            shouldHide = true;
        } else if (selectedTipo === "egreso" && !row.hasClass('movement-egreso')) {
            shouldHide = true;
        }

        // Filtro por relevancia
        if (!showNotRelevant && row.hasClass('not-relevant-to-cash')) {
            shouldHide = true;
        }

        // Filtro por búsqueda de texto
        if (searchText !== '') {
            let rowText = '';

            // Agregar texto de todas las celdas
            row.find('td').each(function() {
                rowText += $(this).text().toLowerCase() + ' ';
            });
            
            // Agregar el contenido del atributo data-obs si existe
            let dataObs = row.attr('data-obs');
            if (dataObs) {
                rowText += dataObs.toLowerCase() + ' ';
            }
            
            if (!rowText.includes(searchText)) {
                shouldHide = true;
            }
        }

        // Aplicar visibilidad
        if (shouldHide) {
            row.addClass('d-none');
        }
    });
    
    // Contar filas visibles para debug
    let visibleRows = $('#tbodyProducts tr:not(.d-none)').length;
    console.log(`Filas visibles después de aplicar filtros: ${visibleRows}`);
}

function showSnapDetails(evento, id){
    console.log("showMovementModal " + evento + " " + id)
    mostrarLoader(true);
    sendAsyncPost("getSnap", {id: id})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2){
            let data = response.objectResult
            // Parsear fecha
            const fechaHora = data.fecha_hora;
            const fechaFormateada = `${fechaHora.substring(6,8)}/${fechaHora.substring(4,6)}/${fechaHora.substring(0,4)} ${fechaHora.substring(8,10)}:${fechaHora.substring(10,12)}:${fechaHora.substring(12,14)}`;
            
            // Información general
            $('#fechaHora').text(fechaFormateada)
            $('#usuario').text(data.user_name)

            // Saldos
            $('#saldoUYU').text(parseFloat(data.saldo_UYU).toLocaleString('es-UY', {minimumFractionDigits: 2}))
            $('#saldoUSD').text(parseFloat(data.saldo_USD).toLocaleString('es-US', {minimumFractionDigits: 2}))
            
            // Parsear detalle de efectivo
            const efectivoDetalle = JSON.parse(data.efectivo_detalle);
            console.log(efectivoDetalle)

            // Poblar denominaciones UYU
            const denominacionesUYU = document.getElementById('denominacionesUYU');
            denominacionesUYU.innerHTML = '';
            Object.keys(efectivoDetalle.UYU).forEach(denominacion => {
                const cantidad = efectivoDetalle.UYU[denominacion];
                const tipo = parseInt(denominacion) >= 20 ? 'billetes' : 'monedas';
                denominacionesUYU.innerHTML += `
                    <div class="denomination-row">
                        <span class="denomination-value">$U ${denominacion}</span>
                        <span class="denomination-count">${cantidad} ${tipo}</span>
                    </div>
                `;
            });

            // Poblar denominaciones USD
            const denominacionesUSD = document.getElementById('denominacionesUSD');
            denominacionesUSD.innerHTML = '';
            Object.keys(efectivoDetalle.USD).forEach(denominacion => {
                const cantidad = efectivoDetalle.USD[denominacion];
                denominacionesUSD.innerHTML += `
                    <div class="denomination-row">
                        <span class="denomination-value">US$ ${denominacion}</span>
                        <span class="denomination-count">${cantidad} billetes</span>
                    </div>
                `;
            });

            // Poblar cheques
            const chequesList = document.getElementById('chequesList');
            if (efectivoDetalle.cheques.length > 0) {
                console.log(efectivoDetalle.cheques);
                chequesList.innerHTML = '';
                efectivoDetalle.cheques.forEach(cheque => {
                    // chequesList.innerHTML += `<div class="mb-2">${cheque.id}</div>`;
                    chequesList.innerHTML += `<div class="w-100 d-flex align-items-center justify-content-between cheque-item p-3 mt-0 mb-2 border rounded cursor-pointer" 
                                data-cheque-id="${cheque.id}" 
                                data-referencia="${cheque.ref}"
                                data-deferred="${formatDate(cheque.deferred)}"
                                data-user_name="${cheque.user_name}"
                                data-titular="${cheque.holder}"
                                data-banco="${cheque.bank}"
                                data-importe="${cheque.importe}"
                                >
                                <small><strong>Titular:</strong> ${cheque.holder}</small> <small class="text-muted"> <strong>Fecha:</strong> ${formatDate(cheque.deferred)}</small> <small class="text-muted"> <strong>Banco:</strong> ${cheque.bank}</small> <span class="text-success"> <strong>$${parseFloat(cheque.importe).toFixed(2)} </strong> </span>
                        </div>`;
                });
            } else {
                chequesList.innerHTML = '<em class="text-center text-muted">No hay cheques registrados</em>';
            }

            // Abrir Modal
            $('#modalShowSnap').modal('show')
        }
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo procesar el movimiento", "Notificación", null);
		console.log("este es el catch", response);
	});
}

function showMovementModal(evento, id){
    console.log("showMovementModal " + evento + " " + id)
    mostrarLoader(true);
    sendAsyncPost("getMovement", {id: id})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2){
            populateMovimientoModal(response.objectResult);
            $('#modalShowMovement').modal('show')
        }
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo procesar el movimiento", "Notificación", null);
		console.log("este es el catch", response);
	});
}

// Función para poblar el modal con datos del movimiento
function populateMovimientoModal(data) {
    // Determinar el tipo de movimiento
    const esIngreso = data.tipo.toLowerCase() === 'ingreso';
    const tipoClase = esIngreso ? 'ingreso' : 'egreso';
    
    // Parsear fecha y hora
    const fechaHora = data.fecha_hora;
    const fechaFormateada = fechaHora.substring(6,8) + "/" + fechaHora.substring(4,6) + "/" + fechaHora.substring(0,4);
    const horaFormateada = fechaHora.substring(8,10) + ":" + fechaHora.substring(10,12) + ":" + fechaHora.substring(12,14);
    
    // Tipo de movimiento
    const tipoBadge = $('#tipoBadge');
    tipoBadge.text(data.tipo.charAt(0).toUpperCase() + data.tipo.slice(1));
    tipoBadge.removeClass('ingreso egreso').addClass(tipoClase);
    
    // Medio de pago
    $('#medioBadge').text(data.medio);
    
    // Importe
    const importeCard = $('#importeCard');
    const importeValor = $('#importeValor');
    const simboloMoneda = data.moneda === 'USD' ? 'US$' : '$U';

    const iconoFlecha = esIngreso ? 'fa-arrow-up' : 'fa-arrow-down';
    const importeFormateado = parseFloat(data.importe).toLocaleString('es-UY', {minimumFractionDigits: 2});
    
    importeCard.removeClass('ingreso egreso').addClass(tipoClase);
    importeValor.removeClass('ingreso egreso').addClass(tipoClase);
    importeValor.html('<i class="fas ' + iconoFlecha + ' mr-2"></i>' + simboloMoneda + ' ' + importeFormateado);
    
    // Detalles del movimiento
    $('#fechaMovimiento').text(fechaFormateada);
    $('#horaMovimiento').text(horaFormateada);
    
    $('#monedaMovimiento').text(data.moneda === 'USD' ? 'USD - Dólares' : 'UYU - Pesos Uruguayos');
    
    // Manejar sección de cheque (como en tu código original)
    if(data.medio == 'Cheque' || data.medio == 'Giro' || data.medio == 'Depósito') {
        let fecha_cheque = data.fecha;
        let fecha_dif_cheque = data.fecha_diferido;
        $('#sectionCheque').removeClass('d-none');
        if(data.medio == 'Cheque'){
            $('#sectionFechaDif').removeClass('d-none')
            $('#sectionTitular').removeClass('d-none')
            if(fecha_dif_cheque && fecha_dif_cheque.length >= 8) {
                $('#fechaDiferido').text(fecha_dif_cheque.substring(6, 8) + "/" + fecha_dif_cheque.substring(4, 6) + "/" + fecha_dif_cheque.substring(0, 4));
            } else {
                $('#fechaDiferido').text('No especificada');
            }
        } else {
            $('#sectionTitular').addClass('d-none')
            $('#sectionFechaDif').addClass('d-none')
        }
        $('#fechaCheque').text(fecha_cheque.substring(6, 8) + "/" + fecha_cheque.substring(4, 6) + "/" + fecha_cheque.substring(0, 4));
        $('#titularMovimiento').text(data.titular || 'No especificado');
        if(data.medio == 'Giro' || data.medio == 'Depósito')
            $('#bancoTitle').text('Red de Cobranza:')
        else
            $('#bancoTitle').text('Banco:')
        
        $('#bancoMovimiento').text(data.banco || 'No especificado');
    } else {
        $('#sectionCheque').addClass('d-none');
    }
    
    // Información adicional
    $('#observacionesMovimiento').text(data.observaciones || 'Sin observaciones');
    // $('#referenciaMovimiento').text(data.referencia || 'Sin referencia');
}

function showVoucherByRef(ref){
    console.log(`showVoucherByRef ${ref}`)
    let data = {id:ref}
    openModalVoucherFromCash(data, "CLIENT", "sale");
}

function openModalVoucherFromCash(button, prepareFor, view){
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

function openModalNewMovement(){
    console.log(`openModalNewMovement`)
    // Vaciar los inputs de texto y número
    $('#modalNewMovement input[type=number], #modalNewMovement input[type=text]').val('');
    $('#modalNewMovement').modal('show')
}

function updateInputClass(element) {
    $('#chequeSection').addClass('d-none'); // Ocultar chequeSection
    $('#efectivoSection').removeClass('d-none'); // Ocultar efectivoSection
    if (element.id === 'inputIngreso') {
        console.log('Ingreso seleccionado');
        $('#inputImporte').removeClass('input-ingreso input-egreso').addClass('input-ingreso');
        $('#egresoTypeSection').addClass('d-none'); // Ocultar div
    } else if (element.id === 'inputEgreso') {
        console.log('Egreso seleccionado');
        $('#inputEfectivo').click()
        $('#inputImporte').removeClass('input-ingreso input-egreso').addClass('input-egreso');
        $('#egresoTypeSection').removeClass('d-none'); // Mostrar div
    }
}

function keyPressNewMovement(event, value, size){
    console.log('keyPressNewMovement')

	if(event.keyCode === 13 && !event.shiftKey){
		if(event.target.id === "inputImporte")
			$('#inputObservacion').focus();
		else if(event.target.id === "inputObservacion")
			$('#btnConfirmModalNewMovement').click();
	} else if(event.keyCode === 13 && event.shiftKey){
		if(event.target.id === "inputObservacion")
			$('#inputImporte').focus();
	}

	if(value != null && value.length === size) {
		return false;
	}
}

async function updateEgresoType(element){
    if (element.id === 'inputEfectivo') {
        console.log('Efectivo seleccionado');
        $('#chequeSection').addClass('d-none'); // Ocultar chequeSection
        $('#efectivoSection').removeClass('d-none'); // // Mostrar efectivoSection
    } else if (element.id === 'inputCheque') {
        console.log('Cheque seleccionado');
        
        // Esperar a que termine la carga antes de mostrar la sección
        await loadAllChequesInCash('chequeSection')
        
        $('#efectivoSection').addClass('d-none'); // Ocultar efectivoSection
        $('#chequeSection').removeClass('d-none');  // Mostrar chequeSection
    }
}
async function showCheques(){
    $('#modalShowChequesBody').empty()
    
    // Esperar a que termine la carga
    await loadAllChequesInCash('modalShowChequesBody')
    
    if($('#modalShowChequesBody').html() != ""){   
        $('#modalShowCheques').modal('show')
    } else {
        showReplyMessage(1, "Ningún cheque en caja", "Notificación", null);
    }
}

function loadAllChequesInCash(place){
    mostrarLoader(true)
    
    // Retornar la promesa para que pueda ser esperada
    return sendAsyncPost("getAllChequesInCash", {})
    .then(function(response){
        mostrarLoader(false)
        if(response.result == 2){
            console.log(response.cheques)
            $("#"+ place).empty()

            // Recorrer el array de cheques
            response.cheques.forEach(function(chequeArray, index) {
                // Cada elemento es un array con un objeto dentro
                const cheque = chequeArray;
                
                // Crear el HTML para cada cheque
                const chequeHtml = `
                    <div class="w-100 cheque-item p-3 mr-3 ml-3 mt-0 mb-2 border rounded cursor-pointer" 
                            data-cheque-id="${cheque.id}" 
                            data-referencia="${cheque.ref}"
                            data-importe="${cheque.importe}"
                            onclick="toggleChequeSelection(this)">
                        <div class="row">
                            <div class="d-none">
                                <input type="checkbox" class="cheque-checkbox" 
                                        id="cheque_${cheque.id}" 
                                        value="${cheque.id}"
                                        onchange="updateChequeSelection(this)">
                            </div>
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Ref: ${cheque.ref_view}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-success">$${parseFloat(cheque.importe).toFixed(2)}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">${cheque.bank}</small>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">${formatDate(cheque.deferred)}</small>
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-md-6">
                                        <small><strong>Titular:</strong> ${cheque.holder}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <small><strong>Usuario:</strong> ${cheque.user_name}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ${place != 'modalShowChequesBody' ? `
                        
                        <div id="" class="egresoCheque_options d-none" onclick="event.stopPropagation();">
                            <hr>
                            <div class="row">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" checked name="type_${cheque.id}" id="egresoCheque_Efectivo_${cheque.id}" value="option1_${cheque.id}" style="cursor: pointer;" onchange="updateEgresoChequeType(this,'egresoCheque_Obs_${cheque.id}')">
                                    <label class="form-check-label no-select" for="egresoCheque_Efectivo_${cheque.id}" style="cursor: pointer;"> <i class="fas fa-money-bill"></i> Efectivo </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type_${cheque.id}" id="egresoCheque_Banco_${cheque.id}" value="option2_${cheque.id}" style="cursor: pointer;" onchange="updateEgresoChequeType(this,'egresoCheque_Obs_${cheque.id}')">
                                    <label class="form-check-label no-select" for="egresoCheque_Banco_${cheque.id}" style="cursor: pointer;"> <i class="fas fa-university"></i> Banco </label>
                                </div>
                                <input class="form-control form-control-inline shadow-sm col d-none" type="text" name="type" id="egresoCheque_Obs_${cheque.id}" autocomplete="off" >
                            </div>
                        </div>
                        ` : ''}         
                    </div>
                `;
                
                $("#" + place ).append(chequeHtml);
            });
        
            // Mostrar total de cheques cargados
            console.log(`Se cargaron ${response.cheques.length} cheques`);
        }
        return response; // Retornar la respuesta para uso posterior si es necesario
    })
    .catch(function(response){
        mostrarLoader(false)
        console.log("este es el catch", response);
        throw response; // Re-lanzar el error para que pueda ser manejado arriba
    });
}

function updateEgresoChequeType(element, input){
    console.log('updateEgresoChequeType - ' + input + ' - ' + element.value)
    if(element.value.startsWith('option2_')){
        $('#' + input).removeClass('d-none');
    } else {
        $('#' + input).addClass('d-none');
    }
}

function toggleChequeSelection(element) {
    console.log('toggleChequeSelection')
    const checkbox = $(element).find('.cheque-checkbox');
    checkbox.prop('checked', !checkbox.prop('checked'));
    updateChequeSelection(checkbox[0]);
}

// Función para manejar la selección de cheques
function updateChequeSelection(checkbox) {
    console.log('updateChequeSelection')
    const chequeItem = $(checkbox).closest('.cheque-item');
    
    if (checkbox.checked) {
        chequeItem.addClass('bg-light border-primary');
        chequeItem.find('.egresoCheque_options').removeClass('d-none');
        console.log(`Cheque seleccionado: ${chequeItem.data('cheque-id')}`);
    } else {
        chequeItem.removeClass('bg-light border-primary');
        chequeItem.find('.egresoCheque_options').addClass('d-none');
        console.log(`Cheque deseleccionado: ${chequeItem.data('cheque-id')}`);
    }
    
    // Actualizar contador de seleccionados
    updateSelectedCount();
}

// Función para contar cheques seleccionados
function updateSelectedCount() {
    console.log('updateSelectedCount')
    const selectedCount = $('#modalNewMovement .cheque-checkbox:checked').length;
    const totalAmount = getSelectedChequesTotal();
    
    console.log(`Cheques seleccionados: ${selectedCount}, Total: $${totalAmount.toFixed(2)}`);
    
    // $('#selectedInfo').text(`${selectedCount} cheques seleccionados - Total: $${totalAmount.toFixed(2)}`);
}

// Función para obtener el total de cheques seleccionados
function getSelectedChequesTotal() {
    console.log('getSelectedChequesTotal')
    let total = 0;
    $('.cheque-checkbox:checked').each(function() {
        const chequeItem = $(this).closest('.cheque-item');
        const importe = parseFloat(chequeItem.data('importe'));
        total += importe;
    });
    return total;
}

// Función para obtener IDs de cheques seleccionados
function getSelectedChequeIds() {
    console.log('getSelectedChequeIds')
    const selectedCheques = [];
    
    $('#chequeSection .cheque-checkbox:checked').each(function() {
        const chequeId = $(this).val();
        
        // Obtener el tipo seleccionado (efectivo o banco)
        const tipoSeleccionado = $(`input[name="type_${chequeId}"]:checked`).val();
        
        // Obtener la observación
        const observacion = $(`#egresoCheque_Obs_${chequeId}`).val() || '';
        
        // Determinar si es efectivo o banco
        let tipo = '';
        if(tipoSeleccionado && tipoSeleccionado.startsWith('option1_')) {
            tipo = 'efectivo';
        } else if(tipoSeleccionado && tipoSeleccionado.startsWith('option2_')) {
            tipo = 'banco';
        }
        
        selectedCheques.push({
            id: chequeId,
            tipo: tipo,
            observacion: observacion
        });
    });
    
    return selectedCheques;
}

// Función para formatear fecha (YYYYMMDD -> DD/MM/YYYY)
function formatDate(dateString) {
    console.log('formatDate ' + dateString)
    if (dateString && dateString.length === 8) {
        const year = dateString.substring(0, 4);
        const month = dateString.substring(4, 6);
        const day = dateString.substring(6, 8);
        return `${day}/${month}/${year}`;
    }
    return dateString;
}

// // Función para seleccionar todos los cheques
// function selectAllCheques() {
//     console.log('selectAllCheques')
//     $('.cheque-checkbox').prop('checked', true);
//     $('.cheque-item').addClass('bg-light border-primary');
//     updateSelectedCount();
// }

// // Función para deseleccionar todos los cheques
// function deselectAllCheques() {
//     console.log('deselectAllCheques')
//     $('.cheque-checkbox').prop('checked', false);
//     $('.cheque-item').removeClass('bg-light border-primary');
//     updateSelectedCount();
// }

function confirmMovement(){
    console.log("confirmMovement")
    if(!$('#modalNewMovement').hasClass('show'))
        return
    // Verificar que radio button (Ingreso/Egreso) está chequeado
    let isCheckedIngreso = $('#inputIngreso').is(':checked');
    let isCheckedEgreso = $('#inputEgreso').is(':checked');
    let isCheckedEfectivo = $('#inputEfectivo').is(':checked');
    let isCheckedCheque = $('#inputCheque').is(':checked');
    let chequesSelected = []
    let importTotal = 0
    let observacion = null


    if (isCheckedIngreso) {
        console.log('Movimiento Tipo: "Ingreso"');
        console.log('"Efectivo"');
        importTotal = parseFloat($('#inputImporte').val().trim()) || 0;
        importTotal = parseFloat(importTotal.toFixed(2));
        observacion = $('#inputObservacion').val().trim()
        if(importTotal <= 0){
            showReplyMessage(1, "Importe no válido", "Error", "modalNewMovement");
            return
        } else {
            sendMovement('ingreso', 'efectivo', importTotal, observacion, chequesSelected)
        }
    } else if (isCheckedEgreso) {
        console.log('Movimiento Tipo: "Egreso"');
        if(isCheckedCheque){
            console.log('"Cheque"');
            chequesSelected = getSelectedChequeIds()
            console.log(chequesSelected);
            if(chequesSelected.length < 1){
		        showReplyMessage(1, "Ningun cheque seleccionado", "Error", "modalNewMovement");
                return
            } else {
                sendMovement('egreso', 'cheque', null, null, chequesSelected)
            }
        } else {
            console.log('"Efectivo"');
            importTotal = parseFloat($('#inputImporte').val().trim()) || 0;
            importTotal = parseFloat(importTotal.toFixed(2));
            observacion = $('#inputObservacion').val().trim()
            if(importTotal <= 0){
		        showReplyMessage(1, "Importe no válido", "Error", "modalNewMovement");
                return
            } else {
                sendMovement('egreso', 'efectivo', importTotal, observacion, chequesSelected)
            }
        }
    } else {
        console.log('Ningún radio button está chequeado.');
    }
}

function sendMovement(tipo, subtipo, importe, observacion, lista ){ // subtipo (efectivo o cheque) | lista (IDs de los cheques)
    console.log("sendMovement " + tipo + " | " + subtipo + " | " + importe  + " | " + observacion + " | " + lista)
    mostrarLoader(true)
    let data = {
		tipo: tipo,
		subtipo: subtipo,
		importe: importe,
        observacion: observacion,
		cheques: JSON.stringify(lista),
	};
	sendAsyncPost("newMovement", data)
	.then(function(response){
		mostrarLoader(false)
        if(response.result == 2){
            showReplyMessageWithFunction(
                2, 
                "Movimiento realizado con éxito!", 
                "Notificación", 
                "modalNewMovement",
                function() {
                    window.location.reload()
                }
            );
        }
	})
	.catch(function(response){
		mostrarLoader(false)
            showReplyMessage(1, "Error. No se pudo procesar el movimiento", "Notificación", "modalNewMovement");
		console.log("este es el catch", response);
	});
}

// Función para Cerrar la caja en el estado actual (Guardar saldo, billetes, cheques)
function openModalCerrarCaja(){
    console.log('openModalCerrarCaja')
    cleanModalNewSnap()
    // fillInFields()
    $('#modalNewSnap').modal('show')
}

// Función para Arquear la caja en el estado actual (Contar billetes, cheques)
function openModalArqueoCaja(){
    // $('#modalNewArqueo').modal('hide')
    // CHEQUES
    sendAsyncPost("getAllChequesInCash", {})
    .then(function(response){
        mostrarLoader(false)
        if(response.result == 2){
            console.log(response.cheques)
            $("#chequesList_arqueoCaja").empty()

            // Recorrer el array de cheques
            response.cheques.forEach(function(chequeArray, index) {
                // Cada elemento es un array con un objeto dentro
                const cheque = chequeArray;
                
                // Crear el HTML para cada cheque
                const chequeHtml = `
                    <div class="w-100 d-flex align-items-center justify-content-between cheque-item p-3 mt-0 mb-2 border rounded cursor-pointer" 
                            data-cheque-id="${cheque.id}" 
                            data-referencia="${cheque.ref}"
                            data-referencia_view="${cheque.ref_view}"
                            data-deferred="${formatDate(cheque.deferred)}"
                            data-user_name="${cheque.user_name}"
                            data-titular="${cheque.holder}"
                            data-banco="${cheque.bank}"
                            data-importe="${cheque.importe}"
                            >
                            <small><strong>Titular:</strong> ${cheque.holder}</small> <small class="text-muted"> <strong>Fecha:</strong> ${formatDate(cheque.deferred)}</small> <small class="text-muted"> <strong>Banco:</strong> ${cheque.bank}</small> <span class="text-success"> <strong>$${parseFloat(cheque.importe).toFixed(2)} </strong> </span>
                    </div>
                `;
                
                $("#chequesList_arqueoCaja").append(chequeHtml);
            });
            if(response.cheques.length == 0){
                // Limpiar lista de cheques
                $('#chequesList_arqueoCaja').html('<em>No hay cheques registrados</em>').attr('class', 'text-center text-muted');
            }
        
            // Mostrar total de cheques cargados
            console.log(`Se cargaron ${response.cheques.length} cheques`);
        }
    })
    .catch(function(response){
        mostrarLoader(false)
        console.log("este es el catch", response);
    });

    // EFECTIVOS
    sendAsyncPost("getArqueo", {})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2 && response.data != null){
            response.data.UYU.forEach(element => {
                $(`#modalNewArqueo input[data-val="UYU_${element.valor}"]`).val(element.cantidad);
            });
            response.data.USD.forEach(element => {
                $(`#modalNewArqueo input[data-val="USD_${element.valor}"]`).val(element.cantidad);
            });
        }

        // Calcular y actualizar los saldos basándose en los movimientos
        const balances = updateModalBalances();
        
        // Actualizar variables globales
        saldoActualUYU = balances.saldoActualUYU;
        saldoActualUSD = balances.saldoActualUSD;
        
        // Recalcular diferencias después de llenar los valores
        calculateDifferences('modalNewArqueo');
        
        // Mostrar el modal
        $('#modalNewArqueo').modal('show');
        
        // Enfocar el primer input después de que el modal se muestre
        setTimeout(() => {
            $('#modalNewArqueo .denomination-count').first().focus();
        }, 100);
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo obtener el arqueo", "Notificación", null);
		console.log("este es el catch", response);
	});
}

// Función para manejar el input de billetes con Enter
function handleBillInput(modal, event, input) {
    if (event.key === 'Enter') {
        event.preventDefault();
        
        // Validar y corregir el valor
        let value = input.value.trim();
        
        // Si está vacío, poner 0
        if (value === '') {
            input.value = '0';
        } else {
            // Convertir a número entero
            let numValue = parseInt(value);
            
            // Si es negativo o no es un número válido, poner 0
            if (isNaN(numValue) || numValue < 0) {
                input.value = '0';
            } else {
                // Si es válido, asegurar que sea entero
                input.value = numValue.toString();
            }
        }
        
        // Recalcular diferencias
        calculateDifferences(modal);
        
        // Mover al siguiente input
        moveToNextInput(modal, input);
    }
}

// Función para moverse al siguiente input
function moveToNextInput(modal, currentInput) {
    console.log("moveToNextInput" + " - " + modal)
    const allInputs = $('#' + modal + ' .denomination-count');
    const currentIndex = allInputs.index(currentInput);
    
    if (currentIndex < allInputs.length - 1) {
        // Mover al siguiente input
        allInputs.eq(currentIndex + 1).focus().select();
    } else {
        // Si es el último, quitar el foco
        currentInput.blur();
    }
}

// Función para calcular las diferencias en tiempo real
function calculateDifferences(modal) {
    console.log("calculateDifferences" + " - " + modal)
    let totalUYU = 0;
    let totalUSD = 0;

    let denominacionesUYU = modal == 'modalNewArqueo' ? 'denominacionesUYU_arqueoCaja' : 'denominacionesUYU_cierreCaja'
    let denominacionesUSD = modal == 'modalNewArqueo' ? 'denominacionesUSD_arqueoCaja' : 'denominacionesUSD_cierreCaja'
    // console.log(denominacionesUYU)
    // Calcular total UYU
    $('#' + denominacionesUYU + ' .denomination-count').each(function() {
        const cantidad = parseInt($(this).val()) || 0;
        const valor = parseInt($(this).data('valor')) || 0;
        // console.log(` UYU: ${cantidad} * ${valor} = ${cantidad * valor}`)
        totalUYU += cantidad * valor;
    });
    
    // Calcular total USD
    $('#' + denominacionesUSD +' .denomination-count').each(function() {
        const cantidad = parseInt($(this).val()) || 0;
        const valor = parseInt($(this).data('valor')) || 0;
        // console.log(` USD: ${cantidad} * ${valor} = ${cantidad * valor}`)
        totalUSD += cantidad * valor;
    });
    
    // Calcular diferencias
    const diferenciaUYU = totalUYU - saldoActualUYU;
    const diferenciaUSD = totalUSD - saldoActualUSD;
    
    // Formatear números
    const formatCurrency = (amount) => {
        return amount.toLocaleString('es-UY', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    };
    
    // Actualizar diferencias UYU
    const $diferenciaUYU = modal == 'modalNewSnap' ? $('#diferenciaUYU_cierreCaja') : $('#diferenciaUYU_arqueoCaja')
    $diferenciaUYU.text(`Diferencia: ${diferenciaUYU > 0 ? "+" : ""}${formatCurrency((diferenciaUYU))}`);
    // console.log(diferenciaUYU)
    if (diferenciaUYU > 0) {
        $diferenciaUYU.removeClass('text-danger text-muted').addClass('text-danger');
    } else if (diferenciaUYU < 0) {
        $diferenciaUYU.removeClass('text-success text-muted').addClass('text-danger');
    } else {
        $diferenciaUYU.removeClass('text-success text-danger').addClass('text-muted');
    }
    
    // Actualizar diferencias USD
    const $diferenciaUSD = modal == 'modalNewSnap' ? $('#diferenciaUSD_cierreCaja') : $('#diferenciaUSD_arqueoCaja')
    $diferenciaUSD.text(`Diferencia: ${diferenciaUSD > 0 ? "+" : ""}${formatCurrency((diferenciaUSD))}`);
    
    if (diferenciaUSD > 0) {
        $diferenciaUSD.removeClass('text-danger text-muted').addClass('text-danger');
    } else if (diferenciaUSD < 0) {
        $diferenciaUSD.removeClass('text-success text-muted').addClass('text-danger');
    } else {
        $diferenciaUSD.removeClass('text-success text-danger').addClass('text-muted');
    }
}

function cleanArqueo(){
    // Limpiar todos los inputs de denominaciones
    // $('#modalNewArqueo').modal('hide')
    sendAsyncPost("saveArqueo", {})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2){
            $('#modalNewArqueo .denomination-count').val('');
            $('#modalNewArqueo .denomination-count').first().trigger('change').focus();
        }
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo guardar el arqueo", "Notificación", "modalNewArqueo");
		console.log("este es el catch", response);
	});
}

function confirmArqueo(){
    console.log("confirmArqueo")
    let data = {
        UYU: [],
        USD: []
    };

    // Obtener todas las denominaciones UYU
    $('#denominacionesUYU_arqueoCaja input[data-moneda="UYU"]').each(function() {
        const valor = parseInt($(this).data('valor'));
        const cantidad = parseInt($(this).val()) || 0;
        
        data.UYU.push({
            valor: valor,
            cantidad: cantidad
        });
    });

    // Obtener todas las denominaciones USD
    $('#denominacionesUSD_arqueoCaja input[data-moneda="USD"]').each(function() {
        const valor = parseInt($(this).data('valor'));
        const cantidad = parseInt($(this).val()) || 0;
        
        data.USD.push({
            valor: valor,
            cantidad: cantidad
        });
    });

    console.log(data)
    // mostrarLoader(true);

    $('#modalNewArqueo').modal('hide')
    sendAsyncPost("saveArqueo", {data: data})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2){
            showReplyMessage(2, "Arqueo guardado con éxito", "Notificación", null);
            // fillWithArqueo(response.arqueo);
            // $('#modalShowMovement').modal('show')
        }
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo guardar el arqueo", "Notificación", "modalNewArqueo");
		console.log("este es el catch", response);
	});
}

// Función mejorada para limpiar e inicializar el modal
function cleanModalNewSnap() {

    // CHEQUES
    sendAsyncPost("getAllChequesInCash", {})
    .then(function(response){
        mostrarLoader(false)
        if(response.result == 2){
            console.log(response.cheques)
            $("#chequesList_cierreCaja").empty()

            // Recorrer el array de cheques
            response.cheques.forEach(function(chequeArray, index) {
                // Cada elemento es un array con un objeto dentro
                const cheque = chequeArray;
                
                // Crear el HTML para cada cheque
                const chequeHtml = `
                    <div class="w-100 d-flex align-items-center justify-content-between cheque-item p-3 mt-0 mb-2 border rounded cursor-pointer" 
                            data-cheque-id="${cheque.id}" 
                            data-referencia="${cheque.ref}"
                            data-referencia_view="${cheque.ref_view}"
                            data-deferred="${formatDate(cheque.deferred)}"
                            data-user_name="${cheque.user_name}"
                            data-titular="${cheque.holder}"
                            data-banco="${cheque.bank}"
                            data-importe="${cheque.importe}"
                            >
                            <small><strong>Titular:</strong> ${cheque.holder}</small> <small class="text-muted"> <strong>Fecha:</strong> ${formatDate(cheque.deferred)}</small> <small class="text-muted"> <strong>Banco:</strong> ${cheque.bank}</small> <span class="text-success"> <strong>$${parseFloat(cheque.importe).toFixed(2)} </strong> </span>
                    </div>
                `;
                
                $("#chequesList_cierreCaja").append(chequeHtml);
            });
            if(response.cheques.length == 0){
                // Limpiar lista de cheques
                $('#chequesList_cierreCaja').html('<em>No hay cheques registrados</em>').attr('class', 'text-center text-muted');
            }
        
            // Mostrar total de cheques cargados
            console.log(`Se cargaron ${response.cheques.length} cheques`);
        }
    })
    .catch(function(response){
        mostrarLoader(false)
        console.log("este es el catch", response);
    });

    // EFECTIVOS

    // Limpiar todos los inputs de denominaciones
    $('#modalNewSnap .denomination-count').val('');
    
    // Limpiar spans internos
    $('#saldoInicialUYU_cierreCaja').text('');
    $('#saldoInicialUSD_cierreCaja').text('');
    $('#saldoUYU_cierreCaja').text('');
    $('#saldoUSD_cierreCaja').text('');
    
    
    
    sendAsyncPost("getArqueo", {})
	.then(function(response){
        console.log(response)
		mostrarLoader(false)
        if(response.result == 2 && response.data != null){
            response.data.UYU.forEach(element => {
                $(`#modalNewSnap input[data-val="UYU_${element.valor}"]`).val(element.cantidad);
            });
            response.data.USD.forEach(element => {
                $(`#modalNewSnap input[data-val="USD_${element.valor}"]`).val(element.cantidad);
            });
        }
        
        // Calcular y actualizar los saldos basándose en los movimientos
        const balances = updateModalBalances();
        
        // Actualizar variables globales
        saldoActualUYU = balances.saldoActualUYU;
        saldoActualUSD = balances.saldoActualUSD;
        
        // Resetear diferencias
        calculateDifferences('modalNewSnap');
        
        // Enfocar el primer input
        setTimeout(() => {
            $('#modalNewSnap .denomination-count').first().focus();
        }, 100);
	})
	.catch(function(response){
		mostrarLoader(false)
        showReplyMessage(1, "Error. No se pudo obtener el arqueo", "Notificación", null);
		console.log("este es el catch", response);
	});
    
}

// Función para actualizar los valores en el modal
function updateModalBalances() {
    const balances = calculateBalances();
    
    // Formatear números con comas para miles
    const formatCurrency = (amount) => {
        return amount.toLocaleString('es-UY', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    };
    
    // Actualizar saldos iniciales en las tarjetas
    $('#modalNewSnap .saldo-card h4').eq(0).html(`$U ${formatCurrency(balances.saldoInicialUYU)}<span id="saldoInicialUYU_cierreCaja"></span>`);
    $('#modalNewSnap .saldo-card h4').eq(1).html(`US$ ${formatCurrency(balances.saldoInicialUSD)}<span id="saldoInicialUSD_cierreCaja"></span>`);
    $('#modalNewArqueo .saldo-card h4').eq(0).html(`$U ${formatCurrency(balances.saldoInicialUYU)}<span id="saldoInicialUYU_arqueoCaja"></span>`);
    $('#modalNewArqueo .saldo-card h4').eq(1).html(`US$ ${formatCurrency(balances.saldoInicialUSD)}<span id="saldoInicialUSD_arqueoCaja"></span>`);
    
    // Actualizar saldos actuales en los spans específicos
    $('#saldoActualUYU_display').text(formatCurrency(balances.saldoActualUYU));
    $('#saldoActualUSD_display').text(formatCurrency(balances.saldoActualUSD));
    $('#saldoActualUYUArq_display').text(formatCurrency(balances.saldoActualUYU));
    $('#saldoActualUSDArq_display').text(formatCurrency(balances.saldoActualUSD));
    
    console.log('Saldos calculados:', balances);
    
    return balances;
}

// Función para calcular balances (debes tener esta función ya definida)
function calculateBalances() {
    let saldoInicialUYU = 0;
    let saldoInicialUSD = 0;
    let saldoActualUYU = 0;
    let saldoActualUSD = 0;
    
    // Buscar el último cierre de caja (movement-snap) para obtener saldos iniciales
    const lastSnap = $('#tbodyProducts tr.movement-snap').first();
    if (lastSnap.length > 0) {
        saldoInicialUYU = parseFloat(lastSnap.data('uyu')) || 0;
        saldoInicialUSD = parseFloat(lastSnap.data('usd')) || 0;
    }
    
    // Inicializar saldos actuales con los iniciales
    saldoActualUYU = saldoInicialUYU;
    saldoActualUSD = saldoInicialUSD;
    
    // Procesar todos los movimientos relevant-to-cash posteriores al último cierre
    $('#tbodyProducts tr.relevant-to-cash').each(function() {
        const $row = $(this);
        const isIngreso = $row.hasClass('movement-ingreso');
        const isEgreso = $row.hasClass('movement-egreso');
        
        // Verificar el tipo de movimiento usando data-medio - solo procesar Efectivo
        const tipoMovimiento = $row.data('medio');
        if (tipoMovimiento !== 'Efectivo') {
            return; // Saltar cheques, giros, depósitos, etc.
        }
        
        // Obtener datos directamente de los data attributes
        const monto = parseFloat($row.data('importe')) || 0;
        const moneda = $row.data('moneda');
        
        if (moneda === 'UYU') {
            if (isIngreso) {
                saldoActualUYU += monto;
            } else if (isEgreso) {
                saldoActualUYU -= monto;
            }
        } else if (moneda === 'USD') {
            if (isIngreso) {
                saldoActualUSD += monto;
            } else if (isEgreso) {
                saldoActualUSD -= monto;
            }
        }
    });
    
    return {
        saldoInicialUYU: saldoInicialUYU,
        saldoInicialUSD: saldoInicialUSD,
        saldoActualUYU: saldoActualUYU,
        saldoActualUSD: saldoActualUSD
    };
}

function extraerNumero(selector) {
    let texto = $(selector).text();
    // Remover todo excepto dígitos, comas, puntos y signos
    let limpio = texto.replace(/[^\d,.\-+]/g, '');
    // Reemplazar puntos de miles y coma decimal
    limpio = limpio.replace(/\./g, '').replace(',', '.');
    return parseFloat(limpio) || 0;
}

function confirmNewSnap(){ // HERE HERE HERE HERE HERE SEGUIR SEGUIR SEGUIR AQUI

    let diferenciaUYU = extraerNumero('#diferenciaUYU_cierreCaja')
    console.log(diferenciaUYU)
    let diferenciaUSD = extraerNumero('#diferenciaUSD_cierreCaja')
    console.log(diferenciaUSD)
    if(diferenciaUYU >= 1 || diferenciaUYU <= -1 || diferenciaUSD >= 1 || diferenciaUSD <= -1){
        showReplyMessage(1, "Error. Las diferencias deben ser 0", "Notificación", "modalNewSnap");
        return;
    }

    // obtener el efectivo
    let UYU = []
    let USD = []
    $('#denominacionesUYU_cierreCaja .denomination-count').each(function() {
        const cantidad = parseInt($(this).val()) || 0;
        const valor = parseInt($(this).data('valor')) || 0;
        UYU.push({[valor]: cantidad})
    });
    $('#denominacionesUSD_cierreCaja .denomination-count').each(function() {
        const cantidad = parseInt($(this).val()) || 0;
        const valor = parseInt($(this).data('valor')) || 0;
        USD.push({[valor]: cantidad})

    });

    // obtener todos los movimientos que van en el snap
    let movimientos = []
    $('#tbodyProducts tr').each(function() {
        let row = $(this);
        if (!row.hasClass('movement-snap')) { // Todos los que no son el ultimo snap
            movimientos.push(row.attr('id'));
        }
    });
    
    // obtener los cheques
    let cheques = []
    $('#chequesList_cierreCaja div').each(function() {
        cheques.push($(this).data('cheque-id'));
    });

    // los saldos del snap
    let SALDOS = updateModalBalances();
    
    console.log(movimientos)
    console.log(cheques)
    console.log(UYU)
    console.log(USD)
    console.log(SALDOS.saldoActualUYU)
    console.log(SALDOS.saldoActualUSD)

    mostrarLoader(true)
    let data = {
		movimientos: movimientos,
		cheques: cheques,
		efectivo: {
            UYU: UYU,
            USD: USD
	    },
        saldos: {
            UYU: SALDOS.saldoActualUYU,
            USD: SALDOS.saldoActualUSD,
        }
	};
    console.log(data)

	sendAsyncPost("newSnap", data)
	.then(function(response){
		mostrarLoader(false)
        console.log(response)
        if(response.result == 2){
            showReplyMessageWithFunction(
                2, 
                "Cierre de caja realizado con éxito!", 
                "Notificación", 
                "modalNewSnap",
                function() {
                    window.location.reload()
                }
            );
        }
	})
	.catch(function(response){
		mostrarLoader(false)
            showReplyMessage(1, "Error. No se pudo procesar el cierre", "Notificación", "modalNewSnap");
		console.log("este es el catch", response);
	});
}