<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_vouchers_received.php';
require_once '../src/controllers/ctr_users.php';

return function (App $app){
	$container = $app->getContainer();
	$vouchReceivedController = new ctr_vouchers_received();
	$userController = new ctr_users();

	//UPDATED
	$app->get('/ver-recibos_manuales-proveedores', function($request, $response, $args) use ($container, $userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'PROVIDER' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "manualReceiptsProviders.twig", $args);
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	})->setName("ManualReceiptsProvides");

	$app->get('/ver-comprobantes-recibidos', function($request, $response, $args) use ($container, $userController, $vouchReceivedController){
		// $responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		// if($responseCurrentSession->result == 2){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'PROVIDER' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$args['resultDatesVoucher'] = $vouchReceivedController->getMinAndMaxDateVoucher($responseCurrentSession->currentSession->idEmpresa);
				$args['resultTypeVouchers'] = $vouchReceivedController->getTypeExistingVouchers($responseCurrentSession->currentSession->idEmpresa);
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "vouchersProvider.twig", $args);
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
		// }else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("VouchersReceived");
	//UPDATED
	$app->post('/createManualReceiptReceived', function(Request $request, Response $response) use ($userController, $vouchReceivedController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'PROVIDER' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$dateMaked = $data['dateMaked'];
				$amount = $data['amount'];
				return json_encode($vouchReceivedController->createManualReceiptReceived($dateMaked, $amount, $responseCurrentSession->currentSession));
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getManualReceiptsReceived', function(Request $request, Response $response) use ($userController, $vouchReceivedController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'PROVIDER' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$lastId = $data['lastId'];
				$filterNameReceiver = $data['filterNameReceiver'];
				return json_encode($vouchReceivedController->getManualReceiptsReceived($lastId, $filterNameReceiver, $responseCurrentSession->currentSession->idEmpresa));
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/deleteManualReceiptReceived', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$indexVoucher = $data['index'];
			return json_encode(ctr_vouchers_received::deleteManualReceiptReceived($indexVoucher));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/modifyManualReceiptReceived', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$indexVoucher = $data['index'];
			$dateMaked = $data['dateMaked'];
			$total = $data['total'];
			return json_encode(ctr_vouchers_received::modifyManualReceiptReceived($indexVoucher, $dateMaked, $total));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/loadVouchersReceived', function(Request $request, Response $response) use ($userController, $vouchReceivedController){
		// $responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		// if($responseCurrentSession->result == 2){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'PROVIDER' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$dateReceived = $data['dateReceived'];
				$payMethod = $data['payMethod'];
				$typeVoucher = $data['typeVoucher'];
				$dateVoucher = $data['dateVoucher'];
				$numberVoucher = $data['numberVoucher'];
				$documentProvider = $data['documentProvider'];
				return json_encode($vouchReceivedController->getVouchersReceived($dateReceived, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentProvider, $responseCurrentSession->currentSession));
			}else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	// }else return json_encode($responseCurrentSession);
	});
}

?>