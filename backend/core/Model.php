<?php

namespace Core;

class Model
{
    protected static $db;

    public function __construct()
    {
        // self::$db = new Database();
    }

    public static function resultado($exito, $mensaje = null, $datos = null, $error = null)
    {
        $resultado = [
            'success' => $exito
        ];

        if ($mensaje !== null) $resultado['mensaje'] = $mensaje;
        if ($datos !== null) $resultado['datos'] = $datos;
        if ($error !== null) $resultado['error'] = $error;
        return $resultado;
    }
}
