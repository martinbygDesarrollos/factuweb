<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class managment_spreadsheet{

	public function basicExample(){
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'Hello World !');

		$writer = new Xlsx($spreadsheet);
		$writer->save('hello world.xlsx');
	}

	public function accountState( $accountState, $documentEntity, $nameEntity, $dateInitBar, $dateFinishBar, $prepareFor, $idBusiness, $nameBusiness ){

		$numberFormat = new NumberFormat();
		$price_number = $numberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
		//var_dump($accountState);exit;
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();


		//INFORMACION GENERAL
		//nombre empresa logueada
		//$sheet->getDefaultStyle()->getFont()->setSize(14);
		$sheet->setCellValue('A1', $nameBusiness);
		$sheet->getStyle('A1')->getFont()->setBold(true);

		//descripcion de la moneda
		//$sheet->getDefaultStyle()->getFont()->setSize(12);
		$coinSymbol = '';
		if ( $accountState['MAINCOIN'] == "UYU" ){
			$coinSymbol = '$';
			$sheet->setCellValue('A3', "ESTADO DE CUENTA EN Pesos Uruguayos");
		}elseif ( $accountState['MAINCOIN'] == "USD" ){
			$coinSymbol = 'U$S';
			$sheet->setCellValue('A3', "ESTADO DE CUENTA EN Dólares");
		}

		//descripcion de la moneda
		if ( $prepareFor == "CLIENT" ){
			$sheet->setCellValue('A5', "Cliente: [".$documentEntity."] ".$nameEntity);
		}elseif ( $prepareFor == "PROVIDER" ){
			$sheet->setCellValue('A5', "Proveedor: [".$documentEntity."] ".$nameEntity);
		}

		//fechas
		$sheet->setCellValue('A7', "Desde ".$dateInitBar." hasta ".$dateFinishBar);


		//tabla reporte
		$sheet->setCellValue('A9', "FECHA");
		$sheet->setCellValue('B9', "DOCUMENTO");
		$sheet->setCellValue('C9', "DEBE");
		$sheet->setCellValue('D9', "HABER");
		$sheet->setCellValue('E9', "SALDO");

		$excelRow = 10;
		foreach ($accountState['listResult'] as $value) {
			$sheet->setCellValue('A'.$excelRow, $value['FECHA']);
			$sheet->setCellValue('B'.$excelRow, $value["DOCUMENTO"]);

			if ( gettype( $value["DEBE"] ) == "string" && $value["DEBE"] !== 0 ){
				$sheet->setCellValue('C'.$excelRow,$value["INTDEBE"] );

				$sheet->getStyle('C'.$excelRow)
				->getNumberFormat()
				->setFormatCode($price_number);
			}

			if ( gettype( $value["HABER"] ) == "string" && $value["HABER"] !== 0 ){
				$sheet->setCellValue('D'.$excelRow, $value["INTHABER"]);

				$sheet->getStyle('D'.$excelRow)
				->getNumberFormat()
				->setFormatCode($price_number);
			}


			$sheet->setCellValue('E'.$excelRow,  $value["INTSALDO"]);
			$sheet->getStyle('E'.$excelRow)
				->getNumberFormat()
				->setFormatCode($price_number);


			if ( $value["DOCUMENTO"] === "Saldo inicial" ){
				$sheet->setCellValue('C'.$excelRow, "");
				$sheet->setCellValue('D'.$excelRow, "");
			}
			$excelRow = $excelRow + 1;
		}


		$sheet->setCellValue('B'.($excelRow +1), 'Total');
		$sheet->getStyle('B'.($excelRow +1))->getFont()->setBold(true);
		$sheet->setCellValue('E'.($excelRow +1), $accountState['INTSALDOTOTAL']);
		$sheet->getStyle('E'.($excelRow +1))->getNumberFormat()->setFormatCode($price_number);
	    $sheet->getStyle('E'.($excelRow +1))->getFont()->setBold(true);

		$sheet->getColumnDimension('A')->setWidth(15);
		$sheet->getColumnDimension('B')->setAutoSize(true);
		$sheet->getColumnDimension('C')->setAutoSize(true);
		$sheet->getColumnDimension('D')->setAutoSize(true);
		$sheet->getColumnDimension('E')->setAutoSize(true);


		$writer = new Xlsx($spreadsheet);
		$writer->save("excel/EC_".$documentEntity."_".date("Ym").".xlsx");

		$file_name = "EC_".$documentEntity."_".date("Ym");
		return $file_name;
	}

	public function vouchersDetails( $arrayVouchers, $typeMoney, $yearMonth ){
		//var_dump("lista de comprobantes",$arrayVouchers);exit;
		$response = new \stdClass();

		if ( $typeMoney == "1" ){
			return $this->vouchersDetailsMoneyUnified( $arrayVouchers, $yearMonth );
		}


		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle("CFEs emitidos");

		$numberFormat = new NumberFormat();
		$rut_format = $numberFormat::FORMAT_NUMBER;
		$price_format = $numberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

		//$sheet->setCellValue('A1', "RUT");
		//$sheet->setCellValue('B1', "Nombre emisor");
		$sheet->setCellValue('A1', "Tipo");
		$sheet->setCellValue('B1', "Serie");
		$sheet->setCellValue('C1', "Número");
		$sheet->setCellValue('D1', "ID de la Compra");
		$sheet->setCellValue('E1', "Documento receptor");
		$sheet->setCellValue('F1', "Nombre receptor");
		$sheet->setCellValue('G1', "Fecha emisión");
		$sheet->setCellValue('H1', "Fecha creación");
		$sheet->setCellValue('I1', "Cod. artículo");
		$sheet->setCellValue('J1', "Artículo");
		$sheet->setCellValue('K1', "Ind. Facturación");
		$sheet->setCellValue('L1', "Moneda");
		$sheet->setCellValue('M1', "Cambio");
		$sheet->setCellValue('N1', "Precio unitario");
		$sheet->setCellValue('O1', "Cantidad");
		$sheet->setCellValue('P1', "Total");

		$indexRowExcel = 2;
		foreach ($arrayVouchers as $ikey => $ivalue) {
			foreach ($ivalue['detalles'] as $jkey => $jvalue) {
				//$sheet->setCellValue(('A'.$indexRowExcel), $ivalue['businessrut']);
				//$sheet->getStyle('A'.$indexRowExcel)->getNumberFormat()->setFormatCode($rut_format);
				//$sheet->setCellValue(('B'.$indexRowExcel), $ivalue['business']);
				$sheet->setCellValue(('A'.$indexRowExcel), $ivalue['voucher']);
				$sheet->setCellValue(('B'.$indexRowExcel), $ivalue['serieCFE']);
				$sheet->setCellValue(('C'.$indexRowExcel), $ivalue['numeroCFE']);
				$sheet->setCellValue(('D'.$indexRowExcel), $jvalue['idCompra']);
				$sheet->setCellValue(('E'.$indexRowExcel), $ivalue['docClient']);
				$sheet->getStyle('E'.$indexRowExcel)->getNumberFormat()->setFormatCode($rut_format);
				$sheet->setCellValue(('F'.$indexRowExcel), $ivalue['nombreCliente']);
				$sheet->setCellValue(('G'.$indexRowExcel), $ivalue['fecha']);
				$sheet->setCellValue(('H'.$indexRowExcel), $ivalue['fechaHoraEmision']);
				$sheet->setCellValue(('I'.$indexRowExcel), $jvalue['codItem']);
				$sheet->setCellValue(('J'.$indexRowExcel), $jvalue['nomItem']);
				$sheet->setCellValue(('K'.$indexRowExcel), $jvalue['indFact']);
				$sheet->setCellValue(('L'.$indexRowExcel), $ivalue['moneda']);
				$sheet->setCellValue(('M'.$indexRowExcel), $jvalue['tipoCambio']);
				$sheet->getStyle('M'.$indexRowExcel)->getNumberFormat()->setFormatCode($price_format);

				$sheet->setCellValue(('N'.$indexRowExcel), $jvalue['precio']);
				$sheet->getStyle('N'.$indexRowExcel)->getNumberFormat()->setFormatCode($price_format);

				$sheet->setCellValue(('O'.$indexRowExcel), $jvalue['cantidad']);

				$sheet->setCellValue(('P'.$indexRowExcel), $jvalue['total']);
				$sheet->getStyle('P'.$indexRowExcel)->getNumberFormat()->setFormatCode($price_format);

				$indexRowExcel++;
			}

		}



		$sheet->getColumnDimension('A')->setAutoSize(true);
		$sheet->getColumnDimension('B')->setAutoSize(true);
		$sheet->getColumnDimension('C')->setAutoSize(true);
		$sheet->getColumnDimension('D')->setAutoSize(true);
		$sheet->getColumnDimension('E')->setAutoSize(true);
		$sheet->getColumnDimension('F')->setAutoSize(true);
		$sheet->getColumnDimension('G')->setAutoSize(true);
		$sheet->getColumnDimension('H')->setAutoSize(true);
		$sheet->getColumnDimension('I')->setAutoSize(true);
		$sheet->getColumnDimension('J')->setAutoSize(true);
		$sheet->getColumnDimension('K')->setAutoSize(true);
		$sheet->getColumnDimension('L')->setAutoSize(true);
		$sheet->getColumnDimension('M')->setAutoSize(true);
		$sheet->getColumnDimension('N')->setAutoSize(true);
		$sheet->getColumnDimension('O')->setAutoSize(true);
		$sheet->getColumnDimension('P')->setAutoSize(true);

		$writer = new Xlsx($spreadsheet);
		$writer->save("excel/Emitidos_".$yearMonth.".xlsx");

		$file_name = "Emitidos_".$yearMonth;
		$response->result = 2;
		$response->name = $file_name;

		return $response;
	}

	function vouchersDetailsMoneyUnified( $arrayVouchers, $yearMonth ){
		$response = new \stdClass();

		//en la hoja del tipo de moneda uyu no agregar tipo cambio
		$spreadsheet = new Spreadsheet();
		$sheetPesos = $spreadsheet->getActiveSheet();
		$sheetPesos->setTitle("CFEs emitidos - UYU");

		$sheetDolar = $spreadsheet->createSheet();
		$sheetDolar->setTitle("CFEs emitidos - USD");

		$numberFormat = new NumberFormat();
		$rut_format = $numberFormat::FORMAT_NUMBER;
		$price_format = $numberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

		$sheetPesos->setCellValue('A1', "Tipo");
		$sheetPesos->setCellValue('B1', "Serie");
		$sheetPesos->setCellValue('C1', "Número");
		$sheetPesos->setCellValue('D1', "ID de la Compra");
		$sheetPesos->setCellValue('E1', "Documento receptor");
		$sheetPesos->setCellValue('F1', "Nombre receptor");
		$sheetPesos->setCellValue('G1', "Fecha emisión");
		$sheetPesos->setCellValue('H1', "Fecha creación");
		$sheetPesos->setCellValue('I1', "Cod. artículo");
		$sheetPesos->setCellValue('J1', "Artículo");
		$sheetPesos->setCellValue('K1', "Ind. Facturación");

		$sheetPesos->setCellValue('L1', "Precio unitario");
		$sheetPesos->setCellValue('M1', "Cantidad");

		$sheetPesos->setCellValue('N1', "Total");


		$sheetDolar->setCellValue('A1', "Tipo");
		$sheetDolar->setCellValue('B1', "Serie");
		$sheetDolar->setCellValue('C1', "Número");
		$sheetDolar->setCellValue('D1', "ID de la Compra");
		$sheetDolar->setCellValue('E1', "Documento receptor");
		$sheetDolar->setCellValue('F1', "Nombre receptor");
		$sheetDolar->setCellValue('G1', "Fecha emisión");
		$sheetDolar->setCellValue('H1', "Fecha creación");
		$sheetDolar->setCellValue('I1', "Cod. artículo");
		$sheetDolar->setCellValue('J1', "Artículos");
		$sheetDolar->setCellValue('K1', "Ind. Facturación");
		$sheetDolar->setCellValue('L1', "Cambio");

		$sheetDolar->setCellValue('M1', "Precio unitario");
		$sheetDolar->setCellValue('N1', "Cantidad");


		$sheetDolar->setCellValue('O1', "Total");

		$indexRowExcelDolar = 2;
		$indexRowExcelPesos = 2;

		foreach ($arrayVouchers as $ikey => $ivalue) {
			if ( $ivalue['moneda'] == "UYU" ){
				foreach ($ivalue['detalles'] as $jkey => $jvalue) {
					$sheetPesos->setCellValue(('A'.$indexRowExcelPesos), $ivalue['voucher']);
					$sheetPesos->setCellValue(('B'.$indexRowExcelPesos), $ivalue['serieCFE']);
					$sheetPesos->setCellValue(('C'.$indexRowExcelPesos), $ivalue['numeroCFE']);
					$sheetPesos->setCellValue(('D'.$indexRowExcelPesos), $jvalue['idCompra']);
					$sheetPesos->setCellValue(('E'.$indexRowExcelPesos), $ivalue['docClient']);
					$sheetPesos->getStyle('E'.$indexRowExcelPesos)->getNumberFormat()->setFormatCode($rut_format);
					$sheetPesos->setCellValue(('F'.$indexRowExcelPesos), $ivalue['nombreCliente']);
					$sheetPesos->setCellValue(('G'.$indexRowExcelPesos), $ivalue['fecha']);
					$sheetPesos->setCellValue(('H'.$indexRowExcelPesos), $ivalue['fechaHoraEmision']);
					$sheetPesos->setCellValue(('I'.$indexRowExcelPesos), $jvalue['codItem']);
					$sheetPesos->setCellValue(('J'.$indexRowExcelPesos), $jvalue['nomItem']);
					$sheetPesos->setCellValue(('K'.$indexRowExcelPesos), $jvalue['indFact']);

					$sheetPesos->setCellValue(('L'.$indexRowExcelPesos), $jvalue['precio']);
					$sheetPesos->getStyle('L'.$indexRowExcelPesos)->getNumberFormat()->setFormatCode($price_format);

					$sheetPesos->setCellValue(('M'.$indexRowExcelPesos), $jvalue['cantidad']);


					$sheetPesos->setCellValue(('N'.$indexRowExcelPesos), $jvalue['total']);
					$sheetPesos->getStyle('N'.$indexRowExcelPesos)->getNumberFormat()->setFormatCode($price_format);

					$indexRowExcelPesos++;
				}
			}else{
				foreach ($ivalue['detalles'] as $jvaluedolar) {
					$sheetDolar->setCellValue(('A'.$indexRowExcelDolar), $ivalue['voucher']);
					$sheetDolar->setCellValue(('B'.$indexRowExcelDolar), $ivalue['serieCFE']);
					$sheetDolar->setCellValue(('C'.$indexRowExcelDolar), $ivalue['numeroCFE']);
					$sheetDolar->setCellValue(('D'.$indexRowExcelDolar), $jvalue['idCompra']);
					$sheetDolar->setCellValue(('E'.$indexRowExcelDolar), $ivalue['docClient']);
					$sheetDolar->getStyle('E'.$indexRowExcelDolar)->getNumberFormat()->setFormatCode($rut_format);
					$sheetDolar->setCellValue(('F'.$indexRowExcelDolar), $ivalue['nombreCliente']);
					$sheetDolar->setCellValue(('G'.$indexRowExcelDolar), $ivalue['fecha']);
					$sheetDolar->setCellValue(('H'.$indexRowExcelDolar), $ivalue['fechaHoraEmision']);
					$sheetDolar->setCellValue(('I'.$indexRowExcelDolar), $jvaluedolar['codItem']);
					$sheetDolar->setCellValue(('J'.$indexRowExcelDolar), $jvaluedolar['nomItem']);
					$sheetDolar->setCellValue(('K'.$indexRowExcelDolar), $jvaluedolar['indFact']);
					$sheetDolar->setCellValue(('L'.$indexRowExcelDolar), $jvaluedolar['tipoCambio']);
					$sheetDolar->getStyle('L'.$indexRowExcelDolar)->getNumberFormat()->setFormatCode($price_format);


					$sheetDolar->setCellValue(('M'.$indexRowExcelDolar), $jvaluedolar['precio']);
					$sheetDolar->getStyle('M'.$indexRowExcelDolar)->getNumberFormat()->setFormatCode($price_format);

					$sheetDolar->setCellValue(('N'.$indexRowExcelDolar), $jvaluedolar['cantidad']);


					$sheetDolar->setCellValue(('O'.$indexRowExcelDolar), $jvaluedolar['totalusd']);
					$sheetDolar->getStyle('O'.$indexRowExcelDolar)->getNumberFormat()->setFormatCode($price_format);

					$indexRowExcelDolar++;
				}
			}
		}


		$sheetPesos->getColumnDimension('A')->setAutoSize(true);
		$sheetPesos->getColumnDimension('B')->setAutoSize(true);
		$sheetPesos->getColumnDimension('C')->setAutoSize(true);
		$sheetPesos->getColumnDimension('D')->setAutoSize(true);
		$sheetPesos->getColumnDimension('E')->setAutoSize(true);
		$sheetPesos->getColumnDimension('F')->setAutoSize(true);
		$sheetPesos->getColumnDimension('G')->setAutoSize(true);
		$sheetPesos->getColumnDimension('H')->setAutoSize(true);
		$sheetPesos->getColumnDimension('I')->setAutoSize(true);
		$sheetPesos->getColumnDimension('J')->setAutoSize(true);
		$sheetPesos->getColumnDimension('K')->setAutoSize(true);
		$sheetPesos->getColumnDimension('L')->setAutoSize(true);
		$sheetPesos->getColumnDimension('M')->setAutoSize(true);
		$sheetPesos->getColumnDimension('N')->setAutoSize(true);

		$sheetDolar->getColumnDimension('A')->setAutoSize(true);
		$sheetDolar->getColumnDimension('B')->setAutoSize(true);
		$sheetDolar->getColumnDimension('C')->setAutoSize(true);
		$sheetDolar->getColumnDimension('D')->setAutoSize(true);
		$sheetDolar->getColumnDimension('E')->setAutoSize(true);
		$sheetDolar->getColumnDimension('F')->setAutoSize(true);
		$sheetDolar->getColumnDimension('G')->setAutoSize(true);
		$sheetDolar->getColumnDimension('H')->setAutoSize(true);
		$sheetDolar->getColumnDimension('I')->setAutoSize(true);
		$sheetDolar->getColumnDimension('J')->setAutoSize(true);
		$sheetDolar->getColumnDimension('K')->setAutoSize(true);
		$sheetDolar->getColumnDimension('L')->setAutoSize(true);
		$sheetDolar->getColumnDimension('M')->setAutoSize(true);
		$sheetDolar->getColumnDimension('N')->setAutoSize(true);
		$sheetDolar->getColumnDimension('O')->setAutoSize(true);


		$writer = new Xlsx($spreadsheet);
		$writer->save("excel/Emitidos_discriminado_moneda_".$yearMonth.".xlsx");

		$file_name = "Emitidos_discriminado_moneda_".$yearMonth;

		$response->result = 2;
		$response->name = $file_name;
		return $response;
	}





	public function exportDeudoresExcel( $listClients ){ //$listClients array que tiene todos los clientes que tienen saldo en pesos o dolares

		$response = new \stdClass();

		$spreadsheet = new Spreadsheet();
		$excelSheet = $spreadsheet->getActiveSheet();
		$excelSheet->setTitle("Clientes");


		$numberFormat = new NumberFormat();
		$rut_format = $numberFormat::FORMAT_NUMBER;
		$price_format = $numberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

		$excelSheet->setCellValue('A1', "Documento");
		$excelSheet->setCellValue('B1', "Nombre");
		$excelSheet->setCellValue('C1', 'Saldo $');
		$excelSheet->setCellValue('D1', 'Saldo U$S');

		$indexRow = 2;

		foreach ($listClients as $client) {

			$excelSheet->setCellValue(('A'.$indexRow), $client['docReceptor']);
			$excelSheet->getStyle('A'.$indexRow)->getNumberFormat()->setFormatCode($rut_format);

			$excelSheet->setCellValue(('B'.$indexRow), $client['nombreReceptor']);
			$excelSheet->setCellValue(('C'.$indexRow), $client['saldoUYU']);
			$excelSheet->getStyle('C'.$indexRow)->getNumberFormat()->setFormatCode($price_format);

			$excelSheet->setCellValue(('D'.$indexRow), $client['saldoUSD']);
			$excelSheet->getStyle('D'.$indexRow)->getNumberFormat()->setFormatCode($price_format);


			$indexRow++;

		}


		$excelSheet->getColumnDimension('A')->setAutoSize(true);
		$excelSheet->getColumnDimension('B')->setAutoSize(true);
		$excelSheet->getColumnDimension('C')->setAutoSize(true);
		$excelSheet->getColumnDimension('D')->setAutoSize(true);


		$writer = new Xlsx($spreadsheet);
		$writer->save("excel/Clientes_con_saldo_".date("ym").".xlsx");

		$file_name = "Clientes_con_saldo_".date("ym");

		$response->result = 2;
		$response->name = $file_name;
		return $response;


	}





	public function exportDeudoresExcelProviders( $list, $dateTo ){ //$list array que tiene todos los proveedores que tienen saldo en pesos o dolares
		$response = new \stdClass();

		$spreadsheet = new Spreadsheet();
		$excelSheet = $spreadsheet->getActiveSheet();
		$excelSheet->setTitle("Proveedores");


		$numberFormat = new NumberFormat();
		$rut_format = $numberFormat::FORMAT_NUMBER;
		$price_format = $numberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

		$excelSheet->setCellValue('A1', "Rut");
		$excelSheet->setCellValue('B1', "Razón social");
		$excelSheet->setCellValue('C1', 'Saldo $');
		$excelSheet->setCellValue('D1', 'Saldo U$S');

		$indexRow = 2;

		foreach ($list as $prov) {

			$excelSheet->setCellValue(('A'.$indexRow), $prov['rut']);
			$excelSheet->getStyle('A'.$indexRow)->getNumberFormat()->setFormatCode($rut_format);

			$excelSheet->setCellValue(('B'.$indexRow), $prov['razonSocial']);
			$excelSheet->setCellValue(('C'.$indexRow), $prov['balance']->balanceUYU);
			$excelSheet->getStyle('C'.$indexRow)->getNumberFormat()->setFormatCode($price_format);

			$excelSheet->setCellValue(('D'.$indexRow), $prov['balance']->balanceUSD);
			$excelSheet->getStyle('D'.$indexRow)->getNumberFormat()->setFormatCode($price_format);


			$indexRow++;

		}


		$excelSheet->getColumnDimension('A')->setAutoSize(true);
		$excelSheet->getColumnDimension('B')->setAutoSize(true);
		$excelSheet->getColumnDimension('C')->setAutoSize(true);
		$excelSheet->getColumnDimension('D')->setAutoSize(true);


		$writer = new Xlsx($spreadsheet);
		$writer->save("excel/Proveedores_con_saldo_".$dateTo.".xlsx");

		$file_name = "Proveedores_con_saldo_".$dateTo;

		$response->result = 2;
		$response->name = $file_name;
		return $response;


	}

}