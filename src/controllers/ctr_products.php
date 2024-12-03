<?php

require_once '../src/class/products.php';
require_once '../src/class/others.php';
require_once '../src/utils/handle_date_time.php';

require_once 'ctr_users.php';
require_once 'rest/ctr_rest.php';

class ctr_products{

	//Controla si la empresa esta autorizada para utilizar el iva seleccionado.
	public function authorizedToUse($idIva, $currentSession){
		$response = new \stdClass();
		$voucherController = new ctr_vouchers();

		$responseGetIvas = $voucherController->getIVAsAllowed($currentSession);
		if($responseGetIvas->result == 2){
			foreach ($responseGetIvas->listResult as $key => $value) {
				if($value['idIVA'] == $idIva){
					$response->result = 2;
					return $response;
				}
			}
			$response->result = 0;
			$response->message = "El IVA seleccionado no corresponde a uno de los habilitados para la empresa.";
		}else return $responseGetIvas;

		return $response;
	}

	public function deleteProduct($idProduct, $idEmpresa){
		$response = new \stdClass();
		$productsClass = new products();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
			$responseDeleteProduct = $productsClass->deleteProduct($idProduct, $idEmpresa);
			if($responseDeleteProduct->result == 2){
				$response->result = 2;
				$response->message = "El artículo fue borrado correctamente del sistema.";
			}else return $responseDeleteProduct;
		// }else return $responseGetBusiness;

		return $response;
	}

	public function loadPriceList($lastId, $textToSearch, $heading, $idEmpresa){
		$response = new \stdClass();
		$productsClass = new products();
		return $productsClass->loadPriceList($idEmpresa, $lastId, $textToSearch, $heading);

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// }else return $responseGetBusiness;

		// return $response;
	}
	//UPDATED
	public function insertHeading($nameHeading, $idEmpresa){
		$response = new \stdClass();
		$productsClass = new products();
		$userController = new ctr_users();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetHeadingByName = $productsClass->getHeadingByName($nameHeading, $idEmpresa);
		if($responseGetHeadingByName->result != 2){
			$responseInsertHeading = $productsClass->insertHeading($nameHeading, $idEmpresa);
			if($responseInsertHeading->result == 2){
				$response->result = 2;
				$response->message = "El rubro '". $nameHeading ."' fue ingresado correctamente.";
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error y el rubro '". $nameHeading ."' no pudo ser ingresado en el sistema.";
			}
		}else{
			$response->result = 0;
			$response->message = "El rubro '". $nameHeading ."' ya existe en el sistema.";
		}
		// }else return $responseGetBusiness;

		return $response;
	}
	//UPDATED
	public function insertProduct($idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $barcode, $inventory, $minInventory, $amount, $idEmpresa){
		$response = new \stdClass();
		$productsController = new ctr_products();
		$productsClass = new products();
		$othersClass = new others();
		$handleDateTimeClass = new handleDateTime();
		error_log("INSERTAR PRODUCTO: RUBRO: $idHeading, IVA: $idIva, DESCRIPCION: $description, DETALLES: $detail, MARCA: $brand, MONEDA: $typeCoin, COSTO: $cost, COEFICIENTE: $coefficient, DESCUENTO: $discount, CODIGO DE BARRAS: $barcode, INVENTARIO: $inventory, INVENTARIO MINIMO: $minInventory, IMPORTE: $amount, EMPRESA: $idEmpresa.");
		$responseGetHeading = $productsClass->getHeadingById($idHeading, $idEmpresa);
		if($responseGetHeading->result == 2){
			$responseGetProductByDescription = $productsClass->getProductByDescription($description, $idEmpresa);
			if($responseGetProductByDescription->result != 2){
				$responseGetIva = $othersClass->getValueIVA($idIva);
				if($responseGetIva->result == 2){
					$idNewInventory = null;
					// if(!is_null($inventory) && !is_null($minInventory)){ // SI NO son NULL ni inventario ni minimo de inventario creo la fila inventario para el producto nuevo
					$dateInventory = $handleDateTimeClass->getCurrentDateTimeInt();
					error_log("INVENTARIO DATOS( Inventario: $inventory, min Inventario: $minInventory, fecha Inventario: $dateInventory");
					$responseInsertInventory = $productsClass->insertInventory($inventory, $minInventory, $dateInventory, $idEmpresa);
					if($responseInsertInventory->result == 2)
						$idNewInventory = $responseInsertInventory->id;
					else return $responseInsertInventory;
					// }
					$responseInsertProduct = $productsClass->insertProduct($idHeading, $idIva, $idNewInventory, $idEmpresa, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode);
					if($responseInsertProduct->result == 2){
						$response->result = 2;
						$response->message = "El artículo fue ingresado correctamente.";
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y el artículo no pudo ingresarse en el sistema.";
					}
				}else return $responseGetIva;
			}else{
				$response->result = 0;
				$response->message = "Ya existe un artículo con la descripción '" . $description . "'.";
			}
		}else return $responseGetHeading;

		return $response;
	}

	public function updateProduct($idProduct, $idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $currentSession){
		$response = new \stdClass();
		$productsController = new ctr_products();
		$productsClass = new products();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		$responseGetProduct = $productsClass->getProductById($idProduct, $currentSession->idEmpresa);
		if($responseGetProduct->result == 2){
			$responseGetHeading = $productsClass->getHeadingById($idHeading, $currentSession->idEmpresa);
			if($responseGetHeading->result == 2){
				$responseIsAuthorized = $productsController->authorizedToUse($idIva, $currentSession);
				if($responseIsAuthorized->result == 2){
					$responseGetProductByDescription = $productsClass->getProductByDescription($description, $currentSession->idEmpresa);
					if(($responseGetProductByDescription->result == 2 && $responseGetProductByDescription->objectResult->idArticulo == $idProduct) || ($responseGetProductByDescription->result != 2)){
						$responseUpdateProduct = $productsClass->updateProduct($idHeading, $idIva, null, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $currentSession->idEmpresa);
						if($responseUpdateProduct->result == 2){
							$response->result = 2;
							$response->message = "El producto fue actualizado correctamente.";
						}else return $responseUpdateProduct;
					}else{
						$response->result = 0;
						$response->message = "La descripcíón que intenta asignarle a este artículo ya corresponde a otro distinto y el sistema no permite descripciones duplicadas.";
					}
				}else return $responseIsAuthorized;
			}else return $responseGetHeading;
		}else return $responseGetProduct;
		// }else return $responseGetBusiness;

		return $response;
	}

	public function updateStockProduct($detalle, $currentSession){
		$response = new \stdClass();
		// $productsController = new ctr_products();
		$productsClass = new products();
		$handleDateTimeClass = new handleDateTime();

		// $responseGetBusiness = ctr_users::getBusinesSession();
		// if($responseGetBusiness->result == 2){
		// $responseGetProduct = $productsClass->getProductById($idProduct, $currentSession->idEmpresa);
		// if($responseGetProduct->result == 2){
		// 	$responseGetHeading = $productsClass->getHeadingById($idHeading, $currentSession->idEmpresa);
		// 	if($responseGetHeading->result == 2){
		// 		$responseIsAuthorized = $productsController->authorizedToUse($idIva, $currentSession);
		// 		if($responseIsAuthorized->result == 2){
					$responseGetProductByDescription = $productsClass->getProductByDescription($detalle->nomItem, $currentSession->idEmpresa);
					if($responseGetProductByDescription->result == 2){
						if(!$responseGetProductByDescription->objectResult->idInventario){// Si no tiene inventario creo uno
							$idNewInventory = null;
							$dateInventory = $handleDateTimeClass->getCurrentDateTimeInt();
							$responseInsertInventory = $productsClass->insertInventory($detalle->cantidad, 0, $dateInventory, $currentSession->idEmpresa);
							if($responseInsertInventory->result == 2){
								$idNewInventory = $responseInsertInventory->id;
								$productsClass->setInventoryToProduct($responseGetProductByDescription->objectResult->idArticulo, $idNewInventory, $currentSession->idEmpresa);
							}
						}
						$responseUpdateStock = $productsClass->substractStock($responseGetProductByDescription->objectResult->idInventario, $detalle->cantidad);
						if($responseUpdateStock->result == 2){
							$response->result = 2;
							$response->message = "El stock fue actualizado correctamente.";
						}else return $responseUpdateStock;
					}else{
						$response->result = 0;
						$response->message = "Producto no encontrado.";
					}
		// 		}else return $responseIsAuthorized;
		// 	}else return $responseGetHeading;
		// }else return $responseGetProduct;
		// }else return $responseGetBusiness;

		return $response;
	}

	//busca un producto por el codigo de barra dentro de la base de datos.
	public function addProductByCodeBar($barcode, $idEmpresa){
		$productsClass = new products();
		return $productsClass->addProductByCodeBar($barcode, $idEmpresa);
		// if($responseGetBusiness->result == 2){
		// }else return $responseGetBusiness;
	}

	public function getProductById($idProduct, $idEmpresa){
		$productsClass = new products();
		return $productsClass->getProductById($idProduct, $idEmpresa);
		// if($responseGetBusiness->result == 2){objectResult
		// }else return $responseGetBusiness;
		// $responseGetBusiness = ctr_users::getBusinesSession();
	}
	// UPDATED [MOVED FROM CLASS TO CONTROLLER]
	public function saveProductsInSession ($productsToSave){
		$response = new \stdClass();

		if( isset($_SESSION['systemSession']) ){ //verifico si un usuario tiene una sesion iniciada
			if ( isset($_SESSION['arrayProductsSales']) ){
				array_push($_SESSION['arrayProductsSales'], $productsToSave);
				$response->result = 2;
				$response->data = $_SESSION['arrayProductsSales'];
			}else{
				$_SESSION['arrayProductsSales'][] = $productsToSave;
				$response->result = 2;
				$response->data = $_SESSION['arrayProductsSales'];
			}
		}
		else{
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}

		return $response;
	}

	// UPDATED [MOVED FROM CLASS TO CONTROLLER]
	public function removeProductsSession (){
		$response = new \stdClass();

		if( isset($_SESSION['systemSession']) ){ //verifico si un usuario tiene una sesion iniciada
			if ( isset($_SESSION['arrayProductsSales']) ){//verifico si hay productos en esta sesion
				foreach ($_SESSION['arrayProductsSales'] as $indice => $producto){
					//for( var i = 0; i < arrayDetails.length; i++) {
					if($producto["removed"] == false || $producto["removed"] == "false"){
						//echo "este es el indice ".$indice." este es el value ".$producto["description"]." \n";
						$_SESSION['arrayProductsSales'][$indice]["removed"] = "true";
					}
				}
				//var_dump($_SESSION);
				//exit;
				//sleep(1);
				$response->result = 2;
				$response->message = "Se removieron todos los articulos.";
			}
			else{
				$response->result = 2;
				$response->message = "Actulamente no hay productos guardados.";
			}
		}
		else{
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}


		return $response;
	}

	public function getProductByDescription($description){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetProduct = products::getProductByDescription($description, $responseGetBusiness->idBusiness);
			if($responseGetProduct->result == 2){
				$responseGetIva = others::getValueIVA($responseGetProduct->objectResult->idIva);
				if($responseGetIva->result == 2){
					$responseGetConfigIva = ctr_users::getVariableConfiguration("IVA_INCLUIDO");
					if($responseGetConfigIva->result == 2){
						$responseGetProduct->objectResult->valorIva = $responseGetIva->objectResult->valor;
						$responseGetProduct->objectResult->ivaIncluido = $responseGetConfigIva->configValue;
					}else return $responseGetConfigIva;
				}else return $responseGetIva;
			}
			return $responseGetProduct;
		}else return $responseGetBusiness;
	}

	public function getSuggestionProductByDescription($textToSearch, $idEmpresa){
		$productsClass = new products();
		return $productsClass->getSuggestionProductByDescription($textToSearch, $idEmpresa);
	}

	// public function getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch){
	// 	$responseGetBusiness = ctr_users::getBusinesSession();
	// 	if($responseGetBusiness->result == 2){
	// 		return products::getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch, $responseGetBusiness->idBusiness);
	// 	}else return $responseGetBusiness;
	// }

	//obtiene el cfe del comprobante seleccionado para ingresar todos los detalles a la lista de precio
	//UPDATED
	public function getVoucherDetailJSON($idBusiness, $rut, $tipoCFE, $serieCFE, $numeroCFE, $token){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$productsClass = new products();
		$othersClass = new others();

		$responseRest = $restController->consultarCFE($rut, null, $tipoCFE, $serieCFE, $numeroCFE, "application/json", $token);
		if($responseRest->result == 2){
			$jsonPrintFormat = json_decode($responseRest->cfe->representacionImpresa);

			$inserted = 0;
			$toInsert = 0;

			$includeIVA = $jsonPrintFormat->montosBrutos; // 1 IVA INCLUIDO
			$heading = $productsClass->getHeadingByName("Articulos", $idBusiness);
			$idHeading = $heading->objectResult->idRubro;
			foreach ($jsonPrintFormat->detalles as $key => $itemDetail) {
				if(!is_null($itemDetail->nomItem) && strlen($itemDetail->nomItem) > 2){
					$discount = 0;
					if(!is_null($itemDetail->descRec)){
						if(strcmp(substr($itemDetail->descRec,0,1), "-") == 0)
							$discount = substr($itemDetail->descRec, 1, strlen($itemDetail->descRec));
					}

					$toInsert ++;
					if(is_null($itemDetail->descripcion) ||  strlen($itemDetail->descripcion) == 0)
						$itemDetail->descripcion = null;

					$cost = $othersClass->getCostFromAmountAndIVA($itemDetail->precio, $itemDetail->indFact);
					$itemDetail->nomItem = strtolower($itemDetail->nomItem);
					$responseGetProductByDescription = $productsClass->getProductByDescription($itemDetail->nomItem, $idBusiness);
					if($responseGetProductByDescription->result != 2){
						$responseInsertDetail = $productsClass->insertProduct($idHeading, $itemDetail->indFact, null, $idBusiness, $itemDetail->nomItem, $itemDetail->descripcion, null, $jsonPrintFormat->tipoMoneda, $cost, 0, $itemDetail->precio, $discount, null);
						if($responseInsertDetail->result == 2)
							$inserted++;
					}
				}
			}
			if($toInsert > 0 ){
				if($toInsert == $inserted && $inserted){
					$response->result = 2;
					$response->message = "Los detalles del comprobante seleccionado fueron agregados correctamente.";
				}else{
					$response->result = 0;
					$response->message = "Algunos detalles del comprobante seleccionado no fueron ingresados.";
				}
			}else{
				$response->result = 2;
				$response->message = "No se obtuvieron detalles validos que insertar del comprobante seleccionado.";
			}

			$response->toInsert =  $toInsert;
			$response->inserted = $inserted;
		}else return $responseRest;

		return $response;
	}
}