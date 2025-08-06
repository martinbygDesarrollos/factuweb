<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';
require_once '../src/controllers/ctr_payment.php';
require_once '../src/controllers/ctr_caja.php';


return function (App $app){
	$container = $app->getContainer();
	$userClass = new users();
	$cajaController = new ctr_caja();
	$userController = new ctr_users();
    $paymentController = new ctr_payment();
    
    $app->get('/testTarjeta', function ($request, $response, $args) use ($container, $userController) {
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
			$args['systemSession'] = $responseCurrentSession->currentSession;
			$args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
			return $this->view->render($response, "testTarjeta.twig", $args);
		}else return $response->withRedirect($request->getUri()->getBaseUrl());
	})->setName("TestTarjeta");

	$app->post('/postearTransaccion', function(Request $request, Response $response) use ($userClass, $userController, $paymentController, $cajaController){ // VTA
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
				$data = $request->getParams();
				$monto = floatval($data['monto']); // Este es el monto a prorratear
				$consumidorFinal = filter_var($data['consumidorFinal'] ?? false, FILTER_VALIDATE_BOOLEAN); // "true", "1", 1, true → true | "false", "0", 0, false, null → false
				$facturaNro = floatval($data['numeroFactura']);

				$caja = null;
				$idPOS = null;
				$POS = null;
				$moneda = null;

				$totalGravado = 0;  // Base imponible total (sin IVA)
				$totalIVA = 0;      // IVA total
				$totalFactura = 0;  // Total de la factura

				$responseGetCaja = $cajaController->getUserCaja($responseCurrentSession->currentSession);
				if($responseGetCaja->result == 2){
					$caja = $responseGetCaja->caja;
					$idPOS = $caja->POS;
				} else {
					return json_encode(['result' => '0', 'message' => 'Erro interno del servidor']);
				}
				$responseGetPOS = $cajaController->getPOSData($idPOS);
				if($responseGetPOS->result == 2){
					$POS = $responseGetPOS->POS;
				} else {
					return json_encode(['result' => '0', 'message' => 'Erro interno del servidor']);
				}

				$monedaISO = $caja->moneda == "UYU" ? '0858' :  '0840';
				$operacion = 'VTA';

				// Configuración de descuentos
				$configDiscountInPercentage = null; // true para porcentaje, false para valor absoluto
				$idUser = $responseCurrentSession->currentSession->idUser;
				$responseGetFormato = $userClass->getConfigurationUser($idUser, "DESCUENTO_EN_PORCENTAJE");
				if($responseGetFormato->result == 2){
					$configDiscountInPercentage = $responseGetFormato->objectResult->valor == "SI" ? true : false;
				}
				// Datos del carrito (tu array)
				$cart = $responseCurrentSession->currentSession->cart;
				// Procesar cada producto del carrito
				foreach ($cart as $element) {
					// Convertir strings a números
					$import = floatval($element['import']);
					$quantity = intval($element['quantity']);
					$discount = floatval($element['discount']);
					$idIva = intval($element['idIva']);
					
					// PASO 1: Calcular precio con descuento (con IVA incluido)
					$precioConIVAyDescuento = 0;
					
					if (!$configDiscountInPercentage) {
						// Descuento en valor absoluto
						$precioConIVAyDescuento = ($import - $discount) * $quantity;
					} else {
						// Descuento en porcentaje
						$precioConIVAyDescuento = ($import * $quantity) * ((100 - $discount) / 100);
					}
					
					// PASO 2: Separar precio sin IVA del precio con IVA
					$precioSinIVA = 0;
					$factorIVA = 0;
					
					switch ($idIva) {
						case 1: // 0% IVA (Exento)
							$precioSinIVA = $precioConIVAyDescuento; // El precio ya está sin IVA
							$factorIVA = 0;
							break;
							
						case 2: // 10% IVA (Mínimo)
							$precioSinIVA = $precioConIVAyDescuento / 1.10; // Quitar el 10% de IVA
							$factorIVA = 0.10;
							break;
							
						case 3: // 22% IVA (Básico)
							$precioSinIVA = $precioConIVAyDescuento / 1.22; // Quitar el 22% de IVA
							$factorIVA = 0.22;
							break;
							
						default: // Cualquier otro caso = 0% IVA
							$precioSinIVA = $precioConIVAyDescuento;
							$factorIVA = 0;
							break;
					}
					
					// PASO 3: Calcular IVA y base gravada
					$ivaProducto = 0;
					$baseGravadaProducto = 0;
					
					if ($idIva === 1 || $factorIVA === 0) {
						$baseGravadaProducto = $precioSinIVA;
						$ivaProducto = 0;
					} else {
						// Producto gravado
						$baseGravadaProducto = $precioSinIVA;
						$ivaProducto = $precioSinIVA * $factorIVA;
					}
					
					// PASO 4: Sumar a los totales
					$totalGravado += $baseGravadaProducto;
					$totalIVA += $ivaProducto;
					$totalFactura += $precioSinIVA + $ivaProducto;
					
					// Guardar item procesado para debug
					$itemsProcessed[] = [
						'barcode' => $element['barcode'],
						'description' => $element['description'],
						'quantity' => $quantity,
						'import_original' => $import,
						'discount' => $discount,
						'precio_con_descuento' => $precioConIVAyDescuento,
						'precio_sin_iva' => $precioSinIVA,
						'base_gravada' => $baseGravadaProducto,
						'iva' => $ivaProducto,
						'total' => $precioSinIVA + $ivaProducto,
						'id_iva' => $idIva
					];
				}

				if($monto < $totalFactura){ // PRORRATEAR
					$proporcion = $monto / $totalFactura;
					
					$totalGravadoNew = $totalGravado * $proporcion;
					$totalIVANew = $totalIVA * $proporcion;
					
					// Ajuste por redondeo si es necesario
					$diferencia = $monto - ($totalGravadoNew + $totalIVANew);
					if(abs($diferencia) > 0.001) {
						$totalGravadoNew += $diferencia;
					}
					
					$totalFacturaNew = $monto; // El total ahora es el monto

					// { // LOGS
					// var_dump("TOTAL GRAVADO NUEVO: " . round($totalGravadoNew, 2));
					// var_dump("TOTAL IVA NUEVO: " . round($totalIVANew, 2));
					// var_dump("TOTAL FACTURA NUEVO: " . round($totalFacturaNew, 2));
					// }

					$totalIVA = $totalIVANew;
					$totalGravado = $totalGravadoNew;
					$totalFactura = $totalFacturaNew;
				}

				// { // LOGS
				// 	var_dump("MONTO: $monto");
				// 	var_dump("MONEDA: $monedaISO");
				// 	var_dump("CONSUMIDOR FINAL: $consumidorFinal");
				// 	var_dump("OPERACION: $operacion");
				// 	// var_dump("CODIGO EMPRESA: $POS->empCod");
				// 	// var_dump("TERMINAL: $POS->termCod");
				// 	// var_dump("HASH EMPRESA: $POS->empHash");

				// 	var_dump("TOTAL GRAVADO: " . round($totalGravado, 2));
				// 	var_dump("TOTAL IVA: " . round($totalIVA, 2));
				// 	var_dump("TOTAL FACTURA: " . round($totalFactura, 2));
				// }
				// exit;
				// Enviar solicitud a la API de TransAct
				return json_encode($paymentController->postearTransaccionTarjeta(
					$POS->empCod,
					$POS->termCod,
					$POS->empHash,
					$operacion,
					$monedaISO,
					$monto,
					$facturaNro,
					$totalGravado,
					$totalIVA,
					$consumidorFinal,
					$responseCurrentSession->currentSession
				));
		} else return json_encode($responseCurrentSession);
	});

	$app->post('/postearTransaccionDEV', function(Request $request, Response $response) use ($userClass, $userController, $paymentController, $cajaController){ // DEV
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
				$data = $request->getParams();
				$monto = ($data['monto']); // Este es el monto a prorratear
				$consumidorFinal = filter_var($data['consumidorFinal'] ?? false, FILTER_VALIDATE_BOOLEAN); // "true", "1", 1, true → true | "false", "0", 0, false, null → false
				// $facturaNro = floatval($data['factura']);
				$ticketNro = ($data['ticket']);
				
				$caja = null;
				$idPOS = null;
				$POS = null;
				$moneda = null;

				$totalGravado = ($data['gravado']);  // Base imponible total (sin IVA)
				$totalIVA = $monto - $totalGravado;      // IVA total
				$totalFactura = $monto;  // Total de la factura

				$responseGetCaja = $cajaController->getUserCaja($responseCurrentSession->currentSession);
				if($responseGetCaja->result == 2){
					$caja = $responseGetCaja->caja;
					$idPOS = $caja->POS;
				} else {
					return json_encode(['result' => '0', 'message' => 'Erro interno del servidor']);
				}
				$responseGetPOS = $cajaController->getPOSData($idPOS);
				if($responseGetPOS->result == 2){
					$POS = $responseGetPOS->POS;
				} else {
					return json_encode(['result' => '0', 'message' => 'Erro interno del servidor']);
				}

				$monedaISO = $caja->moneda == "UYU" ? '0858' :  '0840';
				$operacion = 'DEV';

				// { // LOGS
					// var_dump("MONTO: $monto");
					// var_dump("MONEDA: $monedaISO");
					// var_dump("CONSUMIDOR FINAL: $consumidorFinal");
					// var_dump("OPERACION: $operacion");
					// // var_dump("CODIGO EMPRESA: $POS->empCod");
					// // var_dump("TERMINAL: $POS->termCod");
					// // var_dump("HASH EMPRESA: $POS->empHash");

					// var_dump("TOTAL GRAVADO: " . round($totalGravado, 2));
					// var_dump("TOTAL IVA: " . round($totalIVA, 2));
					// var_dump("TOTAL FACTURA: " . round($totalFactura, 2));
				// }
				// exit;
				// Enviar solicitud a la API de TransAct
				return json_encode($paymentController->postearTransaccionTarjetaDEV(
					$POS->empCod,
					$POS->termCod,
					$POS->empHash,
					$operacion,
					$monedaISO,
					$totalFactura,
					1,
					$totalGravado,
					$totalIVA,
					$consumidorFinal,
					$ticketNro,
					$responseCurrentSession->currentSession
				));
		} else return json_encode($responseCurrentSession);
	});

	$app->post('/cancelarTransaccion', function(Request $request, Response $response) use ($userClass, $userController, $paymentController, $cajaController){
		$responseCurrentSession = $userController->validateCurrentSession();
		if($responseCurrentSession->result == 2){
				$data = $request->getParams();

				// Validar datos requeridos
				if(!isset($data['tokenNro'])) {
					return json_encode((object)[
						'result' => 0,
						'message' => 'Falta el token de la transacción'
					]);
				}
				
				$tokenNro = $data['tokenNro']; // Este es el monto a prorratear
				return json_encode($paymentController->cancelarTransaccionTarjeta(
					$tokenNro,
					$responseCurrentSession->currentSession
				));
		} else return json_encode($responseCurrentSession);
	});

    $app->post('/consultarTransaccion', function(Request $request, Response $response) use ($userController, $paymentController){
        $responseCurrentSession = $userController->validateCurrentSession();
        if($responseCurrentSession->result == 2){
            $data = $request->getParams();
            
            // Validar datos requeridos
            if(!isset($data['tokenNro'])) {
                return json_encode((object)[
                    'result' => 0,
                    'message' => 'Falta el token de la transacción'
                ]);
            }
            
            $tokenNro = $data['tokenNro'];
            
            // Consultar estado de la transacción
            return json_encode($paymentController->consultarTransaccionTarjeta(
                $tokenNro,
                $responseCurrentSession->currentSession
            ));
        } else return json_encode($responseCurrentSession);
    });

    $app->post('/reserveCFE', function(Request $request, Response $response) use ($userController, $paymentController){
        $responseCurrentSession = $userController->validateCurrentSession();
        if($responseCurrentSession->result == 2){
            $data = $request->getParams();
            $TipoCFE = $data['TipoCFE'];
            return json_encode($paymentController->reserveCFE($TipoCFE, $responseCurrentSession->currentSession));
        } else return json_encode($responseCurrentSession);
    });

}
?>