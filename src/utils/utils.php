<?php
require_once '../src/controllers/ctr_users.php';

class utils{

	public function getTypeToCancelVoucher($typeVoucher, $isCobranza){
		$responseNewType = new \stdClass();

		switch ($typeVoucher) {
			case '101':
				if($isCobranza == 1){
					$responseNewType->type = 101;
					$responseNewType->name = "e-Ticket";
				}else{
					$responseNewType->type = 102;
					$responseNewType->name = "Nota de crédito";
				}
			break;
			case '102':
			$responseNewType->type = 103;
			$responseNewType->name = "Nota de débito";
			break;
			case '103':
			$responseNewType->type = 102;
			$responseNewType->name = "Nota de crédito";
			break;
			case '111':
				if($isCobranza == 1){
					$responseNewType->type = 111;
					$responseNewType->name = "e-Factura";
				}else{
					$responseNewType->type = 112;
					$responseNewType->name = "Nota de crédito";
				}
			break;
			case '112':
			$responseNewType->type = 113;
			$responseNewType->name = "Nota de débito";
			break;
			case '113':
			$responseNewType->type = 112;
			$responseNewType->name = "Nota de crédito";
			break;
		}

		return $responseNewType;
	}

	public function getNameVoucher($typevoucher, $isCobranza){
		$firstPart = substr($typevoucher ?? "", 0, 1);
		$secondPart = substr($typevoucher ?? "", 1, 2);

		$nameVoucher = "";

		switch ($secondPart) {
			case '01': {
				if($isCobranza == 1) $nameVoucher = "e-Ticket Cobranza";
				else $nameVoucher = "e-Ticket";
				break;
			}
			case '02':	$nameVoucher = "N.C. e-Ticket"; break;
			case '03':	$nameVoucher = "N.D. e-Ticket"; break;
			case '11':{
				if($isCobranza == 1) $nameVoucher = "e-Factura Cobranza";
				else $nameVoucher = "e-Factura";
				break;
			}
			case '12':	$nameVoucher = "N.C. e-Factura"; break;
			case '13':	$nameVoucher = "N.D. e-Factura"; break;
			case '21':	$nameVoucher = "e-Factura Exportación";	break;
			case '22':	$nameVoucher = "N.C. e-Factura Exportación"; break;
			case '23':	$nameVoucher = "N.D. e-Factura Exportación"; break;
			case '24':	$nameVoucher = "e-Remito de Exportación"; break;
			case '31':	$nameVoucher = "e-Ticket Venta por Cuenta Ajena"; break;
			case '32':	$nameVoucher = "N.C. e-Ticket Venta por Cuenta Ajena"; break;
			case '33':	$nameVoucher = "N.D. e-Ticket Venta por Cuenta Ajena"; break;
			case '41':	$nameVoucher = "e-Factura Venta por Cuenta Ajena"; break;
			case '42':	$nameVoucher = "N.C. e-Factura Venta por Cuenta Ajena"; break;
			case '43':	$nameVoucher = "N.D. e-Factura Venta por Cuenta Ajena"; break;
			case '51':	$nameVoucher = "e-Boleta de entrada"; break;
			case '52':	$nameVoucher = "N.C. e-Boleta de entrada"; break;
			case '53':	$nameVoucher = "N.D. e-Boleta de entrada"; break;
			case '81':	$nameVoucher = "e-Remito"; break;
			case '82':	$nameVoucher = "e-Resguardo"; break;
		}

		if($firstPart == 2)
			$nameVoucher .= " Contingencia";

		return $nameVoucher;
	}

	public function stringToLower($string){
		try{
			$result = mb_strtolower($string ?? "");
			return $result;
		}catch(Exception $e){
			return $string;
		}
	}

	public function stringToLowerWithFirstCapital($string){
		try{
			$result = ucwords(mb_strtolower($string ?? ""));
			return $result;
		}catch(Exception $e){
			return $string;
		}
	}

	public function formatDocuments($document, $currentSession){
		$userController = new ctr_users();

		$responseConfig = $userController->getVariableConfiguration("FORMATO_DE_RUT", $currentSession);
		if($responseConfig->result == 2){
			if(strlen($document) == 8)
				return substr($document,0,1) . "." . substr($document, 1, 3) . "." . substr($document,4,3) . "-". substr($document, 7, 1);
			else if(strlen($document) == 7){
				return substr($document, 0, 3) . "." . substr($document,3,3) . "-". substr($document, 6, 1);
			}if(strlen($document) == 12){
				if($responseConfig->configValue == 2631)
					return substr($document,0, 2) . " " . substr($document, 2, 6) . " " . substr($document, 8, 3) . " " . substr($document, 11, 1);
				else if($responseConfig->configValue == 3333)
					return substr($document,0, 3) . " " . substr($document, 3, 3) . " " . substr($document, 6, 3) . " " . substr($document, 9, 3);
			}
		}
		return $document;
	}

	public function convertObjectClientToReceiver($client){
		$reponse = array(
		  "documento"=> $client->docReceptor,
		  "nombre"=> $client->nombreReceptor,
		  "direccion"=> $client->direccion,
		  "ciudad"=> $client->localidad,
		  "departamento"=> $client->departamento,
		  "pais"=>"Uruguay"
		);

		return $reponse;
	}

	public function combinarArraysPorTipo($resultCaes, $companyCaes) {
		$utilsClass = new utils();
		$resultado = [];
		$resultadoTemporal = [];
		
		// Recorrer el primer array
		foreach ($resultCaes as $cae1) {
			$cfeType = $cae1->cfeType;
			$encontrado = false;
			
			// Buscar coincidencia en el segundo array
			foreach ($companyCaes as $cae2) {
				if ($cae1->cfeType == $cae2->tipoCFE) {
					// Combinar los atributos
					$itemCombinado = (object) [
						'cfeType' => $cae1->cfeType,
						'serie' => $cae1->serie,
						'from' => $cae1->from,
						'to' => $cae1->to,
						'expirationDate' => $cae1->expirationDate,
						'emissionDate' => $cae1->emissionDate,
						'nextNumber' => $cae1->nextNumber,
						'isUsable' => $cae1->isUsable,
						'type' => $cae1->type,
						'typeText' => $utilsClass->getCaeTypeText($cae1->type),
						// Atributos del segundo array con valores predeterminados
						'vencimiento' => $cae2->vencimiento ?? '---',
						'disponibles' => $cae2->disponibles ?? '---',
						'total' => $cae2->total ?? '---'
					];
					
					$resultadoTemporal[$cfeType] = $itemCombinado;
					$encontrado = true;
					break;
				}
			}
			
			// Si no se encuentra coincidencia en el segundo array
			if (!$encontrado) {
				$itemCombinado = (object) [
					'cfeType' => $cae1->cfeType,
					'serie' => $cae1->serie,
					'from' => $cae1->from,
					'to' => $cae1->to,
					'expirationDate' => $cae1->expirationDate,
					'emissionDate' => $cae1->emissionDate,
					'nextNumber' => $cae1->nextNumber,
					'isUsable' => $cae1->isUsable,
					'type' => $cae1->type,
					'typeText' => $utilsClass->getCaeTypeText($cae1->type),
					// Valores predeterminados para atributos no encontrados
					'vencimiento' => '---',
					'disponibles' => '---',
					'total' => '---'
				];
				
				$resultadoTemporal[$cfeType] = $itemCombinado;
			}
		}
		
		// Convertir el array asociativo a un array indexado
		$resultado = array_values($resultadoTemporal);
		
		return $resultado;
	}

	public function getCaeTypeText($tipo){
		switch ($tipo) {
			case 0: return "Normal";
			case 1: return "Exonerado";
			case 2: return "Literal E";
			case 3: return "Monotributo";
			case 4: return "Monotributo MIDES";
			default: return "";
		}
	}
}