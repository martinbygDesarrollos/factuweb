<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_vouchers_emitted.php';
require_once '../src/controllers/ctr_users.php';

return function (App $app){
	$container = $app->getContainer();

	$app->get('/ver-recibos-manuales-clientes', function($request, $response, $args) use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "manualReceiptsClients.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("ManualReceiptsClients");

	$app->get('/ver-comprobantes-emitidos', function($request, $response, $args) use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['resultDatesVoucher'] = ctr_vouchers_emitted::getMinAndMaxDateVoucher();
			$args['resultTypeVouchers'] = ctr_vouchers_emitted::getTypeExistingVouchers();
			$args['branchCompany'] = ctr_users::getBranchCompanyByRut($responseCurrentSession->currentSession->rut);
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "vouchersClients.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("VouchersEmitted");

	$app->post('/cancelVoucherEmitted', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idVoucher = $data['idVoucher'];
			$dateCancelVoucher = $data['dateCancelVoucher'];
			$appendix = $data['appendix'];
			return json_encode(ctr_vouchers_emitted::cancelVoucherEmitted($idVoucher, $dateCancelVoucher, $appendix));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getManualReceiptsEmitted', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$filterNameReceiver = $data['filterNameReceiver'];
			return json_encode(ctr_vouchers_emitted::getManualReceiptsEmitted($lastId, $filterNameReceiver));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/deleteManualReceipt', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$indexVoucher = $data['indexVoucher'];
			return json_encode(ctr_vouchers_emitted::deleteManualReceiptEmitted($indexVoucher));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/modifyManualReceipt', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$indexVoucher = $data['indexVoucher'];
			$total = $data['total'];
			$dateReceipt = $data['dateReceipt'];
			$typeCoin = $data['typeCoin'];
			return json_encode(ctr_vouchers_emitted::modifyManualReceiptEmitted($indexVoucher, $total, $dateReceipt, $typeCoin));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/loadVouchersEmitted', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastVoucherEmittedIdFound = $data['lastVoucherEmittedIdFound'];
			//$dateEmitted = $data['dateEmitted'];
			$payMethod = $data['payMethod'];
			$typeVoucher = $data['typeVoucher'];
			$dateVoucher = $data['dateVoucher'];
			$numberVoucher = $data['numberVoucher'];
			$documentClient = $data['documentClient'];
			$branchCompany = $data['branchCompany'];

			return json_encode(ctr_vouchers_emitted::getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $branchCompany));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/calculateTotalVoucherSelected', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idsSelected = $data['idsSelected'];
			return json_encode(ctr_vouchers_emitted::calculateTotalVoucherSelected($idsSelected));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/createVoucherReceiptEmitted', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentClient = $data['documentClient'];
			$address = $data['address'];
			$city = $data['city'];
			$idsVouchersSelected = $data['idsSelected'];
			$dateVoucher = $data['dateVoucher'];
			$USDValue = $data['USDValue'];
			$total = $data['total'];
			$reasonReference = $data['reasonReference'];
			$checkedOfficial = $data['checkedOfficial'];

			return json_encode(ctr_vouchers_emitted::createVoucherReceiptEmitted($documentClient, $address, $city, $idsVouchersSelected, $dateVoucher, $USDValue, $total, $reasonReference, $checkedOfficial, null));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/accountStatePost', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			return json_encode(ctr_vouchers_emitted::getClientAccountSate(2, "2021-03-17", "2021-09-17", "USD", "SI"));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getLastVoucherEmitted', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			return json_encode(ctr_vouchers_emitted::getLastVoucherEmitted());
		}else return json_encode($responseCurrentSession);
	});
}

?>