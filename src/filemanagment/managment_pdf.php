<?php

require_once URL_UTILS . 'pdf/fpdf.php';

class managment_pdf{

	public function generateAccountState($accountState, $addressee, $dateInit, $dateEnding, $prepareFor, $myBusiness){
		$pdfFile = managment_pdf::createWithHeader($accountState['MAINCOIN'], $myBusiness->nombre);

		$pdfFile->SetFont('Arial', "B", 12);
		if($prepareFor == "CLIENT")
			$pdfFile->Cell(10,10, "Cliente: [" . $addressee->docReceptor . "] ". $addressee->nombreReceptor);
		else if($prepareFor == "PROVIDER")
			$pdfFile->Cell(10,10, "Proveedor: [" .  $addressee->rut . "] " . $addressee->razonSocial);
		$pdfFile->Ln();
		$pdfFile->SetFont('Courier', "B", 12);
		$pdfFile->Cell(195,10, "Desde " . $dateInit . " hasta " . $dateEnding,0,0,"R");
		$pdfFile->Ln();
		$pdfFile = managment_pdf::insertTable($accountState['listResult'], $pdfFile);
		$pdfFile->Ln();
		$pdfFile->SetFont('Courier', "I", 12);

		$coin = '$';
		if($accountState['MAINCOIN'] == "USD") $coin = 'U$S';
		$pdfFile->Cell(195,10,"Total al " . $accountState['DATEENDING'] . " " . $coin . "    ". $accountState['SALDOTOTAL'],0,0,"R");
		$pdfFile->Ln();
		$pdfFile->Ln();
		if(isset($accountState['BALANCEUYU']) && isset($accountState['BALANCEUSD'])){
			$pdfFile->Cell(195,10,'Saldo a la fecha  $     ' . $accountState['BALANCEUYU'],0,0,"R");
			$pdfFile->Ln();
			$pdfFile->Cell(195,10,'Saldo a la fecha U$S    ' . $accountState['BALANCEUSD'],0,0,"R");
		}
		$pdfFile->Output("F","pdfs/accountState_" . $myBusiness->idEmpresa . ".pdf");
		$b64Doc = chunk_split(base64_encode(file_get_contents('../public/pdfs/accountState_' . $myBusiness->idEmpresa . '.pdf')));
		return $b64Doc;
	}

	public function createWithHeader($typeCoin, $nameBusiness){
		$file = new FPDF('P','mm','A4');
		$file->AddPage();

		$file->SetFont("Arial", "B", 16);
		$file->Cell(195,10, utf8_decode($nameBusiness),"B");
		$file->Ln();
		$file->SetFont('Courier', "I", 14);


		if($typeCoin == "UYU") $file->Cell(10,10, "ESTADO DE CUENTA EN Pesos Uruguayos");
		else $file->Cell(10,10, utf8_decode("ESTADO DE CUENTA EN Dólares"));
		$file->Ln();


		return $file;
	}

	public function insertTable($listResult, $pdfFile){
		$header = array_keys($listResult[0]);

		$pdfFile->SetLineWidth(.3);

		$pdfFile->SetFont("Courier", "I", "12");
		$pdfFile->Ln();

		$rowWidth = array(25,65,0,35,35,35);

		for($i = 0; $i < sizeof($header); $i ++) {
			if($header[$i] != "MONEDA" && $header[$i] != "INTDEBE" && $header[$i] != "INTHABER" && $header[$i] != "INTSALDO")
				$pdfFile->Cell($rowWidth[$i],6, utf8_decode($header[$i]),"TB", 0, "R");
		}
		$pdfFile->Ln();

		foreach ($listResult as $key => $value) {
			for($i = 0; $i < sizeof($header); $i ++ ){
				if($header[$i] != "MONEDA"){
					if ($header[$i] != "INTDEBE" && $header[$i] != "INTHABER" && $header[$i] != "INTSALDO"){
						if($header[$i] != "DOCUMENTO" && $value[$header[$i]] == 0){
							$pdfFile->Cell($rowWidth[$i],6, " ",0,0,"R");
						}else {
							$pdfFile->Cell($rowWidth[$i],6, utf8_decode($value[$header[$i]]),0,0,"R");
						}
					}
				}
			}
			$pdfFile->Ln();
		}
		return $pdfFile;
	}

	public function generateFeeServiceFile($listService){
		$file = new FPDF('P','mm','A4');
		$file->AddPage();

		$file->SetFont("Arial", "B", 16);
		$file->Cell(195,10, utf8_decode("Lista de cuotas por servicio"),"B");
		$file->Ln();
		$file->SetFont('Courier', "I", 14);

		$file->SetLineWidth(.1);
		$file->SetFont('Courier', "I", 8);
		$file->Ln();

		$rowWidth = array(25, 40, 22, 15, 6, 16, 16, 15, 13, 24);
		$header = array_keys($listService[0]);

		for($i = 0; $i < sizeof($header); $i ++) {
			if($header[$i] != "MONEDA")
				$file->Cell($rowWidth[$i],6, utf8_decode($header[$i]),"TB", 0, "R");
			else
				$file->Cell($rowWidth[$i],6, " ","TB", 0, "R");
		}
		$file->Ln();

		foreach ($listService as $key => $value) {
			for($i = 0; $i < sizeof($header); $i ++ ){
				$file->Cell($rowWidth[$i],6, utf8_decode($value[$header[$i]]),0,0,"R");
			}
			$file->Ln();
		}

		$file->Ln();
		$file->SetFont('Courier', "I", 12);
		$file->Output("F","pdfs/cuotasPorServicio.pdf");
		$b64Doc = chunk_split(base64_encode(file_get_contents('../public/pdfs/cuotasPorServicio.pdf')));
		return $b64Doc;
	}
}