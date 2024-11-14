<?php

require_once 'rest/ctr_rest.php';
require_once 'ctr_vouchers_emitted.php';

require_once '../src/class/defaultclass/users.php';

class ctr_accounting{

	private $objectAccountingSettings = null;

	public function getAccountingSettings(){
		$usersClass = new users();
		if (is_null($this->objectAccountingSettings)){
			$responseSettings = $usersClass->getAccountingConfigurationByRut($_SESSION['systemSession']->rut);
			if ( $responseSettings->result == 2 )
				$this->objectAccountingSettings = json_decode($responseSettings->objectResult->json);
			else return $responseSettings;
		}

		return $this->objectAccountingSettings;
	}

	//NECESITA EL LAST ID PORQUE EN EL CONTROLADOR DE LOS COMPROBANTES EMITIDOS SE LLAMA RECURSIVAMENTE
	//WORKING
	public function countAllVouchersEmittedRest( $rut, $pageSize, $lastid, $dateFrom, $dateTo, $currentSession ){
		$emittedVoucherController = new ctr_vouchers_emitted();
		$userController = new ctr_users();

		$result = $emittedVoucherController->countAllVouchersEmittedRest( $rut, $pageSize, $lastid, $dateFrom, $dateTo, $currentSession->tokenRest );
		return $result;

		// $responseCurrentSession = $userController->validateCurrentSession("ACCOUNTING");
		// if($responseCurrentSession->result == 2){
		// }else return $responseCurrentSession;
	}


	//FUNCION QUE BUSCA TODOS LOS DATOS DE LOS COMPROBANTES Y LOS ENVÍA AL TXT PARA EXPORTAR
	//WORKING
	public function exportSaleData( $rut, $pageSize, $dateFrom, $dateTo, $currentSession ){
		$userController = new ctr_users();
		$arrayLines = array();
		$response = new \stdClass();
		//validar session del usuario
		// $responseCurrentSession = $userController->validateCurrentSession("ACCOUNTING");
		// if($responseCurrentSession->result == 2){

			//borrando los archivos exportados antermente
		$arrayErrorsUnlink = array();
		foreach (scandir(PATH_CONTAB) as $item) {
			if ( $item != "." && $item != ".." ){
				$resultUnlink = unlink(PATH_CONTAB.$item);

				if ( !$resultUnlink )
					array_push($arrayErrorsUnlink, "archivo ".$item." no se pudo eliminar");
			}
		}

		if ( count($arrayErrorsUnlink)>0 ){
			$response->result = 1;
			$response->message = $arrayErrorsUnlink;
			return $response;
		}

		$pathFile = PATH_CONTAB."IM".substr($dateFrom,2, 2).substr($dateFrom,4, 2)."01.txt";

		if ( file_exists($pathFile) ){
			if ( !unlink($pathFile) ){
				$response->result = 1;
				$response->message = "No se puedo borrar el contenido previo del archivo";
				return $response;
			}
		}

		if ( strlen($dateFrom) != 14 || strlen($dateTo) != 14 ){
			$response->result = 1;
			$response->message = "Las fechas ingresadas ".$dateFrom." - ".$dateTo. ".";
			return $response;
		}

		$accountingController = new ctr_accounting();
		$restController = new ctr_rest();
		$arrayErrors = array();

		$setting = $accountingController->getAccountingSettings();

		$clients = $accountingController->getClientsAccountingNumber(); //lista de todos los ruts de clientes y su respectivo numero contable

		if ( $clients->result == 1 ){
			$clients = null;
		}else if ( $clients->result != 1 && $clients->result != 2){
			$response->result = 1;
			$response->message = "Ocurrio un error al procesar clientes y sus números contables.";
			return $response;//$clients;
		}

		//primera linea del archivo txt
		$line = "dia,debe,haber,rut,numero,concepto,moneda,total,codigoiva,iva,cotizacion,libro";

		$responsePutContent = file_put_contents( $pathFile, $line."\n", FILE_APPEND );

		if ( !$responsePutContent ){
			array_push($arrayErrors, " linea: ".$line );
		}

		//datos a agregar en el txt
		$diaFile = null;
		$debeFile = null;
		$haberFile = null;
		$rutFile = null;
		$numeroFile = null;
		$conceptoFile = null;
		$monedaFile = null;
		$totalFile = null;
		$codigoivaFile = null;
		$ivaFile = null;
		$cotizacionFile = null;
		$libroFile = null;
		/////////////

		$getEmitted = $restController->listarEmitidos($rut, $pageSize, null, $dateFrom, null, $dateTo, $currentSession->tokenRest);

		if ( $getEmitted->result == 2 ){

			foreach ($getEmitted->listEmitidos as $voucher) {

				if ( $voucher->tipoCFE == 111 || $voucher->tipoCFE == 101 ){

					$generalData = $this->getBasicDataFromVoucher( $voucher ); //pedir datos generales

					$diaFile = $generalData->dayFile; //////////////////////////
					$rutFile = $generalData->rutFile; //////////////////////////
					///// segun el metodo de pago traer el rubro del haber
					$haberFile = $generalData->hFile; //////////////////////////

					if ( $generalData->hFile == 1 ){
						$haberFile = $setting->VENTAS_CONTADO;
						if ($generalData->moneyFile == "UYU"){
							$debeFile = $setting->DEUDORES_VARIOS_MN;
						}else
							$debeFile = $setting->DEUDORES_VARIOS_ME;
					}else if ( $generalData->hFile == 2 ){
						$haberFile = $setting->VENTAS_CREDITO;
						if ( isset($clients) ){
							$index = array_search($rutFile, array_column($clients->listResult, 'docReceptor'));
							if ($index !== false){
								$debeFile = $clients->listResult[$index]["nroContable"];
							}
							else{
								if ($generalData->moneyFile == "UYU"){
									$debeFile = $setting->CAJA_MONEDA_NACIONAL;
								}else
									$debeFile = $setting->CAJA_MONEDA_EXTRANJERA;
							}
						}
						else{
							if ($generalData->moneyFile == "UYU"){
								$debeFile = $setting->CAJA_MONEDA_NACIONAL;
							}else
								$debeFile = $setting->CAJA_MONEDA_EXTRANJERA;
						}
					}

					if ($generalData->moneyFile == "UYU")
						$monedaFile = $setting->MONEDA_NACIONAL;
					else $monedaFile = $setting->MONEDA_EXTRANJERA; //////////////////////////

					$numeroFile = $voucher->numeroCFE; //////////////////////////

					$libroFile = $setting->LIBRO;

					$detailsData = $this->getDetailsFromVoucher( $rut, $voucher->tipoCFE, $voucher->serieCFE, $voucher->numeroCFE, $currentSession ); //pedir datos especificos del comprobante

					$conceptoFile = $detailsData->descriptionFile; //////////////////////////

					$quoteFile = $detailsData->quoteFile; //////////////////////////

					foreach ($detailsData->itemsDetailFile as $value) {
						if ( !isset($value["iva"]) )
							$value["iva"] = "0";

						$line = $diaFile.",".$debeFile.",".$haberFile.",".$rutFile.",".$numeroFile.",".$conceptoFile.",".$monedaFile.",".$value["precio"].",".$value["indFact"].",".$value["iva"].",".$quoteFile.",".$libroFile;
						array_push($arrayLines, $line);
					}
				}
			}
		}

		//PARA QUE EL TXT QUEDE ORDENADO, TODAS LAS LINEAS SE GUARDAN EN UN ARRAY, ESE ARRAY SE ORDENA Y LUEGO SE RECORRE PARA GUARDAR TODAS LAS LINEAS EN ORDEN DE FECHA ASC
		asort($arrayLines, SORT_REGULAR|SORT_STRING);

		foreach ($arrayLines as $lineValue) {

			$responsePutContent = file_put_contents( $pathFile, $lineValue."\n", FILE_APPEND );
			if ( !$responsePutContent ){
				array_push($arrayErrors, "linea: ".$line );
			}
		}

		if ( count($arrayErrors)>0 ){

			$response->result = 1;
			array_unshift($arrayErrors, "Ocurrieron los siguientes errores al exportar datos de comprobantes:");
			$response->message = $arrayErrors;
			return $response;
		}else{

			$response->result = 2;
			$response->name = "IM".substr($dateFrom,2, 2).substr($dateFrom,4, 2)."01";
			return $response;
		}
		// }else return $responseCurrentSession;
	}

	//recibe un comprobante y devuelve un objeto con menos datos y todos los datos con el formato que se requiere en el archivo que se exporta para memory
	public function getBasicDataFromVoucher( $voucher ){
		$validateClass = new validate();
		$response = new \stdClass();
		$dayFile = substr($voucher->fecha,6, 2);
		$hFile = $voucher->formaPago; //devuelvo el mismo dato que tiene el comprobante puede ser 1 contado o 2 credito
		$rutFile = "";
		//verificar que es un rut, porque puede ser una cedula o ""
		if ( strlen($voucher->receptor->documento) == 12 ){
			$responseValidRut = $validateClass->validateRUT($voucher->receptor->documento);
			if ($responseValidRut){
				$rutFile = $voucher->receptor->documento;
			}
		}

		$moneyFile = $voucher->tipoMoneda; //devuelvo el mismo dato que tiene el comprobante, puede ser UYU USD
		$totalFile = $voucher->total;

		//////////response
		$response->dayFile = $dayFile;
		$response->hFile = $hFile;
		$response->rutFile = $rutFile;
		$response->moneyFile = $moneyFile;
		$response->totalFile = $totalFile;

		return $response;
	}
	//WORKING
	public function getDetailsFromVoucher( $rut, $tipoCFE, $serieCFE, $numeroCFE, $currentSession ){
		$response = new \stdClass();
		$restController = new ctr_rest();
		$accountingController = new ctr_accounting();
		$arrayItemsDetail = null;

		$setting = $accountingController->getAccountingSettings();

		$restResponse = $restController->consultarCFE($rut, null, $tipoCFE, $serieCFE, $numeroCFE, "application/json", $currentSession->tokenRest );
		if ( $restResponse->result == 2 ){
			$repImpresa = json_decode($restResponse->cfe->representacionImpresa);

			$descriptionFile = ""; //ejemplo A 52866 - MERCEDES MENDOZA GUERRERO
			$name = strtoupper($repImpresa->receptor->nombre);
			$descriptionFile = $serieCFE." ".$numeroCFE." - ".$name;

			$arrayItemsDetail = $this->processAllItemsVoucher($repImpresa->detalles);
			foreach ($arrayItemsDetail as $key => $item) {
				foreach ($setting->INDICADORES_FACTURACION as $indFact => $value) {
					if ( $item["indFact"] == $indFact ){
						$arrayItemsDetail[$key]["indFact"] = $value;
					}
				}

				if ( $item["indFact"] == 1 ){
					$arrayItemsDetail[$key]["iva"] = "0";
				}
				else if ( $item["indFact"] == 2 ){
					if ( isset($repImpresa->totales->ivaMinimo) ){
						$arrayItemsDetail[$key]["iva"] = $repImpresa->totales->ivaMinimo;
					}else $arrayItemsDetail[$key]["iva"] = "";
				}
				else if ( $item["indFact"] == 3 ){
					if ( isset($repImpresa->totales->ivaBasico) ){
						$arrayItemsDetail[$key]["iva"] = $repImpresa->totales->ivaBasico;
					}else $arrayItemsDetail[$key]["iva"] = "";
				}
				else{
					$arrayItemsDetail[$key]["iva"] = "";
				}
			}

			$quoteFile = "0";
			if ( $repImpresa->tipoMoneda != "UYU" ){
				$quoteFile = $repImpresa->tipoCambio;
			}

			$response->quoteFile = $quoteFile;
			$response->itemsDetailFile = $arrayItemsDetail;
			$response->descriptionFile = $descriptionFile;
			return $response;
		} else return $restResponse;
	}

	//tiene que llegar un array con los items del detalle del comprobante
	//comprobante con representacion impresa en formato json. cfe->representacionImpresa->detalles.
	public function processAllItemsVoucher( $arrayItems ){
		$responseArray = array();
		foreach( $arrayItems as $key => $value){
		   	if ( array_search($value->indFact, array_column($responseArray, 'indFact')) === false ){
        		$responseArray [] = array(
		            "indFact" => $value->indFact,
		            "precio" => ($value->precio * $value->cantidad)
	        	);
        	}
        	else{
        		for ($i=0; $i < count($responseArray) ; $i++) {
        			if( $responseArray[$i]['indFact'] == $value->indFact ){
        				$responseArray[$i]['precio'] += $value->precio;
        			}
        		}
        	}
        }
		return $responseArray;
	}

	public function addLineToAccountingFile($line, $pathFile = "" ){
		$restController = new ctr_rest();
		$response = new \stdClass();
		$response->result = 0;
		$response->message = "No se pudo abrir el archivo";

		$typeLine = gettype($line);
		if ( $typeLine == "NULL" ){
			$response->result = 1;
			$response->result = "La linea a registrar debe de ser distinta de NULL.";
			return $response;
		}else if ( $typeLine != "string" ){
			$response->result = 1;
			$response->result = "La linea a registrar un texto.";
			return $response;
		}

		$logFile = fopen($pathFile, 'a') or false;
		if ( $logFile ){
			fwrite($logFile, $line."\n");
			fclose($logFile);

			$response->result = 2;
			$response->message = "ok";
			return $response;
		}else return $response;
	}

	public function getClientsAccountingNumber(){
		$clientClass = new clients();
		return $clientClass->getClientsAccountingNumber();
	}
}

?>