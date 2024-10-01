<?php

class providers{
	
	public function insertProvider($rut, $businessName, $address, $phoneNumber, $email, $idBusiness){
		return DataBase::sendQuery("INSERT INTO proveedores(rut, razonSocial, direccion, telefono, email, idEmpresa) VALUES (?,?,?,?,?,?)", array('sssssi', $rut, $businessName, $address, $phoneNumber, $email, $idBusiness), "BOOLE");
	}

	public function modifyProvider($idProvider, $nameBusiness, $address, $phoneNumber, $email){
		return DataBase::sendQuery("UPDATE proveedores SET razonSocial = ?, direccion = ?, telefono = ?, email = ? WHERE idProveedor = ?", array('ssssi', $nameBusiness, $address, $phoneNumber, $email, $idProvider), "BOOLE");
	}

	public function getProvider($rut, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM proveedores WHERE rut = ? AND idEmpresa = ?", array('si', $rut, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un proveedor con el rut '" . $rut . "' en la base de datos.";
		return $responseQuery;
	}

	public function getProviderWithId($idProvider){
		$responseQuery = DataBase::sendQuery("SELECT * FROM proveedores WHERE idProveedor = ?", array('i', $idProvider), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El id del proveedor seleccionado no fue encontrado en la base de datos.";
		return $responseQuery;
	}

	public function getLastId(){
		$responseQuery = DataBase::sendQuery("SELECT MAX(idProveedor) AS lastId FROM proveedores", null, "OBJECT");
		if($responseQuery->result == 2) return ($responseQuery->objectResult->lastId + 1);
	}

	public function getProviders($lastId, $textToSearch, $withBalance, $myBusiness){
		$limitPage = 7;
		if($lastId == 0) {
			$lastId = providers::getLastId();
			$limitPage = 14;
		}

		$sqlToSend = "SELECT * FROM proveedores AS P WHERE P.idEmpresa = ? ";

		if($withBalance == "YES"){
			$sqlToSend .= "AND ((SELECT COALESCE(SUM(total),0) FROM comprobantes_recibidos AS C WHERE P.idProveedor = C.idProveedor AND moneda = 'UYU' AND formaPago = 2 AND isCobranza = 0 AND (tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')) <> (SELECT COALESCE(SUM(total),0) FROM comprobantes_recibidos AS C WHERE P.idProveedor = C.idProveedor AND moneda = 'UYU' AND (isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2'))) OR ((SELECT COALESCE(SUM(total),0) FROM comprobantes_recibidos AS C WHERE P.idProveedor = C.idProveedor AND moneda = 'USD' AND formaPago = 2 AND isCobranza = 0 AND (tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')) <> (SELECT COALESCE(SUM(total),0) FROM comprobantes_recibidos AS C WHERE P.idProveedor = C.idProveedor AND moneda = 'USD' AND (isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2'))))) ";
		}

		if(!is_null($textToSearch)){
			if(ctype_digit($textToSearch))
				$sqlToSend .= " AND rut LIKE '%" . $textToSearch . "%' ";
			else
				$sqlToSend .= " AND razonSocial LIKE '%" . $textToSearch . "%' ";
		}

		$sqlToSend .= " AND P.idProveedor < ? ORDER BY P.idProveedor DESC LIMIT " . $limitPage;

		$responseQuery = DataBase::sendQuery($sqlToSend, array('ii', $myBusiness, $lastId), "LIST");
		if($responseQuery->result == 2){
			$newLastId = $lastId;
			$arrayResult = array();
			foreach($responseQuery->listResult AS $key => $value){
				if($newLastId > $value['idProveedor']) $newLastId = $value['idProveedor'];

				$value['razonSocial'] = utils::stringToLowerWithFirstCapital($value['razonSocial']);
				$value['direccion'] = utils::stringToLowerWithFirstCapital($value['direccion']);
				$value['email'] = utils::stringToLower($value['email']);

				$arrayResult[] = $value;
			}
			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastId = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay proveedores que mostrar.";
		}
		return $responseQuery;
	}

	public function getSuggestionProviders($suggestionProvider, $myBusiness){
		$sql = null;
		if(ctype_digit($suggestionProvider))
			$sql = "SELECT DISTINCT * FROM proveedores WHERE idEmpresa = ? AND rut LIKE '%" . $suggestionProvider . "%'";
		else $sql = "SELECT DISTINCT * FROM proveedores WHERE idEmpresa = ? AND razonSocial LIKE '%" . $suggestionProvider . "%'";

		$responseQuery = DataBase::sendQuery($sql, array('i', $myBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach($responseQuery->listResult AS $key => $value){
				$newRow = array();
				$newRow['name'] = $value['razonSocial'];
				$newRow['document'] = $value['rut'];
				$arrayResult[] = $newRow;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay proveedores que mostrar con la sugerencia de texto ingresada.";
		}
		return $responseQuery;
	}



	public function getProvidersToExport($idBusiness, $date){

		$dataBase = new DataBase();

		$whereDate = "";
		if ( !isset($date) ){
			$whereDate = "";
		}else{
			$whereDate = " fecha <= '".$date."' AND ";
		}

		$sqlToSend = "SELECT idProveedor, rut, razonSocial FROM proveedores AS P
			WHERE P.idEmpresa = ? AND
				(
			        (	SELECT COALESCE(SUM(total),0)
			         	FROM comprobantes_recibidos AS C
			         	WHERE P.idProveedor = C.idProveedor AND
			         		idReceptor = ".$idBusiness." AND
			         		moneda = 'UYU' AND
			         		formaPago = 2 AND
			         		isCobranza = 0 AND
			         		".$whereDate."
			         		(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
			        )
			        <>
			    	(	SELECT COALESCE(SUM(total),0)
			         	FROM comprobantes_recibidos AS C
			         	WHERE P.idProveedor = C.idProveedor AND
			         		idReceptor = ".$idBusiness." AND
			         		moneda = 'UYU' AND
			         		".$whereDate."
			         		(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2') )
			        )
				OR

		            (	SELECT COALESCE(SUM(total),0)
		             	FROM comprobantes_recibidos AS C
		             	WHERE P.idProveedor = C.idProveedor AND
		             		idReceptor = ".$idBusiness." AND
		             		moneda = 'USD' AND
		             		formaPago = 2 AND
		             		isCobranza = 0 AND
		             		".$whereDate."
		             		(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
		            )
		            <>
		            (	SELECT COALESCE(SUM(total),0)
		             	FROM comprobantes_recibidos AS C
		             	WHERE P.idProveedor = C.idProveedor AND
		             		idReceptor = ".$idBusiness." AND
		             		moneda = 'USD' AND
		             		".$whereDate."
		             		(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2')	)
		            )

			    )";


		$responseQuery = $dataBase->sendQuery($sqlToSend, array('i', $idBusiness), "LIST");
		if($responseQuery->result == 1){
			$responseQuery->listResult = array();
			$responseQuery->message = "Actualmente no hay proveedores que mostrar.";
		}
		return $responseQuery;

	}
}