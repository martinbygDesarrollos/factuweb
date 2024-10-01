<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_clients.php';
require_once '../src/controllers/ctr_services.php';

return function (App $app){
	$container = $app->getContainer();

	$app->get('/ver-cuotas-servicios', function($request, $response, $args)use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$responseShowOptionFeeService = ctr_users::getVariableConfiguration("REALIZAR_FACTURA_POR_SERVICIO");
			if($responseShowOptionFeeService->result == 2)
				$args['showInvoiceAllFeesService'] = $responseShowOptionFeeService->configValue;

			$responseGetPeriods = ctr_users::getVariableConfiguration("PERIODOS_FACTURACION_SERVICIOS");
			if($responseGetPeriods->result == 2){
				$list = explode(",", $responseGetPeriods->configValue);
				$args['periods'] = $list;
			}

			return $this->view->render($response, "feeServices.twig", $args);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('FeeServices');

	$app->get('/ver-servicios', function($request, $response, $args)use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$responseGetIVA = ctr_vouchers::getIVAsAllowed();
			if($responseGetIVA->result == 2)
				$args['listIVA'] = $responseGetIVA->listResult;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "services.twig", $args);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('Services');

	$app->post('/getFeeServiceWithDetail', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];
			return json_encode(ctr_services::getFeeServiceWithDetail($idFeeService));
		}else return json_encode($responseSession);
	});

	$app->post('/loadFeeServices', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];

			return json_encode(ctr_services::getListFeeServices($lastId, $textToSearch));
		}else return json_encode($responseSession);
	});

	$app->post('/getServiceSelected', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];
			return json_encode(ctr_services::getServiceSelected($idService));
		}else return json_encode($responseSession);
	});

	$app->post('/listServiceToChange', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];
			$idClient = $data['idClient'];
			return json_encode(ctr_services::listServiceToChange($idService, $idClient));
		}else return json_encode($responseSession);
	});

	//list all service getallservice
	$app->post('/loadServices', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			return json_encode(ctr_services::getListServices($lastId, $textToSearch));
		}else return json_encode($responseSession);
	});

	$app->post('/activeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];
			return json_encode(ctr_services::activeService($idService));
		}else return json_encode($responseSession);
	});


	$app->post('/createService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$name = $data['name'];
			$description = $data['description'];
			$cost = $data['cost'];
			$amount = $data['amount'];
			$typeCoin = $data['typeCoin'];
			$idIva = $data['idIVA'];

			return json_encode(ctr_services::createService($name, $description, $typeCoin, $cost, $amount, $idIva));
		}else return json_encode($responseSession);
	});

	$app->post('/modifyService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];
			$name = $data['name'];
			$description = $data['description'];
			$cost = $data['cost'];
			$amount = $data['amount'];
			$typeCoin = $data['typeCoin'];
			$idIva = $data['idIVA'];

			return json_encode(ctr_services::modifyService($idService, $name, $description, $cost, $amount, $typeCoin, $idIva));
		}else return json_encode($responseSession);
	});

	$app->post('/deleteService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];

			return json_encode(ctr_services::deleteService($idService));
		}else return json_encode($responseSession);
	});

	$app->post('/deleteFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];

			return json_encode(ctr_services::deleteFeeService($idFeeService));
		}else return json_encode($responseSession);
	});

	$app->post('/getIVAsAllowed', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			return json_encode(ctr_vouchers::getIVAsAllowed());
		}else return json_encode($responseSession);
	});

	$app->post('/changeCurrentValueService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];
			return json_encode(ctr_services::changeCurrentValueFeeService($idFeeService));
		}else return json_encode($responseSession);
	});

//crea una factura para una cuota de servicio
	$app->post('/invoiceFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];
			$dateExpiration = $data['dateExpiration'];
			$dateEmitted = $data['dateEmitted'];
			return json_encode(ctr_services::invoiceOneFeeService($idFeeService, $dateEmitted, $dateExpiration));
		}else return json_encode($responseSession);
	});

	$app->post('/invoiceAllFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$dateEmitted = $data['dateEmitted'];
			$dateExpiration = $data['dateExpiration'];
			return json_encode(ctr_services::invoiceAllFeeService($dateEmitted, $dateExpiration));
		}else return json_encode($responseSession);
	});

	$app->post('/getAllServiceForClient', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idClient = $data['idClient'];
			return json_encode(ctr_services::getAllService($idClient));
		}else return json_encode($responseSession);
	});

	$app->post('/getSelectedFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];
			return json_encode(ctr_services::getFeeServiceToShow($idFeeService));
		}else return json_encode($responseSession);
	});

	$app->post('/createNewFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idService = $data['idService'];
			$idClient = $data['idClient'];
			$period = $data['period'];
			return json_encode(ctr_services::createNewFeeService($idService, $idClient, $period));
		}else return json_encode($responseSession);
	});

	$app->post('/modifyFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idFeeService = $data['idFeeService'];
			$idService = $data['idService'];
			$period = $data['period'];
			return json_encode(ctr_services::modifyFeeService($idFeeService, $idService, $period));
		}else return json_encode($responseSession);
	});

	$app->post('/getFeeServiceToExport', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			return json_encode(ctr_services::getFeeServiceToExport());
		}else return json_encode($responseSession);
	});

	$app->post('/getCountBillableFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$dateEmitted = $data['date'];
			$dateExpiration = $data['date2'];
			return json_encode(ctr_services::getCountBillableFeeService($dateEmitted, $dateExpiration));
		}else return json_encode($responseSession);
	});

	$app->post('/getLatsFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			return json_encode(ctr_services::getLatsFeeService());
		}else return json_encode($responseSession);
	});
}
?>