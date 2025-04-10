<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_vouchers.php';
require_once '../src/controllers/ctr_services.php';
require_once '../src/controllers/ctr_users.php';

return function (App $app){
	$container = $app->getContainer();
	$userClass = new users();
	$voucherController = new ctr_vouchers();
	$vouchEmittedController = new ctr_vouchers_emitted();
	$vouchReceivedController = new ctr_vouchers_received();
	$spreadsheetClass = new managment_spreadsheet();
	$userController = new ctr_users();


	// $app->get('/nueva-venta', function($request, $response, $args)use ($container){
	// 	$responseCurrentSession = ctr_users::validateCurrentSession("VENTAS");
	// 	if($responseCurrentSession->result == 2){
	// 		$args['systemSession'] = $responseCurrentSession->currentSession;
	// 		$responseGetIVA = ctr_vouchers::getIVAsAllowed();
	// 		if($responseGetIVA->result == 2)
	// 			$args['listIVA'] = $responseGetIVA->listResult;

	// 		$responseGetConfigPermitProducts = ctr_users::getVariableConfiguration("PERMITIR_PRODUCTOS_NO_INGRESADOS");
	// 		if($responseGetConfigPermitProducts->result == 2)
	// 			$args['productsNoEntered'] = $responseGetConfigPermitProducts->configValue;
	// 		$responseGetConfigPermitListProducts = ctr_users::getVariableConfiguration("PERMITIR_LISTA_DE_PRECIOS");
	// 		if($responseGetConfigPermitListProducts->result == 2)
	// 			$args['listProducts'] = $responseGetConfigPermitListProducts->configValue;
	// 		$args['adenda'] = "";
	// 		$responseGetConfigAdenda = ctr_users::getVariableConfiguration("ADENDA");
	// 		if($responseGetConfigAdenda->result == 2)
	// 			$args['adenda'] = $responseGetConfigAdenda->configValue;
	// 		$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
	// 		return $this->view->render($response, "sale.twig", $args);
	// 	}else return $response->withRedirect($request->getUri()->getBaseUrl());
	// })->setName('Sale');
	//UPDATED
	$app->get('/nuevo-punto-venta', function($request, $response, $args) use ($container, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			// error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$responseGetIVA = $voucherController->getIVAsAllowed($responseCurrentSession->currentSession);
				if($responseGetIVA->result == 2)
					$args['listIVA'] = $responseGetIVA->listResult;

				$responseGetConfigPermitProducts = $userController->getVariableConfiguration("PERMITIR_PRODUCTOS_NO_INGRESADOS", $responseCurrentSession->currentSession);
				if($responseGetConfigPermitProducts->result == 2)
					$args['productsNoEntered'] = $responseGetConfigPermitProducts->configValue;
				$responseGetConfigPermitListProducts = $userController->getVariableConfiguration("PERMITIR_LISTA_DE_PRECIOS", $responseCurrentSession->currentSession);
				if($responseGetConfigPermitListProducts->result == 2)
					$args['listProducts'] = $responseGetConfigPermitListProducts->configValue;
				$args['adenda'] = "";
				$responseGetConfigAdenda = $userController->getVariableConfiguration("ADENDA", $responseCurrentSession->currentSession);
				error_log($responseGetConfigAdenda->configValue);
				if($responseGetConfigAdenda->result == 2)
					$args['adenda'] = $responseGetConfigAdenda->configValue;
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "pointsale.twig", $args);
			}else return json_encode($responsePermissions);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName('PointSale');
	//UPDATED
	$app->get('/generar-estado-cuenta/{id}/{dateInit}/{dateEnding}/{typeCoin}/{prepareFor}/{config}', function($request, $response, $args) use ($container, $userController, $voucherController, $vouchEmittedController, $vouchReceivedController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;

			$idSelected = $args['id'];//id de la empresa seleccionada
			$dateInit = $args['dateInit'];//fecha de inicio ingresado
			$dateEnding = $args['dateEnding'];//fecha de fin ingresado
			$typeCoin = $args['typeCoin'];//tipo de moneda ingresado
			$prepareFor = $args['prepareFor'];//
			$config = $args['config'];

			$args['prepareFor'] = $prepareFor;
			$args['dateFrom'] = $dateInit;
			$args['dateTo'] = $dateEnding;
			$args['typeCoinSelected'] = $typeCoin;
			$args['dateFromToFromat'] = $voucherController->parceDateFormat($dateInit, $dateEnding);
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;

			//cuando se ingresa al estado de cuenta de un cliente
			if(strcmp($prepareFor, "CLIENT") == 0){
				if(strcmp($typeCoin, "USD") == 0){
					$responseGetQuote = $voucherController->getQuote("USD", null);//usar cotizaciòn
					if($responseGetQuote->result == 2){
						$args['currentQuote'] = $responseGetQuote->currentQuote;
					}
				}

				$resultGetAccountStateClient = $vouchEmittedController->getClientAccountSate($idSelected, $dateInit, $dateEnding, $typeCoin, $config, $responseCurrentSession->currentSession);
				if($resultGetAccountStateClient->result == 2){
					if($resultGetAccountStateClient->resultFile == 2)
						$args['fileAccountSate'] = $resultGetAccountStateClient->fileGenerate;
					else $args['errorFileAccountState'] = $resultGetAccountStateClient->messageFile;

					$args['nameSelected'] = $resultGetAccountStateClient->name;
					$args['documentSelected'] = $resultGetAccountStateClient->documentSelected;
					$args['listAccountState'] = $resultGetAccountStateClient->accountState;

				}else $args['errorMessage'] = $resultGetAccountStateClient->message;
			}else if(strcmp($prepareFor, "PROVIDER") == 0){ 			//cuando se ingresa al estado de cuenta de un proveedor
				$resultGetAccountSatetProvider = $vouchReceivedController->getProviderAccountSate($idSelected, $dateInit, $dateEnding, $typeCoin, $responseCurrentSession->currentSession);
				if($resultGetAccountSatetProvider->result == 2){
					if($resultGetAccountSatetProvider->resultFile == 2)
						$args['fileAccountSate'] = $resultGetAccountSatetProvider->fileGenerate;
					else $args['errorFileAccountState'] = $resultGetAccountSatetProvider->messageFile;

					$args['nameSelected'] = $resultGetAccountSatetProvider->name;
					$args['documentSelected'] = $resultGetAccountSatetProvider->documentSelected;
					$args['listAccountState'] = $resultGetAccountSatetProvider->accountState;

				}else $args['errorMessage'] = $resultGetAccountSatetProvider->message;
			}
			return $this->view->render($response, "accountState.twig", $args);
		}
		return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("AccountState");
	//UPDATED
	$app->post('/createNewVoucher', function(Request $request, Response $response) use ($container, $userClass, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			// error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$objClient = json_decode(stripslashes($data['client']), true);
				$typeVoucher = $data['typeVoucher'];
				$typeCoin = $data['typeCoin'];
				$shapePayment = $data['shapePayment'];
				$dateVoucher = $data['dateVoucher'];
				$dateExpiration = isset($data['dateExpiration']) ? $data['dateExpiration'] : null;
				$adenda = $data['adenda'];
				$idBuy = $data['idBuy'];
				$discountTipo = $data['discountTipo'];
				$mediosPago = isset($data['mediosPago']) ? json_decode($data['mediosPago'], true) : array();
				$listDetail = json_decode($data['detail'],true);
				// var_dump($mediosPago);
				// var_dump($listDetail);
				// exit;
				$idUser = $responseCurrentSession->currentSession->idUser;
				$responseGetFormato = $userClass->getConfigurationUser($idUser, "FORMATO_TICKET");
				if($responseGetFormato->result == 2){
					$ticketFormat = "application/pdf;template=".$responseGetFormato->objectResult->valor;
				}
				// Le agrego al objecto de la sesion el formato del tiket (POR AHORA)
				$responseCurrentSession->currentSession->ticketFormat = $ticketFormat;
				return json_encode($voucherController->createNewVoucher($objClient, $typeVoucher, $typeCoin, $shapePayment, $dateVoucher, $dateExpiration, $adenda, $listDetail, $idBuy, $discountTipo, $mediosPago, $responseCurrentSession->currentSession));
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/loadProductsFromDetails', function(Request $request, Response $response) use ($userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			return json_encode($voucherController->loadProductsFromDetails($responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/consultCaes', function(Request $request, Response $response) use ($userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$typeCFE = $data['typeCFE'];
			return json_encode($voucherController->consultCaes($typeCFE, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/updateDataVouchers', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$callFrom = $data['callFrom'];
			return json_encode(ctr_vouchers::loadVouchers($callFrom));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/updateDataVouchersAdmin', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			return json_encode($userController->updateDataVouchersAdmin($responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});

//EMITIDOS
	//UPDATED
	$app->post('/updateDataVouchersById', function(Request $request, Response $response) use ($userController, $vouchEmittedController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$lastVoucher = $vouchEmittedController->getLastVoucherEmitted($responseCurrentSession->currentSession->idEmpresa);
			if ( $lastVoucher->result == 2 ){
				$objectResult = $lastVoucher->objectResult;
				$id = $objectResult->id;
				$lastDate = $objectResult->fechaHoraEmision;
				$rut = $responseCurrentSession->currentSession->rut;
				return json_encode($vouchEmittedController->getVouchersEmittedFromRest($responseCurrentSession->currentSession, 1, $id, null, $lastDate));
			}
			else{//error al encontrar ultimo comprobante
				return json_encode($lastVoucher);
			}
		}else return json_encode($responseCurrentSession);
	});

//RECIBIDOS
	$app->post('/updateDataReceivedVouchersById', function(Request $request, Response $response) use ($vouchReceivedController) {
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$lastVoucher = $vouchReceivedController->getLastVoucherReceived();
			if ( $lastVoucher->result == 2 ){
				$id = $lastVoucher->objectResult->id;
				$rut = $responseCurrentSession->currentSession->rut;
				return json_encode(ctr_vouchers_received::getVouchersReceivedRest($rut, 20, null));
			}else return json_encode($lastVoucher);
		}else return json_encode($responseCurrentSession);
	});

//actualizar comprobantes desde login linsu
	$app->post('/updateVouchersFromLinsu', function(Request $request, Response $response) use ($vouchEmittedController) {
		$data = $request->getParams();
		$resultsession = users::setNewTokenAndSession($data['id']);
		$lastVoucher = $vouchEmittedController->getLastIdVoucherByRut($data['rut']);
		if ( $lastVoucher->result == 2 ){
			$objectResult = $lastVoucher->objectResult;
			$id = $objectResult->id;
			$lastDate = $objectResult->fechaHoraEmision;
			$rut = $data['rut'];
			$response = json_encode($vouchEmittedController->getVouchersEmittedFromRest($rut,1, $id, null, $lastDate));
			return $response;
		}
		else{//error al encontrar ultimo comprobante
			return json_encode($lastVoucher);
		}
	});
	//UPDATED
	$app->post('/getVoucherCFE', function(Request $request, Response $response) use ($container, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idVoucher = $data['idVoucher'];
			$prepareFor = $data['prepareFor'];
			return json_encode($voucherController->getVoucherCFE($idVoucher, $prepareFor, "text/html;template=A5Vertical", $responseCurrentSession->currentSession)); /*a gusto*/
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getVoucherToExportCFE', function(Request $request, Response $response) use ($userController, $userClass, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idVoucher = $data['idVoucher'];
			$prepareFor = $data['prepareFor'];
			//configuración
			$ticketFormat = "application/pdf;template=a4";
			$idUser = $responseCurrentSession->currentSession->idUser; // no corroboro que la sesion se haya iniciado porque se hace en el ctr_users::validateCurrentSession(null);
			$responseGetFormato = $userClass->getConfigurationUser($idUser, "FORMATO_TICKET");
	    	if($responseGetFormato->result == 2){
				$ticketFormat = "application/pdf;template=".$responseGetFormato->objectResult->valor;
	    	}
			return json_encode($voucherController->getVoucherCFE($idVoucher, $prepareFor, $ticketFormat, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getQuote', function(Request $request, Response $response) use ($userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$typeCoin = $data['typeCoin'];
			$dateQuote = $data['dateQuote'];
			//$dateQuote = null;
			return json_encode($voucherController->getQuote($typeCoin, $dateQuote));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/updateSoldInfo', function(Request $request, Response $response) use ($userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$typeCoin = $data['typeCoin'];
			return json_encode($voucherController->getSoldInfo($typeCoin, $responseCurrentSession->currentSession->idEmpresa));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/exportExcelCFE', function(Request $request, Response $response) use ($userController, $userClass, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$prepareFor = $data['prepareFor'];
			$dateFrom = $data['dateFrom'];
			$dateTo = $data['dateTo'];
			$groupByCurrency = $data['groupByCurrency'];
			$includeReceipts = $data['includeReceipts'];
			$typeVoucher = $data['typeVoucher'];
			return json_encode($voucherController->exportCFEs($prepareFor, $dateFrom, $dateTo, $groupByCurrency, $includeReceipts, $typeVoucher, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/createNewVoucher2', function(Request $request, Response $response) use ($userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			// error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$objClient = $data['documentClient'];
				$typeCoin = $data['typeCoin'];
				$inputAmount = $data['total'];
				$dateVoucher = $data['dateVoucher'];
				$details = $data['idsSelected'];
				$discount = $data['discount'];
				return json_encode($voucherController->createCreditNoteToDiscount($objClient, $dateVoucher, $typeCoin, $inputAmount, $details, $discount, $responseCurrentSession->currentSession));
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/exportAccountStateExcel', function(Request $request, Response $response) use ($userController, $voucherController ){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			// error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$entity = $data['entity'];
				$idEntity = $data['idEntity'];
				$dateInit = $data['init'];
				$dateEnding = $data['finish'];
				$typeCoin = $data['coin'];
				$config = $data['config'];

				$resultExcel = $voucherController->getExcelAccountSate( $entity, $idEntity, $dateInit, $dateEnding, $typeCoin, $config, $responseCurrentSession->currentSession);
				return json_encode($resultExcel);
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/exportCfesVoucherDetails', function($request, $response, $args)use ($userController, $voucherController, $spreadsheetClass){

		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$dateInit = $data['dateInit'];
			$dateFinish = $data['dateFinish'];
			$prepareFor = $data['prepareFor'];
			$typeVoucher = $data['type'];
			$lastId = $data['lastid'];
			$limit = $data['limit'];
			$typeMoney = $data['typeMoney'];
			$receipts = $data['receipts'];
			$client = $data['client'];

			//listado de los comprobantes en un periodo con sus items
			$list = $voucherController->getListVouchers( $dateInit, $dateFinish, $prepareFor, $typeVoucher, $lastId, $limit, $receipts, $client, $responseCurrentSession->currentSession);
			if ( $list->result != 0 ){


				$dateInit = str_replace("-", "", $dateInit);
				$dateFinish = str_replace("-", "", $dateFinish);

				$yearMonth = date("Ym");
				if ( substr($dateInit,0,6) == substr($dateFinish,0,6) ){
					$yearMonth = substr($dateInit,0,6);
				}else
					$yearMonth = substr($dateInit,0,6)."_".substr($dateFinish,0,6);

				$result = $spreadsheetClass->vouchersDetails($list->listResult, $typeMoney, $yearMonth);
				return json_encode($result);
			}else return json_encode($list);
		}else return json_encode($responseCurrentSession);

	});
}

?>