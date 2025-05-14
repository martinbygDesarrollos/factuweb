<?php
require_once 'handle_date_time.php';

class dbf_management{

    public function importProductsBatch($currentSession, $isLiteralE, $uploadedFilePath, $offset = 0, $limit = 500){
        set_time_limit(60); // 1 minuto por lote
        
        $products = array();
        $response = new \stdClass();
        $db = $uploadedFilePath;
        
        // Recuperar o inicializar datos de tracking
        // session_start();
        if ($offset == 0) {
            $_SESSION['import_tracking'] = [
                'processedBarcodes' => [],
                'processedDescriptions' => []
            ];
        }
        
        $processedBarcodes = &$_SESSION['import_tracking']['processedBarcodes'];
        $processedDescriptions = &$_SESSION['import_tracking']['processedDescriptions'];
        
        $fdbf = fopen($db,'r');
        if (!$fdbf) {
            $response->result = 1;
            $response->message = "Error al abrir el archivo";
            return $response;
        }
        
        $fields = array();
        $buf = fread($fdbf,32);
        $header=unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
        
        // Si es la primera vez, leer la estructura
        if ($offset == 0) {
            $goon = true;
            $unpackString='';
            while ($goon && !feof($fdbf)) {
                $buf = fread($fdbf,32);
                if (substr($buf,0,1)==chr(13)) {
                    $goon=false;
                }else {
                    $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
                    $unpackString.="A$field[fieldlen]$field[fieldname]/";
                    array_push($fields, $field);
                }
            }
            $_SESSION['import_tracking']['unpackString'] = $unpackString;
        } else {
            $unpackString = $_SESSION['import_tracking']['unpackString'];
        }
        
        // Calcular posiciones
        $startPos = $header['FirstRecord'] + ($offset * $header['RecordLength']);
        $endPos = min($offset + $limit, $header['RecordCount']);
        
        fseek($fdbf, $startPos);
        
        for ($i = $offset + 1; $i <= $endPos; $i++) {
            $buf = fread($fdbf, $header['RecordLength']);
            
            $deletedRow = substr($buf, 0, 1);
            $buf = substr($buf, 1);
            $row = unpack($unpackString, $buf);
            
            // Verificar si el registro está eliminado
            if ($deletedRow != chr(0x2A)) {
                $codebar = trim($row['CODEBAR']);
                $description = mb_convert_encoding(trim($row['DESC']), 'UTF-8', 'CP850');
                
                // Verificar duplicados
                if (!in_array($codebar, $processedBarcodes) && !in_array($description, $processedDescriptions)) {
                    // Verificar EAN
                    if ($this->isValidEAN($codebar)) {
                        $processedBarcodes[] = $codebar;
                        $processedDescriptions[] = $description;
                        
                        $product = new \stdClass();
                        $product->idIva = $this->getIva(trim($row['IVA']), $isLiteralE);
                        $product->percentageIva = floatval(trim($row['IVA']));
                        $product->costo = floatval(trim($row['COSTO']));
                        $product->coeficiente = floatval(trim($row['COEF']));
                        $product->descripcion = $description;
                        $product->marca = mb_convert_encoding(trim($row['MARCA']), 'UTF-8', 'CP850');
                        $product->codigoBarra = $codebar;
                        $product->detalle = mb_convert_encoding(trim($row['OBS']), 'UTF-8', 'CP850');
                        $product->moneda = "UYU";
                        $product->descuento = 0.00;
                        
                        // CALCULO EL IMPORTE
                        $multiplier = 1 + (abs($product->coeficiente) / 100);
                        if ($product->coeficiente < 0) {
                            $multiplier = 1 - (abs($product->coeficiente) / 100);
                        }
                        
                        $costWithCoeff = $product->costo * $multiplier;
                        $importe = round(($costWithCoeff * (1 + $product->percentageIva / 100)), 2);
                        $product->importe = $importe;
                        
                        $products[] = $product;
                    }
                }
            }
        }
        
        fclose($fdbf);
        
        $response->products = $products;
        $response->result = 2;
        $response->offset = $endPos;
        $response->total = $header['RecordCount'];
        $response->processed = $endPos;
        $response->isComplete = ($endPos >= $header['RecordCount']);
        
        // Limpiar tracking si se completó
        if ($response->isComplete) {
            unset($_SESSION['import_tracking']);
        }
        
        return $response;
    }

    public function importProducts($currentSession, $isLiteralE, $uploadedFilePath){
        $products = array();
		$response = new \stdClass();
        $db = $uploadedFilePath;

        // Arrays para llevar el seguimiento de códigos de barra y descripciones ya procesados
        $processedBarcodes = array();
        $processedDescriptions = array();
    
        $fdbf = fopen($db,'r');
        if (!$fdbf) echo "error";
        $fields = array();
        $buf = fread($fdbf,32);
        $header=unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
        $goon = true;
        $unpackString='';
        while ($goon && !feof($fdbf)) {
            $buf = fread($fdbf,32);
            if (substr($buf,0,1)==chr(13)) {
                $goon=false;
            }else {
                $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
                $unpackString.="A$field[fieldlen]$field[fieldname]/";
                array_push($fields, $field);
            }
        }

        fseek($fdbf, $header['FirstRecord']);
        for ($i=1; $i<=$header['RecordCount']; $i++) {
            $buf = fread($fdbf,$header['RecordLength']);

            $deletedRow = substr($buf, 0, 1);
            $buf = substr($buf, 1);
            $row = unpack($unpackString, $buf);
        
            // Verificar si el registro está eliminado
            if ($deletedRow != chr(0x2A)) { // Si no está marcado como eliminado

                $codebar = trim($row['CODEBAR']);
                // $description = trim($row['DESC']);
                $description = mb_convert_encoding(trim($row['DESC']), 'UTF-8', 'CP850');

                // Verificar si ya hemos procesado este código de barras o descripción
                if (in_array($codebar, $processedBarcodes) || in_array($description, $processedDescriptions)) {
                    // Si ya se procesó este producto, saltarlo
                    continue;
                }
                
                // Verificar si el código de barras tiene formato EAN-8 o EAN-13
                if ($this->isValidEAN($codebar)) {
                    $product = new \stdClass();
                    // $client->nombre = "SIN NOMBRE";
                    // $client->nombreSocio = "SIN NOMBRE";
                    $product->idIva = $this->getIva(trim($row['IVA']), $isLiteralE);
                    $product->percentageIva = floatval(trim($row['IVA']));
                    $product->costo = floatval(trim($row['COSTO']));
                    $product->coeficiente = floatval(trim($row['COEF']));
                    $product->descripcion = $description;
                    $product->marca = trim($row['MARCA']);
                    $product->codigoBarra = $codebar;
                    $product->detalle = trim($row['OBS']);
                    $product->moneda = "UYU";
                    $product->descuento = 0.00;

                    // CALCULO EL IMPORTE
                    $isNegative = ($product->coeficiente < 0);

                    // Transformar el coeficiente a multiplicador (si es 50, el multiplicador es 1.50)
                    $multiplier = 1 + (abs($product->coeficiente) / 100);

                    // Si el coeficiente es negativo, interpretamos que es un descuento
                    if ($isNegative) {
                        $multiplier = 1 - (abs($product->coeficiente) / 100);
                    }

                    // Calcular el costo con el coeficiente aplicado
                    $costWithCoeff = $product->costo * $multiplier; 

                    // Precio final con IVA
                    $importe = round(($costWithCoeff * ( 1 + $product->percentageIva / 100)), 2);

                    $product->importe = $importe;

                    $products[] = $product;
                }
            } else { // registro eliminado, ignorar

            }
        }
        // var_dump($products); exit;
        $response->products = $products;
		$response->result = 2;
		return $response;
    }

    private function getIva($iva, $isLiteralE){
        if ($isLiteralE) { return 16; }
        switch ($iva) {
            case '22.00':
                return 3;
                break;
            
            case '10.00':
                return 2;
                break;
            
            case '00.00':
                return 1;
                break;
            
            default:
                # code...
                break;
        }
    }

    private function isValidEAN($barcode) {
        // Eliminar cualquier espacio en blanco
        $barcode = trim(str_replace(' ', '', $barcode));
        
        // Verificar si está vacío
        if (empty($barcode)) {
            return false;
        }
        
        // Verificar si es numérico
        if (!ctype_digit($barcode)) {
            return false;
        }
        
        // Verificar si tiene la longitud de un EAN-8 o EAN-13
        $length = strlen($barcode);
        if ($length != 8 && $length != 13) {
            return false;
        }
        
        // Verificar el dígito de control (último dígito)
        $checkDigit = substr($barcode, -1);
        $barcode = substr($barcode, 0, -1);
        
        // Algoritmo para calcular el dígito de control
        $sum = 0;
        $position = 1;
        
        // Recorrer los dígitos en orden inverso (de derecha a izquierda)
        for ($i = strlen($barcode) - 1; $i >= 0; $i--) {
            $digit = (int)$barcode[$i];
            
            // Para EAN-8 y EAN-13, los dígitos en posición impar se multiplican por 3
            if ($position % 2 == 0) {
                $sum += $digit;
            } else {
                $sum += $digit * 3;
            }
            
            $position++;
        }
        
        // Calcular el dígito de control esperado
        $expectedCheckDigit = (10 - ($sum % 10)) % 10;
        
        // Comparar el dígito de control calculado con el proporcionado
        return (int)$checkDigit === $expectedCheckDigit;
    }
}