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

    public static function getCatalogoSucursales()
    {
        $query = <<<SQL
            SELECT
                SUCURSAL.ID AS ID
                , SUCURSAL.NOMBRE AS NOMBRE
                , REGION.ID AS REGION_ID
                , REGION.NOMBRE AS REGION_NOMBRE
                , EMPRESA.ID AS EMPRESA_ID
                , EMPRESA.NOMBRE AS EMPRESA_NOMBRE
            FROM
                SUCURSAL
                LEFT JOIN REGION ON REGION.ID = SUCURSAL.REGION
                LEFT JOIN EMPRESA ON EMPRESA.ID = REGION.EMPRESA
            ORDER BY NOMBRE
        SQL;

        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Sucursales obtenidas.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las sucursales.', null, $e->getMessage());
        }
    }

    public static function getCatalogoConceptosViaticos()
    {
        $query = "SELECT * FROM CAT_VIATICOS_CONCEPTO ORDER BY NOMBRE";
        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Conceptos de viáticos obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los conceptos de viáticos.', null, $e->getMessage());
        }
    }

    public static function getCatalogoMetodosEntrega()
    {
        $query = "SELECT * FROM CAT_VIATICOS_METODO_ENTREGA ORDER BY ID";
        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Métodos de entrega obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los métodos de entrega.', null, $e->getMessage());
        }
    }
}
