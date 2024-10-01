<?php

class products{

	public function getMaxProductId($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT MAX(idArticulo) AS maxID FROM articulos where idEmpresa =?", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No hay articulos ingresados hasta el momento.";

		return $responseQuery;
	}

	public function loadPriceList($idBusiness, $lastId, $textToSearch, $heading){
		/*echo $lastId."  ".$textToSearch."  ".$heading;
		return;*/
		if($lastId == 0){
			$responseGetID = products::getMaxProductId($idBusiness);
			if($responseGetID->result == 2)
				$lastId = $responseGetID->objectResult->maxID + 1;
			else return $responseGetID;
		}

		//echo $heading;
		$query = "SELECT * FROM articulos AS A, rubro AS R, indicadores_facturacion AS I";
		$where = " WHERE A.idRubro = R.idRubro AND A.idIva = I.id
					AND A.idEmpresa = ?
					AND A.idArticulo < ?
					AND A.descripcion LIKE '%". $textToSearch ."%'";
		if( $heading ){
			$where .= " AND R.idRubro = " . $heading;
		}
		$orderAndLimit = " ORDER BY A.idArticulo DESC LIMIT 20 ";
		$responseQuery = DataBase::sendQuery($query . $where . $orderAndLimit, array('ii', $idBusiness, $lastId), "LIST");
		if($responseQuery->result == 2){
			$newLastID = $lastId;
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $row) {
				if($newLastID > $row['idArticulo']) $newLastID = $row['idArticulo'];

				$row['importe'] = number_format($row['importe'],2,",",".");
				$row['costo'] = number_format($row['costo'],2,",",".");
				$row['valor'] = number_format($row['valor'],2,",",".");
				$row['descuento'] = number_format($row['descuento'],2,",",".");

				if(is_null($row['detalle']) || $row['detalle'] == "")
					$row['detalle'] = "";

				if(is_null($row['marca']) || $row['marca'] == "")
					$row['marca'] = "";

				if(strcmp($row['moneda'], "UYU") == 0)
					$row['monedaSimbol'] = '$';
				else
					$row['monedaSimbol'] = 'U$S';

				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastId = $newLastID;
		}else if($responseQuery->result == 1) $responseQuery->message = "No se encontraron artículos para listar.";

		return $responseQuery;
	}

	public function getSuggestionProductByDescription($textToSearch, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM articulos AS A, rubro AS R WHERE A.idRubro = R.idRubro AND A.idEmpresa = ? AND A.descripcion LIKE '%". $textToSearch ."%' ORDER BY A.descripcion DESC LIMIT 7", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $row){
				//$row['importe'] = number_format($row['importe'],2, ",",".");
				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1) $responseQuery->message = "No se encontraron sugerencias en base a las facturas realizadas.";

		return $responseQuery;
	}

	public function getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM articulos AS A, rubro AS R WHERE A.idRubro = R.idRubro AND A.idEmpresa = ? AND a.moneda = ? AND A.descripcion LIKE '". $textToSearch ."%' ORDER BY A.descripcion DESC LIMIT 7", array('is', $idBusiness, $coinToSearch), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $row){
				$row['importe'] = number_format($row['importe'],2, ",",".");
				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1) $responseQuery->message = "No se encontraron sugerencias en base a las facturas realizadas.";

		return $responseQuery;
	}

	public function getHeading($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM rubro WHERE idEmpresa = ?", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "El rubro seleccionado no fue encontrado en la base de datos.";

		return $responseQuery;
	}

	public function getHeadingById($idHeading, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM rubro WHERE idRubro = ? AND idEmpresa = ?", array('ii', $idHeading, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El rubro seleccionado no fue encontrado en la base de datos.";

		return $responseQuery;
	}

	public function getHeadingByName($nameHeading, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM rubro WHERE rubro = ? AND idEmpresa = ?", array('si', $nameHeading, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un rubro con el nombre '". $nameHeading ."' en la base de datos.";

		return $responseQuery;
	}

	public function insertHeading($nameHeading, $idBusiness){
		return DataBase::sendQuery("INSERT INTO rubro(rubro, idEmpresa) VALUES (?,?)", array('si', $nameHeading, $idBusiness), "BOOLE");
	}

	public function updateHeading($nameBrand, $idHeading, $idBusiness){
		return DataBase::sendQuery("UPDATE rubro SET rubro= ? WHERE idRubro = ? AND idEmpresa = ?", array('sii', $nameHeading, $idHeading, $idBusiness), "BOOLE");
	}

	public function deleteHeading($idHeading, $idBusiness){
		return DataBase::sendQuery("DELETE FROM rubro WHERE idRubro = ? AND idBusiness = ?", array('ii', $idHeading, $idBusiness), "BOOLE");
	}

	public function getProductByDescription($description, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM articulos WHERE descripcion = ? AND idEmpresa = ?", array('si', $description, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un artículo con la descripción '". $description ."'.";

		return $responseQuery;
	}

	public function addProductByCodeBar($barcode, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM articulos WHERE codigoBarra = ? AND idEmpresa = ?", array('si', $barcode, $idBusiness), "LIST");
		/*if($responseQuery->result == 2){
			$responseQueryGetIva = others::getValueIVA($responseQuery->objectResult->idIva);
			if($responseQueryGetIva->result == 2)
				$responseQuery->objectResult->valueIVA = $responseQueryGetIva->objectResult->valor;
		}else if($responseQuery->result == 1) $responseQuery->message = "El codigo de barra escaneado no corresponde a un artículo del sistema.";*/

		if($responseQuery->result == 1)
			$responseQuery->message = "El codigo de barra escaneado no corresponde a un artículo del sistema.";

		return $responseQuery;
	}

	public function getProductById($idProduct, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM articulos WHERE idArticulo = ? AND idEmpresa = ?", array('ii', $idProduct, $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseQueryGetIva = others::getValueIVA($responseQuery->objectResult->idIva);
			if($responseQueryGetIva->result == 2)
				$responseQuery->objectResult->valueIVA = $responseQueryGetIva->objectResult->valor;
		}else if($responseQuery->result == 1) $responseQuery->message = "El identificador ingresado no corresponde a un artículo del sistema.";

		return $responseQuery;
	}

	public function insertProduct($idHeading, $idIva, $idInventory, $idBusiness, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode){
		return DataBase::sendQuery("INSERT INTO articulos(idRubro, idIva, idInventario, idEmpresa, descripcion, detalle, marca, moneda, costo, coeficiente, importe, descuento, codigoBarra) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", array('iiiissssdddds', $idHeading, $idIva, $idInventory, $idBusiness, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode), "BOOLE");
	}

	public function updateProduct($idHeading, $idIva, $idInventory, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $idBusiness){
		return DataBase::sendQuery("UPDATE articulos SET idRubro= ?, idIva= ?, idInventario= ?, descripcion= ?, detalle= ?, marca= ?, moneda= ?, costo= ?, coeficiente= ?, descuento = ?, importe = ?, codigoBarra= ? WHERE idArticulo = ? AND idEmpresa = ?", array('iiissssddddsii', $idHeading, $idIva, $idInventory, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $idBusiness), "BOOLE");
	}

	public function deleteProduct($idProduct, $idBusiness){
		return DataBase::sendQuery("DELETE FROM articulos WHERE idArticulo = ? AND idEmpresa = ?", array('ii', $idProduct, $idBusiness), "BOOLE");
	}

	public function insertInventory($inventory, $minInventory, $dateInventory, $idBusiness){
		return DataBase::sendQuery("INSERT INTO inventario(inventario, inventarioMinimo, fechaInventario, idEmpresa) VALUES (?,?,?,?)", array('iiii', $inventory, $minInventory, $dateInventory, $idBusiness), "BOOLE");
	}

	public function updateInventory($inventory, $minInventory, $dateInventory, $idInventory, $idBusiness){
		return DataBase::sendQuery("UPDATE inventario SET inventario = ?, inventarioMinimo = ?, fechaInventario = ? WHERE idInventario = ? AND idEmpresa = ?", array('iiiii', $inventory, $minInventory, $dateInventory, $idInventory, $idBusiness), "BOOLE");
	}

	public function deleteInventory($idInventory, $idBusiness){
		return DataBase::sendQuery("DELETE FROM inventario WHERE idInventario = ? AND idEmpresa = ?", array('ii', $idInventory, $idBusiness), "BOOLE");
	}

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

	public function removedProductsSession (){
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
}