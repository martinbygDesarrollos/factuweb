function eliminarEmpresa(){
    console.log("Eliminar empresa de forma permanente y cerrar la sesion!!!!!! DESTRUCTIVE ACTION")
    showConfirmDelete()
}

function showConfirmDelete(){
	$('#modalConfirmDeleteColor').addClass('alert-danger');
	
	$('#modalConfirmDeleteTitle').html("Eliminar empresa permanentemente");
	$('#modalConfirmDeleteMessage').html("");

	$('#modalConfirmDeleteButtonNO').click(function(){
		$('#modalConfirmDelete').modal('hide');
	});
	$('#modalConfirmDeleteButtonSI').click(function(){
		$('#modalConfirmDelete').modal('hide');
        confirmDelete();
	});

	$("#modalConfirmDelete").modal();
	$('#modalConfirmButtonSI').focus();
}

function confirmDelete(){
    console.log('CONFIRMAR ELIMINACION')
    mostrarLoader(true)
    sendAsyncPost("eliminarEmpresa", {})
    .then(function(response){
        console.log(response);
        mostrarLoader(false)
        if (response.result == 2 ){
            window.location.href = getSiteURL() + 'cerrar-session';
        } else {
            showReplyMessage(response.result, response.message, "Notificación", null);
        }
    })
    .catch(function(response){
        mostrarLoader(false)
        console.log("este es el catch", response);
    });
}