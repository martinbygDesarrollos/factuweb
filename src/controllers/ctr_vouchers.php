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
	public function getIVAsAllowed(){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetConfig = ctr_users::getVariableConfiguration("INDICADORES_FACTURACION_USABLES");
			if($responseGetConfig->result == 2){
				$listIVA = explode(",", $responseGetConfig->configValue);//en configValue obtenemos los ids de los indicadores_facturacion a los que se tienen permisos
				$arrayResult = array();
				for($i = 0; $i < sizeof($listIVA); $i ++) {
					$responseGetIVA = others::getValueIVA($listIVA[$i]);//$responseGetIVA tenemos toda la informaciòn de un indicadores_facturacion segùn el id
					if($responseGetIVA->result == 2){
						$newRow = array("idIVA" => $responseGetIVA->objectResult->id, "nombre" => $responseGetIVA->objectResult->nombre, "valor" => number_format($responseGetIVA->objectResult->valor,2,",","."));
						$arrayResult[] = $newRow;
					}
				}
				$response->result = 2;
				$response->listResult = $arrayResult;
			}else return $responseGetConfig;
		}else return $responseGetBusiness;

		return $response;
	}

	public function consultCaes($typeCFE){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetCaes = ctr_rest::consultarCaes($responseGetBusiness->infoBusiness->rut);
			if($responseGetCaes->result == 2){
				foreach ($responseGetCaes->caes->caes as $key => $cae) {
					if(strcmp($cae->cfeType, $typeCFE)){
						if($cae->isUsable == true){
							$response->result = 2;
							$response->message ="USABLE";
						}else{
							$response->result = 0;
							$response->message = "Actualmente no puede emitir " . utils::getNameVoucher($typeCFE,0) . ".";
						}
						break;
					}
				}
				if(!isset($response->result)){
					$response->result = 0;
					$response->message = "No se encontro información para CFEs de tipo: " . utils::getNameVoucher($typeCFE,0) . " entre los CAEs disponibles.";
				}
			}else return $responseGetCaes;
		}else return $responseGetBusiness;

		return $response;
	}

	//crea un comprobante para enviarlo a ormen luego de que se envia y es aceptado el comproabnte se registra en el sistema. ctr_rest es la comunicacion con ormen
	public function createNewVoucher($objClient, $typeVoucher, $typeCoin, $shapePayment, $dateVoucher, $dateExpiration, $adenda, $listDetail, $idBuy){
		$response = new \stdClass();
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$receiver = null;
			if(count($objClient) != 0){
				$responseIsValid = ctr_clients::isValidForEmit($typeVoucher, $objClient[0]['document']);
				if($responseIsValid->result == 2){
					$receiver = ctr_rest::prepareReceptorToSend($objClient[0]['document'], $objClient[0]['name'], $objClient[0]['address'], $objClient[0]['city'], $objClient[0]["department"], "Uruguay");
				}else return $responseIsValid;
			}

			$arrayDetail = array();
			//var_dump($listDetail);
			//exit;

			foreach ($listDetail as $key => $detail){
				$iva = 1;
				if ($detail['idIva'] > 0 || $detail['idIva'] != null || $detail['idIva'] != ""){
					$iva = $detail['idIva'];
				}
				$arrayDetail[] = ctr_rest::prepareDetalleToSend($detail['idIva'], $detail['description'], null, $detail['detail'], $detail['count'], null, $detail['price']);
			}

			$responseGetGrossAmount = ctr_users::getVariableConfiguration("IVA_INCLUIDO");
			if($responseGetGrossAmount->result == 2){
				$grossAmount = 0;
				if(strcmp($responseGetGrossAmount->configValue, "SI") == 0)
					$grossAmount = 1;

				$branchCompany = null;
				$responseGetGrossAmount = ctr_users::getVariableConfiguration("SUCURSAL_IS_PRINCIPAL");
				if ($responseGetGrossAmount->result == 2){
					$branchCompany = $responseGetGrossAmount->configValue;
				}


				$responseCreateCFE = ctr_vouchers::createNewCFE($typeVoucher, $dateVoucher, $grossAmount, $shapePayment, $dateExpiration, $typeCoin, $arrayDetail, $receiver, 0, null, $adenda, $branchCompany, $idBuy);
				if($responseCreateCFE->result == 2){
					//$responseGetVouchers = ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetBusiness->infoBusiness->rut);
					$lastVoucher = ctr_vouchers_emitted::getLastVoucherEmitted();
					if ( $lastVoucher->result == 2 )
						$lastVoucherId = $lastVoucher->objectResult->id;
					else $lastVoucherId;

					$responseGetVouchers = ctr_vouchers_emitted::getVouchersEmittedFromRest($responseGetBusiness->infoBusiness->rut, 1, $lastVoucherId, null, null);
					if($responseGetVouchers->result == 2){
						$response->result = 2;
						$response->message = "El comprobante fue emitido correctamente y ya se encuentra en el sistema.";
					}else{
						$response->result = 1;
						$response->message = "El comprobante fue emitido correctamente pero un error no permitio traerlo al sistema. Actualice los comprobantes almacenados para obtenerlo.";
					}
				}else return $responseCreateCFE;
			}else return $responseGetGrossAmount;

		}else return $responseGetBusiness;

		return $response;
	}

	//carga todos los articulos facturados en comprobantes emitidos como articulos para la lista de precios y para sugerencia en venta.
	public function loadProductsFromDetails(){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			if($responseGetBusiness->infoBusiness->detallesObtenidos == 0){
				ctr_products::insertHeading("Artículos");
				$responseInsertDetail = ctr_vouchers::getVouchersEmittedForLoadDetails($responseGetBusiness->idBusiness, $responseGetBusiness->infoBusiness->rut, 200, null);
				if(isset($responseInsertDetail->inserted) && isset($responseInsertDetail->toInsert)){
					$responseSetUpdatedDetails = ctr_users::setUpdatedDetails($responseGetBusiness->idBusiness);
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
		}else return $responseGetBusiness;

		return $response;
	}

	public function getVouchersEmittedForLoadDetails($idBusiness, $rut, $pageSize, $lastId){
		$response = new \stdClass();
		$toInsert = 0;
		$inserted =  0;

		$responseSendRest = ctr_rest::listarEmitidos($rut, $pageSize, $lastId, null,null, null);
		if($responseSendRest->result == 2){
			foreach ($responseSendRest->listEmitidos as $key => $voucher) {
				if($voucher->tipoCFE == 101 || $voucher->tipoCFE == 111 && ($voucher->isCobranza == 0 && $voucher->isAnulado == 0)){
					$responseInsertDetail = ctr_products::getVoucherDetailJSON($idBusiness, $rut, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE);
					if(isset($responseInsertDetail->toInsert)){
						$toInsert += $responseInsertDetail->toInsert;
						$inserted += $responseInsertDetail->inserted;
					}
				}
				$lastId = $voucher->id;
			}
			if(sizeof($responseSendRest->listEmitidos) == 200){
				$responseRecursive = ctr_vouchers::getVouchersEmittedForLoadDetails($idBusiness, $rut, $pageSize, $lastId);
				$toInsert += $responseRecursive->toInsert;
				$inserted += $responseRecursive->inserted;
			}
		}else return $responseSendRest;

		$response->toInsert = $toInsert;
		$response->inserted = $inserted;

		return $response;
	}

	public function exportCFEs($prepareFor, $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $typeVoucher){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
			if($responseGetInfoBusiness->result == 2){
				$dateTo = handleDateTime::getDateInt($dateTo) . "000000";
				$dateFrom = handleDateTime::getDateInt($dateFrom) . "000000";
				return ctr_rest::exportacion($responseGetInfoBusiness->objectResult->rut, $prepareFor, "application%2Fvnd.ms-excel", $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $typeVoucher);
			}else return $responseGetInfoBusiness;
		}else return $responseGetBusiness;

		return $response;
	}

	public function createNewCFE($typeCFE, $dateVoucher, $grossAmount, $typePay, $dateExpiration, $typeCoin, $detail, $receiver, $indCollection, $reference, $appendix, $branchCompany, $idBuy){
		$response = new \stdClass();
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
			if($responseInfoBusiness->result == 2){

				$exchangeRate = null;
				if(strcmp($typeCoin, "UYU") != 0){
					$responseExchange = ctr_vouchers::getQuote($typeCoin, $dateVoucher);
					if($responseExchange->result == 2)
						$exchangeRate = $responseExchange->currentQuote;
					else return $responseExchange;
				}
				$dateVoucherINT = handleDateTime::getDateInt($dateVoucher);
				if(!is_null($dateExpiration))
					$dateExpiration = handleDateTime::getDateInt($dateExpiration);

				return ctr_rest::nuevoCFE($responseInfoBusiness->objectResult->rut, $typeCFE, $dateVoucherINT, $grossAmount, $typePay, $dateExpiration, $typeCoin, $exchangeRate, $detail, $receiver, $indCollection, $reference, $appendix, $branchCompany, $idBuy);
			}else return $responseInfoBusiness;
		}else return $responseGetBusiness;

		return $response;
	}

	//obtiene la cotizacion de una moneda ingresada
	public function getQuote($typeCoin, $dateQuote){

		if(is_null($dateQuote) || strlen($dateQuote) < 4)
			$dateQuote = date('Y-m-d');
		return ctr_rest::obtenerCotizacion($dateQuote, $dateQuote, $typeCoin);
	}


	//obtiene la cotizacion de varias monedas
	public function getQuotes(){
		$response = new \stdClass();

		$currentDate = date('Y-m-d');

		$responseRestUSD = ctr_rest::obtenerCotizacion($currentDate, $currentDate, "USD");
		if($responseRestUSD->result == 2)
			$response->USD =  bcdiv($responseRestUSD->currentQuote, '1', 4);

		$responseRestUI = ctr_rest::obtenerCotizacion($currentDate, $currentDate, "UI");
		if($responseRestUI->result == 2)
			$response->UI =  bcdiv($responseRestUI->currentQuote, '1', 4);

		$responseRestEUR = ctr_rest::obtenerCotizacion($currentDate, $currentDate, "EUR");
		if($responseRestEUR->result == 2)
			$response->EUR = bcdiv($responseRestEUR->currentQuote, '1', 4);

		return $response;
	}

	public function parceDateFormat($dateInit, $dateEnding){
		$dateInitINT = handleDateTime::getDateInt($dateInit);
		$dateEndingINT = handleDateTime::getDateInt($dateEnding);

		return handleDateTime::setFormatBarDate($dateInitINT) . " al " . handleDateTime::setFormatBarDate($dateEndingINT);
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

	public function getVoucherCFE($idVoucher, $prepareFor, $formatFile){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetUser = ctr_users::getUserInSesion();
			if($responseGetUser->result == 2){
				if(strcmp($prepareFor, "CLIENT") == 0){
					$responseGetVoucherClient = vouchersEmitted::getVoucherEmitted($idVoucher, $responseGetBusiness->idBusiness);
					if($responseGetVoucherClient->result == 2){
						$voucherClient = $responseGetVoucherClient->objectResult;
						$responseRest = ctr_rest::consultarCFE($responseGetUser->objectResult->rut, null, $voucherClient->tipoCFE, $voucherClient->serieCFE, $voucherClient->numeroCFE, $formatFile);
						if($responseRest->result == 2){
							$response->result = 2;
							$response->voucherCFE = $responseRest->cfe;
						}else return $responseRest;
					}else return $responseGetVoucherClient;
				}else if(strcmp($prepareFor, "PROVIDER") == 0){
					$responseGetVoucherProvider = vouchersReceived::getVoucherReceived($idVoucher, $responseGetBusiness->idBusiness);
					if($responseGetVoucherProvider->result == 2){
						$responseGetProvider = ctr_providers::getProvider($responseGetVoucherProvider->objectResult->idProveedor);
						if($responseGetProvider->result == 2){
							$voucherProvider = $responseGetVoucherProvider->objectResult;
							$responseRest = ctr_rest::consultarCFE($responseGetUser->objectResult->rut, $responseGetProvider->provider->rut, $voucherProvider->tipoCFE, $voucherProvider->serieCFE, $voucherProvider->numeroCFE, $formatFile);
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
			}else return $responseGetUser;
		}else return $responseGetBusiness;

		return $response;
	}

	//1 para clientes 2 para proveedores
	public function insertReceiptHistory($document, $numTable, $action, $total, $dateReceipt, $typeCoin){
		$dateInsertInt = handleDateTime::getDateTimeNowInt();

		$responseGetUser = ctr_users::getUserInSesion();
		if(!is_null($responseGetUser)){
			if($responseGetUser->result == 2){
				$responseGetBusiness = ctr_users::getBusinesSession();
				if($responseGetBusiness->result == 2){
					vouchersEmitted::insertReceiptHistory($dateInsertInt, $responseGetUser->objectResult->idUsuario, $numTable, $action, $total, $dateReceipt, $typeCoin, $document,$responseGetBusiness->idBusiness);
				}
			}
		}
	}

	//genera un estado de cuenta pdf para descargar .
	public function exportAccountState($addressee, $dateInitINT, $dateEndingINT, $accountState, $prepareFor, $myBusiness){
		$response = new \stdClass();

		if(!is_null($accountState)){
			$variableShowBalance = ctr_users::getVariableConfiguration("VER_SALDOS_ESTADO_CUENTA_PDF");
			if($variableShowBalance->result == 2){
				if($variableShowBalance->configValue == "NO"){
					unset($accountState["BALANCEUSD"]);
					unset($accountState["BALANCEUYU"]);
				}
				$resultPDF = managment_pdf::generateAccountState($accountState, $addressee, handleDateTime::setFormatBarDate($dateInitINT), handleDateTime::setFormatBarDate($dateEndingINT), $prepareFor, $myBusiness);
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

	public function createCreditNoteToDiscount($documentClient, $dateVoucher, $typeCoin, $inputAmount, $details, $discount){

		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
			if($responseInfoBusiness->result == 2){
				$objClient = ctr_clients::findClientWithDoc($documentClient);
				$isRut = validate::validateRUT($objClient->client->docReceptor);
				if( $isRut->result == 2 ){
					$typeVoucher = 112;
				}else{
					$typeVoucher = 102;
				}

				$responseGetGrossAmount = ctr_users::getVariableConfiguration("IVA_INCLUIDO");
				if($responseGetGrossAmount->result == 2){
					$grossAmount = 0;
					if(strcmp($responseGetGrossAmount->configValue, "SI") == 0)
						$grossAmount = 1;
				}

				$exchangeRate = null;
				if(strcmp($typeCoin, "UYU") != 0){
					$responseExchange = ctr_vouchers::getQuote($typeCoin, $dateVoucher);
					if($responseExchange->result == 2)
						$exchangeRate = $responseExchange->currentQuote;
					else return $responseExchange;
				}

				$shapePayment = 2;
				$arrayDetails = array();
				$arrayReference = array();
				$vouchersSelected = explode(",", $details); //todos los ids de las facturas seleccionadas
				for ($i=0; $i < count($vouchersSelected) ; $i++) {
					$voucherDetail = vouchersEmitted::getVoucherEmitted($vouchersSelected[$i], $responseInfoBusiness->objectResult->idEmpresa);
					//se prepara un array referencias con todas los comprobantes que se seleccionaron para crear el recibo
					$arrayReference [] = ctr_rest::prepareReferenciasToSend($voucherDetail->objectResult->tipoCFE, $voucherDetail->objectResult->serieCFE, $voucherDetail->objectResult->numeroCFE, null, null);

					//detalles de las facturas referenciadas
					$responseRest = ctr_rest::consultarCFE($responseInfoBusiness->objectResult->rut, null, $voucherDetail->objectResult->tipoCFE, $voucherDetail->objectResult->serieCFE, $voucherDetail->objectResult->numeroCFE, "application/json");
					//busco todos los datos de esa factura para sacar el indice de facturacion
					if($responseRest->result == 2){

						$jsonPrintFormat = json_decode($responseRest->cfe->representacionImpresa); //datos de los productos
						foreach ($jsonPrintFormat->detalles as $key => $itemDetail) {
							$arrayDetails[] = ctr_rest::prepareDetalleToSend($itemDetail->indFact, $itemDetail->nomItem, $itemDetail->codItem, $itemDetail->descripcion, $itemDetail->cantidad, $itemDetail->uniMedida, $itemDetail->precio);
						}
					}

					//metodo de pago
					if ( $voucherDetail->objectResult->formaPago != $shapePayment )
						$shapePayment = $voucherDetail->objectResult->formaPago;
				}
				$inputAmount = (($inputAmount * $discount) / 100);
				$detalleCreditNote = ctr_vouchers::prepareItemsToCreditNoteDiscount($inputAmount, $arrayDetails, $discount);
				$dateVoucherINT = handleDateTime::getDateInt($dateVoucher);

				$receiver = utils::convertObjectClientToReceiver($objClient->client);

				return ctr_rest::nuevoCFE($responseInfoBusiness->objectResult->rut, $typeVoucher, $dateVoucherINT, $grossAmount, $shapePayment , null, $typeCoin, $exchangeRate, $detalleCreditNote, $receiver , 0, $arrayReference, null, null, null);
			}else return $responseInfoBusiness;
		}else return $responseGetBusiness;

		return $response;
	}

	public function prepareItemsToCreditNoteDiscount($inputAmount, $arrayDetails, $discount){

        $responseArray = array();
        $listInvoiceInd = others::getListIva();
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

	public function getExcelAccountSate($entity, $idEntity, $dateInit, $dateFinish, $typeCoin, $config){ //client o provider, 123, , , UYU,

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

		$responseGetBusiness = $userController->getBusinesSession();
		if($responseGetBusiness->result == 2){

			$idBusiness = $responseGetBusiness->idBusiness;
			$nameBusiness = $responseGetBusiness->infoBusiness->nombre;

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
					$resultGetProv = $provController->getProvider($idEntity);
					if ( $resultGetProv->result == 2 ) {
						$accountState = $resultProvAccountState->listResult;
						$resultExcel = $spreadsheetClass->accountState($accountState, $resultGetProv->provider->rut, $resultGetProv->provider->razonSocial, $dateInitBar, $dateFinishBar, $entity, $idBusiness, $nameBusiness);
						$response->result = 2;
						$response->name = $resultExcel;
						return $response;
					}else return $resultGetClient;
				}
			}
		}else return $responseGetBusiness;
	}



	public function getListVouchers( $dateinit, $datefinish, $prepareFor, $typeVoucher, $lastId, $limit, $isCobranza, $clientDocument ){

		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();
		$vouchersReceivedClass = new vouchersReceived();
		$clientController = new ctr_clients();
		$restController = new ctr_rest();
		$utilsClass = new utils();

		//isCobranza 1 incluir recibos, 0 sin recibos


		$idBusiness = $_SESSION['systemSession']->idBusiness;

		$dateinit = str_replace("-", "", $dateinit);
		$datefinish = str_replace("-", "", $datefinish);

		if ( $prepareFor == "CLIENT" ){

			if ( $lastId == 0 ){
				$lastId = $vouchersEmittedClass->getMaxIdVouchers($idBusiness);
			}

			$idClient = "";
			if ( isset($clientDocument) && $clientDocument != "" ){
				//buscar id cliente segun el documento

				$client = $clientController->findClientWithDoc($clientDocument);
				if($client->result == 2){
					$idClient = $client->client->id;
				}
			}


			if ( !isset($isCobranza) ) $isCobranza = "";

			$resultVouchers = $vouchersEmittedClass->getListVouchersEmitted( $dateinit, $datefinish, $idClient, $typeVoucher, $isCobranza, $lastId, $idBusiness, $limit );

			if ( $resultVouchers->result == 2 ){
				$newLastId = $lastId;
				foreach ($resultVouchers->listResult as $key => $value) {
					if($newLastId > $value['id'])
						$newLastId = $value['id'];

					$responseRest = $restController->consultarCFE($_SESSION['systemSession']->rut, null, $value['tipoCFE'], $value['serieCFE'], $value['numeroCFE'], "application/json");

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
									'idCompra'=>$info->idCompra
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
									'idCompra'=>$info->idCompra
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


					$resultVouchers->listResult[$key]['businessrut'] = $_SESSION['systemSession']->rut;
					$resultVouchers->listResult[$key]['business'] = $_SESSION['systemSession']->business;
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