<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_providers.php';
require_once '../src/backup/ctr_backup.php';
require_once '../src/controllers/ctr_products.php';
require_once '../src/controllers/ctr_clients.php';
require_once '../src/controllers/ctr_caja.php';
require_once '../src/utils/handle_date_time.php';


return function (App $app){
	$container = $app->getContainer();
	$userController = new ctr_users();
	$cajaController = new ctr_caja();
	$usersClass = new users();
	$voucherController = new ctr_vouchers();
	$providerController = new ctr_providers();
	$spreadsheetClass = new managment_spreadsheet();
	$productController = new ctr_products();
	$clientController = new ctr_clients();
	$handleDateTimeClass = new handleDateTime();

	//UPDATED
	$app->get('/ver-clientes', function($request, $response, $args)use ($container, $userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$responseGetPeriods = $userController->getVariableConfiguration("PERIODOS_FACTURACION_SERVICIOS", $responseCurrentSession->currentSession);
			if($responseGetPeriods->result == 2){
				$list = explode(",", $responseGetPeriods->configValue);
				$args['periods'] = $list;

			}
			return $this->view->render($response, "clients.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName('Clients');
	
	//UPDATED
	$app->get('/home', function ($request, $response, $args) use ($container, $userController, $voucherController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			// var_dump($args['systemSession']);
			$responseShowQuote = $userController->getVariableConfiguration("VER_COTIZACION_INICIO", $responseCurrentSession->currentSession);
			if($responseShowQuote->result == 2){
				$args['showQuoteValue'] = $responseShowQuote->configValue;
				if($responseShowQuote->configValue = "SI"){
					$args['quote'] = $voucherController->getQuotes();
				}
			}
			return $this->view->render($response, "home.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("Home");

	$app->get('/ver-clientes/{clientWithBalance}', function($request, $response, $args)use ($container, $userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$responseGetPeriods = ctr_users::getVariableConfiguration("PERIODOS_FACTURACION_SERVICIOS");
			if($responseGetPeriods->result == 2){
				$list = explode(",", $responseGetPeriods->configValue);
				$args['periods'] = $list;

				if(isset($args['clientWithBalance'])){
					if (strcmp($args['clientWithBalance'], "unchecked") == 0){
						$args['paramCwb'] = $args['clientWithBalance'];
					}else{
						$args['paramCwb'] = "checked";
					}
				}else{
					$args['paramCwb'] = "checked";
				}
			}
			return $this->view->render($response, "clients.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	});

	$app->get('/ver-proveedores', function($request, $response, $args) use ($container, $userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$this->view->render($response, "providers.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName('Providers');
	//UPDATED
	$app->get('/configuraciones', function ($request, $response, $args) use ($container, $userController, $usersClass, $cajaController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			// $args['resultPermissions'] = $userController->getListPermissions($responseCurrentSession->currentSession->idEmpresa);
			//$args['arrayBranchCompany'] = ctr_users::getBranchCompanyByRut($responseCurrentSession->currentSession->rut);
			//var_dump($args['arrayBranchCompany']);
			$responseGetIvas = $userController->getListIvas();
			if($responseGetIvas->result == 2)
				$args['listIvas'] = $responseGetIvas->listResult;
			$responseGetSectionPermissions = $usersClass->getPermissionsBusiness($responseCurrentSession->currentSession->idEmpresa);
			if($responseGetSectionPermissions->result == 2)
				$args['section'] = $responseGetSectionPermissions->listResult;
			$responseGetFormatTicket = $userController->getVariableConfiguration('FORMATO_TICKET', $responseCurrentSession->currentSession);
			if($responseGetFormatTicket->result == 2)
				$args['formatTicket'] = $responseGetFormatTicket->configValue;
			
			if($responseCurrentSession->currentSession->superUser == "SI"){ // HACER
				$responseGetAllUsers = $userController->getAllUsersfromCompany($responseCurrentSession->currentSession->idEmpresa);
				if($responseGetAllUsers->result == 2)
					$args['users'] = $responseGetAllUsers->listResult;
				$responseGetAllCajas = $cajaController->getAllCajasFromCompany($responseCurrentSession->currentSession->idEmpresa);
				if($responseGetAllCajas->result == 2)
					$args['cajas'] = $responseGetAllCajas->listResult;
				$responseGetPOS = $cajaController->getAllPOSFromCompany($responseCurrentSession->currentSession->idEmpresa);
				if($responseGetPOS->result == 2)
					$args['listPOS'] = $responseGetPOS->listPOS;

				// var_dump($args['cajas']);
				// var_dump($args['users']);
			} else {
				$responseGetCaja = $cajaController->getUserCaja($responseCurrentSession->currentSession);
				if($responseGetCaja->result == 2)
					$args['caja'] = $responseGetCaja->caja;
				$responseGetPOS = $cajaController->getAllPOSFromCompany($responseCurrentSession->currentSession->idEmpresa);
				if($responseGetPOS->result == 2)
					$args['listPOS'] = $responseGetPOS->listPOS;
			}

			$responseGetFastSaleMeedioPago = $userController->getVariableConfiguration('SUPERFAST_SALE_MEDIOPAGO', $responseCurrentSession->currentSession);
			if($responseGetFastSaleMeedioPago->result == 2)
				$args['SUPERFAST_SALE_MEDIOPAGO'] = $responseGetFastSaleMeedioPago->configValue;
			
				$responseGetAdenda = $userController->getVariableConfiguration('ADENDA', $responseCurrentSession->currentSession);
			if($responseGetAdenda->result == 2)
				$args['adenda'] = $responseGetAdenda->configValue;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "settings.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("Settings");
	// NEW
	$app->get('/caja', function ($request, $response, $args) use ($container, $userController, $cajaController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				
				$responseGetCaja = $cajaController->getUserCaja($responseCurrentSession->currentSession);
				if($responseGetCaja->result == 2){
					$args['caja'] = $responseGetCaja->caja;
				}
				
				$responseGetMovements = $cajaController->getMovementsWithoutSnap($responseCurrentSession->currentSession);
				if($responseGetMovements->result == 2){
					$args['movements'] = $responseGetMovements->movimientos;
					$args['totales'] = $responseGetMovements->totales;
					$args['cheques'] = $responseGetMovements->cheques;
				}
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "cashRegister.twig", $args);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("CashRegister");
	// NEW
	$app->get('/caes', function ($request, $response, $args) use ($container, $userController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$listResult = null;
			$companyCaes = $userController->getCaes($responseCurrentSession->currentSession);
			if($companyCaes->result == 2) {
				$listResult = $companyCaes->caes;
			} else {
				$listResult = array();
			}
			$args['caes'] = $listResult;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "caes.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("Caes");

	$app->get('/iniciar-sesion[/{user}[/{rut}]]', function ($request, $response, $args) use ($container, $userController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result != 2){
			if(isset($args['user']) && isset($args['rut'])){
				$args['paramUser'] = $args['user'];
				$args['paramRut'] = $args['rut'];
			}
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "signIn.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("SignIn");

	$app->get('/cerrar-session', function ($request, $response, $args) use ($container, $userController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2)
			session_destroy();
		return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("SignOut");

	// $app->post('/signOutPost', function ($request, $response, $args) use ($container, $userController) {
	// 	$responseCurrentSession = $userController->validateCurrentSession();
	// 	if($responseCurrentSession->result == 2){
	// 		unset($_SESSION['systemSession']);//session_destroy();
	// 		$response = new \stdClass();
	// 		$response->result = 2;
	// 		$response->message = "No hay sesión activa";
	// 		return json_encode($response);
	// 	}else{
	// 		$response = new \stdClass();
	// 		$response->result = 2;
	// 		$response->message = "No hay sesión activa";
	// 		return json_encode($response);
	// 	}
	// });

	$app->post('/signIn', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result != 2){
			$data = $request->getParams();
			$rut = $data['rut'];
			$user = $data['user'];
			$password = $data['password'];
			return json_encode($userController->signIn($rut, $user, $password));
		}else{
			$response = new \stdClass();
			$response->result = 0;
			$response->message = "Ya cuenta con una sesión activa.";
			return json_encode($response);
		}
	});

	$app->post('/signInFromIntranet', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		error_log("validando session result ".$responseCurrentSession->result);
		if($responseCurrentSession->result != 2){
			$data = $request->getParams();
			$id = $data['idUserSigecom'];
			error_log("no hay sesion - id pasado por parametro".$id);
			return json_encode(ctr_users::signInUserFromIntranet($id));
		}else{
			$response = new \stdClass();
			$data = $request->getParams();
			$id = $data['idUserSigecom'];
			error_log("HAY sesion - id pasado por parametro".$id);
			$response->result = 2;
			$response->message = "ok";
			return json_encode($response);
		}
	});

	$app->post('/getRestorePoint', function(Request $request, Response $response){
		$data = $request->getParams();
		$newObject = new ctr_backup();
		return json_encode(ctr_backup::exportRestorePoint());
	});

	$app->post('/importRestorePoint', function(Request $request, Response $response){
		$data = $request->getParams();
		$zipBase64 = $data['zipBase64'];
		$newObject = new ctr_backup();
		return json_encode(ctr_backup::importRestorePoint($zipBase64));
	});

	$app->post('/importTableSelected', function(Request $request, Response $response){
		$data = $request->getParams();
		$tableToImport = $data['tableToImport'];
		return json_encode(ctr_backup::importContentTable($tableToImport));
	});

	$app->post('/getSuggestionRut', function(Request $request, Response $response) use ($userController){
		$data = $request->getParams();
		$rutPart = $data['rutPart'];
		return json_encode($userController->getSuggestionRut($rutPart));
	});
	//UPDATED
	$app->post('/getBusinessForModal', function(Request $request, Response $response) use ($userController, $clientController, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$suggestionPerson = $data['suggestionPerson'];
			$prepareFor = $data['prepareFor'];

			if($prepareFor == "CLIENT")
				return json_encode($clientController->getClientsForModal($suggestionPerson, $responseCurrentSession->currentSession->idEmpresa));
			else if($prepareFor == "PROVIDER")
				return json_encode($providerController->getProvidersForModal($suggestionPerson, $responseCurrentSession->currentSession->idEmpresa));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/searchClientsToSale', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$textToSearch = $data['textToSearch'];
				return json_encode($clientController->searchClientsToSale($textToSearch, $responseCurrentSession->currentSession->idEmpresa, $responseCurrentSession->currentSession->rut, $responseCurrentSession->currentSession->tokenRest));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/searchClientToSale', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$documentClient = $data['documentClient'];
				return json_encode($clientController->searchClientToSale($documentClient, $responseCurrentSession->currentSession->idEmpresa, $responseCurrentSession->currentSession->rut, $responseCurrentSession->currentSession->tokenRest));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getListClients', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			$withBalance = $data['withBalance'];

			return json_encode($clientController->getListClientsView($lastId, $textToSearch, $withBalance, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/getAllChequesInCash', function(Request $request, Response $response) use ($userController, $clientController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responseGetMovements = $cajaController->getChequesWithoutSnap($responseCurrentSession->currentSession);
			return json_encode($responseGetMovements);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/newMovement', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$data['tipo'] = (isset($data['tipo']) && $data['tipo'] != "") ? $data['tipo'] : null;
				$data['subtipo'] = (isset($data['subtipo']) && $data['subtipo'] != "") ? $data['subtipo'] : null;
				$data['importe'] = (isset($data['importe']) && $data['importe'] != "") ? $data['importe'] : null;
				$data['observacion'] = (isset($data['observacion']) && $data['observacion'] != "") ? $data['observacion'] : null;
				$data['cheques'] =  isset($data['cheques']) ? json_decode($data['cheques'], true) : array();
				// var_dump($data);
				// exit;
				$caja = null;

				$responseGetCaja = $cajaController->getUserCaja($responseCurrentSession->currentSession);
				if($responseGetCaja->result == 2){
					$caja = $responseGetCaja->caja;
				}
				$fecha = $handleDateTimeClass->getCurrentDateTimeInt();
				$movement = array(
					"tipo" => $data['tipo'],
					"medio" => $data['subtipo'],
					"importe" => $data['importe'],
					"fecha" => substr($fecha, 0, 8),
					"fecha_hora" => $fecha,
					"moneda" => $caja->moneda,
					"referencia" => null,
					"banco" => null,
					"titular" => null,
					"fecha_diferido" => null,
					"isAnulado" => 0,
					"observaciones" => $data['observacion'],
					"snap" => null,
					"caja" => $responseCurrentSession->currentSession->caja,
					"usuario" => $responseCurrentSession->currentSession->idUser,
					"cheques" => $data['cheques']
				);
				return json_encode($cajaController->insertMovement($movement, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/getMovement', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$id = $data['id'];
				return json_encode($cajaController->getMovementById($id, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/getSnap', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$id = $data['id'];
				return json_encode($cajaController->getSnapById($id, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/newSnap', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$movimientos = is_array($data['movimientos']) ? $data['movimientos'] : [];
				// $cheques = is_array($data['cheques']) ? $data['cheques'] : [];
				$cheques = isset($data['cheques']) && is_array($data['cheques']) ? $data['cheques'] : [];
				// echo "<br><br> MOVIMIENTOS <br><br>";
				// var_dump($movimientos);
				// echo "<br><br> CHEQUES <br><br>";
				// var_dump($cheques);
				// echo "<br><br> EFECTIVO UYU <br><br>";
				// var_dump($data['efectivo']['UYU']);
				// echo "<br><br> EFECTIVO USD <br><br>";
				// var_dump($data['efectivo']['USD']);
				// echo "<br><br><br><br>";
				// exit;
				// $id = $data['id'];
				return json_encode($cajaController->newSnap($movimientos, $cheques, $data['efectivo']['UYU'], $data['efectivo']['USD'], $data['saldos']['UYU'], $data['saldos']['USD'], $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/saveArqueo', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$arqueo = (isset($data['data']) ) ? $data['data'] : null;
				// var_dump($arqueo);
				// exit;
				return json_encode($cajaController->updateArqueo($arqueo));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/getArqueo', function(Request $request, Response $response) use ($userController, $clientController, $cajaController, $handleDateTimeClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CAJA', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				// var_dump($arqueo);
				// exit;
				return json_encode($cajaController->getArqueo());
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	// NEW SUPER USER o USER NORMAL A SU PROPIA CAJA
	$app->post('/updateCaja', function(Request $request, Response $response) use ($userController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$data['POS'] = (isset($data['POS']) && $data['POS'] != "") ? intval($data['POS']) : null;
			$data['id'] = (isset($data['id']) && $data['id'] != "") ? intval($data['id']) : null;
			if($responseCurrentSession->currentSession->superUser == "SI"){
				return json_encode($cajaController->updateCaja($data, $data['id']));
			} else if($data['id'] == $responseCurrentSession->currentSession->caja){
				return json_encode($cajaController->updateCaja($data, $data['id']));
			} else return json_encode(['result' => 0, 'message' => 'Exclusivo para usuarios administradores']);
		}else return json_encode($responseCurrentSession);
	});
	
	// NEW SUPER USER
	$app->post('/updatePOS', function(Request $request, Response $response) use ($userController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$data['id'] = (isset($data['id']) && $data['id'] != "") ? intval($data['id']) : null;
			$data['marca'] = (isset($data['marca']) && $data['marca'] != "") ? $data['marca'] : null;
			$data['codigo'] = (isset($data['codigo']) && $data['codigo'] != "") ? $data['codigo'] : null;
			$data['hash'] = (isset($data['hash']) && $data['hash'] != "") ? $data['hash'] : null;
			$data['terminal'] = (isset($data['terminal']) && $data['terminal'] != "") ? $data['terminal'] : null;
			if($responseCurrentSession->currentSession->superUser == "SI"){
				return json_encode($cajaController->updatePOS($data, $data['id']));
			} else return json_encode(['result' => 0, 'message' => 'Exclusivo para usuarios administradores']);
		}else return json_encode($responseCurrentSession);
	});

	// NEW
	$app->post('/setCajaToUser', function(Request $request, Response $response) use ($userController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			if($responseCurrentSession->currentSession->superUser == "SI"){
				$data = $request->getParams();
				$user = (isset($data['user']) && $data['user'] != "") ? intval($data['user']) : null;
				$caja = (isset($data['caja']) && $data['caja'] != "") ? intval($data['caja']) : null;
				return json_encode($cajaController->setCajaToUser($user, $caja));
			} else return json_encode(['result' => 0, 'message' => 'Exclusivo para usuarios administradores']);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/updateClient', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CLIENT', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idReceiver = $data['idReceiver'];
				$nameReceiver = $data['nameReceiver'];
				$numberMobile = $data['numberMobile'];
				$addressReceiver = $data['addressReceiver'];
				$locality = $data['locality'];
				$department = $data['department'];
				$email = $data['email'];
				return json_encode($clientController->updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idReceiver, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	//si el documento no está lo crea sino modifica el cliente
	$app->post('/createModifyClient', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CLIENT', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$documentReceiver = $data['documentReceiver'];
				$nameReceiver = $data['nameReceiver'];
				$numberMobile = $data['numberMobile'];
				$addressReceiver = $data['addressReceiver'];
				$locality = $data['locality'];
				$department = $data['department'];
				$email = $data['email'];
				// return json_encode($clientController->createModifyClient($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $responseCurrentSession->currentSession->idEmpresa, $responseCurrentSession->currentSession->rut, $responseCurrentSession->currentSession->tokenRest));
				return json_encode($clientController->createModifyClientJustLocal($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $responseCurrentSession->currentSession->idEmpresa, $responseCurrentSession->currentSession->rut, $responseCurrentSession->currentSession->tokenRest));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getClientSelected', function(Request $request, Response $response) use ($userController, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CLIENT', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idReceiver = $data['idReceiver'];
				return json_encode($clientController->getClientWithId($idReceiver));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/findWithDocument', function(Request $request, Response $response) use ($userController, $clientController, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('CLIENT', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$document = $data['document'];
				$prepareFor = $data['prepareFor'];
				if($prepareFor == "CLIENT")
					return json_encode($clientController->findClientWithDoc($document, $responseCurrentSession->currentSession->idEmpresa));
				else if($prepareFor == "PROVIDER")
					return json_encode($providerController->findProviderWithDoc($document, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getBalanceClient', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentClient = $data['documentClient'];
			return json_encode(ctr_clients::getBalanceClient($documentClient));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getBalanceProvider', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = ctr_users::validateCurrentSession('PROVIDER');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentProvider = $data['documentProvider'];
			return json_encode(ctr_providers::getBalanceProvider($documentProvider));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getProvider', function(Request $request, Response $response) use ($userController, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idProvider = $data['idProvider'];
				return json_encode($providerController->getProvider($idProvider, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/modifyProvider', function(Request $request, Response $response) use ($userController, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idProvider = $data['idProvider'];
				$nameBusiness = $data['nameBusiness'];
				$address = $data['address'];
				$phoneNumber = $data['phoneNumber'];
				$email = $data['email'];

				return json_encode($providerController->modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getProviders', function(Request $request, Response $response) use ($userController, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('PROVIDER', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$lastId = $data['lastId'];
				$textToSearch = $data['textToSearch'];
				$withBalance = $data['withBalance'];
				return json_encode($providerController->getProviders($lastId, $textToSearch, $withBalance, $responseCurrentSession->currentSession));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getLastAccountStateInfo', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$prepareFor = $data['prepareFor'];
			return json_encode($userController->getLastAccountStateInfo($prepareFor));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/loadDataFirstLogin', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			return json_encode($userController->loadDataFirstLogin($responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});


	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//funciones para manejar datos de la configuracion del usuario

	//NEW
	$app->post('/getUserCaja', function(Request $request, Response $response) use ($userController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			// if($responseCurrentSession->currentSession->permission->CAJA == "SI")
			return json_encode($cajaController->getUserCaja($responseCurrentSession->currentSession));
			// else
			// return json_encode(['result' => 0, 'message' => 'Empresa sin permisos']);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getConfiguration', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$nameConfig = $data['nameConfiguration'];
			return json_encode($userController->getVariableConfiguration($nameConfig, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/loadConfiguration', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			return json_encode($userController->getListConfigurationUser($responseCurrentSession->currentSession->idUser));
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/updateVariableConfiguration', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$variable = $data['variable'];
			$value = $data['value'];
			return json_encode($userController->updateVariableUser($variable, $value, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$app->post('/getBusinesSession', function(Request $request, Response $response) use ($userController){
		//validamos la sesion del usuario
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){ //si la sesion es ok
			return json_encode($responseCurrentSession);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/getBranchCompanyByRut', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			return json_encode($userController->getBranchCompanyByRut($responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});


////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			RUTAS PARA MANEJAR DATOS EN LA SESION
	//UPDATED
	$app->post('/saveProductsInSession', function($request, $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$product = $data['product'];
			return json_encode($productController->saveProductsInSession($product));
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/getCart', function($request, $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			return json_encode($userController->getCart());
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/deleteCart', function($request, $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			return json_encode($userController->deleteCart());
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/updateArticleInCart', function($request, $response) use ($userController){
		// $responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$article = $data['article'];
				$index = $data['index'];
				return json_encode($userController->updateArticleInCart($article, $index));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	// NEW
	$app->post('/deleteArticleFromCart', function($request, $response) use ($userController){
		// $responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$index = $data['index'];
				return json_encode($userController->deleteArticleFromCart($index));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	$app->post('/setNewDataSession', function($request, $response) use ($userController){
		// $responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$cart = $data['cart'];
				return json_encode($userController->setNewDataSession($cart));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});
	//UPDATED
	$app->post('/removeProductsSession', function($request, $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				return json_encode($productController->removeProductsSession());
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			RUTAS PARA MANEJAR PERMISOS
	//UPDATED
	$app->post('/updatePermissionSection', function($request, $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			if($responseCurrentSession->currentSession->superUser == "SI"){
				$data = $request->getParams();
				$idPermission = $data['idPermission'];
				return  json_encode($userController->setPermissionsBusiness($idPermission, $responseCurrentSession->currentSession));
			} else return json_encode(['result' => 0, 'message' => 'Exclusivo para usuarios administradores']);
		}else return json_encode($responseCurrentSession);
	});
////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//WORKING
	$app->post('/loadCustomersEfactura', function(Request $request, Response $response) use ($userController){
		$clientController = new ctr_clients();
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$result = $clientController->loadCustomersEfactura($responseCurrentSession->currentSession);
			return json_encode($result);
		}else return json_encode($responseCurrentSession);
	});
	//exportar a un doc excel todos los clientes que tienen saldo
	//UPDATED
	$app->post('/exportExcelDeudores', function(Request $request, Response $response) use ( $userController, $spreadsheetClass, $clientController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$dateTo = $data['dateTo'];
			$listClientes = $clientController->exportExcelDeudores( $dateTo, $responseCurrentSession->currentSession);
			if ( $listClientes->result == 2 ){
				$responseExcel = $spreadsheetClass->exportDeudoresExcel($listClientes->listResult);
				return json_encode($responseExcel);
			}else return json_encode($listClientes);
		}else return json_encode($responseCurrentSession);
	});
	//exportar a un doc excel todos los clientes que tienen saldo
	//UPDATED
	$app->post('/exportExcelDeudoresProveedores', function(Request $request, Response $response) use ($userController, $spreadsheetClass, $providerController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$dateTo = $data['dateTo'];
			$listProvider = $providerController->exportExcelDeudores( $dateTo, $responseCurrentSession->currentSession);
			if ( $listProvider->result == 2 ){
				$responseExcel = $spreadsheetClass->exportDeudoresExcelProviders($listProvider->listResult, $dateTo);
				return json_encode($responseExcel);
			}else return json_encode($listProvider);
		}else return json_encode($responseCurrentSession);
	});
////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ELiminar empresa
	$app->post('/eliminarEmpresa', function(Request $request, Response $response) use ($userController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2 && $responseCurrentSession->currentSession->superUser == "SI"){
			// var_dump($responseCurrentSession->currentSession);
			// $data = $request->getParams();
			return json_encode($userController->eliminarEmpresa($responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});

}
?>