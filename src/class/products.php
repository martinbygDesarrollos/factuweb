<?php

class products{

	public function getMaxProductId($idBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT MAX(idArticulo) AS maxID FROM articulos where idEmpresa =?", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No hay articulos ingresados hasta el momento.";

		return $responseQuery;
	}
	//UPDATED
	public function loadPriceList($idBusiness, $lastId, $textToSearch, $heading){
		/*echo $lastId."  ".$textToSearch."  ".$heading;
		return;*/
		$dbClass = new DataBase();
		$productsClass = new products();
		if($lastId == 0){
			$responseGetID = $productsClass->getMaxProductId($idBusiness);
			if($responseGetID->result == 2)
				$lastId = $responseGetID->objectResult->maxID + 1;
			else return $responseGetID;
		}

		//echo $heading;
		// $query = "SELECT * FROM articulos AS A, rubro AS R, indicadores_facturacion AS I";
		// $where = " WHERE A.idRubro = R.idRubro AND A.idIva = I.id
		// 			AND A.idEmpresa = ?
		// 			AND A.idArticulo < ?
		// 			AND A.descripcion LIKE '%". $textToSearch ."%'";
		// if( $heading ){
		// 	$where .= " AND R.idRubro = " . $heading;
		// }
		// $orderAndLimit = " ORDER BY A.idArticulo DESC LIMIT 20 ";

		$query = "SELECT A.*, R.*, I.*, 
				INV.inventario, 
				INV.inventarioMinimo, 
				INV.fechaInventario
		FROM articulos AS A
		JOIN rubro AS R ON A.idRubro = R.idRubro
		JOIN indicadores_facturacion AS I ON A.idIva = I.id
		LEFT JOIN inventario AS INV ON A.idInventario = INV.idInventario";

		$where = " WHERE A.idEmpresa = ? 
				AND A.idArticulo < ? 
				AND (A.descripcion LIKE '%". $textToSearch ."%' OR A.codigoBarra LIKE '%". $textToSearch ."%')";
		if( $heading ){
  			$where .= " AND R.idRubro = " . $heading;
		}
		$orderAndLimit = " ORDER BY A.idArticulo DESC LIMIT 20";

		error_log($query . $where . $orderAndLimit . "- EMPRESA: '" . $idBusiness . "' - LAST ID: '" . $lastId . "'");

		$responseQuery = $dbClass->sendQuery($query . $where . $orderAndLimit, array('ii', $idBusiness, $lastId), "LIST");
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

	public function getSuggestionProductByDescription($textToSearch, $idEmpresa){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM articulos AS A, rubro AS R WHERE A.idRubro = R.idRubro AND A.idEmpresa = ? AND A.descripcion LIKE '%". $textToSearch ."%' ORDER BY A.descripcion DESC LIMIT 7", array('i', $idEmpresa), "LIST");
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

	// public function getSuggestionProductByDescriptionAndCoin($textToSearch, $coinToSearch, $idBusiness){
	// 	$responseQuery = DataBase::sendQuery("SELECT * FROM articulos AS A, rubro AS R WHERE A.idRubro = R.idRubro AND A.idEmpresa = ? AND a.moneda = ? AND A.descripcion LIKE '". $textToSearch ."%' ORDER BY A.descripcion DESC LIMIT 7", array('is', $idBusiness, $coinToSearch), "LIST");
	// 	if($responseQuery->result == 2){
	// 		$arrayResult = array();
	// 		foreach ($responseQuery->listResult as $key => $row){
	// 			$row['importe'] = number_format($row['importe'],2, ",",".");
	// 			$arrayResult[] = $row;
	// 		}
	// 		$responseQuery->listResult = $arrayResult;
	// 	}else if($responseQuery->result == 1) $responseQuery->message = "No se encontraron sugerencias en base a las facturas realizadas.";

	// 	return $responseQuery;
	// }

	public function getHeading($idBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM rubro WHERE idEmpresa = ?", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "Ningun rubro en el sistema aún";

		return $responseQuery;
	}
	//UPDATED
	public function insertDefaultRubro($idBusiness){
		$dbClass = new DataBase();
		$productsClass = new products();

		$responseQuery = $dbClass->sendQuery("SELECT * FROM rubro WHERE rubro = ? AND idEmpresa = ?", array('si', "Varios", $idBusiness), "OBJECT");
		if($responseQuery->result != 2){
			return $responseQuery2 = $productsClass->insertHeading("Varios", $idBusiness);
		}
		return $responseQuery;
	}
	//UPDATED
	public function getHeadingById($idHeading, $idBusiness){
		$dbClass = new DataBase();

		$responseQuery = $dbClass->sendQuery("SELECT * FROM rubro WHERE idRubro = ? AND idEmpresa = ?", array('ii', $idHeading, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El rubro seleccionado no fue encontrado en la base de datos.";

		return $responseQuery;
	}
	//UPDATED
	public function getHeadingByName($nameHeading, $idBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM rubro WHERE rubro = ? AND idEmpresa = ?", array('si', $nameHeading, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un rubro con el nombre '". $nameHeading ."' en la base de datos.";

		return $responseQuery;
	}
	//UPDATED
	public function insertHeading($nameHeading, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO rubro(rubro, idEmpresa) VALUES (?,?)", array('si', $nameHeading, $idBusiness), "BOOLE");
	}

	public function updateHeading($nameBrand, $idHeading, $idBusiness){
		return DataBase::sendQuery("UPDATE rubro SET rubro= ? WHERE idRubro = ? AND idEmpresa = ?", array('sii', $nameHeading, $idHeading, $idBusiness), "BOOLE");
	}
	//UPDATED [NEW]
	public function substractStock($id, $cantidad){
		$cantidad = intval($cantidad);
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE inventario SET inventario = inventario - $cantidad WHERE idInventario = ?", array('i', $id), "BOOLE");
	}
	//UPDATED [NEW]
	public function increaseStock($id, $cantidad){
		$cantidad = intval($cantidad);
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE inventario SET inventario = inventario + $cantidad WHERE idInventario = ?", array('i', $id), "BOOLE");
	}

	public function deleteHeading($idHeading, $idBusiness){
		return DataBase::sendQuery("DELETE FROM rubro WHERE idRubro = ? AND idBusiness = ?", array('ii', $idHeading, $idBusiness), "BOOLE");
	}
	//UPDATED
	public function getProductByDescription($description, $idEmpresa){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM articulos WHERE descripcion = ? AND idEmpresa = ?", array('si', $description, $idEmpresa), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un artículo con la descripción '". $description ."'.";

		return $responseQuery;
	}

	public function addProductByCodeBar($barcode, $idEmpresa){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT A.*,R.rubro FROM articulos as A LEFT JOIN rubro AS R ON A.idRubro = R.idRubro WHERE A.codigoBarra = ? AND A.idEmpresa = ?", array('si', $barcode, $idEmpresa), "LIST");
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
		$dbClass = new DataBase();
		$othersClass = new others();
		error_log("PRODUCTO: " . $idProduct . " de EMPRESA: " . $idBusiness);
		$responseQuery = $dbClass->sendQuery("SELECT A.*, 
												INV.inventario, 
												INV.inventarioMinimo, 
												INV.fechaInventario
											FROM articulos as A 
											LEFT JOIN inventario AS INV ON A.idInventario = INV.idInventario 
											WHERE A.idArticulo = ? AND A.idEmpresa = ?", array('ii', $idProduct, $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseQueryGetIva = $othersClass->getValueIVA($responseQuery->objectResult->idIva);
			if($responseQueryGetIva->result == 2){
				$responseQuery->objectResult->valueIVA = $responseQueryGetIva->objectResult->valor;
				return $responseQuery;
			}
		} else if($responseQuery->result == 1) {
			$responseQuery->message = "El identificador ingresado no corresponde a un artículo del sistema.";
		}
		return $responseQuery;
	}
	//UPDATED
	public function insertProduct($idHeading, $idIva, $idInventory, $idBusiness, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO articulos(idRubro, idIva, idInventario, idEmpresa, descripcion, detalle, marca, moneda, costo, coeficiente, importe, descuento, codigoBarra) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", array('iiiissssdddds', $idHeading, $idIva, $idInventory, $idBusiness, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $amount, $discount, $barcode), "BOOLE");
	}

	public function updateProduct($idHeading, $idIva, $idInventory, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE articulos SET idRubro= ?, idIva= ?, idInventario= ?, descripcion= ?, detalle= ?, marca= ?, moneda= ?, costo= ?, coeficiente= ?, descuento = ?, importe = ?, codigoBarra= ? WHERE idArticulo = ? AND idEmpresa = ?", array('iiissssddddsii', $idHeading, $idIva, $idInventory, $description, $detail, $brand, $typeCoin, $cost, $coefficient, $discount, $amount, $barcode, $idProduct, $idBusiness), "BOOLE");
	}
	//UPDATED [NEW]
	public function setInventoryToProduct($idProduct, $idInventory, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE articulos SET idInventario = ? WHERE idArticulo = ? AND idEmpresa = ?", array('iii', $idInventory, $idProduct, $idBusiness), "BOOLE");
	}
	//UPDATED
	public function deleteProduct($idProduct, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("DELETE FROM articulos WHERE idArticulo = ? AND idEmpresa = ?", array('ii', $idProduct, $idBusiness), "BOOLE");
	}
	//UPDATED
	public function insertInventory($inventory, $minInventory, $dateInventory, $idEmpresa){
		$inventory = ($inventory === "" || $inventory === null) ? 1 : $inventory;
		$minInventory = ($minInventory === "" || $minInventory === null) ? 0 : $minInventory;
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO inventario(inventario, inventarioMinimo, fechaInventario, idEmpresa) VALUES (?,?,?,?)", array('iisi', $inventory, $minInventory, $dateInventory, $idEmpresa), "BOOLE");
	}
	//UPDATED
	public function updateInventory($inventory, $minInventory, $dateInventory, $idInventory, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE inventario SET inventario = ?, inventarioMinimo = ?, fechaInventario = ? WHERE idInventario = ? AND idEmpresa = ?", array('iisii', $inventory, $minInventory, $dateInventory, $idInventory, $idBusiness), "BOOLE");
	}
	//UPDATED
	public function deleteInventory($idInventory, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("DELETE FROM inventario WHERE idInventario = ? AND idEmpresa = ?", array('ii', $idInventory, $idBusiness), "BOOLE");
	}

	
}