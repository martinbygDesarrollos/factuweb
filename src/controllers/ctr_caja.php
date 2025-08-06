<?php

require_once '../src/class/defaultclass/users.php';
require_once '../src/class/caja.php';
require_once '../src/class/others.php';

require_once '../src/utils/validate.php';
require_once '../src/utils/handle_date_time.php';

require_once 'rest/ctr_rest.php';
require_once 'ctr_clients.php';
require_once 'ctr_products.php';
require_once 'ctr_vouchers.php';
require_once 'ctr_vouchers_emitted.php';
require_once 'ctr_vouchers_received.php';
require_once '../src/utils/utils.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

class ctr_caja{

    //NEW
	public function getAllPOSFromCompany($idEmpresa){
		$cajaClass = new caja();
		return $cajaClass->getAllPOSFromCompany($idEmpresa);
	}
	//NEW
	public function getPOSData($idPOS){
		$cajaClass = new caja();
		return $cajaClass->getPOSData($idPOS);
	}
	//NEW
	public function getAllCajasFromCompany($idEmpresa){
		$cajaClass = new caja();
		$responseGetCajas = $cajaClass->getAllCajasFromCompany($idEmpresa);
		return $responseGetCajas;
	}
    //NEW
	public function getUserCaja($currentSession){
		$response = new \stdClass();
		$cajaClass = new caja();
		$responseGetCaja = $cajaClass->getUserCaja($currentSession->idUser);
		if($responseGetCaja->result == 2){
			$response->result = 2;
			$response->caja = $responseGetCaja->objectResult;
		} else {
			$response->result = 0;
			$response->caja = null;
		}
		return $response;
	}
	
	//NEW
	public function updateCaja($data, $idCaja){
		$response = new \stdClass();
		$cajaClass = new caja();
		$responseGetCaja = $cajaClass->updateCaja($data, $idCaja);
		if($responseGetCaja->result == 2){
			$response->result = 2;
			$response->message = "Caja actualizada con éxito!";
		} else {
			$response->result = 0;
		}
		return $response;
	}

	//NEW
	public function updatePOS($data, $idPOS){
		$response = new \stdClass();
		$cajaClass = new caja();
		$responseGetCaja = $cajaClass->updatePOS($data, $idPOS);
		if($responseGetCaja->result == 2){
			$response->result = 2;
			$response->message = "POS actualizado con éxito!";
		} else {
			$response->result = 0;
		}
		return $response;
	}

	//NEW
	public function setCajaToUser($user, $caja){
		$response = new \stdClass();
		$cajaClass = new caja();
		$responseGetCaja = $cajaClass->setCajaToUser($user, $caja);
		if($responseGetCaja->result == 2){
			$response->result = 2;
			$response->message = "Asignación Usuario - Caja realizada con éxito!";
		} else {
			$response->result = 0;
		}
		return $response;
	}

    //NEW
	public function getMovementsWithoutSnap($currentSession){
		$voucherEmittedController = new ctr_vouchers_emitted();
		$vouchReceivedController = new ctr_vouchers_received();
		$handleDateTimeClass = new handleDateTime();
		$cajaController = new ctr_caja();
		$cajaClass = new caja();

		$totales = new \stdClass();
		$totales->USD = 0;
		$totales->UYU = 0;

		$response = new \stdClass();
		$response->result = 0;
		$response->movimientos = array();
		$response->cheques = array();

		$cheques_ingreso = array();
		$cheques_egreso = array();

		$movimientoCajaSnap = null;
		$responseGetLastSnapCaja = $cajaClass->getLastSnapCaja($currentSession->caja, $currentSession->idEmpresa); 
		if($responseGetLastSnapCaja->result == 2){
			$movimientoCajaSnap = $responseGetLastSnapCaja->objectResult;
			$totales->UYU = floatval($responseGetLastSnapCaja->objectResult->saldo_UYU);
			$totales->USD = floatval($responseGetLastSnapCaja->objectResult->saldo_USD);
		}

		$responseGetMovements = $cajaClass->getMovementsWithoutSnap($currentSession->caja);
		$movimientos = array();
		if($responseGetMovements->result == 2){
			$movimientos = $responseGetMovements->listResult;
			$response->result = 2;
			foreach ($movimientos as $key => &$value) {
				$monto = 0;
				if($value['tipo'] == "ingreso"){
					if($value['medio_pago'] == 'Efectivo'){
						$monto = floatval($value['importe']);
					} else if($value['medio_pago'] == 'Cheque'){
						$cheques_ingreso[] = $value;
					}
				} else if($value['tipo'] == "egreso"){
					if($value['medio_pago'] == 'Cheque'){
						$cheques_egreso[] = $value;
					} else if($value['medio_pago'] == 'Efectivo'){
						$monto = floatval(-$value['importe']);
					}
				}
				// Sumar al total correspondiente según la moneda
				if($value['coin'] == "UYU"){
					$totales->UYU += $monto;
				} elseif($value['coin'] == "USD"){
					$totales->USD += $monto;
				}
				// A MODO DE TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST

				if($value['tipo'] == "ingreso" && $value['ref']){ // INGRESO ~ busco en emitidos (NO SEGURO)
					$voucherRef = $voucherEmittedController->getVoucherEmittedById($value['ref'], $currentSession->idEmpresa);
					if($voucherRef->result == 2){
						$tipoMap = [
							101 => 'eTicket',
							111 => 'eFactura'
							// Agrega más si tenés otros tipos
						];
						$tipoNombre = $tipoMap[$voucherRef->voucher->tipoCFE] ?? 'Desconocido';
	
						$value['ref_view'] = $tipoNombre . ' ' . $voucherRef->voucher->serieCFE . $voucherRef->voucher->numeroCFE;
					}
				} else if($value['tipo'] == "egreso" && $value['ref'] ){ // EGRESO ~ busco en recibidos (NO SEGURO)
					if($value['medio_pago'] != "Cheque"){ // SI NO ES CHEQUE
						$voucherRef = $vouchReceivedController->getVoucherReceivedById($value['ref'], $currentSession->idEmpresa);
						if($voucherRef->result == 2){
							$tipoMap = [
								101 => 'eTicket',
								111 => 'eFactura'
								// Agrega más si tenés otros tipos
							];
							$tipoNombre = $tipoMap[$voucherRef->voucher->tipoCFE] ?? 'Desconocido';
		
							$value['ref_view'] = $tipoNombre . ' ' . $voucherRef->voucher->serieCFE . $voucherRef->voucher->numeroCFE;
						}
					} else { // SI ES CHEQUE BUSCO EL REFERENCIADO Y TOMO SU REFERENCIA
						$id_ref = trim(substr($value['ref'], 7));
						$responseGetChequeEmitted = $cajaController->getMovementById($id_ref, $currentSession);
						if($responseGetChequeEmitted->result == 2){
							$id_ref = $responseGetChequeEmitted->objectResult->referencia;
							$voucherRef = $voucherEmittedController->getVoucherEmittedById($id_ref, $currentSession->idEmpresa);
							if($voucherRef->result == 2){
								$tipoMap = [
									101 => 'eTicket',
									111 => 'eFactura'
									// Agrega más si tenés otros tipos
								];
								$tipoNombre = $tipoMap[$voucherRef->voucher->tipoCFE] ?? 'Desconocido';
			
								$value['ref_view'] = $tipoNombre . ' ' . $voucherRef->voucher->serieCFE . $voucherRef->voucher->numeroCFE;
								$value['ref'] = $id_ref;
							}
						}
					}
				}

				// A MODO DE TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST TEST
			}
			// Alternativa más moderna usando str_starts_with() (PHP 8+)
			foreach ($cheques_egreso as $key => $cheque) {
				if (!empty($cheque['ref']) && str_starts_with($cheque['ref'], 'Cheque:')) {
					$parte_siguiente = trim(substr($cheque['ref'], 7));
					// Buscar si este ID está en la lista de cheques_ingreso
					$id_a_buscar = $parte_siguiente;
					foreach ($cheques_ingreso as $key_ingreso => $cheque_ingreso) {
						if ($cheque_ingreso['id'] == $id_a_buscar) {
							unset($cheques_ingreso[$key_ingreso]);
							break; // Salir del loop una vez encontrado
						}
					}
				}
			}
		} else {
			$response->result = 0;
			$response->movimientos = array();
			$response->cheques = array();
		}
		// var_dump($movimientoCajaSnap);
		if($movimientoCajaSnap){ // pongo el movimiento del cierre de caja anterior
			// array_unshift($movimientos, ['id' => $movimientoCajaSnap->id, 'tipo' => 'snap', 'fecha_hora' => $movimientoCajaSnap->fecha_hora, 'UYU' => $movimientoCajaSnap->saldo_UYU, 'USD' => $movimientoCajaSnap->saldo_USD, 'user' => $movimientoCajaSnap->usuario, 'user_name' => $movimientoCajaSnap->user_name]);
			$fechaHora = $handleDateTimeClass->formatDateTimeFromInt($movimientoCajaSnap->fecha_hora);
			$movimientos[] = ['id' => $movimientoCajaSnap->id, 'tipo' => 'snap', 'fecha' => substr($fechaHora, 0, 10), 'hora' => substr($fechaHora, 11), 'UYU' => $movimientoCajaSnap->saldo_UYU, 'USD' => $movimientoCajaSnap->saldo_USD, 'user' => $movimientoCajaSnap->usuario, 'user_name' => $movimientoCajaSnap->user_name];
			$response->result = 2;
		} else { // Si no tiene ninguno, cierro una caja en 0
			$UYU = array(
				array("2000" => "0"),
				array("1000" => "0"),
				array("500" => "0"),
				array("200" => "0"),
				array("100" => "0"),
				array("50" => "0"),
				array("20" => "0"),
				array("10" => "0"),
				array("5" => "0"),
				array("2" => "0"),
				array("1" => "0")
			);
			$USD = array(
				array("100" => "0"), 
				array("50" => "0"), 
				array("20" => "0"), 
				array("10" => "0"), 
				array("5" => "0"), 
				array("1" => "0")
			);
			$responseInsertSnap = $cajaController->newSnap(array(), array(), $UYU, $USD, 0.00, 0.00, $currentSession);
			if ($responseInsertSnap->result == 2){
				$responseGetLastSnapCaja = $cajaClass->getLastSnapCaja($currentSession->caja, $currentSession->idEmpresa); 
				if($responseGetLastSnapCaja->result == 2){
					$movimientoCajaSnap = $responseGetLastSnapCaja->objectResult;
					$fechaHora = $handleDateTimeClass->formatDateTimeFromInt($movimientoCajaSnap->fecha_hora);
					// array_unshift($movimientos, ['id' => $movimientoCajaSnap->id, 'tipo' => 'snap', 'fecha_hora' => $movimientoCajaSnap->fecha_hora, 'UYU' => $movimientoCajaSnap->saldo_UYU, 'USD' => $movimientoCajaSnap->saldo_USD, 'user' => $movimientoCajaSnap->usuario, 'user_name' => $movimientoCajaSnap->user_name]);
					$movimientos[] = ['id' => $movimientoCajaSnap->id, 'tipo' => 'snap', 'fecha' => substr($fechaHora, 0, 10), 'hora' => substr($fechaHora, 11), 'UYU' => $movimientoCajaSnap->saldo_UYU, 'USD' => $movimientoCajaSnap->saldo_USD, 'user' => $movimientoCajaSnap->usuario, 'user_name' => $movimientoCajaSnap->user_name];
					$response->result = 2;
				}
			}
		}
		
		$response->cheques = array_values($cheques_ingreso);
		$response->movimientos = array_values($movimientos);
		
		$response->totales = $totales;
		return $response;
	}

    //NEW
	public function getChequesWithoutSnap($currentSession){
		$voucherEmittedController = new ctr_vouchers_emitted();
		$totales = new \stdClass();
		$cajaClass = new caja();

		$response = new \stdClass();
		$response->result = 0;
		$response->cheques = array();

		$cheques_ingreso = array();
		$cheques_egreso = array();

		$responseGetCheques = $cajaClass->getChequesWithoutSnap($currentSession->caja);
		if($responseGetCheques->result == 2){
			$response->result = 2;
			$movimientos = $responseGetCheques->listResult;
			foreach ($movimientos as $key => $value) {
				if($value['tipo'] == "ingreso"){
					$cheques_ingreso[] = $value;
				} else {
					$cheques_egreso[] = $value;
				}
			}
			// Recorro los egresos para ver si estan los cheques que estan en ingresos
			foreach ($cheques_egreso as $key => $cheque) {
				if (!empty($cheque['ref']) && str_starts_with($cheque['ref'], 'Cheque:')) {
					$parte_siguiente = trim(substr($cheque['ref'], 7));
					// Buscar si este ID está en la lista de cheques_ingreso
					$id_a_buscar = $parte_siguiente;
					foreach ($cheques_ingreso as $key_ingreso => $cheque_ingreso) {
						if ($cheque_ingreso['id'] == $id_a_buscar) {
							unset($cheques_ingreso[$key_ingreso]);
							break; // Salir del loop una vez encontrado
						}
					}
				}
			}
			foreach ($cheques_ingreso as $key => &$value) {
				$voucherRef = $voucherEmittedController->getVoucherEmittedById($value['ref'], $currentSession->idEmpresa);
				if($voucherRef->result == 2){
					$tipoMap = [
						101 => 'eTicket',
						111 => 'eFactura'
						// Agrega más si tenés otros tipos
					];
					$tipoNombre = $tipoMap[$voucherRef->voucher->tipoCFE] ?? 'Desconocido';

					$value['ref_view'] = $tipoNombre . ' ' . $voucherRef->voucher->serieCFE . $voucherRef->voucher->numeroCFE;
				} else {
					$value['ref_view'] = 'Desconocido';
				}
			}
			$response->cheques = array_values($cheques_ingreso); // Esto reindexará el array
		} else {
			$response->result = 0;
			$response->cheques = array();
		}
		return $response;
	}
    // NEW
    public function insertMultipleMovements($movimientos, $currentSession){
        $response = new \stdClass();
		$cajaClass = new caja();
		$errores = array();
		$exitos = array();
		foreach ($movimientos as $key => $value) {
			$responseInsertMovement = $cajaClass->insertMovement($value);
			// var_dump($responseInsertMovement);
			if($responseInsertMovement->result == 2){
				$exitos[] = $value;
			} else {
				$errores[] = $value;
			}
		}
		$response->exitos = $exitos;
		$response->errores = $errores;
		$response->list = $movimientos;
		if(count($errores) > 0){
			$response->result = 1; 
		} else {
			$response->result = 2; 
		}

		return $response;
    }

	// NEW
	public function updateArqueo($arqueo){
		$response = new \stdClass();
		if( isset($_SESSION['systemSession']) ){ //verifico si un usuario tiene una sesion iniciada
			if($arqueo) {
				$_SESSION['arqueo'] = $arqueo;
				$response->result = 2;
				$response->data = $_SESSION['arqueo'];
			} else {
				unset($_SESSION['arqueo']);
				$response->result = 2;
				$response->message = "Arqueo eliminado de la sesión.";
			}
		} else{
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}
		return $response;
	}

	// NEW
	public function getArqueo(){
		$response = new \stdClass();
		if( isset($_SESSION['systemSession']) ){ //verifico si un usuario tiene una sesion iniciada
			if( isset($_SESSION['arqueo']) ) {
				$arqueo = $_SESSION['arqueo'];
				$response->result = 2;
				$response->data = $arqueo;
			} else {
				$response->result = 2;
				$response->data = null;
			}
		} else {
			$response->result = 0;
			$response->message = "Actulamente no hay una sesión activa en el sistema.";
		}
		return $response;
	}

	// NEW funcion para pasar el isAnulado a 1 por el id del campo referencia [se usa cuando se anula una factura ]
	public function anularMovementByRef($idReferencia, $currentSession){
		$cajaClass = new caja();
		return $cajaClass->anularMovementByRef($idReferencia, $currentSession->caja);
	}

    // NEW
	public function getMovementById($idMovement, $currentSession){
		$cajaClass = new caja();
		return $cajaClass->getMovementById($idMovement, $currentSession->caja);
	}

    // NEW
	public function getSnapById($idSnap, $currentSession){
		$cajaClass = new caja();
		$responseGetSnap = $cajaClass->getSnapById($idSnap, $currentSession->idEmpresa, $currentSession->caja);

		// ----------- ----------- ----------- ----------- ----------- ----------- -----------
		// Decodificar el efectivo_detalle
		$efectivoDetalle = json_decode($responseGetSnap->objectResult->efectivo_detalle, true);

		// Enriquecer los cheques si existen
		if (isset($efectivoDetalle['cheques']) && is_array($efectivoDetalle['cheques'])) {
			$chequesEnriquecidos = [];
			
			foreach ($efectivoDetalle['cheques'] as $chequeId) {
				$datosCompletos = $cajaClass->getChequeWithDetails($chequeId, $currentSession->caja);
				if($datosCompletos->result == 2){
					$chequesEnriquecidos[] = [
						'id' => $chequeId,
						'ref' => $datosCompletos->objectResult->referencia,
						'holder' => $datosCompletos->objectResult->titular,
						'importe' => $datosCompletos->objectResult->importe,
						"fecha" => substr($datosCompletos->objectResult->fecha_hora, 0, 10),
						"hora" => substr($datosCompletos->objectResult->fecha_hora, 11),
						"bank" => $datosCompletos->objectResult->banco,
						"user_name" =>$datosCompletos->objectResult->user_name,
						"user" => $datosCompletos->objectResult->usuario,
						"deferred" => $datosCompletos->objectResult->fecha_diferido
					];
				}
			}
			
			// Reemplazar el array simple con el enriquecido
			$efectivoDetalle['cheques'] = $chequesEnriquecidos;
			
			// Volver a encodear y asignar
			$responseGetSnap->objectResult->efectivo_detalle = json_encode($efectivoDetalle);
		}
		// ----------- ----------- ----------- ----------- ----------- ----------- -----------

		// var_dump($responseGetSnap); exit;
		return $responseGetSnap;
	}

    // NEW
    public function insertMovement($movimiento, $currentSession){
		$response = new \stdClass();
		$cajaController = new ctr_caja();
		$cajaClass = new caja();
		if($movimiento['tipo'] == 'egreso' && $movimiento['medio'] == 'cheque'){
			$movimientos = array();
			foreach ($movimiento['cheques'] as $key => $chequeAux) {
				$responseGetChequeEmitted = $cajaController->getMovementById($chequeAux['id'], $currentSession);
				if($responseGetChequeEmitted->result == 2){
					$cheque = $responseGetChequeEmitted->objectResult;
					$movement = array(
						"tipo" => $movimiento['tipo'],
						"medio" => $movimiento['medio'],
						"importe" => $cheque->importe,
						"fecha" => $movimiento['fecha'],
						"fecha_hora" => $movimiento['fecha_hora'],
						"moneda" => $movimiento['moneda'],
						"referencia" => "Cheque:" . $cheque->id,
						"banco" => $cheque->banco,
						"titular" => $cheque->titular,
						"fecha_diferido" => $cheque->fecha_diferido,
						"isAnulado" => 0,
						"observaciones" => $chequeAux['tipo'] == 'efectivo' ? "Egreso de cheque a efectivo" : $chequeAux['observacion'],
						"snap" => null,
						"caja" => $movimiento['caja'],
						"usuario" => $movimiento['usuario']
					);
					$movimientos[] = $movement;
				}
				if($chequeAux['tipo'] == 'efectivo'){ // A parte si alguno de los cheques es a efectivo debo hacer el ingreso
					$movement = array(
						"tipo" => 'ingreso',
						"medio" => 'efectivo',
						"importe" => $cheque->importe,
						"fecha" => $movimiento['fecha'],
						"fecha_hora" => $movimiento['fecha_hora'],
						"moneda" => $movimiento['moneda'],
						"referencia" => null,
						"banco" => null,
						"titular" => null,
						"fecha_diferido" => null,
						"isAnulado" => 0,
						"observaciones" => "Ingreso de cheque: " . $cheque->id,
						"snap" => null,
						"caja" => $movimiento['caja'],
						"usuario" => $movimiento['usuario']
					);
					$movimientos[] = $movement;
				}
			}
			// var_dump($movimientos); exit;
			return $cajaController->insertMultipleMovements($movimientos, $currentSession);
		}
		$responseInsertMovement = $cajaClass->insertMovement($movimiento);
		if($responseInsertMovement->result == 2){
			$response->result = 2; 
		} else {
			$response->result = 0; 
			$response->message = "Error. No se pudo ingresar el movimiento"; 
		}
		return $response;
    }

	
	// Función para transformar el array anidado a objeto plano
	function flattenCurrencyArray($currencyArray) {
		$result = array();
		foreach ($currencyArray as $item) {
			foreach ($item as $denomination => $quantity) {
				$result[$denomination] = $quantity;
			}
		}
		return $result;
	}

    // NEW
    public function newSnap($movimientos, $cheques, $UYU, $USD, $saldoUYU, $saldoUSD, $currentSession){
		$response = new \stdClass();
		$cajaController = new ctr_caja();
		$cajaClass = new caja();

		foreach ($movimientos as $key => $value) {
			if (in_array($value, $cheques)) {
				unset($movimientos[$key]);
			}
		}
		$movimientos = array_values($movimientos); // para reindexar

		// Transformar los arrays
		$flatUYU = $cajaController->flattenCurrencyArray($UYU);
		$flatUSD = $cajaController->flattenCurrencyArray($USD);

		$cheques = $cheques ?? []; // Si es null, queda []
		// Convertir cheques a array de números
		$chequeNumbers = array_map('intval', $cheques);

		// Crear la estructura final
		$finalData = array(
			"UYU" => $flatUYU,
			"USD" => $flatUSD,
			"cheques" => $chequeNumbers
		);
		$jsonForDB = json_encode($finalData);
		$exitos = array();
		$fallos = array();
		$responseNewSnap = $cajaClass->insertSnap($saldoUYU, $saldoUSD, $jsonForDB, $currentSession->idUser, $currentSession->idEmpresa, $currentSession->caja);
		if($responseNewSnap->result == 2){
			foreach ($movimientos as $key => $value) {
				$responseAsign = $cajaClass->asignMovementTOSnap(intval($value), $responseNewSnap->id, $currentSession->caja);
				if($responseAsign->result == 2)
					$exitos[] = $responseAsign->id;
				else
					$fallos[] = $responseAsign->id;
			}
		}
		if(count($exitos) > 0 ){
			$cajaController->updateArqueo(null);
		}
		$response->exitos = $exitos;
		$response->fallos = $fallos;
		$response->result = count($fallos) == 0 ? 2 : 1;

		return $response;
    }
}