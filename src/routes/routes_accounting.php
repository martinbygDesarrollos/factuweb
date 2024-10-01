<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_accounting.php';


return function (App $app){

	$userController = new ctr_users();
	$accountingController = new ctr_accounting();

	$app->get('/contabilidad', function($request, $response, $args) use ($userController) {
		$responseCurrentSession = $userController->validateCurrentSession("ACCOUNTING");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			//$args['dateFrom'] = date("Y-m-01");
			//$args['dateTo'] = date("Y-m-t");
			return $this->view->render($response, "accounting.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("Accounting");

	$app->post('/exportAccountingData', function(Request $request, Response $response) use ($userController, $accountingController) {
		$responseCurrentSession = $userController->validateCurrentSession("ACCOUNTING");
		if($responseCurrentSession->result == 2){
			$response = new \stdClass();
			$data = $request->getParams();
			$option = $data["option"];
			$idBusiness = $responseCurrentSession->currentSession->idBusiness;
			$rut = $responseCurrentSession->currentSession->rut;
			$pageSize = 999;
			$dateFrom = $data["from"];
			$dateTo = $data["to"];
			switch ($option) {
				case '1':
					return json_encode($accountingController->exportSaleData( $rut, $pageSize, $dateFrom, $dateTo ));
				default:
					$response->result = 1;
					$response->message = "No se encontró la opción seleccionada";
					return $response;
			}
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/howmanyRowsExportAccounting', function(Request $request, Response $response) use ($userController, $accountingController) {
		$responseCurrentSession = $userController->validateCurrentSession("ACCOUNTING");
		if($responseCurrentSession->result == 2){
			$response = new \stdClass();
			$data = $request->getParams();
			$option = $data["option"];
			$pageSize = 20;
			$dateFrom = $data["from"];
			$dateTo = $data["to"];
			$idBusiness = $responseCurrentSession->currentSession->idBusiness;
			$rut = $responseCurrentSession->currentSession->rut;
			switch ($option) {
				case '1':
					return json_encode($accountingController->countAllVouchersEmittedRest($rut, $pageSize, null, $dateFrom, $dateTo));
				default:
					$response->result = 1;
					$response->message = "No se encontró la opción seleccionada";
					return $response;
			}
		}else return json_encode($responseCurrentSession);
	});
}

?>