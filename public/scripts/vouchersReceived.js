let dateReceived = 0;
let payMethodSelected = 0;
let selectedTypeVoucher = 0;
let selectedDateVoucher = 0;
let selectedNumberVoucher = 0;
let selectedDocumentClient = null;

function loadVouchersReceived(){

	let data = {
		dateReceived: dateReceived,
		payMethod: payMethodSelected,
		typeVoucher: selectedTypeVoucher,
		dateVoucher: selectedDateVoucher,
		numberVoucher: selectedNumberVoucher,
		documentProvider: selectedDocumentClient
	};

	let response = sendPost("loadVouchersReceived", data);
	if(response.result == 2){
		if(dateReceived != response.dateReceived)
			dateReceived = response.dateReceived;
		list = response.listResult;
		let rowExist = $('#' + list[0].id).attr('id') || null;
		if(rowExist != list[0].id){
			for (var i = list.length - 1; i >= 0; i--) {
				let row = createRow(list[i].id, list[i].indice, list[i].razonSocial, list[i].tipoCFE, list[i].serieCFE, list[i].numeroCFE, list[i].fecha, list[i].formaPago, list[i].moneda ,list[i].total);
				$('#tbodyVouchersReceived').append(row);
			}
		}
	}
	resizeScreen();
}

function createRow(idVoucher, index, documentProvider, tipoCFE, serieCFE, numeroCFE, dateVoucher, typePay, typeCoin, total){
	var nullCell = "<td class='text-right'></td>";
	var row = "<tr id='" + idVoucher + "' onclick='openModalVoucherProvider(this)'>";

	row += "<td class='text-right'>" + tipoCFE + " " + serieCFE + numeroCFE +"</td>";
	row += "<td class='text-right'>" + documentProvider + "</td>";
	row += "<td class='text-right toHidden1'>" + dateVoucher + "</td>";
	//row += "<td class='text-right toHidden1'>" + typePay + "</td>";
	//row += "<td class='text-right toHidden1'>" + typeCoin + "</td>";
	row += "<td class='text-right toHidden1'>"+ typeCoin +"  "+ total + "</td></tr>";

	return row;
}

function openModalVoucherProvider(btnVoucher){
	openModalVoucher(btnVoucher, "PROVIDER", null);
}

function searchVoucherNumber(inputText){
	let text = inputText.value || null;

	if(text) selectedNumberVoucher = text;
	else selectedNumberVoucher = 0;

	dateReceived = 0;
	$('#tbodyVouchersReceived').empty();
	loadVouchersReceived();
}

function searchVoucherProvider(inputText){
	let text = inputText.value || null;

	if(text) selectedDocumentClient = text;
	else selectedDocumentClient = null;

	dateReceived = 0;
	$('#tbodyVouchersReceived').empty();
	loadVouchersReceived();
}

function changePaymentMethod(selectPayMethod){
	payMethodSelected = selectPayMethod.value;
	dateReceived = 0;
	$('#tbodyVouchersReceived').empty();
	loadVouchersReceived();
}

function changeTypeVoucher(selectTypeVoucher){
	dateReceived = 0;
	let nameVoucher = selectTypeVoucher.options[selectTypeVoucher.selectedIndex].text;
	selectedTypeVoucher = selectTypeVoucher.value;
	if(nameVoucher.includes("Recibo"))
		selectedTypeVoucher += "1";

	$('#tbodyVouchersReceived').empty();
	loadVouchersReceived();
}

function findWithDate(){
	let iconSearch = $('#iconSearch');
	if(iconSearch.hasClass('fa-search')){
		iconSearch.removeClass('fa-search');
		iconSearch.addClass('fa-times');
		let selectedDate = document.getElementById('inputDateFilter').value || null;
		if(selectedDate){
			selectedDateVoucher = selectedDate;
			dateReceived = 0;
			$('#tbodyVouchersReceived').empty();
			loadVouchersReceived();
		}else showReplyMessage(1, "Debe seleccionar una fecha para filtrar los comprobantes.", "Fecha requerida", null);
	}else if(iconSearch.hasClass('fa-times')){
		iconSearch.removeClass('fa-times');
		iconSearch.addClass('fa-search');
		selectedDateVoucher = 0;
		dateReceived = 0;
		$('#tbodyVouchersReceived').empty();
		loadVouchersReceived();
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
				prepareFor: "Recibidos",
				groupByCurrency: groupByCurrency,
				typeVoucher: typeCFE,
				includeReceipts: includeReceipts
			}

			$("#modalExport").modal("hide");
			mostrarLoader(true)
			sendAsyncPost("exportExcelCFE", data)
			.then((response)=>{
				mostrarLoader(false)
				if(response.result == 2){
					$("#modalExport").modal("show");
					const linkSource = `data:` + response.format + `;base64,${ response.file }`;
					const downloadLink = document.createElement("a");
					const fileName = "Emitidos" + dateInit + "--" + dateFinish + ".xlsx";
					downloadLink.href = linkSource;
					downloadLink.download = fileName;
					downloadLink.click();
				}else showReplyMessage(response.result, response.message, "Detalle de ventas", "modalExport");
			})

			// let response = sendPost("exportExcelCFE", data);
			// if(response.result == 2){
			// 	const linkSource = `data:` + response.format + `;base64,${ response.file }`;
			// 	const downloadLink = document.createElement("a");
			// 	const fileName = "Recibidos.xlsx";
			// 	downloadLink.href = linkSource;
			// 	downloadLink.download = fileName;
			// 	downloadLink.click();
			// }else showReplyMessage(response.result, response.message, "Exportar comprobantes emitidos", "modalExport");
		}else showReplyMessage(1, "Debe ingresar la fecha de inicio del período a exportar", "Campo fecha inicio requerido", "modalExport");
	}else showReplyMessage(1, "Debe ingresar la fecha final del período a exportar", "Campo fecha final requerido", "modalExport");
}