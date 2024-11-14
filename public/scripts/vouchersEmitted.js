//let dateEmitted = 0;
let lastVoucherEmittedIdFound = 0;
let payMethodSelected = 0;
let selectedTypeVoucher = 0;
let selectedDateVoucher = 0;
let selectedNumberVoucher = 0;
let selectedDocumentClient = null;
let valueBranchCompany = 0;

function loadVouchersEmitted(){
	let data =  {
		lastVoucherEmittedIdFound: lastVoucherEmittedIdFound,
		//dateEmitted: dateEmitted,
		payMethod: payMethodSelected,
		typeVoucher: selectedTypeVoucher,
		dateVoucher: selectedDateVoucher,
		numberVoucher: selectedNumberVoucher,
		documentClient: selectedDocumentClient,
		branchCompany: valueBranchCompany
	};
	////console.log(data);
	// mostrarLoader(true)
	let response = sendPost("loadVouchersEmitted", data);
	if(response.result == 2){
		if(lastVoucherEmittedIdFound != response.lastVoucherEmittedIdFound)
			lastVoucherEmittedIdFound = response.lastVoucherEmittedIdFound;

		list = response.listResult;
		let rowExist = $('#' + list[0].id).attr('id') || null;
		if(rowExist != list[0].id){
			for (var i = list.length - 1; i >= 0; i--) {
				let row = createRow(list[i]);
				$('#tbodyVouchersEmitted').append(row);
			}
		}
	}
	// mostrarLoader(false)
	resizeScreen();
}

function restartValuesTable(){
	lastVoucherEmittedIdFound = 0;
	dateEmitted = 0;
	payMethodSelected = 0;
	selectedTypeVoucher = 0;
	selectedDateVoucher = 0;
	selectedNumberVoucher = 0;
	selectedDocumentClient = null;
	$('#tbodyVouchersEmitted').empty();
}

function createRow(voucher){

	idVoucher = voucher.id
	index = voucher.indice
	documentClient = voucher.nombreCliente
	tipoCFE = voucher.tipoCFE
	serieCFE = voucher.serieCFE
	numeroCFE = voucher.numeroCFE
	dateVoucher = voucher.fechaHoraEmision
	typePay = voucher.formaPago
	typeCoin = voucher.moneda
	total = voucher.total

	if ( tipoCFE == "e-Ticket Cobranza"){
		tipoCFE = "Recibo e-Ticket";
	}
	else if ( tipoCFE == "e-Factura Cobranza"){
		tipoCFE = "Recibo e-Factura";
	}

	var nullCell = "<td class='text-right'></td>";
	var row = "";


	if ( voucher.isAnulado ){
		row = "<tr id='" + idVoucher + "' onclick='openModalVoucherClient(this)' class='voucherDgiAnulado ' >";

	}else{
		row = "<tr id='" + idVoucher + "' onclick='openModalVoucherClient(this)'>";

	}


	row += "<td class='text-right'>" + tipoCFE + " " + serieCFE + numeroCFE +"</td>";
	row += "<td class='text-right'>" + documentClient +"</td>";
	row += "<td class='text-right toHidden1'>" + dateVoucher + "</td>";
	//row += "<td class='text-right toHidden1'>" + typePay + "</td>";
	//row += "<td class='text-right toHidden1'>" + typeCoin + "</td>";
	row += "<td class='text-right toHidden1'>"+ typeCoin + "  " + total + "</td></tr>";

	return row;
}

function openModalVoucherClient(btnVoucher){
	openModalVoucher(btnVoucher, "CLIENT", "vouchersEmitted");
}

function searchVoucherNumber(inputText){
	let text = inputText.value || null;

	if(text) selectedNumberVoucher = text;
	else selectedNumberVoucher = 0;

	lastVoucherEmittedIdFound = 0;
	dateEmitted = 0;
	$('#tbodyVouchersEmitted').empty();
	loadVouchersEmitted();
}

function searchVoucherClient(inputText){
	let text = inputText.value || null;

	if(text) selectedDocumentClient = text;
	else selectedDocumentClient = null;

	lastVoucherEmittedIdFound=0;
	dateEmitted = 0;
	$('#tbodyVouchersEmitted').empty();
	loadVouchersEmitted();
}

function changePaymentMethod(selectPayMethod){
	payMethodSelected = selectPayMethod.value;
	lastVoucherEmittedIdFound = 0;
	dateEmitted = 0;
	$('#tbodyVouchersEmitted').empty();
	loadVouchersEmitted();
}

function changeTypeVoucher(selectTypeVoucher){
	lastVoucherEmittedIdFound = 0;
	dateEmitted = 0;
	let nameVoucher = selectTypeVoucher.options[selectTypeVoucher.selectedIndex].text;
	selectedTypeVoucher = selectTypeVoucher.value;
	if(nameVoucher.includes("Recibo"))
		selectedTypeVoucher += "1";

	$('#tbodyVouchersEmitted').empty();
	loadVouchersEmitted();
}

function findWithDate(){
	let iconSearch = $('#iconSearch');
	if(iconSearch.hasClass('fa-search')){
		iconSearch.removeClass('fa-search');
		iconSearch.addClass('fa-times');
		let selectedDate = document.getElementById('inputDateFilter').value || null;
		if(selectedDate){

			selectedDateVoucher = selectedDate;
			dateEmitted = 0;
			lastVoucherEmittedIdFound = 0;
			$('#tbodyVouchersEmitted').empty();
			loadVouchersEmitted();
		}else showReplyMessage(1, "Debe seleccionar una fecha para filtrar los comprobantes.", "Fecha requerida", null);
	}else if(iconSearch.hasClass('fa-times')){
		iconSearch.removeClass('fa-times');
		iconSearch.addClass('fa-search');
		selectedDateVoucher = 0;
		dateEmitted = 0;
		lastVoucherEmittedIdFound = 0;
		$('#tbodyVouchersEmitted').empty();
		loadVouchersEmitted();
	}
}

$('#modalExport').on('shown.bs.modal', function(){
	$('#inputDateInitExport').val(getPreviousMonth(true));
	$('#inputDateFinishExport').val(getLastDayMonth($('#inputDateInitExport').val()));
	$('#radioUnifyCoin').prop('checked',true).change();
});

function exportCFEs(){
	let dateInit = $('#inputDateInitExport').val() || null;
	let dateFinish = $('#inputDateFinishExport').val() || null;
	let includeReceipts = $('#includeReceipts').is(':checked');
	let unifyCoin = $('#radioUnifyCoin').is(':checked');
	let typeCFE = $('#selectTypeVoucherExport').val();

	if(dateInit){
		if(dateFinish){

			let groupByCurrency = 1;
			if(unifyCoin) groupByCurrency = 0;

			if(includeReceipts)
				includeReceipts = 1;
			else includeReceipts = 0;

			let data = {
				dateFrom: dateInit,
				dateTo: dateFinish,
				prepareFor: "Emitidos",
				groupByCurrency: groupByCurrency,
				typeVoucher: typeCFE,
				includeReceipts: includeReceipts
			}
			let response = sendPost("exportExcelCFE", data);
			if(response.result == 2){
				const linkSource = `data:` + response.format + `;base64,${ response.file }`;
				const downloadLink = document.createElement("a");
				const fileName = "Emitidos" + dateInit + "--" + dateFinish + ".xlsx";
				downloadLink.href = linkSource;
				downloadLink.download = fileName;
				downloadLink.click();
			}else showReplyMessage(response.result, response.message, "Exportar comprobantes emitidos", "modalExport");
		}else showReplyMessage(1, "Debe ingresar la fecha de inicio del período a exportar", "Campo fecha inicio requerido", "modalExport");
	}else showReplyMessage(1, "Debe ingresar la fecha final del período a exportar", "Campo fecha final requerido", "modalExport");
}

function changeBranchCompany(selectSucursal){
	valueBranchCompany = selectSucursal.value; //codigoDGI que tiene la sucursal
	//console.log(valueBranchCompany);
	lastVoucherEmittedIdFound = 0;
	dateEmitted = 0;
	$('#tbodyVouchersEmitted').empty();
	loadVouchersEmitted();
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


$('#modalExportVoucherDetails').on('shown.bs.modal', function(){
	$('#inputDateInitExportVoucherDetails').val(getPreviousMonth(true));
	$('#inputDateFinishExportVoucherDetails').val(getLastDayMonth($('#inputDateInitExportVoucherDetails').val()));
	$('#radioUnifyCoinVoucherDetails').prop('checked',true).change();

	document.getElementById("inputCheckClientExportVoucherDetails").checked = true;
	$( "#inputCheckClientExportVoucherDetails" ).trigger( "change" );
});


function exportCfesVoucherDetails(){
	let dateInit = $('#inputDateInitExportVoucherDetails').val() || null;
	let dateFinish = $('#inputDateFinishExportVoucherDetails').val() || null;
	let includeReceipts = $('#includeReceiptsVoucherDetails').is(':checked'); //incluir recibos
	let unifyCoin = $('#radioUnifyCoinVoucherDetails').is(':checked');//unificado en pesos
	let typeCFE = $('#selectTypeVoucherExportVoucherDetails').val();

	let client = null;
	let checkClient = document.getElementById("inputCheckClientExportVoucherDetails").checked

	if (!checkClient){
		client = document.getElementById("inputDatalistExportVoucherDetails").value
	}


	let groupByCurrency = 1;
	if(unifyCoin) groupByCurrency = 0;

	if(includeReceipts)
		includeReceipts = 1;
	else includeReceipts = 0;


	//bloquear boton de descarga
	//poner el progres bar


	// progressBarIdProcess = loadPrograssBar();
	// $('#progressbar h5').text("Exportando detalles de ventas...");
	$("#modalExportVoucherDetails").modal("hide");
	// $("#progressbar").modal("show");
	mostrarLoader(true)
	sendAsyncPost("exportCfesVoucherDetails", { dateInit:dateInit, dateFinish:dateFinish,
		prepareFor:"CLIENT", type:typeCFE, lastid:0, limit:0, typeMoney:groupByCurrency, receipts:includeReceipts, client:client})
	.then((response)=>{
		mostrarLoader(false)
		// stopPrograssBar(progressBarIdProcess);
		// $('#progressbar').modal("hide");
		// $('#progressbar h5').text("Descartando productos...");
		if(response.result == 2){
			window.location.href = getSiteURL() + 'downloadExcel.php?n='+response.name;
			$("#modalExportVoucherDetails").modal("show");
		}else showReplyMessage(response.result, response.message, "Detalle de ventas", "modalExportVoucherDetails");
	})
}



// function enableClientSearchExportVoucherDetails(){

// 	let val = document.getElementById("inputCheckClientExportVoucherDetails").checked
// 	let divCheckClient = document.getElementById("inputCheckClientExportVoucherDetails").parentNode
// 	let nodes = divCheckClient.childNodes
// 	for (let i = 0; i < nodes.length; i++) {
// 		if (nodes[i].nodeName === "LABEL"){
// 			if (val)
// 				nodes[i].removeAttribute("style");
// 			else
// 				nodes[i].style = "text-decoration: line-through;";
// 		}
// 	}

// 	if (val){
// 		document.getElementById("inputDatalistExportVoucherDetails").value = ""
// 		document.getElementById("inputDatalistExportVoucherDetails").disabled = true
// 		document.getElementById("inputDatalistExportVoucherDetails").readOnly = true

// 		let datalist = document.getElementById("inputListClientExportVoucherDetails");
// 		datalist.replaceChildren();
// 	}else{
// 		document.getElementById("inputDatalistExportVoucherDetails").disabled = false
// 		document.getElementById("inputDatalistExportVoucherDetails").readOnly = false
// 	}
// }

function enableClientSearchExportVoucherDetails() {
    // Using jQuery for cleaner code
    const $checkbox = $("#inputCheckClientExportVoucherDetails");
    const $label = $("#labelCheckClientExportVoucherDetails");
    const $searchContainer = $("#inputListClientExportVoucherDetails").closest('.input-group').parent().parent(); // This gets the outer div containing the search section
    const $datalist = $("#inputListClientExportVoucherDetails");
    const $searchInput = $("#inputDatalistExportVoucherDetails");
    const $mainContainer = $checkbox.closest('.form-control');

    if ($checkbox.is(":checked")) {
        // If checkbox is checked
        $label.css("text-decoration", ""); // Remove strike-through
        $searchContainer.addClass("d-none"); // Hide the entire container
        $searchInput.val(""); // Clear input value
        $datalist.empty(); // Clear datalist options
		$mainContainer.css("height", ""); // Remove the height style
    } else {
		// If checkbox is unchecked
        $label.css("text-decoration", "line-through");
        $searchContainer.removeClass("d-none"); // Show the entire container
        $searchInput.prop({
			"disabled": false,
            "readonly": false
        });
		$mainContainer.css("height", "calc(76px + .5rem)"); // Add the height style
    }
}

function getBusinessExportVoucherDetails(){

	let inputValue = document.getElementById('inputDatalistExportVoucherDetails').value;
	let datalist = document.getElementById("inputListClientExportVoucherDetails");
	if(inputValue.length >= 3){

		sendAsyncPost("getBusinessForModal",{suggestionPerson: inputValue, prepareFor: "CLIENT"})
		.then((response)=>{
			console.log(response)
			if(response.result == 2){
				datalist.replaceChildren();

				for (let i = response.listPeople.length - 1; i >= 0; i--) {
					let option = document.createElement("option");
					option.label = response.listPeople[i].name;
					option.value = response.listPeople[i].document;
					datalist.append(option);
				}
			}else if(response.result == 1){
				datalist.replaceChildren();
			}
		})
	}else{
		datalist.replaceChildren();
	}

}