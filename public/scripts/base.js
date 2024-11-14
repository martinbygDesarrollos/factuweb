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