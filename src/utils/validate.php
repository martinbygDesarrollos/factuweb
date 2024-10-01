<?php

class validate{

	public function validateCI($ci){
		$ciLimpia = preg_replace( '/\D/', '', $ci );
		$validationDigit = $ciLimpia[-1];
		$ciLimpia = preg_replace('/[0-9]$/', '', $ciLimpia );

		$ci = preg_replace( '/\D/', '', $ci );
		$ci = str_pad( $ci, 7, '0', STR_PAD_LEFT );
		$a = 0;

		$baseNumber = "2987634";
		for ( $i = 0; $i < 7; $i++ ) {
			$baseDigit = $baseNumber[ $i ];
			$ciDigit = $ci[ $i ];
			$a += ( intval($baseDigit ) * intval( $ciDigit ) ) % 10;
		}

		return $a % 10 == 0 ? 0 : 10 - $a % 10;
	}

	public function validateRUT($rut){
		$response = new \stdClass();

		$lengthRut = strlen($rut);
		if(( $lengthRut < 10 || $lengthRut > 12)){
			$response->result = 1;
			$response->message = "El rut ingresado no tiene una longitud valida para ser procesado.";
			return $response;
		}

		if(!is_numeric($rut)){
			$response->result = 1;
			$response->message = "El rut ingresado contiene caracteres no numéricos.";
			return $response;
		}

		$pattern = "/^[[:digit:]]+$/";
		if (!preg_match($pattern, $rut)){
			$response->result = 1;
			$response->message = "El rut ingresado contiene caracteres no numéricos.";
			return $response;
		}

		$rutDigitVerify = substr($rut, ($lengthRut-1), 1);
		$rutNumber = substr($rut, 0, ($lengthRut -1));

		$total = 0;
		$factors = array(2,3,4,5,6,7,8,9,2,3,4);

		$factorIndex = 0;
		for ($i = ($lengthRut-2); $i >= 0; $i--) {
			$total += ($factors[$factorIndex] *  substr($rut, $i, 1));
			$factorIndex++;
		}

		$digitVerify = 11 - ($total % 11);
		if($digitVerify == 11)
			$digitVerify = 0;
		else if($digitVerify == 10)
			$digitVerify = 1;

		if($digitVerify == $rutDigitVerify)
			$response->result = 2;
		else{
			$response->result = 1;
			$response->message = "El rut '" . $rut . "' no es valido.";
		}

		return $response;
	}

	public function validateEmail($email){
		$response = new \stdClass();

		if(filter_var($email, FILTER_VALIDATE_EMAIL))
			$response->result = 2;
		else{
			$response->result = 1;
			$response->message = "El correo ingresado no es valido.";
		}

		return $response;
	}
}