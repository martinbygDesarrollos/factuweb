<pre>
<?php
require_once '../src/config.php';
//CONEXIÓN BD
$connection = new mysqli(DB_HOST, DB_USR, DB_PASS, DB_DB) or die("No se puede conectar con la Base de Datos");
$connection->set_charset("utf8");

$timezone = "America/Montevideo";
date_default_timezone_set($timezone);

$timeTotal = 0;

if($connection){
	$time_start = microtime(true);
	$query = $connection->prepare("SELECT * FROM comprobantes");
	$query->execute();
	$result = $query->get_result();
	if(!$result) echo nl2br("No se encontró resultado SQL\n");
	$time_end = microtime(true);

    $time = sprintf("%01.3f",$time_end - $time_start);      echo "<h2>Emitidos    [OK] ".$time."</h2>";
	$timeTotal = $timeTotal + $time;

	$time_start = microtime(true);
	$query = $connection->prepare("SELECT * FROM clientes");
	$query->execute();
	$result = $query->get_result();
	if(!$result) echo nl2br("No se encontró resultado SQL\n");
	$time_end = microtime(true);

	$time = sprintf("%01.3f",$time_end - $time_start);      echo "<h2>Clientes    [OK] ".$time."</h2>";
	$timeTotal = $timeTotal + $time;

	$time_start = microtime(true);
	$query = $connection->prepare("SELECT * FROM comprobantes_recibidos");
	$query->execute();
	$result = $query->get_result();
	if(!$result) echo nl2br("No se encontró resultado SQL\n");
	$time_end = microtime(true);

	$time = sprintf("%01.3f",$time_end - $time_start);      echo "<h2>Recibidos   [OK] ".$time."</h2>";
	$timeTotal = $timeTotal + $time;

	$time_start = microtime(true);
	$query = $connection->prepare("SELECT * FROM proveedores");
	$query->execute();
	$result = $query->get_result();
	if(!$result) echo nl2br("No se encontró resultado SQL\n");
	$time_end = microtime(true);

	$time = sprintf("%01.3f",$time_end - $time_start);      echo "<h2>Proveedores [OK] ".$time."</h2><hr>";
	$timeTotal = $timeTotal + $time;

}

mysqli_close($connection);

$time_start = microtime(true);
file_get_contents('https://google.com');
$time_end = microtime(true);
$time = sprintf("%01.3f",$time_end - $time_start);          echo "<h2>Google      [OK] ".$time."</h2>";
$timeTotal = $timeTotal + $time;

$time_start = microtime(true);
file_get_contents('https://byg.efactura.com.uy/currency?Currency=USD&From='.date("Y-m-d").'&To='.date("Y-m-d"));
$time_end = microtime(true);
$time = sprintf("%01.3f",$time_end - $time_start);          echo "<h2>EFactura    [OK] ".$time."</h2>";
$timeTotal = $timeTotal + $time;

$time_start = microtime(true);
file_get_contents('https://ww3.byg.uy/index_files/style.css');
$time_end = microtime(true);
$time = sprintf("%01.3f",$time_end - $time_start);          echo "<h2>ByG         [OK] ".$time."</h2><hr>";
$timeTotal = $timeTotal + $time;

$timeTotal = sprintf("%01.0f",$timeTotal*1000);                  echo "<h2>Total       [OK] ".$timeTotal." ms</h2>";

exit;

?>