<?php

require_once '../src/utils/handle_date_time.php';
require_once '../src/connection/open_connection.php';

class users{
	//UPDATED
	public function setUpdatedDetails($idBusiness){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("UPDATE empresas SET detallesObtenidos = ? WHERE idEmpresa = ?", array('ii', 1, $idBusiness), "BOOLE");
	}
	//UPDATED
	public function itsSuperUser($userEmail){
		$response = new \stdClass();
		$dbClass = new DataBase();

		$responseQuery = $dbClass->sendQuery("SELECT * FROM super_usuario WHERE correo = ?", array('s', $userEmail), "OBJECT");
		if($responseQuery->result == 2)
			$response->result = 2;
		else if($responseQuery->result == 1){
			$response->result = 1;
			$response->message = "El usuario no es administrador.";
			//este mensaje sale cuando no se encuentra superusuario ???
		}else return $responseQuery;

		return $response;
	}

	public function getUserInsertedWithRutEmail($rut, $userName){
		$dbClass = new DataBase();
		$responseQuery =  $dbClass->sendQuery("SELECT U.idUsuario FROM usuarios AS U, empresas AS E WHERE U.idEmpresa = E.idEmpresa AND E.rut = ?  AND U.correo = ?", array('ss', $rut, $userName), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontro un usuario con el correo: " . $userName . " en la empresa de rut: " . $rut . " en la base de datos.";
		return $responseQuery;
	}
	//UPDATED
	public function getBusiness($rut){
		$dbClass = new DataBase();
		$responseQuery =  $dbClass->sendQuery("SELECT * FROM empresas WHERE rut = ?", array('s', $rut), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "No hay una empresa con el rut: " . $rut . " en la base de datos.";
		return $responseQuery;
	}
	//UPDATED
	//obtenes todos los datos de la empresa segùn su id
	public function getEmpresaById($idEmpresa){
		$dbClass = new DataBase();
		$responseQuery =  $dbClass->sendQuery("SELECT * FROM empresas WHERE idEmpresa = ?", array('i', $idEmpresa), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El identificador ingresado no corresponde a una empresa registrada en la base de datos.";
		return $responseQuery;
	}
	//UPDATED
	public function getSuggestionRut($rutPart){
		$dbClass = new DataBase();
		$responseQuery =  $dbClass->sendQuery("SELECT DISTINCT rut, nombre FROM empresas WHERE rut LIKE '" . $rutPart . "%'", null, "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "Actualmente no hay registros que mostrar con la sugerencia de rut '" . $rutPart . "' en la base de datos.";
		return $responseQuery;
	}

	public function insertUser($idBusiness, $email, $tokenRest){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("INSERT INTO usuarios(correo, tokenRest, idEmpresa, datosActualizados) VALUES (?,?,?,?)",array('ssii', $email, $tokenRest, $idBusiness, 1), "BOOLE");
		if($responseQuery->result == 2)
		$usersInstance->setNewTokenAndSession($responseQuery->id);
		return $responseQuery;
	}

	public function updatedVouchers($idUser, $value){
		return DataBase::sendQuery("UPDATE usuarios SET datosActualizados = ? WHERE idUsuario = ?", array('ii', $value, $idUser), "BOOLE");
	}

	/*public function updateSession($idUser, $tokenRest){
		$responseQuery = DataBase::sendQuery("UPDATE usuarios SET tokenRest = ? , datosActualizados = ? WHERE idUsuario = ?", array('sii', $tokenRest, 0, $idUser), "BOOLE");
		if($responseQuery->result == 2)
			users::setNewTokenAndSession($idUser);
		return $responseQuery;
	}*/

	public function updateSession($idUser, $tokenRest){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("UPDATE usuarios SET tokenRest = ? WHERE idUsuario = ?", array('si', $tokenRest, $idUser), "BOOLE");
		if($responseQuery->result == 2)
			$usersInstance->setNewTokenAndSession($idUser);
		return $responseQuery;
	}

	public function insertBusiness($rut, $nameBusiness, $typeEmtity, $dateInit, $idStreet, $address, $town, $location, $postalCode){
		$dbClass = new DataBase();
		return $dbClass->sendQuery("INSERT INTO empresas(rut, nombre, tipoEntidad, fechaInicio, idCalle, direccion, departamento, localidad, codigoPostal) VALUES(?,?,?,?,?,?,?,?,?)", array('sssiisssi', $rut, $nameBusiness, $typeEmtity, $dateInit, $idStreet, $address, $town, $location, $postalCode), "BOOLE");
	}
	//UPDATED
	public function insertHistoric($idUser, $vouchersEmitted, $vouchersEmittedInserted, $voucherReceived, $voucherReceivedInserted, $idBusiness){
		$usersInstance = new users();
		$handleDateTimeClass = new handleDateTime();
		$dbClass = new DataBase();
		$dateTime = $handleDateTimeClass->getCurrentDateTimeInt();
		$browser = $usersInstance->getBrowser();
		$ip = $usersInstance->getIpClient();

		$responseQuery = $dbClass->sendQuery("INSERT INTO historial_usuario(idUsuario, fechaHora, navegador, ip, emitidosObtenidos, emitidosIngresados, recibidosObtenidos, recibidosIngresados, idEmpresa) VALUES (?,?,?,?,?,?,?,?,?)", array('isssiiiii', $idUser, $dateTime, $browser, $ip, $vouchersEmitted, $vouchersEmittedInserted, $voucherReceived, $voucherReceivedInserted, $idBusiness), "BOOLE");
		if($responseQuery->result == 1)
			$responseQuery->message = "Ocurrió un error y no se ingresó un registro en el historial al iniciar de sesión.";

		return $responseQuery;
	}

	public function getBrowser(){

		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if(strpos($user_agent, 'MSIE') !== FALSE){
			return 'Internet explorer';
        }elseif(strpos($user_agent, 'Edge') !== FALSE){//Microsoft Edge
        	return 'Microsoft Edge';
        }elseif(strpos($user_agent, 'Trident') !== FALSE){ //IE 11
        	return 'Internet explorer';
        }elseif(strpos($user_agent, 'Opera Mini') !== FALSE){
        	return "Opera Mini";
        }elseif(strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR') !== FALSE){
        	return "Opera";
        }elseif(strpos($user_agent, 'Firefox') !== FALSE){
        	return 'Mozilla Firefox';
        }elseif(strpos($user_agent, 'Chrome') !== FALSE){
        	return 'Google Chrome';
        }elseif(strpos($user_agent, 'Safari') !== FALSE){
        	return "Safari";
        }else return 'No detectado';
    }

    public function getIpClient() {

    	if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    	elseif (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
    	else "No detectado";
    }
	//UPDATED
    public function getListConfigurationUser($idUser){
		$dbClass = new DataBase();
    	$responseQuery = $dbClass->sendQuery("SELECT * FROM configuraciones WHERE idUsuario = ?", array('i', $idUser), "LIST");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "Actualmente no hay configuraciones ingresadas en la base de datos.";
    	return $responseQuery;
    }

    /*
    * VL: se busca por id de usuario y por variable (ej INDICADORES_FACTURACION_USABLES) si ese usuario tiene permitida o no esa configuraciòn
    */
    public function getConfigurationUser($idUser, $variable){
		$dbClass = new DataBase();
    	$responseQuery = $dbClass->sendQuery("SELECT * FROM configuraciones WHERE idUsuario = ? AND variable = ?", array('is', $idUser, $variable), "OBJECT");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "No se encontro la configuracion seleccionada para este usuario.";
    	return $responseQuery;
    }

    public function updateConfigurationUser($idConfig, $idUser, $variable, $value){
		$dbClass = new DataBase();
    	return $dbClass->sendQuery("UPDATE configuraciones SET valor = ? WHERE id = ? AND idUsuario = ? AND variable = ?", array('siis', $value, $idConfig, $idUser, $variable), "BOOLE");
    }

    public function insertConfigurationUser($idUser, $typeVariable, $variable, $value){
		$dbClass = new DataBase();
    	return $dbClass->sendQuery("INSERT INTO configuraciones(idUsuario, tipo, variable, valor) VALUES (?,?,?,?)", array('isss', $idUser, $typeVariable, $variable, $value), "BOOLE");
    }

    public function verifyUserConfigurations($idUser){
    	$userClass = new users();

    	$responseGetFormatRut = $userClass->getConfigurationUser($idUser, "FORMATO_DE_RUT");
    	if($responseGetFormatRut->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "FORMATO_DE_RUT", "1111");

    	$responseGetFromDate = $userClass->getConfigurationUser($idUser, "FECHA_DESDE_ACCOUNT_SATE");
    	if($responseGetFromDate->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "FECHA_DESDE_ACCOUNT_SATE", "MES_ACTUAL");

    	$responseGetIntervalDate = $userClass->getConfigurationUser($idUser, "INTERVALO_FECHA_ACCOUNT_SATE");
    	if($responseGetIntervalDate->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "INTERVALO_FECHA_ACCOUNT_SATE", 30);

    	$responseGetSufijo = $userClass->getConfigurationUser($idUser, "SUFIJO_NOMBRE_SERVICIO_FACTURA");
    	if($responseGetSufijo->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "SUFIJO_NOMBRE_SERVICIO_FACTURA", "NINGUNO");

    	$responseGetSufijoFormat = $userClass->getConfigurationUser($idUser, "SUFIJO_FORMATO_SERVICIO_FACTURA");
    	if($responseGetSufijoFormat->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "SUFIJO_FORMATO_SERVICIO_FACTURA", "NINGUNO");

    	$responseGetSugerenciaVencimiento = $userClass->getConfigurationUser($idUser, "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO");
    	if($responseGetSugerenciaVencimiento->result == 1)
    		$userClass->insertConfigurationUser($idUser, "VALUE", "SUGERENCIA_VENCIMIENTO_FACTURA_SERVICIO", 30);

    	$responseGetPermitProducts = $userClass->getConfigurationUser($idUser, "PERMITIR_LISTA_DE_PRECIOS");
    	if($responseGetPermitProducts->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "PERMITIR_LISTA_DE_PRECIOS", "SI");

    	$responseGetPermitProducts = $userClass->getConfigurationUser($idUser, "PERMITIR_NOTAS_DE_DEBITO");
    	if($responseGetPermitProducts->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "PERMITIR_NOTAS_DE_DEBITO", "SI");

    	$responseGetPermitProducts = $userClass->getConfigurationUser($idUser, "PERMITIR_PRODUCTOS_NO_INGRESADOS");
    	if($responseGetPermitProducts->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "PERMITIR_PRODUCTOS_NO_INGRESADOS", "NO");

    	$responseGetConfiguration = $userClass->getConfigurationUser($idUser, "VER_SALDOS_ESTADO_CUENTA");
    	if($responseGetConfiguration->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "VER_SALDOS_ESTADO_CUENTA", "SI");

    	$responseGetConfiguration = $userClass->getConfigurationUser($idUser, "VER_SALDOS_ESTADO_CUENTA_PDF");
    	if($responseGetConfiguration->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "VER_SALDOS_ESTADO_CUENTA_PDF", "SI");

    	$responseGetConfiguration = $userClass->getConfigurationUser($idUser, "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA");
    	if($responseGetConfiguration->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA", "NO");

    	$responseGetConfiguration = $userClass->getConfigurationUser($idUser, "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA");
    	if($responseGetConfiguration->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "INCLUIR_COBRANZAS_CONTADO_ESTADO_CUENTA", "NO");

    	$responseGetConfigurationCotizacion = $userClass->getConfigurationUser($idUser, "VER_COTIZACION_INICIO");
    	if($responseGetConfigurationCotizacion->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "VER_COTIZACION_INICIO", "SI");

    	$responseGetConfigurationFacturarService = $userClass->getConfigurationUser($idUser, "REALIZAR_FACTURA_POR_SERVICIO");
    	if($responseGetConfigurationFacturarService->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "REALIZAR_FACTURA_POR_SERVICIO", "SI");

    	$responseGetConfigurationIVAIncluido = $userClass->getConfigurationUser($idUser, "IVA_INCLUIDO");
    	if($responseGetConfigurationIVAIncluido->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "IVA_INCLUIDO", "SI");

    	$responseGetTypeDiscount = $userClass->getConfigurationUser($idUser, "DESCUENTO_EN_PORCENTAJE");
    	if($responseGetTypeDiscount->result == 1)
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "DESCUENTO_EN_PORCENTAJE", "SI");

    	$responseGetConfigurationIVA = $userClass->getConfigurationUser($idUser, "INDICADORES_FACTURACION_USABLES");
    	if($responseGetConfigurationIVA->result == 1)
    		$userClass->insertConfigurationUser($idUser, "LIST", "INDICADORES_FACTURACION_USABLES", "1,2,3");

    	$responseGetPeriodInoviceService = $userClass->getConfigurationUser($idUser, "PERIODOS_FACTURACION_SERVICIOS");
    	if($responseGetPeriodInoviceService->result == 1)
    		$userClass->insertConfigurationUser($idUser, "LIST", "PERIODOS_FACTURACION_SERVICIOS", "M");

    	$responseGetBranchCompany = $userClass->getConfigurationUser($idUser, "SUCURSAL_IS_PRINCIPAL");
    	if($responseGetBranchCompany->result == 1){
    		$userClass->insertConfigurationUser($idUser, "VALUE", "SUCURSAL_IS_PRINCIPAL", "0");
    	}

    	$responseGetBranchCompany = $userClass->getConfigurationUser($idUser, "FORMATO_TICKET");
    	if($responseGetBranchCompany->result == 1){
    		$userClass->insertConfigurationUser($idUser, "VALUE", "FORMATO_TICKET", "a4");
    	}

    	$responseGetBranchCompany = $userClass->getConfigurationUser($idUser, "INDICADORES_FACTURACION_DEFECTO");
    	if($responseGetBranchCompany->result == 1){
    		$userClass->insertConfigurationUser($idUser, "VALUE", "INDICADORES_FACTURACION_DEFECTO", "3");
    	}

    	$responseGetAdenda = $userClass->getConfigurationUser($idUser, "ADENDA");
    	//var_dump($responseGetAdenda);exit;
    	if($responseGetAdenda->result == 1){
    		$userClass->insertConfigurationUser($idUser, "VALUE", "ADENDA", "");
    	}

		$responseStockManagement = $userClass->getConfigurationUser($idUser, "MANEJO_DE_STOCK");
    	if($responseStockManagement->result == 1){
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "MANEJO_DE_STOCK", "NO");
		}

		$responseStockManagement = $userClass->getConfigurationUser($idUser, "VER_TOTAL_VENTAS_FOOTER");
    	if($responseStockManagement->result == 1){
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "VER_TOTAL_VENTAS_FOOTER", "NO");
		}
		
		$responseStockManagement = $userClass->getConfigurationUser($idUser, "VER_SIEMPRE_CLIENTE");
    	if($responseStockManagement->result == 1){
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "VER_SIEMPRE_CLIENTE", "NO");
		}

		$responseStockManagement = $userClass->getConfigurationUser($idUser, "SKIP_SELECT_CLIENTE");
    	if($responseStockManagement->result == 1){
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "SKIP_SELECT_CLIENTE", "NO");
		}

		$responseStockManagement = $userClass->getConfigurationUser($idUser, "SUPERFAST_SALE");
    	if($responseStockManagement->result == 1){
    		$userClass->insertConfigurationUser($idUser, "BOOLEAN", "SUPERFAST_SALE", "NO");
		}

		$responseGetBranchCompany = $userClass->getConfigurationUser($idUser, "SUPERFAST_SALE_MEDIOPAGO");
    	if($responseGetBranchCompany->result == 1){
    		$userClass->insertConfigurationUser($idUser, "VALUE", "SUPERFAST_SALE_MEDIOPAGO", "Efectivo");
    	}

    }

    public function getListConfigurationsWithBusiness($idBusiness){
    	$responseQuery = DataBase::sendQuery("SELECT * FROM configuraciones WHERE idUsuario IN (SELECT MIN(idUsuario) FROM usuarios WHERE idEmpresa = ? AND correo NOT IN (SELECT correo FROM super_usuario))", array('i', $idBusiness), "LIST");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "Actualmente no hay una empresa ingresada con este indentificador";
    	return $responseQuery;
    }

    public function cloneUserConfiguration($idBusiness, $idUser){
    	$responseGetConfigs = users::getListConfigurationsWithBusiness($idBusiness);
    	if($responseGetConfigs->result == 2){
    		foreach ($responseGetConfigs->listResult as $key => $value) {
    			users::insertConfigurationUser($idUser, $value['tipo'], $value['variable'], $value['valor']);
    		}
    	}else if($responseGetConfigs->result == 1){
    		users::verifyUserConfigurations($idUser);
    	}
    }

    public function insertPermission($idBusiness, $section, $permission){
		$dbClass = new DataBase();
    	return $dbClass->sendQuery("INSERT INTO permisos_empresa(idEmpresa, seccion, permiso) VALUES (?,?,?)", array('iss', $idBusiness, $section, $permission), "BOOLE");
    }

    public function getPermission($section, $idBusiness){
		$dbClass = new DataBase();
    	return $dbClass->sendQuery("SELECT * FROM permisos_empresa WHERE seccion = ? AND idEmpresa = ?", array('si', $section, $idBusiness), "OBJECT");
    }

    public function getPermissionsBusiness($idBusiness){
		$dbClass = new DataBase();
    	return $dbClass->sendQuery("SELECT * FROM permisos_empresa WHERE idEmpresa = ?", array('i', $idBusiness), "LIST");
    }
	//UPDATED
	public function setPermissionsBusiness($idBusiness, $idPermission){
		$dbClass = new DataBase();
		$responseQuery = null;
		$idPermission = explode(",", $idPermission);

		$caseStatement = "";

		foreach ($idPermission as $key => $value) {
			if(isset($value) && $value != ""){
				$value = trim($value);
				$caseStatement .= "WHEN id = $value THEN 'SI' ";
			}
		}

		$sql = "UPDATE permisos_empresa 
            SET permiso = CASE 
                $caseStatement
                ELSE 'NO' 
            END
            WHERE idEmpresa = ?";
		error_log("CONSULTA: $sql");
		$responseQuery = $dbClass->sendQuery($sql, array('i', $idBusiness), "BOOLE");
		return $responseQuery;


		//PONE A TODOS EN NO
		// $responseQuery = $dbClass->sendQuery("UPDATE permisos_empresa SET permiso = ? WHERE idEmpresa = ?", array('si', "NO", $idBusiness), "BOOLE");
		// if($responseQuery->result == 2){
		// 	foreach ($idPermission as $key => $value) {
		// 		//PONE A LOS SELECCIONADOS EN SI
		// 		$value = $value.trim();
		// 		$responseQuery = $dbClass->sendQuery("UPDATE permisos_empresa SET permiso = ? WHERE idEmpresa = ? AND id = ?", array('sii', "SI", $idBusiness, $value), "BOOLE");
		// 	}
		// }
		// return $responseQuery;
    }

    public function setDefaultPermission($idBusiness){
		$usersInstance = new users();
    	$responsePermissionClient = $usersInstance->getPermission("CLIENT", $idBusiness);
    	if($responsePermissionClient->result == 1)
    		$usersInstance->insertPermission($idBusiness, "CLIENT", "SI");

    	$responsePermissionProvider = $usersInstance->getPermission("PROVIDER", $idBusiness);
    	if($responsePermissionProvider->result == 1)
    		$usersInstance->insertPermission($idBusiness, "PROVIDER", "SI");

    	$responsePermissionService = $usersInstance->getPermission("SERVICE", $idBusiness);
    	if($responsePermissionService->result == 1)
    		$usersInstance->insertPermission($idBusiness, "SERVICE", "NO");

    	$responsePermissionSales = $usersInstance->getPermission("VENTAS", $idBusiness);
    	if($responsePermissionSales->result == 1)
    		$usersInstance->insertPermission($idBusiness, "VENTAS", "NO");

    	$responsePermissionAccounting = $usersInstance->getPermission("ACCOUNTING", $idBusiness);
    	if($responsePermissionAccounting->result == 1)
    		$usersInstance->insertPermission($idBusiness, "ACCOUNTING", "NO");
    	
		$responsePermissionPointOfSale = $usersInstance->getPermission("POINTOFSALE", $idBusiness);
    	if($responsePermissionPointOfSale->result == 1)
    		$usersInstance->insertPermission($idBusiness, "POINTOFSALE", "NO");
    }

    public function setNewTokenAndSession($idUser){
		$usersInstance = new users();
		$dbClass = new DataBase();
		$handleDateTimeClass = new handleDateTime();
    	$newToken = bin2hex(random_bytes((100 - (100 % 2)) / 2));
    	$dateTokenGenerate = $handleDateTimeClass->getCurrentDateTimeInt();
    	$responseQuery = $dbClass->sendQuery('UPDATE usuarios SET token = ? , tokenFecha = ? WHERE idUsuario = ?', array('ssi', $newToken, $dateTokenGenerate, $idUser), "BOOLE");
    	if($responseQuery->result == 2){
    		$responseQuery = null;
    		$responseQuery = $usersInstance->getUserById($idUser);
    		if($responseQuery->result == 2){
    			$objectSession = new \stdClass();
    			$objectSession->idUser = $responseQuery->objectResult->idUsuario;
    			$objectSession->userName = $responseQuery->objectResult->correo;
    			$objectSession->rut = $responseQuery->objectResult->rut;
    			$objectSession->token = $responseQuery->objectResult->token;
    			$objectSession->tokenGenerate = $dateTokenGenerate;
    			$objectSession->empresa = $responseQuery->objectResult->nombre;
    			$objectSession->idEmpresa = $responseQuery->objectResult->idEmpresa;
    			$objectSession->tokenRest = $responseQuery->objectResult->tokenRest;
				
    			$responsePermission = $usersInstance->getPermissionsBusiness($responseQuery->objectResult->idEmpresa);
    			if($responsePermission->result == 2){
    				$arraySecction = array();
    				foreach ($responsePermission->listResult as $key => $value)
    					$arraySecction[$value['seccion']] = $value['permiso'];
    				$objectSession->permission = $arraySecction;
    			}

    			$_SESSION['systemSession'] = $objectSession;
    			// error_log("session modificada (iniciada)");
    			// error_log("id en session ".$_SESSION['systemSession']->idUser);
				unset($responseQuery->objectResult);
    		}
    	}else $responseQuery->message = "Un error interno no permitio iniciar sesión con este usuario.";

    	return $responseQuery;
    }

    public function getUserById($idUser){
    	$dbClass = new DataBase();
		$responseQuery = $dbClass->sendQuery("SELECT * FROM usuarios AS U, empresas AS E WHERE U.idEmpresa = E.idEmpresa AND U.idUsuario = ?", array('i', $idUser), "OBJECT");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "El identificador ingresado no corresponde a un usuario registrado.";

    	return $responseQuery;
    }

    public function eliminarEmpresa($currentSession){
		$usersInstance = new users();
    	$dbClass = new DataBase();

		$response = new \stdClass();
		$response->result = 2;
		$errores = false;

		$responseQuery = $dbClass->sendQuery("DELETE FROM `comprobantes_recibidos` WHERE idReceptor = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `clientes` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `proveedores` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `cuotas_servicios` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `servicios` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `articulos` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `inventario` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `rubro` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `bitacora_comprobantes` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `usuarios` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `comprobantes` WHERE idEmisor = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `permisos_empresa` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `configuraciones` WHERE idUsuario = ?", array('i', $currentSession->idUser), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}
		$responseQuery = $dbClass->sendQuery("DELETE FROM `empresas` WHERE idEmpresa = ?", array('i', $currentSession->idEmpresa), "BOOLE");
    	if($responseQuery->result != 2){
			$errores = true;
		}

		if($errores == true){
			$response->result = 0;
			$response->message = "Error.";
		}

    	return $response;
    }

    public function getUserByNickName($nickName){
    	$responseQuery = DataBase::sendQuery("SELECT * FROM usuarios WHERE correo = ?", array('s', $nickName), "OBJECT");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "El usuario ingresado no corresponde a una cuenta registrada en el sistema";

    	return $responseQuery;
    }

    public function getAccountingConfigurationByRut($rut){
    	$responseQuery = DataBase::sendQuery("SELECT json FROM valores_contabilidad WHERE rutEmpresa = ?", array('s', $rut), "OBJECT");
    	if($responseQuery->result == 1)
    		$responseQuery->message = "No se encontró un registro para el rut ".$rut;

    	return $responseQuery;
    }
}