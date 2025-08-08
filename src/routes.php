<?php

use Slim\App;
use Slim\Http\Response;

require_once '../src/controllers/ctr_users.php';

return function (App $app) {
    $routesUsers = require_once __DIR__ . "/../src/routes/routes_users.php";
    $routesVouchers = require_once __DIR__ . "/../src/routes/routes_vouchers.php";
    $routesVouchersE = require_once __DIR__ . "/../src/routes/routes_vouchers_emitted.php";
    $routesVouchersR = require_once __DIR__ . "/../src/routes/routes_vouchers_received.php";
    $routesServices = require_once __DIR__ . "/../src/routes/routes_services.php";
    $routesProduct = require_once __DIR__ . "/../src/routes/routes_products.php";
    $routesAccounting = require_once __DIR__ . "/../src/routes/routes_accounting.php";
    $routesPayment = require_once __DIR__ . "/../src/routes/routes_payment.php";
    $container = $app->getContainer();

    $routesVouchers($app);
    $routesVouchersE($app);
    $routesVouchersR($app);
    $routesUsers($app);
    $routesServices($app);
    $routesProduct($app);
    $routesAccounting($app);
    $routesPayment($app);

    $userController = new ctr_users();

    //ruta de inicio
    $app->get('/', function ($request, $response, $args) use ($container, $userController) {
        if(isset($_SESSION['systemSession'])){
            $responseFunction = $userController->validateCurrentSession();
            $args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
            if($responseFunction->result == 2){
                return $response->withStatus(302)->withHeader('Location', 'home');
            } else {
                return $response->withStatus(302)->withHeader('Location', 'iniciar-sesion');
            }
        }
        $args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;

        return $this->view->render($response, "index.twig", $args);
        // return $response->withStatus(302)->withHeader('Location', 'iniciar-sesion');



        // $responseValidateCurrenSession = ctr_users::validateCurrentSession(null);
        // if($responseValidateCurrenSession->result == 2)
        //     $args['systemSession'] = $responseValidateCurrenSession->currentSession;

        //  if($responseValidateCurrenSession->result == 2){
        //     $args['systemSession'] = $responseValidateCurrenSession->currentSession;

        //     $responseGetUserSession = ctr_users::getUserInSesion();
        //     $args['updateVouchers'] = $responseGetUserSession->objectResult->datosActualizados;
        // }

        // $responseShowQuote = ctr_users::getVariableConfiguration("VER_COTIZACION_INICIO"); //obtenes respuesta de si el usuario de la sesion activa tiene permiso de VER_COTIZACION_INICIO
        // if($responseShowQuote->result == 2){
        //     $args['showQuoteValue'] = $responseShowQuote->configValue;
        //     if($responseShowQuote->configValue = "SI"){
        //         $args['quote'] = ctr_vouchers::getQuotes();
        //         $args['versionerp'] = '?'.FECHA_ULTIMO_PUSH;
        //     }
        // }
        // return $this->view->render($response, "index.twig", $args);
    })->setName("Start");
};
