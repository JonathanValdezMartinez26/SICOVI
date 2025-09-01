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

    public static function getCatalogoEmpresas()
    {
        $qry = <<<SQL
            SELECT
                ID,
                RAZON_SOCIAL
            FROM
                EMPRESA
            WHERE
                ESTATUS = 1
            ORDER BY
                RAZON_SOCIAL
        SQL;

        try {
            $db = new Database();
            $r = $db->queryAll($qry);
            return self::resultado(true, 'Empresas encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las empresas.', null, $e->getMessage());
        }
    }

    public static function getCatalogoSucursales()
    {
        $query = <<<SQL
            SELECT
                *
            FROM
                SUCURSALES_REGIONES
            ORDER BY
                EMPRESA, REGION_NOMBRE, SUCURSAL_NOMBRE
        SQL;

        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Sucursales obtenidas.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las sucursales.', null, $e->getMessage());
        }
    }

    public static function getCatalogoBancos()
    {
        $query = "SELECT * FROM CAT_BANCO ORDER BY NOMBRE";
        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Bancos obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los bancos.', null, $e->getMessage());
        }
    }

    public static function getPerfiles()
    {
        $query = "SELECT * FROM PERFIL ORDER BY NOMBRE";
        try {
            $db = new Database();
            $result = $db->queryAll($query);
            return self::resultado(true, 'Perfiles obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los perfiles.', null, $e->getMessage());
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

    public static function getPersonalSucursal($datos)
    {
        $query = <<<SQL
            SELECT
                PERSONA,
                GET_NOMBRE_PERSONA(PERSONA) AS NOMBRE
            FROM
                NOMINA
            WHERE
                SUCURSAL = :sucursalId
        SQL;

        $params = [
            'sucursalId' => $datos['sucursal']
        ];

        try {
            $db = new Database();
            $result = $db->queryAll($query, $params);
            return self::resultado(true, 'Personal de la sucursal obtenidos.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el personal de la sucursal.', null, $e->getMessage());
        }
    }
}
