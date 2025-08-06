////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			OBTENER DATOS DE LA SESION
// function getDataSession(indexDataSession){ //indexDataSession es el nombre que se le dá al indice que tiene los datos que puse en la session
// 	console.log("getDataSession")
// 	let response = sendPost('getDataSession', {indexToSearch: indexDataSession});
// 	console.log(response)
// 	return response;
// }



////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			INSERTAR DATOS EN LA SESION


////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			MODIFICAR DATOS EN LA SESION
function updateDataSession(indice0, indice1, data){ //arrayIndexSession tiene una lista con los nombres o numeros que indican los indices de la sesion que tiene el dato a cambiar

	let response = sendPost('updateDataSession', {index0:indice0, index1:indice1, newData: data});
	if (response.result == 2){
		console.log("updateDataSession OK")
		// btnAddDetailClickNumber = 0;
		return response;
	}
	else console.log(response.message);
}

// function setNewDataSession(productsInCart){ //arrayIndexSession tiene una lista con los nombres o numeros que indican los indices de la sesion que tiene el dato a cambiar

// 	let response = sendPost('setNewDataSession', {cart: productsInCart});
// 	if (response.result == 2){
// 		console.log("setNewDataSession OK")
// 		// btnAddDetailClickNumber = 0;
// 		return response;
// 	}
// 	else console.log(response.message);
// }


////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			MARCAR TODOS LOS PRODUCTOS EN LA SESION COMO ELIMINADOS

async function removedAllProducts (){

	return new Promise(resolve => {
		resolve(sendAsyncPost('removeProductsSession'));
		/*setTimeout(
	        function() {
	          resolve("ok");
	        }
	    , 5000);*/
	});
}