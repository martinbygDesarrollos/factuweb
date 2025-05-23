<?php

require_once '../src/utils/handle_date_time.php';
require_once '../src/utils/utils.php';

class vouchersEmitted{

	public function insertDetailVoucherEmitted($name, $description, $unitMeasure, $price, $discount, $typeDiscount, $idIVA, $idBusiness){
		return DataBase::sendQuery("INSERT INTO detalelles_emitidos(nombre, descripcion, unidadMedida, precio, descuento, tipoDescuento, idIVA, idEmpresa) VALUES (?,?,?,?,?,?,?,?)", array('sssddiii', $name, $description, $unitMeasure, $price, $discount, $typeDiscount, $idIVA, $idBusiness), "BOOLE");
	}

	//retorna la fecha maxima y minima de un comprobante
	//UPDATED
	public function getMinAndMaxDateVoucher($idBusiness){
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();

		$responseQuery = $dbClass->sendQuery("SELECT MIN(fecha) AS minDate, MAX(fecha) AS maxDate FROM comprobantes WHERE idEmisor = ?", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->objectResult->minDate = $handleDateTimeClass->setFormatHTMLDate($responseQuery->objectResult->minDate);
			$responseQuery->objectResult->maxDate = $handleDateTimeClass->setFormatHTMLDate($responseQuery->objectResult->maxDate);
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes de los cuales obtener la fecha máxima y mínima.";
		}
		return $responseQuery;
	}
	//UPDATED
	public function getTypeExistingVouchers($idBusiness){
		$dbClass = new DataBase();
		$utilsClass = new utils();
		
		$responseQuery = $dbClass->sendQuery("SELECT DISTINCT tipoCFE, isCobranza FROM comprobantes WHERE idEmisor = ? ORDER BY tipoCFE", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				$newRow = array();
				$newRow['typeCFE'] = $value['tipoCFE'];
				$responseNameCFE = $utilsClass->getNameVoucher($value['tipoCFE'], $value['isCobranza']);
				if(!is_null($responseNameCFE)){
					$newRow['nameCFE'] = $responseNameCFE;
					$arrayResult[] = $newRow;
				}
			}
			$responseQuery->listResult = $arrayResult;
		}if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes emitidos en el sistema.";
		}

		return $responseQuery;
	}

	//obtengo la fechaHoraEmision mas reciente de un comprobante
	public function getMaxIndexVouchers(){
		$responseQuery = DataBase::sendQuery("SELECT fechaHoraEmision FROM comprobantes WHERE id IS NOT NULL ORDER BY fechaHoraEmision DESC LIMIT 1", null, "OBJECT");
		if($responseQuery->result == 2) return $responseQuery->objectResult->fechaHoraEmision;
	}

	//obtengo unalista de vouchers
	//UPDATED
	public function getVouchersEmitted($lastVoucherEmittedIdFound, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentClient, $idEmpresa, $branchCompany){
		$vouchersEmittedClass = new vouchersEmitted();
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$utilsClass = new utils();

		if($lastVoucherEmittedIdFound == 0){
			$lastVoucherEmittedIdFound = $vouchersEmittedClass->getMaxIdVouchers($idEmpresa);
			$operWhereId = "<=";
		}else{
			$operWhereId = "<";
		}

		$withPayMethod = "";
		if($payMethod != 0)
			$withPayMethod = " AND formaPago = " . $payMethod . " ";

		$withTypeVoucher = "";
		if($typeVoucher != 0){
			if(strlen($typeVoucher) == 4){
				$typeVoucher = substr($typeVoucher, 0, 3);
				$withTypeVoucher = " AND tipoCFE = " . $typeVoucher . " AND isCobranza = 1 ";
			}else $withTypeVoucher = " AND tipoCFE = " . $typeVoucher . " AND isCobranza = 0 ";
		}

		$withDateVoucher = "";
		if($dateVoucher != 0)
			$withDateVoucher = " AND fecha = " . $dateVoucher . " ";

		$withNumberVoucher = "";
		if($numberVoucher != 0)
			$withNumberVoucher = " AND numeroCFE LIKE '%" . $numberVoucher . "%' ";

		$withbranchCompany = "";
		if($branchCompany != 0)
			$withbranchCompany = " AND sucursal = " . $branchCompany . " ";

		$withClient = "";
		if(!empty($documentClient)){
			$withClient = " AND idCliente IN (SELECT id FROM clientes WHERE docReceptor LIKE '%" . $documentClient . "%' OR nombreReceptor LIKE '%" . $documentClient . "%') ";
		}
		$sqlQuery = "SELECT * FROM comprobantes ";
		$sqlQueryWhere = " WHERE id ". $operWhereId ." ? AND idEmisor = ? AND id IS NOT NULL " . $withPayMethod . $withTypeVoucher . $withDateVoucher . $withNumberVoucher . $withbranchCompany . $withClient;
		$sqlQueryOrder = "ORDER BY id DESC LIMIT 20";
		error_log( "CONSULTA: $sqlQuery $sqlQueryWhere $sqlQueryOrder : $lastVoucherEmittedIdFound, $idEmpresa");
		$responseQuery = $dbClass->sendQuery($sqlQuery. $sqlQueryWhere. $sqlQueryOrder, array('si', $lastVoucherEmittedIdFound, $idEmpresa), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			$newLastId = $lastVoucherEmittedIdFound;
			foreach ($responseQuery->listResult as $key => $value){
				if($newLastId > $value['id']) $newLastId = $value['id'];

				$value['fechaHoraEmision'] = substr($handleDateTimeClass->setFormatBarDateTime($value['fechaHoraEmision']), 0, 16);
				$value['total'] = number_format($value['total'], 2, ",", ".");
				$value['tipoCFE_CODE'] = $value['tipoCFE'];
				$value['tipoCFE'] = $utilsClass->getNameVoucher($value['tipoCFE'], $value['isCobranza']);
				$value['fecha'] = $handleDateTimeClass->setFormatBarDate($value['fecha']);

				if($value['formaPago'] == 1) $value['formaPago'] = "Contado";
				else $value['formaPago'] = "Crédito";

				if(strcmp($value['moneda'], "UYU") == 0) $value['moneda'] = '$';
				else $value['moneda'] = 'U$S';

				$arrayResult[] = $value;
			}

			array_multisort(array_map(function($element) {
				return $element['id'];
			}, $arrayResult), SORT_ASC, $arrayResult);

			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastVoucherEmittedIdFound = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes para su empresa en la base de datos.";
		}
		return $responseQuery;
	}

	//UPDATED
	public function getListVouchersEmitted( $dateInit, $dateFinish, $idClient, $typeVoucher, $isCobranza, $lastId, $idBusiness, $limit ){

		$dataBaseClass = new DataBase();

		$sql = "SELECT indice, id, fecha, fechaHoraEmision, tipoCFE, serieCFE, numeroCFE, moneda, isCobranza, idCliente, isAnulado, total FROM `comprobantes` ";


		$where = "WHERE fecha <= ? AND fecha >= ? AND id <= ? AND idEmisor = ? ";
		if ( isset($idClient) && $idClient != "" ){
			$where .= "AND idCliente = ".$idClient." ";
		}

		//voucher 0
		if ( isset($typeVoucher) && $typeVoucher != "" && $typeVoucher != "0"){
			$where .= "AND tipoCFE = ".$typeVoucher." ";
		}

		//isCobranza 1 incluir recibos, 0 sin recibos
		if ( isset($isCobranza) && $isCobranza != "" ){
			if ( $isCobranza == "0" ){//
				$where .= "AND isCobranza <> 1 ";
			}
		}

		$orderAndLimit = "ORDER BY `fecha` ASC ";

		if ( isset($limit) && $limit != "" && $limit != "0" ){
			$orderAndLimit .= "LIMIT ".$limit." ";
		}
		error_log($sql . $where . $orderAndLimit . " : " . $dateFinish . " - " . $dateInit . " - " . $lastId . " - " . $idBusiness);
		return $dataBaseClass->sendQuery($sql . $where . $orderAndLimit, array('sssi', $dateFinish, $dateInit, $lastId, $idBusiness), "LIST");
	}

	//agrega una nueva linea en el historioal o bitacora de acciones
	public function insertReceiptHistory($dateInsert, $idUser, $numTable, $action, $total, $dateReceipt, $typeCoin, $document, $myBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO bitacora_comprobantes(isCliente, fechaRealizacion, idUsuario, accion, total, fecha, moneda, documento, idEmpresa) VALUES (?,?,?,?,?,?,?,?,?)", array('isisdisii', $numTable, $dateInsert, $idUser, $action, $total, $dateReceipt, $typeCoin, $document, $myBusiness), "BOOLE");
	}

	//se modifica un comprobante
	//UPDATED
	public function modifyManualReceipt($indexVoucher, $total, $dateReceipt, $typeCoin, $myBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE comprobantes SET total= ?, fecha = ?, moneda = ? WHERE indice = ? AND idEmisor = ?", array('disii', $total, $dateReceipt, $typeCoin, $indexVoucher, $myBusiness), "BOOLE");
	}

	//UPDATED setea como anulado un comprobante 
	public function cancelVoucherById($idVoucher, $myBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE comprobantes SET isAnulado = 1 WHERE id = ? AND idEmisor = ?", array('si', $idVoucher, $myBusiness), "BOOLE");
	}

	//si un comprobante que se emitio por sigecom es anulado por dgi, se actualiza el campo isAnulado, para que no se tenga en cuenta en el estado de cuenta y demas
	public function updateVoucherAnuladoByDgi($indexVoucher, $tipoCFE, $serieCFE, $numeroCFE, $isAnulado, $myBusiness){
		$dataBaseClass = new DataBase();
		return $dataBaseClass->sendQuery(
			"UPDATE comprobantes SET isAnulado = ? WHERE indice = ? AND idEmisor = ? AND tipoCFE = ? AND serieCFE = ? AND numeroCFE = ?",
			array('iiiisi',$isAnulado, $indexVoucher, $myBusiness, $tipoCFE, $serieCFE, $numeroCFE),
			"BOOLE");
	}


	//obtengo un unico comprobante, que coincide con el valor que llega por parametro y es el campo indice del comprobante.
	//UPDATED
	public function getVoucherWithIndex($indexVoucher){
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE indice = ?", array('i', $indexVoucher), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->objectResult->total = number_format($responseQuery->objectResult->total,2,",",".");
			$responseQuery->objectResult->fecha = $handleDateTimeClass->setFormatBarDate($responseQuery->objectResult->fecha);
		}else if($responseQuery->result == 1){
			$responseQuery->message = "No se encontro un comprobante emitido con el indice ingresado en la base de datos.";
		}
		return $responseQuery;
	}

	//elimina un comprobante segun corresponda el valor que llega por parametro y el campo indice del comprobante
	public function deleteManualReceipts($indexVoucher){
		return DataBase::sendQuery("DELETE FROM comprobantes WHERE indice = ?", array('i', $indexVoucher), "BOOLE");
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//FUNCIONES PARA EL USO DEL ESTADO DE CUENTA.


	//obtener el estado de cuenta de un cliente segun la fecha desde y fecha hasta
	//UPDATED
	public function getAccountState($idClient, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness, $config){
		/* el cobranza pueden haberlo puesto contado o credito por error, incluir ambos */
		$dbClass = new DataBase();
		$responseQuery = null;
		$vouchersEmittedClass = new vouchersEmitted();
		$handleDateTimeClass = new handleDateTime();
		$utilsClass = new utils();

		if($config == "NO")
			$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE isAnulado != 1 AND idCliente = ? AND fecha >= ? AND fecha <= ? AND moneda = ? AND idEmisor = ? AND (formaPago = 2 OR isCobranza = 1) ORDER BY fecha, fechaHoraEmision", array('iiisi',$idClient, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness), "LIST");
		else if($config == "SI")
			$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE isAnulado != 1 AND idCliente = ? AND fecha >= ? AND fecha <= ? AND moneda = ? AND idEmisor = ? ORDER BY fecha, fechaHoraEmision", array('iiisi',$idClient, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness), "LIST");

		if($responseQuery->result != 0){
			$arrayResult = array();
			$saldoInicial = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, $typeCoin, $dateInitINT, $config, $myBusiness);
			$arrayResult[] = array(
				"FECHA" => $handleDateTimeClass->setFormatBarDate($dateInitINT),
				"DOCUMENTO" => "Saldo inicial",
				"MONEDA" => $typeCoin,
				"DEBE" => number_format(0,2,",","."),
				"INTDEBE" => 0,
				"HABER" => number_format( 0,2,",","."),
				"INTHABER" => 0,
				"SALDO" => number_format($saldoInicial->balance,2,",","."),
				"INTSALDO" => $saldoInicial->balance
			);

			if($responseQuery->result == 2){
				foreach($responseQuery->listResult as $key => $row){
					$newRow = array();

					$newRow['id'] = $row['id'];
					$newRow['isCobranza'] = $row['isCobranza'];

					$newRow['FECHA'] = $handleDateTimeClass->setFormatBarDate($row['fecha']);

					if(!is_null($row['id']))
						$newRow['DOCUMENTO'] = $utilsClass->getNameVoucher($row['tipoCFE'], $row['isCobranza']). " " . $row['serieCFE'] . $row['numeroCFE'];
					else $newRow['DOCUMENTO'] = "Recibo manual";

					$newRow['MONEDA'] = $row['moneda'];
					$debe = 0;
					$haber = 0;

					//dependiendo de que tipo de documento sea el total se agrega en el debe o en el haber
					if($row['isCobranza'] == 1){
						$newRow['DEBE'] = 0;
						$newRow['INTDEBE'] = 0;
						$newRow['HABER'] = number_format($row['total'],2,",",".");
						$newRow['INTHABER'] = $row['total']; //valor numero entero
						$haber = $row['total'];
					}elseif ($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 1){
						$newRow['DEBE'] = number_format($row['total'],2,",",".");
						$newRow['INTDEBE'] = $row['total'];
						$debe = $row['total'];
						$newRow['HABER'] = 0;
						$newRow['INTHABER'] = 0;
					}elseif($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 2){
						$newRow['DEBE'] = 0;
						$newRow['INTDEBE'] = 0;
						$newRow['HABER'] = number_format($row['total'],2,",",".");
						$newRow['INTHABER'] = $row['total'];
						$haber = $row['total'];
					}elseif($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 3){
						$newRow['DEBE'] = number_format($row['total'],2,",",".");
						$newRow['INTDEBE'] = $row['total'];
						$debe = $row['total'];
						$newRow['HABER'] = 0;
						$newRow['INTHABER'] = 0;
					}

					if(isset($newRow['DEBE']) || isset($newRow['HABER'])){
						$saldoInicial->balance = $saldoInicial->balance + $debe - $haber;
						$newRow['SALDO'] = number_format($saldoInicial->balance, 2, ",", ".");
						$newRow['INTSALDO'] = $saldoInicial->balance;
						$arrayResult[] = $newRow;
					}
				}
			}else if($responseQuery->result == 1){
				$responseQuery->message = "Actualmente no hay un estado de cuenta que generar para el cliente en el rango de fechas ingresado.";
			}

			$balanceDollar = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, "USD", $handleDateTimeClass->getCurrentDateTimeInt(), $config, $myBusiness);
			$balancePesos = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, "UYU", $handleDateTimeClass->getCurrentDateTimeInt(), $config, $myBusiness);

			$responseQuery->listResult = array(
				"listResult" => $arrayResult,
				"MAINCOIN" => $typeCoin,
				"SALDOTOTAL" => number_format($saldoInicial->balance,2, ",", "."),
				"INTSALDOTOTAL" => $saldoInicial->balance,
				"DATEENDING" => $handleDateTimeClass->setFormatBarDate($dateEndingINT),
				"BALANCEUSD" => number_format($balanceDollar->balance, 2, ",", "."),
				"INTBALANCEUSD" => $balanceDollar->balance,
				"BALANCEUYU" => number_format($balancePesos->balance, 2, ",", "."),
				"INTBALANCEUYU" => $balancePesos->balance
			);
		}
		return $responseQuery;
	}
	public function getSoldInfo($typeCoin, $myBusiness){
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$mesAnterior = intval($handleDateTimeClass->getPreviousDateInt());
		$mesActual = intval($handleDateTimeClass->getCurrentDateInt());
		$query = "SELECT * 
				FROM comprobantes 
				WHERE isAnulado != 1 
				AND fecha >= ?
				AND fecha <= ?
				AND moneda = ?
				AND tipoCFE IN ('101', '102', '103', '111', '112', '113') 
				AND idEmisor = ?
				ORDER BY fecha";
		
		$responseQuery = $dbClass->sendQuery($query, array('iisi', $mesAnterior, $mesActual, $typeCoin, $myBusiness), "LIST");
		$gananciasMesActualUYU = 0;
		$gananciasMesAnteriorUYU = 0;
		$gananciasMesActualUSD = 0;
		$gananciasMesAnteriorUSD = 0;
		if($responseQuery->result != 0){
			if($responseQuery->result == 2){
				foreach($responseQuery->listResult as $key => $row){
					if (intval($row['fecha']) >= $mesAnterior && intVal($row['fecha']) <= intval(substr($mesAnterior, 0 , 6) . "31")) { // MES ANTERIOR
						if ($row['tipoCFE'] == '101' || $row['tipoCFE'] == '103' || $row['tipoCFE'] == '111' || $row['tipoCFE'] == '113') { // ++++++
							if($row['moneda'] == "UYU"){
								$gananciasMesAnteriorUYU += floatval($row['total']);
							} else if($row['moneda'] == "USD"){
								$gananciasMesAnteriorUSD += floatval($row['total']);
							}
						} else if($row['tipoCFE'] == '102' || $row['tipoCFE'] == '112') { // ------
							if($row['moneda'] == "UYU"){
								$gananciasMesAnteriorUYU -= floatval($row['total']);
							} else if($row['moneda'] == "USD"){
								$gananciasMesAnteriorUSD -= floatval($row['total']);
							}
						}
					} elseif (intval($row['fecha']) > intval(substr($mesAnterior, 0 , 6) . "31") && intVal($row['fecha']) <= $mesActual) { // MES ACTUAL
						if ($row['tipoCFE'] == '101' || $row['tipoCFE'] == '103' || $row['tipoCFE'] == '111' || $row['tipoCFE'] == '113') { // ++++++
							if($row['moneda'] == "UYU"){
								$gananciasMesActualUYU += floatval($row['total']);
							} else if($row['moneda'] == "USD"){
								$gananciasMesActualUSD += floatval($row['total']);
							}
						} else if($row['tipoCFE'] == '102' || $row['tipoCFE'] == '112') { // ------
							if($row['moneda'] == "UYU"){
								$gananciasMesActualUYU -= floatval($row['total']);
							} else if($row['moneda'] == "USD"){
								$gananciasMesActualUSD -= floatval($row['total']);
							}
						}
					}
				}
			}else if($responseQuery->result == 1){
				$responseQuery->message = "Actualmente no hay un Comprobantes emitidos para generar estadisticas.";
			}
		}
		$meses = [
			'01' => 'Enero',
			'02' => 'Febrero',
			'03' => 'Marzo',
			'04' => 'Abril',
			'05' => 'Mayo',
			'06' => 'Junio',
			'07' => 'Julio',
			'08' => 'Agosto',
			'09' => 'Septiembre',
			'10' => 'Octubre',
			'11' => 'Noviembre',
			'12' => 'Diciembre'
		];
		unset($responseQuery->listResult);
		$monthNumberAnterior = substr((string)$mesAnterior, 4, 2);
		$monthNumberActual = substr((string)$mesActual, 4, 2);
		$responseQuery->mesActualName = $meses[$monthNumberActual];
		$responseQuery->mesAnteriorName = $meses[$monthNumberAnterior];
		$responseQuery->soldMesActualUYU = number_format(floatval($gananciasMesActualUYU), 2, ".", "");
		$responseQuery->soldMesAnteriorUYU = number_format($gananciasMesAnteriorUYU, 2, ".", "");
		$responseQuery->soldMesActualUSD = number_format(floatval($gananciasMesActualUSD), 2, ".", "");
		$responseQuery->soldMesAnteriorUSD = number_format($gananciasMesAnteriorUSD, 2, ".", "");
		return $responseQuery;
	}

	//obtener el importe de los vouchers
	//UPDATED
	public function getBlanaceFromVouchers($listVouchers, $idBusiness){
		$response = new \stdClass();
		$vouchersEmittedClass = new vouchersEmitted();
		$handleDateTimeClass = new handleDateTime();

		$resultDebe = 0;
		$resultHaber = 0;

		$idClinet = null;
		$typeCoin = null;
		$log = "";
		foreach ($listVouchers as $key => $value) {
			if($value->tipoCFE < 150 && substr($value->tipoCFE,-1) == 1)
				$resultDebe = $resultDebe + $value->total;
			else if($value->tipoCFE < 150 && substr($value->tipoCFE,-1) == 2)
				$resultHaber = $resultHaber + $value->total;
			else if($value->tipoCFE < 150 && substr($value->tipoCFE,-1) == 3)
				$resultDebe = $resultDebe + $value->total;

			$idClient = $value->idCliente;
			$typeCoin = $value->moneda;
			$log .= "[CFE: $value->tipoCFE => VALOR: $value->total] ";
		}
		error_log("get Balance From Vouchers: $log");
		$response->idClient = $idClient;
		$response->typeCoin = $typeCoin;
		$resultbalance = $vouchersEmittedClass->getBalanceToDateEmitted($idClient, $typeCoin, $handleDateTimeClass->getDateTimeInt(date('Y-m-d')), "NO", $idBusiness);//getDateToINT(date('Y-m-d')), "NO", $idBusiness);
		if($resultbalance->balance >= ($resultDebe - $resultHaber))
			$response->balance = $resultDebe - $resultHaber;
		else $response->balance = $resultbalance->balance;

		return $response;
	}

	//obtener importe total de los comprobantes segun la fecha de emision
	//UPDATED
	public function getBalanceToDateEmitted($idClient, $typeCoin, $dateLimitINT, $config, $myBusiness){
		$dbClass = new DataBase();
		/* aca tambien solo los 2 o cobranza*/
		$responseQuery = null;
		if($config == "NO")
			$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE isAnulado != 1 AND fecha < ? AND idCliente = ? AND moneda = ? AND idEmisor = ? AND (formaPago = 2 OR isCobranza = 1)", array('iisi', $dateLimitINT, $idClient, $typeCoin, $myBusiness), "LIST");
		else
			$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE isAnulado != 1 AND fecha < ? AND idCliente = ? AND moneda = ? AND idEmisor = ? AND ((formaPago = 2 OR formaPago = 1) OR isCobranza = 1)", array('iisi', $dateLimitINT, $idClient, $typeCoin, $myBusiness), "LIST");

		if($responseQuery->result == 2){
			$resultDebe = 0;
			$resultHaber = 0;
			foreach($responseQuery->listResult as $key => $value){

				if($value['isCobranza'] == 1){
					$resultHaber = $resultHaber + $value['total'];
				}else if($value['tipoCFE'] < 150 && substr($value['tipoCFE'],-1) == 1)
					$resultDebe = $resultDebe + $value['total'];
				else if($value['tipoCFE'] < 150 && substr($value['tipoCFE'],-1) == 2)
					$resultHaber = $resultHaber + $value['total'];
				else if($value['tipoCFE'] < 150 && substr($value['tipoCFE'],-1) == 3)
					$resultDebe = $resultDebe + $value['total'];
			}
			unset($responseQuery->listResult);
			$responseQuery->balance = $resultDebe - $resultHaber;
		}else if($responseQuery->result == 1){
			$responseQuery->balance = 0;
			$responseQuery->message = "NO hay comprobantes para el cliente seleccionado en la base de datos.";
		}

		return $responseQuery;
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//Obtener el ultimo comprobante emitido
	//UPDATED
	public function getLastVoucherEmitted($myBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE idEmisor = ? ORDER BY fechaHoraEmision DESC LIMIT 1", array('i', $myBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un comprobante emitido para su empresa en la base de datos.";
		return $responseQuery;
	}

	public function getLastIdVoucherByRut($rut){
		$responseQuery = DataBase::sendQuery("SELECT * FROM `comprobantes` as c LEFT JOIN `empresas` as e on c.idEmisor = e.idEmpresa WHERE e.rut = ? ORDER BY fechaHoraEmision LIMIT 1", array('s', $rut), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un comprobante emitido para su empresa en la base de datos.";
		return $responseQuery;
	}

	//obtener un comprobante por id
	//UPDATED
	public function getVoucherEmitted($idVoucher, $idBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM comprobantes WHERE id = ? AND idEmisor = ? ", array('si', $idVoucher, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un comprobante emitido para el identificador ingresado en la base de datos.";
		return $responseQuery;
	}

	//crea un nuevo registro de un comprobante
	//UPDATED
	public function createVoucher($idClient, $dateVoucher, $typeCoin, $total,$idEmisor){
		$dbClass = new DataBase();

		error_log(date("d/m/y") . " createVoucher insertando comprobantes -> total $total, fecha $dateVoucher, $typeCoin moneda, isAnulado 0, isCobranza 1, formaPago 1, idCliente $idClient, idEmisor $idEmisor ");
		error_log(date("d/m/y")." createVoucher datos de sesion -> iduser ".$_SESSION['systemSession']->idUser .", username ".$_SESSION['systemSession']->userName .", rut". $_SESSION['systemSession']->rut .", Empresa ".$_SESSION['systemSession']->empresa .", idEmpresa ".$_SESSION['systemSession']->idEmpresa);


		return $dbClass->sendQuery("INSERT INTO comprobantes(total, fecha, moneda, isAnulado, isCobranza, formaPago, idCliente, idEmisor) VALUES (?,?,?,?,?,?,?,?)", array('disiiiii',$total, $dateVoucher, $typeCoin,0, 1, 1, $idClient, $idEmisor), "BOOLE");
	}

	//crea un nuevo registro de un comprobante
	//UPDATED	
	public function insertVoucherEmitted($id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $moneda, $sucursal, $isAnulado, $isCobranza, $fechaHoraEmision, $formaPago, $idClient, $idBusiness){
		$dbClass = new DataBase();
		error_log(date("d/m/y") . " insertVoucherEmitted insertando comprobantes -> id, tipoCFE, serieCFE, numeroCFE, total, fecha, moneda, sucursal, isAnulado, isCobranza, fechaHoraEmision, formaPago, idCliente, idEmisor $id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $moneda, $sucursal, $isAnulado, $isCobranza, $fechaHoraEmision, $formaPago, $idClient, $idBusiness");
		error_log(date("d/m/y")." insertVoucherEmitted datos de sesion -> iduser, username, rut, business, idBusiness ".$_SESSION['systemSession']->idUser .", ".$_SESSION['systemSession']->userName .", ". $_SESSION['systemSession']->rut .", ".$_SESSION['systemSession']->empresa .", ".$_SESSION['systemSession']->idEmpresa);

		return $dbClass->sendQuery("INSERT INTO comprobantes(id, tipoCFE, serieCFE, numeroCFE, total, fecha, moneda, sucursal, isAnulado, isCobranza, fechaHoraEmision, formaPago, idCliente, idEmisor) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array('sisidisiiisiii', $id, $tipoCFE, $serieCFE, $numeroCFE, $total, $fecha, $moneda, $sucursal, $isAnulado, $isCobranza, $fechaHoraEmision, $formaPago, $idClient, $idBusiness), "BOOLE");
	}

	//obtiene un comprobante segun el mayor indice que se encuentre
	//UPDATED
	public function getMaxIndexManualReceipt($myBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT MAX(indice) AS maxIndex FROM comprobantes WHERE id IS NULL AND idEmisor = ?", array('i', $myBusiness), "OBJECT");
		if($responseQuery->result == 2) return ($responseQuery->objectResult->maxIndex + 1);
	}

	//obtener comprobantes emitidos por la empresa logueada y donde el receptor del comprobante es filterNameReceiver
	//UPDATED
	public function getManualReceiptsEmitted($lastId, $filterNameReceiver, $myBusiness){
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$vouchersEmittedClass = new vouchersEmitted();
		if($lastId == 0) $lastId = $vouchersEmittedClass->getMaxIndexManualReceipt($myBusiness);
		$moreFilter = "";
		if($filterNameReceiver) $moreFilter = " AND cli.nombreReceptor LIKE '%" . $filterNameReceiver . "%' ";
		$sql = "SELECT * FROM comprobantes AS comp, clientes AS cli WHERE comp.idCliente = cli.id AND comp.indice < ? AND idEmisor = ? AND comp.id IS NULL " . $moreFilter . "ORDER BY comp.indice DESC LIMIT 15";
		error_log("SQL: $sql");
		$responseQuery = $dbClass->sendQuery($sql, array('ii', $lastId, $myBusiness), "LIST");
		if($responseQuery->result == 2) {
			$minIndex = $lastId;
			$arrayResult = array();
			foreach($responseQuery->listResult as $key => $value){
				if($minIndex > $value['indice']) $minIndex = $value['indice'];
				$newRow = array();

				$newRow['index'] = $value['indice'];
				$newRow['dateReceipt'] = $handleDateTimeClass->setFormatBarDate($value['fecha']);

				$newRow['docClient'] = $value['docReceptor'];
				$newRow['nameClient'] = $value['nombreReceptor'];

				if($value['moneda'] == "UYU") $newRow['typeCoin'] = '$';
				else $newRow['typeCoin'] = 'U$S';

				$newRow['total'] = number_format($value['total'],2,",",".");

				$arrayResult[] = $newRow;
			}
			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastId = $minIndex;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes emitidos que mostrar en la base de datos.";
		}
		return $responseQuery;
	}

	//obtengo el id del comprobante mas reciente en ser creado.
	//UPDATED
	public function getMaxIdVouchers($myBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT MAX(id) AS maxId FROM comprobantes WHERE idEmisor = ?", array('i', $myBusiness), "OBJECT");
		if($responseQuery->result == 2)
			return ($responseQuery->objectResult->maxId);
	}




	public function getVoucherByTipoSerieNum($tipoCFE, $serieCFE, $numeroCFE, $idBusiness){
		$dataBaseClass = new DataBase();
		$responseQuery = $dataBaseClass->sendQuery("SELECT indice, isAnulado, isCobranza FROM `comprobantes`
			WHERE tipoCFE = ? AND serieCFE = ? AND numeroCFE = ? AND idEmisor = ?", array('isii', $tipoCFE, $serieCFE, $numeroCFE, $idBusiness), "OBJECT");
		return $responseQuery;
	}
}