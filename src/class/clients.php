<?php

class clients{
	//UPDATED
	public function getBillableClients($currentSession, $dateEmitted){
		$userControlle = new ctr_users();
		$idBusiness = $currentSession->idEmpresa;
		$dbClass = new DataBase();
		//$currentMonth = handleDateTime::getCurrentMonth();
		//$nextYearMonth = handleDateTime::getNextYearMonth();
		//configuracion  de la fecha de factura
		$responseConfiguration = $userControlle->getVariableConfiguration("SUFIJO_NOMBRE_SERVICIO_FACTURA", $currentSession);
		$dateToControl = 0;
		if ($responseConfiguration && $responseConfiguration->result == 2){
			if( strcmp($responseConfiguration->configValue, "FECHA_ANTERIOR") == 0)
				$dateToControl = date('Ymd',strtotime ('-1 month' , strtotime($dateEmitted)));
			if( strcmp($responseConfiguration->configValue, "FECHA_ACTUAL") == 0)
				$dateToControl = date('Ymd',strtotime($dateEmitted));
			if( strcmp($responseConfiguration->configValue, "FECHA_POSTERIOR") == 0)
				$dateToControl = date('Ymd',strtotime ('+1 month' , strtotime($dateEmitted)));
		}

		$responseQuery = $dbClass->sendQuery("SELECT * FROM clientes WHERE id IN ( SELECT CS.idCliente FROM cuotas_servicios AS CS, servicios AS S WHERE CS.idServicio = S.idServicio AND S.activo = 1 AND CS.vigente = 1 AND S.idEmpresa = ? AND (CS.fechaUltimaFactura iS NULL OR CS.fechaUltimaFactura NOT LIKE '" . $dateToControl . "%'))", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontraron clientes con cuotas de servicio facturables.";

		return $responseQuery;
	}
	//UPDATED
	public function getClientWithId($idReceiver){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM clientes WHERE id = ?", array('i', $idReceiver), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El id del cliente seleccionado no fue encontrado en la base de datos.";
		return $responseQuery;
	}

	public function updateClient($nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $identifier){
		return DataBase::sendQuery("UPDATE clientes SET nombreReceptor = ?, localidad = ?, departamento = ?, correo = ?, celular = ?, direccion = ? WHERE id = ?", array('ssssssi', $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $identifier), "BOOLE");
	}
	//UPDATED
	public function updateClientByDocument($documentReceiver, $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE clientes SET nombreReceptor = ?, localidad = ?, departamento = ?, correo = ?, celular = ?, direccion = ? WHERE docReceptor = ? AND idEmpresa = ?", array('ssssssii', $nameReceiver, $locality, $department, $email, $numberMobile, $addressReceiver, $documentReceiver, $idBusiness), "BOOLE");
	}

	public function getLastId($myBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT MAX(id) AS lastID FROM clientes WHERE idEmpresa = ?", array('i', $myBusiness), "OBJECT");
		if($responseQuery->result == 2) return ($responseQuery->objectResult->lastID + 1);
	}
	//UPDATED
	public function getListClientsView($lastId, $textToSearch, $onlyWithBalance, $myBusiness){
		$dbClass = new DataBase();
		$utilsClass = new utils();
		$clientClass = new clients();
		if($lastId == 0){
			$lastId = $clientClass->getLastId($myBusiness);
		}

		$sqlToSend = "SELECT * FROM clientes AS Cli WHERE Cli.idEmpresa = ? ";

		if($onlyWithBalance == "YES"){
			$sqlToSend .= " AND (

				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						moneda = 'UYU' AND
						formaPago = 2 AND
						isAnulado = 0 AND
						isCobranza = 0 AND
						(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
				)
				<>
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						isAnulado = 0 AND
						moneda = 'UYU' AND
						(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2')
				)

			)
			OR
			(

				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						moneda = 'USD' AND
						formaPago = 2 AND
						isAnulado = 0 AND
						isCobranza = 0 AND
						(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
				)
				<>
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						isAnulado = 0 AND
						moneda = 'USD' AND
						(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2'))
				)
			)
		) ";
		}

		if(!is_null($textToSearch)){
			if(ctype_digit($textToSearch))
				$sqlToSend .= " AND docReceptor LIKE '%" . $textToSearch . "%' ";
			else
				$sqlToSend .= " AND nombreReceptor LIKE '%" . $textToSearch . "%' ";
		}

		$sqlToSend .= " AND Cli.id < ? ORDER BY Cli.id DESC LIMIT 20";

		$responseQuery = $dbClass->sendQuery($sqlToSend, array('ii', $myBusiness, $lastId), "LIST");

		if($responseQuery->result == 2) {
			if(sizeof($responseQuery->listResult) > 0){
				$newLastId = $lastId;
				$arrayResult = array();
				foreach($responseQuery->listResult as $key => $value){
					if($newLastId > $value['id']) $newLastId = $value['id'];


					if(is_null($value['departamento'])) $value['departamento'] = " ";
					if(is_null($value['localidad'])) $value['localidad'] = " ";
					if(is_null($value['celular'])) $value['celular'] = " ";
					if(is_null($value['correo'])) $value['correo'] = " ";

					$value['nombreReceptor'] = $utilsClass->stringToLowerWithFirstCapital($value['nombreReceptor']);
					$value['direccion'] = $utilsClass->stringToLowerWithFirstCapital($value['direccion']);
					$value['correo'] = $utilsClass->stringToLower($value['correo']);

					$arrayResult[] = $value;
				}
				$responseQuery->listResult = $arrayResult;
				$responseQuery->lastId = $newLastId;
			}
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay clientes que listar.";
		}

		return $responseQuery;
	}


	//misma funcion que getListClientsView pero no se filtra por nombre, no hay lim por paginacion
	//UPDATED
	public function getListDeudoresToExport($myBusiness, $dateTo){
		$dbClass = new DataBase();
		$whereDate = "";
		if ( !isset($dateTo) ){
			$whereDate = "";
		}else{
			$whereDate = " fecha <= '".$dateTo."' AND ";
		}

		$sqlToSend = "SELECT docReceptor, nombreReceptor, id  FROM clientes AS Cli ";

		$sqlToSend .= "
			WHERE (
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						moneda = 'UYU' AND
						formaPago = 2 AND
						isAnulado = 0 AND
						isCobranza = 0 AND
						".$whereDate."
						(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
				)
				<>
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						isAnulado = 0 AND
						moneda = 'UYU' AND
						".$whereDate."
						(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2')
				)

			)";

		$sqlToSend .= "
			OR
			(
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						moneda = 'USD' AND
						formaPago = 2 AND
						isAnulado = 0 AND
						isCobranza = 0 AND
						".$whereDate."
						(tipoCFE LIKE '__1' OR tipoCFE LIKE '__3')
				)
				<>
				(
					SELECT COALESCE(SUM(total),0)
					FROM comprobantes
					WHERE Cli.id = comprobantes.idCliente AND
						comprobantes.idEmisor = ".$myBusiness." AND
						isAnulado = 0 AND
						moneda = 'USD' AND
						".$whereDate."
						(isCobranza = 1 OR (formaPago = 2 AND tipoCFE LIKE '__2'))
				)
			)
		) ";

		$sqlToSend .= "  ORDER BY Cli.nombreReceptor ASC ";
		$responseQuery = $dbClass->sendQuery($sqlToSend, array('i', $myBusiness), "LIST");
		if($responseQuery->result == 1){
			$responseQuery->listResult = array();
			$responseQuery->message = "Actualmente no hay clientes que listar.";
		}
		return $responseQuery;
	}
	//UPDATED
	public function getClient($documentClient, $myBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM clientes WHERE docReceptor = ? AND idEmpresa = ?", array('si', $documentClient, $myBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un cliente con el número de documento '" . $documentClient . "' en la base de datos.";
		return $responseQuery;
	}
	//UPDATED
	public function insertClient($documentClient, $nameBusiness, $address, $locality, $city, $email, $mobileNumber, $myBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO clientes(docReceptor, nombreReceptor, direccion, localidad, departamento, correo, celular, idEmpresa) VALUES (?,?,?,?,?,?,?,?)", array('sssssssi', $documentClient, $nameBusiness, $address, $locality, $city, $email, $mobileNumber, $myBusiness), "BOOLE");
	}

	//buscar clientes de manera local, por texto o documento que sean de la empresa en sesión
	//UPDATED
	public function getClientsForModal($suggestionClient, $myBusiness){
		$dbClass = new DataBase();

		$sql = null;
		if(ctype_digit($suggestionClient))
			$sql = "SELECT DISTINCT docReceptor, nombreReceptor FROM clientes WHERE docReceptor LIKE '%" . $suggestionClient . "%' AND idEmpresa = ? GROUP BY docReceptor";
		else
			$sql = "SELECT DISTINCT docReceptor, nombreReceptor FROM clientes WHERE nombreReceptor LIKE '%" . $suggestionClient . "%' AND docReceptor IS NOT NULL AND idEmpresa = ? GROUP BY docReceptor";

		$responseQuery = $dbClass->sendQuery($sql, array('i', $myBusiness), "LIST");
		if($responseQuery->result == 2) {
			$arrayResult = array();
			foreach($responseQuery->listResult as $key => $value){
				$newRow = array();
				$newRow['name'] = $value['nombreReceptor'];
				$newRow['document'] = $value['docReceptor'];

				$arrayResult[] = $newRow;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay clientes que mostrar con la sugerencia de texto ingresada.";
		}
		return $responseQuery;
	}

	//devuelve todos los clientes que se registraron en la base local para la empresa que se pasa por parametro
	public function getAllCustomersByBusiness( $business ){
		return DataBase::sendQuery(" SELECT * FROM clientes WHERE idEmpresa = ?", array('i', $business), "LIST");
	}

	public function getClientsAccountingNumber(){
		return DataBase::sendQuery("SELECT docReceptor, nroContable FROM `clientes` WHERE nroContable IS NOT null ORDER BY `docReceptor`  ASC", array(), "LIST");
    }

    function getClientsListByDocument( $docReceptor ){

    	$response = new stdClass();
    	$response->result = 1;
    	$response->listResult = array();
    	$list = DataBase::sendQuery("SELECT docReceptor, nombreReceptor FROM `clientes` WHERE docReceptor like '%".$docReceptor."%' AND idEmpresa = ? ORDER BY `nombreReceptor`  ASC", array('i',$_SESSION['systemSession']->idBusiness), "LIST");

    	if ( $list->result == 2 ){
    		foreach ($list->listResult as $key => $value) {
				$objectResult = new \stdClass();
				$objectResult->name = $value['nombreReceptor'];
				$objectResult->document = $value['docReceptor'];
				array_push($response->listResult, $objectResult);
			}
    	}

    	return $response;
    }


    function getClientsListByDocumentOrName( $dataReceptor, $idEmpresa ){
		$dbClass = new DataBase();
    	$response = new stdClass();
    	$response->result = 1;
    	$response->listResult = array();
    	$list = $dbClass->sendQuery("SELECT docReceptor, nombreReceptor FROM `clientes` WHERE (docReceptor like '%".$dataReceptor."%' OR nombreReceptor like '%".$dataReceptor."%') AND idEmpresa = ? ORDER BY `nombreReceptor` ASC", array('i', $idEmpresa), "LIST");

    	if ( $list->result == 2 ){
    		foreach ($list->listResult as $key => $value) {
				$objectResult = new \stdClass();
				$objectResult->name = $value['nombreReceptor'];
				$objectResult->document = $value['docReceptor'];
				array_push($response->listResult, $objectResult);
			}
    	}

    	return $response;
    }
}