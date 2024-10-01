<?php

require_once 'ctr_users.php';
require_once 'ctr_clients.php';
require_once 'rest/ctr_rest.php';

require_once '../src/filemanagment/managment_pdf.php';
require_once '../src/filemanagment/managment_excel.php';

require_once '../src/class/services.php';
require_once '../src/class/others.php';

class ctr_services{

	//cuenta la cantidad de servicios facturables que hay segun las cuotas y sus fechas de facturacion (mensual, anual, etc).
	public function getCountBillableFeeService($dateEmitted){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetClients = ctr_clients::getBillableClients($dateEmitted);
			if($responseGetClients->result == 2){
				$countBillable = 0;
				foreach ($responseGetClients->clients as $key => $value) {
					$responseGetListFeeService = services::getInvoiceFeesServiceClient($value['id'], $responseGetBusiness->idBusiness, $dateEmitted);
					if($responseGetListFeeService->result == 2)
						$countBillable = $countBillable + sizeof($responseGetListFeeService->listResult);
				}
				$response->result = 2;
				$response->countBillable = $countBillable;
			}else return $responseGetClients;
		}else return $responseGetBusiness;

		return $response;
	}

	//genera un archivo excel con todas las cuotas por servicios que tiene el sistema.
	public function getFeeServiceToExport(){
		$response = new \stdClass();
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetQuote = ctr_vouchers::getQuote("UYI", null);
			if($responseGetQuote->result == 2){
				$responseGetFeeServices = services::getFeeServiceToExport($responseGetQuote->currentQuote, $responseGetBusiness->idBusiness);
				if($responseGetFeeServices->result == 2){
					$file = managment_excel::createExcelFeeService($responseGetFeeServices->listResult);
					if(!is_null($file)){
						$response->result = 2;
						$response->file = $file;
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y el archivo excel con la información por cuotas no fue generado.";
					}
				}else return $responseGetFeeServices;
			}else return $responseGetQuote;
		}else return $responseGetBusiness;

		return $response;
	}

	public function createService($name, $description, $typeCoin, $cost, $amount, $idIva){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getServiceWithName($name, null, $responseGetBusiness->idBusiness);
			if($responseGetService->result == 1){
				$responseInsertService = services::createService($name, $description, $typeCoin, $cost, $amount, $idIva, $responseGetBusiness->idBusiness);
				if($responseInsertService->result == 2){
					$response->result = 2;
					$response->message = "El servicio fue creado correctamente.";
					$responseGetNewService = services::getServiceWithIdToShow($responseInsertService->id, $responseGetBusiness->idBusiness);
					if($responseGetNewService->result == 2)
						$response->service = $responseGetNewService->objectResult;
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y el servicio no fue creado.";
				}
			}else if($responseGetService->result == 2){
				$response->result = 0;
				$response->message = "Ya existe otro servicio con el nombre ingresado.";
			}
		}else return $responseGetBusiness;

		return $response;
	}

	public function modifyService($idService, $name, $description, $cost, $amount, $typeCoin, $idIva){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
			if($responseGetService->result == 2){
				$responseGetServiceRepeatedName = services::getServiceWithName($name, $idService, $responseGetBusiness->idBusiness);
				if($responseGetServiceRepeatedName->result == 1){
					$responseModifyService = services::modifyService($idService, $name, $description, $cost, $amount, $typeCoin, $idIva, $responseGetBusiness->idBusiness);
					if($responseModifyService->result == 2){
						$response->result = 2;
						$response->message = "El servicio fue modificado correctamente";
						$responseGetServiceToShow = services::getServiceWithIdToShow($idService, $responseGetBusiness->idBusiness);
						if($responseGetServiceToShow->result == 2){
							$service = $responseGetServiceToShow->objectResult;
							if(strcmp($service->moneda, "UYI") == 0){
								$responseGetQuote = ctr_vouchers::getQuote("UYI", null);
								if($responseGetQuote->result == 2){
									$service->costoFormat = number_format($service->costo * $responseGetQuote->currentQuote, 2, ",", ".");
									$service->importeCot = number_format($service->importe * $responseGetQuote->currentQuote, 2, ",", ".");
								}
							}
							$response->service = $service;
						}else return $responseGetServiceToShow;
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y el servicio no fue modificado.";
					}
				}else{
					$response->result = 0;
					$response->message = "Ya existe un servicio con el nombre que ingresó.";
				}
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function deleteService($idService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
			if($responseGetService->result == 2){
				$responseDeleteService = services::deleteService($idService, $responseGetBusiness->idBusiness);
				if($responseDeleteService->result == 2){
					$response->result = 2;
					$response->message = "El servicio y sus cuotas asociadas fueron borradas correctamente.";
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y el servicio no fue borrado.";
				}
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function activeService($idService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
			if($responseGetService->result == 2){
				$newValue = 1;
				if($responseGetService->objectResult->activo == 1)
					$newValue = 0;
				$responseUpdateService = services::activeService($idService, $newValue, $responseGetBusiness->idBusiness);
				if($responseUpdateService->result == 2){
					$response->result = 2;
					if($newValue == 0){
						services::disableAllServiceFees($idService, 0, $responseGetBusiness->idBusiness);
						$response->message = "El servicio fue desactivado correctamente.";
					}else
					$response->message = "El servicio fue activado correctamente.";
				}else{
					$response->result = 0;
					if($newValue == 0)
						$response->message = "Ocurrió un error y el servicio no fue desactivado correctamente.";
					else
						$response->message = "Ocurrió un error y el servicio no fue activado correctamente.";
				}
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function getServiceSelected($idService){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function listServiceToChange($idService, $idClient){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			return services::listServiceToChange($idService, $idClient, $responseGetBusiness->idBusiness);
		}else return $responseGetBusiness;
	}

	public function getAllService($idClient){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetClient = ctr_clients::getClientWithId($idClient);
			if($responseGetClient->result == 2){
				return services::getAllService($idClient, $responseGetBusiness->idBusiness);
			}else return $responseGetClient;
		}else return $responseGetBusiness;
	}

	//se buscan los servicios
	public function getListServices($lastId, $textToSearch){
		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetServices = services::getListServices($lastId, $textToSearch, $responseGetBusiness->idBusiness);
			if($responseGetServices->result == 2){
				$arrayResult = array();
				$currentQuote = 1;
				$responseGetQuote = ctr_vouchers::getQuote("UYI", null);
				if($responseGetQuote->result == 2)
					$currentQuote = $responseGetQuote->currentQuote;

				foreach($responseGetServices->listResult as $key => $value){
					if(strcmp($value['moneda'], "UYI") == 0){
						$value['costoFormat'] = number_format($value['costo'] * $currentQuote, 2, ",", ".");
						$value['importeCot'] = number_format($value['importe'] * $currentQuote,2, ",", ".");
					}
					$arrayResult[] = $value;
				}
				$responseGetServices->listResult = $arrayResult;
			}
			return $responseGetServices;
		}else return $responseGetBusiness;
	}

	public function createNewFeeService($idService, $idClient, $period){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
			if($responseGetService->result == 2){
				$responseGetClient = ctr_clients::getClientWithId($idClient);
				if($responseGetClient->result == 2){
					if($period == 0)
						$period = null;
					$responseInsertFeeService = services::createFeeService($responseGetBusiness->idBusiness, $idClient, $idService, $period, 1);
					if($responseInsertFeeService->result == 2){
						$response->result = 2;
						$response->message = "Se creo una nueva cuota del servicio " . $responseGetService->objectResult->nombre . " al cliente seleccionado";
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y la cuota del servicio " . $responseGetService->objectResult->nombre . " al cliente seleccionado no fue creada.";
					}
				}else return $responseGetClient;
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function modifyFeeService($idFeeService, $idService, $period){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetFeeService = services::getFeeServiceWithId($idFeeService, $responseGetBusiness->idBusiness);
			if($responseGetFeeService->result == 2){
				$responseGetService = services::getServiceWithId($idService, $responseGetBusiness->idBusiness);
				if($responseGetService->result == 2){
					if($period == 0)
						$period = null;
					$responseUpdateFeeService = services::modifyFeeService($idFeeService, $idService, $period, $responseGetBusiness->idBusiness);
					if($responseUpdateFeeService->result == 2){
						$response->result = 2;
						$response->message = "La cuota seleccionada fue modificada correctamente.";
						$response->newService = $responseGetService->objectResult->nombre;
					}else{
						$response->result = 0;
						$response->message = "La cuota seleccionada no pudo ser actualizada en la base de datos.";
					}
				}else return $responseGetService;
			}else return $responseGetFeeService;
		}else return $responseGetBusiness;

		return $response;
	}

	//Factura una cuota por servicio
	public function invoiceOneFeeService($idFeeService, $dateEmitted, $dateExpiration){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetFeeService = services::getBillableServiceWithId($idFeeService, $responseGetBusiness->idBusiness, $dateEmitted);
			if($responseGetFeeService->result == 2){
				$period = $responseGetFeeService->objectResult->periodo;
				$feeService = $responseGetFeeService->objectResult;
				$responseGetClient = ctr_clients::getClientWithId($responseGetFeeService->objectResult->idCliente);
				if($responseGetClient->result == 2){

					$client = $responseGetClient->client;
					$receiver = ctr_rest::prepareReceptorToSend($client->docReceptor, $client->nombreReceptor, $client->direccion, $client->localidad, $client->departamento, "Uruguay");

					$responseGetConfigIVA = ctr_users::getVariableConfiguration("IVA_INCLUIDO");
					if($responseGetConfigIVA->result == 2){
						$grossAmount = 0;
						if(strcmp($responseGetConfigIVA->configValue, "SI") == 0)
							$grossAmount = 1;

						$responseGetService = services::getServiceWithId($feeService->idServicio, $responseGetBusiness->idBusiness);
						if($responseGetService->result == 2){
							$service = $responseGetService->objectResult;

							$finalAmount = $service->importe;
							if($grossAmount == 0)
								$finalAmount = $service->costo;

							$apendix = null;
							if(strcmp($service->moneda, "UYI") == 0){
								$responseGetQuote = ctr_vouchers::getQuote("UYI", $dateEmitted);
								if($responseGetQuote->result == 2){
									$service->moneda = "UYU";
									$apendix = "Cotización Unidad Indexada del " . handleDateTime::convertSqlDateHtmlDate($dateEmitted) . ": $" . $responseGetQuote->currentQuote;
									$finalAmount = ($finalAmount * $responseGetQuote->currentQuote);
								}
								else return $responseGetQuote;
							}

							$responseGetSuffixNameService = ctr_users::getVariableConfiguration("SUFIJO_NOMBRE_SERVICIO_FACTURA");
							$valueSuffix = "";
							if($responseGetSuffixNameService->result == 2){
								if(strcmp($responseGetSuffixNameService->configValue, "NINGUNO") != 0){
									$responseGetSuffixFormatService = ctr_users::getVariableConfiguration("SUFIJO_FORMATO_SERVICIO_FACTURA");
									$formatSuffix = "";
									if($responseGetSuffixFormatService->result == 2){
										if(strcmp($responseGetSuffixFormatService->configValue, "NUMERICA") == 0){
											if($period)
												$valueSuffix = handleDateTime::getDateSuffixForPeriod($dateEmitted, $responseGetSuffixNameService->configValue, $period);
											else
												$valueSuffix = handleDateTime::getDateSuffix($dateEmitted, $responseGetSuffixNameService->configValue);
										}
										else if(strcmp($responseGetSuffixFormatService->configValue, "TEXTUAL") == 0){
											if($period)
												$valueSuffix = handleDateTime::getDateSuffixTextForPeriod($dateEmitted, $responseGetSuffixNameService->configValue, $period);
											else
												$valueSuffix = handleDateTime::getDateSuffixText($dateEmitted, $responseGetSuffixNameService->configValue);
										}
									}
								}
							}
							$detailCFE = array(ctr_rest::prepareDetalleToSend($service->idIVA, $service->nombre . " (" . $valueSuffix . ") ", $service->idServicio, $service->descripcion, 1,null, $finalAmount));
							$responseValidate = validate::validateRut($responseGetClient->client->docReceptor);
							$typeCFE = 101;
							if($responseValidate->result == 2)
								$typeCFE = 111;

							$responseGetBranchCompanyDefault = ctr_users::getVariableConfiguration("SUCURSAL_IS_PRINCIPAL");
							$branchCompany = null;
							if($responseGetBranchCompanyDefault->result == 2)
								$branchCompany = $responseGetBranchCompanyDefault->configValue;

							$responseNewCFE = ctr_vouchers::createNewCFE($typeCFE, $dateEmitted, $grossAmount, 2, $dateExpiration, $service->moneda, $detailCFE, $receiver, null, null, $apendix, $branchCompany, null);
							if($responseNewCFE->result == 2){
								$dateCFE = handleDateTime::getDateInt(substr($responseNewCFE->cfe->fecha,0,10));
								services::updateLastInvoiceDate($feeService->idCuota, $feeService->idCliente, $dateCFE, $responseGetBusiness->idBusiness);

								$responseGetInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
								if($responseGetBusiness->result == 2)
									$responseUpdateVouchers = ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetInfoBusiness->objectResult->rut);

								$response->result = 2;
								$response->message = "La cuota del cliente " . $client->nombreReceptor . " sobre el servicio " . $service->nombre . " fue facturada correctamente.";
							}else return $responseNewCFE;
						}else return $responseGetService;
					}else return $responseGetConfigIVA;
				}else return $responseGetClient;
			}else return $responseGetFeeService;
		}else return $responseGetBusiness;

		return $response;
	}


	//factura todas las cuotas por servicios, la fecha de emision y de vencimiento son datos para el comprobante, no filtros
	public function invoiceAllFeeService($dateEmitted, $dateExpiration){
		$response = new \stdClass();


		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){

			$responseGetListClients = ctr_clients::getBillableClients($dateEmitted);
			if($responseGetListClients->result == 2){
				$arrayResult = array();
				foreach ($responseGetListClients->clients as $key => $client){ //recorre todos los clientes a los que se le va a facturar

					$receiver = ctr_rest::prepareReceptorToSend($client['docReceptor'], $client['nombreReceptor'], $client['direccion'], $client['localidad'], $client['departamento'], "Uruguay");
					$responseGetListFee = services::getInvoiceFeesServiceClient($client['id'], $responseGetBusiness->idBusiness, $dateEmitted);
					//en la misma lista responseGetListFee puede haber clientes facturables y clientes que no
					if($responseGetListFee->result == 2){

						$detailCFEUSD = array();
						$detailCFEUYU = array();

						$responseGetConfig = ctr_users::getVariableConfiguration("REALIZAR_FACTURA_POR_SERVICIO");
						if($responseGetConfig->result == 2){
							$billForFeeService = $responseGetConfig->configValue;
							$responseGetConfigIVA = ctr_users::getVariableConfiguration("IVA_INCLUIDO");
							if($responseGetConfigIVA->result == 2){
								$grossAmount = 0;
								if(strcmp($responseGetConfigIVA->configValue, "SI") == 0)
									$grossAmount = 1;

								$quoteUYI = 0;
								$responseGetQuote = ctr_vouchers::getQuote("UYI", $dateEmitted);
								if($responseGetQuote->result == 2)
									$quoteUYI = $responseGetQuote->currentQuote;

								$apendix = null;
								foreach ($responseGetListFee->listResult as $key => $itemFee) {
									if ( $itemFee['periodo'] )
										$period = $itemFee['periodo'];
									else $period = null;
									$responseGetSuffixNameService = ctr_users::getVariableConfiguration("SUFIJO_NOMBRE_SERVICIO_FACTURA");
									$valueSuffix = "";
									if($responseGetSuffixNameService->result == 2){
										if(strcmp($responseGetSuffixNameService->configValue, "NINGUNO") != 0){
											$responseGetSuffixFormatService = ctr_users::getVariableConfiguration("SUFIJO_FORMATO_SERVICIO_FACTURA");
											$formatSuffix = "";
											if($responseGetSuffixFormatService->result == 2){
												if(strcmp($responseGetSuffixFormatService->configValue, "NUMERICA") == 0){
													if($period)
														$valueSuffix = handleDateTime::getDateSuffixForPeriod($dateEmitted, $responseGetSuffixNameService->configValue, $period);
													else
														$valueSuffix = handleDateTime::getDateSuffix($dateEmitted, $responseGetSuffixNameService->configValue);
												}
												else if(strcmp($responseGetSuffixFormatService->configValue, "TEXTUAL") == 0){
													if($period)
														$valueSuffix = handleDateTime::getDateSuffixTextForPeriod($dateEmitted, $responseGetSuffixNameService->configValue, $period);
													else
														$valueSuffix = handleDateTime::getDateSuffixText($dateEmitted, $responseGetSuffixNameService->configValue);
												}
											}
										}
									}
									$finalAmount = $itemFee['importe'];
									if($grossAmount == 0)
										$finalAmount = $itemFee['costo'];

									if(strcmp($itemFee['moneda'], "UYI") == 0){
										if($quoteUYI != 0){
											$apendix = "Cotización Unidad Indexada del " . handleDateTime::convertSqlDateHtmlDate($dateEmitted) . ": $" . $responseGetQuote->currentQuote;
											//$apendix = "Cotización Unidad Indexada del " . $dateEmitted . ": $" . $quoteUYI;
											$finalAmount = ($finalAmount * $quoteUYI);
										}else return $responseGetQuote;
									}

									$detailItem = ctr_rest::prepareDetalleToSend($itemFee['idIVA'], $itemFee['nombre'] . " (" .$valueSuffix. ") " , $itemFee['idCuota'], $itemFee['descripcion'], 1, null, $finalAmount);
									if(strcmp($itemFee['moneda'], "UYU") == 0 || strcmp($itemFee['moneda'], "UYI") == 0)
										$detailCFEUYU[] = $detailItem;
									else if(strcmp($itemFee['moneda'], "USD") == 0)
										$detailCFEUSD[] = $detailItem;
								}
								$responseValidate = validate::validateRut($client['docReceptor']);
								$typeCFE = 101;
								if($responseValidate->result == 2)
									$typeCFE = 111;

								$responseGetBranchCompanyDefault = ctr_users::getVariableConfiguration("SUCURSAL_IS_PRINCIPAL");
								$branchCompany = null;
								if($responseGetBranchCompanyDefault->result == 2)
									$branchCompany = $responseGetBranchCompanyDefault->configValue;

								//si el tipo de moneda es uyu
								if(sizeof($detailCFEUYU) > 0){
									if(strcmp($billForFeeService, "SI") == 0){
										foreach ($detailCFEUYU as $key => $value) {
											$responseNewCFEUYU = ctr_vouchers::createNewCFE($typeCFE, $dateEmitted, $grossAmount, 2, $dateExpiration, "UYU", array($value), $receiver, null, null, $apendix,$branchCompany, null);
											if($responseNewCFEUYU->result == 2){
												$dateNewCFEUYU = handleDateTime::getDateInt(substr($responseNewCFEUYU->cfe->fecha, 0, 10));
												services::updateLastInvoiceDate($value['codItem'], $client['id'], $dateNewCFEUYU, $responseGetBusiness->idBusiness);
											}else $arrayResult[] = $responseNewCFEUYU->message;
										}
									}else{
										$responseNewCFEUYU = ctr_vouchers::createNewCFE($typeCFE, $dateEmitted, $grossAmount, 2, $dateExpiration, "UYU", $detailCFEUYU, $receiver, null, null, $apendix, $branchCompany, null);
										if($responseNewCFEUYU->result == 2){
											$dateNewCFEUYU = handleDateTime::getDateInt(substr($responseNewCFEUYU->cfe->fecha, 0, 10));
											foreach ($detailCFEUYU as $key => $value)
												services::updateLastInvoiceDate($value['codItem'], $client['id'], $dateNewCFEUYU, $responseGetBusiness->idBusiness);
										}else $arrayResult[] = $responseNewCFEUYU->message;
									}
								}
								//si el tipo de moneda es usd
								if(sizeof($detailCFEUSD) > 0){
									if(strcmp($billForFeeService, "SI") == 0){
										foreach ($detailCFEUSD as $key => $value) {
											$responseNewCFEUSD = ctr_vouchers::createNewCFE($typeCFE, $dateEmitted, $grossAmount, 2, $dateExpiration, "USD", array($value), $receiver, null, null, null, $branchCompany, null);
											if($responseNewCFEUSD->result == 2){
												$dateNewCFEUSD = handleDateTime::getDateInt(substr($responseNewCFEUSD->cfe->fecha, 0, 10));
												foreach ($detailCFEUSD as $key => $value)
													services::updateLastInvoiceDate($value['codItem'], $client['id'], $dateNewCFEUSD, $responseGetBusiness->idBusiness);
												//services::updateLastInvoiceDate($detailCFEUSD[0]['codItem'], $client['id'], $dateNewCFEUSD, $responseGetBusiness->idBusiness);
											}else $arrayResult[] = $responseNewCFEUSD->message;
										}
									}else{
										$responseNewCFEUSD = ctr_vouchers::createNewCFE($typeCFE, $dateEmitted, $grossAmount, 2, $dateExpiration, "USD", $detailCFEUSD, $receiver, null, null, null, $branchCompany, null);
										if($responseNewCFEUSD->result == 2){
											$dateNewCFEUSD = handleDateTime::getDateInt(substr($responseNewCFEUSD->cfe->fecha, 0, 10));
											foreach ($detailCFEUSD as $key => $value)
												services::updateLastInvoiceDate($value['codItem'], $client['id'], $dateNewCFEUSD, $responseGetBusiness->idBusiness);
										}else $arrayResult[] = $responseNewCFEUSD->message;
									}
								}
							}else return $responseGetConfigIVA;
						}else return $responseGetConfig;
					}
					//propongo quitar el else ya que la lista puede haber clientes facturables y otros que no
				}

				$responseGetInfoBusiness = ctr_users::getBusinessInformation($responseGetBusiness->idBusiness);
				if($responseGetBusiness->result == 2)
					$responseUpdateVouchers = ctr_vouchers_emitted::updateDataVoucherEmitted($responseGetInfoBusiness->objectResult->rut);

				if(sizeof($arrayResult) == 0){
					$response->result = 2;
					$response->message = "Todos los servicios fueron facturados correctamente.";
				}else{
					$response->result = 1;
					$response->message = "";
					foreach ($arrayResult as $value) {
						if ( strpos($response->message, $value) === FALSE ){
							$response->message .= $value;
						}
					}

					if ($response->message == ""){
						$response->message = "Algunos servicios no fueron facturados correctamente, porque no cumplen con los datos requeridos para emitir el comprobante correspondiente";
					}
				}
			}else return $responseGetListClients;
		}else return $responseGetBusiness;

		return $response;
	}

	public function getFeeService($idFeeService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetFeeService = services::getFeeServiceWithId($idFeeService, $responseGetBusiness->idBusiness);
			if($responseGetFeeService->result == 2){
				$response->result = 2;
				$response->feeService = $responseGetFeeService->objectResult;
			}else{
				$response->result = 0;
				$response->message = $responseGetFeeService->message;
			}
		}else return $responseGetBusiness;

		return $response;
	}

	public function getFeeServiceWithDetail($idFeeService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetFeeService = services::getFeeServiceWithId($idFeeService, $responseGetBusiness->idBusiness);
			if($responseGetFeeService->result == 2){
				$responseGetService = services::getServiceWithId($responseGetFeeService->objectResult->idServicio, $responseGetBusiness->idBusiness);
				if($responseGetService->result == 2)
					$responseGetFeeService->objectResult->nombreServicio = $responseGetService->objectResult->nombre;
				$response->result = 2;
				$response->feeService = $responseGetFeeService->objectResult;

				$responseGetClient = ctr_clients::getClientWithId($responseGetFeeService->objectResult->idCliente);
				if($responseGetClient->result == 2){
					$responseGetClient->client->nombreReceptor = utils::stringToLowerWithFirstCapital($responseGetClient->client->nombreReceptor);
					$response->client = $responseGetClient->client;
				}
			}else{
				$response->result = 0;
				$response->message = $responseGetFeeService->message;
			}
		}else return $responseGetBusiness;

		return $response;
	}

	public function getFeeServiceToShow($idFeeService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetFeeService = services::getFeeServiceWithId($idFeeService, $responseGetBusiness->idBusiness);
			if($responseGetFeeService->result == 2){
				$feeService = $responseGetFeeService->objectResult;

				if(!is_null($feeService->fechaUltimaFactura))
					$feeService->fechaUltimaFactura = handleDateTime::setFormatBarDate($feeService->fechaUltimaFactura);

				if(is_null($feeService->periodo))
					$feeService->periodo = 0;

				$responseGetService = services::getServiceWithId($feeService->idServicio, $responseGetBusiness->idBusiness);
				if($responseGetService->result == 2){
					$service = $responseGetService->objectResult;
					$feeService->nombreServicio = utils::stringToLowerWithFirstCapital($service->nombre);
					$feeService->descripcion = $service->descripcion;
					$feeService->importe = number_format($service->importe,2,",",".");
				}

				$responseGetClient = ctr_clients::getClientWithId($feeService->idCliente);
				if($responseGetClient->result == 2)
					$feeService->nombreCliente = utils::stringToLowerWithFirstCapital($responseGetClient->client->nombreReceptor);

				$response->result = 2;
				$response->feeService = $feeService;
			}else return $responseGetFeeService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function deleteFeeService($idFeeService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseDeleteFeeService = services::deleteFeeService($idFeeService, $responseGetBusiness->idBusiness);
			if($responseDeleteFeeService->result == 2){
				$response->result = 2;
				$response->message = "La cuota fue borrada correctamente.";
			}else{
				$response->result = 0;
				$response->message = "La cuota seleccionada no pudo ser borrada de la base de datos.";
			}
		}else return $responseGetBusiness;

		return $response;
	}

	public function changeCurrentValueFeeService($idFeeService){
		$response = new \stdClass();

		$responseMyBusiness = ctr_users::getBusinesSession();
		if($responseMyBusiness->result == 2){
			$responseGetFeeService = services::getFeeServiceWithId($idFeeService, $responseMyBusiness->idBusiness);
			if($responseGetFeeService->result == 2){
				$responseGetService = services::getServiceWithId($responseGetFeeService->objectResult->idServicio, $responseMyBusiness->idBusiness);
				if($responseGetService->result == 2){
					if($responseGetService->objectResult->activo == 1){
						$newValue = 1;
						if($responseGetFeeService->objectResult->vigente == 1)
							$newValue = 0;
						$responseUpdateValue = services::changeCurrentValueFeeService($newValue, $idFeeService, $responseMyBusiness->idBusiness);
						if($responseUpdateValue->result == 2){
							$response->result = 2;
							if($newValue == 1)
								$response->message = "El servicio fue activado correctamente.";
							else
								$response->message = "El servicio fue desactivado correctamente.";
						}else return $responseUpdateValue;
					}else{
						$response->result = 0;
						$response->message = "La cuota seleccionada no puede cambiar su estado porque el servicio esta desactivado.";
					}
				}else return $responseGetService;
			}else return $responseGetFeeService;
		}else return $responseMyBusiness;

		return $response;
	}

	public function getListFeeServices($lastId, $textToSearch){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			 // services::testData($responseGetBusiness->idBusiness);
			$responseGetFee = services::getListFeeServices($lastId, $textToSearch, $responseGetBusiness->idBusiness);
			if($responseGetFee->result == 2){
				$arrayServices = array();
				$list = $responseGetFee->listResult;

				$currentQuote = 1;
				$responseGetQuote = ctr_vouchers::getQuote("UYI", null);
				if($responseGetQuote->result == 2)
					$currentQuote = $responseGetQuote->currentQuote;

				for($i = 0; $i < sizeof($list); $i++) {
					$responseGetClient = ctr_clients::getClientWithId($list[$i]['idCliente']);
					if($responseGetClient->result == 2)
						$list[$i]['nombreCliente'] = utils::stringToLowerWithFirstCapital($responseGetClient->client->nombreReceptor);
					if(strcmp($list[$i]['moneda'], "UYI") == 0){
						$list[$i]['costoFormat'] = number_format($list[$i]['costo'] * $currentQuote, 2, ",", ".");
						$list[$i]['importeCot'] = number_format($list[$i]['importe'] * $currentQuote,2, ",", ".");
					}
				}
				$response->result = 2;
				$response->services = $list;
				$response->lastId = $responseGetFee->lastId;
			}else return $responseGetFee;
		}else return $responseGetBusiness;

		return $response;
	}

	public function getService($idServices){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getService($idServices);
			if($responseGetService->result == 2){
				$response->result = 2;
				$response->service = $responseGetService->objectResult;
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function deactiveService($idService){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getService($idService);
			if($responseGetService->result == 2){
				$newValue = 1;
				if($responseGetService->objectResult->vigente == 1)
					$newValue = 0;
				$responseDesactiveService = services::deactiveService($newValue, $idService);
				if($responseDesactiveService->result == 2){
					$response->result = 2;
					if($newValue == 1)
						$response->message = "El servicio fue activado correctamente.";
					else
						$response->message = "El servicio fue desactivado correctamente.";
				}else return $responseDesactiveService;
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

	public function updateDateVoucherInvoice($idService, $newDateVoucher){
		$response = new \stdClass();

		$responseGetBusiness = ctr_users::getBusinesSession();
		if($responseGetBusiness->result == 2){
			$responseGetService = services::getService($idService);
			if($responseGetService->result == 2){
				$newDateVoucherINT = handleDateTime::getDateInt($newDateVoucher);
				$responseUpdateDateVoucher = services::updateDateVoucherInvoice($idService, $newDateVoucherINT);
				if($responseUpdateDateVoucher->result == 2){
					$response->result = 2;
					$response->message = "La fecha de facturación del servicio fue modificada correctamente.";
				}else return $responseUpdateDateVoucher;
			}else return $responseGetService;
		}else return $responseGetBusiness;

		return $response;
	}

}