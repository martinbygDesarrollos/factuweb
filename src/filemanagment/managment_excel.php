<?php

require_once URL_UTILS . "PHPExcel/Classes/PHPExcel.php";

class managment_excel{

	public function createExcelFeeService($listService){
		$objPHPExcel = new PHPExcel();
		PHPExcel_Settings::setLocale('es_es');
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'Documento')
		->setCellValue('B1', 'Nombre')
		->setCellValue('C1', 'Servicio')
		->setCellValue('D1', 'Descripción')
		->setCellValue('E1', 'Período')
		->setCellValue('F1', 'Moneda')
		->setCellValue('G1', 'IVA')
		->setCellValue('H1', 'Costo')
		->setCellValue('I1', 'Importe')
		->setCellValue('J1', 'UI')
		->setCellValue('K1', 'Estado')
		->setCellValue('L1', 'Fecha')
		->getStyle('A:L')
		->getAlignment()
		->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$index = 1;

		foreach ($listService as $key => $row) {
			$index++;

			$objPHPExcel->getActiveSheet()->setCellValueExplicit('A'. $index, $row['DOCUMENTO'] ,PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$index, $row['NOMBRE']);
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$index, $row['SERVICIO']);
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$index, $row['DESCRIPCION']);
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$index, $row['PERIODO']);
			$objPHPExcel->getActiveSheet()->setCellValue('F'.$index, $row['MONEDA']);
			$objPHPExcel->getActiveSheet()->setCellValue('G'.$index, $row['IVA']);
			$objPHPExcel->getActiveSheet()->setCellValue('H'.$index, $row['COSTO']);
			$objPHPExcel->getActiveSheet()->setCellValue('I'.$index, $row['IMPORTE']);
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$index, $row['UI']);
			$objPHPExcel->getActiveSheet()->setCellValue('k'.$index, $row['ESTADO']);
			$objPHPExcel->getActiveSheet()->setCellValue('l'.$index, $row['FECHA']);
		}

		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);

		$objPHPExcel->getActiveSheet()->setTitle('Cuotas por servicios');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$location = dirname(dirname(__DIR__)) . "/public/pdfs/";
		$objWriter->save($location . "excel.xlsx");

		$b64Doc = chunk_split(base64_encode(file_get_contents($location . "excel.xlsx")));
		return $b64Doc;
	}
}