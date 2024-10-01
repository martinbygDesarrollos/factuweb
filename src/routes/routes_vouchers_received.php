<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_vouchers_received.php';
require_once '../src/controllers/ctr_users.php';

return function (App $app){
	$container = $app->getContainer();

	$app->get('/ver-recibos_manuales-proveedores', function($request, $response, $args) use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "manualReceiptsProviders.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("ManualReceiptsProvides");

	$app->get('/ver-comprobantes-recibidos', function($request, $response, $args) use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['resultDatesVoucher'] = ctr_vouchers_received::getMinAndMaxDateVoucher();
			$args['resultTypeVouchers'] = ctr_vouchers_received::getTypeExistingVouchers();
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "vouchersProvider.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("VouchersReceived");

	$app->post('/createManualReceiptReceived', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$dateMaked = $data['dateMaked'];
			$amount = $data['amount'];
			return json_encode(ctr_vouchers_received::createManualReceiptReceived($dateMaked, $amount));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getManualReceiptsReceived', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$filterNameReceiver = $data['filterNameReceiver'];
			return json_encode(ctr_vouchers_received::getManualReceiptsReceived($lastId, $filterNameReceiver));
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

	$app->post('/loadVouchersReceived', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("PROVIDER");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$dateReceived = $data['dateReceived'];
			$payMethod = $data['payMethod'];
			$typeVoucher = $data['typeVoucher'];
			$dateVoucher = $data['dateVoucher'];
			$numberVoucher = $data['numberVoucher'];
			$documentProvider = $data['documentProvider'];

			return json_encode(ctr_vouchers_received::getVouchersReceived($dateReceived, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentProvider));
		}else return json_encode($responseCurrentSession);
	});
}

?>