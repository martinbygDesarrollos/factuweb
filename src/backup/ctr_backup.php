<?php

class ctr_backup{

	function __construct() {
		if(!file_exists(PATH_BACKUP)){
			mkdir(PATH_BACKUP, 0777, true);
			mkdir(PATH_BACKUP_TEMPFILES, 0777, true);
			mkdir(PATH_BACKUP_RESTOREFILES, 0777, true);
		}
	}
	
	public function exportRestorePoint(){
		$response = new \stdClass();

		$responseGetListTables = DataBase::getDataBaseNameTable();
		if($responseGetListTables->result == 2){
			$currentTime = handleDateTime::getCurrentDateTimeInt();
			foreach ($responseGetListTables->listResult as $key => $table) {
				$file = fopen(PATH_BACKUP_TEMPFILES . 'BackUp-' . DB_DB . "-" . "TABLE-" . $table . '.json', 'w');
				$responseQuery = DataBase::sendQuery("SELECT * FROM " . $table, null, "LIST");
				if($responseQuery->result == 2)
					fwrite($file, json_encode($responseQuery->listResult));
				fclose($file);
			}

			$newZip = new ZipArchive();
			$name = "backUp_" . $currentTime. ".zip";
			$fileName = PATH_BACKUP . $name;
			if($newZip->open($fileName, ZipArchive::CREATE ) === true){
				$listFiles = array_diff(scandir(PATH_BACKUP_TEMPFILES), array('..', '.'));
				if(sizeof($listFiles) > 0){
					foreach ($listFiles as $key => $tempFile)
						$newZip->addfile(PATH_BACKUP_TEMPFILES . $tempFile, $tempFile);

					$response->result = 2;
					$response->message = "Se creo el archivo de respaldo correspondiente a la fecha ". handleDateTime::setFormatBarDateTime($currentTime) . " sobre " . $newZip->numFiles . " tablas con datos.";
					$newZip->close();
					$response->fileBase64 = base64_encode(file_get_contents($fileName));
					$response->fileName = $name;
					unlink($fileName);
				}else{
					$response->result = 0;
					$response->message = "No se generaron archivos para respaldar.";
				}
			}else{
				$response->result = 0;
				$response->message = "El archivo de respaldo no fue creado por un error al crear el zip.";
			}
			ctr_backup::clearFolder(PATH_BACKUP_TEMPFILES);
		}else return $responseGetListTables;

		return $response;
	}

	public function importRestorePoint($dataFile){
		$response = new \stdClass();

		DataBase::importDataBase();
		DataBase::clearTables();

		ctr_backup::clearFolder(PATH_BACKUP_RESTOREFILES);
		if(!is_null($dataFile)){
			$zip_Array = explode(";base64,", $dataFile);
			$zip_contents = base64_decode($zip_Array[1]);
			$file = PATH_BACKUP_RESTOREFILES . "restore.zip";
			file_put_contents($file, $zip_contents);

			$zip = new ZipArchive();
			$descompressFile = $zip->open($file);
			if($descompressFile === TRUE){
				$zip->extractTo(PATH_BACKUP_RESTOREFILES);
				$zip->close();
				unlink($file);
			}

			$listFilesToRestore = array_diff(scandir(PATH_BACKUP_RESTOREFILES), array('..', '.'));
			if(sizeof($listFilesToRestore) > 0){
				foreach ($listFilesToRestore as $key => $fileToRestore) {
					$contentFile = file_get_contents(PATH_BACKUP_RESTOREFILES . $fileToRestore);
					$contentUTF = utf8_encode($contentFile);
					$jsonContent = json_decode($contentUTF, true);
					if(!is_null($jsonContent)){
						$arrayFileName =explode("-", $fileToRestore);
						$arrayNameExtension = explode(".", $arrayFileName[3]);
						$nameTable = $arrayNameExtension[0];
						$tempFileQuerys = fopen(PATH_BACKUP_RESTOREFILES . $nameTable . '.sql', 'w');
						//CAMPOS DE CADA TABLA
						$listAttr = array_keys($jsonContent[0]);
						$stringQuery = "INSERT INTO `" . $nameTable . "` (";
						foreach ($listAttr as $key => $attrib) {
							$stringQuery .= "`" .$attrib . "`,";
						}
						$stringQuery = substr($stringQuery, 0, strlen($stringQuery) - 1) . ") VALUES ".chr(13).chr(10);
						//---------------------------------------

						// CARGAR TODOS LOS VALUES DE LA CONSULTA SQL PARA LA TABLA
						foreach ($jsonContent as $key => $value) {
							$stringQuery .= "(";
							foreach ($listAttr as $key => $attrib){
								if(ctype_digit($value[$attrib]) || is_numeric($value[$attrib]))
									$stringQuery .= $value[$attrib] . ",";
								else{
									if(strlen($value[$attrib]) > 0)
										$stringQuery .= "'" . $value[$attrib] . "',";
									else
										$stringQuery .= 'NULL' . ",";
								}
							}
							$stringQuery = $stringQuery = substr($stringQuery, 0, strlen($stringQuery) - 1) . ")," .chr(13).chr(10);
						}
						$stringQuery = $stringQuery = substr($stringQuery, 0, strlen($stringQuery) - 3) . ";".chr(13).chr(10);
						//----------------------------------------------------------------

						fwrite($tempFileQuerys, $stringQuery);
						fclose($tempFileQuerys);
					}
					unlink(PATH_BACKUP_RESTOREFILES . $fileToRestore);
				}
				$response->result = 2;
				$response->message = "El respaldo ingresado esta correcto.";
				$response->listOrder = ORDER_TO_INSERT;


			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error al descomprimir el archivo, por favor vuelva a cargarlo.";
			}
		}else{
			$response->result = 0;
			$response->message = "Debe ingresar un archivo .zip para comenzar la restauración del sistema.";
		}

		return $response;
	}

	function importContentTable($order){
		$response = new \stdClass();

		$nameFile = PATH_BACKUP_RESTOREFILES . $order . ".sql";
		if(file_exists($nameFile)){
			$contentFile = file_get_contents($nameFile);
			$responseInsert = DataBase::sendQuery($contentFile, null, "BOOLE");
			if($responseInsert->result == 2){
				$response->result = 2;
				$response->message = "fue insertado correcto";
			}else return $responseInsert;
		}else{
			$response->result = 1;
			$response->message = "Esta tabla no contiene registros";
		}

		return $response;
	}

	public function clearFolder($pathFolder){
		$listFiles = array_diff(scandir($pathFolder), array('..', '.'));
		if(sizeof($listFiles) > 0){
			foreach ($listFiles as $key => $tempFile){
				if(is_dir($pathFolder . $tempFile))
					rmdir($pathFolder . $tempFile);
				else
					unlink($pathFolder . $tempFile);
			}
		}
	}
}