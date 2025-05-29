<?php

namespace Core;

class Model
{
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

    public static function getCatalogoConceptosViaticos()
    {
        $query = "SELECT * FROM CAT_CONCEPTO_VIATICOS ORDER BY NOMBRE";
        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Conceptos de viáticos obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los conceptos de viáticos.', null, $e->getMessage());
        }
    }
}
