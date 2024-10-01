<?php

class sendPetition{

	public function getNameClient($document){
		$urlMethod = 'https://www.gargano.uy/wsgestcom/ws_ci.php?pwd&ci=' . $document;
		$curlPetition = curl_init();
		curl_setopt($curlPetition, CURLOPT_URL, $urlMethod);
		curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, false);
		$responseCurl = curl_exec($curlPetition);
		curl_close($curlPetition);
		return $responseCurl;
	}

	public function getContactDetail($contactType, $value){
		return array(
			"contactType" => $contactType,
			"value" => $value
		);
	}

	public function getReferenciasArray($tipoCFE, $serieCFE, $numeroCFE, $indReferencia, $razon){
		if(is_null($indReferencia)){
			return array(
				"tipo" => $tipoCFE,
				"serie" => $serieCFE,
				"numero" => $numeroCFE,
			);
		}else{
			return array(
				"indRef" => $indReferencia,
				"razon" => $razon
			);
		}
	}

	public function getReceptorArray($documento, $nombre, $direccion, $ciudad, $departamento, $pais){
		$arrayReceptor = array(
			"documento" => $documento,
			"nombre" => $nombre,
		);

		$arrayReceptor["direccion"] = $direccion;
		$arrayReceptor["ciudad"] = $ciudad;
		$arrayReceptor["departamento"] = $departamento;
		$arrayReceptor["pais"] = $pais;

		return $arrayReceptor;
	}

	public function getDetallesArray($indFact, $nomItem, $codItem, $descItem, $cantidad, $uniMedida, $precio){
		$arrayDetail = array(
			"indFact" => $indFact,
			"nomItem" => $nomItem,
			"cantidad" => $cantidad,
			"precio" => $precio
		);
		if(!is_null($codItem))
			$arrayDetail["codItem"] = $codItem;
		if(!is_null($descItem))
			$arrayDetail["descripcion"] = $descItem;
		if(!is_null($uniMedida))
			$arrayDetail["uniMedida"] = $uniMedida;

		return $arrayDetail;
	}

	//llega un dato que puede ser nombre, rut o ci y te devuelve lo que encuentra en ormen
	public function buscarCliente($rut, $textToSearch, $token){
		return sendPetition::prepareAndSendCurl("GET", "customers/search/" . $rut . "?Text=" . $textToSearch, $token, null);
	}

	//llega un dato que puede ser nombre, rut o ci y te devuelve lo que encuentra en DGI
	public function buscarClienteDGI($textToSearch, $token){
		return sendPetition::prepareAndSendCurl("GET", "company/search?Text=" . $textToSearch, $token, null);
	}

	public function status($rut, $token){
		return sendPetition::prepareAndSendCurl("GET", "status?rut=" . $rut, $token, null,);
	}


	public function exportacion($rut, $typeCall, $data, $token){
		return sendPetition::prepareAndSendCurl("GET", "company/" . $rut . "/cfe/" . $typeCall .  "/export" . $data, $token, null);
	}

	public function obtenerCotizacion($dateFrom, $dateTo, $typeCoin){
		return sendPetition::prepareAndSendCurl("GET", 'currency?Currency=' . $typeCoin . '&From=' . $dateFrom . '&To=' . $dateTo, null, null);
	}

	public function consultarRut($rut, $rutBusiness, $token){
		return sendPetition::prepareAndSendCurl("GET", 'company?SenderRUT=' . $rut . '&RUTConsulta=' . $rutBusiness, $token, null);
	}
	
	public function ping(){
		return sendPetition::prepareAndSendCurl("GET", "ping", null, null);
	}

	public function login($rut, $user, $password){
		$data = array(
			"credenciales" => array(
				"user" => $user,
				"clave" => $password
			)
		);

		return sendPetition::prepareAndSendCurl("POST", "login", null, $data);
	}

	public function nuevoCFE($rut, $data, $token){
		return sendPetition::prepareAndSendCurl("POST", "company/" . $rut . "/cfe", $token, $data);
	}

	public function consultarCFE($rut, $rutEmisor, $tipoCFE, $serieCFE, $numeroCFE, $repImpresa, $formatImpresion, $token){
		if(is_null($rutEmisor))
			$rutEmisor = "";
		else
			$rutEmisor = '&RUTEmisor=' . $rutEmisor;

		$url = "company/" . $rut . "/cfe?tipocfe=". $tipoCFE . "&seriecfe=" . $serieCFE . "&numerocfe=" . $numeroCFE . "&conrepresentacionimpresa=" . $repImpresa . "&formatorepresentacionimpresa=" . $formatImpresion . $rutEmisor;
		return sendPetition::prepareAndSendCurl("GET", $url, $token, null);
	}

	public function consultarCaes($rut, $token)	{
		return sendPetition::prepareAndSendCurl("GET", "caes/" . $rut, $token, null);
	}

	public function consultarCertificadoDigital($rut, $token){
		return sendPetition::prepareAndSendCurl("GET", 'certificate/' . $rut, $token, null);
	}

	public function nuevoCliente($rut, $data, $token){

		return sendPetition::prepareAndSendCurl("POST", 'customers/' . $rut, $token, $data);
	}

	public function modificarCliente($rut, $document, $data, $token){
		return sendPetition::prepareAndSendCurl("PUT", 'customers/' . $rut . '/' . $document, $token, $data);
	}

	public function listarClientes($rut, $token){
		return sendPetition::prepareAndSendCurl("GET", 'customers/'. $rut, $token, null);
	}

	public function consultarCliente($rut, $documento, $token){
		return sendPetition::prepareAndSendCurl("GET", "customers/" . $rut . "/" . $documento, $token, null);
	}

	public function listarRecibidos($rut, $pageSize, $lastId, $dateFrom, $dateTo, $token){
		if(!is_null($dateFrom) && !is_null($dateTo))
			$dateFrom = "&from=" . $dateFrom . "&to=" . $dateTo;
		else $dateFrom = "";

		if(!is_null($lastId))
			$lastId = "&LastId=" . $lastId;
		else $lastId = "";
		return sendPetition::prepareAndSendCurl("GET", "company/" . $rut . "/cfe/recibidos?PageSize=" . $pageSize . $lastId . $dateFrom, $token, null);
	}

	public function listarEmitidos($rut, $pageSize, $lastId, $dateFrom, $dateTo, $branchCompany, $token){
		if(!is_null($dateFrom) && !is_null($dateTo))
			$dateFrom = "&from=" . $dateFrom . "&to=" . $dateTo;
		else $dateFrom = "";

		if(!is_null($lastId))
			$lastId = "&LastId=" . $lastId;
		else $lastId = "";

		if(!is_null($branchCompany))
			$branchCompany = "&sucursal=" . $lastId;
		else $branchCompany = "";

		return sendPetition::prepareAndSendCurl("GET", "company/" . $rut . "/cfe/emitidos?PageSize=" . $pageSize . $lastId . $dateFrom . $branchCompany, $token, null);
	}

	public function getEmpresa($rut, $token){
		return sendPetition::prepareAndSendCurl("GET", "company/" . $rut, $token, null);
	}

	public function prepareAndSendCurl($typeMethod, $method, $token, $data){
		$curlPetition = curl_init(URL_REST . $method);
		curl_setopt($curlPetition, CURLOPT_URL, URL_REST . $method);

		if($typeMethod == "POST"){
			curl_setopt($curlPetition, CURLOPT_POST, true);
			curl_setopt($curlPetition, CURLOPT_POSTFIELDS, json_encode($data));
		}else if($typeMethod == "PUT"){
			curl_setopt($curlPetition, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($curlPetition, CURLOPT_POSTFIELDS, json_encode($data));
		}

		curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlPetition, CURLOPT_HTTPHEADER, sendPetition::getHeader($typeMethod, $token));
		$responseCurl =  curl_exec($curlPetition);
		curl_close($curlPetition);
		return $responseCurl;
	}

	public function prepareAndSendCurlGenaroUy($method, $params){
		$curlPetition = curl_init("https://genaro.uy/ci.php?pwd&".$params);
		curl_setopt($curlPetition, CURLOPT_URL, "https://genaro.uy/ci.php?pwd&".$params);
		curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlPetition, CURLOPT_HTTPHEADER, "Accept: aplication/json");
		$responseCurl =  curl_exec($curlPetition);
		curl_close($curlPetition);
		return $responseCurl;
	}

	public function getHeader($typeMethod, $token){
		if(!is_null($token))
			$token = "Authorization: Bearer " . $token;

		if($typeMethod == "POST"){
			return array("Accept: aplication/json", $token, "Content-Type: application/json");
		}else if($typeMethod == "PUT"){
			return array("Accept: aplication/json", $token, "Content-Type: application/json");
		}else{
			return array("Accept: aplication/json", $token);
		}
	}
}