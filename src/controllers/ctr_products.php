<?php

require_once '../src/class/products.php';
require_once '../src/class/others.php';
require_once '../src/utils/handle_date_time.php';

require_once 'ctr_users.php';
require_once 'rest/ctr_rest.php';

class ctr_products{

	//Controla si la empresa esta autorizada para utilizar el rut seleccionado.
	public function authorizedToUse($idIva){
		$response = new \stdClass();

		$responseGetIvas = ctr_vouchers::getIVAsAllowed();
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

	public function deleteProduct($idProduct){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseDeleteProduct = products::deleteProduct($idProduct, $responseGetBusiness->idBusiness);
			if($responseDeleteProduct->result == 2){
				$response->result = 2;
				$response->message = "El artículo fue borrado correctamente del sistema.";
			}else return $responseDeleteProduct;
		}else return $responseGetBusiness;

		return $response;
	}

	public function loadPriceList($lastId, $textToSearch, $heading){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return products::loadPriceList($responseGetBusiness->idBusiness, $lastId, $textToSearch, $heading);
		}else return $responseGetBusiness;

		return $response;
	}

	public function insertHeading($nameHeading){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetHeadingByName = products::getHeadingByName($nameHeading, $responseGetBusiness->idBusiness);
			if($responseGetHeadingByName->result != 2){
				$responseInsertHeading = products::insertHeading($nameHeading, $responseGetBusiness->idBusiness);
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
		}else return $responseGetBusiness;

		return $response;
	}

	public function insertProduct($idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $barcode, $inventory, $minInventory, $amount){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetHeading = products::getHeadingById($idHeading, $responseGetBusiness->idBusiness);
			if($responseGetHeading->result == 2){
				$responseGetProductByDescription = products::getProductByDescription($description, $responseGetBusiness->idBusiness);
				if($responseGetProductByDescription->result != 2){
					$responseGetIva = others::getValueIVA($idIva);
					if($responseGetIva->result == 2){
						$idNewInventory = null;
						if(!is_null($inventory) && !is_null($minInventory)){
							$dateInventory = handleDateTime::getCurrentDateTimeInt();
							$responseInsertInventory = products::insertInventory($inventory, $minInventory, $dateInventory, $responseGetBusiness->idBusiness);
							if($responseInsertInventory->result == 2)
								$idNewInventory = $responseInsertInventory->id;
							else return $responseInsertInventory;
						}
						$responseInsertProduct = products::insertProduct($idHeading, $idIva, $idNewInventory, $responseGetBusiness->idBusiness, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode);
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
		}else return $responseGetBusiness;

		return $response;
	}

	public function updateProduct($idProduct, $idHeading, $idIva, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetProduct = products::getProductById($idProduct, $responseGetBusiness->idBusiness);
			if($responseGetProduct->result == 2){
				$responseGetHeading = products::getHeadingById($idHeading, $responseGetBusiness->idBusiness);
				if($responseGetHeading->result == 2){
					$responseIsAuthorized = ctr_products::authorizedToUse($idIva);
					if($responseIsAuthorized->result == 2){
						$responseGetProductByDescription = products::getProductByDescription($description, $responseGetBusiness->idBusiness);
						if(($responseGetProductByDescription->result == 2 && $responseGetProductByDescription->objectResult->idArticulo == $idProduct) || ($responseGetProductByDescription->result != 2)){
							$responseUpdateProduct = products::updateProduct($idHeading, $idIva, null, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $responseGetBusiness->idBusiness);
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
		}else return $responseGetBusiness;

		return $response;
	}

	//busca un producto por el codigo de barra dentro de la base de datos.
	public function addProductByCodeBar($barcode){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return products::addProductByCodeBar($barcode, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function getProductById($idProduct){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return products::getProductById($idProduct, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
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

	public function getSuggestionProductByDescription($textToSearch){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return products::getSuggestionProductByDescription($textToSearch, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return products::getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	//obtiene el cfe del comprobante seleccionado para ingresar todos los detalles a la lista de precio
	public function getVoucherDetailJSON($idBusiness, $rut, $tipoCFE, $serieCFE, $numeroCFE){
		$response = new \stdClass();

		$responseRest = ctr_rest::consultarCFE($rut, null, $tipoCFE, $serieCFE, $numeroCFE, "application/json");
		if($responseRest->result == 2){
			$jsonPrintFormat = json_decode($responseRest->cfe->representacionImpresa);

			$inserted = 0;
			$toInsert = 0;

			$includeIVA = $jsonPrintFormat->montosBrutos; // 1 IVA INCLUIDO
			$heading = products::getHeadingByName("Articulos", $idBusiness);
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

					$cost = others::getCostFromAmountAndIVA($itemDetail->precio, $itemDetail->indFact);
					$itemDetail->nomItem = strtolower($itemDetail->nomItem);
					$responseGetProductByDescription = products::getProductByDescription($itemDetail->nomItem, $idBusiness);
					if($responseGetProductByDescription->result != 2){
						$responseInsertDetail = products::insertProduct($idHeading, $itemDetail->indFact, null, $idBusiness, $itemDetail->nomItem, $itemDetail->descripcion, null, $jsonPrintFormat->tipoMoneda, $cost, 0, $itemDetail->precio, $discount, null);
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