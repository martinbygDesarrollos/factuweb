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
	function cancelVoucherEmitted($idVoucher, $dateCancelVoucher, $appendix){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetVoucher = vouchersEmitted::getVoucherEmitted($idVoucher, $responseGetBusiness->idBusiness);
			if($responseGetVoucher->result == 2){
				$responseGetType = utils::getTypeToCancelVoucher($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->isCobranza);
				$detailReceiver = null;
				$responseGetClient = null;
				if(!is_null($responseGetVoucher->objectResult->idCliente)){ //asigno un cliente
					$responseGetClient = ctr_clients::getClientWithId($responseGetVoucher->objectResult->idCliente);
					if($responseGetClient->result == 2){
						$detailReceiver = ctr_rest::prepareReceptorToSend($responseGetClient->client->docReceptor, $responseGetClient->client->nombreReceptor, $responseGetClient->client->direccion, $responseGetClient->client->localidad, $responseGetClient->client->departamento,"Uruguay");
					}else return $responseGetClient;
				}

				// intentar traer el comprobante
				$responseRestGetCFE = ctr_rest::consultarCFE($responseGetBusiness->infoBusiness->rut, null, $responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, "application/json");
				if($responseRestGetCFE->result == 2){
					// se obtuvo el comprobante
					$verifyCancelledVoucher = ctr_rest::updateVoucherAnuladoDgi($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, $responseRestGetCFE->cfe->isAnulado);
					if ( $verifyCancelledVoucher->anular ){


						$jsonPrintFormat = json_decode($responseRestGetCFE->cfe->representacionImpresa);
						$arrayDetails = array();
						if( $responseGetVoucher->objectResult->isCobranza  == 1){
							if($responseGetVoucher->objectResult->tipoCFE == 101 || $responseGetVoucher->objectResult->tipoCFE == 111){
								$usdValue = ctr_vouchers::getQuote("USD", null);
								$arrayReference = ctr_rest::prepareReferenciasToSend($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, null, null);
								$indFact = 7; //Producto o servicio no facturable negativo
								$responseCreateVoucherCancel2 = null;
								if( $responseGetClient ){
									$responseCreateVoucherCancel2 = ctr_vouchers_emitted::createVoucherReceiptEmitted($responseGetClient->client->docReceptor, $responseGetClient->client->direccion, $responseGetClient->client->localidad, $idVoucher, $dateCancelVoucher, $usdValue->currentQuote, $responseRestGetCFE->cfe->total, $arrayReference, 1, $indFact);
								}else{
									$responseCreateVoucherCancel2 = ctr_vouchers_emitted::createVoucherReceiptEmitted(null, null, null, $idVoucher, $dateCancelVoucher, $usdValue->currentQuote, $responseRestGetCFE->cfe->total, $arrayReference, 1, $indFact);
								}
								if ($responseCreateVoucherCancel2->result == 2){
									$response->result = 2;
									$response->message = "Se emitió correctamente la cancelación del comprobante seleccionado.";
									return $response;
								}else return $responseCreateVoucherCancel2;
							}else{
								foreach ($jsonPrintFormat->detalles as $key => $itemDetail)
									$arrayDetails[] = ctr_rest::prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio);
							}
						}else{
							foreach ($jsonPrintFormat->detalles as $key => $itemDetail)
								$arrayDetails[] = ctr_rest::prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio);
						}


						$arrayReference = ctr_rest::prepareReferenciasToSend($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->serieCFE, $responseGetVoucher->objectResult->numeroCFE, null, null);
						$newAppendix = "Anulación " . utils::getNameVoucher($responseGetVoucher->objectResult->tipoCFE, $responseGetVoucher->objectResult->isCobranza) . " " . $responseGetVoucher->objectResult->serieCFE . $responseGetVoucher->objectResult->numeroCFE . ". \n" .  $appendix;

						$branchCompany = null;
						$responseGetGrossAmount = ctr_users::getVariableConfiguration("SUCURSAL_IS_PRINCIPAL");
						if ($responseGetGrossAmount->result == 2){
							$branchCompany = $responseGetGrossAmount->configValue;
						}

						$responseCreateVoucherCancel = ctr_vouchers::createNewCFE($responseGetType->type, $dateCancelVoucher, $jsonPrintFormat->montosBrutos, $responseGetVoucher->objectResult->formaPago, null, $jsonPrintFormat->tipoMoneda, $arrayDetails, $detailReceiver, 0, array($arrayReference), $newAppendix, $branchCompany, null);
						if($responseCreateVoucherCancel->result == 2){
							ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetBusiness->infoBusiness->rut);
							$response->result = 2;
							$response->message = "Se emitió correctamente la cancelación del comprobante seleccionado.";
						}else return $responseCreateVoucherCancel;


					} else return $verifyCancelledVoucher;

				}else return $responseRestGetCFE;
			}else return $responseGetVoucher;
		}else return $responseGetBusiness;

		return $response;
	}

	public function getMinAndMaxDateVoucher(){

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return vouchersEmitted::getMinAndMaxDateVoucher($responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function getTypeExistingVouchers(){

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return vouchersEmitted::getTypeExistingVouchers($responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function createVoucherReceiptEmitted($documentClient, $address, $city, $idsVouchersSelected, $dateVoucher, $usdValue, $total, $reasonReference, $checkedOfficial, $indFact){
		$response = new \stdClass();
		$responseGetBusiness = ctr_users::getBusinesSession();//obtenemos los datos de la empresa mediante el id de la sesiòn
		if($responseGetBusiness->result == 2){
			$resultGetBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
			if($resultGetBusiness->result == 2){
				$responseGetClient = ctr_clients::findClientWithDoc($documentClient); //obtenemos un cliente a partir del documento
				//if($responseGetClient->result == 2){
					$tipoCFE = null;
					if( $documentClient ){
						$resultValidateRUT = validate::validateRut($documentClient);
						if($resultValidateRUT->result == 2)
							$tipoCFE = 111;
						else if(validate::validateCI($documentClient))
							$tipoCFE = 101;
						else{
							$response->result = 0;
							$response->message = "El documento del cliente no pudo validarse como rut o cédula.";
						}
					}else{ $tipoCFE = 101; }

					$lastAccountState = ctr_users::getLastAccountStateInfo('CLIENT');
					$typeCoin = null;
					if($lastAccountState->result == 2)
						$typeCoin = $lastAccountState->information->selectedCoin;

					if($checkedOfficial == 0){
						return ctr_vouchers_emitted::createManualReceiptEmitted($documentClient, $dateVoucher, $typeCoin, $total);
					}else{
						if(!is_null($tipoCFE)){
							$dateVoucherINT = handleDateTime::getDateInt($dateVoucher);
							if ( !$indFact )
								$indFact = 6;
							$detalle = array(ctr_rest::prepareDetalleToSend($indFact, "Recibo cobranza", null, null, 1, null, $total));
							if($responseGetClient->result == 2){
								$client = $responseGetClient->client;
								$receptor = ctr_rest::prepareReceptorToSend($client->docReceptor, $client->nombreReceptor, $client->direccion, $client->localidad, $client->departamento, "Uruguay");
							}else $receptor = null;
							$arrayReferencias = array();
							if(strlen($idsVouchersSelected) > 3){
								$arrayIdVouchers = explode(",", $idsVouchersSelected);
								if(sizeof($arrayIdVouchers) >= 1){
									foreach ($arrayIdVouchers as $key => $value) {
										$responseGetVoucher = vouchersEmitted::getVoucherEmitted($value, $responseGetBusiness->idBusiness);
										if($responseGetVoucher->result == 2){
											if(!$typeCoin){
												$typeCoin = $responseGetVoucher->objectResult->moneda;
											}
											$objVoucher = $responseGetVoucher->objectResult;
											$arrayReferencias[] = ctr_rest::prepareReferenciasToSend($objVoucher->tipoCFE, $objVoucher->serieCFE, $objVoucher->numeroCFE, null, null);
										}
									}
								}
							}else $arrayReferencias[] = ctr_rest::prepareReferenciasToSend(null, null, null, 1, $reasonReference);
							$responseSendRest = ctr_rest::nuevoRecibo($resultGetBusiness->objectResult->rut, $tipoCFE, $dateVoucherINT, 1, 1, $typeCoin, $usdValue, $detalle, $arrayReferencias, $receptor);
							if($responseSendRest->result == 2){
								$response->result = 2;
								$response->message = "Su recibo oficial fue creado correctamente.";
								ctr_vouchers_emitted::updateDataVoucherEmitted($resultGetBusiness->objectResult->rut);
							}else return $responseSendRest;
						}
					}
				//}else {echo "1 // ";return $responseGetClient;}
			}else return $resultGetBusiness;
		}else return $responseGetBusiness;

		return $response;
	}

	public function calculateTotalVoucherSelected($idsSelected){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$arrayIds = explode(",", $idsSelected);
			$total = 0;
			$arrayVouchers = array();
			foreach($arrayIds as $key => $voucher){
				$responseGetVoucher = vouchersEmitted::getVoucherEmitted($voucher, $responseGetBusiness->idBusiness);
				if($responseGetVoucher->result == 2){
					$arrayVouchers[] = $responseGetVoucher->objectResult;
				}else return $responseGetVoucher;
			}
			$response->result = 2;
			$response->total = vouchersEmitted::getBlanaceFromVouchers($arrayVouchers, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
		return $response;
	}

	public function getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $branchCompany){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			if($dateVoucher != 0)
				$dateVoucher = handleDateTime::getDateInt($dateVoucher);
			$responseGetVouchers = vouchersEmitted::getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $responseGetBusiness->idBusiness, $branchCompany);
			if($responseGetVouchers->result == 2){
				$newList = array();
				foreach ($responseGetVouchers->listResult as $key => $value) {
					if(is_null($value['idCliente'])){
						$value['documentoCliente'] = "Consumidor Final";
						$value['nombreCliente'] = "Consumidor Final";
					}
					else{
						$responseGetClient = ctr_clients::getClientWithId($value['idCliente']);
						if($responseGetClient->result == 2){
							if(!empty($responseGetClient->client->docReceptor)){
								$value['documentoCliente'] = utils::formatDocuments($responseGetClient->client->docReceptor);
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
		}else return $responseGetBusiness;
	}

	public function createManualReceiptEmitted($documentClient, $dateVoucher, $typeCoin, $total){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$resultGetClient = ctr_clients::findClientWithDoc($documentClient);
			if($resultGetClient->result == 2){
				$dateVoucherINT = handleDateTime::getDateInt($dateVoucher);
				$responseSendQuery = vouchersEmitted::createVoucher($resultGetClient->client->id, $dateVoucherINT, $typeCoin, $total, $responseGetBusiness->idBusiness);
				if($responseSendQuery->result == 2){
					ctr_vouchers::insertReceiptHistory($documentClient, 1, "Crear", $total, $dateVoucherINT, $typeCoin);
					$response->result = 2;
					$response->message = "El recibo manual fue creado correctamente.";
				}else return $responseSendQuery;
			}else return $resultGetClient;
		}else return $responseGetBusiness;

		return $response;
	}

	public function modifyManualReceiptEmitted($indexVoucher, $total, $dateReceipt, $typeCoin){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$resultGetVoucher = vouchersEmitted::getVoucherWithIndex($indexVoucher);
			if($resultGetVoucher->result == 2){
				$dateInit = handleDateTime::getDateInt($dateReceipt);
				$resultSendQuery = vouchersEmitted::modifyManualReceipt($indexVoucher, $total, $dateInit, $typeCoin, $responseGetBusiness->idBusiness);
				if($resultSendQuery->result == 2){
					$resultGetClient = ctr_clients::getClientWithId($resultGetVoucher->objectResult->idCliente);
					if($resultGetVoucher->result == 2)
						ctr_vouchers::insertReceiptHistory($resultGetClient->client->docReceptor, 1, "Modificar", $total, $dateInit, $typeCoin);
					$response->result = 2;
					$response->message = "El recibo manual fue modificado correctamente.";
					$response->newTotal = number_format($total,2, ",",".");
				}else return $resultSendQuery;
			}else return $resultGetVoucher;
		}else $response = $responseGetBusiness;

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

	public function getManualReceiptsEmitted($lastId, $filterNameReceiver){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseSendQuery = vouchersEmitted::getManualReceiptsEmitted($lastId, $filterNameReceiver, $responseGetBusiness->idBusiness);
			if($responseSendQuery->result == 2){
				$response->result = 2;
				$response->vouchers = $responseSendQuery->listResult;
				$response->lastId = $responseSendQuery->lastId;
			}else return $responseSendQuery;
		}else return $responseGetBusiness;

		return $response;
	}

	public function getBalanceToDateEmitted($idClient, $idBusiness){
		$response = new \stdClass();
		$responseGetConfigClashCollection = ctr_users::getVariableConfiguration("INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA");
		if($responseGetConfigClashCollection->result == 2){
			$resultGetBalanceUYU = vouchersEmitted::getBalanceToDateEmitted($idClient, "UYU", handleDateTime::getCurrentDateTimeInt(), $responseGetConfigClashCollection->configValue, $idBusiness);
			$response->balanceUYU = $resultGetBalanceUYU->balance;
			$resultGetBalanceUSD = vouchersEmitted::getBalanceToDateEmitted($idClient, "USD", handleDateTime::getCurrentDateTimeInt(), $responseGetConfigClashCollection->configValue, $idBusiness);
			$response->balanceUSD = $resultGetBalanceUSD->balance;
		}else{
			$response->balanceUYU = 0;
			$response->balanceUSD = 0;
		}
		return $response;
	}

	public function getLastVoucherEmitted(){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return vouchersEmitted::getLastVoucherEmitted($responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function getLastIdVoucherByRut($rut){
		return vouchersEmitted::getLastIdVoucherByRut($rut);
	}

	public function updateDataVoucherEmitted($rut){
		//posible error al traer primer comprobante realizado
		$response = new \stdClass();
		$vouchEmittedController = new ctr_vouchers_emitted();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetLastId = ctr_vouchers_emitted::getLastVoucherEmitted();
			if($responseGetLastId->result == 2){
				$lastVoucherId = $responseGetLastId->objectResult->id;

				$responseSendRest = $vouchEmittedController->getVouchersEmittedFromRest($rut, 1, $lastVoucherId, null, null);
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
		}else return $responseGetBusiness;
		return $response;
	}

	//el parametro lastId de esta función recibe null y string que representa id de comprobante
	public function getVouchersEmittedFirstLogin($rut, $pageSize, $lastId){
		$response = new \stdClass();
		$counterRecords = 0;
		$counterInserted =  0;

		$response->arrayErrors = array();
		$responseSendRest = ctr_rest::listarEmitidos($rut, $pageSize, $lastId, null,null, null);
		if($responseSendRest->result == 2){
			$arrayErrors = array();
			foreach ($responseSendRest->listEmitidos as $key => $voucher) {
				$counterRecords++;
				$idClient = null;
				if(!empty($voucher->receptor->documento)){
					if ( is_numeric($voucher->receptor->documento) ){
						$responseGetClient = ctr_clients::findClientWithDoc($voucher->receptor->documento);
						if($responseGetClient->result == 2){
							$idClient = $responseGetClient->client->id;
						}else if($responseGetClient->result == 1){
							$resposneInsertClientFirst = ctr_clients::insertClientFirstLogin($rut, $voucher->receptor->documento, $voucher->receptor->nombre);
							if($resposneInsertClientFirst->result == 2)
								$idClient = $resposneInsertClientFirst->id;
						}
					}
				}
				if(is_null($voucher->formaPago)) $voucher->formaPago = 1;
				$responseInsertVoucherEmitted = ctr_vouchers_emitted::insertVoucherEmitted($voucher->id ,$voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $voucher->sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $idClient);
				if($responseInsertVoucherEmitted->result == 2){
					$lastId = $voucher->id;
					$counterInserted++;
				}else $arrayErrors[] = $responseInsertVoucherEmitted;
			}
			if(sizeof($responseSendRest->listEmitidos) == 200){
				$responseRecursive = ctr_vouchers_emitted::getVouchersEmittedFirstLogin($rut, $pageSize, $lastId);
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
	public function getVouchersEmittedFromRest($rut, $pageSize, $lastIdLocal, $lastId, $lastDate){
		$response = new \stdClass();
		$counterRecords = 0;
		$counterInserted =  0;

		$response->arrayErrors = array();
		//si el ultimo local y el ultimo que hay en rest son iguales no busco de nuevo
		$responseSendRest = ctr_rest::listarEmitidos($rut, 1, null, null,null, null);//obtengo el ultimo comprobante registrado en ormen, el comp más reciente
		if ( $responseSendRest->result == 2 ){
			$voucher = $responseSendRest->listEmitidos[0];
			//last id llega por parametro es el ultimo que tengo local - y - $voucher->id es el ultimo voucher que se encuentra
			if ( $lastIdLocal != $voucher->id ){
				$responseSendRest = ctr_rest::listarEmitidos($rut, $pageSize, $lastId, null,null, null);
				if($responseSendRest->result == 2){
					$responseGetBusiness = ctr_users::getBusinessInformationByRut($rut);
					if ( $responseGetBusiness->result != 2 ){
						return $responseGetBusiness;
					}
					$idBusiness = $responseGetBusiness->objectResult->idEmpresa;
					$arrayErrors = array();
					foreach ($responseSendRest->listEmitidos as $key => $voucher) {
						if ($lastIdLocal != $voucher->id){
							$counterRecords++;
							$idClient = null;
							//consulta si el comprobante se encuentra en el sistema, si se encuentra incrementa el $counterInserted
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
								$sucursal = null;
								if ( isset($voucher->sucursal) ){
									if($voucher->sucursal == $sucursal)
										$sucursal = $voucher->sucursal;
								}

								$responseInsertVoucherEmitted = vouchersEmitted::insertVoucherEmitted($voucher->id ,$voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $voucher->total, $voucher->fecha, $voucher->tipoMoneda, $sucursal, $voucher->isAnulado, $voucher->isCobranza, $voucher->emision, $voucher->formaPago, $idClient, $idBusiness);
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
						$responseRecursive = ctr_vouchers_emitted::getVouchersEmittedFromRest($rut, $pageSize, $lastIdLocal, $newLastId, null);
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

	public function insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision, $formaPago, $idClient){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetVoucher = vouchersEmitted::getVoucherEmitted($id, $responseGetBusiness->idBusiness);
			if($responseGetVoucher->result != 2){
				return vouchersEmitted::insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $tipoMoneda, $sucursal, $isAnulado, $isCobranza, $emision, $formaPago, $idClient, $responseGetBusiness->idBusiness);
			}else{
				$response->result = 2;
				$response->message ="Ya existe el comprobante";
			}
		}else return $responseGetBusiness;

		return $response;
	}

	public function getClientAccountSate($idClient, $dateInit, $dateEnding, $typeCoin, $config){
		$response = new \stdClass();

		$dateInitINT = handleDateTime::getDateInt($dateInit);
		$dateEndingINT = handleDateTime::getDateInt($dateEnding);

		$responseGetBusiness = ctr_users::getBusinesSession(); //obtiene los datos de la empresa con la que se inicio sesion
		if($responseGetBusiness->result == 2){
			$resultGetClient = ctr_clients::getClientWithId($idClient);//obtengo todos los datos de ese cliente a partir de ese id
			if($resultGetClient->result == 2){
				$variableShowBalance = ctr_users::getVariableConfiguration("VER_SALDOS_ESTADO_CUENTA");//consulto si el usuario tiene acceso a esa configuraciòn
				if($variableShowBalance->result == 2){
					//$responseAccountState se obtienen todos los datos para mostrar en el estado de cuentas
					$responseAccountState = vouchersEmitted::getAccountState($idClient, $dateInitINT, $dateEndingINT, $typeCoin, $responseGetBusiness->idBusiness, $config);
					if($responseAccountState->result != 0){
						$resultGetBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
						if($resultGetBusiness->result == 2){
							$resultGenerateFile = ctr_vouchers::exportAccountState($resultGetClient->client, $dateInitINT, $dateEndingINT, $responseAccountState->listResult, "CLIENT", $resultGetBusiness->objectResult);
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
							ctr_vouchers::saveInfoAccountStateTemp($resultGetClient->client->id, $resultGetClient->client->docReceptor, handleDateTime::setFormatHTMLDate($dateInitINT), handleDateTime::setFormatHTMLDate($dateEndingINT), $typeCoin, $config, "CLIENT");
						}else return $resultGetBusiness;
					}else return $responseAccountState;
				}else return $variableShowBalance;
			}else return $resultGetClient;
		}else return $responseGetBusiness;

		return $response;
	}


//////////////////////////////////////////////////////////////////////////////////////////////
//OBTENER CUANTOS COMPROBANTES EMITIDOS HAY EN TOTAL EN UN PERIODO DETERMINADO

	public function countAllVouchersEmittedRest($rut, $pageSize, $lastId, $dateFrom, $dateTo){

		//error_log("en la funcion contando todos los emitidos");
		$response = new \stdClass();
		$restController = new ctr_rest();
		$voucherEmittedController = new ctr_vouchers_emitted();

		$idBusiness = $_SESSION['systemSession']->idBusiness;
		$response->value = 0;

		$responseSendRest = $restController->listarEmitidos($rut, $pageSize, $lastId, $dateFrom, null, $dateTo);
		if($responseSendRest->result == 2){
			$response->result = 2;
			$response->value += sizeof($responseSendRest->listEmitidos);

			if(sizeof($responseSendRest->listEmitidos) == $pageSize){
				$newLastId = $responseSendRest->listEmitidos[sizeof($responseSendRest->listEmitidos)-1]->id;
				$responseRecursive = $voucherEmittedController->countAllVouchersEmittedRest($rut, $pageSize, $newLastId, $dateFrom, $dateTo);
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