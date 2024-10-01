<?php
include_once "../src/config.php";
if (class_exists('DataBase'))
	return;

class DataBase {
	public static function connection(){
		static $connection = null;
		if (null === $connection) {
			$connection = new mysqli(DB_HOST, DB_USR, DB_PASS, DB_DB)
			or die("No se puede conectar con la Base de Datos");
		}
		$connection->set_charset("utf8");
		return $connection;
	}

	/*
	* 	VL:
	*	$sql hace referencia a la consulta que llega ej ("SELECT * FROM usuarios AS U, empresas AS E WHERE U.idEmpresa = E.idEmpresa AND U.idUsuario = ?")
	*	$params vienen datos en esta variable si en la consulta se usan ? de lo contrario no tenemos datos en $params
	*	en los parametros tenemos en el primer indice una cadena que indica que valores se usan en la consulta en el ejemplo siguiente tenemos string, string, ... , integer, integer, y luego se obtienen los datos *	 para sustituir en la consulta
	*	ejemplo de $params array('sssiisssi', $rut, $nameBusiness, $typeEmtity, $dateInit, $idStreet, $address, $town, $location, $postalCode)
	*	$tipoRetorno este parametro hace referencia a lo que se espera que devuelva la consulta a la base, puede ser de tipo LIST, OBJECT o BOOLE
	*/
	public function sendQuery($sql, $params, $tipoRetorno){
		$response = new \stdClass();

		$connection = DataBase::connection(); 	//se hace la conexiòn a la base de datos
		if($connection){	//obtenemos la conexiòn, en caso de que haya podido realizar correctamente. En caso contrario obtenemos un null.
			$query = $connection->prepare($sql);

			$paramsTemp = array();
			if($params){
				foreach($params as $key => $value)
					$paramsTemp[$key] = &$params[$key];

				call_user_func_array(array($query, 'bind_param'), $paramsTemp);
			}

			if($query->execute()){
				$result = $query->get_result();

				if($tipoRetorno == "LIST"){
					/*
					*	En result viene una lista (varias filas con datos) la respuesta de la consulta sql, se recorre por completo y se devuelve el array $arrayResult
					*	EJEMPLO $sql = "SELECT * FROM indicadores_facturacion"
					*/
					$arrayResult = array();
					while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
						$arrayResult[] = $row;
					}
					if(sizeof($arrayResult) > 0){
						//en caso de que estè todo bien
						$response->result = 2;
						$response->listResult = $arrayResult;
					}else $response->result = 1;
				}else if($tipoRetorno == "OBJECT") {

					/*
					*	En result viene una sola linea de respuestas con datos y es lo que se devuelve
					*	EJEMPLO $sql = "SELECT * FROM usuarios AS U, empresas AS E WHERE U.idEmpresa = E.idEmpresa AND U.idUsuario = 1"
					*/
					$objectResult = $result->fetch_object();
					if(!is_null($objectResult)){
						$response->result = 2;
						$response->objectResult = $objectResult;
					}else $response->result = 1; //en caso de que no se devuelvan datos
				}else if($tipoRetorno == "BOOLE"){

					/*
					*	Este tipo se usa ganaralmente para las consultas de insert into, update o delete
					*/

					$response->result = 2;
					$response->id = $connection->insert_id;
				}
			}else{
				$response->result = 0;
				if(strpos($query->error, "Duplicate") !== false){
					$msjError = $query->error;
					$msjError = str_replace("Duplicate entry", "BASE DE DATOS: El valor ", $msjError);
					$msjError = str_replace(" for key", " ya fue ingresado previamente para el campo ", $msjError);
					$response->message = $msjError . "(dato único)";
				}else if(strpos($query->error, "Column") !== false){
					$msjError = $query->error;
					$msjError = str_replace("Column", "BASE DE DATOS: La columna", $msjError);
					$msjError = str_replace("cannot be", "no puede ser", $msjError);
					$response->message = $msjError;
				}else{
					$response->message = "BASE DE DATOS: " . $query->error;
				}
			}
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y no se pudo acceder a la base de datos del sistema.";
		}
		return $response;
	}

	public function getDataBaseNameTable(){
		$responseQuery = DataBase::sendQuery('SHOW TABLES', null, "LIST");
		if($responseQuery->result == 2){
			$arrayResult = array();
			foreach ($responseQuery->listResult as $key => $table) {
				$arrayResult[] = $table['Tables_in_' . DB_DB];
			}
			$responseQuery->listResult = $arrayResult;
		}else if($responseQuery->result == 1) $responseQuery->message = "Las tablas de la base de datos no fueron genereadas.";

		return $responseQuery;
	}

	public function importDataBase(){
		$responseQuery = DataBase::getDataBaseNameTable();
		if($responseQuery->result != 2){
			$filename = dirname(dirname(__DIR__)) . "/src/connection/sigecom.sql";
			$contentQuerys = file_get_contents($filename);
			return DataBase::connection()->multi_query($contentQuerys);
		}
	}

	public function clearTables(){
		$responseQuery = DataBase::getDataBaseNameTable();
		if($responseQuery->result == 2){
			foreach ($responseQuery->listResult as $key => $value)
				DataBase::sendQuery("DELETE FROM " . $value, null, "BOOLE");
		}
	}
}
