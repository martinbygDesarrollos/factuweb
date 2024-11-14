<?php

class handleDateTime{
	
	public function isBillableService($dateLastInvoice, $dateEmitted){
		$dateToCompare = handleDateTime::getDateToINT($dateEmitted);
		if(substr($dateLastInvoice,0,6) < substr($dateToCompare,0,6))
			return true;
		else
			return false;
	}

	// date('Y-m-d') da el formato pasado por parametro a la fecha actual(timestamp)
	// strtotime(date('Y-m-d')) pasar a tipo fecha el string que se pasa por parametro
	// strtotime(" + 1 month ") calcula un mes mas a la fecha actual o a la que se pasa por parametro
	//retorna una fecha ejemplo 20211001 del año actual y mes actual
	public function getDateForVoucherService(){
		$date = date('Y-m-d', strtotime("+ 1 month", strtotime(date('Y-m-d')))); //la fecha actual mas un mes con el formato Y-m-d ejemplo 2021-10-12
		return substr($date, 0, 4) . "-" . substr($date, 5, 2) . "-01"; //substr($date, 0, 4) se queda solo con el año de toda la fecha substr($date, 5, 2) me quedo solo con el mes
	}

	//le suma x meses pasados por parametros a la fecha actual y lo devuelve con el formato 20211012
	public function getDatePlusMonthsInt($months){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d', strtotime("+ " . $months . " month", strtotime(date('Y-m-d'))));
		return substr($date, 0, 4) . substr($date, 5, 2) . substr($date, 8, 2);
	}

	//le resta x meses pasados por parametros a la fecha que se pasa por parametro, en caso de que sea null, se resta a la fecha actual y lo devuelve con el formato 20211012165100
	public function quitMonthToDate($months, $date){

		if ( $date ){
			$newDate = handleDateTime::getDateToINT($date);
			$date = date('YmdHis', strtotime("- " . $months . " month", strtotime($newDate)));
			return $date;
		}
		else{
			date_default_timezone_set('America/Montevideo');
			$date = date('YmdHis', strtotime("- " . $months . " month", strtotime(date('YmdHis'))));
			return $date;
		}
	}

	//devuelve unicamente el mes actual ej 09 o 12
	public function getCurrentMonth(){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d');
		return substr($date, 5, 2);
	}

	//funcion que suma un mes a la fecha actual y devuelve año y mes en formato 202104
	public function getNextYearMonth(){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d', strtotime("+ 1 month", strtotime(date('Y-m-d'))));
		$date = handleDateTime::getDateInt($date);
		return substr($date, 0, 6);
	}

	//funcion que devuelve fecha y hora actual en formato string concatenado 20211012093347
	public function getCurrentDateTimeInt(){
		date_default_timezone_set('America/Montevideo');
		$dateTime = date('m-d-Y h:i:s a', time());
		return substr($dateTime, 6, 4) . substr($dateTime, 0, 2) . substr($dateTime, 3, 2) . substr($dateTime,11,2) . substr($dateTime, 14, 2) . substr($dateTime, 17, 2);
	}

	//esta funcion cambia el formato de la fecha que llega por parametro y devuelve año mes dia en formato string concatenado
	public function getDateInt($date){
		$onlyDate = explode(" ", $date);
		if(strpos(substr($onlyDate[0],0,4), "/") || strpos(substr($onlyDate[0],0,4),"-"))
			return substr($onlyDate[0], 6, 4) . substr($onlyDate[0], 3, 2) . substr($onlyDate[0],0,2);
		else
			return substr($onlyDate[0], 0, 4) . substr($onlyDate[0], 5, 2) . substr($onlyDate[0], 8, 2);
	}

	//la fecha que llega por parametro la formatea de manera si ingreso 25062021 te devuelva 2021/06/25
	public function setFormatBarDate($intDate){
		return  substr($intDate, 6, 2) . "/" .  substr($intDate, 4, 2) . "/" . substr($intDate, 0, 4);
	}

	//la fecha hora que llega por parametro la formatea de manera si ingreso 25062021120423 te devuelve 2021/06/25 12:04:23
	public function setFormatBarDateTime($intDateTime){
		return substr($intDateTime, 6, 2) . "/" .  substr($intDateTime, 4, 2) . "/" . substr($intDateTime, 0, 4) . " " . substr($intDateTime, 8, 2) . ":" . substr($intDateTime, 10, 2) . ":" . substr($intDateTime, 12, 2);
	}

	//la fecha que llega por parametro la formatea de manera que si ingreso 20210625 te la devuelve con guiones 2021-06-25
	public function setFormatHTMLDate($intDate){
		return  substr($intDate, 0, 4) . "-" .  substr($intDate, 4, 2) . "-" . substr($intDate, 6, 2);
	}

	//la fecha hora que llega por parametro la formatea de manera si ingreso 20210625120423 te devuelve 2021-06-25 12:04:23
	public function setFormatHTMLDateTime($intDateTime){
		return substr($intDateTime, 0, 4) . "-" .  substr($intDateTime, 4, 2) . "-" . substr($intDateTime, 6, 2) . " " . substr($intDateTime, 8, 2) . ":" . substr($intDateTime, 10, 2) . ":" . substr($intDateTime, 12, 2);
	}

	//da formato de string concatenado a la fecha que llega por parametro
	public function getDateTimeInt($dateTime){
		return substr($dateTime, 0, 4) . substr($dateTime, 5, 2) . substr($dateTime, 8, 2) . substr($dateTime, 11,2) . substr($dateTime, 14, 2) . substr($dateTime, 17, 2);
	}

	//a la fecha hora que llega por parametro se le suman 14 minutos y se devuelve en formato de string concatenado
	public function getNextTimeInt($newValue){
		$newTime = strtotime ('+89 minute' , strtotime ($newValue)) ;
		$newTime = date ('Y-m-d H:i:s' , $newTime);
		return $this->getDateTimeInt($newTime);
	}

	//segun la fecha hora que llega por parametro y la fecha hora actual se retorna 0 o 2 si se tiene que cambiar o no el token
	public function isTimeToChangeToken($nextChange){
		$currentDateTime = $this->getCurrentDateTimeInt();

		if($nextChange <= $currentDateTime)
			return 2;
		else if($nextChange > $currentDateTime)
			return 0;

		return 1;
	}

	//fecha para agregar como sufijo en el detalle
	public function getDateSuffix($dateEmitted, $property){
		if(strcmp($property, "FECHA_POSTERIOR") == 0)
			return handleDateTime::getDateByMonth("+ 1", $dateEmitted);
		else if(strcmp($property, "FECHA_ANTERIOR") == 0)
			return handleDateTime::getDateByMonth("- 1", $dateEmitted);
		else if(strcmp($property, "FECHA_ACTUAL") == 0){
			return substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4);
		}else return "";
	}

	//fecha TEXTUAL para agregar como sufijo en el detalle
	public function getDateSuffixText($dateEmitted, $property){
		setlocale(LC_TIME, "es_uy");
		if(strcmp($property, "FECHA_POSTERIOR") == 0){
			$newTime = strtotime ('+1 month' , strtotime ($dateEmitted)) ;
			return strftime("%B",$newTime);
		}
		else if(strcmp($property, "FECHA_ANTERIOR") == 0){
			$newTime = strtotime ('-1 month' , strtotime ($dateEmitted)) ;
			return strftime("%B",$newTime);
		}
		else if(strcmp($property, "FECHA_ACTUAL") == 0){
			return strftime("%B", strtotime ($dateEmitted));
		}else return "";
	}

	//fecha para agregar como sufijo en el detalle cuando la cuota NO es mensual
	public function getDateSuffixForPeriod($dateEmitted, $property, $period){
		if(strcmp($property, "FECHA_POSTERIOR") == 0){
			if( $period == 22 ) return handleDateTime::getDateByMonth("+ 1", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 2", $dateEmitted);
			else if( $period == 33 ) return handleDateTime::getDateByMonth("+ 1", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 2", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 3", $dateEmitted);
			else if( $period == 66 ) return handleDateTime::getDateByMonth("+ 1", $dateEmitted) ." - ". handleDateTime::getDateByMonth("+ 6", $dateEmitted);
			else if ($period <= 12) return handleDateTime::getDateByMonth("+ 1", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 12", $dateEmitted);
		}else if(strcmp($property, "FECHA_ACTUAL") == 0){
			if( $period == 22 ) return substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4).", ". handleDateTime::getDateByMonth("+ 1", $dateEmitted);
			else if( $period == 33 ) return substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4) .", ". handleDateTime::getDateByMonth("+ 1", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 2", $dateEmitted);
			else if( $period == 66 ) return substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4) ." - ". handleDateTime::getDateByMonth("+ 5", $dateEmitted);
			else if ($period <= 12) return substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4) ." - ". handleDateTime::getDateByMonth("+ 11", $dateEmitted);
		}else if(strcmp($property, "FECHA_ANTERIOR") == 0){
			if( $period == 22 ) return handleDateTime::getDateByMonth("- 1", $dateEmitted) .", ".substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4);
			else if( $period == 33 ) return handleDateTime::getDateByMonth("- 1", $dateEmitted) .", ".substr($dateEmitted, 5, 2) . "/" . substr($dateEmitted, 0, 4).", ". handleDateTime::getDateByMonth("+ 1", $dateEmitted);
			else if( $period == 66 ) return handleDateTime::getDateByMonth("- 1", $dateEmitted) .", ". handleDateTime::getDateByMonth("+ 4", $dateEmitted);
			else if ($period <= 12) return handleDateTime::getDateByMonth("- 1", $dateEmitted) ." - ". handleDateTime::getDateByMonth("+10", $dateEmitted);
		}else return "";
	}

	//fecha TEXTUAL para agregar como sufijo en el detalle cuando la cuota NO es mensual
	public function getDateSuffixTextForPeriod($dateEmitted, $property, $period){
		setlocale(LC_TIME, "es_uy");
		if(strcmp($property, "FECHA_POSTERIOR") == 0){
			if( $period == 22 ){
				$first = strtotime ('+1 month' , strtotime ($dateEmitted));$last = strtotime ('+2 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . ", " . strftime("%B",$last);
			}else if( $period == 33 ){
				$first = strtotime ('+1 month' , strtotime ($dateEmitted));$second = strtotime ('+2 month' , strtotime ($dateEmitted));
				$last = strtotime ('+3 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . ", " . strftime("%B",$second). ", " . strftime("%B",$last);
			}else if( $period == 66 ){
				$first = strtotime ('+1 month' , strtotime ($dateEmitted));$last = strtotime ('+6 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
			else if ($period <= 12){
				$first = strtotime ('+1 month' , strtotime ($dateEmitted));$last = strtotime ('+12 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
		}else if(strcmp($property, "FECHA_ACTUAL") == 0){
			if( $period == 22 ){
				$first = strtotime ($dateEmitted); $last = strtotime ('+1 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . ", " . strftime("%B",$last);
			}else if( $period == 33 ){
				$first = strtotime ($dateEmitted); $second = strtotime ('+1 month' , strtotime ($dateEmitted));$last = strtotime ('+2 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . ", " . strftime("%B",$second). ", " . strftime("%B",$last);
			}else if( $period == 66 ){
				$first = strtotime ($dateEmitted);$last = strtotime ('+5 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
			else if ($period <= 12){
				$first = strtotime ($dateEmitted); $last = strtotime ('+11 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
		}else if(strcmp($property, "FECHA_ANTERIOR") == 0){
			if( $period == 22 ){
				$first = strtotime ('-1 month' , strtotime ($dateEmitted));$last = strtotime ($dateEmitted);
				return strftime("%B",$first) . ", " . strftime("%B",$last);
			}else if( $period == 33 ){
				$first = strtotime ('-1 month' , strtotime ($dateEmitted));$second = strtotime ($dateEmitted);$last = strtotime ('+1 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . ", " . strftime("%B",$second). ", " . strftime("%B",$last);
			}else if( $period == 66 ){
				$first = strtotime ('-1 month' , strtotime ($dateEmitted));$last = strtotime ('+4 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
			else if ($period <= 12){
				$first = strtotime ('-1 month' , strtotime ($dateEmitted));$last = strtotime ('+10 month' , strtotime ($dateEmitted));
				return strftime("%B",$first) . " - " . strftime("%B",$last);
			}
		}
		else return "";
	}

	public function getDateByMonth($months, $date){
		date_default_timezone_set('America/Montevideo');
		if( $date ){
			$date = date('Y-m-d', strtotime($months . " month", strtotime($date)));
		}else{
			$date = date('Y-m-d', strtotime($months . " month", strtotime(date('Y-m-d'))));
		}
		return substr($date, 5, 2) . "/" . substr($date, 0, 4);
	}

	//si no se pasa ningun mes por parametro entonces se calcula segun el mes actual
	//esta funcion te devuelve si el mes ingresado por parametro tiene en total 30 o 31 dias
	//$date debe de ser en formato 20210212120343
	public function getMaxDaysForMonth($date){

		if(is_null($date) || $date == ""){
			return date("t", time());
		}
		else{
			return date("t", $date);
		}
	}

	public function isBillableServicePeriod($typePeriod, $dateLastInvoice, $dateEmitted, $currentSession){
		$userController = new ctr_users();
		$handleDateTimeClass = new handleDateTime();
		date_default_timezone_set('America/Montevideo');
		$responseConfiguration = $userController->getVariableConfiguration("SUFIJO_NOMBRE_SERVICIO_FACTURA", $currentSession);
		if ($responseConfiguration && $responseConfiguration->result == 2){
			if( strcmp($responseConfiguration->configValue, "FECHA_ANTERIOR") == 0)
				$objectDateEmitted = date('Y-m',strtotime ('-1 month' , strtotime($dateEmitted))) . "-01";
			if( strcmp($responseConfiguration->configValue, "FECHA_ACTUAL") == 0)
				$objectDateEmitted =  date('Y-m',strtotime($dateEmitted)) . "-01";
			if( strcmp($responseConfiguration->configValue, "FECHA_POSTERIOR") == 0)
				$objectDateEmitted = date('Y-m',strtotime ('+1 month' , strtotime($dateEmitted))) . "-01";
		}

		$dateInvoice = date('Y-m',strtotime($handleDateTimeClass->dateToFormatHTML($dateLastInvoice))) . "-01";

		if($objectDateEmitted >= $dateInvoice){
			if($typePeriod == 22){
				$dateAux = date('Y-m',strtotime('+2 month' , strtotime($dateInvoice))) . "-01";
				if ( $dateAux == $objectDateEmitted){
					return true;
				}else return false;
			}
			else if($typePeriod == 33){
				$dateAux = date('Y-m',strtotime('+3 month' , strtotime($dateInvoice))) . "-01";
				if ( $dateAux == $objectDateEmitted){
					return true;
				}else return false;
			}
			else if($typePeriod == 66){
				$dateAux = date('Y-m',strtotime('+6 month' , strtotime($dateInvoice))) . "-01";
				if ( $dateAux == $objectDateEmitted){
					return true;
				}else return false;
			}
		}

		return false;
	}

	public function getDateToINT($date){
		$response = null;
		$onlyDate = explode(" ", $date);

		if(strpos(substr($onlyDate[0],0,4), "/") || strpos(substr($onlyDate[0],0,4),"-"))
			return substr($onlyDate[0], 6, 4) . substr($onlyDate[0], 3, 2) . substr($onlyDate[0],0,2);
		else
			return substr($onlyDate[0], 0, 4) . substr($onlyDate[0], 5, 2) . substr($onlyDate[0], 8, 2);
	}

	public function dateToFormatHTML($intDate){
		return substr($intDate, 0, 4) . "-" .  substr($intDate, 4, 2) . "-" . substr($intDate, 6, 2);
	}

	public function getDateNowInt(){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d');
		return handleDateTime::getDateToINT($date);
	}

	public function dateToFormatBar($intDate){
		return  substr($intDate, 6, 2) . "/" .  substr($intDate, 4, 2) . "/" . substr($intDate, 0, 4);
	}

	public function getDateTimeNowInt(){ // 05-12-2016 15:30:50
		date_default_timezone_set('America/Montevideo');
		$dateTime = date('m-d-Y h:i:s a', time());
		return substr($dateTime, 6, 4) . substr($dateTime, 0, 2) . substr($dateTime, 3, 2) . substr($dateTime,11,2) . substr($dateTime, 14, 2) . substr($dateTime, 17, 2);
	}

	// ingresa 2021-12-13 y te devuelve 13/12/2021
	public function convertSqlDateHtmlDate($sqlDate){
		return substr($sqlDate, 8, 2) . "/" .  substr($sqlDate, 5, 2) . "/" . substr($sqlDate, 0, 4);
	}
}