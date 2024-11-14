<?php

require_once '../src/class/defaultclass/users.php';
require_once '../src/class/others.php';

require_once '../src/utils/validate.php';
require_once '../src/utils/handle_date_time.php';

require_once 'rest/ctr_rest.php';
require_once 'ctr_clients.php';
require_once 'ctr_products.php';
require_once 'ctr_vouchers.php';
require_once 'ctr_vouchers_emitted.php';
require_once 'ctr_vouchers_received.php';


class ctr_users{

	//obtiene la lista de permisos de un usuario, a que secciones puede acceder y que funciones puede ejecutar
	//UPDATED
	public function getListPermissions($idEmpresa){
		$usersClass = new users();
		$response = new \stdClass();
		// $responseGetBusiness = ctr_users::getBusinesSession(); // ESTO ES AL PEDO || SACAR
		// if($responseGetBusiness->result == 2){
		$responseServicePermission = $usersClass->getPermission("SERVICE", $idEmpresa);
		if($responseServicePermission->result == 2)
			$response->service = $responseServicePermission->objectResult->permiso;
		$responseVentasPermission = $usersClass->getPermission("VENTAS", $idEmpresa);
		if($responseVentasPermission)
			$response->ventas = $responseVentasPermission->objectResult->permiso;
		// }else return $responseGetBusiness;

		return $response;
	}

	public function setUpdatedDetails($idBusiness){
		return users::setUpdatedDetails($idBusiness);
	}

	//obtiene el ultimo estado de cuenta que se genero para sugerirlo en el modal, se guarda al generar un estado de cuenta esta info
	//[OK] UNICO QUE ACCEDE A LA SESSION DIRECTAMENTE [NO ME GUSTA PERO POR AHORA LO DEJAMOS ASI 10/2024 11/2024]
	public function getLastAccountStateInfo($prepareFor){
		$response = new \stdClass();
		$usersClass = new users();

		if(isset($_SESSION['systemSession'])){
			$sesion = $_SESSION['systemSession'];
			if($prepareFor == "CLIENT"){
				if(isset($sesion->accountStateClient)){
					$response->result = 2;
					$response->information = $sesion->accountStateClient;
				}else $response->result = 1;
			}else if($prepareFor == "PROVIDER"){
				if(isset($sesion->accountStateProvider)){
					$response->result = 2;
					$response->information = $sesion->accountStateProvider;
				}else $response->result = 1;
			}else $response->result = 1;

			$showCheckBox = "NO";
			$responseGetConfig = $usersClass->getConfigurationUser($sesion->idUser, "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA");
			if($responseGetConfig->result == 2)
				$showCheckBox = $responseGetConfig->objectResult->valor;
			$response->showCheckBoxCash = $showCheckBox;
		}else{
			$response->result = 0;
		}

		return $response;
	}

	public function getSuggestionRut($rutPart){
		$response = new \stdClass();
		$usersClass = new users();
		$responseRuts = $usersClass->getSuggestionRut($rutPart);
		if($responseRuts->result == 2){
			$response->result = 2;
			$response->listResult = $responseRuts->listResult;
		}else return $responseRuts;
		return $response;
	}
	//UPDATED
	public function getBranchCompanyByRut($currentSession){
		$response = new \stdClass();
		$restController = new ctr_rest();

		$responseSuc = $restController->getBranchCompanyByRut($currentSession->rut, $currentSession->tokenRest);
		if($responseSuc->result == 2){
			$response->result = 2;
			$response->listResult = $responseSuc->branchCompany;
		}else return $responseSuc;
		return $response;
	}

	//MV:obtiene una variable  de configuracion segun el nombre (sirve para cualquier configuracion)
	//VL:En esta funciòn se obtiene el usuario que està en la sesiòn actual y consulta en la tabla "configuraciones" si ese usuario tiene permisos para esa variable
	//MA: Saco todo lo de static y las llamadas al pedo a la base, solo dejo lo indispensable los permisos que los obtenga SI (Por si se quitan mientras esta en curso la session y que no haga falta salir y entrar) pero la informacion de la empresa quedara en el sassion
	//$variable es por ejemplo "PERIODOS_FACTURACION_SERVICIOS"
	//UPDATED
	public function getVariableConfiguration($variable, $currentSession){
		$response = new \stdClass();
		$usersClass = new users();
		$responseGetConfig = $usersClass->getConfigurationUser($currentSession->idUser, $variable);
		if($responseGetConfig->result == 2){
			$response->result = 2;
			$response->configValue = $responseGetConfig->objectResult->valor;
		}else{
			$response->result = 1;
			$response->message = "La variable que intenta obtener no fue ingresada en la base de datos.";
		}
		return $response;
	}
	//UPDATED
	public function getListConfigurationUser($idUsuario){
		$usersClass = new users();
		return $usersClass->getListConfigurationUser($idUsuario);
		// $resultGetUser = ctr_users::getUserInSesion();
		// if($resultGetUser->result == 2){
		// }else return $resultGetUser;
	}

	public function getListIvas(){
		$othersClass = new others();
		return $othersClass->getListIva();
	}

	//WORKING
	public function updateVariableUser($variable, $value, $currentSession){
		$response = new \stdClass();
		$usersClass = new users();
		$userControllerInstance = new ctr_users();
		// $responseMyBusiness = $userControllerInstance->getBusinesSession();
		// if($responseMyBusiness->result == 2){
		// $resultGetUser = $userControllerInstance->getUserInSesion();
		// if($resultGetUser->result == 2){
		$resultGetConfiguration = $usersClass->getConfigurationUser($currentSession->idUser, $variable);
		if($resultGetConfiguration->result == 2){
			$responseSendQuery = $usersClass->updateConfigurationUser($resultGetConfiguration->objectResult->id, $currentSession->idUser, $variable, $value);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->message = "Su configuración fue modificada correctamente.";
			}else return $responseSendQuery;
		}else return $resultGetConfiguration;
		// }else return $resultGetUser;
		// }else return $responseMyBusiness;

		return $response;
	}

	public function setValueUpdateVouchers($value){
		$responseGetUser = ctr_users::getUserInSesion();
		if($responseGetUser->result == 2){
			return users::updatedVouchers($responseGetUser->objectResult->idUsuario, $value);
		}else return $responseGetUser;
	}

	//actualiza la cantidad de comprobantes emitidos que se obtuvieron al iniciar sesión, cuanta todos los del dia, no solo los nuevos.
	public function updatedVouchers($vouchersEmitted, $vouchersEmittedInserted, $voucherReceived, $voucherReceivedInserted){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseGetUser = ctr_users::getUserInSesion();
			if($responseGetUser->result == 2){
				users::insertHistoric($responseGetUser->objectResult->idUsuario, $vouchersEmitted, $vouchersEmittedInserted, $voucherReceived, $voucherReceivedInserted, $responseMyBusiness->idBusiness);
				return users::updatedVouchers($responseGetUser->objectResult->idUsuario, 1);
			}else return $responseGetUser;
		}else return $responseMyBusiness;
	}

	// public function getUserInSesion(){ // ELIMINAR ELIMINAR
	// 	if(isset($_SESSION['systemSession'])){
	// 		$session = $_SESSION['systemSession'];
	// 		return users::getUserById($session->idUser);
	// 	}
	// }

	public function getBusinessInformation($idBusiness){
		return users::getBusinessWithId($idBusiness);
	}

	public function getBusinessInformationByRut($rut){
		return users::getBusiness($rut);
	}

	/*
	* VL: Se obtienen los datos de la empresa mediante el id que se encuentra en la session
	*/
	// public function getBusinesSession(){
	// 	$response = new \stdClass();

	// 	if(isset($_SESSION['systemSession'])){// se verifica que haya una sesion activa
	// 		$sesion = $_SESSION['systemSession'];
	// 		if(isset($sesion->idBusiness)){
	// 			$responseGetBusiness = users::getBusinessWithId($sesion->idBusiness); //$responseGetBusiness todos los datos de la empresa que pasas el id
	// 			if($responseGetBusiness->result == 2){
	// 				$response->result = 2;
	// 				$response->idBusiness = $sesion->idBusiness;//id de la empresa de la sesiòn
	// 				$response->infoBusiness = $responseGetBusiness->objectResult; //todos los demàs datos de la empresa
	// 			}else{
	// 				unset($_SESSION['systemSession']);//session_destroy();
	// 				return $responseGetBusiness;
	// 			}
	// 		}else{
	// 			unset($_SESSION['systemSession']);//session_destroy();
	// 			$response->result = 1;
	// 			$response->message = "No se encontro la empresa vinculada a su usuario, por favor vuelva a iniciar sesión";
	// 		}
	// 	}else{
	// 		unset($_SESSION['systemSession']);//session_destroy();
	// 		$response->result = 0;
	// 		$response->message = "Su sesión caducó, por favor vuelva a ingresar.";
	// 	}

	// 	return $response;
	// }
	// public function getInfoEmpresa(){  // [OLD] getBusinesSession
	// 	$response = new \stdClass();
	// 	$sesion = $_SESSION['systemSession'];
	// 	if(isset($sesion->idEmpresa)){
	// 		$responseGetEmpresa = users::getEmpresaById($sesion->idEmpresa); //$responseGetBusiness todos los datos de la empresa que pasas el id
	// 		if($responseGetEmpresa->result == 2){
	// 			$response->result = 2;
	// 			$response->idEmpresa = $sesion->idEmpresa;//id de la empresa de la sesiòn
	// 			$response->infoEmpresa = $responseGetEmpresa->objectResult; //todos los demàs datos de la empresa
	// 		}else{
	// 			unset($_SESSION['systemSession']);//session_destroy();
	// 			return $responseGetEmpresa;
	// 		}
	// 	}else{
	// 		unset($_SESSION['systemSession']);//session_destroy();
	// 		$response->result = 1;
	// 		$response->message = "No se encontro la empresa vinculada a su usuario, por favor vuelva a iniciar sesión";
	// 	}
	// 	return $response;
	// }

	public function updateDataVouchersAdmin($currentSession){
		$response = new \stdClass();
		$usersClass = new users();
		$voucherEmittedController = new ctr_vouchers_emitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// $responseGetUser = ctr_users::getUserInSesion();
		// if($responseGetUser->result == 2){
		$responseGetSuperUser = $usersClass->itsSuperUser($currentSession->userName);
		if($responseGetSuperUser->result == 2){
			$responseUpdateVouchers = $voucherEmittedController->getVouchersEmittedFirstLogin($currentSession, 200, null);
			if($responseUpdateVouchers->counterInserted == $responseUpdateVouchers->counterRecords){
				$response->result = 2;
				$response->message = "Se actulizó la lista de comprobantes emitidos.";
			}else if($responseUpdateVouchers->counterInserted > 0 && sizeof($responseUpdateVouchers->arrayErrors) > 0){
				$response->result = 1;
				$response->message = "Algunos comprobantes emitidos no fueron ingresados al sisitema.";
			}else if($responseUpdateVouchers->counterInserted == 0){
				$response->result = 0;
				$response->message = "Ningun comprobante fue ingresado al sistema.";
			}
		}else {
			$response->result = 0;
			$response->message = "Esta función es exclusiva para usuarios administradores.";
		}
		// }else return $responseGetUser;
		// }else return $responseGetBusiness;

		return $response;
	}

	//carga todos los comprobantes emitidos y recibidos por primera vez.
	public function loadDataFirstLogin(){
		set_time_limit(180);
		//error_log( "FATAL ERROR : respuesta time limit ".$time_limit );
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){ //info de la empresa

			$responseGetUserInSession = ctr_users::getUserInSesion();
			if($responseGetUserInSession->result == 2){ //si hay un usuario en session

				$responseIsSuperUser = users::itsSuperUser($responseGetUserInSession->objectResult->correo);
				if($responseIsSuperUser->result == 2){ //si el usuario logueado es superuser

					$vouchersReceived = 0;
					$vouchersReceivedInserted = 0;
					$resultReceived = null;
					$responseGetReceived = ctr_vouchers_received::getVouchersReceivedFirstLogin($responseGetBusiness->infoBusiness->rut, 200, null);

					if(isset($responseGetReceived)){
						if(isset($responseGetReceived->counterRecords) && isset($responseGetReceived->counterInserted)){
							if($responseGetReceived->counterRecords == $responseGetReceived->counterInserted)
								$resultReceived = 2;
							else if($responseGetReceived->counterRecords < $responseGetReceived->counterInserted && $responseGetReceived->counterInserted > 0)
								$resultReceived = 1;
							else if($responseGetReceived->counterInserted == 0)
								$resultReceived = 0;

							if ( $responseGetReceived->counterRecords > 0 ){
								$vouchersReceived = $responseGetReceived->counterRecords;
							}

							if ( $responseGetReceived->counterInserted > 0 ){
								$vouchersReceivedInserted = $responseGetReceived->counterInserted;
							}
						}
						else if(isset($responseGetReceived->result)) {
							if ($responseGetReceived->result == 1) {
								$resultReceived = 2;
							}
							if($responseGetReceived->result == 0){
								$resultReceived = 0;
							}
						}
					}

					$vouchersEmitted = 0;
					$vouchersEmittedInserted = 0;
					$resultEmitted = null;
					$responseGetEmitted = ctr_vouchers_emitted::getVouchersEmittedFirstLogin($responseGetBusiness->infoBusiness->rut, 200, null);
					if(isset($responseGetEmitted)){
						if(isset($responseGetEmitted->counterRecords) && isset($responseGetEmitted->counterInserted)){
							if($responseGetEmitted->counterRecords == $responseGetEmitted->counterInserted)
								$resultEmitted = 2;
							else if($responseGetEmitted->counterRecords < $responseGetEmitted->counterInserted && $responseGetEmitted->counterInserted > 0)
								$resultEmitted = 1;
							else if($responseGetEmitted->counterInserted == 0)
								$resultEmitted = 0;

							if ( $responseGetEmitted->counterRecords > 0 ){
								$vouchersEmitted = $responseGetEmitted->counterRecords;
							}

							if ( $responseGetEmitted->counterInserted > 0 ){
								$vouchersEmittedInserted = $responseGetEmitted->counterInserted;
							}
						}
						else if(isset($responseGetEmitted->result)) {
							if ($responseGetEmitted->result == 1) {
								$resultEmitted = 2;
							}
							if($responseGetEmitted->result == 0){
								$resultEmitted = 0;
							}
						}
					}

					$responseInsertHistoric = users::insertHistoric($responseGetUserInSession->objectResult->idUsuario, $vouchersEmitted, $vouchersEmittedInserted, $vouchersReceived, $vouchersReceivedInserted, $responseGetBusiness->idBusiness);

					if($responseInsertHistoric->result == 2){
						if($resultEmitted == 2 && $resultReceived == 2){
							$response->result = 2;
							$response->message = "Todos los comprobantes fueron obtenidos e insertados correctamente.";
						}else if($resultEmitted == 2 && $resultReceived != 2){
							$response->result = 1;
							$response->message = "Algunos comprobantes recibidos no fueron insertados correctamente.";
						}else if($resultEmitted != 2 && $resultReceived == 2){
							$response->result = 1;
							$response->message = "Algunos comprobantes emitidos no fueron insertados correctamente.";
						}else{
							$response->result = 0;
							$response->message = "Ocurrió un error y los comprobantes y emitidos y recibidos no fueron ingresados en su totalidad.";
						}
					}else {
						//error_log("FATAL ERROR : loadDataFirstLogin return responseInsertHistoric ".$responseInsertHistoric." ".time());
						return $responseInsertHistoric;
					}
				}else {
					//error_log("FATAL ERROR : loadDataFirstLogin return responseIsSuperUser ".$responseIsSuperUser." ".time());
					return $responseIsSuperUser;
				}
			}else {
				//error_log("FATAL ERROR : loadDataFirstLogin return responseGetUserInSession ".$responseGetUserInSession." ".time());
				return $responseGetUserInSession;
			}
		}else {
			//error_log("FATAL ERROR : loadDataFirstLogin return responseGetBusiness ".$responseGetBusiness." ".time());
			return $responseGetBusiness;
		}

		//error_log("FATAL ERROR : loadDataFirstLogin Fin por defecto ".time());
		return $response;
	}

	public function signIn($rut, $userEmail, $userPassword) {
		$response = new \stdClass();
		$usersClass = new users();
		$othersClass = new others();
		$validateClass = new validate();
		$userController = new ctr_users();
		$restController = new ctr_rest();
	
		error_log("Attempting signin for user: $userEmail with RUT: $rut");
	
		$othersClass->loadIndicadoresFacturacion();
	
		// Validate RUT
		$responseValidRut = $validateClass->validateRut($rut);
		if ($responseValidRut->result != 2) {
			error_log("Invalid RUT: " . json_encode($responseValidRut));
			return $responseValidRut;
		}
	
		// Validate Email
		$responseValidEmail = $validateClass->validateEmail($userEmail);
		if ($responseValidEmail->result != 2) {
			error_log("Invalid email: " . json_encode($responseValidEmail));
			return $responseValidEmail;
		}
	
		// Sign in through REST API
		$responseSendRestSignIn = $restController->signIn($rut, $userEmail, $userPassword);
		if ($responseSendRestSignIn->result != 2) {
			error_log("REST API signin failed: " . json_encode($responseSendRestSignIn));
			return $responseSendRestSignIn;
		}
	
		// Check user status
		$responseHasPermission = $restController->status($rut, $responseSendRestSignIn->token);
		if ($responseHasPermission->result != 2) {
			error_log("User doesn't have permission: " . json_encode($responseHasPermission));
			return $responseHasPermission;
		}
	
		// Get business information
		$responseGetBusiness = $userController->getBusiness($rut, $userEmail, $responseSendRestSignIn->token);
		if ($responseGetBusiness->result != 2) {
			error_log("Failed to get business info: " . json_encode($responseGetBusiness));
			return $responseGetBusiness;
		}
	
		$usersClass->setDefaultPermission($responseGetBusiness->business->idEmpresa);
	
		// Get or create user
		$responseGetUserInserted = $usersClass->getUserInsertedWithRutEmail($rut, $userEmail);
		if ($responseGetUserInserted->result == 2) {
			// Update existing user
			$responseUpdateUser = $usersClass->updateSession($responseGetUserInserted->objectResult->idUsuario, $responseSendRestSignIn->token);
			if ($responseUpdateUser->result != 2) {
				error_log("Failed to update user session: " . json_encode($responseUpdateUser));
				return $responseUpdateUser;
			}
			$usersClass->verifyUserConfigurations($responseGetUserInserted->objectResult->idUsuario);
		} elseif ($responseGetUserInserted->result == 1) {
			// Insert new user
			$responseInsertNewUser = $usersClass->insertUser($responseGetBusiness->business->idEmpresa, $userEmail, $responseSendRestSignIn->token);
			if ($responseInsertNewUser->result != 2) {
				error_log("Failed to insert new user: " . json_encode($responseInsertNewUser));
				return $responseInsertNewUser;
			}
			if ($responseGetBusiness->newBusiness == 0) {
				$usersClass->cloneUserConfiguration($responseGetBusiness->business->idEmpresa, $responseInsertNewUser->id);
			} else {
				$usersClass->verifyUserConfigurations($responseInsertNewUser->id);
			}
		} else {
			error_log("Failed to get or insert user: " . json_encode($responseGetUserInserted));
			return $responseGetUserInserted;
		}
	
		error_log("Sign-in successful for user: $userEmail with RUT: $rut");
		$response->result = 2;
		return $response;
	}


	//NO SE LLAMA A ESTA FUNCION PORQUE NO SE HACE EL INICIO DE SESION DESDE ACA
// 	public function signInUserFromIntranet($idUser){
// /** VL
//  * Este metodo no inserta usuarios,
//  * porque cuando se asocia un usuario de sigecom a un usuario de linsu,
//  * el usuario de sigecom ya tiene que estar creado previamente
//  */
// 		$othersClass = new others();
// 		$usersClass = new users();
// 		$response = new \stdClass();
// 		$restController = new ctr_rest();
// 		$userController = new ctr_users();

// 		$othersClass->loadIndicadoresFacturacion();

// 		//obtener rut y token almacenados para el id de usuario que se pasa por parametro
// 		$user = $usersClass->getUserById($idUser);
// 		if ( $user->result == 2 ){
// 			$rut = $user->objectResult->rut;
// 			$token = $user->objectResult->tokenRest;
// 			$userEmail = $user->objectResult->correo;
// 			$responseHasPermission = $restController->status($rut, $token);
// 			if($responseHasPermission->result == 2){
// 				$responseGetBusiness = $userController->getBusiness($rut, $userEmail, $token);
// 				if($responseGetBusiness->result == 2){
// 					$usersClass->setDefaultPermission($responseGetBusiness->business->idEmpresa);
// 					$responseUpdateUser = $usersClass->updateSession($idUser, $token);
// 					if($responseUpdateUser->result == 2){
// 						$usersClass->verifyUserConfigurations($idUser);
// 						$response->result = 2;
// 					}else return $responseUpdateUser;
// 				}else return $responseGetBusiness;
// 			}else return $responseHasPermission;
// 		}else return $user;
// 		return $response;
// 	}

	//obtiene toda la informacion de la empresa por el rut, se hace a traves del token que retorna ormen
	public function getBusiness($rut, $userEmail, $userToken){
		$response = new \stdClass();
		$usersClass = new users();
		$restControllerInstance = new ctr_rest();

		$responseGetBusinessInserted = $usersClass->getBusiness($rut);
		if($responseGetBusinessInserted->result == 2){
			$response->result = 2;
			$response->business = $responseGetBusinessInserted->objectResult;
			$response->newBusiness = 0;
		}else if($responseGetBusinessInserted->result == 1){
			$responseGetSuperUser = $usersClass->itsSuperUser($userEmail);
			if($responseGetSuperUser->result == 2){
				$responseSendRestGetBusiness = $restControllerInstance->consultarRut($rut, $rut, $userToken);
				if($responseSendRestGetBusiness->result == 2){
					$business = $responseSendRestGetBusiness->empresa;
					$responseSendQuery = $usersClass->insertBusiness($rut, $business->razonSocial, $business->tipoEntidad, $business->fechaInicio, $business->idCalle, $business->direccion, $business->departamento, $business->localidad, $business->codigoPostal);
					if($responseSendQuery->result == 2){
						$responseGetNewBusiness = $usersClass->getBusiness($rut);
						if($responseGetNewBusiness->result == 2){
							$response->result = 2;
							$response->business = $responseGetNewBusiness->objectResult;
							$response->newBusiness = 1;
						}else{
							$response->result = 0;
							$response->message = "Ocurrió un error al obtener la nueva empresa ingresada.";
						}
					}else return $responseSendQuery;
				}else return $responseSendRestGetBusiness;
			}else return $responseGetSuperUser;
		}else return $responseGetBusinessInserted;

		return $response;
	}

	/* MV: CONTROL DE SESIÓN: Se obtiene el usuario en sesion y se valida contra el token en base de datos, ademas se compara con la fecha en que se genero y si la diferencia es x se genera nuevamente
	*	VL: tambièn se valida el permiso de acceso a una secciòn en particular, por ejemplo si se intenta acceder a la secciòn ventas la variable $section tendrà "VENTAS"
	*	y se valida en la tabla permisos_empresa si esa empresa tiene acceso a esa secciòn
	*/
	// public function validateCurrentSession($section){
	// 	$response = new \stdClass();

	// 	if(isset($_SESSION['systemSession'])){
	// 		$currentSession = $_SESSION['systemSession'];
	// 		$responseGetUser = users::getUserById($currentSession->idUser);
	// 		if($responseGetUser->result == 2){
	// 			//En $responseGetUser tenemos los datos de el usuario que inicio sesion y los datos de la empresa para la que inicio sesion
	// 			if(strcmp($currentSession->token, $responseGetUser->objectResult->token) == 0){
	// 				if(!is_null($section)){ //ej VENTAS
	// 					$responseGetAccess = users::getPermission($section, $currentSession->idBusiness);
	// 					if($responseGetAccess->result == 2){
	// 						if(strcmp($responseGetAccess->objectResult->permiso, "NO") == 0){
	// 							$response->result = 1;
	// 							$response->message = "Su empresa no tiene permisos para acceder a esta sección.";
	// 							return $response;
	// 						}
	// 					}else{
	// 						$response->result = 0;
	// 						$response->message = "Los permisos de acceso de su empresa no fueron asignados, contacte a su administrador.";
	// 						return $response;
	// 					}
	// 				}
	// 				$nextChange = handleDateTime::getNextTimeInt($currentSession->tokenGenerate);
	// 				if(handleDateTime::isTimeToChangeToken($nextChange) == 2){
	// 					$responseUpdateToken = users::setNewTokenAndSession($responseGetUser->objectResult->idUsuario);
	// 					if($responseUpdateToken->result == 2){
	// 						$response->result = 2;
	// 						$response->currentSession = $currentSession;
	// 					}else{
	// 						$response->result = 0;
	// 						$response->message = "Su sesión caducó, por favor vuelva a ingresar.";
	// 					}
	// 				}else{
	// 					$response->result = 2;
	// 					$response->currentSession = $currentSession;
	// 				}
	// 			}else{
	// 				$response->result = 0;
	// 				$response->message = "La sesión del usuario caducó, por favor vuelva a ingresar.";
	// 			}
	// 		}else{
	// 			$response->result = 0;
	// 			$response->message = "La sesión detectada no es válida, por favor vuelva a ingresar.";
	// 		}
	// 	}else{
	// 		$response->result = 0;
	// 		$response->message = "Actulamente no hay una sesión activa en el sistema.";
	// 	}

	// 	if($response->result == 2){
	// 		$responseGetSuperUser = users::itsSuperUser($currentSession->userName);
	// 		if($responseGetSuperUser->result == 2) $currentSession->superUser = "SI";
	// 		else $currentSession->superUser  = "NO";

	// 		$response->currentSession = $currentSession;
	// 	}

	// 	return $response;
	// }

	public function validateCurrentSession(){
		$response = new \stdClass();
		$response->result = 0; // Default to failure

		if (!isset($_SESSION['systemSession'])) {
			$response->message = "Actualmente no hay una sesión activa en el sistema.";
			return $response;
		}

		$currentSession = $_SESSION['systemSession'];
		$userClass = new Users();
		$responseGetUser = $userClass->getUserById($currentSession->idUser);

		if ($responseGetUser->result !== 2) {
			$response->message = "La sesión detectada no es válida, por favor vuelva a ingresar.";
			return $response;
		}

		if (!hash_equals($currentSession->token, $responseGetUser->objectResult->token)) {
			$response->message = "La sesión del usuario caducó, por favor vuelva a ingresar.";
			return $response;
		}

		$userClass = new Users();
		$handleDateTimeClass = new handleDateTime();

		// if(!is_null($section)){ //ej VENTAS
		// 	$responseGetAccess = users::getPermission($section, $currentSession->idBusiness);
		// 	if($responseGetAccess->result == 2){
		// 		if(strcmp($responseGetAccess->objectResult->permiso, "NO") == 0){
		// 			$response->result = 1;
		// 			$response->message = "Su empresa no tiene permisos para acceder a esta sección.";
		// 			return $response;
		// 		}
		// 	}else{
		// 		$response->result = 0;
		// 		$response->message = "Los permisos de acceso de su empresa no fueron asignados, contacte a su administrador.";
		// 		return $response;
		// 	}
		// }

		$nextChange = $handleDateTimeClass->getNextTimeInt($currentSession->tokenGenerate);
		if($handleDateTimeClass->isTimeToChangeToken($nextChange) == 2){
			$responseUpdateToken = $userClass->setNewTokenAndSession($responseGetUser->objectResult->idUsuario);
			if($responseUpdateToken->result == 2){
				$response->result = 2;
				$response->currentSession = $currentSession;
			}else{
				$response->result = 0;
				$response->message = "Su sesión caducó, por favor vuelva a ingresar.";
			}
		}else{
			$response->result = 2;
			$response->currentSession = $currentSession;
		}

		if($response->result == 2){
			$responseGetSuperUser = $userClass->itsSuperUser($currentSession->userName);
			if($responseGetSuperUser->result == 2) 
				$currentSession->superUser = "SI";
			else 
				$currentSession->superUser  = "NO";
		}

		$response->result = 2; // Success
		$response->currentSession = $currentSession;
		return $response;
	}

	function validatePermissions($permiso, $idEmpresa){
		$userClass = new Users();
		$response = new \stdClass();
		// if (!isset($_SESSION['systemSession'])) {
		// 	$response->message = "Actualmente no hay una sesión activa en el sistema.";
		// 	return $response;
		// }
		// $currentSession = $_SESSION['systemSession'];
		$responseGetAccess = $userClass->getPermission($permiso, $idEmpresa);
		if($responseGetAccess->result == 2){
			if(strcmp($responseGetAccess->objectResult->permiso, "NO") == 0){
				$response->result = 1;
				$response->message = "Su empresa no tiene permisos para acceder a esta sección.";
				return $response;
			} else {
				$response->result = 2;
				return $response;
			}
		}else{
			$response->result = 0;
			$response->message = "Los permisos de acceso de su empresa no fueron asignados, contacte a su administrador.";
			return $response;
		}
	} 

	public function getDataSession($index){

		$response = new \stdClass();

		if( isset($_SESSION['systemSession']) ){ //verifico si un usuario tiene una sesion iniciada
			if ( isset($_SESSION[$index]) ){
				$response->data = $_SESSION[$index];
				$response->result = 2;
			}else{
				$response->data = [];
				$response->result = 1;
			}
		}
		else{
			$response->data = [];
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}
		return $response;
	}

	public function updateProductsDataSession($index1, $index2, $newData){

		$response = new \stdClass();

		if( isset($_SESSION['systemSession']) ){
			$_SESSION["arrayProductsSales"][$index1][$index2] = $newData;
			//var_dump($_SESSION["arrayProductsSales"][$index1]);
			$response->result = 2;
		}
		else{
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}

		return $response;
	}

	//UPDATED
	public function setPermissionsBusiness( $idPermission, $currentSession ){
		$response = new \stdClass();
		$userClass = new Users();

		// if(isset($_SESSION['systemSession'])){
		$idBusiness = $currentSession->idEmpresa;
		$idUser = $currentSession->idUser;
		$token = $currentSession->tokenRest;

		$userClass->setPermissionsBusiness($idBusiness, $idPermission);
		//$response = users::updateSession($idUser, $token);
		$response = $userClass->setNewTokenAndSession($idUser);
		// }
// 
		return $response;
	}
}