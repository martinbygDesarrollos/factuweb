function signIn(){
	let rutValue = $("#inputRut").val() || null;
	let userValue = $('#inputUser').val() || null;
	let passwordValue = $('#inputPassword').val() || null;

	if(rutValue){
		if(rutValue.length > 10){
			if(userValue){
				if(userValue.length > 4){
					if(passwordValue){
						if(passwordValue.length >= 4){
							let response = sendPost('signIn', { rut: rutValue, user: userValue, password: passwordValue});
							if(response.result == 2){
								window.location.href = getSiteURL();
							}else showReplyMessage(response.result, response.message, "Iniciar sesión", null);
						}else showReplyMessage(1, "Por seguridad su contraseña debe contener al menos 4 caracteres", "Contraseña no valida", null);
					}else showReplyMessage(1, "Debe ingresar su contraseña para iniciar sesión", "Contraseña requerida", null);
				}else showReplyMessage(1, "El sistema no permite nombres de usuario con longitud menor a 5", "Usuario no valido", null);
			}else showReplyMessage(1, "Debe ingresar su usuario para iniciar sesión", "Usuario requerido", null);
		}else showReplyMessage(1, "La longitud del rut ingresado no es valida.", "RUT no valido", null);
	}else showReplyMessage(1, "Debe ingresar el rut de su empresa para iniciar sesión", "RUT requerido", null);
}

function loadRuts(inputRut){
	var rutPart = inputRut.value;
	$("#listRuts").empty();
	if(rutPart.length >= 6 && rutPart.length <= 11){
		var response = sendPost("getSuggestionRut",{rutPart: rutPart});
		if(response.result == 2){
			for (var i = response.listResult.length - 1; i >= 0; i--) {
				var option = document.createElement("option");
				option.value = response.listResult[i].rut;
				option.label = response.listResult[i].nombre;
				$("#listRuts").append(option);
			}
		}
	}
}

/*function keyPressSignIn(eventEnter, inputValue, size){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputRut")
			$('#inputUser').focus();
		if(eventEnter.srcElement.id == "inputUser")
			$('#inputPassword').focus();
		else if(eventEnter.srcElement.id == "inputPassword")
			$('#buttonConfirm').click();
	}else if(inputValue != null && inputValue.length == size) return false;
}*/


function keyPressSignIn(eventEnter, value, size){
	if(eventEnter.keyCode == 13 && !eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputRut")
			$('#inputUser').focus();
		if(eventEnter.srcElement.id == "inputUser")
			$('#inputPassword').focus();
		else if(eventEnter.srcElement.id == "inputPassword")
			$('#buttonConfirm').click();
	}
	//else if(inputValue != null && inputValue.length == size) return false;
	else if(eventEnter.keyCode == 13 && eventEnter.shiftKey){
		if(eventEnter.srcElement.id == "inputPassword")
			$('#inputUser').focus();
		if(eventEnter.srcElement.id == "inputUser")
			$('#inputRut').focus();
	}
}