<?php

namespace Core;

class Controller
{
    private $datos = [];

    public function __construct()
    {
        include_once LIBRERIAS . '/mpdf/mpdf.php';
        include_once LIBRERIAS . '/PhpSpreadsheet/PhpSpreadsheet.php';
        include_once LIBRERIAS . '/PhpSpreadsheet/Mensajero.php';
    }

    public static function respuesta($exito, $mensaje = null, $datos = null, $error = null)
    {
        $resultado = [
            'success' => $exito
        ];
        if ($mensaje !== null) $resultado['mensaje'] = $mensaje;
        if ($datos !== null) $resultado['datos'] = $datos;
        if ($error !== null) $resultado['error'] = $error;
        return $resultado;
    }

    public static function respuestaJSON($informacion)
    {
        header('Content-Type: application/json');
        if (!isset($informacion['success']) && isset($informacion['error'])) header('HTTP/1.0 500');

        echo json_encode($informacion);
        exit;
    }

    public function set($variable, $valor)
    {
        $this->datos[$variable] = $valor;
    }

    public function render($archivo, $template = false)
    {
        if (!file_exists(VISTAS . "/$archivo.php")) {
            header('Location: /' . VISTA_DEFECTO);
            exit;
        }

        ob_start();
        extract($this->datos);
        require(VISTAS . "/$archivo.php");

        if (!$template) {
            $contenido = ob_get_contents();
            ob_end_clean();
            $this->set('contenido', $contenido);
            ob_start();
            extract($this->datos);
            require_once RAIZ . '/Core/View.php';
        }

        $vista = ob_get_contents();
        ob_end_clean();
        echo $vista;
    }
}
