<?php

class others{

	public function getCostFromAmountAndIVA($amount, $idIVA){
		$responseQuery = others::getValueIVA($idIVA);
		if($responseQuery->result == 2){
			return $amount - (($responseQuery->objectResult->valor / 100) * $amount);
		}else return 0;
	}

	public function getAmountFromCostAndIVA($cost, $idIVA){
		$responseQuery = others::getValueIVA($idIVA);
		if($responseQuery->result == 2){
			return $cost + (($responseQuery->objectResult->valor / 100) * $cost);
		}else return 0;
	}

	public function getValueIVA($idIVA){
		$responseQuery = DataBase::sendQuery("SELECT * FROM indicadores_facturacion WHERE id = ?", array('i', $idIVA), "OBJECT");
		if($responseQuery->result == 1){
			$responseQuery->message = "Los indicadores de facturación no estan insertados en la base de datos.";
		}
		return $responseQuery;
	}

	public function getListIva(){
		$responseQuery = DataBase::sendQuery("SELECT * FROM indicadores_facturacion", null, "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $value) {
				$value['valor'] = number_format($value['valor'], 2,",",".") . " %";
				$arrayResult[] = $value;
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "No se encontraron los indicadores de facturación en la base de datos.";
		}
		return $responseQuery;
	}

	public function loadIndicadoresFacturacion(){
		$responseQuery = others::getListIva();
		if($responseQuery->result == 1){
			$sqliIndicadores =  "INSERT INTO indicadores_facturacion (id, nombre, valor) VALUES (?,?,?)";

			DataBase::sendQuery($sqliIndicadores , array('isi', 1, 'Exento de IVA', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 2, 'Gravado a Tasa Mínima', 10.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 3, 'Gravado a Tasa Básica', 22.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 4, 'Gravado a Otra Tasa', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 5, 'Entrega gratuita', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 6, 'Producto o servicio no facturable', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 7, 'Producto o servicio no facturable negativo', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 8, 'Ítem a rebajar en e-Remitos', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 9, 'Ítem a anular en e-Resguardos', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 10, 'Exportación y asimiladas', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 11, 'Impuesto percibido', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 12, 'IVA en suspenso', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 13, 'Ítem vendido por un no contribuyente', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 14, 'Item vendido por un contribuyente Monotributo', 0.00),"BOOLE");
			DataBase::sendQuery($sqliIndicadores, array('isi', 15, 'Ítem vendido por un contribuyente IMEBA', 0.00),"BOOLE");
		}
	}
}