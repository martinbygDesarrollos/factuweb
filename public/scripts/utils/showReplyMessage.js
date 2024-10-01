function showReplyMessage(codeResult, message, title, currentModal){
	$("#modalButtonResponse").off('click');
	if(currentModal)
		$('#' + currentModal).modal('hide');

	$('#modalColourResponse').removeClass('alert-success');
	$('#modalColourResponse').removeClass('alert-warning');
	$('#modalColourResponse').removeClass('alert-danger');

	if(codeResult == 0)
		$('#modalColourResponse').addClass('alert-danger');
	else if(codeResult == 2)
		$('#modalColourResponse').addClass('alert-success');
	else if(codeResult == 1)
		$('#modalColourResponse').addClass('alert-warning');

	$('#modalTitleResponse').html(title);
	$('#modalMessageResponse').html(message);

	$('#modalButtonResponse').click(function(){
		$('#modalResponse').modal('hide');
		if(currentModal && codeResult != 2)
			$('#' + currentModal).modal();
	});

	$("#modalResponse").modal();
	//document.getElementById("modalButtonResponse").focus();ç
	$('#modalButtonResponse').focus();

}

function openLoadModal(animation){
	$('#progressBarRestoreFile').removeClass('loadProgressBar');
	if(animation)
		$('#progressBarRestoreFile').addClass('loadProgressBar');
	$('#modalLoad').modal({backdrop: 'static', keyboard: false});
}

function showAlert(codeResult, message, title){
	//console.log("alerta");
	$('#idBasicAlert').removeClass('alert-success');
	$('#idBasicAlert').removeClass('alert-warning');
	$('#idBasicAlert').removeClass('alert-danger');

	if(codeResult == 0)
		$('#idBasicAlert').addClass('alert-danger');
	else if(codeResult == 2)
		$('#idBasicAlert').addClass('alert-success');
	else if(codeResult == 1)
		$('#idBasicAlert').addClass('alert-warning');

	$('#alertTitleResponse').html(title);
	$( "#idBasicAlert" ).hover(
	  function() {
	    $('#alertTitleResponse').html(message);
	  }, function() {
	    $('#alertTitleResponse').html(title);
	  }
	);

	$('#idBasicAlert').removeAttr("hidden");
	$('.fa-info-circle').removeAttr("hidden");
	$('#idBasicAlert p').removeAttr("hidden");
	setTimeout(function(){
		$('#idBasicAlert').attr("hidden", "true");
		$('.fa-info-circle').attr("hidden", "true");
		$('#idBasicAlert p').attr("hidden", "true");
		//console.log("ocultando el alert");
	}, 4000);
}

function showConfirmMessage(message, title, confirmFunction, currentModal){
	$("#modalConfirm").off('click');
	if(currentModal)
		$('#' + currentModal).modal('hide');

	$('#modalConfirmColor').addClass('alert-success');
	
	$('#modalConfirmTitle').html(title);
	$('#modalConfirmMessage').html(message);

	$('#modalConfirmButtonNO').click(function(){
		$('#modalConfirm').modal('hide');
		if(currentModal)
			$('#' + currentModal).modal();
	});
	$('#modalConfirmButtonSI').click(function(){
		$('#modalConfirm').modal('hide');
		if(confirmFunction)
			confirmFunction();
		$("#selectTypeVoucher").focus(); // Por ahora dejo aca pero no deberia ir ya que es una funcion de para cualquier uso de confirmacion de mensajes que ejecuta la funcion que le pases por parametro al darle si
	});

	$("#modalConfirm").modal();
	$('#modalConfirmButtonSI').focus();
}