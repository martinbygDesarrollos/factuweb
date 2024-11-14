<?php

require_once 'ctr_users.php';
require_once 'ctr_clients.php';
require_once 'ctr_providers.php';
require_once 'rest/ctr_rest.php';
require_once 'ctr_vouchers.php';

require_once '../src/class/vouchers_emitted.php';

require_once '../src/utils/handle_date_time.php';

class ctr_vouchers_emitted{

	//aca se cancelan los comprobantes emitidos dependiendo de que tipo de comprobante es seleccionara el tipo de cfe
	//UPDATED
	function cancelVoucherEmitted($idVoucher, $dateCancelVoucher, $appendix, $currentSession){
		$response = new \stdClass();
		$userController = new ctr_users();
		$restController = new ctr_rest();
		$clientController = new ctr_clients();
		$vouchersEmittedClass = new vouchersEmitted();
		$utilsClass = new utils();
		$voucherController = new ctr_vouchers();
		$voucherEmittedController = new ctr_vouchers_emitted();




		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
			$responseGetVoucher = $vouchersEmittedClass->getVoucherEmitted($idVoucher, $currentSession->idEmpresa);
			if($responseGetVoucher->result == 2){
				$responseGetType = $utilsClass->getTypeToCancelVoucher($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->isCobranza);
				$detailReceiver = null;
				$responseGetClient = null;
				if(!is_null($responseGetVoucher->objectResult->idCliente)){ //asigno un cliente
					$responseGetClient = $clientController->getClientWithId($responseGetVoucher->objectResult->idCliente);
					if($responseGetClient->result == 2){
						$detailReceiver = $restController->prepareReceptorToSend($responseGetClient->client->docReceptor, $responseGetClient->client->nombreReceptor, $responseGetClient->client->direccion, $responseGetClient->client->localidad, $responseGetClient->client->departamento,"Uruguay");
					}else return $responseGetClient;
				}

				// intentar traer el comprobante
				$responseRestGetCFE =  $restController->consultarCFE($currentSession->rut, null, $responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, "application/json", $currentSession->tokenRest);
				if($responseRestGetCFE->result == 2){
					// se obtuvo el comprobante
					$verifyCancelledVoucher =  $restController->updateVoucherAnuladoDgi($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, $responseRestGetCFE->cfe->isAnulado, $currentSession->idEmpresa);
					if ( $verifyCancelledVoucher->anular ){


						$jsonPrintFormat = json_decode($responseRestGetCFE->cfe->representacionImpresa);
						$arrayDetails = array();
						if( $responseGetVoucher->objectResult->isCobranza  == 1){
							if($responseGetVoucher->objectResult->tipoCFE == 101 || $responseGetVoucher->objectResult->tipoCFE == 111){
								$usdValue = $voucherController->getQuote("USD", null);
								$arrayReference = $restController->prepareReferenciasToSend($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, null, null);
								$indFact = 7; //Producto o servicio no facturable negativo
								$responseCreateVoucherCancel2 = null;
								if( $responseGetClient ){
									$responseCreateVoucherCancel2 = $voucherEmittedController->createVoucherReceiptEmitted($responseGetClient->client->docReceptor, $responseGetClient->client->direccion, $responseGetClient->client->localidad, $idVoucher, $dateCancelVoucher, $usdValue->currentQuote, $responseRestGetCFE->cfe->total, $arrayReference, 1, $indFact, $currentSession);
								}else{
									$responseCreateVoucherCancel2 = $voucherEmittedController->createVoucherReceiptEmitted(null, null, null, $idVoucher, $dateCancelVoucher, $usdValue->currentQuote, $responseRestGetCFE->cfe->total, $arrayReference, 1, $indFact, $currentSession);
								}
								if ($responseCreateVoucherCancel2->result == 2){
									$response->result = 2;
									$response->message = "Se emitió correctamente la cancelación del comprobante seleccionado.";
									return $response;
								}else return $responseCreateVoucherCancel2;
							}else{
								foreach ($jsonPrintFormat->detalles as $key => $itemDetail)
									$arrayDetails[] = $restController->prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio, $itemDetail->descRecItemTipo, +$itemDetail->descRecItem);
							}
						}else{
							foreach ($jsonPrintFormat->detalles as $key => $itemDetail)
								$arrayDetails[] = $restController->prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio, $itemDetail->descRecItemTipo, +$itemDetail->descRecItem);
						}


						$arrayReference = $restController->prepareReferenciasToSend($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, null, null);
						$newAppendix = "Anulación " . $utilsClass->getNameVoucher($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->isCobranza) . " " . $responseGetVoucher->objectResult->serieCFE . $responseGetVoucher->objectResult->numeroCFE . ". \n" .  $appendix;

						$branchCompany = null;
						$responseGetGrossAmount = $userController->getVariableConfiguration("SUCURSAL_IS_PRINCIPAL", $currentSession);
						if ($responseGetGrossAmount->result == 2){
							$branchCompany = $responseGetGrossAmount->configValue;
						}

						$responseCreateVoucherCancel = $voucherController->createNewCFE($responseGetType->type, $dateCancelVoucher, $jsonPrintFormat->montosBrutos, $responseGetVoucher->objectResult->formaPago, null, $jsonPrintFormat->tipoMoneda, $arrayDetails, $detailReceiver, 0, array($arrayReference), $newAppendix, $branchCompany, null, $currentSession);
						if($responseCreateVoucherCancel->result == 2){
							$voucherEmittedController->updateDataVoucherEmitted($currentSession);
							$response->result = 2;
							$response->message = "Se emitió correctamente la cancelación del comprobante seleccionado.";
						}else return $responseCreateVoucherCancel;


					} else return $verifyCancelledVoucher;

				}else return $responseRestGetCFE;
			}else return $responseGetVoucher;
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function getMinAndMaxDateVoucher($idEmpresa){
		$vouchersEmittedClass = new vouchersEmitted();

		return $vouchersEmittedClass->getMinAndMaxDateVoucher($idEmpresa);

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// }else return $responseGetBusiness;
	}

	public function getTypeExistingVouchers($idEmpresa){
		$vouchersEmittedClass = new vouchersEmitted();
		
		return $vouchersEmittedClass->getTypeExistingVouchers($idEmpresa);
		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// }else return $responseGetBusiness;
	}
	//UPDATED
	public function createVoucherReceiptEmitted($documentClient, $address, $city, $idsVouchersSelected, $dateVoucher, $usdValue, $total, $reasonReference, $checkedOfficial, $indFact, $currentSession){
		$response = new \stdClass();
		$clientController = new ctr_clients();
		$validateClass = new validate();
		$userController = new ctr_users();
		$voucherEmittedController = new ctr_vouchers_emitted();
		$handleDateTimeClass = new handleDateTime();
		$restController = new ctr_rest();
		$vouchersEmittedClass = new vouchersEmitted();


		// $responseGetBusiness = ctr_users::getBusinesSession();//obtenemos los datos de la empresa mediante el id de la sesiòn
		// if($responseGetBusiness->result == 2){
		// 	$resultGetBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
		// 	if($resultGetBusiness->result == 2){
		$responseGetClient = $clientController->findClientWithDoc($documentClient, $currentSession->idEmpresa); //obtenemos un cliente a partir del documento
		//if($responseGetClient->result == 2){
		$tipoCFE = null;
		if( $documentClient ){
			$resultValidateRUT = $validateClass->validateRut($documentClient);
			if($resultValidateRUT->result == 2)
				$tipoCFE = 111;
			else if($validateClass->validateCI($documentClient))
				$tipoCFE = 101;
			else{
				$response->result = 0;
				$response->message = "El documento del cliente no pudo validarse como rut o cédula.";
			}
		}else{ $tipoCFE = 101; }

		$branchCompany = null;
		$responseGetGrossAmount = $userController->getVariableConfiguration("SUCURSAL_IS_PRINCIPAL", $currentSession);
		if ($responseGetGrossAmount->result == 2){
			$branchCompany = $responseGetGrossAmount->configValue;
		}

		$lastAccountState = $userController->getLastAccountStateInfo('CLIENT'); // NO ENTIENDO
		$typeCoin = null;
		if($lastAccountState->result == 2)
			$typeCoin = $lastAccountState->information->selectedCoin;

		if($checkedOfficial == 0){
			return $voucherEmittedController->createManualReceiptEmitted($documentClient, $dateVoucher, $typeCoin, $total, $currentSession);
		}else{
			if(!is_null($tipoCFE)){
				$dateVoucherINT = $handleDateTimeClass->getDateInt($dateVoucher);
				if ( !$indFact )
					$indFact = 6;
				$detalle = array($restController->prepareDetalleToSend($indFact, "Recibo cobranza", null, null, 1, null, $total));
				if($responseGetClient->result == 2){
					$client = $responseGetClient->client;
					$receptor = $restController->prepareReceptorToSend($client->docReceptor, $client->nombreReceptor, $client->direccion, $client->localidad, $client->departamento, "Uruguay");
				}else $receptor = null;
				$arrayReferencias = array();
				if(strlen($idsVouchersSelected) > 3){
					$arrayIdVouchers = explode(",", $idsVouchersSelected);
					if(sizeof($arrayIdVouchers) >= 1){
						foreach ($arrayIdVouchers as $key => $value) {
							$responseGetVoucher = $vouchersEmittedClass->getVoucherEmitted($value, $currentSession->idEmpresa);
							if($responseGetVoucher->result == 2){
								if(!$typeCoin){
									$typeCoin = $responseGetVoucher->objectResult->moneda;
								}
								$objVoucher = $responseGetVoucher->objectResult;
								$arrayReferencias[] = $restController->prepareReferenciasToSend($objVoucher->tipoCFE, $objVoucher->serieCFE, $objVoucher->numeroCFE, null, null);
							}
						}
					}
				}else $arrayReferencias[] = $restController->prepareReferenciasToSend(null, null, null, 1, $reasonReference);
				$responseSendRest = $restController->nuevoRecibo($currentSession->rut, $tipoCFE, $dateVoucherINT, 1, 1, $typeCoin, $usdValue, $detalle, $arrayReferencias, $receptor, $currentSession->tokenRest, $branchCompany);
				if($responseSendRest->result == 2){
					$response->result = 2;
					$response->message = "Su recibo oficial fue creado correctamente.";
					$voucherEmittedController->updateDataVoucherEmitted($currentSession);
				}else return $responseSendRest;
			}
		}
				//}else {echo "1 // ";return $responseGetClient;}
		// 	}else return $resultGetBusiness;
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function calculateTotalVoucherSelected($idsSelected, $idEmpresa){
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$arrayIds = explode(",", $idsSelected);
		$total = 0;
		$arrayVouchers = array();
		foreach($arrayIds as $key => $voucher){
			$responseGetVoucher = $vouchersEmittedClass->getVoucherEmitted($voucher, $idEmpresa);
			if($responseGetVoucher->result == 2){
				$arrayVouchers[] = $responseGetVoucher->objectResult;
			}else return $responseGetVoucher;
		}
		$response->result = 2;
		$response->total = $vouchersEmittedClass->getBlanaceFromVouchers($arrayVouchers, $idEmpresa);
		// }else return $responseGetBusiness;
		return $response;
	}
	//UPDATED
	public function getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $branchCompany, $currentSession){
		$handleDateTimeClass = new handleDateTime();
		$vouchersEmittedClass = new vouchersEmitted();
		$clientController = new ctr_clients();
		$utilsClass = new utils();
		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		if($dateVoucher != 0)
			$dateVoucher = $handleDateTimeClass->getDateInt($dateVoucher);
		$responseGetVouchers = $vouchersEmittedClass->getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $currentSession->idEmpresa, $branchCompany);
		if($responseGetVouchers->result == 2){
			$newList = array();
			foreach ($responseGetVouchers->listResult as $key => $value) {
				if(is_null($value['idCliente'])){
					$value['documentoCliente'] = "Consumidor Final";
					$value['nombreCliente'] = "Consumidor Final";
				}
				else{
					$responseGetClient = $clientController->getClientWithId($value['idCliente']);
					if($responseGetClient->result == 2){
						if(!empty($responseGetClient->client->docReceptor)){
							$value['documentoCliente'] = $utilsClass->formatDocuments($responseGetClient->client->docReceptor, $currentSession);
							if($responseGetClient->client->nombreReceptor != ""){
								$value['nombreCliente'] = $responseGetClient->client->nombreReceptor;
							}else{
								$value['nombreCliente'] = "Consumidor Final";
							}
						}
						else
							$value['documentoCliente'] = "Consumidor Final";
					}
				}
				$newList[] = $value;
				$responseGetVouchers->listResult = $newList;
			}
		}
		return $responseGetVouchers;
		// }else return $responseGetBusiness;
	}

	public function createManualReceiptEmitted($documentClient, $dateVoucher, $typeCoin, $total, $currentSession){
		$response = new \stdClass();
		$clientController = new ctr_clients();
		$handleDateTimeClass = new handleDateTime();
		$vouchersEmittedClass = new vouchersEmitted();
		$voucherController = new ctr_vouchers();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$resultGetClient = $clientController->findClientWithDoc($documentClient, $currentSession->idEmpresa);
		if($resultGetClient->result == 2){
			$dateVoucherINT = $handleDateTimeClass->getDateInt($dateVoucher);
			$responseSendQuery = $vouchersEmittedClass->createVoucher($resultGetClient->client->id, $dateVoucherINT, $typeCoin, $total, $currentSession->idEmpresa);
			if($responseSendQuery->result == 2){
				$voucherController->insertReceiptHistory($documentClient, 1, "Crear", $total, $dateVoucherINT, $typeCoin, $currentSession);
				$response->result = 2;
				$response->message = "El recibo manual fue creado correctamente.";
			}else return $responseSendQuery;
		}else return $resultGetClient;
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function modifyManualReceiptEmitted($indexVoucher, $total, $dateReceipt, $typeCoin, $currentSession){
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();
		$handleDateTimeClass = new handleDateTime();
		$clientController = new ctr_clients();
		$voucherController = new ctr_vouchers();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$resultGetVoucher = $vouchersEmittedClass->getVoucherWithIndex($indexVoucher);
		if($resultGetVoucher->result == 2){
			$dateInit = $handleDateTimeClass->getDateInt($dateReceipt);
			$resultSendQuery = $vouchersEmittedClass->modifyManualReceipt($indexVoucher, $total, $dateInit, $typeCoin, $currentSession->idEmpresa);
			if($resultSendQuery->result == 2){
				$resultGetClient = $clientController->getClientWithId($resultGetVoucher->objectResult->idCliente);
				if($resultGetVoucher->result == 2)
				$voucherController->insertReceiptHistory($resultGetClient->client->docReceptor, 1, "Modificar", $total, $dateInit, $typeCoin, $currentSession);
				$response->result = 2;
				$response->message = "El recibo manual fue modificado correctamente.";
				$response->newTotal = number_format($total,2, ",",".");
			}else return $resultSendQuery;
		}else return $resultGetVoucher;
		// }else $response = $responseGetBusiness;

		return $response;
	}

	public function deleteManualReceiptEmitted($indexVoucher){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$resultGetVoucher = vouchersEmitted::getVoucherWithIndex($indexVoucher);
			if($resultGetVoucher->result == 2){
				$responseSendQuery = vouchersEmitted::deleteManualReceipts($indexVoucher);
				if($responseSendQuery->result == 2){
					$resultGetClient = ctr_clients::getClientWithId($resultGetVoucher->objectResult->idCliente);
					if($resultGetVoucher->result == 2)
						ctr_vouchers::insertReceiptHistory($resultGetClient->client->docReceptor, 1, "Borrar", $resultGetVoucher->objectResult->total, $resultGetVoucher->objectResult->fecha, $resultGetVoucher->objectResult->moneda);
					$response->result = 2;
					$response->message = "El recibo manual fue borrado correctamente.";
				}else return $responseSendQuery;
			}else return $resultGetVoucher;
		}else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function getManualReceiptsEmitted($lastId, $filterNameReceiver, $idEmpresa){
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseSendQuery = $vouchersEmittedClass->getManualReceiptsEmitted($lastId, $filterNameReceiver, $idEmpresa);
		if($responseSendQuery->result == 2){
			$response->result = 2;
			$response->vouchers = $responseSendQuery->listResult;
			$response->lastId = $responseSendQuery->lastId;
		}else return $responseSendQuery;
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function getBalanceToDateEmitted($idClient, $currentSession){
		$response = new \stdClass();
		$userController = new ctr_users();
		$handleDateTimeClass = new handleDateTime();
		$vouchersEmittedClass = new vouchersEmitted();
		$responseGetConfigClashCollection = $userController->getVariableConfiguration("INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA", $currentSession);
		if($responseGetConfigClashCollection->result == 2){
			$resultGetBalanceUYU = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, "UYU", $handleDateTimeClass->getCurrentDateTimeInt(), $responseGetConfigClashCollection->configValue, $currentSession->idEmpresa);
			$response->balanceUYU = $resultGetBalanceUYU->balance;
			$resultGetBalanceUSD = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, "USD", $handleDateTimeClass->getCurrentDateTimeInt(), $responseGetConfigClashCollection->configValue, $currentSession->idEmpresa);
			$response->balanceUSD = $resultGetBalanceUSD->balance;
		}else{
			$response->balanceUYU = 0;
			$response->balanceUSD = 0;
		}
		return $response;
	}
	//UPDATED
	public function getLastVoucherEmitted($idEmpresa){
		$voucherEmittedClass = new vouchersEmitted();
		return $voucherEmittedClass->getLastVoucherEmitted($idEmpresa);
	}

	public function getLastIdVoucherByRut($rut){
		return vouchersEmitted::getLastIdVoucherByRut($rut);
	}
	//UPDATED
	public function updateDataVoucherEmitted($currentSession){
		//posible error al traer primer comprobante realizado
		$response = new \stdClass();
		$vouchEmittedController = new ctr_vouchers_emitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetLastId = $vouchEmittedController->getLastVoucherEmitted($currentSession->idEmpresa);
		if($responseGetLastId->result == 2){
			$lastVoucherId = $responseGetLastId->objectResult->id;

			$responseSendRest = $vouchEmittedController->getVouchersEmittedFromRest($currentSession, 1, $lastVoucherId, null, null);
			if(isset($responseSendRest->result) && ($responseSendRest->result == 1 || $responseSendRest->result == 0)){
				return $responseSendRest;
			}else if($responseSendRest->counterRecords == $responseSendRest->counterInserted){
				$response->result = 2;
			}else if($responseSendRest->counterRecords < $responseSendRest->counterInserted && $responseSendRest->counterInserted > 0){
				$response->result = 1;
				if(sizeof($responseSendRest->arrayErrors) > 0)
					$response->errors = $responseSendRest->arrayErrors;
			}else if($responseSendRest->counterInserted == 0){
				$response->result = 0;
			}
			$response->vouchersEmitted = $responseSendRest->counterRecords;
			$response->vouchersEmittedInserted = $responseSendRest->counterInserted;
		}else return $responseGetLastId;
		// }else return $responseGetBusiness;
		return $response;
	}

	//el parametro lastId de esta función recibe null y string que representa id de comprobante
	//UPDATED
	public function getVouchersEmittedFirstLogin($currentSession, $pageSize, $lastId){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$clientController = new ctr_clients();
		$vouchEmittedController = new ctr_vouchers_emitted();
		$counterRecords = 0;
		$counterInserted =  0;

		$response->arrayErrors = array();
		// 								listarEmitidos($rut, $pageSize, $lastId, $dateFrom, $branchCompany, $dateTo, $tokenRest)
		$responseSendRest = $restController->listarEmitidos($currentSession->rut, $pageSize, $lastId, null,null, null, $currentSession->tokenRest);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listEmitidos as $key => $voucher) {
				$counterRecords++;
				$idClient = null;
				if(!empty($voucher->receptor->documento)){
					if ( is_numeric($voucher->receptor->documento) ){
						$responseGetClient = $clientController->findClientWithDoc($voucher->receptor->documento, $currentSession->idEmpresa);
						if($responseGetClient->result == 2){
							$idClient = $responseGetClient->client->id;
						}else if($responseGetClient->result == 1){
							$resposneInsertClientFirst = $clientController->insertClientFirstLogin($currentSession->rut, $voucher->receptor->documento, $voucher->receptor->nombre, $currentSession);
							if($resposneInsertClientFirst->result == 2) // ACA ACA ACA ACA ACA ACA ACA
								$idClient = $resposneInsertClientFirst->id;
						}
					}
				}
				if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
				$responseInsertVoucherEmitted = $vouchEmittedController->insertVoucherEmitted($voucher->id ,$voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $voucher->sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $idClient, $currentSession->idEmpresa);
				if($responseInsertVoucherEmitted->result == 2){
					$lastId = $voucher->id;
					$counterInserted++;
				}else $arrayErrors[] = $responseInsertVoucherEmitted;
			}
			if(sizeof($responseSendRest->listEmitidos) == 200){
				$responseRecursive = $vouchEmittedController->getVouchersEmittedFirstLogin($currentSession, $pageSize, $lastId);
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

////////////////////////////////////////////////////////////////////////////////////////////////////////
	//FUNCION NUEVA PARA TRAER COMPROBANTES EMITIDOS
////////////////////////////////////////////////////////////////////////////////////////////////////////
	//el lastIdLocal se usa como tope de salida y el lastId se utiliza para consultar por un comprobante anterior
	//UPDATED
	public function getVouchersEmittedFromRest($currentSession, $pageSize, $lastIdLocal, $lastId, $lastDate){
		$response = new \stdClass();
		$usersClass = new users();
		$othersClass = new others();
		$validateClass = new validate();
		$userController = new ctr_users();
		$restController = new ctr_rest();
		$vouchersEmittedClass = new vouchersEmitted();
		$clientController = new ctr_clients();
		
		$rut = $currentSession->rut;
		$tokenRest = $currentSession->tokenRest;
		$idEmpresa = $currentSession->idEmpresa;

		$counterRecords = 0;
		$counterInserted =  0;
		$response->arrayErrors = array();
		//si el ultimo local y el ultimo que hay en rest son iguales no busco de nuevo
		$responseSendRest = $restController->listarEmitidos($rut, 1, null, null,null, null, $tokenRest);//obtengo el ultimo comprobante registrado en ormen, el comp más reciente
		if ( $responseSendRest->result == 2 ){
			$voucher = $responseSendRest->listEmitidos[0];
			//last id llega por parametro es el ultimo que tengo local - y - $voucher->id es el ultimo voucher que se encuentra
			if ( $lastIdLocal != $voucher->id ){
				$responseSendRest = $restController->listarEmitidos($rut, $pageSize, $lastId, null,null, null, $tokenRest);
				if($responseSendRest->result == 2){
					// $responseGetBusiness = ctr_users::getBusinessInformationByRut($rut);
					// if ( $responseGetBusiness->result != 2 ){
					// 	return $responseGetBusiness;
					// }
					// $idBusiness = $responseGetBusiness->objectResult->idEmpresa;
					// idEmpresa
					$arrayErrors = array();
					foreach ($responseSendRest->listEmitidos as $key => $voucher) {
						if ($lastIdLocal != $voucher->id){
							$counterRecords++;
							$idClient = null;
							//consulta si el comprobante se encuentra en el sistema, si se encuentra incrementa el $counterInserted
							$responseGetVoucher = $vouchersEmittedClass->getVoucherEmitted($voucher->id, $idEmpresa);
							if($responseGetVoucher->result == 1){
								if(isset($voucher->receptor->documento) && strlen($voucher->receptor->documento) > 6){
									$responseGetClient = $clientController->findClientWithDoc($voucher->receptor->documento, $idEmpresa);
									if($responseGetClient->result == 2){
										$idClient = $responseGetClient->client->id;
									}else{
										$responseInsertClientFirst = $clientController->insertClientFirstLogin($rut, $voucher->receptor->documento, $voucher->receptor->nombre, $currentSession);
										if($responseInsertClientFirst->result == 2)
											$idClient = $responseInsertClientFirst->id;
									}
								}
								if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
								$sucursal = null;
								if ( isset($voucher->sucursal) ){
									if($voucher->sucursal == $sucursal)
										$sucursal = $voucher->sucursal;
								}

								$responseInsertVoucherEmitted = $vouchersEmittedClass->insertVoucherEmitted($voucher->id ,$voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $idClient, $idEmpresa);
								if($responseInsertVoucherEmitted->result == 2){
									$newLastID = $voucher->id;
									$counterInserted++;
								}else $arrayError[] = $responseInsertVoucherEmitted;
							}else {
								$response->result = 2;
								$response->counterRecords = $counterRecords;
								$response->counterInserted = $counterInserted;
								$response->arrayErrors = $arrayErrors;
								return $response;
							}
						}else {
							$response->result = 2;
							$response->counterRecords = $counterRecords;
							$response->counterInserted = $counterInserted;
							$response->arrayErrors = $arrayErrors;
							return $response;
						}
					}
					if(sizeof($responseSendRest->listEmitidos) == $pageSize){
						error_log("funcion recursiva getVouchersEmittedFromRest");
						$newLastId = $responseSendRest->listEmitidos[sizeof($responseSendRest->listEmitidos)-1]->id;
						$responseRecursive = $this->getVouchersEmittedFromRest($currentSession, $pageSize, $lastIdLocal, $newLastId, null);
						return $responseRecursive;
					}
				}else return $responseSendRest;

				$response->result = 2;
				$response->counterRecords = $counterRecords;
				$response->counterInserted = $counterInserted;
				$response->arrayErrors = $arrayErrors;
				return $response;
			}else {
				//error_log("No se traen comprobantes para actualizar. El ultimo local es igual al de ormen");
				$response->result = 2;
				$response->message = "La base local ya se encuentra actualizada";
				return $response;
			}
		}else {
			error_log("Ocurrió un error al obtener comprobantes: ".$responseSendRest->message);
			return $responseSendRest;
		}
	}

	public function updateVouchersEmitted($rut, $pageSize, $lastId, $dateFrom, $dateTo, $idBusiness){
		$response = new \stdClass();

		$newLastID = $lastId;
		$counterRecords = 0;
		$counterInserted =  0;
		$sucursal = 0;

		$variableBrancCompany = ctr_users::getVariableConfiguration("SUCURSAL_IS_PRINCIPAL");
			if($variableBrancCompany->result == 2){
				$sucursal = $variableBrancCompany->configValue;
			}


		$response->arrayErrors = array();
		$responseSendRest = ctr_rest::listarEmitidos($rut, $pageSize, null, $dateFrom,null, $dateTo);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listEmitidos as $key => $voucher) {
				$counterRecords++;
				$idClient = null;
				$responseGetVoucher = vouchersEmitted::getVoucherEmitted($voucher->id, $idBusiness);
				if($responseGetVoucher->result == 1){
					if(isset($voucher->receptor->documento) && strlen($voucher->receptor->documento) > 6){
						$responseGetClient = ctr_clients::findClientWithDoc($voucher->receptor->documento);
						if($responseGetClient->result == 2){
							$idClient = $responseGetClient->client->id;
						}else{
							$resposneInsertClientFirst = ctr_clients::insertClientFirstLogin($rut, $voucher->receptor->documento, $voucher->receptor->nombre);
							if($resposneInsertClientFirst->result == 2)
								$idClient = $resposneInsertClientFirst->id;
						}
					}
					if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
					if($voucher->sucursal == $sucursal)
						$sucursal = $voucher->sucursal;

					$responseInsertVoucherEmitted = vouchersEmitted::insertVoucherEmitted($voucher->id ,$voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $idClient, $idBusiness);
					if($responseInsertVoucherEmitted->result == 2){
						$newLastID = $voucher->id;
						$counterInserted++;
					}else $arrayError[] = $responseInsertVoucherEmitted;
				}else if($responseGetVoucher->result == 2) $counterInserted++;
			}
			if(sizeof($responseSendRest->listEmitidos) == 200 && $lastId != $newLastID){
				$responseRecursive = ctr_vouchers_emitted::updateVouchersEmitted($rut, $pageSize, $newLastID, $dateFrom, $dateTo, $idBusiness);
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
	public function insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision, $formaPago, $idClient, $idEmpresa){ // ACA ACA ACA ACA ACA ACA ACA
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetVoucher = $vouchersEmittedClass->getVoucherEmitted($id, $idEmpresa);
		if($responseGetVoucher->result != 2){
			// insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $moneda, $sucursal, $isAnulado, $isCobranza, $fechaHoraEmision, $formaPago, $idClient, $idBusiness)
			return $vouchersEmittedClass->insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision, $formaPago, $idClient, $idEmpresa);
		}else{
			$response->result = 2;
			$response->message ="Ya existe el comprobante";
		}
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function getClientAccountSate($idClient, $dateInit, $dateEnding, $typeCoin, $config, $currentSession){
		$response = new \stdClass();
		$userController = new ctr_users();
		$vouchersEmittedClass = new vouchersEmitted();
		$handleDateTimeClass = new handleDateTime();
		$voucherController = new ctr_vouchers();
		$clientController = new ctr_clients();

		$dateInitINT = $handleDateTimeClass->getDateInt($dateInit);
		$dateEndingINT = $handleDateTimeClass->getDateInt($dateEnding);

		// $responseGetBusiness = $userController->getBusinesSession(); //obtiene los datos de la empresa con la que se inicio sesion
		// if($responseGetBusiness->result == 2){
		$resultGetClient = $clientController->getClientWithId($idClient);//obtengo todos los datos de ese cliente a partir de ese id
		if($resultGetClient->result == 2){
			$variableShowBalance = $userController->getVariableConfiguration("VER_SALDOS_ESTADO_CUENTA", $currentSession);//consulto si el usuario tiene acceso a esa configuraciòn
			if($variableShowBalance->result == 2){
				//$responseAccountState se obtienen todos los datos para mostrar en el estado de cuentas
				$responseAccountState = $vouchersEmittedClass->getAccountState($idClient, $dateInitINT, $dateEndingINT, $typeCoin, $currentSession->idEmpresa, $config);
				if($responseAccountState->result != 0){
					// $resultGetBusiness = $userController->getBusinessInformation($currentSession->idEmpresa);
					// if($resultGetBusiness->result == 2){
					$resultGenerateFile = $voucherController->exportAccountState($resultGetClient->client, $dateInitINT, $dateEndingINT, $responseAccountState->listResult, "CLIENT", $currentSession);
					$response->result = 2;
					if($variableShowBalance->configValue == "NO"){
						unset($responseAccountState->listResult["BALANCEUSD"]);
						unset($responseAccountState->listResult["BALANCEUYU"]);
					}
					$response->accountState = $responseAccountState->listResult;//los datos para mostrar en la tabla
					$response->name = $resultGetClient->client->nombreReceptor;
					$response->documentSelected = $resultGetClient->client->docReceptor;
					if($resultGenerateFile->result == 2){
						$response->resultFile = 2;
						$response->fileGenerate = $resultGenerateFile->fileGenerate;
					}else{
						$response->resultFile = 0;
						$response->messageFile = "Ocurrió un error y el archivo pdf no pudo generarse correctamente.";
					}
					$voucherController->saveInfoAccountStateTemp($resultGetClient->client->id, $resultGetClient->client->docReceptor, $handleDateTimeClass->setFormatHTMLDate($dateInitINT), $handleDateTimeClass->setFormatHTMLDate($dateEndingINT), $typeCoin, $config, "CLIENT");
					// }else return $resultGetBusiness;
				}else return $responseAccountState;
			}else return $variableShowBalance;
		}else return $resultGetClient;
		// }else return $responseGetBusiness;

		return $response;
	}


//////////////////////////////////////////////////////////////////////////////////////////////
//OBTENER CUANTOS COMPROBANTES EMITIDOS HAY EN TOTAL EN UN PERIODO DETERMINADO
	//WORKING
	public function countAllVouchersEmittedRest($rut, $pageSize, $lastId, $dateFrom, $dateTo, $tokenRest){

		//error_log("en la funcion contando todos los emitidos");
		$response = new \stdClass();
		$restController = new ctr_rest();
		$voucherEmittedController = new ctr_vouchers_emitted();

		// $idBusiness = $_SESSION['systemSession']->idBusiness;
		$response->value = 0;

		$responseSendRest = $restController->listarEmitidos($rut, $pageSize, $lastId, $dateFrom, null, $dateTo, $tokenRest);
		if($responseSendRest->result == 2){
			$response->result = 2;
			$response->value += sizeof($responseSendRest->listEmitidos);

			if(sizeof($responseSendRest->listEmitidos) == $pageSize){
				$newLastId = $responseSendRest->listEmitidos[sizeof($responseSendRest->listEmitidos)-1]->id;
				$responseRecursive = $voucherEmittedController->countAllVouchersEmittedRest($rut, $pageSize, $newLastId, $dateFrom, $dateTo, $tokenRest);
				$response->value += $responseRecursive->value;
				$responseRecursive->value = $response->value;
				return $responseRecursive;
			}else{
				error_log("en la ultima consulta");
				return $response;
			}
		}else{
			//error_log("no hay mas comprobantes, saliendo");
			$response->result = 2;
			return $response;
		}
		return $response;
	}
}