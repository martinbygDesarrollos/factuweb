<?php

require_once '../src/utils/handle_date_time.php';
require_once '../src/connection/open_connection.php';

class caja{

    //NEW
    public function getAllPOSFromCompany($idEmpresa){
		$dbClass = new DataBase();
    	$responseQuery = $dbClass->sendQuery("SELECT * FROM pos WHERE empresa = ?", array('i', $idEmpresa), "LIST");
		$arrayResult = array();
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				$arrayResult[] = ["id" => $row['id'], "marca" => $row['marca'], "terminal" => $row['termCod'], "hash" => $row['empHash'], "codigo" => $row['empCod']];
			}
		} else if($responseQuery->result == 1){
			$responseQuery->message = "Ningun POS para esta empresa";
		}
		$responseQuery->listPOS = $arrayResult;
		return $responseQuery;
    }

	//NEW
    public function getPOSData($idPOS){
		$dbClass = new DataBase();
    	$responseQuery = $dbClass->sendQuery("SELECT * FROM pos WHERE id = ?", array('i', $idPOS), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->POS = $responseQuery->objectResult;
		} else {
			$responseQuery->message = "Error al obtener el la información del POS";
		}
		return $responseQuery;
    }

	//NEW
    public function getUserCaja($idUser){
		$dbClass = new DataBase();
    	// $responseQuery = $dbClass->sendQuery("SELECT * FROM caja WHERE usuario = ?", array('i', $idUser), "OBJECT");
		$responseQuery = $dbClass->sendQuery("SELECT c.* FROM caja c INNER JOIN usuarios u ON c.id = u.caja WHERE u.idUsuario = ?", array('i', $idUser), "OBJECT");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "Error al obtener la caja asociada al usuario.";
    	return $responseQuery;
    }

    //NEW
	public function insertCaja($idBusiness, $moneda, $idUser, $nombre){ // EL USUARIO QUE QUEDA EN LA CAJA NO IMPORTA POR AHORA, SOLO IMPORTA LA CAJA DEL USUARIO
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("INSERT INTO caja (usuario, empresa, moneda, nombre, fecha_creacion) VALUES (?,?,?,?,?)",array('iisss', $idUser, $idBusiness, $moneda, $nombre, date('YmdHis')), "BOOLE");
		return $responseQuery;
	}

    //NEW   asignMovementTOSnap(intval($value), $responseNewSnap->id)
	public function asignMovementTOSnap($movement, $snap, $caja){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("UPDATE caja_movimiento SET snap = ? WHERE id = ? AND caja = ?",array('iii', $snap, $movement, $caja), "BOOLE");
		return $responseQuery;
	}

    //NEW 
	public function updateCaja($data, $idCaja){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("UPDATE caja SET nombre = ?, observaciones = ?, moneda = ?, POS = ? WHERE id = ?",array('sssii', $data['nombre'], $data['observacion'], $data['moneda'], $data['POS'], $idCaja), "BOOLE");
		return $responseQuery;
	}

	//NEW
	public function updatePOS($data, $idPOS){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("UPDATE pos SET marca = ?, empCod = ?, termCod = ?, empHash = ? WHERE id = ?",array('ssssi', $data['marca'], $data['codigo'], $data['terminal'], $data['hash'], $idPOS), "BOOLE");
		return $responseQuery;
	}
    
	//NEW 
	public function setCajaToUser($user, $caja){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("UPDATE usuarios SET caja = ? WHERE idUsuario = ?",array('ii', $caja, $user), "BOOLE");
		return $responseQuery;
	}

    //NEW   anula todos los movimientos relacionados a esa referencia
	public function anularMovementByRef($idReferencia, $caja){
		$response = new \stdClass();
		$dbClass = new DataBase();
		$procesados = array();
		$cheques = array();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM caja_movimiento WHERE snap IS NULL AND caja = ? AND referencia = ?", array('is', $caja, $idReferencia), "LIST");
		// Recorro todos los movimientos que tengan de referencia ese <ID>
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				$procesados[] = $dbClass->sendQuery("UPDATE caja_movimiento SET isAnulado = 1 WHERE id = ?", array('i', intval($row['id'])), "BOOLE")->id;
				if($row['medio'] == 'Cheque'){
					$cheques[] = $row;
				}
			}
		}
		// Todos los de tipo cheque si tienen referencia Chque:<ID> (Un egreso de un cheque seria asi y el ingreso ya lo habria anulado en el loop anterior)
		foreach ($cheques as $key => $row) {
			$procesados[] = $dbClass->sendQuery("UPDATE caja_movimiento SET isAnulado = 1 WHERE snap IS NULL AND referencia = ?",array('s', "Cheque:" . $row['id']), "BOOLE")->id;
		}
		// Vuelvo a recorrer a todos por si algun cheque se paso a efectivo (Tendria un ingreso de efectivo con obs: Ingreso de cheque: <ID>)
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				foreach ($cheques as $key => $cheq) {
					$procesados[] = $dbClass->sendQuery("UPDATE caja_movimiento SET isAnulado = 1 WHERE observaciones = ?", array('i', "Ingreso de cheque: " . $cheq['id']), "BOOLE")->id;
				}
			}
		}

		$response->result = 2;
		$response->procesados = $procesados;
		return $response;
	}

    //NEW
	public function insertSnap($saldoUYU, $saldoUSD, $jsonForDB, $idUser, $idEmpresa, $caja){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("INSERT INTO caja_snap (fecha_hora, saldo_UYU, saldo_USD, ingresos, egresos, efectivo_detalle, usuario, empresa, caja) VALUES (?,?,?,?,?,?,?,?,?)",array('ssssssiii',  date('YmdHis'), $saldoUYU, $saldoUSD, null, null, $jsonForDB, $idUser, $idEmpresa, $caja), "BOOLE");
		return $responseQuery;
	}

    //NEW
	public function getAllCajasFromCompany($idBusiness){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM caja WHERE empresa = ?", array('i', $idBusiness), "LIST");
		$arrayResult = array();
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				$arrayResult[] = ["id" => $row['id'], "nombre" => $row['nombre'], "obs" => $row['observaciones'], "moneda" => $row['moneda'], "pos" => $row['POS'] ];
			}
		} else if($responseQuery->result == 1)
			$responseQuery->message = "Ninguna caja para esta empresa";

		$responseQuery->listResult = $arrayResult;
		return $responseQuery;
	}

    //NEW
	public function getMovementsWithoutSnap($caja){ // Obtener todos los cheques y efectivo de ingreso y egreso
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$responseQuery = $dbClass->sendQuery("SELECT  
													cm.*, 
													u.correo as user_name
												FROM caja_movimiento cm
												LEFT JOIN usuarios u ON cm.usuario = u.idUsuario
												WHERE cm.caja = ? AND cm.snap IS NULL AND (cm.isAnulado IS NULL OR cm.isAnulado = 0) 
												ORDER BY cm.id DESC", array('i', $caja), "LIST");
		$arrayResult = array();
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				$fechaHora = $handleDateTimeClass->formatDateTimeFromInt($row['fecha_hora']);
				$arrayResult[] = 
				[
					"id" => $row['id'],
					"tipo" => $row['tipo'],
					"obs" => $row['observaciones'],
					"coin" => $row['moneda'],
					"fecha" => substr($fechaHora, 0, 10),
					"hora" => substr($fechaHora, 11),
					"importe" => $row['importe'],
					"ref" => $row['referencia'],
					"snap" => $row['snap'],
					"caja" => $row['caja'],
					"user_name" => $row['user_name'],
					"user" => $row['usuario'],
					"medio_pago" => $row['medio']
				];
			}
		} else if($responseQuery->result == 1)
			$responseQuery->message = "Ningun movimiento pendiente";

		$responseQuery->listResult = $arrayResult;
		return $responseQuery;
	}

    //NEW
	public function getChequesWithoutSnap($caja){ // Obtener todos los cheques que no estan asociados a ningun snap y son de ingreso(? o sea que estan en caja
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$responseQuery = $dbClass->sendQuery("SELECT  
													cm.*, 
													u.correo as user_name
												FROM caja_movimiento cm
												LEFT JOIN usuarios u ON cm.usuario = u.idUsuario
												WHERE cm.caja = ? AND (cm.isAnulado IS NULL OR cm.isAnulado = 0) AND cm.medio = 'Cheque'
												ORDER BY cm.id DESC", array('i', $caja), "LIST");
		$arrayResult = array();
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $row) {
				$fechaHora = $handleDateTimeClass->formatDateTimeFromInt($row['fecha_hora']);
				$arrayResult[] = 
				[
					"id" => $row['id'],
					"tipo" => $row['tipo'],
					"obs" => $row['observaciones'],
					"coin" => $row['moneda'],
					"fecha" => substr($fechaHora, 0, 10),
					"hora" => substr($fechaHora, 11),
					"importe" => $row['importe'],
					"ref" => $row['referencia'],
					"snap" => $row['snap'],
					"caja" => $row['caja'],
					"user_name" => $row['user_name'],
					"user" => $row['usuario'],
					"bank" => $row['banco'],
					"deferred" => $row['fecha_diferido'],
					"holder" => $row['titular']
				];
			}
		} else if($responseQuery->result == 1)
			$responseQuery->message = "Ningun cheque pendiente";

		$responseQuery->listResult = $arrayResult;
		return $responseQuery;
	}

	//NEW
	// public function newMovement($movement, $idUser){
	// 	$dbClass = new DataBase();
	// 	$responseQuery = $dbClass->sendQuery("INSERT INTO caja_movimiento (tipo, importe, moneda, fecha_hora, isAnulado, referencia, observaciones, snap, caja, usuario) VALUES (?,?,?,?,?,?,?,?,?,?)",array('ssssisssii', $movement['tipo'],$movement['importe'],$movement['moneda'],$movement['fecha_hora'],$movement['isAnulado'],$movement['referencia'],$movement['observaciones'],$movement['snap'], $movement['caja'],$movement['usuario']), "BOOLE");
	// 	return $responseQuery;
	// }

	//NEW
	public function getMovementById($idMovement, $caja){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * from caja_movimiento WHERE id = ? AND caja = ?",array('ii', $idMovement, $caja), "OBJECT");
		return $responseQuery;
	}

	//NEW
	public function getChequeWithDetails($idCheque, $caja){
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
		$responseQuery = $dbClass->sendQuery("SELECT  
													cm.*, 
													u.correo as user_name
												FROM caja_movimiento cm
												LEFT JOIN usuarios u ON cm.usuario = u.idUsuario
												WHERE cm.caja = ? AND cm.id = ?", array('ii', $caja, $idCheque), "OBJECT");
		return $responseQuery;
	}


	//NEW
	public function getSnapById($idSnap, $idEmpresa, $caja){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT cs.*, u.correo as user_name FROM caja_snap cs LEFT JOIN usuarios u ON cs.usuario = u.idUsuario WHERE cs.id = ? AND cs.empresa = ? AND cs.caja = ?", array('iii', $idSnap, $idEmpresa, $caja), "OBJECT");
		return $responseQuery;
	}

	//NEW
	public function getLastSnapCaja($caja, $idEmpresa){ // OBENTER EL ULTIMO CIERRE DE CAJA PARA TENER LOS SALDOS
		error_log("getLastSnapCaja caja: $caja, Empresa: $idEmpresa");
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT cs.*, u.correo as user_name FROM caja_snap cs LEFT JOIN usuarios u ON cs.usuario = u.idUsuario WHERE cs.caja = ? AND cs.empresa = ? ORDER BY id DESC LIMIT 1",array('ii', $caja, $idEmpresa), "OBJECT");
		return $responseQuery;
	}

	//NEW
	public function insertMovement($mov){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("INSERT INTO caja_movimiento (tipo, medio, fecha, banco, fecha_diferido, titular, importe, moneda, fecha_hora, isAnulado, referencia, observaciones, snap, caja, usuario)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
			array('sssssssssissiii',
					$mov['tipo'],
					$mov['medio'],
					$mov['fecha'],
					$mov['banco'],
					$mov['fecha_diferido'],
					$mov['titular'],
					$mov['importe'],
					$mov['moneda'],
					$mov['fecha_hora'],
					$mov['isAnulado'],
					$mov['referencia'],
					$mov['observaciones'],
					$mov['snap'],
					$mov['caja'],
					$mov['usuario']),
			"BOOLE");
		return $responseQuery;
	}

	//NEW
	public function insertSnapCaja($snap){
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("INSERT INTO caja_snap (fecha_hora, saldo_UYU, saldo_USD, ingresos, egresos, efectivo_detalle, usuario, empresa, caja)
			VALUES (?,?,?,?,?,?,?,?,?)",
			array('ssssssiii',
					$snap['fecha_hora'],
					$snap['saldo_UYU'],
					$snap['saldo_USD'],
					$snap['ingresos'],
					$snap['egresos'],
					$snap['efectivo_detalle'],
					$snap['usuario'],
					$snap['empresa'],
					$snap['caja']),
			"BOOLE");
		return $responseQuery;
	}

	//NEW
	// public function insertMedioPago($medioPago, $idMovimiento){
	// 	$dbClass = new DataBase();
	// 	$responseQuery = $dbClass->sendQuery("INSERT INTO medio_pago (tipo, fecha, observaciones, importe, banco, fecha_diferido, enCaja, titular, caja_movimiento) VALUES (?,?,?,?,?,?,?,?,?)",array('ssssssssi', $medioPago['glosa'],$medioPago['fecha'],$medioPago['obs'],$medioPago['valor'],$medioPago['banco'],$medioPago['fecha_diferido'],$medioPago['enCaja'],$medioPago['titular'], $idMovimiento), "BOOLE");
	// 	return $responseQuery;
	// }

}