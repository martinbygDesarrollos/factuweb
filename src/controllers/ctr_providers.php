<?php
require_once 'ctr_users.php';
require_once 'ctr_vouchers.php';
require_once 'ctr_vouchers_received.php';
require_once 'ctr_vouchers_emitted.php';

require_once '../src/class/providers.php';

class ctr_providers{

	public function insertProviderFirstLogin($document, $nameBusiness, $address, $phoneNumber, $email){
		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			return providers::insertProvider($document, $nameBusiness, $address, $phoneNumber, $email, $responseMyBusiness->idBusiness);
		}else return $responseMyBusiness;
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

	public function findProviderWithDoc($document){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseSendQuery = providers::getProvider($document, $responseMyBusiness->idBusiness);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->provider = $responseSendQuery->objectResult;
			}else return $responseSendQuery;
		}else return $responseMyBusiness;

		return $response;
	}

	//obtiene sugerencias de proveedores segun la parte de texto ingresada
	public function getProvidersForModal($suggestionProvider){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseSendQuery = providers::getSuggestionProviders($suggestionProvider, $responseMyBusiness->idBusiness);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->listPeople = $responseSendQuery->listResult;
			}else return $responseSendQuery;
		}else return $responseMyBusiness;

		return $response;
	}

	public function modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email){
		$response = new \stdClass();

		$resultGetProvider = providers::getProviderWithId($idProvider);
		if($resultGetProvider->result == 2){
			$responseSendQuery = providers::modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->message = "El proveedor fue modificado correctamente.";
			}else return $responseSendQuery;
		}else return $resultGetProviders;

		return $response;
	}

	public function getProvider($idProvider){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseSendQuery = providers::getProviderWithId($idProvider, $responseMyBusiness->idBusiness);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->provider = $responseSendQuery->objectResult;
			}else return $responseSendQuery;
		}else $responseMyBusiness;

		return $response;
	}


	public function getProviders($lastId, $textToSearch, $withBalance){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$resultGetProviders = providers::getProviders($lastId, $textToSearch, $withBalance, $responseMyBusiness->idBusiness);
			if($resultGetProviders->result == 2){
				$listResult = array();
				foreach ($resultGetProviders->listResult as $key => $value) {
					$resultGetBalance = ctr_vouchers_received::getBalanceToDateReceived($value['idProveedor'], $responseMyBusiness->idBusiness);
					$resultGetBalance->balanceUYU = number_format($resultGetBalance->balanceUYU,2,",",".");
					$resultGetBalance->balanceUSD = number_format($resultGetBalance->balanceUSD,2,",",".");
					$value['balance'] = $resultGetBalance;
					$listResult[] = $value;
				}
				$response->result = 2;
				$response->listResult = $listResult;
				$response->lastId = $resultGetProviders->lastId;
			}else return $resultGetProviders;
		}else $responseMyBusiness;

		return $response;
	}



	public function exportExcelDeudores( $date ){

		//misma consulta que la que trae todos los proveedores con saldo pero ésta no tiene limite por paginación y no manda lastid ni texto
		$providersClass = new providers();
		$vouchersReceivedClass = new vouchersReceived();
		$response = new \stdClass();
		$responseMyBusiness = ctr_users::getBusinesSession(); //busco la empresa en sesión
		if($responseMyBusiness->result == 2){

			$resultGetProviders = $providersClass->getProvidersToExport($responseMyBusiness->idBusiness, $date); //buscar el listado de proveedores con saldo pendientes
			if($resultGetProviders->result == 2){
				$listResult = array();
				foreach ($resultGetProviders->listResult as $key => $value) {

					$saldoPesos = $vouchersReceivedClass->saldoPendienteProveedor($value['idProveedor'], "20000101", $date, "UYU", $responseMyBusiness->idBusiness);
					$saldoDolares = $vouchersReceivedClass->saldoPendienteProveedor($value['idProveedor'], "20000101", $date, "USD", $responseMyBusiness->idBusiness);

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

		}else $responseMyBusiness;

		return $response;

	}
}