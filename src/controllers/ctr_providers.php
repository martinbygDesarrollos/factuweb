<?php
require_once 'ctr_users.php';
require_once 'ctr_vouchers.php';
require_once 'ctr_vouchers_received.php';
require_once 'ctr_vouchers_emitted.php';

require_once '../src/class/providers.php';

class ctr_providers{
	//UPDATED
	public function insertProviderFirstLogin($document, $nameBusiness, $address, $phoneNumber, $email, $idEmpresa){
		$providerClass = new providers();
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		return $providerClass->insertProvider($document, $nameBusiness, $address, $phoneNumber, $email, $idEmpresa);
		// }else return $responseMyBusiness;
	}

	//calcula el saldo de un provedor analizando todos los cfe que tiene en el sistema.
	public function getBalanceProvider($documentProvider){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseSendQuery = providers::getProvider($documentProvider);
			if($responseSendQuery->result == 2){
				$resultBalanceToDate = ctr_vouchers_received::getBalanceToDateReceived($documentProvider, $responseMyBusiness->idBusiness);
				if(!is_null($resultBalanceToDate->balanceUYU) || !is_null($resultBalanceToDate->balanceUSD)){
					$response->result = 2;
					$response->balanceUYU = number_format($resultBalanceToDate->balanceUYU,2,",",".");
					$response->balanceUSD = number_format($resultBalanceToDate->balanceUSD,2,",",".");
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y no se obtuvo el resultado de los saldos actuales para este cliente";
				}
			}else return $responseSendQuery;
		}else return $responseMyBusiness;

		return $response;
	}
	//UPDATED
	public function findProviderWithDoc($document, $idEmpresa){
		$response = new \stdClass();
		$providerClass = new providers();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
			$responseSendQuery = $providerClass->getProvider($document, $idEmpresa);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->provider = $responseSendQuery->objectResult;
			}else return $responseSendQuery;
		// }else return $responseMyBusiness;

		return $response;
	}

	//obtiene sugerencias de proveedores segun la parte de texto ingresada
	//UPDATED
	public function getProvidersForModal($suggestionProvider, $idEmpresa){
		$response = new \stdClass();
		$providerClass = new providers();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$responseSendQuery = $providerClass->getSuggestionProviders($suggestionProvider, $idEmpresa);
		if($responseSendQuery->result == 2){
			$response->result = 2;
			$response->listPeople = $responseSendQuery->listResult;
		}else return $responseSendQuery;
		// }else return $responseMyBusiness;

		return $response;
	}
	//UPDATED
	public function modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email){
		$response = new \stdClass();
		$providerClass = new providers();

		$resultGetProvider = $providerClass->getProviderWithId($idProvider);
		if($resultGetProvider->result == 2){
			$responseSendQuery = $providerClass->modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->message = "El proveedor fue modificado correctamente.";
			}else return $responseSendQuery;
		}else return $resultGetProviders;

		return $response;
	}
	//UPDATED
	public function getProvider($idProvider, $idEmpresa){
		$providerClass = new providers();
		$response = new \stdClass();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$responseSendQuery = $providerClass->getProviderWithId($idProvider, $idEmpresa);
		if($responseSendQuery->result == 2){
			$response->result = 2;
			$response->provider = $responseSendQuery->objectResult;
		}else return $responseSendQuery;
		// }else $responseMyBusiness;

		return $response;
	}

	//UPDATED
	public function getProviders($lastId, $textToSearch, $withBalance, $currentSession){
		$response = new \stdClass();
		$providerClass = new providers();
		$vouchReceivedController = new ctr_vouchers_received();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$resultGetProviders = $providerClass->getProviders($lastId, $textToSearch, $withBalance, $currentSession->idEmpresa);
		if($resultGetProviders->result == 2){
			$listResult = array();
			foreach ($resultGetProviders->listResult as $key => $value) {
				$resultGetBalance = $vouchReceivedController->getBalanceToDateReceived($value['idProveedor'], $currentSession->idEmpresa);
				$resultGetBalance->balanceUYU = number_format($resultGetBalance->balanceUYU,2,",",".");
				$resultGetBalance->balanceUSD = number_format($resultGetBalance->balanceUSD,2,",",".");
				$value['balance'] = $resultGetBalance;
				$listResult[] = $value;
			}
			$response->result = 2;
			$response->listResult = $listResult;
			$response->lastId = $resultGetProviders->lastId;
		}else return $resultGetProviders;
		// }else $responseMyBusiness;

		return $response;
	}


	//UPDATED
	public function exportExcelDeudores( $date, $currentSession ){
		//misma consulta que la que trae todos los proveedores con saldo pero ésta no tiene limite por paginación y no manda lastid ni texto
		$providersClass = new providers();
		$vouchersReceivedClass = new vouchersReceived();
		$response = new \stdClass();
		// $responseMyBusiness = ctr_users::getBusinesSession(); //busco la empresa en sesión
		// if($responseMyBusiness->result == 2){
		$resultGetProviders = $providersClass->getProvidersToExport($currentSession->idEmpresa, $date); //buscar el listado de proveedores con saldo pendientes
		if($resultGetProviders->result == 2){
			$listResult = array();
			foreach ($resultGetProviders->listResult as $key => $value) {
				$saldoPesos = $vouchersReceivedClass->saldoPendienteProveedor($value['idProveedor'], "20000101", $date, "UYU", $currentSession->idEmpresa);
				$saldoDolares = $vouchersReceivedClass->saldoPendienteProveedor($value['idProveedor'], "20000101", $date, "USD", $currentSession->idEmpresa);
				if (!isset($value['balance'])) {
					$value['balance'] = new \stdClass();
				}
				
				if( gettype($saldoPesos) === "double" )
					$value['balance']->balanceUYU = $saldoPesos;
				else
					$value['balance']->balanceUYU = 0;

				if( gettype($saldoDolares) === "double" )
					$value['balance']->balanceUSD = $saldoDolares;
				else
					$value['balance']->balanceUSD = 0;

				$listResult[] = $value;
			}
			$response->result = 2;
			$response->listResult = $listResult;
		}else return $resultGetProviders;
		// }else $responseMyBusiness;
		return $response;
	}
}