<?php


require_once 'ctr_users.php';
require_once 'ctr_vouchers_emitted.php';
require_once 'ctr_vouchers_received.php';

require_once '../src/class/clients.php';
require_once '../src/utils/handle_date_time.php';

require_once '../src/utils/validate.php';


class ctr_clients{
	//UPDATED
	//buscar un listado de clientes
	public function searchClientsToSale($textToSearch, $idEmpresa, $rut, $token){
		$clientsClass = new clients();
		$restController = new ctr_rest();
		$response = new \stdClass();
		$response->result = 2;
		$response->listResult = array();

		$responseDataLocal = $clientsClass->getClientsListByDocumentOrName($textToSearch, $idEmpresa);
		$responseDataOrmen = $restController->buscarCliente($rut, $textToSearch, $token);
		$responseDataDgi = $restController->buscarClienteDGI($textToSearch, $token);

		$response->listResult = array_merge($responseDataLocal->listResult, $responseDataOrmen->listResult);
		$response->listResult = array_merge($response->listResult, $responseDataDgi->listResult);

		return $response;
	}

	//buscar datos de cliente segun documento
	//UPDATED
	public function searchClientToSale($documentClient, $idEmpresa, $rut, $token){
		$clientsClass = new clients();
		$restController = new ctr_rest();
		$validateClass = new validate();
		$response = new \stdClass();
		$responseGetClient = $clientsClass->getClient($documentClient, $idEmpresa);
		if($responseGetClient->result == 2){
			$responseGetClient->fromRest = 0;
			return $responseGetClient;
		}else{
			//traer datos de la base de ormen
			$responseOrmenClient = $restController->consultarCliente($rut, $documentClient, $token);
			if($responseOrmenClient->result == 2){
				$object = new \stdClass();
				$object->id = null;
				$object->docReceptor = $responseOrmenClient->cliente->document;
				$object->nombreReceptor = $responseOrmenClient->cliente->name;
				$object->direccion = null;
				$object->localidad = null;
				$object->departamento = null;
				foreach ($responseOrmenClient->cliente->contacts as $value) {
					if ( $value->contactType == 1 ){
						$object->correo = $value->value;
					}else{
						$object->celular = $value->value;
					}
				}
				$object->idEmpresa = null;
				$response->result = 2;
				$response->objectResult = $object;
				return $response;
			}else{
				//verificar si es un rut
				$responseValidateRut = $validateClass->validateRut($documentClient);
				//verificar si es una cédula
				$responseValidateCi = $validateClass->validateCI($documentClient);
				$ciLimpia = preg_replace( '/\D/', '', $documentClient );
				$validationDigit = $ciLimpia[-1];
				if($responseValidateRut->result == 2){ //es un rut
					$responseGetClient = $restController->consultarRut($rut, $documentClient, $token);
					if($responseGetClient->result == 2){
						$response->result = 2;
						$response->objectResult = $responseGetClient->empresa;
						$response->objectResult->idEmpresa = $idEmpresa; // Esto porque de aquel lado le ponia desde la SESSION Y YO LO SAQUE PORQUE NO QUIERO TOCAR MAS LA SESSION (PASO TODO POR PARAMETROS)
						$response->fromRest = 0;
						//guardar datos en base local
					}else return $responseGetClient;
				}
				else if ( $responseValidateCi == $validationDigit ){ //es una cédula
					$responseDataOrmen = $restController->buscarCliente($rut, $documentClient, $token);
					if ( $responseDataOrmen->result == 2 && $responseDataOrmen->listResult[0]->document != null){
						$object = new \stdClass();
						$object->id = null;
						$object->docReceptor = $responseDataOrmen->listResult[0]->document;
						$object->nombreReceptor = $responseDataOrmen->listResult[0]->name;
						$object->direccion = null;
						$object->localidad = null;
						$object->departamento = null;
						$object->correo = null;
						$object->celular = null;
						$object->idEmpresa = null;
						$response->result = 2;
						$response->objectResult = $object;
						return $response;
					}else{
						$result = $restController->buscarClienteGenaroUyCedula($documentClient);
						return $result;
					}
				}
				//else return $responseValidateRut;
				//si resulta que es una cedula buscamos el cliente localmente, sino se encuentra de manera local se devuelve solo el nombre
			}
		}
		return $response;
	}

	//controla que la convinacion de tipo de comprobante y documento de cliente sea valida para realizar la venta. Efactura solo con rut ETicket solo con ci
	//UPDATED
	public function isValidForEmit($typeVoucher, $documentClient){
		error_log("TIPO DE COMPROBANTE: $typeVoucher DOCUMENTO: $documentClient");
		$response = new \stdClass();
		$validateClass = new validate();

		$documentClient = $documentClient ?? "";
		$documentClient = trim($documentClient);

		$responseValidateRut = $validateClass->validateRUT($documentClient);
		$resultValidateCI = $validateClass->validateCI($documentClient);

		// Solo intentamos sacar el dígito si el string no está vacío
		$validationDigit = null;
		if (strlen($documentClient) > 0) {
			$validationDigit = intval(substr($documentClient, -1));
		}

		if ($validationDigit !== null && $resultValidateCI === $validationDigit)
			$resultValidateCI = true;
		else
			$resultValidateCI = false;

		// $validationDigit = intval($documentClient[-1]);
		// if ($resultValidateCI === $validationDigit)
		// 	$resultValidateCI = true;
		// else
		// 	$resultValidateCI = false;

		if($typeVoucher == 111 && $responseValidateRut->result == 2){
			$response->result = 2;
		}else if($typeVoucher == 111 && $responseValidateRut->result == 1){
			$response->result = 1;
			$response->message = $responseValidateRut->message;
		}else if($typeVoucher == 111 && $resultValidateCI){
			$response->result = 1;
			$response->message = "Para ventas a un cliente particular debe seleccionar ETicket en tipo de comprobantes";
		}else if($typeVoucher == 101 && $responseValidateRut->result == 2){
			$response->result = 1;
			$response->message = "Por ventas a empresa debe seleccionar EFactura en tipo de comprobantes.";
		}else if($typeVoucher == 101 && $resultValidateCI){
			$response->result = 2;
		}else if($typeVoucher == 101 && !$resultValidateCI){
			$response->result = 1;
			$response->message = "La cédula ingresada no es valida, por favor vuelva a ingresarlo.";
		}

		return $response;
	}

	//verifica si al cliente se le puede facturar un servicio.
	//UPDATED
	public function getBillableClients($dateEmitted, $currentSession){
		$response = new \stdClass();
		$clientsClass = new clients();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetClients = $clientsClass->getBillableClients($currentSession, $dateEmitted);
		if($responseGetClients->result == 2){
			$response->result = 2;
			$response->clients = $responseGetClients->listResult;
		}else return $responseGetClients;
		// }else return $responseGetBusiness;

		return $response;
	}

	//obtiene los saldos totales del cliente a la fecha
	public function getBalanceClient($documentClient){
		$response = new \stdClass();

		$resultMyBusiness = ctr_users::getBusinesSession();
		if($resultMyBusiness->result == 2){
			$resultGetClient = clients::getClient($documentClient, $resultMyBusiness->idBusiness);
			if($resultGetClient->result == 2){
				$resultBalanceToDate = ctr_vouchers_emitted::getBalanceToDateEmitted($resultGetClient->objectResult->id, $resultMyBusiness->idBusiness);
				if(!is_null($resultBalanceToDate->balanceUYU) || !is_null($resultBalanceToDate->balanceUSD)){
					$response->result = 2;
					$response->balanceUYU = number_format($resultBalanceToDate->balanceUYU,2,",",".");
					$response->balanceUSD = number_format($resultBalanceToDate->balanceUSD,2,",",".");
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y no se obtuvo el resultado de los saldos actuales para este cliente";
				}
			}else return $resultGetClient;
		}else return $resultMyBusiness;

		return $response;
	}

	//Busca un cliente por documento
	//UPDATED
	public function findClientWithDoc($docReceiver, $idEmpresa){
		$response = new \stdClass();
		$clientsClass = new clients();
		// $resultMyBusiness = ctr_users::getBusinesSession();
		// if($resultMyBusiness->result == 2){
		$resultGetClient = $clientsClass->getClient($docReceiver, $idEmpresa);
		if($resultGetClient->result == 2){
			$response->result = 2;
			$response->client = $resultGetClient->objectResult;
		}else return $resultGetClient;
		// }else return $resultMyBusiness;

		return $response;
	}

	//Busca un cliente por id de cliente
	//UPDATED
	public function getClientWithId($idReceiver){
		$response = new \stdClass();
		$clientsClass = new clients();

		// $resultMyBusiness = ctr_users::getBusinesSession();
		// if($resultMyBusiness->result == 2){
		$resultGetClient = $clientsClass->getClientWithId($idReceiver);
		if($resultGetClient->result == 2){
			$response->result = 2;
			$response->client = $resultGetClient->objectResult;
		}else return $resultGetClient;
		// }else return $resultMyBusiness;

		return $response;
	}
	//UPDATED
	public function updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idReceiver, $currentSession){
		$response = new \stdClass();
		$clientsClass = new clients();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$validateClass = new validate();

		$responseGetClient = $clientsClass->getClientWithId($idReceiver);
		if($responseGetClient->result == 2){
			$resultUpdateClient = $clientsClass->updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idReceiver);
			if($resultUpdateClient->result == 2){
				$arrayContacts = $clientController->prepareContactToSend($email, $numberMobile);
				$documentType = 1;
				if($validateClass->validateRut($responseGetClient->objectResult->docReceptor))
					$documentType = 2;
				$responseSendRest = $restController->modificarCliente($currentSession->rut, $responseGetClient->objectResult->docReceptor, $documentType, $nameReceiver, 1, $arrayContacts, $currentSession->tokenRest);
				if($responseSendRest->result == 2){
					$response->result = 2;
					$response->message = "EL cliente fue modificado correctamente.";
				}else{
					$response->result = 1;
					$response->message = "El cliente fue modificado correctamente únicamente para este sistema.";
				}
			}else return $resultUpdateClient;
		}else return $responseGetClient;

		return $response;
	}
	//UPDATED
	public function updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token){
		$response = new \stdClass();
		$clientClass = new clients();
		$userController = new ctr_users();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$validateClass = new validate();

		// $responseGetBusiness = $userController->getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// 	$idBusiness = $responseGetBusiness->idBusiness;
		// 	$rut = $responseGetBusiness->infoBusiness->rut;
		// }else return $responseGetBusiness;

		$resultUpdateClient = $clientClass->updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa);
		if($resultUpdateClient->result == 2){
			$arrayContacts = $clientController->prepareContactToSend($email, $numberMobile);
			$documentType = 1;
			if ( $validateClass->validateRUT($documentReceiver) ){
				$documentType = 2;
			}else{
				$ciLimpia = preg_replace( '/\D/', '', $documentReceiver );
				$validationDigit = $ciLimpia[-1];
				$validCi = $validateClass->validateCI($documentReceiver);
				if ($validationDigit == $validCi)
					$documentType = 3;
			}

			$responseSendRest = $restController->modificarCliente($rut, $documentReceiver, $documentType, $nameReceiver, 1, $arrayContacts, $token);
			if($responseSendRest->result == 2){
				$response->result = 2;
				$response->message = "EL cliente fue modificado correctamente.";
			}else{
				$response->result = 1;
				$response->message = "El cliente fue modificado correctamente únicamente para este sistema.";
			}
		}else return $resultUpdateClient;
		return $response;
	}
	//UPDATED
	public function createModifyClient($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token){
		$clientClass = new clients();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$arrayErrors = array();
		$response = new \stdClass();
		$validateClass = new validate();

		//si el dato es vacio lo paso a null
		$locality = $locality == "" ? null : $locality;
		$department = $department == "" ? null : $department;
		$email = $email == "" ? null : $email;
		$numberMobile = $numberMobile == "" ? null : $numberMobile;
		$addressReceiver = $addressReceiver == "" ? null : $addressReceiver;

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// 	$idBusiness = $responseGetBusiness->idBusiness;
		// 	$rut = $responseGetBusiness->infoBusiness->rut;
		// }else return $responseGetBusiness;
		$objClient = $clientClass->getClient($documentReceiver, $idEmpresa);
		if ( $objClient->result == 2 ){ //el cliente se encuentra en el sistema hay que modificar
			$resultModify = $clientController->updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token);
			if ( $resultModify->result != 2 ){
				array_push( $arrayErrors, $resultModify->message );
				$response->result = $resultModify->result;
				if ( $resultModify->result == 1 ) $response->result = 2; /*** si $resultModify es result 1 significa que en ormen no se modificó y localmente si*/
			}else $response->result = 2;
		}else{ //hay que crear el cliente
			$resultNewClient = $clientClass->insertClient($documentReceiver, $nameReceiver, $addressReceiver, $locality, $department, $email, $numberMobile, $idEmpresa);
			if ( $resultNewClient->result != 2 ){
				array_push( $arrayErrors, $resultNewClient->message );
			}else $response->result = 2;
		}

		$arrayContacts = $clientController->prepareContactToSend($email, $numberMobile);
		$documentType = 1;
		if ( $validateClass->validateRUT($documentReceiver) ){
			$documentType = 2;
		}else{
			$ciLimpia = preg_replace( '/\D/', '', $documentReceiver );
			$validationDigit = $ciLimpia[-1];
			$validCi = $validateClass->validateCI($documentReceiver);
			if ($validationDigit == $validCi)
				$documentType = 3;
		}
		$resultOrmenClient = $restController->consultarCliente($rut, $documentReceiver, $token);
		if ( $resultOrmenClient->result == 2 ){ //ntonces el cliente ya esta guardado en ormen
			$responseSendRest = $restController->modificarCliente($rut, $documentReceiver, $documentType, $nameReceiver, 1, $arrayContacts, $token);
			if($responseSendRest->result == 2){
				$response->result = 2;
				$response->message = "EL cliente fue modificado correctamente.";
			}else{
				$response->result = 1;
				$response->message = "El cliente fue modificado correctamente únicamente para este sistema.";
			}
		}else{ //no se encuentra cliente en ormen, se agrega
			$object = array(
				'document' => $documentReceiver,
				'name' => $nameReceiver,
				'notificationMethods' => [1],
				'documentType' => $documentType,
				'contacts' => $arrayContacts
			);
			$responseSendRest = $restController->nuevoCliente($rut, $object, $token);
			if($responseSendRest->resultado->codigo == 200){
				$response->result = 2;
				$response->message = "EL cliente fue creado correctamente.";
			}else{
				$response->result = 1;
				$response->message = $responseSendRest->resultado->error;
			}
		}
		return $response;
	}

	// NEW TEST
	public function createModifyClientJustLocal($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token){
		$clientClass = new clients();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$arrayErrors = array();
		$response = new \stdClass();
		$validateClass = new validate();

		//si el dato es vacio lo paso a null
		$locality = $locality == "" ? null : $locality;
		$department = $department == "" ? null : $department;
		$email = $email == "" ? null : $email;
		$numberMobile = $numberMobile == "" ? null : $numberMobile;
		$addressReceiver = $addressReceiver == "" ? null : $addressReceiver;

		$objClient = $clientClass->getClient($documentReceiver, $idEmpresa);
		if ( $objClient->result == 2 ){ //el cliente se encuentra en el sistema hay que modificar
			$resultModify = $clientController->updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token);
			if ( $resultModify->result != 2 ){
				array_push( $arrayErrors, $resultModify->message );
				$response->result = $resultModify->result;
				if ( $resultModify->result == 1 ) $response->result = 2; /*** si $resultModify es result 1 significa que en ormen no se modificó y localmente si*/
			}else $response->result = 2;
		}else{ //hay que crear el cliente
			$resultNewClient = $clientClass->insertClient($documentReceiver, $nameReceiver, $addressReceiver, $locality, $department, $email, $numberMobile, $idEmpresa);
			if ( $resultNewClient->result != 2 ){
				array_push( $arrayErrors, $resultNewClient->message );
			}else $response->result = 2;
		}
				$response->result = 2;
				$response->message = "EL cliente fue creado correctamente.";
		return $response;
	}
	// NEW TEST
	public function createModifyClientFromPointSale($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token){
		$clientClass = new clients();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$response = new \stdClass();
		$validateClass = new validate();

		//si el dato es vacio lo paso a null
		$locality = $locality == "" ? null : $locality;
		$department = $department == "" ? null : $department;
		$email = $email == "" ? null : $email;
		$numberMobile = $numberMobile == "" ? null : $numberMobile;
		$addressReceiver = $addressReceiver == "" ? null : $addressReceiver;

		$local = false;
		$rest = false;

		$objClient = $clientClass->getClient($documentReceiver, $idEmpresa);
		$needsUpdate = false;

		error_log(print_r($objClient, true));
		if ( $objClient->result == 2 ){ //el cliente se encuentra en el sistema hay que modificar si es distinto
			if($objClient->objectResult->localidad != $locality){
				$needsUpdate = true;
			}
			if($objClient->objectResult->departamento != $department){
				$needsUpdate = true;
			}
			if($objClient->objectResult->correo != $email){
				$needsUpdate = true;
			}
			if($objClient->objectResult->celular != $numberMobile){
				$needsUpdate = true;
			}
			if($objClient->objectResult->direccion != $addressReceiver){
				$needsUpdate = true;
			}
			if($needsUpdate){// Ejecutar la actualización del cliente
				$responseUpdateClientLocal = $clientClass->updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $objClient->objectResult->id);
				if($responseUpdateClientLocal->result == 2){
					$response->result = 2; 
					$response->message = "Cliente correctamente actualizado de forma local"; 
					$local = true;
				} else {
					$response->result = 2; 
					$response->message = "Cliente no se pudo actualizar"; 
				}
			} else {// Es igual, no hace falta
				$response->result = 2; 
				$response->message = "Cliente no modificado"; 
			}
		} else { //hay que crear el cliente
			$resultNewClient = $clientClass->insertClient($documentReceiver, $nameReceiver, $addressReceiver, $locality, $department, $email, $numberMobile, $idEmpresa);
			if ( $resultNewClient->result == 2 ){
				$response->result = 2; 
				$response->message = "Cliente creado de forma local"; 
				$local = true;
			} else {
				$response->result = 2; 
				$response->message = "Cliente no se pudo crear"; 
			}
		}

		if(!$needsUpdate){
			return $response;
		}

		$emails = $email;
		$arrayEmails = explode(';', $emails);
		$validEmails = array();
		foreach ($arrayEmails as $emailAux) {
			$emailAux = trim($emailAux); // Eliminar espacios en blanco
			if (!empty($emailAux) && filter_var($emailAux, FILTER_VALIDATE_EMAIL)) {
				$validEmails[] = $emailAux; // Solo agregar si es válido
			}
		}
		error_log(print_r($validEmails, true));

		// Si viene el mail entonces tambien mando para Ormen
		if(!empty($validEmails)){
			//consulto si exite
			$arrayContacts = $clientController->prepareContactToSend($email, $numberMobile);
			$documentType = 1;
			$responseValidateRUT = $validateClass->validateRUT($documentReceiver);
			error_log(print_r($responseValidateRUT, true));
			if ( $responseValidateRUT->result == 2 ){
				$documentType = 2;
			} else {
				$ciLimpia = preg_replace( '/\D/', '', $documentReceiver );
				$validationDigit = $ciLimpia[-1];
				$validCi = $validateClass->validateCI($documentReceiver);
				if ($validationDigit == $validCi)
					$documentType = 3;
			}
			$resultOrmenClient = $restController->consultarCliente($rut, $documentReceiver, $token);
			error_log(print_r($resultOrmenClient, true));
			if ( $resultOrmenClient->result == 2 ){ //ntonces el cliente ya esta guardado en ormen
				$responseSendRest = $restController->modificarCliente($rut, $documentReceiver, $documentType, $nameReceiver, 1, $arrayContacts, $token);
				error_log(print_r($responseSendRest, true));
				if($responseSendRest->result == 2){
					$rest = true;
					$response->result = 2;
					$response->message = "Cliente correctamente actualizado";
				} else {
					$response->result = 2;
					$response->message = "Cliente no se pudo actualizar";
				}
			}else{ //no se encuentra cliente en ormen, se agrega
				$object = array(
					'document' => $documentReceiver,
					'name' => $nameReceiver,
					'notificationMethods' => [1],
					'documentType' => $documentType,
					'contacts' => $arrayContacts
				);
				$responseSendRest = $restController->nuevoCliente($rut, $object, $token);
				error_log(print_r($responseSendRest, true));
				if($responseSendRest->resultado->codigo == 200){
					$rest = true;
					$response->result = 2;
					$response->message = "EL cliente fue creado correctamente.";
				} else {
					$response->result = 2;
					$response->message = "Cliente no se pudo crear";
				}
			}
		} else {
			// No lo guardo en Ormen porque el/los mail no tiene/n formato correcto
		}
		return $response;
	}

	//NEW TEST
	public function updateClientByDocumentJustLocal($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa, $rut, $token){
		$response = new \stdClass();
		$clientClass = new clients();
		$userController = new ctr_users();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$validateClass = new validate();
		$resultUpdateClient = $clientClass->updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idEmpresa);
		if($resultUpdateClient->result == 2){
			$response->result = 2;
			$response->message = "EL cliente fue modificado correctamente.";
		}else return $resultUpdateClient;
		return $response;
	}
	//UPDATED
	public function prepareContactToSend($emails, $numbers){
		// $restController = new ctr_rest();
		$newArray = array();
		if(!is_null($emails)){
			$arrayValue = explode(";", $emails);
			foreach ($arrayValue as $key => $value) {
				// $valueContact = $restController->prepareContactDetail(1, $value);
				$newArray[] = array(
					"contactType" => 1,
					"value" => $value
				);
				// $newArray[] = $valueContact;
			}
		}

		if(!is_null($numbers)){
			$arrayValue = explode(";", $numbers);
			foreach ($arrayValue as $key => $value) {
				// $valueContact = $restController->prepareContactDetail(5, $value);
				$newArray[] = array(
					"contactType" => 5,
					"value" => $value
				);
				// $newArray[] = $valueContact;
			}
		}

		return $newArray;
	}

	//lista de clientes con los parametros acomodados para ser mostrados en pantalla montos con .00
	//UPDATED
	public function getListClientsView($lastId, $textToSearch, $withBalance, $currentSession){
		$response = new \stdClass();
		$clientClass = new clients();
		$voucherEmittedController = new ctr_vouchers_emitted();

		// $resultMyBusiness = ctr_users::getBusinesSession();
		// if($resultMyBusiness->result == 2){
		$resultListClient = $clientClass->getListClientsView($lastId, $textToSearch, $withBalance, $currentSession->idEmpresa);
		if($resultListClient->result == 2){
			$listClientsWithBalance = array();
			foreach($resultListClient->listResult as $key => $value){
				$resultBalances = $voucherEmittedController->getBalanceToDateEmitted($value['id'], $currentSession);
				$value['saldoUYU'] = number_format($resultBalances->balanceUYU,2,",",".");
				$value['saldoUSD'] = number_format($resultBalances->balanceUSD,2,",",".");
				$listClientsWithBalance[] = $value;
			}
			$response->result = 2;
			$response->listResult = $listClientsWithBalance;
			$response->lastId = $resultListClient->lastId;
		}else return $resultListClient;
		// }else $resultMyBusiness;

		return $response;
	}

	// lista de clientes para la datalist de sugerencias
	//UPDATED
	public function getClientsForModal($suggestionClient, $idEmpresa){
		$response = new \stdClass();
		$clientClass = new clients();

		// $resultMyBusiness = ctr_users::getBusinesSession();
		// if($resultMyBusiness->result == 2){
		$resultGetClients = $clientClass->getClientsForModal($suggestionClient, $idEmpresa);
		if($resultGetClients->result == 2){
			$response->result = 2;
			$response->listPeople = $resultGetClients->listResult;
		}else return $resultGetClients;
		// }else return $resultMyBusiness;

		return $response;
	}

	public function getContactsString($listContacts){
		$contacts = array('email' => null, "celular" => null);

		foreach($listContacts as $key => $contact){
			$contactTypeString = null;
			if($contact->tipoContacto == "CORREO ELECTRONICO") $contactTypeString = "email";
			else if($contact->tipoContacto == "TELEFONO MOVIL") $contactTypeString = "celular";

			if(!is_null($contactTypeString)){
				if(!is_null($contacts[$contactTypeString]))
					$contacts[$contactTypeString] .= ";" . $contact->datoContacto;
				else
					$contacts[$contactTypeString] = $contact->datoContacto;
			}
		}

		return $contacts;
	}

	//agrega los clientes obtenidos de los cfe para agregarlos en la base de datos
	//UPDATED
	public function insertClientFirstLogin($rut, $documentClient, $nameClient, $currentSession){
		$clientController = new ctr_clients();
		$response = new \stdClass();
		$arrayErrors = array(); //usarlo
		$usersClass = new users();
		$othersClass = new others();
		$clientClass = new clients();
		$validateClass = new validate();
		$userController = new ctr_users();
		$restController = new ctr_rest();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$arrayLetters = array(" ","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
		$documentClient = str_replace($arrayLetters, "", strtolower($documentClient));
		$responseValidateRut = $validateClass->validateRut($documentClient);
		if($responseValidateRut->result == 2){
			// $responseGetToken = ctr_rest::getToken();
			// if($responseGetToken->result == 2){
			$responseSendRest = $restController->consultarRut($rut, $documentClient, $currentSession->tokenRest);
			if($responseSendRest->result == 2){
				$contacts = $clientController->getContactsString($responseSendRest->empresa->contactos);
				$business = $responseSendRest->empresa;
				$responseSendQuery = $clientClass->insertClient($documentClient, $business->razonSocial, $business->direccion, $business->localidad, $business->departamento, $contacts['email'], $contacts['celular'], $currentSession->idEmpresa);
				if($responseSendQuery->result == 2){
					$responseClientEfact = $clientController->newClientEfactura($documentClient, $business->razonSocial, $contacts['email'], $contacts['celular'], $rut, $currentSession);
					$logFile = fopen("../lognuevo.txt", 'a') or die("Error creando archivo");
					fwrite($logFile, "\n".date("d/m/Y H:i:s ")."Primer login. Nuevo cliente en EFactura, documento: ".$documentClient);
					fwrite($logFile, "\n".date("d/m/Y H:i:s ")."Respuesta: ".$responseClientEfact->result." ".$responseClientEfact->message);
					fclose($logFile);
					$response->result = 2;
					$response->message = "Cliente agregado";
					$response->id = $responseSendQuery->id;
				}else return $responseSendQuery;
			}else {
				error_log("CONSULTA RUT CON ERROR ".$responseSendRest->message);
				return $responseSendRest;
			}
			// }else return $responseGetToken;
		}else{
			$responseSendQuery = $clientClass->insertClient($documentClient, $nameClient, null, null, null, null, null, $currentSession->idEmpresa);
			if($responseSendQuery->result == 2){
				$responseClientEfact = $clientController->newClientEfactura($documentClient, $nameClient, null, null, $rut,$currentSession);
				$logFile = fopen("../lognuevo.txt", 'a') or die("Error creando archivo");
				fwrite($logFile, "\n".date("d/m/Y H:i:s ")."Primer login! Nuevo cliente en EFactura, documento: ".$documentClient);
				fwrite($logFile, "\n".date("d/m/Y H:i:s ")."Respuesta: ".$responseClientEfact->result." ".$responseClientEfact->message);
				fclose($logFile);
				$response->result = 2;
				$response->message = "Cliente agregado";
				$response->id = $responseSendQuery->id;
			}else return $responseSendQuery;
		}
		// }else return $responseMyBusiness;
		return $response;
	}

	//se pasan los datos del cliente nuevo para que se guarde en efactura
	public function newClientEfactura($document, $name, $mail, $mobile, $rut, $currentSession){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$validateClass = new validate();
		// $responseMyBusiness = ctr_users::getBusinesSession();
		$documentType = 1;
		if($validateClass->validateRut($document)->result == 2 )
			$documentType = 2;

		$contacts = $this->prepareContactToSend($mail, $mobile);

		$object = array(
			'document' => $document,
			'name' => $name,
			'notificationMethods' => [1],
			'documentType' => $documentType,
			'contacts' => $contacts
		);

		$responseRest = $restController->nuevoCliente($rut, $object, $currentSession->tokenRest);
		if ( $responseRest->resultado->codigo != 200 ){
			$response->result = 1;
			$response->message = $responseRest->resultado->error;
		}else{
			$response->result = 2;
			$response->message = "Cliente con documento ".$document." ingresado correctamente.";
		}

		return $response;
	}

	//se obtienen todos los clientes que se encuentran en la base local y se guardan en efactura
	//WORKING
	public function loadCustomersEfactura($currentSession){
		$response = new \stdClass();
		$response->result = 2;
		$arrayErrors = array();
		$clientClass = new clients();
		$restController = new ctr_rest();
		$validateClass = new validate();
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if ( isset($responseMyBusiness)){
			// if ( $responseMyBusiness->result == 2 ){
				//obtener todos los clientes que se registraron localmente para esta empresa
		$listClients = $clientClass->getAllCustomersByBusiness($currentSession->idEmpresa);
		if (isset($listClients)){
			if ( $listClients->result == 2 ){
				if ( count($listClients->listResult) >0 ){
					foreach ($listClients->listResult as $value) {
						set_time_limit ( 15 );

						$documentType = 1;
						if($validateClass->validateRut($value['docReceptor'])->result == 2 )
							$documentType = 2;

						$contacts = $this->prepareContactToSend($value['correo'], $value['celular']);

						$object = array(
							'document' => $value['docReceptor'],
							'name' => $value['nombreReceptor'],
							'notificationMethods' => [1],
							'documentType' => $documentType,
							'contacts' => $contacts);
						$responseRest = $restController->nuevoCliente($currentSession->rut, $object, $currentSession->tokenRest);
						if ( $responseRest->resultado->codigo != 200 ){
							$response->result = 1;
							array_push($arrayErrors, $responseRest->resultado->codigo.": ".$responseRest->resultado->error);
						}else
							array_push($arrayErrors, $responseRest->resultado->codigo.": "."Cliente con documento ".$value['docReceptor']." ingresado correctamente.");
					}
				}else{
					$response->result = 2;
					$response->message = "No se encontraron clientes.";
				}
			}
		}
			// }else array_push($arrayErrors, $responseMyBusiness->message);
		// }else array_push($arrayErrors, $responseMyBusiness->message);

		$responseUpdate = $this->updateCustomersEfactura($currentSession);

		$response->message = array_merge($arrayErrors, $responseUpdate->message);
		//$arrayErrors;
		return $response;
	}

	//se obtienen todos los clientes que se encuentran en la base local y se modifican los datos en efactura
	//WORKING
	public function updateCustomersEfactura($currentSession){
		$response = new \stdClass();
		$response->result = 2;
		$arrayErrors = array();
		$clientClass = new clients();
		$restController = new ctr_rest();
		$validateClass = new validate();
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if ( isset($responseMyBusiness)){
		// 	if ( $responseMyBusiness->result == 2 ){
		//obtener todos los clientes que se registraron localmente para esta empresa
		$listClients = $clientClass->getAllCustomersByBusiness($currentSession->idEmpresa);
		if (isset($listClients)){
			if ( $listClients->result == 2 ){
				foreach ($listClients->listResult as $value) {
					set_time_limit ( 15 );
					$documentType = 1;
					if($validateClass->validateRut($value['docReceptor'])->result == 2 )
						$documentType = 2;
					$contacts = $this->prepareContactToSend($value['correo'], $value['celular']);
					$responseRest = $restController->modificarCliente($currentSession->rut, $value['docReceptor'], $documentType, $value['nombreReceptor'], 1, $contacts, $currentSession->tokenRest);
					if ( $responseRest->result != 2 ){
						$response->result = 1;
						array_push($arrayErrors, "500: ".$responseRest->message);
					}else
						array_push($arrayErrors, "200: ".$responseRest->message);
				}
			}
		}
		// 	}else array_push($arrayErrors, $responseMyBusiness->message);
		// }else array_push($arrayErrors, $responseMyBusiness->message);

		$response->message = $arrayErrors;
		return $response;
	}


	//UPDATED
	public function exportExcelDeudores($dateTo, $currentSession){
		//misma consulta que getListClientsView pero esta no tiene limite por paginacion y no manda lastid ni texto
		$response = new \stdClass();
		$clientsClass = new clients();
		$voucherEmittedController = new ctr_vouchers_emitted();
		// $resultMyBusiness = ctr_users::getBusinesSession();
		// if($resultMyBusiness->result == 2){
		$resultListClient = $clientsClass->getListDeudoresToExport($currentSession->idEmpresa, $dateTo);
		if($resultListClient->result == 2){
			$listClientsWithBalance = array();
			foreach($resultListClient->listResult as $key => $value){
				$resultBalances = $voucherEmittedController->getBalanceToDateEmitted($value['id'], $currentSession);
				$value['saldoUYU'] = $resultBalances->balanceUYU;
				$value['saldoUSD'] = $resultBalances->balanceUSD;
				$listClientsWithBalance[] = $value;
			}
			$response->result = 2;
			$response->listResult = $listClientsWithBalance;
		}else return $resultListClient;
		// }else $resultMyBusiness;
		return $response;
	}
}