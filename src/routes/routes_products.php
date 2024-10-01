<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_products.php';


return function (App $app){
	$container = $app->getContainer();

	$app->get('/ver-lista-precios', function($request, $response, $args)use ($container){
		$responseCurrentSession = ctr_users::validateCurrentSession("VENTAS");
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$responseGetIVA = ctr_vouchers::getIVAsAllowed();
			if($responseGetIVA->result == 2)
				$args['listIVA'] = $responseGetIVA->listResult;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "priceList.twig", $args);
		}else return $response->withRedirect('iniciar-sesion');
	})->setName('PriceList');

	$app->post('/insertHeading', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$nameHeading = $data['nameHeading'];
			return json_encode(ctr_products::insertHeading($nameHeading));
		}else return json_encode($responseCurrentSession);
	});


	//si se quiere insertar nuevos productos
	$app->post('/insertProduct', function(Request $request, Response $response){

		$responseCurrentSession = ctr_users::validateCurrentSession(null);
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idHeading = $data['idHeading'];
			$idIva = $data['idIva'];
			$description = $data['description'];
			$detail = $data['detail'];
			$brand = $data['brand'];
			$typeCoin = $data['typeCoin'];
			$cost = $data['cost'];
			$coefficient = $data['coefficient'];
			$barcode = $data['barcode'];
			$inventory = $data['inventory'];
			$minInventory = $data['minInventory'];
			$discount = $data['discount'];
			$amount = $data['amount'];//importe

			return json_encode(ctr_products::insertProduct($idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $barcode, $inventory, $minInventory, $amount));
		}else return json_encode($responseCurrentSession);
	});

	//actualizar datos de producto
	$app->post('/updateProduct', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession(null);
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

			return json_encode(ctr_products::updateProduct($idProduct, $idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode));
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
	$app->post('/addProductByCodeBar', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$barcode = $data['barcode'];
			return json_encode(ctr_products::addProductByCodeBar($barcode));
		}else return json_encode($responseCurrentSession);
	});

	//obtener un producto por su id
	$app->post('/getProductById', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idProduct = $data['idProduct'];
			return json_encode(ctr_products::getProductById($idProduct));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getSuggestionProductByDescription', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$textToSearch = $data['textToSearch'];
			return json_encode(ctr_products::getSuggestionProductByDescription($textToSearch));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/getSuggestionProductByDescriptionAndCoin', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$textToSearch = $data['textToSearch'];
			$coinToSearch = $data['coinToSearch'];
			return json_encode(ctr_products::getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/loadPriceList', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			$heading = $data['heading'];
			return json_encode(ctr_products::loadPriceList($lastId, $textToSearch, $heading));
		}else return json_encode($responseCurrentSession);
	});

	$app->post('/deleteProduct', function(Request $request, Response $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			$data = $request->getParams();
			$idProduct = $data['idProduct'];
			return json_encode(ctr_products::deleteProduct($idProduct));
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

	$app->post('/getHeadins', function($request, $response){
		$responseCurrentSession = ctr_users::validateCurrentSession('VENTAS');
		if($responseCurrentSession->result == 2){
			return json_encode(products::getHeading($responseCurrentSession->currentSession->idBusiness));
		}else return json_encode($responseCurrentSession);
	});
}
?>