<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_products.php';
require_once '../src/utils/dbf_management.php';


return function (App $app){
	$container = $app->getContainer();
	$userController = new ctr_users();
	$productController = new ctr_products();
	$voucherController = new ctr_vouchers();
	$productsClass = new products();
	$dbfManagement = new dbf_management();

	$app->get('/ver-lista-precios', function($request, $response, $args) use ($container, $userController, $voucherController, $productsClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$responseGetIVA = $voucherController->getIVAsAllowed($responseCurrentSession->currentSession);
				if($responseGetIVA->result == 2)
					$args['listIVA'] = $responseGetIVA->listResult;
				//TEST NEW ---------------------------------------
					
					$responseGetHeadings = $productsClass->getHeading($responseCurrentSession->currentSession->idEmpresa);
					if($responseGetHeadings->result == 2)
						$args['listHeadings'] = $responseGetHeadings->listResult;

					$responseStockManagement = $userController->getVariableConfiguration("MANEJO_DE_STOCK", $responseCurrentSession->currentSession);
					if($responseStockManagement->result == 2){
						$args['stockManagement'] = $responseStockManagement->configValue;
					}
					
				//TEST NEW ---------------------------------------
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "priceList.twig", $args);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('PriceList');

	$app->get('/ver-caja', function($request, $response, $args) use ($container, $userController, $voucherController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$args['systemSession'] = $responseCurrentSession->currentSession;
				$responseGetIVA = $voucherController->getIVAsAllowed($responseCurrentSession->currentSession);
				if($responseGetIVA->result == 2)
					$args['listIVA'] = $responseGetIVA->listResult;
				$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
				return $this->view->render($response, "caja.twig", $args);
			} else return json_encode($responsePermissions);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('Caja');

	$app->post('/insertHeading', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$nameHeading = $data['nameHeading'];
			return json_encode($productController->insertHeading($nameHeading, $responseCurrentSession->currentSession->idEmpresa));
		}else return json_encode($responseCurrentSession);
	});


	//si se quiere insertar nuevos productos
	//UPDATED
	$app->post('/insertProduct', function(Request $request, Response $response) use ($userController, $productController){

		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idHeading = $data['idHeading'] == "" ? null : $data['idHeading'];
			$idIva = $data['idIva'];
			$description = $data['description'];
			$detail = $data['detail'];
			$brand = $data['brand'];
			$typeCoin = $data['typeCoin'];
			$cost = $data['cost'];
			$coefficient = $data['coefficient'];
			$barcode = $data['barcode'];
			$inventory = $data['inventory'] == "" ? null : $data['inventory'];
			$minInventory = $data['minInventory'];
			$discount = $data['discount'];
			$amount = $data['amount'];//importe

			return json_encode($productController->insertProduct($idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $barcode, $inventory, $minInventory, $amount, $responseCurrentSession->currentSession->idEmpresa));
		}else return json_encode($responseCurrentSession);
	});

	//actualizar datos de producto
	$app->post('/updateProduct', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idProduct = $data['idProduct'];
			$idHeading = $data['idHeading'];
			$idIva = $data['idIva'];
			$description = $data['description'];
			$detail = $data['detail'];
			$brand = $data['brand'];
			$typeCoin = $data['typeCoin'];
			$cost = $data['cost'];
			$coefficient = $data['coefficient'];
			$amount = $data['amount'];
			$barcode = $data['barcode'];
			$discount = $data['discount'];
			$inventory = (!isset($data['inventory']) || $data['inventory'] === "") ? null : $data['inventory'];
			$minInventory = (!isset($data['minInventory']) || $data['minInventory'] === "") ? null : $data['minInventory'];

			return json_encode($productController->updateProduct($idProduct, $idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $inventory, $minInventory, $responseCurrentSession->currentSession));
		}else return json_encode($responseCurrentSession);
	});


	//obtengo un articulo según su descripción, para hacer la busqueda de articulos
	$app->post('/getProductByDescription', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$description = $data['description'];
			return json_encode(ctr_products::getProductByDescription($description));
		}else return json_encode($responseCurrentSession);
	});


	//añadir producto por codigo de barras
	$app->post('/addProductByCodeBar', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$barcode = $data['barcode'];
				return json_encode($productController->addProductByCodeBar($barcode, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	//obtener un producto por su id
	$app->post('/getProductById', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idProduct = $data['idProduct'];
				return json_encode($productController->getProductById($idProduct, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	// Obtener productos a partir de sus descripcion
	$app->post('/getSuggestionProductByDescription', function(Request $request, Response $response) use ($userController, $productController){
		// $responseCurrentSession = $userController->validateCurrentSession('VENTAS');
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$textToSearch = $data['textToSearch'];
				return json_encode($productController->getSuggestionProductByDescription($textToSearch, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	// $app->post('/getSuggestionProductByDescriptionAndCoin', function(Request $request, Response $response){
	// 	$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
	// 	if($responseCurrentSession->result == 2){
	// 		$data = $request->getParams();
	// 		$textToSearch = $data['textToSearch'];
	// 		$coinToSearch = $data['coinToSearch'];
	// 		return json_encode(ctr_products::getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch));
	// 	}else return json_encode($responseCurrentSession);
	// });

	$app->post('/loadPriceList', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$lastId = $data['lastId'];
				$textToSearch = $data['textToSearch'];
				$heading = $data['heading'];
				return json_encode($productController->loadPriceList($lastId, $textToSearch, $heading, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/deleteProduct', function(Request $request, Response $response) use ($userController, $productController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				$data = $request->getParams();
				$idProduct = $data['idProduct'];
				return json_encode($productController->deleteProduct($idProduct, $responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getHeadingByName', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$heading = $data['heading'];
			return json_encode(products::getHeadingByName($heading, $responseCurrentSession->currentSession->idBusiness));
			//return json_encode($responseCurrentSession);
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getHeadings', function($request, $response) use ($userController, $productsClass){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$responsePermissions = $userController->validatePermissions('VENTAS', $responseCurrentSession->currentSession->idEmpresa);
			//error_log( "PERMISO 'VENTAS' EMPRESA: " . $responseCurrentSession->currentSession->idEmpresa . ": " . $responsePermissions->result);
			if($responsePermissions->result == 2){
				return json_encode($productsClass->getHeading($responseCurrentSession->currentSession->idEmpresa));
			} else return json_encode($responsePermissions);
		}else return json_encode($responseCurrentSession);
	});

	// $app->post('/importProducts', function(Request $request, Response $response) use ($userController, $productController, $productsClass, $dbfManagement){
	// 	$responseCurrentSession = $userController->validateCurrentSession();
	// 	if($responseCurrentSession->result == 2 && $responseCurrentSession->currentSession->superUser == "SI"){
	// 		$uploadedFilePath = null;
	// 		$data = $request->getParsedBody();
	// 		$uploadedFiles = $request->getUploadedFiles();
	// 		$file = $uploadedFiles['file'];
	// 		// Check if a file was uploaded
	// 		if ($file->getError() === UPLOAD_ERR_OK) {
	// 			// Move the uploaded file to your desired location
	// 			$uploadedFilePath = PATH_IMPORTS . $file->getClientFilename();
	// 			$file->moveTo($uploadedFilePath);
	// 			$urlMethod = 'https://admin.sigecom.uy/literalE/' . $responseCurrentSession->currentSession->rut . '?g=gog';
	// 			$curlPetition = curl_init();
	// 			curl_setopt($curlPetition, CURLOPT_URL, $urlMethod);
	// 			curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, true);
	// 			$responseCurl = curl_exec($curlPetition);
	// 			curl_close($curlPetition);
	// 			$isLiteralE = ($responseCurl == "SI") ? true : false;
	// 			$responseFunction = $dbfManagement->importProducts($responseCurrentSession->currentSession, $isLiteralE, $uploadedFilePath);
	// 			// insertProduct($idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $barcode, $inventory, $minInventory, $amount, $idEmpresa)
	// 			if($responseFunction->result == 2){
	// 				foreach ($responseFunction->products as $prod) {
	// 					$productController->insertProduct(null, $prod->idIva, $prod->descripcion, $prod->detalle, $prod->marca, $prod->moneda, $prod->costo, $prod->coeficiente, $prod->descuento, $prod->codigoBarra, 1, 0, $prod->importe, $responseCurrentSession->currentSession->idEmpresa);
	// 				}
	// 			}
	// 		} else {
	// 			$responseFunction = [
	// 				'result' => 1,
	// 				'message' => 'Error al subir el archivo'
	// 			];
	// 			return json_encode($responseFunction);
	// 		}
	// 	} else return json_encode($responseCurrentSession);
	// });

	$app->post('/importProducts', function(Request $request, Response $response) use ($userController, $productController, $productsClass, $dbfManagement){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2 && $responseCurrentSession->currentSession->superUser == "SI"){
			$uploadedFilePath = null;
			$data = $request->getParsedBody();
			$uploadedFiles = $request->getUploadedFiles();
			
			// Obtener parámetros de paginación
			$offset = isset($data['offset']) ? intval($data['offset']) : 0;
			$limit = isset($data['limit']) ? intval($data['limit']) : 500;
			
			if (isset($uploadedFiles['file'])) {
				$file = $uploadedFiles['file'];
				
				if ($file->getError() === UPLOAD_ERR_OK) {
					// Si es la primera llamada, guardar el archivo
					if ($offset == 0) {
						$uploadedFilePath = PATH_IMPORTS . $file->getClientFilename();
						$file->moveTo($uploadedFilePath);

						error_log(PATH_IMPORTS . $file->getClientFilename());
						
						// Guardar la ruta en sesión para las siguientes llamadas
						session_start();
						$_SESSION['current_import_file'] = $uploadedFilePath;
					} else {
						// Recuperar la ruta del archivo de la sesión
						session_start();
						$uploadedFilePath = $_SESSION['current_import_file'];
					}
				}
				error_log(PATH_IMPORTS);
			} else if ($offset > 0) {
				// Si no hay archivo pero hay offset, recuperar de sesión
				session_start();
				$uploadedFilePath = $_SESSION['current_import_file'];
			}
			
			if ($uploadedFilePath) {
				$urlMethod = 'https://admin.sigecom.uy/literalE/' . $responseCurrentSession->currentSession->rut . '?g=gog';
				$curlPetition = curl_init();
				curl_setopt($curlPetition, CURLOPT_URL, $urlMethod);
				curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, true);
				$responseCurl = curl_exec($curlPetition);
				curl_close($curlPetition);
				$isLiteralE = ($responseCurl == "SI") ? true : false;
				
				// Llamar a la función modificada con parámetros de lotes
				$responseFunction = $dbfManagement->importProductsBatch(
					$responseCurrentSession->currentSession, 
					$isLiteralE, 
					$uploadedFilePath,
					$offset,
					$limit
				);
				
				// Procesar solo los productos del lote actual
				if($responseFunction->result == 2){
					foreach ($responseFunction->products as $prod) {
						$productController->insertProduct(
							null, $prod->idIva, $prod->descripcion, $prod->detalle, 
							$prod->marca, $prod->moneda, $prod->costo, $prod->coeficiente, 
							$prod->descuento, $prod->codigoBarra, 1, 0, $prod->importe, 
							$responseCurrentSession->currentSession->idEmpresa
						);
					}
				}
				
				// Limpiar sesión si se completó
				if ($responseFunction->isComplete) {
					unset($_SESSION['current_import_file']);
				}
				
				return $response->withJson($responseFunction);
			} else {
				return $response->withJson([
					'result' => 1,
					'message' => 'Error al procesar el archivo'
				]);
			}
		} else {
			return $response->withJson($responseCurrentSession);
		}
	});
}
?>