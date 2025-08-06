function showReplyMessage(codeResult, message, title, currentModal){
	// console.log("showReplyMessage")
    console.log("showReplyMessage start", {codeResult, message, title, currentModal});

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------

	if(currentModal) {
        $('#' + currentModal).off('hidden.bs.modal').on('hidden.bs.modal', function() {
            showNewModal();
            $(this).off('hidden.bs.modal'); // Remove handler after use
        }).modal('hide');
    } else {
        showNewModal();
    }
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// const originalHandlers = currentModal ? 
    //     ($('#' + currentModal).data('events')?.['hidden.bs.modal'] || []) : 
    //     [];

	// if(currentModal) {
    //     $('#' + currentModal).off('hidden.bs.modal').on('hidden.bs.modal', function(e) {
    //         // Restore original handlers first
    //         originalHandlers.forEach(handler => {
    //             $('#' + currentModal).on('hidden.bs.modal', handler.handler);
    //         });

    //         showNewModal();
    //         $(this).off('hidden.bs.modal'); // Remove this temporary handler
    //     }).modal('hide');
    // } else {
    //     showNewModal();
    // }
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------

    function showNewModal() {
        // Setup modal shown event
        $('#modalResponse').off('shown.bs.modal').on('shown.bs.modal', function () {
            $('#modalButtonResponse').trigger('focus');
            // $(this).off('shown.bs.modal'); // Remove handler after use
        });

        // Remove any existing click handlers
        $("#modalButtonResponse").off('click');

        // Reset and set modal color classes
        $('#modalColourResponse')
            .removeClass('alert-success alert-warning alert-danger')
            .addClass(getColorClass(codeResult));

        $('#modalTitleResponse').html(title);
        $('#modalMessageResponse').html(message);

        // Setup button click handler
        $('#modalButtonResponse').off('click').on('click', function() {
			$('#modalResponse').off('hidden.bs.modal').on('hidden.bs.modal', function () {
				if(currentModal && codeResult != 2) {
					$('#' + currentModal).modal('show');
				}
			}).modal('hide');
        });

        // Show the modal
        $("#modalResponse").modal('show');
    }

    // Helper function to determine color class
    function getColorClass(code) {
        switch(code) {
            case 0: return 'alert-danger';
            case 1: return 'alert-warning';
            case 2: return 'alert-success';
            default: return '';
        }
    }
}

function showReplyMessageWithFunction(codeResult, message, title, currentModal, onButtonClick){
	// console.log("showReplyMessage")
    console.log("showReplyMessage start", {codeResult, message, title, currentModal});

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------

	if(currentModal) {
        $('#' + currentModal).off('hidden.bs.modal').on('hidden.bs.modal', function() {
            showNewModal();
            $(this).off('hidden.bs.modal'); // Remove handler after use
        }).modal('hide');
    } else {
        showNewModal();
    }

    function showNewModal() {
        // Setup modal shown event
        $('#modalResponse').off('shown.bs.modal').on('shown.bs.modal', function () {
            $('#modalButtonResponse').trigger('focus');
            // $(this).off('shown.bs.modal'); // Remove handler after use
        });

        // Remove any existing click handlers
        $("#modalButtonResponse").off('click');

        // Reset and set modal color classes
        $('#modalColourResponse')
            .removeClass('alert-success alert-warning alert-danger')
            .addClass(getColorClass(codeResult));

        $('#modalTitleResponse').html(title);
        $('#modalMessageResponse').html(message);

        // Setup button click handler
        $('#modalButtonResponse').off('click').on('click', function() {
            // Ejecutar función personalizada si se proporciona
            if(typeof onButtonClick === 'function') {
                onButtonClick();
            }
            
            $('#modalResponse').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                if(currentModal && codeResult != 2) {
                    $('#' + currentModal).modal('show');
                }
            }).modal('hide');
        });

        // Show the modal
        $("#modalResponse").modal('show');
    }

    // Helper function to determine color class
    function getColorClass(code) {
        switch(code) {
            case 0: return 'alert-danger';
            case 1: return 'alert-warning';
            case 2: return 'alert-success';
            default: return '';
        }
    }
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