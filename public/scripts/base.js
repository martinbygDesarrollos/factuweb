let pinIcon = '<i class="fas fa-thumbtack" id="idThumbtackIcon"></i>';
let barsIcon = '<i class="fas fa-bars" id="idBarsIcon"></i>';
//$( window ).width() es el tamaño de la pantalla y $( document ).width(); es el tamaño del documento html
//obtengo estos dos tamaños para saber si esta desde el celu o no y ver que icono se muetsra en el menu
//let windowSize = $( window ).width();
let documentSize = $( document ).width();

if(documentSize < 1200){
	$('#sidebarCollapse').append(barsIcon);
}else{
	$('#sidebarCollapse').append(pinIcon);
}

$('#sidebarCollapse').on('click', function() {
	$('#sidebar, #content').toggleClass('active');

	if($("#sidebarCollapse").children()[0].getAttribute("id") == 'idBarsIcon'){
		$('#sidebarCollapse').empty();
		$('#sidebarCollapse').append(pinIcon);
		//console.log("");
	}else if ($("#sidebarCollapse").children()[0].getAttribute("id") == 'idThumbtackIcon'){
		$('#sidebarCollapse').empty();
		$('#sidebarCollapse').append(barsIcon);
	}else{
		$('#sidebarCollapse').append(barsIcon);
	}

});

function mostrarLoader(valor){
	if(valor){
		console.log('MOSTRAR LOADER')
		$('.loaderback').css('display', 'block')
		$('.loader').css('display', 'block')
	} else {
		console.log('ESCONDER LOADER')
		$('.loaderback').css('display', 'none')
		$('.loader').css('display', 'none')
	}
}

function updateSoldInfo(inputVariable){
	let value = inputVariable.checked;
	// console.log(value)
	if(value){
		$('#infoSold').css('display', 'block')
		sendAsyncPost("updateSoldInfo", {typeCoin : "UYU"})
		.then(function(response){
			// console.log(response)
			if (response.result == 2 ){
				// console.log("Exito")
				// console.log(response)
				$('#SoldLastMonth').text(response.mesAnteriorName + " $" + response.soldMesAnteriorUYU + ". U$D " + response.soldMesAnteriorUSD)
				$('#SoldCurrentMonth').text(response.mesActualName + " $" + response.soldMesActualUYU + ". U$D " + response.soldMesActualUSD)
			} else {
				// showReplyMessage(response.result, response.message, "Nueva factura", null);
				console.log("Error")
				$('#SoldLastMonth').text("--")
				$('#SoldCurrentMonth').text("--")
			}
		})
		.catch(function(response){
			// console.log("Error")
			$('#SoldLastMonth').text("--")
			$('#SoldCurrentMonth').text("--")
			// console.log("este es el catch", response);
		});
	} else {
		$('#infoSold').css('display', 'none')
	}

}
