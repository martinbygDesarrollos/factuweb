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

function mostrarLoaderSearchClient(estado, modal, campo){
    if(estado){
        console.log('MOSTRAR LOADER MODAL');
        
        // Bloquear todos los campos y botones del modal
        $('#' + modal + ' input, #' + modal + ' select, #' + modal + ' button, #' + modal + ' textarea').prop('disabled', true);
        
        // Agregar spinner al campo específico
        if(campo && $('#' + campo).length > 0) {
            // Crear el contenedor del spinner si no existe
            if($('#' + campo + '_spinner').length === 0) {
                $('#' + campo).after('<span id="' + campo + '_spinner" class="input-spinner"> <i class="fas fa-circle-notch fa-spin"></i> </span>');
            }
            
            // Posicionar el spinner dentro del input
            let $input = $('#' + campo);
            let $spinner = $('#' + campo + '_spinner');
            
            // Posicionar el spinner
            $spinner.css({
                'position': 'absolute',
                'right': '10px',
                'top': '50%',
                'transform': 'translateY(-50%)',
                'z-index': '10',
                'pointer-events': 'none',
                'color': '#1D635D'
            });
            
            // Hacer el input container relativo si no lo es
            if($input.parent().css('position') === 'static') {
                $input.parent().css('position', 'relative');
            }
            
            $spinner.show();
        }
    } else {
        console.log('ESCONDER LOADER MODAL');
        
        // Liberar todos los campos y botones del modal
        $('#' + modal + ' input, #' + modal + ' select, #' + modal + ' button, #' + modal + ' textarea').prop('disabled', false);
        
        // Quitar spinner del campo específico
        if(campo) {
            $('#' + campo + '_spinner').hide();
        }
        
        // Ocultar loader general
        $('.loaderback').css('display', 'none');
        $('.loader').css('display', 'none');
    }
}

function updateSoldInfo(inputVariable){
	let value = inputVariable.checked;
	// console.log(value)
	if(value){
		$('#infoSold').css('display', 'block')
		sendAsyncPost("updateSoldInfo", {typeCoin : "UYU"})
		.then(function(response){
			console.log(response)
			if (response.result == 2 ){
				// console.log("Exito")
				// console.log(response)
				// $('#SoldLastMonth').text(response.mesAnteriorName + " $" + response.soldMesAnteriorUYU + (response.soldMesAnteriorUSD == "0.00") ? "" : (". U$D " + response.soldMesAnteriorUSD))
				$('#SoldLastMonth').text(
					response.mesAnteriorName + 
					" $" + response.soldMesAnteriorUYU + 
					((response.soldMesAnteriorUSD == "0.00") ? "" : (". U$D " + response.soldMesAnteriorUSD))
				  	);
				$('#SoldCurrentMonth').text(". " + response.mesActualName + " $" + response.soldMesActualUYU + 
					((response.soldMesActualUSD == "0.00") ? "" : (". U$D " + response.soldMesActualUSD))
				  	);
				  // ". U$D " + response.soldMesActualUSD)
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
