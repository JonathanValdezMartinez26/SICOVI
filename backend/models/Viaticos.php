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
                , CEV.NOMBRE AS ESTATUS_NOMBRE
                , CEV.CLASE_FRONT AS ESTATUS_COLOR
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

    public static function getResumenSolicitud_VG($datos)
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
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD HH24:MI:SS') AS REGISTRO
                , V.PROYECTO
                , TO_CHAR(V.DESDE, 'YYYY-MM-DD') AS DESDE
                , TO_CHAR(V.HASTA, 'YYYY-MM-DD') AS HASTA
                , V.MONTO
                , V.AUTORIZACION_FECHA
                , V.AUTORIZACION_USUARIO
                , GET_NOMBRE_USUARIO(V.AUTORIZACION_USUARIO) AS AUTORIZACION_NOMBRE
                , V.ENTREGA_FECHA
                , CASE
                    WHEN V.ENTREGA_FECHA IS NULL THEN NULL
                    ELSE CME.NOMBRE
                END AS METODO_ENTREGA
                , V.ENTREGA_MONTO
                , V.COMPROBACION_LIMITE AS FECHA_LIMITE
                , V.COMPROBACION_MONTO
            FROM
                VIATICOS V
                LEFT JOIN CAT_METODO_ENTREGA CME ON CME.ID = V.ENTREGA_METODO
            WHERE
                V.ID = :id
        SQL;

        $params = [
            'id' => $datos['id']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($query, $params);
            if (!$r) return self::resultado(false, 'No se encontró la solicitud.');
            return self::resultado(true, 'Solicitud encontrada.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraSolicitud_V($datos)
    {
        $query = <<<SQL
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, DESDE, HASTA, MONTO)
            VALUES (:tipo, :usuario, :proyecto, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto)
        SQL;

        $values = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($query, $values);
            if ($result < 1) return self::resultado(false, 'La solicitud no se guardo.');
            return self::resultado(true, 'Solicitud guardada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraSolicitud_G($datos, $comprobantes = null)
    {
        $queryV = <<<SQL
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, ESTATUS, DESDE, HASTA, MONTO)
            VALUES (:tipo, :usuario, :proyecto, :estatus, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto)
            RETURNING ID INTO :id
        SQL;

        $valuesV = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario'],
            'estatus' => $datos['tipo'] === 1 ? 1 : 4
        ];


        $returningV = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 4000
            ]
        ];

        try {
            $db = new Database();

            $db->beginTransaction();

            $db->CRUD($queryV, $valuesV, $returningV);

            if ($returningV['id']['valor'] !== '' && count($comprobantes) > 0) {
                foreach ($comprobantes as $comprobante) {
                    $queryC = <<<SQL
                        INSERT INTO COMPROBACION_VIATICOS (VIATICOS, COMPROBANTE_ARCHIVO, COMPROBANTE_FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, TOTAL)
                        VALUES (:viaticos, EMPTY_BLOB(), TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :total)
                        RETURNING COMPROBANTE_ARCHIVO INTO :comprobante
                    SQL;

                    $valuesC = [
                        'viaticos' => $returningV['id']['valor'],
                        'fecha' => $comprobante['fecha'],
                        'concepto' => $comprobante['concepto'],
                        'observaciones' => $comprobante['observaciones'],
                        'subtotal' => $comprobante['monto'],
                        'total' => $comprobante['monto']
                    ];

                    $returningC = [
                        'comprobante' => [
                            'valor' => $comprobante['comprobante'],
                            'tipo' => \PDO::PARAM_LOB
                        ]
                    ];

                    $db->CRUD($queryC, $valuesC, $returningC);
                }
            }

            $db->commit();

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', $returningV['id']['valor']);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar la solicitud de gastos.', null, $e->getMessage());
        }
    }

    public static function eliminaSolicitud_VG($datos)
    {
        $query = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 7
            WHERE
                ID = :id
        SQL;

        $params = [
            'id' => $datos['idSolicitud']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($query, $params);
            if ($result < 1) return self::resultado(false, 'No se encontró la solicitud a eliminar.');
            return self::resultado(true, 'Solicitud eliminada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar la solicitud.', null, $e->getMessage());
        }
    }
}
