<?php

require_once '../src/utils/handle_date_time.php';
require_once '../src/utils/utils.php';

class vouchersReceived{

	public function getMinAndMaxDateVoucher($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT MIN(fecha) AS minDate, MAX(fecha) AS maxDate FROM comprobantes_recibidos WHERE idReceptor = ?", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->objectResult->minDate = handleDateTime::setFormatHTMLDate($responseQuery->objectResult->minDate);
			$responseQuery->objectResult->maxDate = handleDateTime::setFormatHTMLDate($responseQuery->objectResult->maxDate);
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes recibidos de los cuales obtener la fecha máxima y mínima.";
		}
		return $responseQuery;
	}

	public function getTypeExistingVouchers($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT DISTINCT tipoCFE, isCobranza FROM comprobantes_recibidos WHERE idReceptor = ? ORDER BY tipoCFE", array('i', $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				if(!is_null($value['tipoCFE'])){
					$newRow = array();
					$newRow['typeCFE'] = $value['tipoCFE'];
					$responseNameCFE = utils::getNameVoucher($value['tipoCFE'], $value['isCobranza']);

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

	public function getMaxDateReceivedVouchers(){
		$responseQuery = DataBase::sendQuery("SELECT fechaEmision FROM comprobantes_recibidos WHERE id IS NOT NULL ORDER BY fechaEmision DESC LIMIT 1", null, "OBJECT");
		if($responseQuery->result == 2) return ($responseQuery->objectResult->fechaEmision);
	}

	public function getVouchersReceived($dateReceived, $payMethod, $typeVoucher, $dateVoucher, $numberVoucher, $documentProvider, $idBusiness){
		if($dateReceived == 0) $dateReceived = vouchersReceived::getMaxDateReceivedVouchers();

		$withPayMethod = "";
		if($payMethod != 0)
			$withPayMethod = " AND formaPago = " . $payMethod . " ";

		$withTypeVoucher = "";
		if($typeVoucher != 0){
			if(strlen($typeVoucher) == 4){
				$typeVoucher = substr($typeVoucher, 0, 3);
				$withTypeVoucher = " AND tipoCFE = " . $typeVoucher . " AND isCobranza = 1 ";
			}
			else $withTypeVoucher = " AND tipoCFE = " . $typeVoucher . " AND isCobranza = 0 ";
		}

		$withDateVoucher = "";
		if($dateVoucher != 0)
			$withDateVoucher = " AND fecha = " . $dateVoucher . " ";

		$withNumberVoucher = "";
		if($numberVoucher != 0)
			$withNumberVoucher = " AND numeroCFE LIKE '%" . $numberVoucher . "%' ";

		$withProvider = "";
		if(!empty($documentProvider)){
			$withProvider = " AND idProveedor IN (SELECT idProveedor FROM proveedores WHERE rut LIKE '%" . $documentProvider . "%' OR razonSocial LIKE '%" . $documentProvider . "%') ";
		}

		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE fechaEmision <= ? AND idReceptor = ? AND id IS NOT NULL " . $withPayMethod . $withTypeVoucher . $withDateVoucher . $withNumberVoucher . $withProvider . " ORDER BY fechaEmision DESC LIMIT 20", array('si', $dateReceived, $idBusiness), "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			$newLastId = $dateReceived;
			foreach ($responseQuery->listResult as $key => $value){
				if($newLastId > $value['fechaEmision']) $newLastId = $value['fechaEmision'];

				$newRow = array();
				$value['total'] = number_format($value['total'], 2, ",", ".");
				$value['tipoCFE'] = utils::getNameVoucher($value['tipoCFE'], $value['isCobranza']);

				$value['fecha'] = substr(handleDateTime::setFormatBarDateTime($value['fechaEmision']), 0, 16);

				if($value['formaPago'] == 1) $value['formaPago'] = "Contado";
				else $value['formaPago'] = "Crédito";

				if(strcmp($value['moneda'], "UYU") == 0) $value['moneda'] = '$';
				else $value['moneda'] = 'U$S';

				$arrayResult[] = $value;
			}

			array_multisort(array_map(function($element) {
				return $element['fechaEmision'];
			}, $arrayResult), SORT_ASC, $arrayResult);

			$responseQuery->listResult = $arrayResult;
			$responseQuery->dateReceived = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay comprobantes para su empresa en la base de datos.";
		}
		return $responseQuery;
	}

	public function deleteManualReceiptReceived($indexVoucher, $idBusiness){
		return DataBase::sendQuery("DELETE FROM comprobantes_recibidos WHERE indice = ? AND idReceptor = ?", array('ii', $indexVoucher, $idBusiness), "BOOLE");
	}

	public function modifyManualReceiptReceived($indexVoucher, $dateMaked, $total, $idBusiness){
		return DataBase::sendQuery("UPDATE comprobantes_recibidos SET total = ?, fecha = ? WHERE indice = ? AND idReceptor = ?", array('diii', $total, $dateMaked, $indexVoucher, $idBusiness), "BOOLE");
	}

	public function getManualReceiptReceived($index, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE indice = ? AND idReceptor = ? ", array('ii', $index, $idBusiness), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->objectResult->fecha = handleDateTime::setFormatBarDate($responseQuery->objectResult->fecha);
			$responseQuery->objectResult->total = number_format($responseQuery->objectResult->total, 2, ",", ".");
		}else if($responseQuery->result == 1){
			$responseQuery->message = "No se encontró un recibo manual con el indice seleccionado en la base de datos.";
		}
		return $responseQuery;
	}

	public function createManualReceiptReceived($idProvider, $dateMaked, $total, $typeCoin, $idBusiness){
		return DataBase::sendQuery('INSERT INTO comprobantes_recibidos(idProveedor, idReceptor, total, fecha, moneda) VALUES (?,?,?,?,?)', array('iidis', $idProvider, $idBusiness, $total, $dateMaked, $typeCoin), "BOOLE");
	}

	public function getLastIdReceived(){
		$responseQuery = DataBase::sendQuery("SELECT MAX(indice) AS lastId FROM comprobantes_recibidos", null, "OBJECT");
		if($responseQuery->result == 2) return ($responseQuery->objectResult->lastId + 1);
	}

	public function getManualReceiptsReceived($lastId, $filterNameReceiver, $myBusiness){
		if($lastId == 0) $lastId = vouchersReceived::getLastIdReceived();

		$searchWhere = "";
		if(!is_null($filterNameReceiver))
			$searchWhere = " AND P.razonSocial LIKE '%" . $filterNameReceiver . "%' ";

		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos AS CR, proveedores AS P WHERE CR.idProveedor = P.idProveedor " . $searchWhere . " AND CR.id IS NULL AND CR.idReceptor = ? AND CR.indice < ? ORDER BY CR.indice DESC LIMIT 20", array('ii', $myBusiness, $lastId), "LIST");
		if($responseQuery->result == 2){
			if(sizeof($responseQuery->listResult) > 0){
				$newLastID = $lastId;
				$listResult = array();
				foreach ($responseQuery->listResult as $key => $row) {
					if($newLastID > $row['indice']) $newLastID = $row['indice'];

					$row['total'] = number_format($row['total'],2,",",".");
					$row['fecha'] = handleDateTime::setFormatBarDate($row['fecha']);

					if($row['moneda'] == "UYU") $row['moneda'] = '$';
					else $row['moneda'] = 'U$S';

					$listResult[] = $row;
				}
				$responseQuery->listResult = $listResult;
				$responseQuery->lastId = $newLastID;
			}
		}else if($responseQuery->result == 1){
			$responseQuery->message = "No hay recibos manuales que listar en la base de datos.";
		}

		return $responseQuery;
	}

	public function getLastVoucherReceived($idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE idReceptor = ? ORDER BY fechaEmision DESC LIMIT 1", array('i', $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontraron comprobantes recibidos en la base de datos.";
		return $responseQuery;
	}

	public function getBalanceToDateReceived($idProvider, $typeCoin, $dateLimitINT, $myBusiness){
		/* aca tambien solo los 2 o cobranza*/
		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE fecha < ? AND idProveedor = ? AND moneda = ?  AND idReceptor = ? AND (formaPago = 2 OR isCobranza = 1)", array('iisi', $dateLimitINT, $idProvider, $typeCoin, $myBusiness), "LIST");
		if($responseQuery->result == 2){
			$resultDebe = 0;
			$resultHaber = 0;
			foreach($responseQuery->listResult as $key => $value){

				if($value['isCobranza'] == 1){
					$resultHaber = $resultHaber + $value['total'];
				}else if($value['tipoCFE'] < 150 and substr($value['tipoCFE'],-1) == 1)
				$resultDebe = $resultDebe + $value['total'];
				else if($value['tipoCFE'] < 150 and substr($value['tipoCFE'],-1) == 2)
					$resultHaber = $resultHaber + $value['total'];
				else if($value['tipoCFE'] < 150 and substr($value['tipoCFE'],-1) == 3)
					$resultDebe = $resultDebe + $value['total'];
			}

			unset($responseQuery->listResult);
			$responseQuery->balance = $resultDebe - $resultHaber;
		}else if($responseQuery->result == 1){
			$responseQuery->balance = 0;
			$responseQuery->message = "Actulamente no hay recibos manuales recibidos en la base de datos.";
		}

		return $responseQuery;
	}

	public function getVoucherReceived($id, $idBusiness){
		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE id = ? AND idReceptor = ?", array('si', $id, $idBusiness), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un comprobante recibido con el identificador seleccionado en la base de datos.";
		return $responseQuery;
	}

	public function insertVoucherReceived($id, $idProvider, $idReceiver, $tipoCFE, $serieCFE, $numeroCFE, $total, $dateVoucher, $typeCoin, $sucursal, $isAnulado, $isCobranza, $dateEmited, $payMethod, $dateExpiration){
		return DataBase::sendQuery("INSERT INTO comprobantes_recibidos(id, idProveedor, idReceptor, tipoCFE, serieCFE, numeroCFE, total, fecha, moneda, sucursal, isAnulado, isCobranza, fechaEmision, formaPago, vencimiento) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array('siiisidisiiisii', $id, $idProvider, $idReceiver, $tipoCFE, $serieCFE, $numeroCFE, $total, $dateVoucher, $typeCoin, $sucursal, $isAnulado, $isCobranza, $dateEmited, $payMethod, $dateExpiration), "BOOLE");
	}

	public function getAccountState($idProvider, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness){
		/* el cobranza pueden haberlo puesto contado o credito por error, incluir ambos */
		$responseQuery = DataBase::sendQuery("SELECT * FROM comprobantes_recibidos WHERE isAnulado != 1 AND idProveedor = ? AND fecha >= ? AND fecha <= ? AND moneda = ? AND idReceptor = ? AND (formaPago = 2 OR isCobranza = 1) ORDER BY fecha", array('iiisi',$idProvider, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness), "LIST");
		if($responseQuery->result != 0){
			$arrayResult = array();
			$saldoInicial = vouchersReceived::getBalanceToDateReceived($idProvider, $typeCoin, $dateInitINT, $myBusiness);
			$arrayResult[] = array(
				"FECHA" => handleDateTime::setFormatBarDate($dateInitINT),
				"DOCUMENTO" => "Saldo inicial",
				"MONEDA" => $typeCoin,
				"DEBE" => 0,
				"INTDEBE" => 0,
				"HABER" => 0,
				"INTHABER" => 0,
				"SALDO" => number_format($saldoInicial->balance,2,",","."),
				"INTSALDO" => $saldoInicial->balance
			);

			if($responseQuery->result == 2){
				foreach($responseQuery->listResult as $key => $row){
					$newRow = array();

					$newRow['id'] = $row['id'];
					$newRow['FECHA'] = handleDateTime::setFormatBarDate($row['fecha']);

					if($row['isCobranza'] == 1 && $row['id']) $newRow['DOCUMENTO'] = "Recibo " . $row['serieCFE'] . $row['numeroCFE'];
					else if($row['isCobranza'] == 1 && !$row['id']) $newRow['DOCUMENTO'] = "Recibo manual";
					else $newRow['DOCUMENTO'] = utils::getNameVoucher($row['tipoCFE'], $row['isCobranza']). " " . $row['serieCFE'] . $row['numeroCFE'];

					$newRow['MONEDA'] = $row['moneda'];
					$debe = 0;
					$haber = 0;

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
						$newRow['INTHABER'] = $row['total']; //valor numero entero
						$haber = $row['total'];
					}elseif($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 3){
						$newRow['DEBE'] = number_format($row['total'],2,",",".");
						$newRow['INTDEBE'] = $row['total'];
						$debe = $row['total'];
						$newRow['HABER'] = 0;
						$newRow['INTHABER'] = 0;
					}

					if(isset($newRow['HABER'])){
						$saldoInicial->balance = $saldoInicial->balance + $debe - $haber;
						$newRow['SALDO'] = number_format($saldoInicial->balance, 2, ",", ".");
						$newRow['INTSALDO'] = $saldoInicial->balance;
						$arrayResult[] = $newRow;
					}
				}
			}else if($responseQuery->result == 1){
				$responseQuery->message = "Actualmente no hay comprobantes recibidos para el proveedor seleccionado en la base de datos.";
			}

			$balanceDollar = vouchersReceived::getBalanceToDateReceived($idProvider, "USD", handleDateTime::getCurrentDateTimeInt(), $myBusiness);
			$balancePesos = vouchersReceived::getBalanceToDateReceived($idProvider, "UYU", handleDateTime::getCurrentDateTimeInt(), $myBusiness);

			$responseQuery->listResult = array(
				"listResult" => $arrayResult,
				"MAINCOIN" => $typeCoin,
				"SALDOTOTAL" => number_format($saldoInicial->balance, 2, ",", "."),
				"INTSALDOTOTAL" => $saldoInicial->balance,
				"DATEENDING" => handleDateTime::setFormatBarDate($dateEndingINT),
				"BALANCEUSD" => number_format($balanceDollar->balance, 2, ",", "."),
				"INTBALANCEUSD" => $balanceDollar->balance,
				"BALANCEUYU" => number_format($balancePesos->balance, 2, ",", "."),
				"INTBALANCEUYU" => $balancePesos->balance
			);
		}

		return $responseQuery;
	}




	public function saldoPendienteProveedor($idProvider, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness){

		$dataBase = new DataBase();
		$responseQuery = $dataBase->sendQuery("SELECT * FROM comprobantes_recibidos
				WHERE isAnulado != 1 AND idProveedor = ? AND fecha >= ? AND fecha <= ? AND moneda = ? AND idReceptor = ? AND (formaPago = 2 OR isCobranza = 1)
				ORDER BY fecha",
			array('iiisi',$idProvider, $dateInitINT, $dateEndingINT, $typeCoin, $myBusiness), "LIST");


		if($responseQuery->result != 0){
			$arrayResult = array();
			$saldoInicial = vouchersReceived::getBalanceToDateReceived($idProvider, $typeCoin, $dateInitINT, $myBusiness)->balance;

			if($responseQuery->result == 2){
				foreach($responseQuery->listResult as $key => $row){

					$debe = 0;
					$haber = 0;

					if($row['isCobranza'] == 1){
						$haber = $row['total'];
					}elseif ($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 1){
						$debe = $row['total'];
					}elseif($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 2){
						$haber = $row['total'];
					}elseif($row['tipoCFE'] < 150 and substr($row['tipoCFE'],-1) == 3){
						$debe = $row['total'];
					}

					$saldoInicial = $saldoInicial + $debe - $haber;
				}
			}

			return $saldoInicial;
		}

		return $responseQuery;
	}
}