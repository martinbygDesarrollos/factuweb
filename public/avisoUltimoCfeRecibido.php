<?php
ini_set('display_errors', 1);

//variables
set_time_limit ( 600 );
define("URL_REST", "https://efactura.com.uy:64431/efactura/");

define("DB_HOST", "127.0.0.1");
define("DB_USR", "sigecom_adminefactura");
define("DB_PASS", 'LOHng9CBwidDk93d');
define("DB_DB", "sigecom_efactura");

define("URL_UTILS", '/home7/sigecom/utils/');

echo nl2br("<pre> Inicio ".date('H:i:s')."\n");

$now = "";
$ultimaFecha = "0";
$companies = null;
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
$arrayCompanies = array(); //todos los ruts
$emailTo = "guillermo@gargano.com.uy";
$whatsappTo = "59899723666";

$opciones = array('http' =>
    array(
        'method'  => 'GET',
        'header'  => array("Accept: aplication/json", "Authorization: Bearer " . $tokenRest),
    )
);
$contexto = stream_context_create($opciones);
$resultado = file_get_contents(URL_REST.'companies', false, $contexto);//rest companies
$resultCompanies = json_decode($resultado);
echo nl2br("Se obtienen los datos de ".count($resultCompanies)." empresas\n");
foreach( $resultCompanies as $value ){
	if ( $value->estado == 6 ){
		array_push($arrayCompanies, $value->rut);
	}
}
foreach ($arrayCompanies as $rut) {
	//echo nl2br("Buscar comprobantes de la empresa ".$rut."\n");
	$resultadoCfe = file_get_contents(URL_REST.'company/'.$rut.'/cfe/recibidos?PageSize=1', false, $contexto);
	$resultadoCfeJson = json_decode($resultadoCfe);//esto sería un array
	if ( count($resultadoCfeJson)>0 ){
		//echo nl2br("El último cfe de ".$rut." tiene fecha ".$resultadoCfeJson[0]->emision."\n");
		//var_dump($resultadoCfeJson[0]->emision > $ultimaFecha);exit;
		if ($resultadoCfeJson[0]->emision > $ultimaFecha) {
			$ultimaFecha = $resultadoCfeJson[0]->emision;
		}
	}
}
echo nl2br("\nSe recorrieron ".count($arrayCompanies)." empresas ".date('H:i:s')."\n\n");
date_default_timezone_set('America/Montevideo');

$hours8  = date('YmdHis', strtotime("- 8 hours" , strtotime(date('YmdHis'))));
$hours12 = date('YmdHis', strtotime("- 12 hours", strtotime(date('YmdHis'))));
$hours24 = date('YmdHis', strtotime("- 24 hours", strtotime(date('YmdHis'))));
$hours36 = date('YmdHis', strtotime("- 36 hours", strtotime(date('YmdHis'))));

if ( $ultimaFecha > 0 ){

	$ultimaFechaFormato = substr($ultimaFecha, 6, 2)."/".substr($ultimaFecha, 4, 2)."/".substr($ultimaFecha, 0, 4)."  ".substr($ultimaFecha, 8, 2).":".substr($ultimaFecha, 10, 2).":".substr($ultimaFecha, 12, 2);
	echo nl2br("La fecha del último comprobante recibido ".$ultimaFechaFormato."\n");
	$message = "Fecha del último comprobante de proveedor recibido: ".$ultimaFechaFormato.".\n\n";
	file_put_contents('/home7/sigecom/public_html/erp/public/ultcfe.txt',$ultimaFechaFormato);

	if ( $ultimaFecha <= $hours8 && $ultimaFecha > $hours12){
		//enviar mail
		echo nl2br("Hace más de 8 horas no se reciben comprobantes.\n");
		$message .= "Hace más de 8 horas no se reciben comprobantes.\n";
		$resultSendMail = mail( $emailTo, $ultimaFechaFormato." último cfe de compras recibido.", $message);
		echo nl2br("Se envió mail, con resultado ".$resultSendMail."\n");
		return $resultSendMail;
	}else if ( $ultimaFecha <= $hours12 && $ultimaFecha > $hours24){
		//enviar mail
		echo nl2br("Hace más de 12 horas no se reciben comprobantes.\n");
		$message .= "Hace más de 12 horas no se reciben comprobantes.\n";
		$resultSendMail = mail( $emailTo,  $ultimaFechaFormato." último cfe de compras recibido." , $message);
		echo nl2br("Se envió mail, con resultado ".$resultSendMail."\n");
		return $resultSendMail;
	}else if ( $ultimaFecha <= $hours24 ){
		echo nl2br("Hace más de 24 horas no se reciben comprobantes.\n");

		$message .= "Hace más de 24 horas no se reciben comprobantes.\n";
		$resultSendMail = mail( $emailTo,  $ultimaFechaFormato." último cfe de compras recibido." , $message);
		echo nl2br("Se envió mail, con resultado ".$resultSendMail."\n");

		$message = $ultimaFechaFormato." último cfe de compras recibido. ";
		$message .= "Hace más de 24 horas no se reciben comprobantes.";
		$urlMessage = 'https://api.chat-api.com/instance312895/message?token=45ek2wrhgr3rg33m';
		$jsonMessage = '{
			"body": "'.$message.'",
			"phone": '.$whatsappTo.'
		}';
		$opcionesMessage = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => $jsonMessage
			)
		);

		$contextMessage = stream_context_create($opcionesMessage);
		file_get_contents($urlMessage, false, $contextMessage);
		echo nl2br("Se envió whatsapp\n");
		return;
	}
}else{
	echo nl2br("Saliendo. La última fecha encontrada es ".$ultimaFecha."\n");
	exit;
}
exit;
?>
