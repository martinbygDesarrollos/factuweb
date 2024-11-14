<?php

require_once 'send_petition.php';
require_once '../src/controllers/ctr_users.php';
require_once '../src/utils/utils.php';

class ctr_rest{

	public function getNameClient($document){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->getNameClient($document));
		if(strpos($responseRest, "ERROR") === FALSE){
			$response->result = 0;
			$response->message = "No se encontro un registro con el documento ingresado.";
		}else{
			$response->result = 2;
			$response->name = str_replace("1", "", $responseRest);
		}
		return $response;
	}
	//UPDATED
	public function nuevoCliente($rut, $data, $token){
		$restController = new ctr_rest();
		$sendPetitionClass = new sendPetition();
		return json_decode($sendPetitionClass->nuevoCliente($rut, $data, $token));
		// $responseGetToken = $restController->getToken();
		// if($responseGetToken->result == 2){
		// }else return $responseGetToken;
	}
	//UPDATED
	public function buscarCliente($rut, $textToSearch, $token){
		$sendPetitionClass = new sendPetition();
		$response = new \stdClass();
		$response->listResult = array();
		$responseRest = json_decode($sendPetitionClass->buscarCliente($rut, $textToSearch, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			foreach ($responseRest->customers as $value) {
				$objectResult = new \stdClass();
				$objectResult->name = $value->socialName;
				$objectResult->document = $value->document;
				array_push($response->listResult, $objectResult);
			}
			//$response->listResult = $responseRest->customers;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		return $response;
	}
	//UPDATED
	//busca clientes en dgi por nombre o rut
	public function buscarClienteDGI($textToSearch, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$response->listResult = array();
		$responseRest = json_decode($sendPetitionClass->buscarClienteDGI($textToSearch, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			foreach ($responseRest->companies as $key => $value) {
				$objectResult = new \stdClass();
				$objectResult->name = $value->socialName;
				$objectResult->document = $value->document;
				array_push($response->listResult, $objectResult);
			}
			//$response->listResult = $responseRest->companies;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		return $response;
	}
	//UPDATED
	public function buscarClienteGenaroUyCedula($documento){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = $sendPetitionClass->prepareAndSendCurlGenaroUy("GET", "ci=".$documento);
		if ( $responseRest == "ERROR"){
			$response->result = 1;
			$response->message = "No se ha encontrado cliente.";
		}
		else{
			$object = new \stdClass();
			$object->id = null;
			$object->docReceptor = $documento;
			$object->nombreReceptor = $responseRest;
			$object->direccion = null;
			$object->localidad = null;
			$object->departamento = null;
			$object->correo = null;
			$object->celular = null;
			$object->idEmpresa = null;
			$response->result = 2;
			$response->objectResult = $object;
		}
		return $response;
	}

	public function buscarClienteGenaroUyNombre($nombre, $apellido){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$response->listResult = array();
		$responseRest = $sendPetitionClass->prepareAndSendCurlGenaroUy("GET", "n=".$nombre."&a=".$apellido);
		if ( $responseRest == "ERROR"){
			$response->result = 1;
			$response->message = "No se ha encontrado cliente.";
		}
		else{
			$object = new \stdClass();
			$object->id = null;
			$object->docReceptor = $documento;
			$object->nombreReceptor = $responseRest;
			$object->direccion = null;
			$object->localidad = null;
			$object->departamento = null;
			$object->correo = null;
			$object->celular = null;
			$object->idEmpresa = null;
			$response->result = 2;
			$response->objectResult = $object;
		}
		return $response;
	}

	public function status($rut, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->status($rut, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}

		return $response;
	}
	//UPDATED
	public function exportacion($rut, $typeCall, $format, $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $type, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){

		$data = "?format=". $format . "&from=" . $dateFrom ."&to=" . $dateTo . "&groupByCurrency=" . $groupByCurrency . "&includeReceipts=" . $includeReceipts . "&type=" . $type;

		if ( strcmp("Emitidos", $typeCall ) == 0 ){
			$data .= "&extraFields=A70";
		}

		$responseRest = json_decode($sendPetitionClass->exportacion($rut, $typeCall, $data, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			$response->format = $responseRest->format;
			$response->file = $responseRest->data;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function obtenerCotizacion($dateFrom, $dateTo, $typeCoin){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->obtenerCotizacion($dateFrom, $dateTo, $typeCoin));
		if(isset($responseRest->resultado)){
			if($responseRest->resultado->codigo == 200){
				$response->result = 2;
				$response->currentQuote = $responseRest->currencies[0]->tcv;
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error, REST no retorno una cotización valida.";
			}
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y REST no retorno un resultado.";
		}

		return $response;
	}
	//UPDATED
	public function consultarRut($rut, $rutBusiness, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->consultarRut($rut, $rutBusiness, $token));
		if(isset($responseRest->resultado->code)){
			if($responseRest->resultado->code == 200){
				$response->result = 2;
				unset($responseRest->resultado);
				$objectResult = new \stdClass();
				$objectResult->docReceptor = $responseRest->rut;
				$objectResult->nombreReceptor = $responseRest->razonSocial;
				$objectResult->direccion = $responseRest->direccion;
				$objectResult->localidad = $responseRest->localidad;
				$objectResult->departamento = $responseRest->departamento;
				$objectResult->razonSocial = $responseRest->razonSocial;
				foreach ($responseRest->contactos as $value) {
					if ( $value->tipoContacto == 'CORREO ELECTRONICO' )
						$objectResult->correo = $value->datoContacto;

					if ( $value->tipoContacto == 'TELEFONO FIJO' )
						$objectResult->celular = $value->datoContacto;
				}
				$objectResult->contactos = $responseRest->contactos;
				// $objectResult->idEmpresa = $_SESSION['systemSession']->idBusiness;
				$response->empresa = $objectResult;
			}else{
				$response->result = 0;
				$response->message = $responseRest->resultado->message;
			}
		}else if(isset($responseRest->resultado->codigo)){
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y REST no retorno un resultado.";
		}

		return $response;
	}

	public function listarRecibidos($rut, $pageSize, $lastId, $dateFrom, $dateTo){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		$responseToken = $restControllerInstance->getToken();
		if($responseToken->result == 2){
			$responseRest = json_decode($sendPetitionClass->listarRecibidos($rut, $pageSize, $lastId, $dateFrom, $dateTo, $responseToken->token));
			if(!isset($responseRest->resultado)){
				if(sizeof($responseRest) > 0){
					$response->result = 2;
					$response->listRecibidos = $responseRest;
				}else{
					$response->result = 1;
					$response->message = "No hay comprobantes recibidos que retornar.";
				}
			}else{
				$response->result  = 0;
				$response->message = "Ocurrió un error y REST no retorno las comprobantes recibidos.";
			}
		}else return $responseToken;

		return $response;
	}
	//UPDATED
	public function listarEmitidos($rut, $pageSize, $lastId, $dateFrom, $branchCompany, $dateTo, $tokenRest){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		// $restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$responseRest = json_decode($sendPetitionClass->listarEmitidos($rut, $pageSize, $lastId, $dateFrom, $dateTo, $branchCompany, $tokenRest));
		if(!isset($responseRest->resultado)){
			if(sizeof($responseRest) > 0){
				$response->result = 2;
				$response->listEmitidos = $responseRest;
			}else{
				$response->result = 1;
				$response->message = "No hay comprobantes que retornar.";
			}
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y REST no retorno las comprobantes emitidos.";
		}
		// }else return $responseToken;

		return $response;
	}

	public function ping(){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->ping());
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			$response->message = "Hay conexión.";
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		return $response;
	}

	public function signIn($rut, $user, $password){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$responseRest = json_decode($sendPetitionClass->login($rut, $user, $password, null));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			$response->token = $responseRest->token;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		return $response;
	}
	//UPDATED
	public function consultarCFE($rut, $rutEmisor, $tipoCFE, $serieCFE, $numeroCFE, $formatFile, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$responseRest = json_decode($sendPetitionClass->consultarCFE($rut, $rutEmisor, $tipoCFE, $serieCFE, $numeroCFE, 1, $formatFile, $token));
		if(isset($responseRest->result)){
			if($responseRest->result->code == 200){
//					$restController->updateVoucherAnuladoDgi($tipoCFE, $serieCFE, $numeroCFE, $responseRest->isAnulado);
				$response->result = 2;
				unset($responseRest->result);
				$response->cfe = $responseRest;
			}else{
				$response->result = 0;
				$response->message = $responseRest->result->message;
			}
		}else{
			$response->result = 0;
			$response->message = $responseRest->result->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function consultarCertificadoDigital($rut){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		$responseToken = $restControllerInstance->getToken();
		if($responseToken->result == 2){
			$responseRest = json_decode($sendPetitionClass->consultarCertificadoDigital($rut, $responseToken->token));
			if($responseRest->resultado->codigo == 200){
				unset($responseRest->resultado);
				$response->result = 2;
				$response->certificadoDigital = $responseRest;
			}else{
				$response->result = 0;
				$response->message = $responseRest->resultado->error;
			}
		}else return $responseToken;

		return $response;
	}
	//UPDATED
	public function consultarCaes($rut, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$responseRest =  json_decode($sendPetitionClass->consultarCaes($rut, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			unset($responseRest->resultado);
			$response->caes = $responseRest;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function listarClientes($rut){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		$responseToken = $restControllerInstance->getToken();
		if($responseToken->result == 2){
			$responseRest = json_decode($sendPetitionClass->listarClientes($rut, $responseToken->token));
			if($responseRest->resultado->codigo == 200){
				if(sizeof($responseRest->customers) > 0){
					$response->result = 2;
					$response->clientes = $responseRest->customers;
				}else{
					$response->result = 1;
					$response->message = "No hay clientes que retornar";
				}
			}else if($responseRest->resultado->codigo == 400){
				$response->result = 0;
				$response->message = "El rut '" . $rut . "' ingresado no es válido.";
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error en listar clientes al comunicarse con REST.";
			}
		}else return $responseToken;

		return $response;
	}
	//UPDATED
	public function modificarCliente($rut, $document, $documentType, $name, $notificationMethod, $contacts, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$data = array(
			"document" => $document,
			"documentType" => $documentType,
			"name" => $name,
			"notificationMethods" => array($notificationMethod),
			"contacts" => $contacts
		);
		$responseRest = json_decode($sendPetitionClass->modificarCliente($rut, $document, $data, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			$response->message = "El cliente '".$document."' fue modificado con exito.";
		}else {
			$response->result = 1;
			$response->message = "Ocurrió un error y REST no pudo guardar los cambios en la información del cliente '".$document."'.";
		}
		// }else return $responseToken;

		return $response;
	}
	//UPDATED
	public function consultarCliente($rut, $documento, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$responseRest = json_decode($sendPetitionClass->consultarCliente($rut, $documento, $token));
		if($responseRest->resultado->codigo == 200){
			$response->result = 2;
			unset($responseRest->resultado);
			$response->cliente = $responseRest;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function nuevoCFES($rut, $idEnvio, $tipoCFE, $fecha, $montosBrutos, $formaPago, $vencimiento, $receptor, $tipoMoneda, $detalles, $adenda, $sucursal){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		$responseToken = $restControllerInstance->getToken();
		if($responseToken->result == 2){
			$responseRest = json_decode($sendPetitionClass->nuevoCFE($rut, $idEnvio, $tipoCFE, $fecha, $montosBrutos, $formaPago, $vencimiento, $receptor, $tipoMoneda, $detalles,$adenda, $sucursal,$responseToken->token));
			if($responseRest->resultado->codigo == 201){
				$response->result = 2;
				unset($responseRest->resultado);
				$response->cfe = $responseRest;
			}else{
				$response->result = 0;
				$response->message = $responseRest->resultado->error;
			}
		}else return $responseToken;

		return $response;
	}

	public function nuevoCFE($rut, $tipoCFE, $fecha, $montosBrutos, $formaPago, $fechaVencimiento, $tipoMoneda, $exchangeRate, $detalle, $receptor, $indCobranza, $referencias, $adenda, $sucursal, $idBuy, $mediosPago, $tokenRest){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken($idUser);
		// if($responseToken->result == 2){

		$compra = null;
		if ( isset($idBuy) && $idBuy != "" ){
			$compra = $idBuy;
		}

		$data = array(
			"tipoCFE" => $tipoCFE,
			"fecha" => $fecha,
			"montosBrutos" => $montosBrutos,
			"formaPago" => $formaPago,
			"receptor" => $receptor,
			"tipoMoneda" => $tipoMoneda,
			"detalles" => $detalle,
			"idCompra" => $compra
		);


		if ( isset($sucursal) && $sucursal != "" && $sucursal >0  ){
			$data = array(
				"tipoCFE" => $tipoCFE,
				"sucursal" => $sucursal,
				"fecha" => $fecha,
				"montosBrutos" => $montosBrutos,
				"formaPago" => $formaPago,
				"receptor" => $receptor,
				"tipoMoneda" => $tipoMoneda,
				"detalles" => $detalle,
				"idCompra" => $compra
			);
		}

		if($formaPago == 2 && !is_null($fechaVencimiento))
			$data['vencimiento'] = $fechaVencimiento;

		if(!is_null($indCobranza))
			$data['IndCobranzaPropia'] = $indCobranza;

		if(!is_null($referencias))
			$data['referencias'] = $referencias;

		if(strcmp($tipoMoneda, "UYU") != 0)
			$data['tipoCambio'] = $exchangeRate;

		if(!is_null($adenda))
			$data['adenda'] = $adenda;
		
		if(!is_null($mediosPago)){
			$data['mediosPago'] = $mediosPago;
		}

		$responseRest = json_decode($sendPetitionClass->nuevoCFE($rut, $data, $tokenRest));
		if($responseRest->resultado->codigo == 201){
			$response->result = 2;
			unset($responseRest->resultado);
			$response->cfe = $responseRest;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function nuevoRecibo($rut, $tipoCFE, $fecha, $montosBrutos, $formaPago, $tipoMoneda, $usdValue, $detalle, $referencias, $receptor, $token, $sucursal = null){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		$restControllerInstance = new ctr_rest();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$data = array(
			"tipoCFE" => $tipoCFE,
			"fecha" => $fecha,
			"montosBrutos" => $montosBrutos,
			"formaPago" => $formaPago,
			"IndCobranzaPropia" => 1,
			"receptor" => $receptor,
			"tipoMoneda" => $tipoMoneda,
			"referencias" => $referencias,
			"detalles" => $detalle,
		);
		
		if ( isset($sucursal) && $sucursal != "" && $sucursal >0  ){
			$data = array(
				"tipoCFE" => $tipoCFE,
				"sucursal" => $sucursal,
				"fecha" => $fecha,
				"montosBrutos" => $montosBrutos,
				"formaPago" => $formaPago,
				"IndCobranzaPropia" => 1,
				"receptor" => $receptor,
				"tipoMoneda" => $tipoMoneda,
				"referencias" => $referencias,
				"detalles" => $detalle,
			);
		}

		if(!is_null($usdValue))
			$data['tipoCambio'] = $usdValue;

		$responseRest = json_decode($sendPetitionClass->nuevoCFE($rut, $data, $token));
		if($responseRest->resultado->codigo == 201){
			$response->result = 2;
			unset($responseRest->resultado);
			$response->cfe = $responseRest;
		}else{
			$response->result = 0;
			$response->message = $responseRest->resultado->error;
		}
		// }else return $responseToken;

		return $response;
	}

	public function prepareContactDetail($contactType, $value){
		$sendPetitionClass = new sendPetition();
		return $sendPetitionClass->getContactDetail($contactType, $value);
	}
	//OK
	public function prepareReferenciasToSend($tipoCFE, $serieCFE, $numeroCFE, $indReferencia, $razon){
		$sendPetitionClass = new sendPetition();
		return $sendPetitionClass->getReferenciasArray($tipoCFE, $serieCFE, $numeroCFE, $indReferencia, $razon);
	}

	public function prepareReceptorToSend($documento, $nombre, $direccion, $ciudad, $departamento, $pais){
		$sendPetitionClass = new sendPetition();
		return $sendPetitionClass->getReceptorArray($documento, $nombre, $direccion, $ciudad, $departamento, $pais);
	}

	public function prepareDetalleToSend($indFact, $nomItem, $codItem, $descItem, $cantidad, $uniMedida, $precio, $descuentoTipo = null, $descuento = null, $mediosPago = null){
		$sendPetitionClass = new sendPetition();
		return $sendPetitionClass->getDetallesArray($indFact, $nomItem, $codItem, $descItem, $cantidad, $uniMedida, $precio, $descuentoTipo, $descuento);
	}

	//sucursales
	//UPDATED
	public function getBranchCompanyByRut ($rut, $token){
		$response = new \stdClass();
		$sendPetitionClass = new sendPetition();
		// $responseToken = $restControllerInstance->getToken();
		// if($responseToken->result == 2){
		$company = $sendPetitionClass->getEmpresa($rut, $token);
		$response->result = 2;
		$response->branchCompany = json_decode($company)->sucursales;
		// }else return $responseToken;

		return $response;
	}


	public function getToken($idUser){
		$response = new \stdClass();
		$userController = new ctr_users();
		$responseGetToken = $userController->getUserInSesion();
		if(!is_null($responseGetToken)){
			if($responseGetToken->result == 2){
				$response->result = 2;
				$response->token = $responseGetToken->objectResult->tokenRest;
			}else{
				$response->result = 0;
				$response->message = "No se pudo acceder al token Rest en la base de datos.";
			}
		}else{
			$response->result = 0;
			$response->message = "No se encontró una sesión activa por lo que el proceso no se finalizó correctamente.";
		}

		return $response;
	}

	//UPDATED
	public function updateVoucherAnuladoDgi($tipoCFE, $serieCFE, $numeroCFE, $isAnulado, $idEmpresa){
		$response = new stdClass();
		$userController = new ctr_users();
		$emitedClass = new vouchersEmitted();
		$utilsClass = new utils();


		// $business = $userController->getBusinesSession();
		$voucher = $emitedClass->getVoucherByTipoSerieNum($tipoCFE, $serieCFE, $numeroCFE, $idEmpresa);
		if ( $voucher->result == 2 ){

			$voucherName = $utilsClass->getNameVoucher($tipoCFE, $voucher->objectResult->isCobranza);

			if( $voucher->objectResult->isAnulado != $isAnulado ){
				$indice = $voucher->objectResult->indice;
				$response = $emitedClass->updateVoucherAnuladoByDgi($indice, $tipoCFE, $serieCFE, $numeroCFE, $isAnulado, $idEmpresa);
				error_log("ctr rest updateVoucherAnuladoDgi, comprobante ".$indice." valor para isAnulado en base local ".$voucher->objectResult->isAnulado." valor en ormen ".$isAnulado." resultado de actualizar la informacion ".$response->result);

				$response->message = "El comprobante ".$voucherName." ".$serieCFE."-".$numeroCFE." se encuentra anulado por DGI. Se actualiza la información.";
				$response->anular = false;
				return $response;
			}else{
				if ( $isAnulado == 0 ){
					//var_dump("el mismo dato que en la base local se puede anular");
					$response->anular = true;
				}else{
					//var_dump("el mismo dato que en la base local ya esta anulado por dgi");
					$response->message = "El comprobante ".$voucherName." ".$serieCFE."-".$numeroCFE." se encuentra anulado por DGI.";
					$response->anular = false;
				}
				$response->result = 2;
				return $response;
			}
		}else{
			$response = $voucher;
			$response->anular = false;
			return $response;
			error_log("ctr rest updateVoucherAnuladoDgi, no se encontro comprobante ".$tipoCFE." ". $serieCFE."-".$numeroCFE.", ".$idEmpresa);
		}

	}
}