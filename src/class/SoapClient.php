<?php
/**
 * TransActSoapClient.php
 * Clase para manejar las solicitudes SOAP a los servicios de TransAct
 */

namespace App\Services;

class TransActSoapClient {
    /** @var \SoapClient $client El cliente SOAP nativo de PHP */
    private $client;
    
    /** @var string $lastError Último error ocurrido */
    private $lastError;
    
    /** @var string $wsdlUrl URL del WSDL del servicio */
    private $wsdlUrl;
    
    /** @var array $lastResponse Última respuesta completa recibida */
    private $lastResponse;
    
    /** @var array $soapOptions Opciones para el cliente SOAP */
    private $soapOptions;
    
    /**
     * Constructor
     * 
     * @param string $serviceType Tipo de servicio ('transaccion', 'cierre', 'lectura')
     * @param bool $isProduction Indica si se está en ambiente de producción
     */
    public function __construct($serviceType = 'transaccion', $isProduction = false) {
        // Base URL para ambiente de desarrollo o producción
        // ? 'https://www.transact.com.uy/Concentrador/' 
        $baseUrl = $isProduction 
            ? 'https://www.aasfreddd.com.py/Concentrador/' 
            : 'https://wwwi.transact.com.uy/Concentrador/';
        
        // Seleccionar el servicio correcto
        switch ($serviceType) {
            case 'transaccion':
                $this->wsdlUrl = $baseUrl . 'TarjetasTransaccion_400.svc?wsdl';
                break;
            case 'cierre':
                $this->wsdlUrl = $baseUrl . 'TarjetasCierre_400.svc?wsdl';
                break;
            case 'lectura':
                $this->wsdlUrl = $baseUrl . 'LecturaTarjeta_400.svc?wsdl';
                break;
            default:
                throw new \InvalidArgumentException("Tipo de servicio no válido: $serviceType");
        }
        
        $this->soapOptions = [
            'trace' => true,                 // Habilitar tracking de la petición para debugging
            'exceptions' => true,            // Lanzar excepciones en caso de error
            'connection_timeout' => 30,      // Tiempo máximo de conexión (segundos)
            'cache_wsdl' => WSDL_CACHE_NONE, // No cachear el WSDL durante desarrollo
            'soap_version' => SOAP_1_1,      // Versión SOAP
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])
        ];
        
        $this->connect();
    }
    
    /**
     * Establece la conexión con el servicio SOAP
     */
    private function connect() {
        try {
            $this->client = new \SoapClient($this->wsdlUrl, $this->soapOptions);
            return true;
        } catch (\SoapFault $e) {
            $this->lastError = "Error de conexión SOAP: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Postea una transacción de tarjeta
     * 
     * @param array $transaccion Datos de la transacción
     * @return object Respuesta formateada
     */
    public function postearTransaccion($transaccion) {
        return $this->executeSoapMethod('PostearTransaccion', ['Transaccion' => $transaccion]);
    }
    
    /**
     * Consulta el estado de una transacción
     * 
     * @param string $tokenNro Token de la transacción
     * @return object Respuesta formateada
     */
    public function consultarTransaccion($tokenNro) {
        return $this->executeSoapMethod('ConsultarTransaccion', ['TokenNro' => $tokenNro]);
    }
    
    /**
     * Cancela una transacción pendiente
     * 
     * @param string $tokenNro Token de la transacción
     * @return object Respuesta formateada
     */
    public function cancelarTransaccion($tokenNro) {
        return $this->executeSoapMethod('CancelarTransaccion', ['TokenNro' => $tokenNro]);
    }
    
    /**
     * Postea una transacción en modo batch
     * 
     * @param array $transaccion Datos de la transacción
     * @param int $referencia Número de referencia
     * @param string $descripcion Descripción opcional
     * @param int $minutosExpira Minutos tras los que expira la transacción
     * @return object Respuesta formateada
     */
    public function postearTransaccionBatch($transaccion, $referencia, $descripcion, $minutosExpira) {
        return $this->executeSoapMethod('PostearTransaccionBatch', [
            'Transaccion' => $transaccion,
            'Referencia' => $referencia,
            'Descripcion' => $descripcion,
            'MinutosExpira' => $minutosExpira
        ]);
    }
    
    /**
     * Confirma una transacción en modo batch
     * 
     * @param string $tokenNro Token de la transacción
     * @return object Respuesta formateada
     */
    public function confirmarPosteoTransaccionBatch($tokenNro) {
        return $this->executeSoapMethod('ConfirmarPosteoTransaccionBatch', ['TokenNro' => $tokenNro]);
    }
    
    /**
     * Postea una consulta de datos de tarjeta
     * 
     * @param array $consultaTarjeta Datos de la consulta
     * @return object Respuesta formateada
     */
    public function postearConsultaDatosTarjeta($consultaTarjeta) {
        return $this->executeSoapMethod('PostearConsultaDatosTarjeta', ['ConsultaTarjeta' => $consultaTarjeta]);
    }
    
    /**
     * Consulta datos de una tarjeta
     * 
     * @param string $tokenNro Token de la consulta
     * @return object Respuesta formateada
     */
    public function consultarDatosTarjeta($tokenNro) {
        return $this->executeSoapMethod('ConsultarDatosTarjeta', ['TokenNro' => $tokenNro]);
    }
    
    /**
     * Postea un cierre de lote
     * 
     * @param array $cierre Datos del cierre
     * @return object Respuesta formateada
     */
    public function postearCierre($cierre) {
        return $this->executeSoapMethod('PostearCierre', ['Cierre' => $cierre]);
    }
    
    /**
     * Consulta el estado de un cierre
     * 
     * @param string $tokenNro Token del cierre
     * @return object Respuesta formateada
     */
    public function consultarCierre($tokenNro) {
        return $this->executeSoapMethod('ConsultarCierre', ['TokenNro' => $tokenNro]);
    }
    
    /**
     * Ejecuta un método SOAP y maneja los posibles errores
     * 
     * @param string $method Nombre del método SOAP
     * @param array $params Parámetros del método
     * @return object Respuesta formateada
     */
    private function executeSoapMethod($method, $params) {
        try {
            // Verificar que el cliente está conectado
            if (!$this->client) {
                if (!$this->connect()) {
                    return $this->formatResponse(false, $this->lastError);
                }
            }
            
            // Ejecutar el método SOAP
            $result = $this->client->__soapCall($method, [$params]);
            $this->lastResponse = $result;
            
            // Extraer el resultado específico del método
            $resultProperty = $method . 'Result';
            
            if (isset($result->$resultProperty)) {
                $methodResult = $result->$resultProperty;
                
                // Verificar si hay código de respuesta y mensaje de error
                if (isset($methodResult->Resp_CodigoRespuesta)) {
                    $successCode = 0; // Código 0 indica éxito en TransAct
                    
                    if ($methodResult->Resp_CodigoRespuesta == $successCode) {
                        return $this->formatResponse(true, 'Operación exitosa', $methodResult);
                    } else {
                        $errorMsg = isset($methodResult->Resp_MensajeError) ? 
                                   $methodResult->Resp_MensajeError : 
                                   "Error código: " . $methodResult->Resp_CodigoRespuesta;
                        return $this->formatResponse(false, $errorMsg, $methodResult);
                    }
                }
                
                // Si no tiene el formato estándar, retornar el resultado tal cual
                return $this->formatResponse(true, 'Operación completada', $methodResult);
            }
            
            return $this->formatResponse(false, 'Respuesta SOAP inválida');
        } catch (\SoapFault $e) {
            $this->lastError = "Error SOAP: " . $e->getMessage();
            return $this->formatResponse(false, $this->lastError);
        } catch (\Exception $e) {
            $this->lastError = "Error general: " . $e->getMessage();
            return $this->formatResponse(false, $this->lastError);
        }
    }
    
    /**
     * Formatea la respuesta para mantener consistencia con el resto de la aplicación
     * 
     * @param bool $success Indica si la operación fue exitosa
     * @param string $message Mensaje descriptivo
     * @param mixed $data Datos de la respuesta
     * @return object Respuesta formateada
     */
    private function formatResponse($success, $message, $data = null) {
        return (object) [
            'result' => $success ? 2 : 0, // Usando tu convención: 2=éxito, 0=error
            'message' => $message,
            'objectResult' => $data
        ];
    }
    
    /**
     * Obtiene la última respuesta completa
     * 
     * @return array Última respuesta
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    /**
     * Obtiene la última petición XML enviada (útil para debugging)
     * 
     * @return string XML de la última petición
     */
    public function getLastRequest() {
        return $this->client ? $this->client->__getLastRequest() : null;
    }
    
    /**
     * Obtiene la última respuesta XML recibida (útil para debugging)
     * 
     * @return string XML de la última respuesta
     */
    public function getLastResponseXml() {
        return $this->client ? $this->client->__getLastResponse() : null;
    }
    
    /**
     * Obtiene el último error
     * 
     * @return string Último error
     */
    public function getLastError() {
        return $this->lastError;
    }
}