//se traen todos los comprobantes desde cero
function updateVouchers(callFrom){
	return new Promise((resolve) => {
		$('#textUpdate').css('visibility', 'visible');
		$('#btnUpdateFooter').addClass('turnI');
		sendAsyncPost("updateDataVouchers", {callFrom: callFrom})
		.then(function(response){
			$('#textUpdate').css('visibility', 'hidden');
			$('#btnUpdateFooter').removeClass('turnI');
			console.log(response);
			resolve(console.log("datos actualizados"));
		})
		.catch(function(response){
			showReplyMessage(0, "Ocurrió un error por lo que algunos comprobantes no fueron actualizados", "Actualizar comprobantes", null);
		});
	});
}

//se traen todos los comprobantes a partir del ultimo id que se encuentra en la base local
function updateVouchersById(){
	return new Promise((resolve, reject)=>{
		sendAsyncPost("updateDataVouchersById")
		.then((response)=>{
			console.log(response);
		})
	});
}

//se traen todos los comprobantes -RECIBIDOS- a partir del ultimo id que se encuentra en la base local
function updateReceivedVouchersById(){
	return new Promise((resolve, reject)=>{
		sendAsyncPost("updateDataReceivedVouchersById")
		.then((response)=>{
			console.log(response);
		})
	});
}

$(window).resize(function() {
	resizeScreen();
});

//css para mostrar o no las columnas  de clientes segun la pantalla del dispositivo
function resizeScreen(){
	let screenWidth = screen.width;

	if(screenWidth < 600){
		$('.toHidden1').css('display', 'none');
		$('.toHidden2').css('display', 'none');
	}else if(screenWidth >= 600 && screenWidth < 1100){
		if(!$('.toHidden1').is(':visible'))
			$(".toHidden1").show()
		$('.toHidden2').css('display', 'none');
	}else if(screenWidth >= 1100){
		if(!$('.toHidden1').is(':visible'))
			$(".toHidden1").show();

		if(!$('.toHidden2').is(':visible'))
			$(".toHidden2").show();
	}
}

console.log = function() {}; // SI EL ENTORNO ES DESARROLLO COMENTAR ESTA LINEA | PRODUCCION DESCOMENTAR