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
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, DESDE, HASTA, MONTO)
            VALUES (:tipo, :usuario, :proyecto, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto)
            RETURNING ID INTO :id
        SQL;

        $valuesV = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario']
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
}
