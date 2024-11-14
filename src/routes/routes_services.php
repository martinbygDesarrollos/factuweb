<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_clients.php';
require_once '../src/controllers/ctr_services.php';

return function (App $app){
	$container = $app->getContainer();
	$userController = new ctr_users();
	$voucherController = new ctr_vouchers();
	$serviceController = new ctr_services();

	//UPDATED
	$app->get('/ver-cuotas-servicios', function($request, $response, $args)use ($container, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				$responseShowOptionFeeService = $userController->getVariableConfiguration("REALIZAR_FACTURA_POR_SERVICIO", $responseCurrentSession->currentSession);
				if($responseShowOptionFeeService->result == 2)
					$args['showInvoiceAllFeesService'] = $responseShowOptionFeeService->configValue;
				$responseGetPeriods = $userController->getVariableConfiguration("PERIODOS_FACTURACION_SERVICIOS", $responseCurrentSession->currentSession);
				if($responseGetPeriods->result == 2){
					$list = explode(",", $responseGetPeriods->configValue);
					$args['periods'] = $list;
				}
				return $this->view->render($response, "feeServices.twig", $args);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('FeeServices');
	//UPDATED
	$app->get('/ver-servicios', function($request, $response, $args) use ($container, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$responseGetIVA = $voucherController->getIVAsAllowed($responseCurrentSession->currentSession);
				if($responseGetIVA->result == 2)
					$args['listIVA'] = $responseGetIVA->listResult;
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "services.twig", $args);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('Services');
	//UPDATED
	$app->post('/getFeeServiceWithDetail', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];
				return json_encode($serviceController->getFeeServiceWithDetail($idFeeService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/loadFeeServices', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$lastId = $data['lastId'];
				$textToSearch = $data['textToSearch'];

				return json_encode($serviceController->getListFeeServices($lastId, $textToSearch, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/getServiceSelected', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];
				return json_encode($serviceController->getServiceSelected($idService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/listServiceToChange', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];
				$idClient = $data['idClient'];
				return json_encode($serviceController->listServiceToChange($idService, $idClient, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

	//list all service getallservice
	//UPDATED
	$app->post('/loadServices', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$lastId = $data['lastId'];
				$textToSearch = $data['textToSearch'];
				return json_encode($serviceController->getListServices($lastId, $textToSearch, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/activeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];
				return json_encode($serviceController->activeService($idService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

	//UPDATED
	$app->post('/createService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$name = $data['name'];
				$description = $data['description'];
				$cost = $data['cost'];
				$amount = $data['amount'];
				$typeCoin = $data['typeCoin'];
				$idIva = $data['idIVA'];

				return json_encode($serviceController->createService($name, $description, $typeCoin, $cost, $amount, $idIva, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/modifyService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];
				$name = $data['name'];
				$description = $data['description'];
				$cost = $data['cost'];
				$amount = $data['amount'];
				$typeCoin = $data['typeCoin'];
				$idIva = $data['idIVA'];

				return json_encode($serviceController->modifyService($idService, $name, $description, $cost, $amount, $typeCoin, $idIva, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/deleteService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];

				return json_encode($serviceController->deleteService($idService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/deleteFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];

				return json_encode($serviceController->deleteFeeService($idFeeService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

	$app->post('/getIVAsAllowed', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			return json_encode(ctr_vouchers::getIVAsAllowed());
		}else return json_encode($responseSession);
	});
	//UPDATED
	$app->post('/changeCurrentValueService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];
				return json_encode($serviceController->changeCurrentValueFeeService($idFeeService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

//crea una factura para una cuota de servicio
	//UPDATED
	$app->post('/invoiceFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];
				$dateExpiration = $data['dateExpiration'];
				$dateEmitted = $data['dateEmitted'];
				return json_encode($serviceController->invoiceOneFeeService($idFeeService, $dateEmitted, $dateExpiration, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/invoiceAllFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$dateEmitted = $data['dateEmitted'];
				$dateExpiration = $data['dateExpiration'];
				return json_encode($serviceController->invoiceAllFeeService($dateEmitted, $dateExpiration, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

	$app->post('/getAllServiceForClient', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			$data = $request->getParams();
			$idClient = $data['idClient'];
			return json_encode(ctr_services::getAllService($idClient));
		}else return json_encode($responseSession);
	});
	//UPDATED
	$app->post('/getSelectedFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];
				return json_encode($serviceController->getFeeServiceToShow($idFeeService, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/createNewFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idService = $data['idService'];
				$idClient = $data['idClient'];
				$period = $data['period'];
				return json_encode($serviceController->createNewFeeService($idService, $idClient, $period, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/modifyFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idFeeService = $data['idFeeService'];
				$idService = $data['idService'];
				$period = $data['period'];
				return json_encode($serviceController->modifyFeeService($idFeeService, $idService, $period, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/getFeeServiceToExport', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				return json_encode($serviceController->getFeeServiceToExport($responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});
	//UPDATED
	$app->post('/getCountBillableFeeService', function(Request $request, Response $response) use ($userController, $serviceController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('SERVICE', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$dateEmitted = $data['date'];
				$dateExpiration = $data['date2'];
				// return json_encode($serviceController->getCountBillableFeeService($dateEmitted, $dateExpiration));// NO ENTIENDO PORQUE SE ENVIA ESTA FECHA $dateExpiration
				return json_encode($serviceController->getCountBillableFeeService($dateEmitted, $responseCurrentSession->currentSession));
				// return json_encode(['result' => 1]);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	});

	$app->post('/getLatsFeeService', function(Request $request, Response $response){
		$responseSession = ctr_users::validateCurrentSession("SERVICE");
		if($responseSession->result == 2){
			return json_encode(ctr_services::getLatsFeeService());
		}else return json_encode($responseSession);
	});
}
?>