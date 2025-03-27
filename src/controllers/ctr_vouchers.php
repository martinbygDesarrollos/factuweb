<?php

require_once 'ctr_users.php';
require_once 'ctr_clients.php';
require_once 'ctr_providers.php';
require_once 'rest/ctr_rest.php';
require_once 'ctr_products.php';
require_once 'ctr_vouchers_emitted.php';
require_once 'ctr_vouchers_received.php';

require_once '../src/class/vouchers_emitted.php';
require_once '../src/class/vouchers_received.php';

require_once '../src/utils/handle_date_time.php';
require_once '../src/utils/validate.php';
require_once '../src/utils/utils.php';
require_once '../src/filemanagment/managment_pdf.php';
require_once '../src/filemanagment/managment_spreadsheet.php';

class ctr_vouchers{


	/*
	* VL: Obtengo toda la informaciòn de los indicadores_facturacion (iva) segùn los permisos de acceso que tenga el usuario que inicia sesiòn
	*/
	//UPDATED
	public function getIVAsAllowed($currentSession){
		$othersClass = new others();
		$userController = new ctr_users();
		$response = new \stdClass();
		$responseGetConfig = $userController->getVariableConfiguration("INDICADORES_FACTURACION_USABLES", $currentSession);
		if($responseGetConfig->result == 2){
			$listIVA = explode(",", $responseGetConfig->configValue);//en configValue obtenemos los ids de los indicadores_facturacion a los que se tienen permisos
			$arrayResult = array();
			for($i = 0; $i < sizeof($listIVA); $i ++) {
				$responseGetIVA = $othersClass->getValueIVA($listIVA[$i]);//$responseGetIVA tenemos toda la informaciòn de un indicadores_facturacion segùn el id
				if($responseGetIVA->result == 2){
					$newRow = array("idIVA" => $responseGetIVA->objectResult->id, "nombre" => $responseGetIVA->objectResult->nombre, "valor" => number_format($responseGetIVA->objectResult->valor,2,",","."));
					$arrayResult[] = $newRow;
				}
			}
			$response->result = 2;
			$response->listResult = $arrayResult;
		}else return $responseGetConfig;

		return $response;
	}
	//UPDATED
	public function consultCaes($typeCFE, $currentSession){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$utilClass = new utils();
		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetCaes = $restController->consultarCaes($currentSession->rut, $currentSession->tokenRest);
		if($responseGetCaes->result == 2){
			foreach ($responseGetCaes->caes->caes as $key => $cae) {
				if(strcmp($cae->cfeType, $typeCFE)){
					if($cae->isUsable == true){
						$response->result = 2;
						$response->message ="USABLE";
					}else{
						$response->result = 0;
						$response->message = "Actualmente no puede emitir " . $utilClass->getNameVoucher($typeCFE,0) . ".";
					}
					break;
				}
			}
			if(!isset($response->result)){
				$response->result = 0;
				$response->message = "No se encontro información para CFEs de tipo: " . $utilClass->getNameVoucher($typeCFE,0) . " entre los CAEs disponibles.";
			}
		}else return $responseGetCaes;
		// }else return $responseGetBusiness;

		return $response;
	}

	//crea un comprobante para enviarlo a ormen luego de que se envia y es aceptado el comproabnte se registra en el sistema. ctr_rest es la comunicacion con ormen
	//UPDATED
	public function createNewVoucher($objClient, $typeVoucher, $typeCoin, $shapePayment, $dateVoucher, $dateExpiration, $adenda, $listDetail, $idBuy, $discountTipo, $mediosPago, $currentSession){
		$response = new \stdClass();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$userController = new ctr_users();
		$voucherController = new ctr_vouchers();
		$voucherEmittedController = new ctr_vouchers_emitted();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$receiver = null;
		if(count($objClient) != 0){
			$responseIsValid = $clientController->isValidForEmit($typeVoucher, $objClient[0]['document']);
			if($responseIsValid->result == 2){
				$receiver = $restController->prepareReceptorToSend($objClient[0]['document'], $objClient[0]['name'], $objClient[0]['address'], $objClient[0]['city'], $objClient[0]["department"], "Uruguay");
			}else return $responseIsValid;
		}
		$arrayDetail = array();
		foreach ($listDetail as $key => $detail){
			$iva = 1;
			if ($detail['idIva'] > 0 || $detail['idIva'] != null || $detail['idIva'] != ""){ // PARA QUE?????????????
				$iva = $detail['idIva'];
			}
			if(isset($detail['discount']) && floatval($detail['discount']) > 0 ){ // EL PRODUCTO TIENE DESCUENTO
				// if($discountTipo == 2){ // DESCUENTO EN PORCENTAJE
				// 	$discount_percentage = floatval($detail['discount']);
				// 	$original_price = floatval($detail['price']);
				// 	$discount_amount = $original_price * ($discount_percentage / 100);
				// 	$detail['price'] = $original_price - $discount_amount;
				// }else{ // DESCUENTO EN PESOS
				// 	$discount_amount = floatval($detail['discount']);
				// 	$original_price = floatval($detail['price']);
				// 	$detail['price'] = $original_price - $discount_amount;
				// }
				$arrayDetail[] = $restController->prepareDetalleToSend($detail['idIva'], $detail['description'], null, $detail['detail'], $detail['count'], null, $detail['price'], $discountTipo, -$detail['discount']);
			} else {
				$arrayDetail[] = $restController->prepareDetalleToSend($detail['idIva'], $detail['description'], null, $detail['detail'], $detail['count'], null, $detail['price']);
			}
		}
		$responseGetGrossAmount = $userController->getVariableConfiguration("IVA_INCLUIDO", $currentSession);
		if($responseGetGrossAmount->result == 2){
			$grossAmount = 0;
			if(strcmp($responseGetGrossAmount->configValue, "SI") == 0)
				$grossAmount = 1;

			$branchCompany = null;
			$responseGetGrossAmount = $userController->getVariableConfiguration("SUCURSAL_IS_PRINCIPAL", $currentSession);
			if ($responseGetGrossAmount->result == 2){
				$branchCompany = $responseGetGrossAmount->configValue;
			}

			$responseCreateCFE = $voucherController->createNewCFE($typeVoucher, $dateVoucher, $grossAmount, $shapePayment, $dateExpiration, $typeCoin, $arrayDetail, $receiver, 0, null, $adenda, $branchCompany, $idBuy, $currentSession, $mediosPago);
			if($responseCreateCFE->result == 2){
				//$responseGetVouchers = ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetBusiness->infoBusiness->rut);
				$lastVoucher = $voucherEmittedController->getLastVoucherEmitted($currentSession->idEmpresa);
				if ( $lastVoucher->result == 2 )
					$lastVoucherId = $lastVoucher->objectResult->id;
				else $lastVoucherId = "";

				$responseGetVouchers = $voucherEmittedController->getVouchersEmittedFromRest($currentSession, 1, $lastVoucherId, null, null);
				if($responseGetVouchers->result == 2){
					$response->result = 2;
					$response->message = "El comprobante fue emitido correctamente y ya se encuentra en el sistema.";
				}else{
					$response->result = 1;
					$response->message = "El comprobante fue emitido correctamente pero un error no permitio traerlo al sistema. Actualice los comprobantes almacenados para obtenerlo.";
				}
			}else return $responseCreateCFE;
		}else return $responseGetGrossAmount;

		// }else return $responseGetBusiness;

		return $response;
	}

	//carga todos los articulos facturados en comprobantes emitidos como articulos para la lista de precios y para sugerencia en venta.
	//UPDATED
	public function loadProductsFromDetails($currentSession){
		$response = new \stdClass();
		$usersClass = new users();
		$userController = new ctr_users();
		$productsController = new ctr_products();
		$voucherController = new ctr_vouchers();

		$responseGetInfoEmpresa = $usersClass->getEmpresaById($currentSession->idEmpresa);
		if($responseGetInfoEmpresa->result == 2){
			if($responseGetInfoEmpresa->objectResult->detallesObtenidos == 0){
				$productsController->insertHeading("Artículos", $currentSession->idEmpresa); // NO IMPORTA SI FUNCIONA
				$responseInsertDetail = $voucherController->getVouchersEmittedForLoadDetails($currentSession->idEmpresa, $currentSession->rut, 200, null, $currentSession->tokenRest);
				if(isset($responseInsertDetail->inserted) && isset($responseInsertDetail->toInsert)){
					$responseSetUpdatedDetails = $userController->setUpdatedDetails($currentSession->idEmpresa);
					if($responseSetUpdatedDetails->result == 2){
						$response->result = 2;
						$response->message = "Se procesesaron " . $responseInsertDetail->toInsert . " detalles de comprobantes emitidos y se ingresaron " . $responseInsertDetail->inserted . " artículos.";
					}else{
						$response->result = 0;
						$response->message = "Se procesesaron " . $responseInsertDetail->toInsert . " detalles de comprobantes emitidos y se ingresaron " . $responseInsertDetail->inserted . " artículos, pero no se registro en la tabla empresas la operación realizada.";
					}
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y los comprobantes no fueron procesados.";
				}
			}else{
				$response->result = 0;
				$response->message = "Los artículos por detalles de comprobantes emitidos ya fueron cargados para esta empresa.";
			}
		}else return $responseGetInfoEmpresa;

		return $response;
	}
	//UPDATED
	public function getVouchersEmittedForLoadDetails($idBusiness, $rut, $pageSize, $lastId, $tokenRest){
		$response = new \stdClass();
		$productsController = new ctr_products();
		$restController = new ctr_rest();
		$voucherController = new ctr_vouchers();
		$toInsert = 0;
		$inserted =  0;
		set_time_limit(180);
		$responseSendRest = $restController->listarEmitidos($rut, $pageSize, $lastId, null, null, null, $tokenRest);
		if($responseSendRest->result == 2){
			foreach ($responseSendRest->listEmitidos as $key => $voucher) {
				if($voucher->tipoCFE == 101 || $voucher->tipoCFE == 111 && ($voucher->isCobranza == 0 && $voucher->isAnulado == 0)){
					$responseInsertDetail = $productsController->getVoucherDetailJSON($idBusiness, $rut, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $tokenRest);
					if(isset($responseInsertDetail->toInsert)){
						$toInsert += $responseInsertDetail->toInsert;
						$inserted += $responseInsertDetail->inserted;
					}
				}
				$lastId = $voucher->id;
			}
			if(sizeof($responseSendRest->listEmitidos) == 200){
				$responseRecursive = $voucherController->getVouchersEmittedForLoadDetails($idBusiness, $rut, $pageSize, $lastId, $tokenRest);
				$toInsert += $responseRecursive->toInsert;
				$inserted += $responseRecursive->inserted;
			}
		}else return $responseSendRest;

		$response->toInsert = $toInsert;
		$response->inserted = $inserted;

		return $response;
	}
	//UPDATED
	public function exportCFEs($prepareFor, $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $typeVoucher, $currentSession){
		$response = new \stdClass();
		$handleDateTimeClass = new handleDateTime();
		$restController = new ctr_rest();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// $responseGetInfoBusiness = ctr_users::getBusinessInformation($currentSession->idEmpresa);
		// if($responseGetInfoBusiness->result == 2){
		$dateTo = $handleDateTimeClass->getDateInt($dateTo) . "000000";
		$dateFrom = $handleDateTimeClass->getDateInt($dateFrom) . "000000";
		return $restController->exportacion($currentSession->rut, $prepareFor, "application%2Fvnd.ms-excel", $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $typeVoucher, $currentSession->tokenRest);
		// }else return $responseGetInfoBusiness;
		// }else return $responseGetBusiness;

		// return $response;
	}
	//UPDATED
	public function createNewCFE($typeCFE, $dateVoucher, $grossAmount, $typePay, $dateExpiration, $typeCoin, $detail, $receiver, $indCollection, $reference, $appendix, $branchCompany, $idBuy, $currentSession, $mediosPago = null){
		// $response = new \stdClass();
		$restController = new ctr_rest();
		$voucherController = new ctr_vouchers();
		$handleDateTimeClass = new handleDateTime();
		$productsController = new ctr_products();

		$exchangeRate = null;
		if(strcmp($typeCoin, "UYU") != 0){
			$responseExchange = $voucherController->getQuote($typeCoin, $dateVoucher);
			if($responseExchange->result == 2)
				$exchangeRate = $responseExchange->currentQuote;
			else return $responseExchange;
		}
		$dateVoucherINT = $handleDateTimeClass->getDateInt($dateVoucher);
		if(!is_null($dateExpiration))
			$dateExpiration = $handleDateTimeClass->getDateInt($dateExpiration);

		$resultNuevoCFE = $restController->nuevoCFE($currentSession->rut, $typeCFE, $dateVoucherINT, $grossAmount, $typePay, $dateExpiration, $typeCoin, $exchangeRate, $detail, $receiver, $indCollection, $reference, $appendix, $branchCompany, $idBuy, $mediosPago, $currentSession->tokenRest);
		
		if($resultNuevoCFE->result == 2){ // Si funciona la creacion del CFE entonces descuento las cantidades (actualizo stocks)
			// $detail
			// var_dump($detail);
			
			$articulos = array_map(function ($item) {
				return (object) $item;
			}, $detail);
			
			// var_dump($articulos); exit;

			foreach ($articulos as $articulo) {
				$productsController->updateStockProduct($articulo, $currentSession);
			}
			// $productsController->updateStockProduct($detail, $currentSession);
		}
		// return $response;
		return $resultNuevoCFE;
	}

	//UPDATED
	//obtiene la cotizacion de una moneda ingresada
	public function getQuote($typeCoin, $dateQuote){
		$restController = new ctr_rest();
		if(is_null($dateQuote) || strlen($dateQuote) < 4)
			$dateQuote = date('Y-m-d');
		return $restController->obtenerCotizacion($dateQuote, $dateQuote, $typeCoin);
	}

	public function getSoldInfo($typeCoin, $idEmpresa){
		$vouchersEmittedClass = new vouchersEmitted();
		$handleDateTimeClass = new handleDateTime();
		return $vouchersEmittedClass->getSoldInfo($typeCoin, $idEmpresa);
	}


	//obtiene la cotizacion de varias monedas
	//UPDATED
	public function getQuotes(){
		$restController = new ctr_rest();
		$response = new \stdClass();

		$currentDate = date('Y-m-d');

		$responseRestUSD = $restController->obtenerCotizacion($currentDate, $currentDate, "USD");
		if($responseRestUSD->result == 2)
			$response->USD =  bcdiv($responseRestUSD->currentQuote, '1', 4);

		$responseRestUI = $restController->obtenerCotizacion($currentDate, $currentDate, "UI");
		if($responseRestUI->result == 2)
			$response->UI =  bcdiv($responseRestUI->currentQuote, '1', 4);

		$responseRestEUR = $restController->obtenerCotizacion($currentDate, $currentDate, "EUR");
		if($responseRestEUR->result == 2)
			$response->EUR = bcdiv($responseRestEUR->currentQuote, '1', 4);

		return $response;
	}
	//UPDATED
	public function parceDateFormat($dateInit, $dateEnding){
		$handleDateTimeClass = new handleDateTime();
		$dateInitINT = $handleDateTimeClass->getDateInt($dateInit);
		$dateEndingINT = $handleDateTimeClass->getDateInt($dateEnding);

		return $handleDateTimeClass->setFormatBarDate($dateInitINT) . " al " . $handleDateTimeClass->setFormatBarDate($dateEndingINT);
	}


	//actualiza la lista de comprobantes por si alguno no esta.
	public function loadVouchers($callFrom){
		$response = new \stdClass();

		$responseGetUserSession = ctr_users::getUserInSesion();
		if($responseGetUserSession->result == 2){
			if(strcmp($callFrom, "FOOTER") == 0)
				ctr_users::setValueUpdateVouchers(0);

			if($responseGetUserSession->objectResult->datosActualizados == 0){
				$responseGetBusiness = ctr_users::getBusinesSession();
				if($responseGetBusiness->result == 2){
					$responseGetInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
					if($responseGetInfoBusiness->result == 2){
						$emitted = ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetInfoBusiness->objectResult->rut);
						$received = ctr_vouchers_received::updateDataVoucherReceived($responseGetInfoBusiness->objectResult->rut);

						$vouchersEmitted = 0;
						$vouchersEmittedInserted = 0;

						$vouchersReceived = 0;
						$vouchersReceivedInserted = 0;

						if($emitted->result == 1 && $received->result == 1){
							$response->result = 2;
						}if($emitted->result == 2 && $received->result == 2){
							$response->result = 2;
						}else if($emitted->result == 2 && $received->result != 2){
							$response->result = 1;
							$response->message = "Los comprobantes emitidos fueron ingresados, algunos comprobantes recibidos no se ingresaron.";
						}else if($emitted->result != 2 && $received->result == 2){
							$response->result = 1;
							$response->message = "Los comprobantes recibidos fueron ingresados, algunos comprobantes emitidos no fueron ingresados.";
						}else if($emitted->result != 2 && $received->result != 2){
							$response->result = 0;
							$response->message = "No se ingresó correctamente ninguno de los comprobantes obtenidos.";
						}

						if(isset($emitted->vouchersEmitted)){
							$vouchersEmitted = $emitted->vouchersEmitted;
							$vouchersEmittedInserted = $emitted->vouchersEmittedInserted;
						}

						if(isset($received->vouchersReceived)){
							$vouchersReceived = $received->vouchersReceived;
							$vouchersReceivedInserted = $received->vouchersReceivedInserted;
						}

						return ctr_users::updatedVouchers($vouchersEmitted, $vouchersEmittedInserted, $vouchersReceived, $vouchersReceivedInserted);
					}else return $responseGetInfoBusiness;
				}else return $responseGetBusiness;
			}else{
				$response->result = 2;
			}
		}else return $responseGetUserSession;

		return $response;
	}
	//UPDATED
	public function getVoucherCFE($idVoucher, $prepareFor, $formatFile, $currentSession){
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();
		$restController = new ctr_rest();
		$vouchersReceivedClass = new vouchersReceived();
		$provController = new ctr_providers();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// $responseGetUser = ctr_users::getUserInSesion();
		// if($responseGetUser->result == 2){
			if(strcmp($prepareFor, "CLIENT") == 0){
				$responseGetVoucherClient = $vouchersEmittedClass->getVoucherEmitted($idVoucher, $currentSession->idEmpresa);
				if($responseGetVoucherClient->result == 2){
					$voucherClient = $responseGetVoucherClient->objectResult;
					$responseRest = $restController->consultarCFE($currentSession->rut, null, $voucherClient->tipoCFE, $voucherClient->serieCFE, $voucherClient->numeroCFE, $formatFile, $currentSession->tokenRest);
					if($responseRest->result == 2){
						$response->result = 2;
						$response->voucherCFE = $responseRest->cfe;
					}else return $responseRest;
				}else return $responseGetVoucherClient;
			}else if(strcmp($prepareFor, "PROVIDER") == 0){
				$responseGetVoucherProvider = $vouchersReceivedClass->getVoucherReceived($idVoucher, $currentSession->idEmpresa);
				if($responseGetVoucherProvider->result == 2){
					$responseGetProvider = $provController->getProvider($responseGetVoucherProvider->objectResult->idProveedor, $currentSession->idEmpresa);
					if($responseGetProvider->result == 2){
						$voucherProvider = $responseGetVoucherProvider->objectResult;
						$responseRest = $restController->consultarCFE($currentSession->rut, $responseGetProvider->provider->rut, $voucherProvider->tipoCFE, $voucherProvider->serieCFE, $voucherProvider->numeroCFE, $formatFile, $currentSession->tokenRest);
						if($responseRest->result == 2){
							$response->result = 2;
							$response->voucherCFE = $responseRest->cfe;
						}else return $responseRest;
					}else return $responseGetProvider;
				}else return $responseGetVoucherProvider;
			}else{
				$response->resutl = 0;
				$response->message = "No se especifico el origen del comprobante que desea abrir.";
			}
		// }else return $responseGetUser;
		// }else return $responseGetBusiness;

		return $response;
	}

	//1 para clientes 2 para proveedores
	public function insertReceiptHistory($document, $numTable, $action, $total, $dateReceipt, $typeCoin, $currentSession){
		$handleDateTimeClass = new handleDateTime();
		$vouchersEmittedClass = new vouchersEmitted();

		$dateInsertInt = $handleDateTimeClass->getDateTimeNowInt();

		// $responseGetUser = ctr_users::getUserInSesion();
		// if(!is_null($responseGetUser)){
		// if($responseGetUser->result == 2){
			// $responseGetBusiness = ctr_users::getBusinesSession();
			// if($responseGetBusiness->result == 2){
		$vouchersEmittedClass->insertReceiptHistory($dateInsertInt, $currentSession->idUser, $numTable, $action, $total, $dateReceipt, $typeCoin, $document, $currentSession->idEmpresa);
			// }
		// }
		// }
	}

	//genera un estado de cuenta pdf para descargar .
	//UPDATED
	public function exportAccountState($addressee, $dateInitINT, $dateEndingINT, $accountState, $prepareFor, $currentSession){
		$userController = new ctr_users();
		$handleDateTimeClass = new handleDateTime();
		$managmentPdfClass = new managment_pdf();
		$response = new \stdClass();

		if(!is_null($accountState)){
			$variableShowBalance = $userController->getVariableConfiguration("VER_SALDOS_ESTADO_CUENTA_PDF", $currentSession);
			if($variableShowBalance->result == 2){
				if($variableShowBalance->configValue == "NO"){
					unset($accountState["BALANCEUSD"]);
					unset($accountState["BALANCEUYU"]);
				}
				$resultPDF = $managmentPdfClass->generateAccountState($accountState, $addressee, $handleDateTimeClass->setFormatBarDate($dateInitINT), $handleDateTimeClass->setFormatBarDate($dateEndingINT), $prepareFor, $currentSession);
				if(!is_null($resultPDF)){
					$response->result = 2;
					$response->fileGenerate = $resultPDF;
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y el archivo no fue generado, vuelva a intentarlo.";
				}
			}else return $variableShowBalance;
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y no pudo generarse el estado de cuenta del cliente.";
		}

		return $response;
	}

	//guarda el ultimo estado de cuenta generado para sugererirlo si abre el modal nuevamente
	//[OK] MODIFICA LA SESSION PERO NO IMPORTARIA POR AHORA
	public function saveInfoAccountStateTemp($idPerson, $document, $dateInit, $dateEnding, $typeCoin, $config, $prepareFor){
		$response = new \stdClass();
		if(isset($_SESSION['systemSession'])){
			$sesion = $_SESSION['systemSession'];

			$lastInfo = new \stdClass();
			$lastInfo->idPerson = $idPerson;
			$lastInfo->document = $document;
			$lastInfo->dateInit = $dateInit;
			$lastInfo->dateEnding = $dateEnding;
			$lastInfo->selectedCoin = $typeCoin;

			if($prepareFor == "CLIENT"){
				$lastInfo->includeCashCollection = $config;
				if($document)
					$sesion->accountStateClient = $lastInfo;
				else
					$sesion->accountStateClient = null;
			}else if($prepareFor == "PROVIDER"){
				if($document)
					$sesion->accountStateProvider = $lastInfo;
				else $sesion->accountStateProvider = null;
			}

			$_SESSION['systemSession'] = $sesion;
		}else {
			$response->result = 0;
			$response->message = "Actualmente no hay sesión";
		}
	}
	//UPDATED
	public function createCreditNoteToDiscount($documentClient, $dateVoucher, $typeCoin, $inputAmount, $details, $discount, $currentSession){
		$response = new \stdClass();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$userController = new ctr_users();
		$voucherController = new ctr_vouchers();
		$voucherEmittedController = new ctr_vouchers_emitted();
		$validateClass = new validate();
		$vouchersEmittedClass = new vouchersEmitted();
		$dateClass = new handleDateTime();
		$utilsClass = new utils();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// 	$responseInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
		// 	if($responseInfoBusiness->result == 2){
			
		$objClient = $clientController->findClientWithDoc($documentClient, $currentSession->idEmpresa);
		$isRut = $validateClass->validateRut($objClient->client->docReceptor);
		
		if( $isRut->result == 2 ){
			$typeVoucher = 112;
		}else{
			$typeVoucher = 102;
		}

		$responseGetGrossAmount = $userController->getVariableConfiguration("IVA_INCLUIDO", $currentSession);
		if($responseGetGrossAmount->result == 2){
			$grossAmount = 0;
			if(strcmp($responseGetGrossAmount->configValue, "SI") == 0)
				$grossAmount = 1;
		}

		$exchangeRate = null;
		if(strcmp($typeCoin, "UYU") != 0){
			$responseExchange = $voucherController->getQuote($typeCoin, $dateVoucher);
			if($responseExchange->result == 2)
				$exchangeRate = $responseExchange->currentQuote;
			else return $responseExchange;
		}

		$branchCompany = null;
		$responseGetGrossAmount = $userController->getVariableConfiguration("SUCURSAL_IS_PRINCIPAL", $currentSession);
		if ($responseGetGrossAmount->result == 2){
			$branchCompany = $responseGetGrossAmount->configValue;
		}

		$shapePayment = 2;
		$arrayDetails = array();
		$arrayReference = array();
		$vouchersSelected = explode(",", $details); //todos los ids de las facturas seleccionadas
		for ($i=0; $i < count($vouchersSelected) ; $i++) {
			$voucherDetail = $vouchersEmittedClass->getVoucherEmitted($vouchersSelected[$i], $currentSession->idEmpresa);
			//se prepara un array referencias con todas los comprobantes que se seleccionaron para crear el recibo
			$arrayReference [] = $restController->prepareReferenciasToSend($voucherDetail->objectResult->tipoCFE, $voucherDetail->objectResult->serieCFE, $voucherDetail->objectResult->numeroCFE, null, null);

			//detalles de las facturas referenciadas
			$responseRest = $restController->consultarCFE($currentSession->rut, null, $voucherDetail->objectResult->tipoCFE, $voucherDetail->objectResult->serieCFE, $voucherDetail->objectResult->numeroCFE, "application/json", $currentSession->tokenRest);
			//busco todos los datos de esa factura para sacar el indice de facturacion
			if($responseRest->result == 2){

				$jsonPrintFormat = json_decode($responseRest->cfe->representacionImpresa); //datos de los productos
				foreach ($jsonPrintFormat->detalles as $key => $itemDetail) {
					$arrayDetails[] = $restController->prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio);
				}
			}

			//metodo de pago
			if ( $voucherDetail->objectResult->formaPago != $shapePayment )
				$shapePayment = $voucherDetail->objectResult->formaPago;
		}
		$inputAmount = (($inputAmount * $discount) / 100);
		$detalleCreditNote = $voucherController->prepareItemsToCreditNoteDiscount($inputAmount, $arrayDetails, $discount);
		$dateVoucherINT = $dateClass->getDateInt($dateVoucher);

		$receiver = $utilsClass->convertObjectClientToReceiver($objClient->client);

		return $restController->nuevoCFE($currentSession->rut, $typeVoucher, $dateVoucherINT, $grossAmount, $shapePayment , null, $typeCoin, $exchangeRate, $detalleCreditNote, $receiver , 0, $arrayReference, null, $branchCompany, null, null, $currentSession->tokenRest);
		// 	}else return $responseInfoBusiness;
		// }else return $responseGetBusiness;

		return $response;
	}

	public function prepareItemsToCreditNoteDiscount($inputAmount, $arrayDetails, $discount){
		$othersClass = new others();

        $responseArray = array();
        $listInvoiceInd = $othersClass->getListIva();
        $totalAllInvoiceSelected = 0;
        foreach( $arrayDetails as $key => $value){
        	$totalAllInvoiceSelected += $value['precio'];
        }

        foreach( $arrayDetails as $key => $value){
        	$price = ($inputAmount * $value['precio']) / $totalAllInvoiceSelected;

        	//$price = ($value['precio'] * $discount)/100;


        	if ( array_search($value['indFact'], array_column($responseArray, 'indFact')) === false ){
        		$idImpuesto = array_search($value['indFact'], array_column($listInvoiceInd->listResult, 'id'));
        		$responseArray [] = array(

		            "indFact" => $value['indFact'],
		            "nomItem" => "Descuento por el pago ".$listInvoiceInd->listResult[$idImpuesto]['nombre'],
		            "codItem" => "",
		            "descItem" => "",
		            "cantidad" => 1,
		            "uniMedida" => "UNI",
		            "precio" => $price
	        	);
        	}
        	else{
        		for ($i=0; $i < count($responseArray) ; $i++) {
        			if( $responseArray[$i]['indFact'] == $value['indFact'] ){
        				$responseArray[$i]['precio'] += $price;
        			}
        		}
        	}
        }
        return $responseArray;
	}
	//UPDATED
	public function getExcelAccountSate($entity, $idEntity, $dateInit, $dateFinish, $typeCoin, $config, $currentSession){ //client o provider, 123, , , UYU,

		$response = new \stdClass();
		$resultAccountState = null;
		$vouchersEmittedClass = new vouchersEmitted();
		$vouchersReceivedClass = new vouchersReceived();
		$spreadsheetClass = new managment_spreadsheet();
		$clientController = new ctr_clients();
		$provController = new ctr_providers();
		$userController = new ctr_users();
		$dateClass = new handleDateTime();

		$dateInitINT = $dateClass->getDateInt($dateInit);
		$dateFinishINT = $dateClass->getDateInt($dateFinish);

		$dateInitBar = $dateClass->setFormatBarDate($dateInitINT);
		$dateFinishBar = $dateClass->setFormatBarDate($dateFinishINT);

		// $responseGetBusiness = $userController->getBusinesSession();
		// if($responseGetBusiness->result == 2){

		$idBusiness = $currentSession->idEmpresa;
		$nameBusiness = $currentSession->empresa;

		if ( $entity == "CLIENT" ){
			$resultAccountState = $vouchersEmittedClass->getAccountState($idEntity, $dateInitINT, $dateFinishINT, $typeCoin, $idBusiness, $config);
			if ( $resultAccountState->result != 0 ){
				$resultGetClient = $clientController->getClientWithId($idEntity);//obtengo todos los datos de ese cliente a partir de ese id
				if ( $resultGetClient->result == 2 ) {
					$accountState = $resultAccountState->listResult;
					$resultExcel = $spreadsheetClass->accountState($accountState, $resultGetClient->client->docReceptor, $resultGetClient->client->nombreReceptor, $dateInitBar, $dateFinishBar, $entity, $idBusiness, $nameBusiness);
					$response->result = 2;
					$response->name = $resultExcel;
					return $response;
				}else return $resultGetClient;
			}else return $resultAccountState;
		}
		elseif ( $entity == "PROVIDER" ) {
			$resultProvAccountState = $vouchersReceivedClass->getAccountState($idEntity, $dateInitINT, $dateFinishINT, $typeCoin, $idBusiness);
			if ($resultProvAccountState->result != 0){
				$resultGetProv = $provController->getProvider($idEntity, $idBusiness);
				if ( $resultGetProv->result == 2 ) {
					$accountState = $resultProvAccountState->listResult;
					$resultExcel = $spreadsheetClass->accountState($accountState, $resultGetProv->provider->rut, $resultGetProv->provider->razonSocial, $dateInitBar, $dateFinishBar, $entity, $idBusiness, $nameBusiness);
					$response->result = 2;
					$response->name = $resultExcel;
					return $response;
				}else return $resultGetClient;
			}
		}
		// }else return $responseGetBusiness;
	}


	//UPDATED // VER VER VER ACA ACA ACA
	public function getListVouchers( $dateinit, $datefinish, $prepareFor, $typeVoucher, $lastId, $limit, $isCobranza, $clientDocument, $currentSession ){

		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();
		$vouchersReceivedClass = new vouchersReceived();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$utilsClass = new utils();

		//isCobranza 1 incluir recibos, 0 sin recibos


		// $idBusiness = $_SESSION['systemSession']->idBusiness;

		$dateinit = str_replace("-", "", $dateinit);
		$datefinish = str_replace("-", "", $datefinish);

		if ( $prepareFor == "CLIENT" ){

			if ( $lastId == 0 ){
				$lastId = $vouchersEmittedClass->getMaxIdVouchers($currentSession->idEmpresa);
			}

			$idClient = "";
			if ( isset($clientDocument) && $clientDocument != "" ){
				//buscar id cliente segun el documento

				$client = $clientController->findClientWithDoc($clientDocument, $currentSession->idEmpresa);
				if($client->result == 2){
					$idClient = $client->client->id;
				}
			}


			if ( !isset($isCobranza) ) $isCobranza = "";

			$resultVouchers = $vouchersEmittedClass->getListVouchersEmitted( $dateinit, $datefinish, $idClient, $typeVoucher, $isCobranza, $lastId, $currentSession->idEmpresa, $limit );

			if ( $resultVouchers->result == 2 ){
				$newLastId = $lastId;
				foreach ($resultVouchers->listResult as $key => $value) {
					if($newLastId > $value['id'])
						$newLastId = $value['id'];

					$responseRest = $restController->consultarCFE($currentSession->rut, null, $value['tipoCFE'], $value['serieCFE'], $value['numeroCFE'], "application/json", $currentSession->tokenRest);

					if ( $responseRest->result == 2 ){

						$arrayDetail = array();
						$info = json_decode($responseRest->cfe->representacionImpresa);
						foreach ($info->detalles as $item) {
							$cantidad = $item->cantidad;
							if ( $info->tipoCFE == 112 || $info->tipoCFE == 102 ){
								$cantidad = ($cantidad * -1);
							}

							if ( $info->tipoMoneda != "USD" ){
								$arrayDetail[] = array(
									'nomItem'=>$item->nomItem,
									'indFact'=>$item->indFact,
									'precio'=>$item->precio,
									'cantidad'=>$cantidad,
									'codItem'=>$item->codItem,
									'total'=>($cantidad * $item->precio),
									'tipoCambio'=>1,
									'totalusd'=>0,
									'idCompra'=>isset($info->idCompra) ? $info->idCompra : ""
								);
							}else{
								$arrayDetail[] = array(
									'nomItem'=>$item->nomItem,
									'indFact'=>$item->indFact,
									'precio'=>$item->precio,
									'cantidad'=>$cantidad,
									'codItem'=>$item->codItem,
									'total'=>($cantidad * ($info->tipoCambio*$item->precio)),
									'tipoCambio'=>$info->tipoCambio,
									'totalusd'=>($cantidad * $item->precio),
									'idCompra'=>isset($info->idCompra) ? $info->idCompra : ""
								);
							}
						}
						$resultVouchers->listResult[$key]['detalles'] = $arrayDetail;
					}

					$resultVouchers->listResult[$key]['voucher'] = $utilsClass->getNameVoucher($value['tipoCFE'], $value['isCobranza']);

					if ( isset($value['idCliente']) && $value['idCliente'] != "" ){
						$resultGetClient = $clientController->getClientWithId($value['idCliente']);
						if ( $resultGetClient->result == 2 ){
							$resultVouchers->listResult[$key]['docClient'] = $resultGetClient->client->docReceptor;
							$resultVouchers->listResult[$key]['nombreCliente'] = $resultGetClient->client->nombreReceptor;
						}
						else{
							$resultVouchers->listResult[$key]['docClient'] = "";
							$resultVouchers->listResult[$key]['nombreCliente'] = "";
						}
					}else{
						$resultVouchers->listResult[$key]['docClient'] = "";
						$resultVouchers->listResult[$key]['nombreCliente'] = "";
					}

					if ( strlen($value['fecha']) >0 )
						$resultVouchers->listResult[$key]['fecha'] = substr($value['fecha'],0,4)."-".substr($value['fecha'],4,2)."-".substr($value['fecha'],6,2);

					if ( strlen($value['fechaHoraEmision']) >0 ) //20220819171759 => 2022-07-01 11:39:00
						$resultVouchers->listResult[$key]['fechaHoraEmision'] = substr($value['fechaHoraEmision'],0,4)."-".substr($value['fechaHoraEmision'],4,2)."-".substr($value['fechaHoraEmision'],6,2)." ".substr($value['fechaHoraEmision'],8,2).":".substr($value['fechaHoraEmision'],10,2)." ".substr($value['fechaHoraEmision'],12,2);


					$resultVouchers->listResult[$key]['businessrut'] = $currentSession->rut;
					$resultVouchers->listResult[$key]['business'] = $currentSession->idEmpresa;
				}

				$resultVouchers->lastId = $newLastId;


			}else{
				$resultVouchers->listResult = array();
			}

			//var_dump($resultVouchers);exit;

			return $resultVouchers;
		}
	}
}