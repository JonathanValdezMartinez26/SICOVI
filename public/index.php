<?php
// Solo se reportan los errores y se ignoran las advertencias
error_reporting(E_ERROR | E_PARSE);

// Se reportan todos los errores y advertencias
// error_reporting(E_ALL);

// Configuración de la zona horaria para contemplar horario de verano
$validaHV = new DateTime('now', new DateTimeZone('America/Mexico_City'));
if ($validaHV->format('I')) date_default_timezone_set('America/Mazatlan');
else date_default_timezone_set('America/Mexico_City');

// Se definen las constantes de la aplicación
define('RAIZ', dirname(__DIR__) . '/backend');
define('CONFIGURACION', parse_ini_file(RAIZ . '/config/configuracion.ini'));
define('CONTROLADORES', RAIZ . '/controllers');
define('LIBRERIAS', RAIZ . '/libs');
define('MODELOS', RAIZ . '/models');
define('VISTAS', RAIZ . '/views');
define('COMPONENTES', RAIZ . '/components');
define('LOGIN', 'Login');
define('VISTA_DEFECTO', 'Inicio');
define('METODO_DEFECTO', 'index');


require_once LIBRERIAS . '/BrowserDetection/BrowserDetection.php';

session_start();
if (!$_SESSION['login'] && !$this->validaNavegador()) {
    echo $this->getErrorNavegador();
    exit;
}

// Registra el autoload
spl_autoload_register(function ($archivo) {
    require_once RAIZ . "/$archivo.php";
});

$urlSolicitada = isset($_GET['url']) ? explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL)) : [''];

$extension = pathinfo(end($urlSolicitada), PATHINFO_EXTENSION);
if ($extension != '' && strtolower($extension) != 'php') {
    $rutaArchivo = dirname(__DIR__) . '/' . $_GET['url'];

    if (!file_exists($rutaArchivo)) {
        header('HTTP/1.0 404 Not Found');
    }
    exit;
}

if ($urlSolicitada[0] == '' || strtolower($urlSolicitada[0]) == strtolower(LOGIN)) {
    $controlador = 'Controllers\\' . LOGIN;
    $login = new $controlador;
    $metodo = isset($urlSolicitada[1]) ? $urlSolicitada[1] : METODO_DEFECTO;
    call_user_func_array([$login, $metodo], []);
    exit;
}

if (!file_exists(CONTROLADORES . "/$urlSolicitada[0].php")) {
    if (isset($_SESSION['login']) && $_SESSION['login']) header('Location: /' . VISTA_DEFECTO);
    else header('Location: /' . LOGIN);
    exit;
}

$controlador = 'Controllers\\' . ucfirst($urlSolicitada[0]);
unset($urlSolicitada[0]);

try {
    $controlador = new $controlador;
} catch (Exception $e) {
    header('HTTP/1.0 500 Error en el servidor');
    echo "Error: No se pudo iniciar el controlador solicitado ($urlSolicitada[0]).";
    exit;
}

$metodo = isset($urlSolicitada[1]) ? $urlSolicitada[1] : METODO_DEFECTO;

if (!method_exists($controlador, $metodo)) {
    header('Location: /' . VISTA_DEFECTO);
    exit;
}

unset($urlSolicitada[1]);

$parametros = count($urlSolicitada) ? array_values($urlSolicitada) : [];

call_user_func_array([$controlador, $metodo], $parametros);

function validaNavegador()
{
    $navegadores = [
        'Chrome' => 120,
        'Edge' => 120,
        // 'Firefox' => 130,
        // 'Safari' => 140,
        // 'Opera' => 105
    ];

    $b = new \foroco\BrowserDetection();
    $navegador = $b->getBrowser($_SERVER['HTTP_USER_AGENT']);

    // if ($navegador['browser_name'] === 'Internet Explorer') return false;
    if (!$navegadores[$navegador['browser_name']] || $navegador['browser_version'] < $navegadores[$navegador['browser_name']]) return false;

    return true;
}

function getErrorNavegador()
{
    return <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Navegador no compatible</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f2f2f2;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .container {
                        background-color: #ffffff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                        text-align: center;
                    }
                    .container h1 {
                        color: #ff0000;
                    }
                    .container p {
                        margin: 10px 0;
                    }
                    .container ul {
                        list-style: none;
                        padding: 0;
                    }
                    .container li {
                        margin: 10px 0;
                        display: flex;
                        align-items: center;
                    }
                    .container img {
                        width: 24px;
                        height: 24px;
                        margin-right: 10px;
                    }
                    .container a {
                        color: #007bff;
                        text-decoration: none;
                    }
                    .navegadores {
                        display: flex;
                        justify-content: center;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Navegador no compatible</h1>
                    <p>El navegador que estás utilizando no es compatible con el sistema de MCM</p>
                    <p>Le recomendamos usar uno de los siguientes navegadores:</p>
                    <div class="navegadores">
                        <ul>
                            <li>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/8/87/Google_Chrome_icon_%282011%29.png" alt="Google Chrome">
                                <a href="https://www.google.com/chrome/">Google Chrome</a>
                            </li>
                            <li>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Microsoft_Edge_logo_%282019%29.svg" alt="Microsoft Edge">
                                <a href="https://www.microsoft.com/edge">Microsoft Edge</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </body>
            </html>
        HTML;
}
