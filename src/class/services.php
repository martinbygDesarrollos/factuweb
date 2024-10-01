<?php

require_once '../src/utils/handle_date_time.php';
require_once '../src/utils/utils.php';
require_once 'others.php';

class services{

	//--------------------------------------------------------------------------------------------
	//----------------------------------------SERVICES--------------------------------------------
	//--------------------------------------------------------------------------------------------

	public function activeService($idService, $newValue, $idBusiness){
		return DataBase::sendQuery("UPDATE servicios SET activo = ? WHERE idServicio = ? AND idEmpresa = ?", array('iii', $newValue, $idService, $idBusiness), "BOOLE");
	}

	public function getServiceWithName($nameService, $idService, $idBusiness){

		$responseQuery = null;
		if(is_null($idService))
			$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE nombre = ? AND idEmpresa = ?", array('si', $nameService, $idBusiness), "OBJECT");
		else
			$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE nombre = ? AND idServicio != ? AND idEmpresa = ?", array('sii', $nameService, $idService, $idBusiness), "OBJECT");

		if($responseQuery->result == 1)
			$responseQuery->message = "Actualmente no hay un servicio con el nombre ingresado.";
		return $responseQuery;
	}

	public function getServiceWithId($idService, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE idServicio = ? AND idEmpresa = ?", array('ii', $idService, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro el servicio seleccionado en la base de datos.";
		return $responseQuery;
	}

	public function getServiceWithIdToShow($idService, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE idServicio = ? AND idEmpresa = ?", array('ii', $idService, $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseGetIVA = others::getValueIVA($responseQuery->objectResult->idIVA);
			if($responseGetIVA->result == 2)
				$responseQuery->objectResult->valorIVA = number_format($responseGetIVA->objectResult->valor,2,",",".");

			$typeCoin = '$';
			if(strcmp($responseQuery->objectResult->moneda, "USD") == 0)
				$typeCoin = 'U$S';
			else if(strcmp($responseQuery->objectResult->moneda, "UYI") == 0)
				$typeCoin = "UI";

			$responseQuery->objectResult->simboloMoneda = $typeCoin;
			$responseQuery->objectResult->costoFormat = number_format($responseQuery->objectResult->costo,2,",",".");
			$responseQuery->objectResult->importeFormat = number_format($responseQuery->objectResult->importe,2,",",".");
		}else if($responseQuery->result == 1){
			$responseQuery->message = "No se encontro el servicio seleccionado en la base de datos.";
		}
		return $responseQuery;
	}

	public function createService($name, $description, $typeCoin, $cost, $amount, $idIva, $idBusiness){
		return DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES(?,?,?,?,?,?,?,?)", array('isssddii', $idBusiness, $name, $description, $typeCoin, $cost, $amount, $idIva, 1), "BOOLE");
	}

	public function modifyService($idService, $name, $description, $cost, $amount, $typeCoin, $idIva, $idBusiness){
		return DataBase::sendQuery("UPDATE servicios SET nombre = ?, descripcion = ?, moneda = ?, costo = ?, importe = ?, idIVA = ? WHERE idServicio = ? AND idEmpresa = ?", array('sssddiii', $name, $description, $typeCoin, $cost, $amount, $idIva, $idService, $idBusiness), "BOOLE");
	}

	public function deleteService($idService, $idBusiness){
		$responseQuery = DataBase::sendQuery("DELETE FROM cuotas_servicios WHERE idServicio = ? AND idEmpresa = ?", array('ii', $idService, $idBusiness), "BOOLE");
		if($responseQuery->result == 2){
			return DataBase::sendQuery("DELETE FROM servicios WHERE idServicio = ? AND idEmpresa = ?", array('ii', $idService, $idBusiness), "BOOLE");
		}else return $responseQuery;
	}


	public function getMaxIdServices($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT MAX(idServicio) AS lastId FROM servicios WHERE idEmpresa = ?", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 2)
			$responseQuery->objectResult->lastId = $responseQuery->objectResult->lastId + 1;
		else if($responseQuery->result == 1)
			$responseQuery->message = "Actualmente no hay servicios ingresados en la base de datos.";
		return $responseQuery;
	}

	public function listServiceToChange($idService, $idClient, $idBusiness){

		$responseQuery = DataBase::sendQuery("SELECT idServicio, nombre, importe, moneda FROM servicios WHERE idEmpresa = ? AND idServicio NOT IN (SELECT idServicio FROM cuotas_servicios WHERE idServicio != ? AND idCliente = ? )", array('iii', $idBusiness, $idService, $idClient), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				$coin = '$';
				if(strcmp($value['moneda'], "USD") == 0)
					$coin = 'U$S';
				else if(strcmp($value['moneda'], "UYI") == 0)
					$coin = 'UI';

				$value['importe'] = $coin . " " . number_format($value['importe'],2,",",".");

				$arrayResult[] = $value;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay servicios ingresados en la base de datos.";
		}
		return $responseQuery;
	}

	public function getAllService($idClient, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE activo = ? AND idEmpresa = ? AND idServicio NOT IN (SELECT idServicio FROM cuotas_servicios WHERE idCliente = ?)", array('iii',1, $idBusiness, $idClient), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $row) {
				$coin = '$';
				if(strcmp($row['moneda'], "USD") == 0)
					$coin = 'U$S';
				else if(strcmp($row['moneda'], "UYI") == 0){
					$coin = 'UI';
				}
				$row['coin'] = $coin;

				$row['importe'] = number_format($row['importe'], 2, ",", ".");

				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay servicios que listar para asignar a este cliente.";
		}

		return $responseQuery;
	}

	public function getListServices($lastId, $textToSearch, $idBusiness){
		if($lastId == 0){
			$responseGetLastId = services::getMaxIdServices($idBusiness);
			if($responseGetLastId->result == 2)
				$lastId = $responseGetLastId->objectResult->lastId;
			else return $responseGetLastId;
		}

		$sqlTextToSearch = '';
		if(!is_null($textToSearch))
			$sqlTextToSearch = " AND nombre LIKE '" . $textToSearch . "%' ";

		$responseQuery = DataBase::sendQuery("SELECT * FROM servicios WHERE idServicio < ? AND idEmpresa = ? ". $sqlTextToSearch ." ORDER BY idServicio DESC LIMIT 20", array('ii', $lastId, $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$newLastId = $lastId;
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				if($newLastId > $value['idServicio']) $newLastId = $value['idServicio'];

				$coin = '$';
				if(strcmp($value['moneda'], "USD") == 0)
					$coin = 'U$S';
				else if(strcmp($value['moneda'], "UYI") == 0)
					$coin = 'UI';

				$value['simboloMoneda'] = $coin;
				$value['costoFormat'] = number_format($value['costo'], 2, ",", ".");
				$value['importeFormat'] = number_format($value['importe'],2,",",".");

				$responseIVA = others::getValueIVA($value['idIVA']);
				if($responseIVA->result == 2)
					$value['iva'] = number_format($responseIVA->objectResult->valor,2,",",".");
				$arrayResult[] = $value;
			}

			$responseQuery->listResult = $arrayResult;

			array_multisort(array_map(function($element) {
				return $element['idServicio'];
			}, $responseQuery->listResult), SORT_ASC, $responseQuery->listResult);

			$responseQuery->lastId = $newLastId;
		}
		if($responseQuery->result == 1)
			$responseQuery->message = "Actualmente no hay servicios ingresados en la base de datos.";

		return $responseQuery;
	}

	//--------------------------------------------------------------------------------------------
	//--------------------------------------FEE SERVICES------------------------------------------
	//--------------------------------------------------------------------------------------------

	public function getFeeServiceToExport($currentQuote, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT C.docReceptor, C.nombreReceptor, S.nombre, S.descripcion, CS.periodo, I.valor, S.moneda, S.costo, S.importe, CS.fechaUltimaFactura, CS.vigente FROM servicios AS S, cuotas_servicios AS CS, clientes AS C, indicadores_facturacion AS I WHERE CS.idCliente = C.id AND CS.idServicio = S.idServicio AND S.idIVA = I.id AND S.idEmpresa = ?", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $row) {
				$arrayRow = array();

				$arrayRow['DOCUMENTO'] = $row['docReceptor'];
				$arrayRow['NOMBRE'] = $row['nombreReceptor'];
				$arrayRow['SERVICIO'] = utils::stringToLowerWithFirstCapital($row['nombre']);
				$arrayRow['DESCRIPCION'] = $row['descripcion'];
				$arrayRow['PERIODO'] = services::getPeriod($row['periodo']);
				$arrayRow['IVA'] = number_format($row['valor'], 2, ",", ".");

				if(strcmp($row['moneda'], "UYU") == 0)
					$arrayRow['MONEDA'] = '$';
				if(strcmp($row['moneda'], "USD") == 0)
					$arrayRow['MONEDA'] = 'U$S';
				if(strcmp($row['moneda'], "UYI") == 0)
					$arrayRow['MONEDA'] = "UI";

				if(strcmp($row['moneda'], "UYI") == 0){
					$arrayRow['COSTO'] = number_format($row['costo'] * $currentQuote, 2, ",", ".");
					$arrayRow['IMPORTE'] = number_format($row['importe'] * $currentQuote, 2, ",", ".");
					$arrayRow['UI'] = number_format($row['importe'], 2, ",", ".");
				}else{
					$arrayRow['COSTO'] = number_format($row['costo'], 2, ",", ".");
					$arrayRow['IMPORTE'] = number_format($row['importe'], 2, ",", ".");
					$arrayRow['UI'] = " ";
				}

				if($row['vigente'] == 1)
					$arrayRow['ESTADO'] = "Vigente";
				else
					$arrayRow['ESTADO'] = "NO";

				if(!is_null($row['fechaUltimaFactura']))
					$arrayRow['FECHA'] = handleDateTime::setFormatBarDate($row['fechaUltimaFactura']);
				else
					$arrayRow['FECHA'] = "No Facturado";

				$arrayResult[] = $arrayRow;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result = 1){
			$responseQuery->message = "Actuamente no hay cuotas por servicios para exportar.";
		}

		return $responseQuery;
	}

	public function getInvoiceFeesServiceClient($idClient, $idBusiness, $dateEmitted){
		$response = new \stdClass();

		$responseQuery = DataBase::sendQuery("SELECT CS.idCuota, CS.idCliente, S.idServicio, S.nombre, S.descripcion, S.moneda, S.costo, S.importe, S.idIVA, CS.periodo, CS.fechaUltimaFactura FROM cuotas_servicios AS CS, servicios AS S WHERE CS.idServicio =  S.idServicio AND S.activo = 1 AND CS.vigente = 1 AND CS.idCliente = ? AND S.idEmpresa = ? ", array('ii', $idClient, $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				$responseIsBillable = services::serviceIsBillable($value['periodo'], $value['fechaUltimaFactura'], $dateEmitted);
				if($responseIsBillable->result == 2)
					$arrayResult[] = $value;
			}

			if(sizeof($arrayResult) > 0){
				$response->result = 2;
				$response->listResult = $arrayResult;
			}else{
				$response->result = 1;
				$response->message = "Actualmente no hay cuotas facturables para el cliente seleccionado.";
			}
			return $response;
		}

		if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay cuotas facturables para el cliente seleccionado.";
		}

		return $responseQuery;
	}

	public function getBillableServiceWithId($idFeeService, $idBusiness, $dateEmitted){

		$responseQuery = DataBase::sendQuery("SELECT * FROM cuotas_servicios WHERE idCuota = ? AND idEmpresa = ?", array('ii', $idFeeService, $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			if($responseQuery->objectResult->vigente == 1){
				$responseIsBillable = services::serviceIsBillable($responseQuery->objectResult->periodo, $responseQuery->objectResult->fechaUltimaFactura, $dateEmitted);
				if($responseIsBillable->result == 1){
					$responseQuery->result = 1;
					$responseQuery->message = $responseIsBillable->message;
					unset($responseQuery->objectResult);
				}
			}else{
				$responseQuery->result = 1;
				$responseQuery->message = "La cuota seleccionada no se encuentra activa.";
				unset($responseQuery->objectResult);
			}
		}else if($responseQuery->result == 1){
			$responseQuery->message = "La cuota seleccionada no fue encontrada dentro de la base de datos.";
		}

		return $responseQuery;
	}

	//esta funcion verifica que segun el periodo y la fecha de la ultima factura, se pueda volver a emitir o no la ultima factura
	public function serviceIsBillable($period, $dateLastBill, $dateEmitted){
		$response = new \stdClass();
		$responseConfiguration = ctr_users::getVariableConfiguration("SUFIJO_NOMBRE_SERVICIO_FACTURA");
		$nextMonth = 0;
		if ($responseConfiguration && $responseConfiguration->result == 2){
			if( strcmp($responseConfiguration->configValue, "FECHA_ANTERIOR") == 0)
				$nextMonth = date('m',strtotime ('-1 month' , strtotime($dateEmitted)));
			if( strcmp($responseConfiguration->configValue, "FECHA_ACTUAL") == 0)
				$nextMonth = date('m',strtotime($dateEmitted));
			if( strcmp($responseConfiguration->configValue, "FECHA_POSTERIOR") == 0)
				$nextMonth = date('m',strtotime ('+1 month' , strtotime($dateEmitted)));
		}

		if(is_null($period)){
			if(is_null($dateLastBill))
				$response->result = 2;
			else if(handleDateTime::isBillableService($dateLastBill, $dateEmitted)){
				$response->result = 2;
			}
			else{
				$response->result = 1;
				$response->message = "Ya fue emitida la factura correspondiente a esta cuota.";
			}
		}else if($period < 13){
			if($period == $nextMonth){
				if(is_null($dateLastBill))
					$response->result = 2;
				else if(handleDateTime::isBillableService($dateLastBill, $dateEmitted))
					$response->result = 2;
				else{
					$response->result = 1;
					$response->message = "Ya fue emitida la factura correspondiente a esta cuota.";
				}
			}else{
				$response->result = 1;
				$response->message = "Esta cuota tiene un período anual, el cual no corresponde a este mes.";
			}
		}else{ //si no es nulo y es mayor a 12(meses del año) entonces puede ser de valor 22, 33 o 66
			if(is_null($dateLastBill))
				$response->result = 2;
			else if(handleDateTime::isBillableServicePeriod($period, $dateLastBill, $dateEmitted)){
				$response->result = 2;
			}
			else{
				$response->result = 1;
				$response->message = "Aún no trascurrio el período desde la ultima emisión de esta cuota.";
			}
		}
		return $response;
	}

	public function modifyFeeService($idFeeService, $idService, $period, $idBusiness){
		return DataBase::sendQuery('UPDATE cuotas_servicios SET periodo = ? , idServicio = ? WHERE idCuota = ? AND idEmpresa = ?', array('iiii', $period, $idService, $idFeeService, $idBusiness), "BOOLE");
	}

	public function updateLastInvoiceDate($idFeeService, $idClient, $date, $idBusiness){
		return DataBase::sendQuery("UPDATE cuotas_servicios SET fechaUltimaFactura = ? WHERE idCuota = ? AND idCliente = ? AND idEmpresa = ?", array('iiii', $date, $idFeeService, $idClient, $idBusiness), "BOOLE");
	}

	public function deleteFeeService($idFeeService, $idBusiness){
		return DataBase::sendQuery("DELETE FROM cuotas_servicios WHERE idCuota = ? AND idEmpresa = ?", array('ii', $idFeeService, $idBusiness), "BOOLE");
	}

	public function disableAllServiceFees($idService, $newValue, $idBusiness){
		return DataBase::sendQuery("UPDATE cuotas_servicios SET vigente = ? WHERE idServicio = ? AND idEmpresa = ?", array('iii', $newValue, $idService, $idBusiness), "BOOLE");
	}

	public function getFeeServiceWithId($idFee, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM cuotas_servicios WHERE idCuota = ? AND idEmpresa = ?", array('ii', $idFee, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro la cuota seleecionada dentro de la base de datos.";
		return $responseQuery;
	}

	public function createFeeService($idBusiness, $idClient, $idService, $period, $active){
		return DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente) VALUES (?,?,?,?,?)", array('iiiii', $idBusiness, $idClient, $idService, $period, $active), "BOOLE");
	}

	public function getMaxIdFeeServices($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT MAX(idCuota) AS lastId FROM cuotas_servicios WHERE idEmpresa = ? ", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 2)
			$responseQuery->objectResult->lastId = $responseQuery->objectResult->lastId + 1;
		else if($responseQuery->result == 1)
			$responseQuery->message = "Actulamente no hay servicios ingresados en la base de datos.";

		return $responseQuery;
	}

	public function getListFeeServices($lastId, $textToSearch, $idBusiness){
		if($lastId == 0){
			$responseGetLastId = services::getMaxIdFeeServices($idBusiness);
			if($responseGetLastId->result == 2)
				$lastId = $responseGetLastId->objectResult->lastId;
			else return $responseGetLastId;
		}

		$sqlText = "";
		if(!is_null($textToSearch))
			$sqlText = " AND CS.idCliente IN (SELECT id FROM clientes WHERE nombreReceptor LIKE '%" . $textToSearch . "%') ";

		$responseQuery = DataBase::sendQuery("SELECT CS.idCuota, CS.idCliente, CS.idServicio, CS.periodo, CS.vigente, CS.fechaUltimaFactura, S.nombre, S.moneda, S.costo, S.importe, S.idIVA FROM cuotas_servicios AS CS, servicios AS S WHERE CS.idServicio = S.idServicio AND CS.idCuota <= ? AND CS.idEmpresa = ? " . $sqlText . " ORDER BY CS.idCuota DESC LIMIT 20", array('ii', $lastId, $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayError = array();
			$newLastId =  $lastId;

			foreach ($responseQuery->listResult as $key => $row) {
				if($newLastId > $row['idCuota']) $newLastId = $row['idCuota'];

				$row['periodo'] = services::getPeriod($row['periodo']);

				if(!is_null($row['fechaUltimaFactura']))
					$row['fechaUltimaFactura'] = handleDateTime::setFormatBarDate($row['fechaUltimaFactura']);
				else
					$row['fechaUltimaFactura'] = "No facturado";


				$responseGetIva = others::getValueIVA($row['idIVA']);
				if($responseGetIva->result == 2)
					$row['montoIVA'] = number_format($responseGetIva->objectResult->valor, 2, ",", ".");

				$coin = '$';
				if(strcmp($row['moneda'], "USD") == 0)
					$coin = 'U$S';
				else if(strcmp($row['moneda'], "UYI") == 0)
					$coin = 'UI';

				$row['simboloMoneda'] = $coin;
				$row['costoFormat'] = number_format($row['costo'],2, ",", ".");
				$row['importeFormat'] = number_format($row['importe'], 2, ",", ".");
				$arrayError[] = $row;
			}
			$responseQuery->listResult = $arrayError;
			$responseQuery->lastId = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay servicios ingresados en la base de datos.";
		}

		return $responseQuery;
	}

	public function getPeriod($period){
		if(is_null($period))
			return "Mensual";
		if($period == 22)
			return "Bimestral";
		else if($period == 33)
			return "Trimestral";
		else if($period == 66)
			return "Semestral";
		else{
			$arrayMonth = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
			return $arrayMonth[$period - 1];
		}
	}

	public function changeCurrentValueFeeService($valueActive, $idFeeService, $idBusiness){
		return DataBase::sendQuery("UPDATE cuotas_servicios SET vigente = ? WHERE idCuota = ? AND idEmpresa = ?", array('iii', $valueActive, $idFeeService, $idBusiness), "BOOLE");
	}

	public function testData($idBusiness){

		$period1 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio1", "Es el primer servicio que se creo", "UYU", 101.4, 130, 3, 1), "BOOLE");
		$period2 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio2", "Es el segundo servicio que se creo", "UYU", 450, 500, 2, 1), "BOOLE");
		$period3 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio3", "Es el tercer servicio que se creo", "UYU", 624, 8000, 3, 1), "BOOLE");
		$period4 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio4", "Es el cuarto servicio que se creo", "UYU", 120, 120, 1, 1), "BOOLE");
		$period5 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio5", "Es el quinto servicio que se creo", "UYU", 300, 300, 1, 1), "BOOLE");
		$period6 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio6", "Es el sexto servicio que se creo", "UYU", 390, 500, 3, 1), "BOOLE");
		$period7 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio7", "Es el septimo servicio que se creo", "UYU", 342, 380, 2, 1), "BOOLE");
		$period8 = DataBase::sendQuery("INSERT INTO servicios(idEmpresa, nombre, descripcion, moneda, costo, importe, idIVA, activo) VALUES (?,?,?,?,?,?,?,?)", array('isssiiii', 1, "Servicio8", "Es el octavo servicio que se creo", "UYU", 360, 400, 2, 1), "BOOLE");

		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 13, $period1->id, 3, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 14, $period1->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 15, $period1->id, 6, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 12, $period3->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 13, $period3->id, 3, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 15, $period3->id, 12, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 70, $period1->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 24, $period2->id, 11, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 17, $period3->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 13, $period4->id, 3, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 68, $period5->id, 6, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 61, $period6->id, 8, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 88, $period7->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 98, $period8->id, null, 1, null), "BOOLE");
		DataBase::sendQuery("INSERT INTO cuotas_servicios(idEmpresa, idCliente, idServicio, periodo, vigente, fechaUltimaFactura) VALUES (?,?,?,?,?,?)", array('iiiiii',1, 16, $period7->id, null, 1, null), "BOOLE");
	}
}