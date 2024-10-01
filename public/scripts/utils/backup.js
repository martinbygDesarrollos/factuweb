function exportBackUp(){
	openLoadModal(true);
	sendAsyncPost("getRestorePoint", null)
	.then(function(response){
		$('#modalLoad').modal('hide');
		showReplyMessage(response.result, response.message, "Exportar respaldo", null);
		if(response.result == 2){
			let linkSource = `data:application/x-zip-compressed;base64,${response.fileBase64}`;
			let downloadLink = document.createElement("a");
			let fileName = response.fileName;
			downloadLink.href = linkSource;
			downloadLink.download = fileName;
			downloadLink.click();
		}
	})
	.catch(function(responseError){
		$('#modalLoad').modal('hide');
		showReplyMessage(0, "Ocurrió un error por lo que no pudo finalizar con el proceso de exportación", "Exportar respaldo", null);
	});
}

function openSelectFile(){
	$('#inputSelectFileImport').click();
}

function importBackUp(){
	let file = $('#inputSelectFileImport').prop('files');
	if(file.length != 0){
		let typeFile = file[0].type;
		let nameFile = file[0].name;

		getBase64(file[0]).then(function(value){
			setValueProgressBar(10, 0);
			openLoadModal(null);
			let data = {
				zipBase64: value
			}
			sendAsyncPost("importRestorePoint", data)
			.then(function(response){
				if(response.result == 2){
					setValueProgressBar(response.listOrder.length, 0);
					for (let i = 0; i < response.listOrder.length; i++) {
						sendAsyncPost("importTableSelected", { tableToImport: response.listOrder[i]})
						.then(function(responseImport){
							if(responseImport.result == 2)
								setValueProgressBar(response.listOrder.length, i);
						}).catch(function(responseImport){
							$('#modalLoad').modal('hide');
							showReplyMessage(0, "Ocurrió un error y el respaldo no pudo ser procesado.", "Archivo no procesado", null);
						});
					}
					$('#modalLoad').modal('hide');
					showReplyMessage(2, "La base de datos fue restaurada al punto ingresado.", "Restauración exitosa", null);
				}else{
					$('#modalLoad').modal('hide');
					showReplyMessage(response.result, response.message, "Restaurar base de datos.", null);
				}
			})
			.catch(function(){
				$('#modalLoad').modal('hide');
				showReplyMessage(0, "Ocurrió un error por lo que no pudo finalizar la operacion correctamente.", "Enviar facturas", null);
			});
		});
	}else showReplyMessage(1, "Debe seleccionar un punto de restauración", "Punto de restauración no valido.", null);
}

function setValueProgressBar(totalTables, cantInserted){
	let newValue = (cantInserted * 100) / totalTables;
	$('#progressBarRestoreFile').css('width', newValue + "%");
}