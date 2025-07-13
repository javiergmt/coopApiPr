<?php
// librerias necesarias
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "reflect.php"); // esta libreria es la que se encarga de llamar a los servicios
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "db.php"); // esta libreria es la que se encarga de conectar a la base de datos y ejecutar los stored procedures
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "funciones.php"); // esta libreria es la que se encarga de conectar a la base de datos y ejecutar los stored procedures

date_default_timezone_set("America/Buenos_Aires"); // se establece la zona horaria de Buenos Aires

// esta api siempre responde en formato JSON
header('Content-Type: application/json');

// se desactiva el cache del navegador
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies

// por seguridad, no se permite el acceso desde otros dominios
error_reporting(0);


/*
    * WebService para llamar a los servicios de la aplicacion
    * Los servicios deben estar definidos como una clase en un archivo dentro de la carpeta services
    * El nombre del archivo debe ser el mismo que el nombre de la clase
    * Y dentro de esa clase debe existir una funcion publica que es el metodo que se quiere ejecutar
*/

/*
    Los request siempre tienen que estar dirigidos a este archivo index.php
    y requieren como minimo los siguientes parametros:
    - service: el nombre del servicio a ejecutar (ejemplo: "creditos")
    - method: el nombre del metodo a ejecutar dentro del servicio (ejemplo: "getOfertas")
    - [opcional] params: un array con los parametros que se le pasan al metodo (ejemplo: ["dni" => 12345678, "nro" => "123456"] )
*/

/* Ejemplo si yo llamo a /index.php?service=creditos&method=getOfertas&params[dni]=12345678&params[nro]=123456
    El servicio a ejecutar es "creditos" y el metodo a ejecutar es "getOfertas"
    Y los parametros que se le pasan al metodo son ["dni" => 12345678, "nro" => "123456"]
*/



try {

    $data = null;

    // Si el request es GET, uso las variable $_GET
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $data = $_GET;
        // Si el content-type es JSON, uso el body del request que es un json
    } elseif (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $bodyData = @file_get_contents('php://input');
        if (!$bodyData) {
            throw new Exception("Invalid Data in request");
        }
        $data = @json_decode($bodyData, TRUE);
          // cualquier otro caso, uso el body del request que es un form-urlencoded o multipart/form-data
    } elseif (isset($_POST) && count($_POST)) {
        $data = $_POST;
    }

    //echo "Datos recibidos: ";
    //echo json_encode($data);
    //echo "\n";

    // valido que los datos sean un array y que contengan los campos service y method
    // si no los contiene, lanza una excepcion
    if (
        !is_array($data)
        || !isset($data['service']) || !$data['service']
        || !isset($data['method']) || !$data['method']
    ) {
        throw new Exception("Unauthorized Service Method");
    }


    // Auth    
    // inicia la session de PHP
    session_start();

    if ( ($data['service'] == 'comercios' && $data['method'] == 'validarVendedor')
        or ($data['service'] == 'comercios' && $data['method'] == 'validarCmUsuario')
        or ($data['service'] == 'promotores' && $data['method'] == 'validarPrUsuario') 
        or ($data['service'] == 'promotores' && $data['method'] == 'enviarMsg') 
        or ($data['service'] == 'promotores' && $data['method'] == 'getLink') 
       ){
        // Si el servicio es login y el metodo es checkUserPassword, no se requiere autenticacion
        // Se permite el acceso al login sin estar logueado
        // Esto es para que se pueda hacer login desde la api

    } elseif (!isset($_SESSION['logged']) || $_SESSION['logged'] === FALSE) {
        //echo "Usuario logueado: " . (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Desconocido') . "\n";
            throw new Exception("Unauthorized");
    }

    // Creo una instancia del procesador de servicios
    // Este procesador es el que se encarga de llamar al servicio y al metodo
    // El procesador recibe el nombre del servicio, el nombre del metodo y los parametros que se le pasan al metodo
    // El procesador se encarga de cargar el archivo del servicio
    // y de llamar al metodo con los parametros que se le pasan
    // Si el servicio o el metodo no existen, lanza una excepcion
    // Si el metodo devuelve un array con una clave "error", se devuelve ese error

    $Processor = new callServiceMethod();
    
    echo "Parametros recibidos: ";
    echo json_encode($data['params']);
    echo "\n";

    $R = $Processor->execute($data['service'], $data['method'], ((isset($data['params']) && $data['params'] && is_array($data['params'])) ? $data['params'] : []));
    
    // Si el resultado es un array y tiene una clave "error", se devuelve ese error
    // si dentro del mensaje de error hay una referencia a SQLSTATE, schema o DATABASE, se devuelve un mensaje genérico por seguridad, por si en el error hay información sensible
    if (is_array($R) && isset($R['error']) && (stripos($R['error'], 'SQLSTATE') !== FALSE || stripos($R['error'], 'schema') !== FALSE || stripos($R['error'], 'DATABASE') !== FALSE)) {
       
        $R['error'] = 'Error WebService. Vuelva a intentar.';
    }

    // devuelvo el resultado en formato JSON
    die(json_encode($R));

} catch (Throwable $ex) {
    //@file_put_contents("./error.log.txt", DATE("Y-m-d H:i:s") . "\n" . $ex->getMessage() . "\n\n-------------------------------------------\n", FILE_APPEND);
    $Msg = $ex->getMessage();
    $Msg = ($Msg && stripos($Msg, 'SQLSTATE') === FALSE && stripos($Msg, 'schema') === FALSE && stripos($Msg, 'DATABASE') === FALSE) ? $Msg : 'Error WebService. Vuelva a intentar.';
    die(json_encode(array('error' => $Msg)));
}
