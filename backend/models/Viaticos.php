<?php

namespace Models;

use Core\Model;
use Core\Database;

class Viaticos extends Model
{
    public static function getSolicitudesUsuario_VG($datos)
    {
        $qry = <<<SQL
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
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
            WHERE
                USUARIO = :usuario
                AND TRUNC(V.REGISTRO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
            ORDER BY
                ID DESC
        SQL;

        $val = [
            'usuario' => $datos['usuario'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $val);
            return self::resultado(true, 'Solicitudes encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function getResumenSolicitud_VG($datos)
    {
        $qry = <<<SQL
            SELECT
                V.ID
                , V.TIPO AS TIPO_ID
                , CASE 
                    WHEN V.TIPO = 1 THEN 'Viáticos'
                    WHEN V.TIPO = 2 THEN 'Gastos'
                    ELSE 'Desconocido'
                END AS TIPO_NOMBRE
                , V.USUARIO AS USUARIO_ID
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , S.NOMBRE AS SUCURSAL_NOMBRE
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
                    ELSE CMEV.NOMBRE
                END AS METODO_ENTREGA
                , V.ENTREGA_MONTO
                , TO_CHAR(V.COMPROBACION_LIMITE, 'YYYY-MM-DD') AS COMPROBACION_LIMITE
                , V.COMPROBACION_MONTO
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN CAT_VIATICOS_METODO_ENTREGA CMEV ON CMEV.ID = V.ENTREGA_METODO
                LEFT JOIN USUARIO U ON U.ID = V.USUARIO
                LEFT JOIN SUCURSAL S ON S.ID = U.SUCURSAL
            WHERE
                V.ID = :id
        SQL;

        $val = [
            'id' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $val);
            if (!$r) return self::resultado(false, 'No se encontró la solicitud.');
            return self::resultado(true, 'Resumen obtenido correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el resumen de la solicitud.', null, $e->getMessage());
        }
    }

    public static function getComprobantesSolicitud_VG($datos)
    {
        $qry = <<<SQL
            SELECT
                VC.ID
                , TO_CHAR(VC.FECHA_REGISTRO, 'YYYY-MM-DD') AS FECHA_REGISTRO
                , CCV.NOMBRE AS CONCEPTO
                , VC.TOTAL
                , A.ID AS ARCHIVO_ID
            FROM
                VIATICOS_COMPROBACION VC
                INNER JOIN ARCHIVO A ON A.ID = VC.ARCHIVO
                INNER JOIN CAT_VIATICOS_CONCEPTO CCV ON CCV.ID = VC.CONCEPTO
            WHERE
                VC.VIATICOS = :idSolicitud
        SQL;

        $val = [
            'idSolicitud' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $val);
            return self::resultado(true, 'Comprobantes obtenidos.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los comprobantes de la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraSolicitud_VG($datos, $comprobantes = null)
    {
        $qryV = <<<SQL
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, ESTATUS, DESDE, HASTA, MONTO, COMPROBACION_LIMITE, COMPROBACION_MONTO)
            VALUES (:tipo, :usuario, :proyecto, :estatus, TO_DATE(:fechaI, 'YYYY-MM-DD'), TO_DATE(:fechaF, 'YYYY-MM-DD'), :monto, TO_DATE(:limite, 'YYYY-MM-DD'), :comprobacion)
            RETURNING ID INTO :id
        SQL;

        $valV = [
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

        $retV = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database();

            $db->beginTransaction();

            $db->CRUD($qryV, $valV, $retV);

            if ($retV['id']['valor'] !== '' && count($comprobantes) > 0) {
                foreach ($comprobantes as $comprobante) {
                    $qryA = <<<SQL
                        INSERT INTO ARCHIVO (ARCHIVO, NOMBRE, TIPO, TAMANO)
                        VALUES (EMPTY_BLOB(), :nombre, :tipo, :tamano)
                        RETURNING ARCHIVO, ID INTO :archivo, :id
                    SQL;

                    $valA = [
                        'nombre' => $comprobante['nombre'],
                        'tipo' => $comprobante['tipo'],
                        'tamano' => $comprobante['tamano']
                    ];

                    $retA = [
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

                    $db->CRUD($qryA, $valA, $retA);

                    $queryC = <<<SQL
                        INSERT INTO VIATICOS_COMPROBACION (VIATICOS, ARCHIVO, FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, IVA, TOTAL)
                        VALUES (:id_viaticos, :id_archivo, TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :iva, :total)
                    SQL;

                    $valuesC = [
                        'id_viaticos' => $retV['id']['valor'],
                        'id_archivo' => $retA['id']['valor'],
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

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', ['solicitudId' => $retV['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar la solicitud de gastos.', null, $e->getMessage());
        }
    }

    public static function cancelarSolicitud_VG($datos)
    {
        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 7
            WHERE
                ID = :id
                AND ((TIPO = 1 AND ESTATUS IN (1, 2)) OR (TIPO = 2 AND ESTATUS IN (4, 2)))

        SQL;

        $val = [
            'id' => $datos['idSolicitud']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($qry, $val);
            if ($result < 1) return self::resultado(false, 'No se encontró la solicitud a eliminar.');
            return self::resultado(true, 'Solicitud eliminada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar la solicitud.', null, $e->getMessage());
        }
    }

    public static function registraComporbante_V($datos)
    {
        $qryA = <<<SQL
            INSERT INTO ARCHIVO (ARCHIVO, NOMBRE, TIPO, TAMANO)
            VALUES (EMPTY_BLOB(), :nombre, :tipo, :tamano)
            RETURNING ARCHIVO, ID INTO :archivo, :id
        SQL;

        $valA = [
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'tamano' => $datos['tamano']
        ];

        $retA = [
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

            $db->CRUD($qryA, $valA, $retA);

            $qryC = <<<SQL
                INSERT INTO VIATICOS_COMPROBACION (VIATICOS, ARCHIVO, FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, IVA, TOTAL)
                VALUES (:id_viaticos, :id_archivo, TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :iva, :total)
            SQL;

            $valC = [
                'id_viaticos' => $datos['solicitudId'],
                'id_archivo' => $retA['id']['valor'],
                'fecha' => $datos['fecha'],
                'concepto' => $datos['concepto'],
                'observaciones' => $datos['observaciones'],
                'subtotal' => $datos['subtotal'],
                'iva' => isset($datos['iva']) ? $datos['iva'] : 0,
                'total' => isset($datos['total']) ? $datos['total'] : $datos['subtotal']
            ];

            $db->CRUD($qryC, $valC);

            $qryV = <<<SQL
                UPDATE
                    VIATICOS
                SET
                    COMPROBACION_MONTO = NVL(COMPROBACION_MONTO, 0) + :monto
                WHERE
                    ID = :id_viaticos
            SQL;

            $valV = [
                'id_viaticos' => $valC['id_viaticos'],
                'monto' => $valC['total']
            ];

            $db->CRUD($qryV, $valV);

            $db->commit();

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', ['comprobanteId' => $retA['id']['valor']]);
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

        $val = [
            'comprobanteId' => $datos['comprobanteId'],
            'solicitudId' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $archivo = $db->queryOne($qry, $val);
            if (!$archivo) return self::resultado(false, 'No se encontró el comprobante a eliminar.');

            $qrys = [
                $qry1,
                $qry2,
                $qry3
            ];

            $val = [
                $val,
                ['archivoId' => $archivo['ARCHIVO']],
                ['solicitudId' => $val['solicitudId'], 'total' => $archivo['TOTAL']]
            ];

            $result = $db->CRUD_multiple($qrys, $val);
            if (!$result) return self::resultado(false, 'No se pudo eliminar el comprobante.');
            return self::resultado(true, 'Comprobante eliminado correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar el comprobante.', null, $e->getMessage());
        }
    }

    public static function getComprobante_V($datos)
    {
        $qry = <<<SQL
            SELECT
                A.ARCHIVO
                , A.NOMBRE
                , A.TIPO
                , A.TAMANO
            FROM
                VIATICOS_COMPROBACION VC
                INNER JOIN ARCHIVO A ON A.ID = VC.ARCHIVO
            WHERE
                VC.ID = :comprobanteId
        SQL;

        $val = [
            'comprobanteId' => $datos['comprobanteId']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $val);
            if (!$r) return self::resultado(false, 'No se encontró el comprobante.');
            return self::resultado(true, 'Comprobante encontrado.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function finalizaComprobacion_V($datos)
    {
        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 4
            WHERE
                ID = :id
                AND ESTATUS = 3
        SQL;

        $val = [
            'id' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($qry, $val);
            if ($result < 1) return self::resultado(false, 'No se encontró la solicitud a finalizar.');
            return self::resultado(true, 'Solicitud finalizada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al finalizar la solicitud.', null, $e->getMessage());
        }
    }

    public static function getSolicitudesEntrega($datos)
    {
        $qry = <<<SQL
            SELECT
                V.ID
                , V.TIPO AS TIPO_ID
                , CASE 
                    WHEN V.TIPO = 1 THEN 'Viáticos'
                    WHEN V.TIPO = 2 THEN 'Gastos'
                    ELSE 'Desconocido'
                END AS TIPO_NOMBRE
                , V.USUARIO AS USUARIO_ID
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , TO_CHAR(V.AUTORIZACION_FECHA, 'YYYY-MM-DD') AS AUTORIZACION_FECHA
                , V.AUTORIZACION_MONTO
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN USUARIO U ON U.ID = V.USUARIO
            WHERE
                V.ESTATUS IN (2)
                AND TRUNC(V.REGISTRO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
                AND U.SUCURSAL = :sucursal
            ORDER BY
                ID DESC
        SQL;

        $params = [
            'sucursal' => $datos['sucursal'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $params);
            return self::resultado(true, 'Solicitudes encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function entrega_VG($datos)
    {
        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = 3
                , ENTREGA_METODO = :metodo
                , ENTREGA_FECHA = SYSDATE
                , ENTREGA_MONTO = :monto
                , ENTREGA_USUARIO = :usuario
            WHERE
                ID = :id
                AND ESTATUS = 2
        SQL;

        $val = [
            'id' => $datos['solicitud'],
            'usuario' => $datos['usuario'],
            'metodo' => $datos['metodo'],
            'monto' => $datos['monto']
        ];

        if (isset($datos['observaciones']) && $datos['observaciones'] != '') {
            $qryO = <<<SQL
                INSERT INTO VIATICOS_OBSERVACIONES (VIATICOS, COMENTARIO, USUARIO)
                VALUES (:id, :observaciones, :usuario)
            SQL;
            $valO = [
                'id' => $datos['solicitud'],
                'observaciones' => $datos['observaciones'],
                'usuario' => $datos['usuario']
            ];
        }

        try {
            $db = new Database();
            $result = $db->CRUD($qry, $val);
            if (isset($qryO)) $db->CRUD($qryO, $valO);
            if ($result < 1) return self::resultado(false, 'No se encontró la solicitud a entregar.');
            return self::resultado(true, 'Solicitud entregada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al entregar la solicitud.', null, $e->getMessage());
        }
    }

    public static function getDatosComprobanteEntrega($datos)
    {
        $qry = <<<SQL
            SELECT
                V.ID
                , V.TIPO AS TIPO_ID
                , CASE 
                    WHEN V.TIPO = 1 THEN 'Viáticos'
                    WHEN V.TIPO = 2 THEN 'Gastos'
                    ELSE 'Desconocido'
                END AS TIPO_NOMBRE
                , V.USUARIO AS USUARIO_ID
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , V.PROYECTO
                , V.ENTREGA_USUARIO
                , GET_NOMBRE_USUARIO(V.ENTREGA_USUARIO) AS ENTREGA_NOMBRE
                , TO_CHAR(V.ENTREGA_FECHA, 'YYYY-MM-DD') AS ENTREGA_FECHA
                , V.ENTREGA_MONTO
                , V.ENTREGA_METODO
                , CMEV.NOMBRE AS METODO_ENTREGA
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_METODO_ENTREGA CMEV ON CMEV.ID = V.ENTREGA_METODO
            WHERE
                V.ID = :id
        SQL;

        $val = [
            'id' => $datos['solicitud']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $val);
            if (!$r) return self::resultado(false, 'No se encontró la solicitud.');
            return self::resultado(true, 'Datos de la solicitud obtenidos correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los datos de la solicitud.', null, $e->getMessage());
        }
    }
}
