<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../lib/php/ejecutaServicio.php";
require_once  __DIR__ . "/../lib/php/select.php";
require_once  __DIR__ . "/../lib/php/devuelveJson.php";
require_once __DIR__ . "/Bd.php";
require_once __DIR__ . "/TABLA_SUSCRIPCION.php";
require_once __DIR__ . "/Suscripcion.php";
require_once __DIR__ . "/suscripcionElimina.php";

use Minishlink\WebPush\WebPush;

const AUTH = [
    "VAPID" => [
        "subject" => "https://notificacionesphp.gilbertopachec2.repl.co/",
        "publicKey" => "BMBlr6YznhYMX3NgcWIDRxZXs0sh7tCv7_YCsWcww0ZCv9WGg-tRCXfMEHTiBPCksSqeve1twlbmVAZFv7GSuj0",
        "privateKey" => "vplfkITvu0cwHqzK9Kj-DYStbCH_9AhGx9LqMyaeI6w"
    ]
];

ejecutaServicio(function () {

    $webPush = new WebPush(AUTH);

    // Obtener el cuerpo de la solicitud (JSON enviado desde el cliente)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Obtener el mensaje del JSON, si no existe, usar un mensaje por defecto
    $mensaje = $data['mensaje'] ?? "Hola individuo, come tierra";

    // Envia el mensaje a todas las suscripciones.

    $pdo = Bd::pdo();

    $suscripciones = select(
        pdo: $pdo,
        from: SUSCRIPCION,
        mode: PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE,
        opcional: Suscripcion::class
    );

    foreach ($suscripciones as $suscripcion) {
        // El payload de la notificación debe ser JSON.
        // Aquí se incluye el mensaje recibido del cliente.
        $payload = json_encode([
            'title' => 'Nueva Notificación',
            'body' => $mensaje, // Usa el mensaje dinámico aquí
            'icon' => 'favicon.ico' // Asegúrate de que esta ruta sea correcta para tu service worker
        ]);
        $webPush->queueNotification($suscripcion, $payload);
    }
    $reportes = $webPush->flush();

    // Genera el reporte de envio a cada suscripcion.
    $reporteDeEnvios = "";
    foreach ($reportes as $reporte) {
        $endpoint = $reporte->getRequest()->getUri();
        $htmlEndpoint = htmlentities($endpoint);
        if ($reporte->isSuccess()) {
            // Reporte de éxito.
            $reporteDeEnvios .= "<dt>$htmlEndpoint</dt><dd>Éxito</dd>";
        } else {
            if ($reporte->isSubscriptionExpired()) {
                suscripcionElimina($pdo, $endpoint);
            }
            // Reporte de fallo.
            $explicacion = htmlentities($reporte->getReason());
            $reporteDeEnvios .= "<dt>$endpoint</dt><dd>Fallo: $explicacion</dd>";
        }
    }

    devuelveJson(["reporte" => ["innerHTML" => $reporteDeEnvios]]);
});
