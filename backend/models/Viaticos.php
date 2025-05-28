<?php

namespace Models;

use Core\Model;
use Core\Database;

class Viaticos extends Model
{
    public static function getSolicitudesUsuario_VG($datos)
    {
        $query = <<<SQL
            SELECT
                V.ID
                , V.TIPO AS TIPO_ID
                , CASE 
                    WHEN V.TIPO = 1 THEN 'Viáticos'
                    WHEN V.TIPO = 2 THEN 'Gastos'
                    ELSE 'Desconocido'
                END AS TIPO_NOMBRE
                , V.PROYECTO
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD') AS REGISTRO
                , V.MONTO
                , CEV.ID AS ESTATUS_ID
                , CEV.NOMBRE AS ESTATUS
            FROM
                VIATICOS V
                LEFT JOIN CAT_ESTATUS_VIATICOS CEV ON CEV.ID = V.ESTATUS
            WHERE
                USUARIO = :usuario
                AND TRUNC(V.REGISTRO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
            ORDER BY
                ID DESC
        SQL;

        $params = [
            'usuario' => $datos['usuario'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($query, $params);
            return self::resultado(true, 'Solicitudes encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraSolicitud_VG($datos)
    {
        $query = <<<SQL
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, DESDE, HASTA, MONTO)
            VALUES (:tipo, :usuario, :proyecto, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto)
            RETURNING ID INTO :id
        SQL;

        $values = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario']
        ];

        $returning = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 4000
            ]
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($query, $values, $returning);
            if ($result < 1) return self::resultado(false, 'La solicitud no se guardo.');

            $response = [
                'id' => $returning['id']['valor']
            ];
            return self::resultado(true, 'Solicitud guardada correctamente.', $response);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraComprobaciones()
    {
        $query = <<<SQL
            INSERT INTO VIATICOS_COMPROBACIONES (VIATICOS, COMPROBANTE, MONTO)
            VALUES (:viatico, :comprobante, :monto)
            RETURNING ID INTO :id
        SQL;

        $values = [
            'viatico' => $_POST['viatico'],
            'comprobante' => $_POST['comprobante'],
            'monto' => $_POST['monto']
        ];

        return self::resultado(true, 'Comprobaciones registradas correctamente.');

        try {
            $db = new Database();
            $r = $db->CRUD($query, $values);
            if ($r > 0) return self::resultado(true, 'Comprobaciones registradas correctamente.');
            return self::resultado(false, 'Las comprobaciones no fueron registradas.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al guardar las comprobaciones.', null, $e->getMessage());
        }
    }
}
