<?php
require_once '/homeX/sigecom/public_html/erp/src/config.php';
//ini_set('display_errors', 1);
set_time_limit ( -1 );

date_default_timezone_set('America/Montevideo');


echo nl2br("<pre> Inicio ".date('H:i:s')."\n");


//variables
$fecha_ini = "- 29 days";
$fecha_fin = "- 2 days";

$fechadesde = date('Ymd', strtotime($fecha_ini, strtotime(date('Ymd'))));
$fechahasta = date('Ymd', strtotime($fecha_fin, strtotime(date('Ymd'))));

$fechadesdeTexto = date('d/m/y', strtotime($fecha_ini, strtotime(date('Ymd'))));
$fechahastaTexto = date("d/m/y", strtotime($fecha_fin, strtotime(date('Ymd'))));

$idUser = 5;
$tokenRest = null;

$connection = new mysqli(DB_HOST, DB_USR, DB_PASS, DB_DB)or die("No se puede conectar con la Base de Datos");
$connection->set_charset("utf8");
if($connection){
	echo nl2br("Conexión base de datos\n");
	$query = $connection->prepare("SELECT tokenRest FROM `usuarios` WHERE idUsuario = ".$idUser);
	$query->execute();
	$result = $query->get_result();
	$tokenRest = $result->fetch_object()->tokenRest;
}
mysqli_close($connection);



$emailTo = "desarrollo@gargano.com.uy";
//$whatsappTo = "59899723666";

//$emailTo = 'byg.desarrollo1213@gmail.com';

$opciones = array('http' =>
    array(
        'method'  => 'GET',
        'header'  => array("Accept: aplication/json", "Authorization: Bearer " . $tokenRest),
        'timeout' => 3600,
    )
);
$contexto = stream_context_create($opciones);
$resultado = file_get_contents(URL_REST.'companies', false, $contexto);//rest companies
$resultCompanies = json_decode($resultado);
echo nl2br("Se obtienen los datos de ".count($resultCompanies)." empresas\n");

$arrayCompanies = array();
foreach( $resultCompanies as $value ){
	if ( $value->estado == 6 ){
		array_push($arrayCompanies, $value->rut);
	}
}

echo nl2br("Hay ".count($arrayCompanies)." empresas habilitadas\n");


$errores = "";
$contenido = "<table><theader><tr><td>RUT</td><td>Emisor</td><td>Receptor</td><td>Período</td><td>Estado</td><td>Fecha recepción</td></tr></theader>";
$contenido .= "<tbody>";
$filas = "";

foreach ($arrayCompanies as $rut) {


	$resultado = file_get_contents(URL_REST.'reports/status?Estado=ER&RUT='.$rut.'&Desde='.$fechadesde.'&Hasta='.$fechahasta, false, $contexto);
	$resultadoJson = json_decode($resultado);

	echo nl2br( "consulta enviada : $rut \n");


	if ( isset($resultadoJson) ){

		if ( $resultadoJson->resultado->codigo == 200 ){
			foreach ($resultadoJson->reportes as $key => $value) {
				$razonSocial = consultaRut($value->rut);
				$filas .= nuevaFilaTabla($value->rut, $razonSocial, $value->idEmisor, $value->idReceptor, $value->periodo, $value->estadoDescripcion, $value->fechaHoraRecepcion);
			}

		}else{

			$errores .= "Consulta enviada: reportes diarios empresa $rut con estado ER - En gestión, desde: $fechadesdeTexto hasta: $fechahastaTexto.
				Respuesta: código ".$resultadoJson->resultado->codigo." mensaje: ".$resultadoJson->resultado->error.".<br>";
		}


	}else{

		$errores .= "Empresa $rut : No se obtuvo respuesta de ORMEN al consultar reportes diarios para todas las empresas con estado ER - En gestión, desde: $fechadesdeTexto hasta: $fechahastaTexto.<br>";

	}



}


$contenido .= $filas;
$contenido .= "</tbody></table>";



function nuevaFilaTabla( $rut, $razonSocial, $idEmisor, $idReceptor, $periodo, $estadoDescripcion, $fechaHoraRecepcion ){

	return "<tr><td>$rut<br>$razonSocial</td><td>$idEmisor</td><td>$idReceptor</td><td>$periodo</td><td>$estadoDescripcion</td><td>$fechaHoraRecepcion</td></tr>";


}

function consultaRut($rut){
	$responseRut = file_get_contents("https://genaro.uy/emisores/?RUC=$rut");
	if (isset($responseRut)) {
		$rutJson = json_decode($responseRut);
		if (isset($rutJson->DENOMINACION)) {
			return $rutJson->DENOMINACION;
		}
	}
	return null;
}


$header  = 'MIME-Version: 1.0' . "\r\n";
$header .= 'Content-type:text/html; charset=UTF-8' . "\r\n";


$mensaje = '<html>
    <head>
    <style>
    table, td, th {
	  border: 1px solid #ddd;
	  text-align: left;
	}

	table {
	  border-collapse: collapse;
	  width: 100%;
	  font-size:16px;
	}

	th, td {
	  padding: 5px;
	}
    </style>
    </head
    <body><h2 style="font-size:25px; text-align: center;">Reportes diarios '.$fechadesdeTexto.' '.$fechahastaTexto.' </h2>
    <p style="font-size:18px;">Se consultaron '.count($arrayCompanies).' empresas, resultado: </p><br><hr><br>
    <div align="center">'. $contenido . '</div><br><hr><br><div align="left">'. $errores . '</div></body></html>';


$resultSendMail = mail( $emailTo, "Reportes diarios Empresas ".$fechadesdeTexto, $mensaje, $header);
echo $resultSendMail;
echo nl2br("\nEmail enviado\n");
echo nl2br("Fin ".date('H:i:s')."\n");
exit;
?>
