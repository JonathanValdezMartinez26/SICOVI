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
                , V.ENTREGA_MONTO
                , V.COMPROBACION_MONTO
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
                , V.ESTATUS AS ESTATUS_ID
                , CEV.NOMBRE AS ESTATUS_NOMBRE
                , V.AUTORIZACION_USUARIO
                , GET_NOMBRE_USUARIO(V.AUTORIZACION_USUARIO) AS AUTORIZACION_NOMBRE
                , TO_CHAR(V.AUTORIZACION_FECHA, 'YYYY-MM-DD HH24:SS:MM') AS AUTORIZACION_FECHA
                , V.AUTORIZACION_MONTO
                , V.ENTREGA_USUARIO
                , GET_NOMBRE_USUARIO(V.ENTREGA_USUARIO) AS ENTREGA_NOMBRE
                , V.ENTREGA_METODO
                , TO_CHAR(V.ENTREGA_FECHA, 'YYYY-MM-DD HH24:SS:MM') AS ENTREGA_FECHA
                , CASE
                    WHEN V.ENTREGA_FECHA IS NULL THEN NULL
                    ELSE CME.NOMBRE
                END AS METODO_ENTREGA
                , V.ENTREGA_MONTO
                , TO_CHAR(V.COMPROBACION_LIMITE, 'YYYY-MM-DD') AS COMPROBACION_LIMITE
                , V.COMPROBACION_MONTO
            FROM
                VIATICOS V
                LEFT JOIN CAT_ESTATUS_VIATICOS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN CAT_METODO_ENTREGA CME ON CME.ID = V.ENTREGA_METODO
            WHERE
                V.ID = :id
        SQL;

        $params = [
            'id' => $datos['solicitudId']
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

    public static function getComprobantesSolicitud_VG($datos)
    {
        $query = <<<SQL
            SELECT
                VC.ID
                , TO_CHAR(VC.FECHA_REGISTRO, 'YYYY-MM-DD') AS FECHA_REGISTRO
                , CCV.NOMBRE AS CONCEPTO
                , VC.TOTAL
                , A.ID AS ARCHIVO_ID
            FROM
                VIATICOS_COMPROBACION VC
                INNER JOIN ARCHIVO A ON A.ID = VC.ARCHIVO
                INNER JOIN CAT_CONCEPTO_VIATICOS CCV ON CCV.ID = VC.CONCEPTO
            WHERE
                VC.VIATICOS = :idSolicitud
        SQL;

        $params = [
            'idSolicitud' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($query, $params);
            return self::resultado(true, 'Comprobantes encontrados.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraSolicitud_VG($datos, $comprobantes = null)
    {
        $queryV = <<<SQL
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, ESTATUS, DESDE, HASTA, MONTO, COMPROBACION_LIMITE, COMPROBACION_MONTO)
            VALUES (:tipo, :usuario, :proyecto, :estatus, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto, TO_DATE(:limite, 'YYYY-MM-DD'), :comprobacion)
            RETURNING ID INTO :id
        SQL;

        $valuesV = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario'],
            'estatus' => $datos['tipo'] == 1 ? 1 : 4,
            'limite' => $datos['limite'],
            'comprobacion' => $datos['comprobado']
        ];

        $returningV = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database();

            $db->beginTransaction();

            $db->CRUD($queryV, $valuesV, $returningV);

            if ($returningV['id']['valor'] !== '' && count($comprobantes) > 0) {
                foreach ($comprobantes as $comprobante) {
                    $queryA = <<<SQL
                        INSERT INTO ARCHIVO (ARCHIVO, NOMBRE, TIPO, TAMANO)
                        VALUES (EMPTY_BLOB(), :nombre, :tipo, :tamano)
                        RETURNING ARCHIVO, ID INTO :archivo, :id
                    SQL;

                    $valuesA = [
                        'nombre' => $comprobante['nombre'],
                        'tipo' => $comprobante['tipo'],
                        'tamano' => $comprobante['tamano']
                    ];

                    $returningA = [
                        'archivo' => [
                            'valor' => $comprobante['comprobante'],
                            'tipo' => \PDO::PARAM_LOB
                        ],
                        'id' => [
                            'valor' => '',
                            'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                            'largo' => 40
                        ]
                    ];

                    $db->CRUD($queryA, $valuesA, $returningA);

                    $queryC = <<<SQL
                        INSERT INTO VIATICOS_COMPROBACION (VIATICOS, ARCHIVO, FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, IVA, TOTAL)
                        VALUES (:id_viaticos, :id_archivo, TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :iva, :total)
                    SQL;

                    $valuesC = [
                        'id_viaticos' => $returningV['id']['valor'],
                        'id_archivo' => $returningA['id']['valor'],
                        'fecha' => $comprobante['fecha'],
                        'concepto' => $comprobante['concepto'],
                        'observaciones' => $comprobante['observaciones'],
                        'subtotal' => $comprobante['subtotal'],
                        'iva' => isset($comprobante['iva']) ? $comprobante['iva'] : 0,
                        'total' => isset($comprobante['total']) ? $comprobante['total'] : $comprobante['subtotal']
                    ];

                    $db->CRUD($queryC, $valuesC, $returningC);
                }
            }

            $db->commit();

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', ['solicitudId' => $returningV['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar la solicitud de gastos.', null, $e->getMessage());
        }
    }

    public static function cancelarSolicitud_VG($datos)
    {
        $query = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 7
            WHERE
                ID = :id
                AND ((TIPO = 1 AND ESTATUS IN (1, 2)) OR (TIPO = 2 AND ESTATUS IN (4, 2)))

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

    public static function registraComporbante_V($datos)
    {
        $queryA = <<<SQL
            INSERT INTO ARCHIVO (ARCHIVO, NOMBRE, TIPO, TAMANO)
            VALUES (EMPTY_BLOB(), :nombre, :tipo, :tamano)
            RETURNING ARCHIVO, ID INTO :archivo, :id
        SQL;

        $valuesA = [
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'tamano' => $datos['tamano']
        ];

        $returningA = [
            'archivo' => [
                'valor' => $datos['comprobante'],
                'tipo' => \PDO::PARAM_LOB
            ],
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database();

            $db->beginTransaction();

            $db->CRUD($queryA, $valuesA, $returningA);

            $queryC = <<<SQL
                INSERT INTO VIATICOS_COMPROBACION (VIATICOS, ARCHIVO, FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, IVA, TOTAL)
                VALUES (:id_viaticos, :id_archivo, TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :iva, :total)
            SQL;

            $valuesC = [
                'id_viaticos' => $datos['solicitudId'],
                'id_archivo' => $returningA['id']['valor'],
                'fecha' => $datos['fecha'],
                'concepto' => $datos['concepto'],
                'observaciones' => $datos['observaciones'],
                'subtotal' => $datos['subtotal'],
                'iva' => isset($datos['iva']) ? $datos['iva'] : 0,
                'total' => isset($datos['total']) ? $datos['total'] : $datos['subtotal']
            ];

            $db->CRUD($queryC, $valuesC, $returningC);

            $queryV = <<<SQL
                UPDATE
                    VIATICOS
                SET
                    COMPROBACION_MONTO = NVL(COMPROBACION_MONTO, 0) + :monto
                WHERE
                    ID = :id_viaticos
            SQL;

            $valuesV = [
                'id_viaticos' => $valuesC['id_viaticos'],
                'monto' => $valuesC['total']
            ];

            $db->CRUD($queryV, $valuesV);

            $db->commit();

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', ['comprobanteId' => $returningA['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar la solicitud de gastos.', null, $e->getMessage());
        }
    }

    public static function eliminaComprobante_V($datos)
    {
        $qry = <<<SQL
            SELECT
                ARCHIVO
                , TOTAL
            FROM
                VIATICOS_COMPROBACION
            WHERE
                ID = :comprobanteId
                AND VIATICOS = :solicitudId
        SQL;

        $qry1 = <<<SQL
            DELETE FROM
                VIATICOS_COMPROBACION
            WHERE
                ID = :comprobanteId
                AND VIATICOS = :solicitudId
        SQL;

        $qry2 = <<<SQL
            DELETE FROM ARCHIVO WHERE ID = :archivoId
        SQL;

        $qry3 = <<<SQL
            UPDATE
                VIATICOS
            SET
                COMPROBACION_MONTO = NVL(COMPROBACION_MONTO, 0) - :total
            WHERE
                ID = :solicitudId
        SQL;

        $params = [
            'comprobanteId' => $datos['comprobanteId'],
            'solicitudId' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $archivo = $db->queryOne($qry, $params);
            if (!$archivo) return self::resultado(false, 'No se encontró el comprobante a eliminar.');

            $qrys = [
                $qry1,
                $qry2,
                $qry3
            ];

            $params = [
                $params,
                ['archivoId' => $archivo['ARCHIVO']],
                ['solicitudId' => $params['solicitudId'], 'total' => $archivo['TOTAL']]
            ];

            $result = $db->CRUD_multiple($qrys, $params);
            if (!$result) return self::resultado(false, 'No se pudo eliminar el comprobante.');
            return self::resultado(true, 'Comprobante eliminado correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar el comprobante.', null, $e->getMessage());
        }
    }

    public static function finalizaComprobacion_V($datos)
    {
        $query = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 4
            WHERE
                ID = :id
                AND ESTATUS = 3
        SQL;

        $params = [
            'id' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($query, $params);
            if ($result < 1) return self::resultado(false, 'No se encontró la solicitud a finalizar.');
            return self::resultado(true, 'Solicitud finalizada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al finalizar la solicitud.', null, $e->getMessage());
        }
    }

    public static function getSolicitudesEntrega($datos)
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
                , TO_CHAR(V.AUTORIZACION_FECHA, 'YYYY-MM-DD') AS AUTORIZACION_FECHA
                , V.AUTORIZACION_MONTO
                , CEV.ID AS ESTATUS_ID
                , CEV.NOMBRE AS ESTATUS_NOMBRE
                , CEV.CLASE_FRONT AS ESTATUS_COLOR
            FROM
                VIATICOS V
                LEFT JOIN CAT_ESTATUS_VIATICOS CEV ON CEV.ID = V.ESTATUS
            WHERE
                V.ESTATUS IN (2)
                AND TRUNC(V.REGISTRO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
            ORDER BY
                ID DESC
        SQL;

        $params = [
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
}
