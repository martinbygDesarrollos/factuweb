<?php

require_once 'ctr_users.php';
require_once 'ctr_clients.php';
require_once 'ctr_providers.php';
require_once 'rest/ctr_rest.php';
require_once 'ctr_vouchers.php';

require_once '../src/class/vouchers_received.php';

require_once '../src/utils/handle_date_time.php';

set_time_limit ( 60 );

class ctr_vouchers_received{
	//UPDATED
	public function getMinAndMaxDateVoucher($idEmpresa){
		$vouchersReceivedClass = new vouchersReceived();
		return $vouchersReceivedClass->getMinAndMaxDateVoucher($idEmpresa);
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		// }else return $responseMyBusiness;
	}
	//UPDATED
	public function getTypeExistingVouchers($idEmpresa){
		$vouchersReceivedClass = new vouchersReceived();
		return $vouchersReceivedClass->getTypeExistingVouchers($idEmpresa);
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		// }else return $responseMyBusiness;
	}
	//UPDATED
	public function getVouchersReceived($dateReceived, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentProvider, $currentSession){
		$vouchersReceivedClass = new vouchersReceived();
		$handleDateTimeClass = new handleDateTime();
		$utilsClass = new utils();
		$provController = new ctr_providers();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		if($dateVoucher != 0)
			$dateVoucher = $handleDateTimeClass->getDateInt($dateVoucher);
		$responseGetVouchers = $vouchersReceivedClass->getVouchersReceived($dateReceived, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentProvider, $currentSession->idEmpresa);
		if($responseGetVouchers->result == 2){
			$newList = array();
			foreach ($responseGetVouchers->listResult as $key => $value) {
				if(is_null($value['idProveedor'])){
					$value['rut'] = "No encontrado";
				}
				else{
					$responseGetProvider = $provController->getProvider($value['idProveedor'], $currentSession->idEmpresa);
					if($responseGetProvider->result == 2){
						if(!empty($responseGetProvider->provider->rut)){
							$value['razonSocial'] = $responseGetProvider->provider->razonSocial;
							$value['rut'] = $utilsClass->formatDocuments($responseGetProvider->provider->rut, $currentSession);
						}
						else
							$value['rut'] = "No encontrado";
					}
					$newList[] = $value;
				}
				$responseGetVouchers->listResult = $newList;
			}
		}
		return $responseGetVouchers;
		// }else return $responseMyBusiness;
	}
	//UPDATED
	public function createManualReceiptReceived($dateMaked, $total, $currentSession){
		$response = new \stdClass();
		$userController = new ctr_users();
		$provController = new ctr_providers();
		$handleDateTimeClass = new handleDateTime();
		$vouchersReceivedClass = new vouchersReceived();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$responseGetAccountState = $userController->getLastAccountStateInfo("PROVIDER");
		if($responseGetAccountState->result == 2){
			$resultGetProvider = $provController->findProviderWithDoc($responseGetAccountState->information->document, $currentSession->idEmpresa);
			if($resultGetProvider->result == 2){
				$dateMakedINT = $handleDateTimeClass->getDateInt($dateMaked);
				$responseSendQuery = $vouchersReceivedClass->createManualReceiptReceived($resultGetProvider->provider->idProveedor, $dateMakedINT, $total, $responseGetAccountState->information->selectedCoin, $currentSession->idEmpresa);
				if($responseSendQuery->result == 2){
					$response->result = 2;
					$response->message = "Se creó un nuevo recibo manual.";
				}else return $responseSendQuery;
			}else return $resultGetProvider;
		}else{
			$response->result = 2;
			$response->message = "El estado de cuenta generado no fue almacenado correctamente por lo que no podrá hacer este recibo manual.";
		}
		// }else return $responseMyBusiness;

		return $response;
	}

	public function modifyManualReceiptReceived($indexVoucher, $dateMaked, $total){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$resultGetManualReceipt = vouchersReceived::getManualReceiptReceived($indexVoucher, $responseMyBusiness->idBusiness);
			if($resultGetManualReceipt->result == 2){
				$dateMakedINT = handleDateTime::getDateInt($dateMaked);
				$responseSendQuery = vouchersReceived::modifyManualReceiptReceived($indexVoucher, $dateMakedINT, $total, $responseMyBusiness->idBusiness);
				if($responseSendQuery->result == 2){
					$resultGetProvider = ctr_providers::getProvider($resultGetManualReceipt->objectResult->idProveedor);
					if($resultGetProvider->result == 2)
						ctr_vouchers::insertReceiptHistory($resultGetProvider->provider->rut, 0, "Modificar", $total, $dateMakedINT, $resultGetManualReceipt->objectResult->moneda);
					$response->result = 2;
					$response->message = "El recibo fue modificado correctamente.";
					$resultModifyManualReceipt = vouchersReceived::getManualReceiptReceived($indexVoucher, $responseMyBusiness->idBusiness);
					if($resultModifyManualReceipt->result == 2)
						$response->manualReceipt = $resultModifyManualReceipt->objectResult;
				}else return $responseSendQuery;
			}else return $resultGetManualReceipt;
		}else return $responseMyBusiness;

		return $response;
	}

	public function deleteManualReceiptReceived($indexVoucher){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$resultGetVoucher = vouchersReceived::getManualReceiptReceived($indexVoucher, $responseMyBusiness->idBusiness);
			if($resultGetVoucher->result == 2){
				$responseSendQuery = vouchersReceived::deleteManualReceiptReceived($indexVoucher, $responseMyBusiness->idBusiness);
				if($responseSendQuery->result == 2){
					$resultGetProvider = ctr_providers::getProvider($resultGetVoucher->objectResult->idProveedor);
					if($resultGetProvider->result == 2){
						$totalFormat = str_replace('.','',$resultGetVoucher->objectResult->total);
						$totalFormat = str_replace(',','.', $totalFormat);
						ctr_vouchers::insertReceiptHistory($resultGetProvider->provider->rut, 0, "Borrar", $totalFormat, handleDateTime::getDateInt($resultGetVoucher->objectResult->fecha), $resultGetVoucher->objectResult->moneda);
					}
					$response->result = 2;
					$response->message = "El recibo fue eliminado correctamente del sistema.";
				}else return $responseSendQuery;;
			}else return $resultGetVoucher;
		}else return $responseMyBusiness;
		return $response;
	}
	//UPDATED
	public function getManualReceiptsReceived($lastId, $filterNameReceiver, $idEmpresa){
		$response = new \stdClass();
		$vouchersReceivedClass = new vouchersReceived();

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$resultSendQuery = $vouchersReceivedClass->getManualReceiptsReceived($lastId, $filterNameReceiver, $idEmpresa);
		if($resultSendQuery->result == 2){
			$response->result = 2;
			$response->listResult = $resultSendQuery->listResult;
			$response->lastId = $resultSendQuery->lastId;
		}else return $resultSendQuery;
		// }else $response = $responseMyBusiness;

		return $response;
	}

	public function getBalanceToDateReceived($idProvider, $myBusiness){
		$response = new \stdClass();
		$handleDateTimeClass = new handleDateTime();
		$vouchersReceivedClass = new vouchersReceived();

		$resultGetBalanceUYU = $vouchersReceivedClass->getBalanceToDateReceived($idProvider, "UYU", $handleDateTimeClass->getCurrentDateTimeInt(), $myBusiness);
		$response->balanceUYU = $resultGetBalanceUYU->balance;
		$resultGetBalanceUSD = $vouchersReceivedClass->getBalanceToDateReceived($idProvider, "USD", $handleDateTimeClass->getCurrentDateTimeInt(), $myBusiness);
		$response->balanceUSD = $resultGetBalanceUSD->balance;

		return $response;
	}

	public function getLastVoucherReceived(){
		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2)
			return vouchersReceived::getLastVoucherReceived($responseMyBusiness->idBusiness);
		else return $responseMyBusiness;
	}

	//se cargan los comprobantes recibidos en el primer inicio de sesion
	//UPDATED
	public function getVouchersReceivedFirstLogin($rut, $pageSize, $lastId, $currentSession){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$vouchReceivedController = new ctr_vouchers_received();
		$provController = new ctr_providers();
		$counterRecords = 0;
		$counterInserted = 0;

		$response->arrayErrors = array();
		$responseSendRest = $restController->listarRecibidos($rut, $pageSize, $lastId, null, null, $currentSession->tokenRest);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listRecibidos as $key => $voucher) {
				$counterRecords++;
				if(isset($voucher->emisor->rut)){
					$idProvider = null;
					$responseGetProvider = $provController->findProviderWithDoc($voucher->emisor->rut, $currentSession->idEmpresa);
					if($responseGetProvider->result == 2)
						$idProvider = $responseGetProvider->provider->idProveedor;
					else{
						$responseInsertProvider = $provController->insertProviderFirstLogin($voucher->emisor->rut, $voucher->emisor->razonSocial, $voucher->emisor->direccion, $voucher->emisor->telefono, $voucher->emisor->email, $currentSession->idEmpresa);
						if($responseInsertProvider->result == 2)
							$idProvider = $responseInsertProvider->id;
					}
					if(!is_null($idProvider)){
						if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
						$responseInsertVoucherReceived = $vouchReceivedController->insertVoucherReceived($voucher->id, $idProvider, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $voucher->sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision,$voucher->formaPago, $voucher->vencimiento, $currentSession->idEmpresa);
						if($responseInsertVoucherReceived->result == 2){
							$lastId = $voucher->id;
							$counterInserted++;
						}else $arrayErrors[] = $responseInsertVoucherReceived;
					}
				}
			}
			if(sizeof($responseSendRest->listRecibidos) == 200){
				$responseRecursive = $vouchReceivedController->getVouchersReceivedFirstLogin($rut, $pageSize, $lastId, $currentSession);
				$counterRecords = $counterRecords + $responseRecursive->counterRecords;
				$counterInserted = $counterInserted +  $responseRecursive->counterInserted;
				$arrayErrors = array_merge($arrayErrors, $responseRecursive->arrayErrors);
			}
		}else return $responseSendRest;

		$response->counterRecords = $counterRecords;
		$response->counterInserted = $counterInserted;
		$response->arrayErrors = $arrayErrors;
		return $response;
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//cuando se inicia sesion se llama a esta funcion para actualizar los comprobantes desde el más actual que se tiene en ormen hasta el comp local
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	public function getVouchersReceivedRest($rut, $pageSize, $lastId){
		set_time_limit ( 60 );
		$response = new \stdClass();
		$counterRecords = 0;
		$counterInserted = 0;
		$response->arrayErrors = array();
		$idBusiness = $_SESSION['systemSession']->idBusiness;

		$responseSendRest = ctr_rest::listarRecibidos($rut, $pageSize, $lastId, null, null);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listRecibidos as $key => $voucher) {
				$counterRecords++;
				if(isset($voucher->emisor->rut)){
					$idProvider = null;
					$responseGetProvider = ctr_providers::findProviderWithDoc($voucher->emisor->rut);
					if($responseGetProvider->result == 2)
						$idProvider = $responseGetProvider->provider->idProveedor;
					else{
						$responseInsertProvider = ctr_providers::insertProviderFirstLogin($voucher->emisor->rut, $voucher->emisor->razonSocial, $voucher->emisor->direccion, $voucher->emisor->telefono, $voucher->emisor->email);
						if($responseInsertProvider->result == 2)
							$idProvider = $responseInsertProvider->id;
					}
					if(!is_null($idProvider)){
						if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
						$resultGetVoucher = vouchersReceived::getVoucherReceived($voucher->id, $idBusiness);
						if ($resultGetVoucher->result != 2){
							$responseInsertVoucherReceived = ctr_vouchers_received::insertVoucherReceived($voucher->id, $idProvider, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $voucher->sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision,$voucher->formaPago, $voucher->vencimiento);
							if($responseInsertVoucherReceived->result == 2){
								$lastId = $voucher->id;
								$counterInserted++;
							}else $arrayErrors[] = $responseInsertVoucherReceived;
						}else{
							$response->result = 2;
							$response->message = "Comprobantes recibidos - La base local ya se encuentra actualizada";
							return $response;
						}
					}
				}
			}
			if(sizeof($responseSendRest->listRecibidos) == $pageSize){
				$newLastId = $responseSendRest->listRecibidos[sizeof($responseSendRest->listRecibidos)-1]->id;
				$responseRecursive = ctr_vouchers_received::getVouchersReceivedRest($rut, $pageSize, $newLastId);
				return $responseRecursive;
			}
		}else return $responseSendRest;

		$response->counterRecords = $counterRecords;
		$response->counterInserted = $counterInserted;
		$response->arrayErrors = $arrayErrors;
		return $response;
	}

	public function updateDataVoucherReceived($rut){
		$response = new \stdClass();
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetLastId = ctr_vouchers_received::getLastVoucherReceived();
			if($responseGetLastId->result == 2){
				$responseSendRest = ctr_vouchers_received::updateVouchersReceived($rut, 200, null, $responseGetLastId->objectResult->fechaEmision, handleDateTime::getCurrentDateTimeInt(), $responseGetBusiness->idBusiness);
				if(isset($responseSendRest)){
					if($responseSendRest->counterRecords == $responseSendRest->counterInserted){
						$response->result = 2;
					}else if($responseSendRest->counterRecords < $responseSendRest->counterInserted && $responseSendRest->counterInserted > 0){
						$response->result = 1;
					}else if($responseSendRest->counterInserted == 0){
						$response->result = 0;
					}
					$response->vouchersReceived = $responseSendRest->counterRecords;
					$response->vouchersReceivedInserted = $responseSendRest->counterInserted;
				}else $response->result = 0;
			}else return $responseGetLastId;
		}else return $responseGetBusiness;

		return $response;
	}

	public function updateVouchersReceived($rut, $pageSize, $lastId, $dateFrom, $dateTo, $idBusiness){
		$response = new \stdClass();

		$newLastId = $lastId;
		$counterRecords = 0;
		$counterInserted =  0;
		$response->arrayErrors = array();
		$responseSendRest = ctr_rest::listarRecibidos($rut, $pageSize, $lastId, $dateFrom, $dateTo);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listRecibidos as $key => $voucher) {
				$counterRecords++;
				$idProvider = null;
				$responseGetVoucher = vouchersReceived::getVoucherReceived($voucher->id, $idBusiness);
				if($responseGetVoucher->result == 1){
					if(isset($voucher->emisor->rut)){
						$responseGetProvider = ctr_providers::findProviderWithDoc($voucher->emisor->rut);
						if($responseGetProvider->result == 2){
							$idProvider = $responseGetProvider->provider->idProveedor;
						}else{
							$responseInsertProvider = ctr_providers::insertProviderFirstLogin($voucher->emisor->rut, $voucher->emisor->razonSocial, $voucher->emisor->direccion, $voucher->emisor->telefono, $voucher->emisor->email);
							if($responseInsertProvider->result == 2)
								$idProvider = $responseInsertProvider->id;
						}
					}
					if(!is_null($idProvider)){
						if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
						$responseInsertVoucherReceived = vouchersReceived::insertVoucherReceived($voucher->id, $idProvider, $responseMyBusiness->idBusiness, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $voucher->sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $voucher->vencimiento);
						if($responseInsertVoucherReceived->result == 2){
							$lastId = $voucher->id;
							$counterInserted++;
						}else $arrayError[] = $responseInsertVoucherEmitted;
					}
				}else if($responseGetVoucher->result == 2) $counterInserted++;
			}
			if(sizeof($responseSendRest->listRecibidos) == 200 && $lastId != $newLastId){
				$responseRecursive = ctr_vouchers_received::updateVouchersReceived($rut, $pageSize, $newLastId, $dateFrom, $dateTo);
				$counterRecords = $counterRecords + $responseRecursive->counterRecords;
				$counterInserted = $counterInserted +  $responseRecursive->counterInserted;
				$arrayErrors = array_merge($arrayErrors, $responseRecursive->arrayErrors);
			}
		}else return $responseSendRest;

		$response->counterRecords = $counterRecords;
		$response->counterInserted = $counterInserted;
		$response->arrayErrors = $arrayErrors;
		return $response;
	}
	//UPDATED
	public function insertVoucherReceived($id, $idProvider, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision, $formaPago, $vencimiento, $idEmpresa){
		$vouchersReceivedClass = new vouchersReceived();
		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		return $vouchersReceivedClass->insertVoucherReceived($id, $idProvider, $idEmpresa, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision,$formaPago, $vencimiento);
		// }else return $responseMyBusiness;
	}
	//UPDATED
	public function getProviderAccountSate($idProvider, $dateInit, $dateEnding, $typeCoin, $currentSession){
		$response = new \stdClass();
		$userController = new ctr_users();
		$handleDateTimeClass = new handleDateTime();
		$voucherController = new ctr_vouchers();
		$vouchersReceivedClass = new vouchersReceived();
		$provController = new ctr_providers();


		$dateInitINT = $handleDateTimeClass->getDateInt($dateInit);
		$dateEndingINT = $handleDateTimeClass->getDateInt($dateEnding);

		// $responseMyBusiness = ctr_users::getBusinesSession();
		// if($responseMyBusiness->result == 2){
		$resultGetProvider = $provController->getProvider($idProvider, $currentSession->idEmpresa);
		if($resultGetProvider->result == 2){
			$variableShowBalance = $userController->getVariableConfiguration("VER_SALDOS_ESTADO_CUENTA", $currentSession);
			if($variableShowBalance->result == 2){
				$responseAccountState = $vouchersReceivedClass->getAccountState($idProvider, $dateInitINT, $dateEndingINT, $typeCoin, $currentSession->idEmpresa);
				if($responseAccountState->result != 0){
					// $resultGetBusiness = ctr_users::getBusinessInformation($responseMyBusiness->idBusiness);
					// if($resultGetBusiness->result == 2){
					$resultGenerateFile = $voucherController->exportAccountState($resultGetProvider->provider, $dateInitINT, $dateEndingINT, $responseAccountState->listResult, "PROVIDER", $currentSession);
					if($variableShowBalance->configValue == "NO"){
						unset($responseAccountState->listResult["BALANCEUSD"]);
						unset($responseAccountState->listResult["BALANCEUYU"]);
					}
					$response->result = 2;
					$response->accountState = $responseAccountState->listResult;
					$response->name = $resultGetProvider->provider->razonSocial;
					$response->documentSelected = $resultGetProvider->provider->rut;
					if($resultGenerateFile->result == 2){
						$response->resultFile = 2;
						$response->fileGenerate = $resultGenerateFile->fileGenerate;
					}else{
						$response->resultFile = 0;
						$response->messageFile = "Ocurrió un error y el archivo pdf no pudo generarse correctamente.";
					}
					$voucherController->saveInfoAccountStateTemp($resultGetProvider->provider->idProveedor, $resultGetProvider->provider->rut, $handleDateTimeClass->setFormatHTMLDate($dateInitINT), $handleDateTimeClass->setFormatHTMLDate($dateEndingINT), $typeCoin, null, "PROVIDER");
					// }else return $resultGetBusiness;
				}else return $responseAccountState;
			}else return $variableShowBalance;
		}else return $resultGetProvider;
		// }else return $responseMyBusiness;

		return $response;
	}
}