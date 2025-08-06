<?php

require_once '../src/class/SoapClient.php';


class ctr_payment{
    /**
     * Postea una transacción de tarjeta a TransAct
     */
    public function postearTransaccionTarjeta($empCod, $termCod, $empHash, $operacion, $monedaISO, $monto, $facturaNro, $montoGravado, $montoIVA, $consumidorFinal, $session) {
        try {
            // Crear cliente SOAP personalizado
            $client = new SoapClient("https://wwwi.transact.com.uy/Concentrador/TarjetasTransaccion_400.svc?wsdl");
            
            // Crear objeto de transacción según la estructura WSDL
            $transaccion = [
                'Comportamiento' => [
                    'ModificarCuotas' => false,
                    'ModificarDecretoLey' => false,
                    'ModificarFactura' => false,
                    'ModificarMoneda' => false,
                    'ModificarMontos' => false,
                    'ModificarPlan' => false,
                    'ModificarTarjeta' => false,
                    'ModificarTipoCuenta' => false,
                    ],
                'EmpHASH' => $empHash,
                'EmpCod' => $empCod,
                'TermCod' => $termCod,
                'Operacion' => $operacion,
                'MonedaISO' => $monedaISO,
                'Monto' => floatval($monto * 100),
                'FacturaNro' => $facturaNro,
                'FacturaMonto' => floatval($monto * 100),
                'FacturaMontoGravado' => floatval($montoGravado * 100),
                'FacturaMontoIVA' => floatval($montoIVA * 100),
                'FacturaConsumidorFinal' => $consumidorFinal,
                'Configuracion' => [
                    'GUIModo' => 0,
                    'ImpresionModo' => 1,
                    'ImpresionTipo' => 9
                    // 'ModoEmulacion' => false, // Asegúrate que este valor está explícitamente en false
                ],
                'TicketOriginal' => 80
            ];
            
            // Llamar al método PostearTransaccion
            $result = $client->PostearTransaccion(['Transaccion' => $transaccion]);
            
            // Formatea la respuesta manualmente
            $formattedResponse = null;
            if ($result->PostearTransaccionResult->Resp_CodigoRespuesta == 0) {
                $formattedResponse = (object)[
                    'result' => 2,
                    'message' => 'Transacción posteada correctamente',
                    'objectResult' => $result->PostearTransaccionResult
                ];
            } else {
                $formattedResponse = (object)[
                    'result' => 0,
                    'message' => 'Error al postear transacción: ' . $result->PostearTransaccionResult->Resp_MensajeError,
                    'objectResult' => $result->PostearTransaccionResult
                ];
            }
            
            return $formattedResponse;
        } catch (Exception $e) {
            // Registrar el error
            $this->logError('postearTransaccionTarjeta', $e->getMessage());
            
            return (object)[
                'result' => 0,
                'message' => 'Error en la comunicación con el servicio de pagos: ' . $e->getMessage()
            ];
        }
    }

    public function postearTransaccionTarjetaDEV($empCod, $termCod, $empHash, $operacion, $monedaISO, $monto, $facturaNro, $montoGravado, $montoIVA, $consumidorFinal, $ticket, $session) {
        try {
            // Crear cliente SOAP personalizado
            $client = new SoapClient("https://wwwi.transact.com.uy/Concentrador/TarjetasTransaccion_400.svc?wsdl");
            
            // Crear objeto de transacción según la estructura WSDL
            $transaccion = [
                'Comportamiento' => [
                    'ModificarCuotas' => false,
                    'ModificarDecretoLey' => false,
                    'ModificarFactura' => false,
                    'ModificarMoneda' => false,
                    'ModificarMontos' => false,
                    'ModificarPlan' => false,
                    'ModificarTarjeta' => false,
                    'ModificarTipoCuenta' => false,
                    ],
                'EmpHASH' => $empHash,
                'EmpCod' => $empCod,
                'TermCod' => $termCod,
                'Operacion' => $operacion,
                'MonedaISO' => $monedaISO,
                'Monto' => floatval($monto * 100),
                'FacturaNro' => $facturaNro,
                'FacturaMonto' => floatval($monto * 100),
                'FacturaMontoGravado' => floatval($montoGravado * 100),
                'FacturaMontoIVA' => floatval($montoIVA * 100),
                'FacturaConsumidorFinal' => $consumidorFinal,
                'Configuracion' => [
                    'GUIModo' => 0,
                    'ImpresionModo' => 1,
                    'ImpresionTipo' => 9
                    // 'ModoEmulacion' => false, // Asegúrate que este valor está explícitamente en false
                ],
                'TicketOriginal' => $ticket
            ];
            
            // Llamar al método PostearTransaccion
            $result = $client->PostearTransaccion(['Transaccion' => $transaccion]);
            
            // Formatea la respuesta manualmente
            $formattedResponse = null;
            if ($result->PostearTransaccionResult->Resp_CodigoRespuesta == 0) {
                $formattedResponse = (object)[
                    'result' => 2,
                    'message' => 'Transacción posteada correctamente',
                    'objectResult' => $result->PostearTransaccionResult
                ];
            } else {
                $formattedResponse = (object)[
                    'result' => 0,
                    'message' => 'Error al postear transacción: ' . $result->PostearTransaccionResult->Resp_MensajeError,
                    'objectResult' => $result->PostearTransaccionResult
                ];
            }
            
            return $formattedResponse;
        } catch (Exception $e) {
            // Registrar el error
            $this->logError('postearTransaccionTarjeta', $e->getMessage());
            
            return (object)[
                'result' => 0,
                'message' => 'Error en la comunicación con el servicio de pagos: ' . $e->getMessage()
            ];
        }
    }

    public function cancelarTransaccionTarjeta($tokenNro, $session) {
        try {
            // Crear cliente SOAP
            $client = new SoapClient("https://wwwi.transact.com.uy/Concentrador/TarjetasTransaccion_400.svc?wsdl");
            
            // Consultar la transacción
            $result = $client->cancelarTransaccion(['TokenNro' => $tokenNro]);
            
            // Para depuración - guardar la respuesta completa
            $this->logDebug('cancelarTransaccionTarjeta', json_encode($result));
            
            // Formatear la respuesta manualmente
            if (isset($result->ConsultarTransaccionResult)) {
                $transaccionResult = $result->ConsultarTransaccionResult;
                
                if (isset($transaccionResult->Resp_CodigoRespuesta) && $transaccionResult->Resp_CodigoRespuesta == 0) { // Resp_MensajeError
                    
                    // Crear respuesta formateada con éxito
                    $formattedResponse = (object)[
                        'result' => 2,
                        'message' => 'Consulta exitosa',
                        'objectResult' => null
                    ];
                    
                } else {
                    // Crear respuesta formateada con error
                    $errorMsg = isset($transaccionResult->Resp_MensajeError) ? 
                        $transaccionResult->Resp_MensajeError : 
                        "Error código: " . (isset($transaccionResult->Resp_CodigoRespuesta) ? $transaccionResult->Resp_CodigoRespuesta : "desconocido");
                        
                    $formattedResponse = (object)[
                        'result' => 0,
                        'message' => $errorMsg,
                        'objectResult' => null
                    ];
                }
            } else {
                // Respuesta SOAP inválida
                $formattedResponse = (object)[
                    'result' => 0,
                    'message' => 'Respuesta SOAP inválida',
                    'objectResult' => null
                ];
            }
            
            return $formattedResponse;
        } catch (Exception $e) {
            // Registrar el error
            $this->logError('consultarTransaccionTarjeta', $e->getMessage());
            
            return (object)[
                'result' => 0,
                'message' => 'Error en la comunicación con el servicio de pagos: ' . $e->getMessage()
            ];
        }
    }

    public function consultarTransaccionTarjeta($tokenNro, $session) {
        try {
            // Crear cliente SOAP
            $client = new SoapClient("https://wwwi.transact.com.uy/Concentrador/TarjetasTransaccion_400.svc?wsdl");
            
            // Consultar la transacción
            $result = $client->ConsultarTransaccion(['TokenNro' => $tokenNro]);
            
            // Para depuración - guardar la respuesta completa
            $this->logDebug('consultarTransaccionTarjeta', json_encode($result));
            
            // Formatear la respuesta manualmente
            if (isset($result->ConsultarTransaccionResult)) {
                $transaccionResult = $result->ConsultarTransaccionResult;
                
                if (isset($transaccionResult->Resp_CodigoRespuesta) && $transaccionResult->Resp_CodigoRespuesta == 0) {
                    // Procesar el voucher antes de incluirlo en la respuesta
                    $voucherArray = [];
                    if (isset($transaccionResult->Voucher) && property_exists($transaccionResult->Voucher, 'string')) {
                        // Si el voucher es un objeto con propiedad 'string'
                        if (is_array($transaccionResult->Voucher->string)) {
                            $voucherArray = $transaccionResult->Voucher->string;
                        } else {
                            $voucherArray = [$transaccionResult->Voucher->string];
                        }
                    }
                    
                    // Crear una copia del objeto de resultado para evitar problemas
                    // de serialización con objetos SOAP
                    $objectResult = [
                        'TransaccionId' => isset($transaccionResult->TransaccionId) ? $transaccionResult->TransaccionId : null,
                        'Ticket' => isset($transaccionResult->Ticket) ? $transaccionResult->Ticket : null,
                        'Lote' => isset($transaccionResult->Lote) ? $transaccionResult->Lote : null,
                        'NroAutorizacion' => isset($transaccionResult->NroAutorizacion) ? $transaccionResult->NroAutorizacion : null,
                        'MsgRespuesta' => isset($transaccionResult->MsgRespuesta) ? $transaccionResult->MsgRespuesta : null,
                        'Aprobada' => isset($transaccionResult->Aprobada) ? $transaccionResult->Aprobada : false,
                        'Resp_TransaccionFinalizada' => isset($transaccionResult->Resp_TransaccionFinalizada) ? $transaccionResult->Resp_TransaccionFinalizada : false,
                        'Voucher' => $voucherArray
                    ];
                    
                    // Si hay datos de transacción, incluirlos
                    if (isset($transaccionResult->DatosTransaccion)) {
                        $objectResult['DatosTransaccion'] = json_decode(json_encode($transaccionResult->DatosTransaccion), true);
                    }
                    
                    // Crear respuesta formateada con éxito
                    $formattedResponse = (object)[
                        'result' => 2,
                        'message' => 'Consulta exitosa',
                        'objectResult' => $objectResult
                    ];
                    
                    // Verificar si la transacción está finalizada
                    if (isset($transaccionResult->Resp_TransaccionFinalizada)) {
                        if ($transaccionResult->Resp_TransaccionFinalizada) {
                            // La transacción ha finalizado (aprobada o rechazada)
                            $aprobada = $transaccionResult->Aprobada;
                            
                            // Ajustar mensaje según el resultado
                            $formattedResponse->message = $aprobada ? 
                                'Transacción aprobada' : 
                                'Transacción rechazada: ' . $transaccionResult->MsgRespuesta;
                        } else {
                            // La transacción aún está en proceso
                            $formattedResponse->result = 1; // Indicamos "pendiente"
                            $formattedResponse->message = 'Transacción en proceso, debe consultar nuevamente';
                        }
                    }
                } else {
                    // Crear respuesta formateada con error
                    $errorMsg = isset($transaccionResult->Resp_MensajeError) ? 
                        $transaccionResult->Resp_MensajeError : 
                        "Error código: " . (isset($transaccionResult->Resp_CodigoRespuesta) ? $transaccionResult->Resp_CodigoRespuesta : "desconocido");
                        
                    $formattedResponse = (object)[
                        'result' => 0,
                        'message' => $errorMsg,
                        'objectResult' => null
                    ];
                }
            } else {
                // Respuesta SOAP inválida
                $formattedResponse = (object)[
                    'result' => 0,
                    'message' => 'Respuesta SOAP inválida',
                    'objectResult' => null
                ];
            }
            
            return $formattedResponse;
        } catch (Exception $e) {
            // Registrar el error
            $this->logError('consultarTransaccionTarjeta', $e->getMessage());
            
            return (object)[
                'result' => 0,
                'message' => 'Error en la comunicación con el servicio de pagos: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para registrar información de depuración
    private function logDebug($metodo, $mensaje) {
        // Implementa la lógica para guardar logs de depuración
        // Ejemplo: escribir en un archivo específico
        // $logFile = '../logs/transact-debug.log';
        error_log(date('Y-m-d H:i:s') . " [$metodo] $mensaje\n");
        // @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Guarda el token de la transacción en la base de datos
     */
    private function guardarTokenTransaccion($tokenNro, $facturaNro, $monto, $POSType, $idUser) {
        // Implementación para guardar el token en la base de datos
        // Por ejemplo, podrías usar:
        
        // $db = new Database();
        // $db->query("INSERT INTO transacciones_tarjeta (token, factura_nro, monto, pos_type, id_usuario, fecha_creacion, estado) 
        //             VALUES (?, ?, ?, ?, ?, NOW(), 'PENDIENTE')", 
        //             [$tokenNro, $facturaNro, $monto, $POSType, $idUsuario]);
    }
    
    /**
     * Registra errores en el log
     */
    private function logError($metodo, $mensaje) {
        // Implementación para registrar errores
        // Por ejemplo, podrías usar:
        
        error_log("Error en ctr_payment::{$metodo}: {$mensaje}");
    }

    public function reserveCFE($TipoCFE, $currentSession){
		$response = new \stdClass();
        $restController = new ctr_rest();
        $object = array(
            'TipoCFE' => $TipoCFE
        );
        return $restController->reserveCFE($currentSession->rut, $object, $currentSession->tokenRest);
    }   

}
