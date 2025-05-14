function sendPost(nombreFuncion, parametros){
	var result = null;
	$.ajax({
		async: false,
		url: getSiteURL() + nombreFuncion,
		type: "POST",
		data: parametros,
		timeout: 120000, //miliseconds
		success: function (response) {
			response = response.trim();
			var response = jQuery.parseJSON(response);
			result =  response;
		},
		error: function (jqXHR, textStatus, errorThrown) {
			var response = {result:0, message:errorThrown}
			resolve(response);
		},
	});

	return result;
}

function sendAsyncPost(nombreFuncion, parametros){
	return new Promise( function(resolve, reject){
		$.ajax({
			async: true,
			url: getSiteURL() + nombreFuncion,
			type: "POST",
			data: parametros,
			timeout: 120000, //miliseconds
			success: function (response) {
				response = response.trim();
				var response = jQuery.parseJSON(response);
				resolve(response);
			},
			error: function ( jqXHR, textStatus, errorThrown) {
				var response = {result:0, message:errorThrown}
				resolve(response);
			},
		});
	});
}

function sendFetch(nombreFuncion, formData) {
    return new Promise(function (resolve, reject) {
        fetch(getSiteURL() + nombreFuncion, {
			async: true,
            method: "POST",
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error("Error uploading file");
            }
        })
        .then(data => {
            resolve(data);
        })
        .catch(error => {
            reject(error);
        });
    });
}