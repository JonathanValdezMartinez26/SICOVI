<?php

namespace Models;

use Core\Model;
use Core\Database;

class Viaticos extends Model
{
    public static function getSolicitudesActivas_VG($datos)
    {
        $qry = <<<SQL
            SELECT
                COUNT(*) AS ACTIVAS
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CVE ON CVE.ID = V.ESTATUS
            WHERE
                CVE.NOMBRE NOT IN ('FINALIZADA', 'CANCELADA', 'RECHAZADA')
                AND V.USUARIO = :usuario
        SQL;

        $val = [
            'usuario' => $datos['usuario']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $val);
            return self::resultado(true, 'Solicitudes activas encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

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
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD HH24:MI:SS') AS REGISTRO
                , V.PROYECTO
                , TO_CHAR(V.DESDE, 'YYYY-MM-DD') AS DESDE
                , TO_CHAR(V.HASTA, 'YYYY-MM-DD') AS HASTA
                , V.MONTO
                , V.ENTREGA_MONTO
                , V.COMPROBACION_MONTO
                , V.DIFERENCIA_MONTO
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
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD HH24:MI:SS') AS REGISTRO
                , V.PROYECTO
                , TO_CHAR(V.DESDE, 'YYYY-MM-DD') AS DESDE
                , TO_CHAR(V.HASTA, 'YYYY-MM-DD') AS HASTA
                , V.MONTO
                , V.ESTATUS AS ESTATUS_ID
                , CEV.NOMBRE AS ESTATUS_NOMBRE
                , TO_CHAR(V.ACTUALIZADO, 'YYYY-MM-DD HH24:MI:SS') AS ACTUALIZADO
                , V.AUTORIZACION_USUARIO
                , GET_NOMBRE_USUARIO(V.AUTORIZACION_USUARIO) AS AUTORIZACION_NOMBRE
                , TO_CHAR(V.AUTORIZACION_FECHA, 'YYYY-MM-DD HH24:SS:MM') AS AUTORIZACION_FECHA
                , V.AUTORIZACION_MONTO
                , VO.OBSERVACION AS AUTORIZACION_OBSERVACION
                , V.ENTREGA_USUARIO
                , GET_NOMBRE_USUARIO(V.ENTREGA_USUARIO) AS ENTREGA_NOMBRE
                , S.NOMBRE AS ENTREGA_SUCURSAL
                , V.ENTREGA_METODO
                , TO_CHAR(V.ENTREGA_FECHA, 'YYYY-MM-DD HH24:SS:MM') AS ENTREGA_FECHA
                , CASE
                    WHEN V.ENTREGA_FECHA IS NULL THEN NULL
                    ELSE CMEV.NOMBRE
                END AS METODO_ENTREGA
                , V.ENTREGA_MONTO
                , TO_CHAR(V.COMPROBACION_LIMITE, 'YYYY-MM-DD') AS COMPROBACION_LIMITE
                , V.COMPROBACION_MONTO
                , V.DIFERENCIA_METODO
                , V.DIFERENCIA_FECHA
                , V.DIFERENCIA_MONTO
                , V.DIFERENCIA_USUARIO
                , GET_NOMBRE_USUARIO(V.DIFERENCIA_USUARIO) AS DIFERENCIA_NOMBRE
                , V.DIFERENCIA_SUCURSAL
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN CAT_VIATICOS_METODO_ENTREGA CMEV ON CMEV.ID = V.ENTREGA_METODO
                LEFT JOIN USUARIO U ON U.ID = V.USUARIO
                LEFT JOIN SUCURSAL S ON S.ID = V.SUCURSAL
                LEFT JOIN VIATICOS_OBSERVACIONES VO ON VO.ID = V.AUTORIZACION_OBSERVACION
            WHERE
                V.ID = :solicitudId
        SQL;

        $val = [
            'solicitudId' => $datos['solicitudId']
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

    public static function getConceptosSolicitud_V($datos)
    {
        $qry = <<<SQL
            SELECT
                VC.ID
                , VC.CONCEPTO AS CONCEPTO_ID
                , CASE VC.CONCEPTO
                    WHEN 1 THEN 'Transporte'
                    WHEN 2 THEN 'Alimentos'
                    WHEN 3 THEN 'Hospedaje'
                    WHEN 4 THEN 'Otros'
                    ELSE 'Desconocido'
                END AS CONCEPTO_NOMBRE
                , VC.OBSERVACIONES
                , VC.MONTO
            FROM
                VIATICOS_CONCEPTOS VC
            WHERE
                VC.VIATICOS = :solicitudId
            ORDER BY
                VC.ID
        SQL;

        $val = [
            'solicitudId' => $datos['solicitudId']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $val);
            return self::resultado(true, 'Conceptos obtenidos.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los conceptos de la solicitud.', null, $e->getMessage());
        }
    }

    public static function getComprobantesSolicitud_VG($datos)
    {
        $qry = <<<SQL
            SELECT
                VC.ID
                , VC.VIATICOS AS SOLICITUD_ID
                , TO_CHAR(VC.FECHA_REGISTRO, 'YYYY-MM-DD HH24:MM:SS') AS FECHA_REGISTRO
                , CCV.ID AS CONCEPTO_ID
                , CCV.NOMBRE AS CONCEPTO_NOMBRE
                , TO_CHAR(VC.FECHA, 'YYYY-MM-DD') AS FECHA_COMPROBANTE
                , VC.TOTAL
                , VC.OBSERVACIONES
                , VC.ESTATUS AS ESTATUS_ID
                , CASE
                    WHEN VC.ESTATUS IS NULL THEN 'Registrado'
                    WHEN VC.ESTATUS = 0 THEN 'Rechazado'
                    WHEN VC.ESTATUS = 1 THEN 'Aceptado'
                    ELSE 'Desconocido'
                END AS ESTATUS_NOMBRE
                ,(
                    SELECT VO.OBSERVACION
                    FROM VIATICOS_OBSERVACIONES VO
                    WHERE VO.VIATICOS >= VC.VIATICOS AND VC.ESTATUS = 0
                    ORDER BY ABS(VO.FECHA - VC.VALIDADO) ASC
                    FETCH FIRST 1 ROWS ONLY
                ) AS MOTIVO_RECHAZO
                , A.ID AS ARCHIVO_ID
                , A.TIPO AS ARCHIVO_TIPO
            FROM
                VIATICOS_COMPROBACION VC
                LEFT JOIN ARCHIVO A ON A.ID = VC.ARCHIVO
                LEFT JOIN CAT_VIATICOS_CONCEPTO CCV ON CCV.ID = VC.CONCEPTO
            WHERE
                VC.VIATICOS = :solicitudId
        SQL;

        $val = [
            'solicitudId' => $datos['solicitudId']
        ];

        if (isset($datos['comprobacion'])) {
            $qry .= ' AND (VC.ESTATUS IS NULL OR VC.ESTATUS = 0)';
        }

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
            INSERT INTO VIATICOS (TIPO, USUARIO, PROYECTO, ESTATUS, DESDE, HASTA, MONTO, COMPROBACION_LIMITE, COMPROBACION_MONTO, SUCURSAL)
            VALUES (:tipo, :usuario, :proyecto, :estatus, TO_DATE(:desde, 'YYYY-MM-DD'), TO_DATE(:hasta, 'YYYY-MM-DD'), :monto, TO_DATE(:limite, 'YYYY-MM-DD'), :comprobacion, :sucursal)
            RETURNING ID INTO :id
        SQL;

        $valV = [
            'usuario' => $_SESSION['usuario_id'],
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'desde' => $datos['desde'],
            'hasta' => $datos['hasta'],
            'monto' => $datos['monto'],
            'sucursal' => $datos['sucursal'],
            'estatus' => $datos['tipo'] == 1 ? 1 : 4,
            'limite' => $datos['fechaLimite'],
            'comprobacion' => $datos['comprobado'],
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

            if (!$retV['id']['valor']) throw new \Exception("Error al insertar la solicitud.");

            if (is_array($comprobantes) && count($comprobantes) > 0) {
                $qryA = <<<SQL
                    INSERT INTO ARCHIVO (ARCHIVO, NOMBRE, TIPO, TAMANO)
                    VALUES (EMPTY_BLOB(), :nombre, :tipo, :tamano)
                    RETURNING ARCHIVO, ID INTO :archivo, :id
                SQL;

                $queryC = <<<SQL
                    INSERT INTO VIATICOS_COMPROBACION (VIATICOS, ARCHIVO, FECHA, CONCEPTO, OBSERVACIONES, SUBTOTAL, IVA, TOTAL)
                    VALUES (:id_viaticos, :id_archivo, TO_DATE(:fecha, 'YYYY-MM-DD'), :concepto, :observaciones, :subtotal, :iva, :total)
                SQL;

                foreach ($comprobantes as $comprobante) {
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

                    $db->CRUD($queryC, $valuesC);
                }
            }

            if (isset($datos['concepto']) && is_array($datos['concepto']) && count($datos['concepto']) > 0) {
                $qryC = <<<SQL
                    INSERT INTO VIATICOS_CONCEPTOS (VIATICOS, CONCEPTO, OBSERVACIONES, MONTO)
                    VALUES (:solicitudId, :concepto, :observaciones, :monto)
                SQL;

                foreach ($datos['concepto'] as $key => $concepto) {
                    $valC = [
                        'solicitudId' => $retV['id']['valor'],
                        'concepto' => $concepto,
                        'observaciones' => $datos['observacionesConcepto'][$key],
                        'monto' => $datos['montoConcepto'][$key]
                    ];

                    $db->CRUD($qryC, $valC);
                }
            }

            $db->commit();

            return self::resultado(true, 'Solicitud registrada correctamente.', ['solicitudId' => $retV['id']['valor']]);
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
                ESTATUS = (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'CANCELADA')
            WHERE
                ID = :id
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

    public static function agregaConceptoSolicitud_V($datos)
    {
        $qryC = <<<SQL
            INSERT INTO VIATICOS_CONCEPTOS (VIATICOS, CONCEPTO, OBSERVACIONES, MONTO)
            VALUES (:solicitudId, :concepto, :observaciones, :monto)
            RETURNING ID INTO :conceptoID
        SQL;
        $valC = [
            'solicitudId' => $datos['solicitudId'],
            'concepto' => $datos['concepto'],
            'observaciones' => $datos['observaciones'],
            'monto' => $datos['total']
        ];

        $qryV = <<<SQL
            UPDATE
                VIATICOS
            SET
                MONTO = NVL(MONTO, 0) + :monto
            WHERE
                ID = :solicitudId
        SQL;
        $valV = [
            'solicitudId' => $valC['solicitudId'],
            'monto' => $valC['monto']
        ];

        $ret = [
            'conceptoID' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database();
            $db->beginTransaction();
            $result = $db->CRUD($qryC, $valC, $ret);
            if ($result < 1) throw new \Exception("No se pudo agregar el concepto a la solicitud.");
            $result = $db->CRUD($qryV, $valV);
            if ($result < 1) throw new \Exception("No se pudo actualizar el monto de la solicitud.");
            $db->commit();

            return self::resultado(true, 'Concepto agregado correctamente.', ['CONCEPTO_ID' => $ret['conceptoID']['valor']]);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al agregar el concepto a la solicitud.', null, $e->getMessage());
        }
    }

    public static function eliminaConceptoSolicitud_V($datos)
    {
        $qryC = <<<SQL
            DELETE FROM
                VIATICOS_CONCEPTOS
            WHERE
                ID = :conceptoId
        SQL;

        $qryV = <<<SQL
            MERGE INTO
                VIATICOS V
            USING (
                SELECT
                    VIATICOS
                    , SUM(MONTO) AS MONTO
                FROM
                    VIATICOS_CONCEPTOS
                WHERE
                    ID = :conceptoId
                GROUP BY
                    VIATICOS
            ) C ON (V.ID = C.VIATICOS)
            WHEN MATCHED THEN
                UPDATE SET
                    V.MONTO = C.MONTO
        SQL;

        $val = [
            'conceptoId' => $datos['conceptoId']
        ];

        try {
            $db = new Database();
            $db->beginTransaction();
            $result = $db->queryOne($qryC, $val);
            if (!$result) throw new \Exception("No se encontró el concepto a eliminar.");
            $result = $db->CRUD($qryV, $val);
            if ($result < 1) throw new \Exception("No se pudo actualizar el monto de la solicitud.");
            $db->commit();

            return self::resultado(true, 'Concepto eliminado correctamente.', $result);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al eliminar el concepto.', null, $e->getMessage());
        }
    }

    public static function actualizaConceptoSolicitud_V($datos)
    {
        $qryC = <<<SQL
            UPDATE
                VIATICOS_CONCEPTOS
            SET
                CONCEPTO = :concepto
                , OBSERVACIONES = :observaciones
                , MONTO = :monto
            WHERE
                ID = :conceptoId
        SQL;

        $qryV = <<<SQL
            MERGE INTO
                VIATICOS V
            USING (
                SELECT
                    VIATICOS
                    , SUM(MONTO) AS MONTO
                FROM
                    VIATICOS_CONCEPTOS
                WHERE
                    ID = :conceptoId
                GROUP BY
                    VIATICOS
            ) C ON (V.ID = C.VIATICOS)
            WHEN MATCHED THEN
                UPDATE SET
                    V.MONTO = C.MONTO
        SQL;

        $val = [
            'conceptoId' => $datos['conceptoId'],
            'concepto' => $datos['concepto'],
            'observaciones' => $datos['observaciones'],
            'monto' => $datos['monto']
        ];

        try {
            $db = new Database();
            $db->beginTransaction();
            $result = $db->CRUD($qryC, $val);
            if ($result < 1) throw new \Exception("No se encontró el concepto a actualizar.");
            $result = $db->CRUD($qryV, ['conceptoId' => $datos['conceptoId']], $ret);
            if ($result < 1) throw new \Exception("No se pudo actualizar el monto de la solicitud.");
            $db->commit();

            return self::resultado(true, 'Concepto actualizado correctamente.');
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al actualizar el concepto.', null, $e->getMessage());
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

            if (!$retA['id']['valor']) throw new \Exception("Error al insertar el archivo del comprobante.");

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

            $res = $db->CRUD($qryC, $valC);
            if ($res < 1) throw new \Exception("Error al insertar el comprobante de viáticos.");

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

            $res = $db->CRUD($qryV, $valV);
            if ($res < 1) throw new \Exception("Error al actualizar el monto de comprobación de la solicitud de viáticos.");

            $db->commit();

            return self::resultado(true, 'Solicitud de gastos registrada correctamente.', ['comprobanteId' => $retA['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar la solicitud de gastos.', null, $e->getMessage());
        }
    }

    public static function editarComprobante_V($datos)
    {
        $qry = <<<SQL
            UPDATE
                ARCHIVO
            SET
                ARCHIVO = EMPTY_BLOB()
                , NOMBRE = :nombre
                , TIPO = :tipo
                , TAMANO = :tamano
                , FECHA = SYSDATE
            WHERE
                ID = (SELECT ARCHIVO FROM VIATICOS_COMPROBACION WHERE ID = :comprobanteId)
            RETURNING ARCHIVO INTO :archivo
        SQL;

        $val = [
            'comprobanteId' => $datos['comprobanteId'],
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'tamano' => $datos['tamano']
        ];

        $ret = [
            'archivo' => [
                'valor' => $datos['nuevoComprobante'],
                'tipo' => \PDO::PARAM_LOB
            ]
        ];

        try {
            $db = new Database();
            $db->beginTransaction();

            $result = $db->CRUD($qry, $val, $ret);
            if ($result < 1) throw new \Exception("No se encontró el comprobante a editar.");

            $qryC = <<<SQL
                UPDATE
                    VIATICOS_COMPROBACION
                SET
                    ESTATUS = NULL
                WHERE
                    ID = :comprobanteId
            SQL;

            $valC = [
                'comprobanteId' => $datos['comprobanteId']
            ];

            $resultC = $db->CRUD($qryC, $valC);
            if ($resultC < 1) throw new \Exception("Error al actualizar el estado del comprobante.");

            $db->commit();
            return self::resultado(true, 'Comprobante editado correctamente.');
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al editar el comprobante.', null, $e->getMessage());
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

    public static function getComprobante_VG($datos)
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

    public static function getSolicitudesAutorizacion($datos)
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
                , V.PROYECTO
                , TO_CHAR(V.DESDE, 'YYYY-MM-DD') AS DESDE
                , TO_CHAR(V.HASTA, 'YYYY-MM-DD') AS HASTA
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_REGISTRO
                , V.MONTO
                , V.ESTATUS AS ESTATUS_ID
                , CEV.NOMBRE AS ESTATUS_NOMBRE
                , V.AUTORIZACION_MONTO
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN USUARIO U ON U.ID = V.USUARIO
            WHERE
                ((V.TIPO = 1 AND CEV.NOMBRE IN ('SOLICITADA', 'AUTORIZADA', 'COMPROBADA', 'RECHAZADA')) OR (V.TIPO = 2 AND CEV.NOMBRE IN ('COMPROBADA', 'RECHAZADA')))
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
            $r = $db->queryAll($qry, $params);
            return self::resultado(true, 'Solicitudes encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function autorizaSolicitud_VG($datos)
    {
        $qryO = <<<SQL
            INSERT INTO VIATICOS_OBSERVACIONES (OBSERVACION, USUARIO, VIATICOS, ESTATUS)
            VALUES (:observaciones, :usuario, :viaticos, (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'AUTORIZADA'))
            RETURNING ID INTO :id
        SQL;

        $valO = [
            'observaciones' => $datos['observaciones'],
            'usuario' => $datos['usuario'],
            'viaticos' => $datos['solicitudId']
        ];

        $retO = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = :autorizado)
                , AUTORIZACION_USUARIO = :usuario
                , AUTORIZACION_FECHA = SYSDATE
                , AUTORIZACION_MONTO = CASE WHEN TIPO = 2 AND AUTORIZACION_MONTO IS NULL THEN COMPROBACION_MONTO ELSE TO_NUMBER(:monto) END
                , AUTORIZACION_OBSERVACION = :observaciones
            WHERE
                ID = :id
        SQL;

        $val = [
            'id' => $datos['solicitudId'],
            'usuario' => $datos['usuario'],
            'autorizado' => $datos['autorizado'],
            'monto' => isset($datos['monto']) ? $datos['monto'] : null,
            'observaciones' => null
        ];

        try {
            $db = new Database();
            $db->beginTransaction();

            if (isset($datos['observaciones']) && $datos['observaciones'] != '') {
                $resultO = $db->CRUD($qryO, $valO, $retO);
                if ($resultO < 1) throw new \Exception("No se pudo insertar la observación de autorización.");
                $val['observaciones'] = $retO['id']['valor'];
            }

            $result = $db->CRUD($qry, $val);
            if ($result < 1) throw new \Exception("No se encontró la solicitud a autorizar.");

            $db->commit();
            return self::resultado(true, 'Solicitud autorizada correctamente.');
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al autorizar la solicitud.', null, $e->getMessage());
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
                (V.TIPO = 1 AND CEV.NOMBRE = 'AUTORIZADA') OR (V.TIPO = 2 AND CEV.NOMBRE = 'COMPROBADA')
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
                ESTATUS = :estatus
                , ENTREGA_METODO = :metodo
                , ENTREGA_FECHA = SYSDATE
                , ENTREGA_MONTO = :monto
                , ENTREGA_USUARIO = :usuario
            WHERE
                ID = :id
        SQL;

        $val = [
            'estatus' => $datos['estatus'],
            'id' => $datos['solicitudId'],
            'usuario' => $datos['usuario'],
            'metodo' => $datos['metodo'],
            'monto' => $datos['monto']
        ];


        try {
            $db = new Database();
            $db->beginTransaction();

            $result = $db->CRUD($qry, $val);
            if ($result < 1) throw new \Exception("No se encontró la solicitud a entregar.");

            if (isset($datos['observaciones']) && $datos['observaciones'] != '') {
                $resultO = self::insertaObservaciones($datos);
                if (!$resultO['success']) throw new \Exception($resultO['error'] ?? $resultO['mensaje']);
            }

            $db->commit();
            return self::resultado(true, 'Solicitud entregada correctamente.', $result);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al entregar la solicitud.', null, $e->getMessage());
        }
    }

    public static function insertaObservaciones($datos)
    {
        $qry = <<<SQL
            INSERT INTO VIATICOS_OBSERVACIONES (OBSERVACION, USUARIO, VIATICOS, ESTATUS, COMPROBANTE)
            VALUES (:observaciones, :usuario, :viaticos, :estatus, :comprobante)
        SQL;

        $val = [
            'observaciones' => $datos['observaciones'],
            'usuario' => $datos['usuario'],
            'viaticos' => $datos['solicitudId'],
            'estatus' => isset($datos['estatus']) ? $datos['estatus'] : null,
            'comprobante' => isset($datos['comprobanteId']) ? $datos['comprobanteId'] : null,
        ];

        try {
            $db = new Database();
            $result = $db->CRUD($qry, $val);
            if ($result < 1) return self::resultado(false, 'No se pudo insertar la observación.');
            return self::resultado(true, 'Observación insertada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al insertar la observación.', null, $e->getMessage());
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
            'id' => $datos['solicitudId']
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

    public static function getComprobaciones($datos)
    {
        $qry = <<<SQL
            WITH COMPROBANTES AS (
                SELECT
                    VC.VIATICOS
                    , COUNT(*) AS TOTAL
                    , SUM(CASE WHEN VC.ESTATUS IS NULL THEN 1 ELSE 0 END) AS REGISTRADOS
                    , SUM(CASE WHEN VC.ESTATUS = 0 THEN 1 ELSE 0 END) AS RECHAZADOS
                    , SUM(CASE WHEN VC.ESTATUS = 1 THEN 1 ELSE 0 END) AS ACEPTADOS
                FROM
                    VIATICOS_COMPROBACION VC
                GROUP BY
                    VC.VIATICOS
            )
            SELECT
                V.ID
                , V.TIPO AS TIPO_ID
                , CASE 
                    WHEN V.TIPO = 1 THEN 'Viáticos'
                    WHEN V.TIPO = 2 THEN 'Gastos'
                    ELSE 'Desconocido'
                END AS TIPO_NOMBRE
                , V.USUARIO AS SOLICITANTE_ID
                , GET_NOMBRE_USUARIO(V.USUARIO) AS SOLICITANTE_NOMBRE
                , TO_CHAR(V.REGISTRO, 'YYYY-MM-DD HH24:MM:SS') AS REGISTRO
                , V.PROYECTO
                , V.ENTREGA_MONTO
                , TO_CHAR(V.COMPROBACION_LIMITE, 'YYYY-MM-DD') AS COMPROBACION_LIMITE
                , V.COMPROBACION_MONTO
                , C.TOTAL
                , C.REGISTRADOS
                , C.RECHAZADOS
                , C.ACEPTADOS
                , C.TOTAL - C.ACEPTADOS AS PENDIENTES
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
                LEFT JOIN COMPROBANTES C ON C.VIATICOS = V.ID
            WHERE
                (C.REGISTRADOS > 0 OR C.RECHAZADOS > 0)
                AND CEV.NOMBRE = 'ACEPTADA'
                AND TRUNC(V.ACTUALIZADO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
            ORDER BY
                V.ACTUALIZADO DESC
        SQL;

        $val = [
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $val);
            return self::resultado(true, 'Comprobaciones obtenidas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las comprobaciones de la solicitud.', null, $e->getMessage());
        }
    }

    public static function actualizaEstatusComprobante($datos)
    {
        $qry = <<<SQL
            UPDATE
                VIATICOS_COMPROBACION
            SET
                ESTATUS = :estatus
                , VALIDO = :usuario
            WHERE
                ID = :comprobanteId
                AND VIATICOS = :solicitudId
        SQL;

        $val = [
            'usuario' => $datos['usuario'],
            'estatus' => $datos['estatus'],
            'comprobanteId' => $datos['comprobanteId'],
            'solicitudId' => $datos['solicitudId'],
        ];

        try {
            $db = new Database();
            $db->beginTransaction();

            $result = $db->CRUD($qry, $val);
            if ($result < 1) throw new \Exception("No se encontró el comprobante a actualizar.");

            if (isset($datos['observaciones']) && $datos['observaciones'] != '') {
                $resultO = self::insertaObservaciones($datos);
                if (!$resultO['success']) throw new \Exception($resultO['error'] ?? $resultO['mensaje']);
            }

            if (isset($datos['finalizar'])) {
                $resultF = self::finalizaValidacion_VG($datos);
                if (!$resultF['success']) throw new \Exception($resultF['error'] ?? $resultF['mensaje']);
            }

            $db->commit();
            return self::resultado(true, 'Comprobante actualizado correctamente.', $result);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al actualizar el comprobante.', null, $e->getMessage());
        }
    }

    public static function finalizaValidacion_VG($datos)
    {
        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = CASE WHEN COMPROBACION_MONTO = ENTREGA_MONTO
                    THEN (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'FINALIZADA')
                    ELSE (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'VALIDADA')
                END
            WHERE
                ID = :id
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

    public static function getSolicitudesAjustes_VG($datos)
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
                , NVL(V.ENTREGA_MONTO, 0) AS ENTREGA_MONTO
                , NVL(V.COMPROBACION_MONTO, 0) AS COMPROBACION_MONTO
                , NVL(V.COMPROBACION_MONTO, 0) - NVL(V.ENTREGA_MONTO, 0) AS DIFERENCIA
            FROM
                VIATICOS V
                LEFT JOIN CAT_VIATICOS_ESTATUS CEV ON CEV.ID = V.ESTATUS
            WHERE
                CEV.NOMBRE = 'VALIDADA'
                AND (NVL(V.COMPROBACION_MONTO, 0) - NVL(V.ENTREGA_MONTO, 0)) <> 0
                AND V.DIFERENCIA_MONTO IS NULL
                AND TRUNC(V.ACTUALIZADO) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF , 'YYYY-MM-DD')
            ORDER BY
                ACTUALIZADO DESC
        SQL;

        $params = [
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

    public static function registraAjuste_VG($datos)
    {
        $qryO = <<<SQL
            INSERT INTO VIATICOS_OBSERVACIONES (OBSERVACION, USUARIO, VIATICOS, ESTATUS)
            VALUES (:observaciones, :usuario, :viaticos, (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'FINALIZADA'))
            RETURNING ID INTO :id
        SQL;

        $valO = [
            'observaciones' => $datos['observaciones'],
            'usuario' => $datos['usuario'],
            'viaticos' => $datos['solicitudId']
        ];

        $retO = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        $qry = <<<SQL
            UPDATE
                VIATICOS
            SET
                DIFERENCIA_MONTO = COMPROBACION_MONTO - NVL(ENTREGA_MONTO, 0)   
                , DIFERENCIA_FECHA = SYSDATE
                , DIFERENCIA_USUARIO = :usuario
                , DIFERENCIA_SUCURSAL = :sucursal
                , DIFERENCIA_OBSERVACION = :observaciones
                , ESTATUS = (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'FINALIZADA')
            WHERE
                ID = :id
        SQL;

        $val = [
            'id' => $datos['solicitudId'],
            'usuario' => $datos['usuario'],
            'sucursal' => $datos['sucursal'],
            'observaciones' => null
        ];

        try {
            $db = new Database();
            $db->beginTransaction();
            if (isset($datos['observaciones']) && $datos['observaciones'] != '') {
                $resultO = $db->CRUD($qryO, $valO, $retO);
                if ($resultO < 1) throw new \Exception("No se pudo insertar la observación de autorización.");
                $val['observaciones'] = $retO['id']['valor'];
            }
            $result = $db->CRUD($qry, $val);
            if ($result < 1) throw new \Exception("No se encontró la solicitud a ajustar.");
            $db->commit();

            return self::resultado(true, 'Ajuste registrado correctamente.', $result);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al registrar el ajuste.', null, $e->getMessage());
        }
    }

    public static function getDatosComprobanteAjuste($datos)
    {
        $qry = <<<SQL
            SELECT
                V.ID
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , GET_NOMBRE_USUARIO(V.ENTREGA_USUARIO) AS ENTREGA_NOMBRE
                , V.ENTREGA_MONTO
                , V.COMPROBACION_MONTO
                , V.DIFERENCIA_MONTO
            FROM
                VIATICOS V
            WHERE
                V.ID = :solicitudId
        SQL;

        $val = [
            'solicitudId' => $datos['solicitudId']
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
}
