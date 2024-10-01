<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/backup/ctr_backup.php';

return function (App $app){
	$container = $app->getContainer();

	$spreadsheetClass = new managment_spreadsheet();


	$app->get('/ver-clientes', function($request, $response, $args)use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$responseGetPeriods = ctr_users::getVariableConfiguration("PERIODOS_FACTURACION_SERVICIOS");
			if($responseGetPeriods->result == 2){
				$list = explode(",", $responseGetPeriods->configValue);
				$args['periods'] = $list;

			}
			return $this->view->render($response, "clients.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName('Clients');

	$app->get('/ver-clientes/{clientWithBalance}', function($request, $response, $args)use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
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

	$app->get('/ver-proveedores', function($request, $response, $args) use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			$this->view->render($response, "providers.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName('Providers');

	$app->get('/configuraciones', function ($request, $response, $args) use ($container) {
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['resultPermissions'] = ctr_users::getListPermissions();
			//$args['arrayBranchCompany'] = ctr_users::getBranchCompanyByRut($responseCurrentSession->currentSession->rut);
			//var_dump($args['arrayBranchCompany']);
			$responseGetIvas = ctr_users::getListIvas();
			if($responseGetIvas->result == 2)
				$args['listIvas'] = $responseGetIvas->listResult;
			$responseGetSectionPermissions = users::getPermissionsBusiness($responseCurrentSession->currentSession->idBusiness);
			if($responseGetSectionPermissions->result == 2)
				$args['section'] = $responseGetSectionPermissions->listResult;
			$responseGetFormatTicket = ctr_users::getVariableConfiguration('FORMATO_TICKET');
			if($responseGetFormatTicket->result == 2)
				$args['formatTicket'] = $responseGetFormatTicket->configValue;
			$responseGetAdenda = ctr_users::getVariableConfiguration('ADENDA');
			if($responseGetAdenda->result == 2)
				$args['adenda'] = $responseGetAdenda->configValue;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "settings.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("Settings");

	$app->get('/iniciar-sesion[/{user}[/{rut}]]', function ($request, $response, $args) use ($container) {
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result != 2){
			if(isset($args['user']) && isset($args['rut'])){
				$args['paramUser'] = $args['user'];
				$args['paramRut'] = $args['rut'];
			}
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "signIn.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("SignIn");

	$app->get('/cerrar-session', function ($request, $response, $args) use ($container) {
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2)
			unset($_SESSION['systemSession']);//session_destroy();
		return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("SignOut");

	$app->post('/signOutPost', function ($request, $response, $args) use ($container) {
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			unset($_SESSION['systemSession']);//session_destroy();
			$response = new \stdClass();
			$response->result = 2;
			$response->message = "No hay sesión activa";
			return json_encode($response);
		}else{
			$response = new \stdClass();
			$response->result = 2;
			$response->message = "No hay sesión activa";
			return json_encode($response);
		}
	});

	$app->post('/signIn', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result != 2){
			$data = $request->getParams();
			$rut = $data['rut'];
			$user = $data['user'];
			$password = $data['password'];
			return json_encode(ctr_users::signIn($rut, $user, $password));
		}else{
			$response = new \stdClass();
			$response->result = 0;
			$response->message = "Ya cuenta con una sesión activa.";
			return json_encode($response);
		}
	});

	$app->post('/signInFromIntranet', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
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

	$app->post('/getSuggestionRut', function(Request $request, Response $response){
		$data = $request->getParams();
		$rutPart = $data['rutPart'];
		return json_encode(ctr_users::getSuggestionRut($rutPart));
	});

	$app->post('/getBusinessForModal', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$suggestionPerson = $data['suggestionPerson'];
			$prepareFor = $data['prepareFor'];

			if($prepareFor == "CLIENT")
				return json_encode(ctr_clients::getClientsForModal($suggestionPerson));
			else if($prepareFor == "PROVIDER")
				return json_encode(ctr_providers::getProvidersForModal($suggestionPerson));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/searchClientsToSale', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("VENTAS");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$textToSearch = $data['textToSearch'];
			return json_encode(ctr_clients::searchClientsToSale($textToSearch));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/searchClientToSale', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession("VENTAS");
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentClient = $data['documentClient'];
			return json_encode(ctr_clients::searchClientToSale($documentClient));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getListClients', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(NULL);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			$withBalance = $data['withBalance'];

			return json_encode(ctr_clients::getListClientsView($lastId, $textToSearch, $withBalance));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/updateClient', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idReceiver = $data['idReceiver'];
			$nameReceiver = $data['nameReceiver'];
			$numberMobile = $data['numberMobile'];
			$addressReceiver = $data['addressReceiver'];
			$locality = $data['locality'];
			$department = $data['department'];
			$email = $data['email'];

			return json_encode(ctr_clients::updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idReceiver));
		}else return json_encode($responseCurrentSession);
	});

//si el documento no está lo crea sino modifica el cliente
	$app->post('/createModifyClient', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentReceiver = $data['documentReceiver'];
			$nameReceiver = $data['nameReceiver'];
			$numberMobile = $data['numberMobile'];
			$addressReceiver = $data['addressReceiver'];
			$locality = $data['locality'];
			$department = $data['department'];
			$email = $data['email'];
			//echo "ruta";exit;
			return json_encode(ctr_clients::createModifyClient($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getClientSelected', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idReceiver = $data['idReceiver'];
			return json_encode(ctr_clients::getClientWithId($idReceiver));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/findWithDocument', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$document = $data['document'];
			$prepareFor = $data['prepareFor'];
			if($prepareFor == "CLIENT")
				return json_encode(ctr_clients::findClientWithDoc($document));
			else if($prepareFor == "PROVIDER")
				return json_encode(ctr_providers::findProviderWithDoc($document));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getBalanceClient', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('CLIENT');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentClient = $data['documentClient'];
			return json_encode(ctr_clients::getBalanceClient($documentClient));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getBalanceProvider', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('PROVIDER');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$documentProvider = $data['documentProvider'];
			return json_encode(ctr_providers::getBalanceProvider($documentProvider));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getProvider', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('PROVIDER');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idProvider = $data['idProvider'];
			return json_encode(ctr_providers::getProvider($idProvider));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/modifyProvider', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('PROVIDER');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idProvider = $data['idProvider'];
			$nameBusiness = $data['nameBusiness'];
			$address = $data['address'];
			$phoneNumber = $data['phoneNumber'];
			$email = $data['email'];

			return json_encode(ctr_providers::modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getProviders', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('PROVIDER');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			$withBalance = $data['withBalance'];
			return json_encode(ctr_providers::getProviders($lastId, $textToSearch, $withBalance));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getLastAccountStateInfo', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$prepareFor = $data['prepareFor'];
			return json_encode(ctr_users::getLastAccountStateInfo($prepareFor));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/loadDataFirstLogin', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			return json_encode(ctr_users::loadDataFirstLogin());
		}else return json_encode($responseCurrentSession);
	});


	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//funciones para manejar datos de la configuracion del usuario


	$app->post('/getConfiguration', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$nameConfig = $data['nameConfiguration'];
			return json_encode(ctr_users::getVariableConfiguration($nameConfig));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/loadConfiguration', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			return json_encode(ctr_users::getListConfigurationUser());
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/updateVariableConfiguration', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$variable = $data['variable'];
			$value = $data['value'];
			return json_encode(ctr_users::updateVariableUser($variable, $value));
		}else return json_encode($responseCurrentSession);
	});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$app->post('/getBusinesSession', function(Request $request, Response $response){
		//validamos la sesion del usuario
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){ //si la sesion es ok
			return json_encode($responseCurrentSession);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getBranchCompanyByRut', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			return json_encode(ctr_users::getBranchCompanyByRut($responseCurrentSession->currentSession->rut));
		}else return json_encode($responseCurrentSession);
	});


////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			RUTAS PARA MANEJAR DATOS EN LA SESION

	$app->post('/saveProductsInSession', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$product = $data['product'];
			return json_encode(products::saveProductsInSession($product));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getDataSession', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$index = $data['indexToSearch'];
			return json_encode(ctr_users::getDataSession($index));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/updateDataSession', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$index1 = $data['index0'];
			$index2 = $data['index1'];
			$newData = $data['newData'];
			return json_encode(ctr_users::updateProductsDataSession($index1, $index2, $newData));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/removedProductsSession', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			return json_encode(products::removedProductsSession());
		}else return json_encode($responseCurrentSession);
	});

////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 			RUTAS PARA MANEJAR PERMISOS

	$app->post('/updatePermissionSection', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			if($responseCurrentSession->currentSession->superUser == "SI"){
				$data = $request->getParams();
				$idPermission = $data['idPermission'];
				return  json_encode(ctr_users::setPermissionsBusiness($idPermission));
			}
		}else return json_encode($responseCurrentSession);
	});
////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$app->post('/loadCustomersEfactura', function(Request $request, Response $response){
		$clientController = new ctr_clients();
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$result = $clientController->loadCustomersEfactura();
			return json_encode($result);
		}else return json_encode($responseCurrentSession);
	});




	//exportar a un doc excel todos los clientes que tienen saldo
	$app->post('/exportExcelDeudores', function(Request $request, Response $response) use ($spreadsheetClass){

		$data = $request->getParams();
		$dateTo = $data['dateTo'];

		$clientController = new ctr_clients();
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$listClientes = $clientController->exportExcelDeudores( $dateTo );

			if ( $listClientes->result == 2 ){

				$responseExcel = $spreadsheetClass->exportDeudoresExcel($listClientes->listResult);
				return json_encode($responseExcel);
			}else return json_encode($listClientes);


		}else return json_encode($responseCurrentSession);
	});




	//exportar a un doc excel todos los clientes que tienen saldo
	$app->post('/exportExcelDeudoresProveedores', function(Request $request, Response $response) use ($spreadsheetClass){

		$data = $request->getParams();
		$dateTo = $data['dateTo'];

		$providerController = new ctr_providers();
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$listProvider = $providerController->exportExcelDeudores( $dateTo );
			if ( $listProvider->result == 2 ){

				$responseExcel = $spreadsheetClass->exportDeudoresExcelProviders($listProvider->listResult, $dateTo);
				return json_encode($responseExcel);
			}else return json_encode($listProvider);


		}else return json_encode($responseCurrentSession);
	});
}
?>