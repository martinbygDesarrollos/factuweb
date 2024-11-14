$('#modalDateToExportAccountingData').on('show.bs.modal', ()=>{
	valueCurrent = getPreviousMonthByDate(getCurrentDate());
	let last = getLastDayMonth(valueCurrent);
	valueCurrent = valueCurrent.substring(0, valueCurrent.length -2);

	$("#dateFromAccountingData").val(valueCurrent+"01");
	$("#dateToAccountingData").val(last);
	$("#dateFromAccountingData").attr("disabled", false);
	$("#dateToAccountingData").attr("disabled", false);
	//unlockButton("buttonExportDataAccounting", null);
})

async function howmanyRowsExportAccData( option ){

	lockButton("buttonExportDataAccounting", null);
	$("#dateFromAccountingData").attr("disabled", true);
	$("#dateToAccountingData").attr("disabled", true);

	valueDateFrom = $("#dateFromAccountingData").val();
	valueDateTo = $("#dateToAccountingData").val();
	$("#modalDateToExportAccountingData").modal("hide");

	mostrarLoader(true)

	if ( valueDateFrom.substring(0, 4)+valueDateFrom.substring(5, 7) > valueDateTo.substring(0, 4)+valueDateTo.substring(5, 7) ){
		date1 = valueDateFrom.substring(8, 10)+"/"+valueDateFrom.substring(5, 7)+"/"+valueDateFrom.substring(2, 4)
		date2 = valueDateTo.substring(8, 10)+"/"+valueDateTo.substring(5, 7)+"/"+valueDateTo.substring(2, 4)
		showReplyMessage(1, "Fechas ingresadas, desde: "+date1+" hasta: "+date2+" incorrectas.", "Exportar datos", "modalDateToExportAccountingData", true);
	}else if( valueDateFrom.substring(0, 4)+valueDateFrom.substring(5, 7) !== valueDateTo.substring(0, 4)+valueDateTo.substring(5, 7) ){
		showReplyMessage(1, "El mes y año ingresado debe de ser el mismo en ambas fechas.", "Exportar datos", "modalDateToExportAccountingData", true);
	}else{
		valueDateFrom = valueDateFrom.substring(0, 4)+valueDateFrom.substring(5, 7)+valueDateFrom.substring(8, 10)+"000000";
		valueDateTo = valueDateTo.substring(0, 4)+valueDateTo.substring(5, 7)+valueDateTo.substring(8, 10)+"235959";
		sendAsyncPost("howmanyRowsExportAccounting", {option:option, from: valueDateFrom, to: valueDateTo})
		.then(( response )=>{
			console.log(response);
			if ( response.value ){
				if ( response.value > 999 ){
					mostrarLoader(false)

					showReplyMessage(1, "Esta consulta trae "+response.value+" comprobantes. Ingrese un período de tiempo más acotado.", "Exportar datos", "modalDateToExportAccountingData");
					unlockButton("buttonExportDataAccounting", null);
					$("#dateFromAccountingData").attr("disabled", false);
					$("#dateToAccountingData").attr("disabled", false);
				}else{
					exportAccountingData(option, valueDateFrom, valueDateTo);
				}
			}else {
				unlockButton("buttonExportDataAccounting", null);
				$("#dateFromAccountingData").attr("disabled", false);
				$("#dateToAccountingData").attr("disabled", false);
				showReplyMessage(1, "No se encontraron comprobantes a exportar", "Exportar datos", "modalDateToExportAccountingData");
				console.log("no se encontraron comprobantes");
			}
		})
	}
}

async function exportAccountingData(option, from, to){
	//bloquear boton de exportar
	//iniciar pantalla de carga
	console.log("funcion de traer los datos");
	sendAsyncPost("exportAccountingData", {option:option, from: from, to: to})
	.then(( response )=>{
		mostrarLoader(false)

		console.log(response);
		unlockButton("buttonExportDataAccounting", null);
		$("#dateFromAccountingData").attr("disabled", false);
		$("#dateToAccountingData").attr("disabled", false);
		$("#modalDateToExportAccountingData").modal("hide");
		if ( response.result == 2 )
			window.location.href = getSiteURL() + 'downloadFile.php?n='+response.name;
	})
}

function lockButton(id, element){
	if ( id ){
		$("#"+id).attr("disabled", true);
	}
}

function unlockButton(id, element){
	if ( id ){
		$("#"+id).attr("disabled", false);
	}
}