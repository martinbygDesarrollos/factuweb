var voucherData = [];
var status = false;
function consultarEstadoTransaccion(tokenNro, intentos = 0, maxIntentos = 30, monto = null) { // MONTO ES SOLO PARA USAR EN LA DEVOLUCION
    console.log("consultarEstadoTransaccion - " + tokenNro)

    const icon = $('#cardIcon');
	const text = $('#statusText');
	const button = $('#btnConfirmPOSPayment');
	const buttonCancel = $('#btnCancelPOSPayment');

    // Realizar la consulta
    sendAsyncPost("consultarTransaccion", { tokenNro: tokenNro })
        .then(function(transaccionResponse) {
            console.log(transaccionResponse)
            buttonCancel.prop('disabled', false);
            // Si la transacción está finalizada o hubo un error crítico
            if (transaccionResponse.result == 2 || transaccionResponse.result == 0) {
                if(transaccionResponse.message.includes("Transacción rechazada")){
                    console.log('O')
                    // Remover todos los eventos click previos
                    buttonCancel.off('click');
                    // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                    buttonCancel.on('click', function() {
                        console.log('HACER NADA, CANCELAR DEBITO DE LA TARJETA')
                        cancelInsertPaymentMethod("ERROR");
                        // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                    });
                    // VISUAL
                    // console.log((transaccionResponse.message).substring(23))
                    // razon = (transaccionResponse.message).substring(23)
                    // $('#logText').text(razon.includes("CANCELADA") ? `OPERACION CANCELADA` : `Tarjeta rechazada...`)
                    // icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                    // text.text(razon ? razon : 'No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
                    // button.prop('disabled', true).find('.maintext').text('Error...');
                    // Extraer el mensaje una sola vez
                    const razon = transaccionResponse.message?.substring(23) || '';
                    console.log(razon);

                    // Determinar el mensaje a mostrar
                    const mensaje = razon.includes("CANCELADA") 
                        ? "OPERACION CANCELADA" 
                        : razon.includes("EXPIRADA")
                        ? "Tiempo agotado"
                        : "Tarjeta rechazada...";

                    // Actualizar elementos del DOM
                    $('#logText').text(mensaje);
                    icon.removeClass()
                        .addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                    text.text(razon || 'No se pudo conectar con el servidor.')
                        .removeClass()
                        .addClass('status-text text-processing');
                    button.prop('disabled', true)
                        .find('.maintext')
                        .text('Error...');
                    buttonCancel.find('.maintext')
                        .text('Volver');
                        
                } else {
                    console.log('P')

                    console.log(transaccionResponse)
                    if (transaccionResponse.result == 2) {
                        console.log('Q')

                        // Transacción exitosa
                        // Añadir logs para depuración
                        // console.log("Respuesta completa:", transaccionResponse);
                        // console.log("ObjectResult:", transaccionResponse.objectResult);
    
                        if (transaccionResponse.objectResult && transaccionResponse.objectResult.Voucher) {
                            console.log("Voucher:", transaccionResponse.objectResult.Voucher);
                            voucherData = transaccionResponse.objectResult.Voucher;
                        } else {
                            console.log("El voucher no está disponible en la respuesta");
                            // Crear un array vacío para evitar errores
                            voucherData = [];
                        }
                        // VISUAL
                        transaccionResponse.objectResult.Aprobada == true ? $('#logText').text(`Tarjeta APROBADA`) : $('#logText').text(`Tarjeta RECHAZADA`);
                        icon.removeClass().addClass('fas fa-check-circle fa-5x success-checkmark text-success');
                        text.text('¡Pago exitoso!').removeClass().addClass('status-text text-success');
                        button.text('Cerrar').prop('disabled', false);
                        let datosSpecific = transaccionResponse.objectResult.DatosTransaccion.Extendida
                        let datos = transaccionResponse.objectResult.DatosTransaccion
                        // Remover todos los eventos click previos
                        button.off('click');
                        // Agregar el nuevo evento click
                        button.on('click', function() {
                            $('#modalPOSPayment').modal('hide')
                            insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), 
                                {
                                    'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 
                                    'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}] [${datosSpecific.DecretoLeyNom}] [Medio: ${datosSpecific.TarjetaMedio}] [${datosSpecific.TransaccionFechaHora}] [Ticket: ${transaccionResponse.objectResult.Ticket}]`,
                                    
                                    'ticket': `${transaccionResponse.objectResult.Ticket}`,
                                    'consumidor': `${datosSpecific.DecretoLeyNom}`,
                                    'RUT': `${datosSpecific.EmpresaRUT}`,
                                    'monto': `${datosSpecific.FacturaMonto}`,
                                    'gravado': `${datosSpecific.FacturaMontoGravado}`,
                                    'factura': `${datosSpecific.FacturaNro}`
                                })
                        });
                        // Remover todos los eventos click previos
                        buttonCancel.off('click');
                        buttonCancel.prop('disabled', true)
                        // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                        buttonCancel.on('click', function() {
                            console.log('HACER REVERSO, CANCELAR DEBITO DE LA TARJETA')
                            cancelInsertPaymentMethod("REV", monto);
                            // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                        });
                    } else {
                        console.log('R')

                        // Remover todos los eventos click previos
                        buttonCancel.off('click');
                        // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                        buttonCancel.on('click', function() {
                            console.log('HACER NADA, CANCELAR DEBITO DE LA TARJETA')
                            cancelInsertPaymentMethod("ERROR");
                            // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                        });
                        console.log(transaccionResponse.message)
                        // Error en la transacción
                       console.log("Error en la transacción")
                    }
                    console.log('S')

                }
                console.log('T')

                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    console.log('HACER NADA, CANCELAR DEBITO DE LA TARJETA')
                    cancelInsertPaymentMethod("CANCEL", tokenNro);
                    // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                });
            }
            // Si la transacción está pendiente (result == 1) o en otro estado intermedio
            else if (intentos < maxIntentos) {
                console.log('U')
                $('#logText').text(`Transaccion en proceso...`)

                console.log(transaccionResponse.message)
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    console.log('HACER CANCELACION, CANCELAR DEBITO DE LA TARJETA')
                    cancelInsertPaymentMethod("CANCEL", tokenNro);
                    // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                });
                // Esperar y consultar nuevamente
                setTimeout(() => {
                    consultarEstadoTransaccion(tokenNro, intentos + 1, maxIntentos, monto);
                }, 2000); // Consultar cada 2 segundos
            } else {// Si se alcanzó el límite de intentos
                console.log('V')
                // VISUAL
                $('#logText').text(`Tiempo agotado`)
                icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
                button.prop('disabled', true).find('.maintext').text('Error...');
            }
        })
        .catch(function(error) {
            console.log('W')
            console.log(error)
            // Si hay un error en la consulta pero no es crítico para el proceso
            if (intentos < maxIntentos) {
                console.log('X')
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    console.log('HACER CANCELACION, CANCELAR DEBITO DE LA TARJETA')
                    cancelInsertPaymentMethod("CANCEL", tokenNro);
                    // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                });
                // Log del error pero continúa intentando
                console.error("Error en intento " + (intentos + 1) + ": ", error);
                $('#logText').text(``)
                setTimeout(() => {
                    consultarEstadoTransaccion(tokenNro, intentos + 1, maxIntentos, monto);
                }, 2000);
            } else {
                console.log('Y')
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    console.log('HACER NADA, CANCELAR DEBITO DE LA TARJETA')
                    cancelInsertPaymentMethod("ERROR");
                    // insertPaymentMethod('Tarjeta', parseFloat(datosSpecific.FacturaMonto / 100), {'banco': `${datosSpecific.EmisorNombre} ${datosSpecific.TarjetaNombre}`, 'obs': `[${datosSpecific.TipoCuentaNombre}] [Tarjeta: ${datos.TarjetaNro}]` })
                });
                $('#logText').text(`Error critico`)
                icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
                button.prop('disabled', true).find('.maintext').text('Error...');
            }
            console.log('Z')
        });
}

function consultarEstadoTransaccionDEV(tokenNro, intentos = 0, maxIntentos = 30, element) {
    console.log("consultarEstadoTransaccion - " + tokenNro)

    const icon = $('#POSDEV-cardIcon');
	const text = $('#POSDEV-statusText');
	const button = $('#btnConfirmPOSDEV');
	const buttonCancel = $('#btnCancelPOSDEV');

    // Realizar la consulta
    sendAsyncPost("consultarTransaccion", { tokenNro: tokenNro })
        .then(function(transaccionResponse) {
            console.log(transaccionResponse)
            buttonCancel.prop('disabled', false);
            // Si la transacción está finalizada o hubo un error crítico
            if (transaccionResponse.result == 2 || transaccionResponse.result == 0) {
                if(transaccionResponse.message.includes("Transacción rechazada")){
                    console.log('O')
                    // Remover todos los eventos click previos
                    buttonCancel.off('click');
                    // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                    buttonCancel.on('click', function() {
                        
                    });
                    
                    const razon = transaccionResponse.message?.substring(23) || '';
                    console.log(razon);

                    // Determinar el mensaje a mostrar
                    const mensaje = razon.includes("CANCELADA") 
                        ? "OPERACION CANCELADA" 
                        : razon.includes("EXPIRADA")
                        ? "Tiempo agotado"
                        : "Tarjeta rechazada...";

                    // Actualizar elementos del DOM
                    $('#POSDEV-logText').text(mensaje);
                    icon.removeClass()
                        .addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                    text.text(razon || 'No se pudo conectar con el servidor.')
                        .removeClass()
                        .addClass('status-text text-processing');
                    button.prop('disabled', true)
                        .find('.maintext')
                        .text('Error...');
                    buttonCancel.find('.maintext')
                        .text('Volver');
                        
                } else {
                    console.log('P')

                    console.log(transaccionResponse)
                    if (transaccionResponse.result == 2) {
                        console.log('Q')

                        // Transacción exitosa
                        // Añadir logs para depuración
                        // console.log("Respuesta completa:", transaccionResponse);
                        // console.log("ObjectResult:", transaccionResponse.objectResult);
    
                        if (transaccionResponse.objectResult && transaccionResponse.objectResult.Voucher) {
                            console.log("Voucher:", transaccionResponse.objectResult.Voucher);
                            voucherData = transaccionResponse.objectResult.Voucher;
                        } else {
                            console.log("El voucher no está disponible en la respuesta");
                            // Crear un array vacío para evitar errores
                            voucherData = [];
                        }
                        // VISUAL
                        transaccionResponse.objectResult.Aprobada == true ? $('#POSDEV-logText').text(`Tarjeta APROBADA`) : $('#POSDEV-logText').text(`Tarjeta RECHAZADA`);
                        icon.removeClass().addClass('fas fa-check-circle fa-5x success-checkmark text-success');
                        text.text('Devolución exitosa!').removeClass().addClass('status-text text-success');
                        button.text('Cerrar').prop('disabled', false);
                        let datosSpecific = transaccionResponse.objectResult.DatosTransaccion.Extendida
                        let datos = transaccionResponse.objectResult.DatosTransaccion
                        // Remover todos los eventos click previos
                        button.off('click');
                        // Agregar el nuevo evento click
                        button.on('click', function() {
                            CFE_reservado = null
                            $(element).closest('.row.mt-1').remove();
                            insertRemainingAmount('inputRemainingAmount')
                            $('#modalPOSDEV').modal('hide')
                            $('#modalSetPayments').modal('show');
                        });
                        // Remover todos los eventos click previos
                        buttonCancel.off('click');
                        buttonCancel.prop('disabled', true)
                        // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                        buttonCancel.on('click', function() {
                            CFE_reservado = null
                            $(element).closest('.row.mt-1').remove();
                            insertRemainingAmount('inputRemainingAmount')
                            $('#modalPOSDEV').modal('hide')
                            $('#modalSetPayments').modal('show');
                        });
                    } else {
                        console.log('R')

                        // Remover todos los eventos click previos
                        buttonCancel.off('click');
                        // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                        buttonCancel.on('click', function() {
                            // $('#modalPOSDEV').modal('hide')
                        });
                        console.log(transaccionResponse.message)
                        // Error en la transacción
                       console.log("Error en la transacción")
                    }
                    console.log('S')

                }
                console.log('T')

                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    
                });
            }
            // Si la transacción está pendiente (result == 1) o en otro estado intermedio
            else if (intentos < maxIntentos) {
                console.log('U')
                $('#POSDEV-logText').text(`Transaccion en proceso...`)

                console.log(transaccionResponse.message)
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    
                });
                // Esperar y consultar nuevamente
                setTimeout(() => {
                    consultarEstadoTransaccionDEV(tokenNro, intentos + 1, maxIntentos, element);
                }, 2000); // Consultar cada 2 segundos
            } else {// Si se alcanzó el límite de intentos
                console.log('V')
                // VISUAL
                $('#POSDEV-logText').text(`Tiempo agotado`)
                icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
                button.prop('disabled', true).find('.maintext').text('Error...');
            }
        })
        .catch(function(error) {
            console.log('W')
            console.log(error)
            // Si hay un error en la consulta pero no es crítico para el proceso
            if (intentos < maxIntentos) {
                console.log('X')
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    
                });
                // Log del error pero continúa intentando
                console.error("Error en intento " + (intentos + 1) + ": ", error);
                $('#POSDEV-logText').text(``)
                setTimeout(() => {
                    consultarEstadoTransaccionDEV(tokenNro, intentos + 1, maxIntentos, element);
                }, 2000);
            } else {
                console.log('Y')
                // Remover todos los eventos click previos
                buttonCancel.off('click');
                // Agregar el nuevo evento click [deberia hacer el reverso porque en este punto ya se debito de la tarjeta]
                buttonCancel.on('click', function() {
                    
                });
                $('#POSDEV-logText').text(`Error critico`)
                icon.removeClass().addClass('fas fa-exclamation-triangle fa-5x error-shake text-danger');
                text.text('No se pudo conectar con el servidor.').removeClass().addClass('status-text text-processing');
                button.prop('disabled', true).find('.maintext').text('Error...');
            }
            console.log('Z')
        });
}


// TODAVIA NO PASO LA TARJETA:
// <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
//    <s:Body>
//       <ConsultarTransaccionResponse xmlns="http://tempuri.org/">
//          <ConsultarTransaccionResult xmlns:a="http://schemas.datacontract.org/2004/07/TransActV4ConcentradorWS.TransActV4Concentrador" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
//             <a:Aprobada>false</a:Aprobada>
//             <a:CodRespAdq/>
//             <a:DatosTransaccion>
//                <a:Cuotas>0</a:Cuotas>
//                <a:DecretoLeyAplicado>false</a:DecretoLeyAplicado>
//                <a:DecretoLeyMonto>0</a:DecretoLeyMonto>
//                <a:DecretoLeyNro/>
//                <a:EmisorId>0</a:EmisorId>
//                <a:Extendida>
//                   <a:CuentaNro/>
//                   <a:DecretoLeyAdqId/>
//                   <a:DecretoLeyId>0</a:DecretoLeyId>
//                   <a:DecretoLeyNom/>
//                   <a:DecretoLeyVoucher/>
//                   <a:EmisorNombre/>
//                   <a:EmpresaNombre/>
//                   <a:EmpresaRUT/>
//                   <a:EmvAppId/>
//                   <a:EmvAppName/>
//                   <a:FacturaMonto>0</a:FacturaMonto>
//                   <a:FacturaMontoGravado>0</a:FacturaMontoGravado>
//                   <a:FacturaMontoGravadoTrn>0</a:FacturaMontoGravadoTrn>
//                   <a:FacturaMontoIVA>0</a:FacturaMontoIVA>
//                   <a:FacturaMontoIVATrn>0</a:FacturaMontoIVATrn>
//                   <a:FacturaNro>0</a:FacturaNro>
//                   <a:FirmarVoucher>false</a:FirmarVoucher>
//                   <a:MerchantID/>
//                   <a:PlanId>0</a:PlanId>
//                   <a:PlanNombre/>
//                   <a:PlanNroPlan>0</a:PlanNroPlan>
//                   <a:PlanNroTipoPlan>0</a:PlanNroTipoPlan>
//                   <a:SucursalDireccion/>
//                   <a:SucursalNombre/>
//                   <a:TarjetaDocIdentidad/>
//                   <a:TarjetaMedio/>
//                   <a:TarjetaNombre/>
//                   <a:TarjetaTitular/>
//                   <a:TarjetaVencimiento/>
//                   <a:TerminalID/>
//                   <a:TextoAdicional/>
//                   <a:TipoCuentaId>0</a:TipoCuentaId>
//                   <a:TipoCuentaNombre/>
//                   <a:TransaccionFechaHora>0001-01-01T00:00:00</a:TransaccionFechaHora>
//                </a:Extendida>
//                <a:MonedaISO/>
//                <a:Monto>0</a:Monto>
//                <a:MontoCashBack>0</a:MontoCashBack>
//                <a:MontoPropina>0</a:MontoPropina>
//                <a:Operacion/>
//                <a:TarjetaAlimentacion>false</a:TarjetaAlimentacion>
//                <a:TarjetaExtranjera>false</a:TarjetaExtranjera>
//                <a:TarjetaIIN/>
//                <a:TarjetaNro/>
//                <a:TarjetaPrestaciones>false</a:TarjetaPrestaciones>
//             </a:DatosTransaccion>
//             <a:EsOffline>false</a:EsOffline>
//             <a:Lote>0</a:Lote>
//             <a:MsgRespuesta/>
//             <a:NroAutorizacion/>
//             <a:Resp_CodigoRespuesta>0</a:Resp_CodigoRespuesta>
//             <a:Resp_EstadoAvance>ESTADOAVANCE_PENDIENTE_PROCESO</a:Resp_EstadoAvance>
//             <a:Resp_MensajeError/>
//             <a:Resp_TokenSegundosReConsultar>2</a:Resp_TokenSegundosReConsultar>
//             <a:Resp_TransaccionFinalizada>false</a:Resp_TransaccionFinalizada>
//             <a:TarjetaId>0</a:TarjetaId>
//             <a:TarjetaTipo/>
//             <a:Ticket>0</a:Ticket>
//             <a:TokenNro>C6946AB2-CADF-4510-A2DD-563F0D9EB11C</a:TokenNro>
//             <a:TransaccionId>0</a:TransaccionId>
//             <a:Voucher i:nil="true" xmlns:b="http://schemas.microsoft.com/2003/10/Serialization/Arrays"/>
//          </ConsultarTransaccionResult>
//       </ConsultarTransaccionResponse>
//    </s:Body>
// </s:Envelope>

// TARJETA RECHAZADA:
// <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
//    <s:Body>
//       <ConsultarTransaccionResponse xmlns="http://tempuri.org/">
//          <ConsultarTransaccionResult xmlns:a="http://schemas.datacontract.org/2004/07/TransActV4ConcentradorWS.TransActV4Concentrador" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
//             <a:Aprobada>false</a:Aprobada>
//             <a:CodRespAdq>TS</a:CodRespAdq>
//             <a:DatosTransaccion>
//                <a:Cuotas>0</a:Cuotas>
//                <a:DecretoLeyAplicado>false</a:DecretoLeyAplicado>
//                <a:DecretoLeyMonto>0</a:DecretoLeyMonto>
//                <a:DecretoLeyNro/>
//                <a:EmisorId>0</a:EmisorId>
//                <a:Extendida>
//                   <a:CuentaNro/>
//                   <a:DecretoLeyAdqId/>
//                   <a:DecretoLeyId>0</a:DecretoLeyId>
//                   <a:DecretoLeyNom/>
//                   <a:DecretoLeyVoucher/>
//                   <a:EmisorNombre/>
//                   <a:EmpresaNombre/>
//                   <a:EmpresaRUT/>
//                   <a:EmvAppId/>
//                   <a:EmvAppName/>
//                   <a:FacturaMonto>0</a:FacturaMonto>
//                   <a:FacturaMontoGravado>0</a:FacturaMontoGravado>
//                   <a:FacturaMontoGravadoTrn>0</a:FacturaMontoGravadoTrn>
//                   <a:FacturaMontoIVA>0</a:FacturaMontoIVA>
//                   <a:FacturaMontoIVATrn>0</a:FacturaMontoIVATrn>
//                   <a:FacturaNro>0</a:FacturaNro>
//                   <a:FirmarVoucher>false</a:FirmarVoucher>
//                   <a:MerchantID/>
//                   <a:PlanId>0</a:PlanId>
//                   <a:PlanNombre/>
//                   <a:PlanNroPlan>0</a:PlanNroPlan>
//                   <a:PlanNroTipoPlan>0</a:PlanNroTipoPlan>
//                   <a:SucursalDireccion/>
//                   <a:SucursalNombre/>
//                   <a:TarjetaDocIdentidad/>
//                   <a:TarjetaMedio/>
//                   <a:TarjetaNombre/>
//                   <a:TarjetaTitular/>
//                   <a:TarjetaVencimiento/>
//                   <a:TerminalID/>
//                   <a:TextoAdicional/>
//                   <a:TipoCuentaId>0</a:TipoCuentaId>
//                   <a:TipoCuentaNombre/>
//                   <a:TransaccionFechaHora>0001-01-01T00:00:00</a:TransaccionFechaHora>
//                </a:Extendida>
//                <a:MonedaISO/>
//                <a:Monto>0</a:Monto>
//                <a:MontoCashBack>0</a:MontoCashBack>
//                <a:MontoPropina>0</a:MontoPropina>
//                <a:Operacion/>
//                <a:TarjetaAlimentacion>false</a:TarjetaAlimentacion>
//                <a:TarjetaExtranjera>false</a:TarjetaExtranjera>
//                <a:TarjetaIIN/>
//                <a:TarjetaNro/>
//                <a:TarjetaPrestaciones>false</a:TarjetaPrestaciones>
//             </a:DatosTransaccion>
//             <a:EsOffline>false</a:EsOffline>
//             <a:Lote>0</a:Lote>
//             <a:MsgRespuesta>CANCELADA(PERFIL DE LLAVE DEL DISPOSITIVO NO ENCONTRADO | NROSERIE=803-665-284 | PROCID=2 | LLAME A NEW AGE DATA (TEL: 2917-00-75))</a:MsgRespuesta>
//             <a:NroAutorizacion/>
//             <a:Resp_CodigoRespuesta>0</a:Resp_CodigoRespuesta>
//             <a:Resp_EstadoAvance>ESTADOAVANCE_CANCELADA</a:Resp_EstadoAvance>
//             <a:Resp_MensajeError/>
//             <a:Resp_TokenSegundosReConsultar>0</a:Resp_TokenSegundosReConsultar>
//             <a:Resp_TransaccionFinalizada>true</a:Resp_TransaccionFinalizada>
//             <a:TarjetaId>0</a:TarjetaId>
//             <a:TarjetaTipo/>
//             <a:Ticket>0</a:Ticket>
//             <a:TokenNro>C6946AB2-CADF-4510-A2DD-563F0D9EB11C</a:TokenNro>
//             <a:TransaccionId>0</a:TransaccionId>
//             <a:Voucher i:nil="true" xmlns:b="http://schemas.microsoft.com/2003/10/Serialization/Arrays"/>
//          </ConsultarTransaccionResult>
//       </ConsultarTransaccionResponse>
//    </s:Body>
// </s:Envelope>

// EN PROCESO:
// <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
//    <s:Body>
//       <ConsultarTransaccionResponse xmlns="http://tempuri.org/">
//          <ConsultarTransaccionResult xmlns:a="http://schemas.datacontract.org/2004/07/TransActV4ConcentradorWS.TransActV4Concentrador" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
//             <a:Aprobada>false</a:Aprobada>
//             <a:CodRespAdq/>
//             <a:DatosTransaccion>
//                <a:Cuotas>0</a:Cuotas>
//                <a:DecretoLeyAplicado>false</a:DecretoLeyAplicado>
//                <a:DecretoLeyMonto>0</a:DecretoLeyMonto>
//                <a:DecretoLeyNro/>
//                <a:EmisorId>0</a:EmisorId>
//                <a:Extendida>
//                   <a:CuentaNro/>
//                   <a:DecretoLeyAdqId/>
//                   <a:DecretoLeyId>0</a:DecretoLeyId>
//                   <a:DecretoLeyNom/>
//                   <a:DecretoLeyVoucher/>
//                   <a:EmisorNombre/>
//                   <a:EmpresaNombre/>
//                   <a:EmpresaRUT/>
//                   <a:EmvAppId/>
//                   <a:EmvAppName/>
//                   <a:FacturaMonto>0</a:FacturaMonto>
//                   <a:FacturaMontoGravado>0</a:FacturaMontoGravado>
//                   <a:FacturaMontoGravadoTrn>0</a:FacturaMontoGravadoTrn>
//                   <a:FacturaMontoIVA>0</a:FacturaMontoIVA>
//                   <a:FacturaMontoIVATrn>0</a:FacturaMontoIVATrn>
//                   <a:FacturaNro>0</a:FacturaNro>
//                   <a:FirmarVoucher>false</a:FirmarVoucher>
//                   <a:MerchantID/>
//                   <a:PlanId>0</a:PlanId>
//                   <a:PlanNombre/>
//                   <a:PlanNroPlan>0</a:PlanNroPlan>
//                   <a:PlanNroTipoPlan>0</a:PlanNroTipoPlan>
//                   <a:SucursalDireccion/>
//                   <a:SucursalNombre/>
//                   <a:TarjetaDocIdentidad/>
//                   <a:TarjetaMedio/>
//                   <a:TarjetaNombre/>
//                   <a:TarjetaTitular/>
//                   <a:TarjetaVencimiento/>
//                   <a:TerminalID/>
//                   <a:TextoAdicional/>
//                   <a:TipoCuentaId>0</a:TipoCuentaId>
//                   <a:TipoCuentaNombre/>
//                   <a:TransaccionFechaHora>0001-01-01T00:00:00</a:TransaccionFechaHora>
//                </a:Extendida>
//                <a:MonedaISO/>
//                <a:Monto>0</a:Monto>
//                <a:MontoCashBack>0</a:MontoCashBack>
//                <a:MontoPropina>0</a:MontoPropina>
//                <a:Operacion/>
//                <a:TarjetaAlimentacion>false</a:TarjetaAlimentacion>
//                <a:TarjetaExtranjera>false</a:TarjetaExtranjera>
//                <a:TarjetaIIN/>
//                <a:TarjetaNro/>
//                <a:TarjetaPrestaciones>false</a:TarjetaPrestaciones>
//             </a:DatosTransaccion>
//             <a:EsOffline>false</a:EsOffline>
//             <a:Lote>0</a:Lote>
//             <a:MsgRespuesta/>
//             <a:NroAutorizacion/>
//             <a:Resp_CodigoRespuesta>0</a:Resp_CodigoRespuesta>
//             <a:Resp_EstadoAvance>ESTADOAVANCE_ENPROCESO</a:Resp_EstadoAvance>
//             <a:Resp_MensajeError/>
//             <a:Resp_TokenSegundosReConsultar>2</a:Resp_TokenSegundosReConsultar>
//             <a:Resp_TransaccionFinalizada>false</a:Resp_TransaccionFinalizada>
//             <a:TarjetaId>0</a:TarjetaId>
//             <a:TarjetaTipo/>
//             <a:Ticket>0</a:Ticket>
//             <a:TokenNro>831AD507-2648-4B10-95F5-902F223D9218</a:TokenNro>
//             <a:TransaccionId>0</a:TransaccionId>
//             <a:Voucher i:nil="true" xmlns:b="http://schemas.microsoft.com/2003/10/Serialization/Arrays"/>
//          </ConsultarTransaccionResult>
//       </ConsultarTransaccionResponse>
//    </s:Body>
// </s:Envelope>

// TRANSACCION EXITOSA:
// <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
//    <s:Body>
//       <ConsultarTransaccionResponse xmlns="http://tempuri.org/">
//          <ConsultarTransaccionResult xmlns:a="http://schemas.datacontract.org/2004/07/TransActV4ConcentradorWS.TransActV4Concentrador" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
//             <a:Aprobada>true</a:Aprobada>
//             <a:CodRespAdq>00</a:CodRespAdq>
//             <a:DatosTransaccion>
//                <a:Cuotas>1</a:Cuotas>
//                <a:DecretoLeyAplicado>true</a:DecretoLeyAplicado>
//                <a:DecretoLeyMonto>0</a:DecretoLeyMonto>
//                <a:DecretoLeyNro>19210</a:DecretoLeyNro>
//                <a:EmisorId>1</a:EmisorId>
//                <a:Extendida>
//                   <a:CuentaNro/>
//                   <a:DecretoLeyAdqId>6</a:DecretoLeyAdqId>
//                   <a:DecretoLeyId>1</a:DecretoLeyId>
//                   <a:DecretoLeyNom>CONSUMIDOR FINAL</a:DecretoLeyNom>
//                   <a:DecretoLeyVoucher>Aplica dev. IVA-Ley 19210 | Sin nro. factura no aplica ley</a:DecretoLeyVoucher>
//                   <a:EmisorNombre>BROU</a:EmisorNombre>
//                   <a:EmpresaNombre>GESTCOM</a:EmpresaNombre>
//                   <a:EmpresaRUT>111111110153</a:EmpresaRUT>
//                   <a:EmvAppId/>
//                   <a:EmvAppName/>
//                   <a:FacturaMonto>14000</a:FacturaMonto>
//                   <a:FacturaMontoGravado>11475</a:FacturaMontoGravado>
//                   <a:FacturaMontoGravadoTrn>11475</a:FacturaMontoGravadoTrn>
//                   <a:FacturaMontoIVA>0</a:FacturaMontoIVA>
//                   <a:FacturaMontoIVATrn>0</a:FacturaMontoIVATrn>
//                   <a:FacturaNro>2752</a:FacturaNro>
//                   <a:FirmarVoucher>false</a:FirmarVoucher>
//                   <a:MerchantID>123456789</a:MerchantID>
//                   <a:PlanId>1</a:PlanId>
//                   <a:PlanNombre>SIN PLAN</a:PlanNombre>
//                   <a:PlanNroPlan>3</a:PlanNroPlan>
//                   <a:PlanNroTipoPlan>1</a:PlanNroTipoPlan>
//                   <a:SucursalDireccion>DIRECCION 1234</a:SucursalDireccion>
//                   <a:SucursalNombre>CASA CENTRAL</a:SucursalNombre>
//                   <a:TarjetaDocIdentidad/>
//                   <a:TarjetaMedio>BAN</a:TarjetaMedio>
//                   <a:TarjetaNombre>MAESTRO</a:TarjetaNombre>
//                   <a:TarjetaTitular/>
//                   <a:TarjetaVencimiento>**/**</a:TarjetaVencimiento>
//                   <a:TerminalID>FI152101</a:TerminalID>
//                   <a:TextoAdicional/>
//                   <a:TipoCuentaId>1</a:TipoCuentaId>
//                   <a:TipoCuentaNombre>CAJA AHORRO $</a:TipoCuentaNombre>
//                   <a:TransaccionFechaHora>2025-06-10T09:17:10</a:TransaccionFechaHora>
//                </a:Extendida>
//                <a:MonedaISO>0858</a:MonedaISO>
//                <a:Monto>14000</a:Monto>
//                <a:MontoCashBack>0</a:MontoCashBack>
//                <a:MontoPropina>0</a:MontoPropina>
//                <a:Operacion>VTA</a:Operacion>
//                <a:TarjetaAlimentacion>false</a:TarjetaAlimentacion>
//                <a:TarjetaExtranjera>false</a:TarjetaExtranjera>
//                <a:TarjetaIIN>501073</a:TarjetaIIN>
//                <a:TarjetaNro>501073*********0215</a:TarjetaNro>
//                <a:TarjetaPrestaciones>false</a:TarjetaPrestaciones>
//             </a:DatosTransaccion>
//             <a:EsOffline>false</a:EsOffline>
//             <a:Lote>6</a:Lote>
//             <a:MsgRespuesta>APROBADA</a:MsgRespuesta>
//             <a:NroAutorizacion>E52006</a:NroAutorizacion>
//             <a:Resp_CodigoRespuesta>0</a:Resp_CodigoRespuesta>
//             <a:Resp_EstadoAvance>ESTADOAVANCE_FINALIZADA_CORRECTAMENTE</a:Resp_EstadoAvance>
//             <a:Resp_MensajeError/>
//             <a:Resp_TokenSegundosReConsultar>0</a:Resp_TokenSegundosReConsultar>
//             <a:Resp_TransaccionFinalizada>true</a:Resp_TransaccionFinalizada>
//             <a:TarjetaId>17</a:TarjetaId>
//             <a:TarjetaTipo>DEB</a:TarjetaTipo>
//             <a:Ticket>52</a:Ticket>
//             <a:TokenNro>831AD507-2648-4B10-95F5-902F223D9218</a:TokenNro>
//             <a:TransaccionId>1664453</a:TransaccionId>
//             <a:Voucher xmlns:b="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
//                <b:string>--</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>10//06//2025                            9:17</b:string>
//                <b:string>#LOGO#</b:string>
//                <b:string>/H              VENTA MAESTRO               /N</b:string>
//                <b:string>GESTCOM</b:string>
//                <b:string>RUT: 111111110153</b:string>
//                <b:string>DIRECCION 1234</b:string>
//                <b:string></b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>DEBITO - ON-LINE - BANDA</b:string>
//                <b:string>Com.: 123456789            Term.: FI152101</b:string>
//                <b:string>Ticket: 52                         Lote: 6</b:string>
//                <b:string>Tar.: 501073*********0215      Vto.: **//**</b:string>
//                <b:string>Plan Venta: SIN PLAN(1)</b:string>
//                <b:string>Plan//Cuotas: 3//1              Aut.: E52006</b:string>
//                <b:string>No Fact.: 2752</b:string>
//                <b:string></b:string>
//                <b:string>/HTOTAL:                            $ 140,00/N</b:string>
//                <b:string>Aplica dev. IVA-Ley 19210</b:string>
//                <b:string>Sin nro. factura no aplica ley</b:string>
//                <b:string></b:string>
//                <b:string>Imp. Factura:                     $ 140,00</b:string>
//                <b:string>Imp. Gravado TRX:                 $ 114,75</b:string>
//                <b:string>CAJA AHORRO $</b:string>
//                <b:string></b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>NO REQUIERE FIRMA</b:string>
//                <b:string></b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>/I              GESTC1-T00001               /N</b:string>
//                <b:string>/I          *** COPIA COMERCIO ***          /N</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#BR#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>10//06//2025                            9:17</b:string>
//                <b:string>#LOGO#</b:string>
//                <b:string>/H              VENTA MAESTRO               /N</b:string>
//                <b:string>GESTCOM</b:string>
//                <b:string>RUT: 111111110153</b:string>
//                <b:string>DIRECCION 1234</b:string>
//                <b:string></b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>DEBITO - ON-LINE - BANDA</b:string>
//                <b:string>Com.: 123456789            Term.: FI152101</b:string>
//                <b:string>Ticket: 52                         Lote: 6</b:string>
//                <b:string>Tar.: 501073*********0215      Vto.: **//**</b:string>
//                <b:string>Plan Venta: SIN PLAN(1)</b:string>
//                <b:string>Plan//Cuotas: 3//1              Aut.: E52006</b:string>
//                <b:string>No Fact.: 2752</b:string>
//                <b:string></b:string>
//                <b:string>/HTOTAL:                            $ 140,00/N</b:string>
//                <b:string>Aplica dev. IVA-Ley 19210</b:string>
//                <b:string>Sin nro. factura no aplica ley</b:string>
//                <b:string></b:string>
//                <b:string>Imp. Factura:                     $ 140,00</b:string>
//                <b:string>Imp. Gravado TRX:                 $ 114,75</b:string>
//                <b:string>CAJA AHORRO $</b:string>
//                <b:string></b:string>
//                <b:string>#CF#</b:string>
//                <b:string>#CF#</b:string>
//                <b:string>/I          *** COPIA CLIENTE ***           /N</b:string>
//                <b:string>#CF#</b:string>
//                <b:string></b:string>
//             </a:Voucher>
//          </ConsultarTransaccionResult>
//       </ConsultarTransaccionResponse>
//    </s:Body>
// </s:Envelope>