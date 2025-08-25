<?php

namespace Models;

use Core\Model;
use Core\Database;

class CapHum extends Model
{
    public static function getPersonas($datos)
    {
        $filtro = $datos['filtro'] ?? '';

        $where = '';
        $params = [];

        if (!empty($filtro)) {
            $where = "(UPPER(P.NOMBRE) LIKE UPPER(:filtro) 
                     OR UPPER(P.APELLIDO_1) LIKE UPPER(:filtro)
                     OR UPPER(P.APELLIDO_2) LIKE UPPER(:filtro)
                     OR UPPER(P.RFC) LIKE UPPER(:filtro)
                     OR UPPER(P.CURP) LIKE UPPER(:filtro))";
            $params['filtro'] = "%$filtro%";
        } else {
            $where = "P.ESTATUS = 1";
        }

        $qry = <<<SQL
            SELECT
                P.ID,
                P.NOMBRE,
                P.APELLIDO_1,
                P.APELLIDO_2,
                P.RFC,
                P.CURP,
                TO_CHAR(P.FECHA_NACIMIENTO, 'YYYY-MM-DD') AS FECHA_NACIMIENTO,
                P.SEXO,
                P.ESTATUS,
                CASE 
                    WHEN P.FOTO IS NOT NULL THEN P.ID
                    ELSE NULL
                END AS FOTO
            FROM
                PERSONA P
            WHERE
            $where
            ORDER BY
                P.NOMBRE, P.APELLIDO_1, P.APELLIDO_2
        SQL;

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $params);
            return self::resultado(true, 'Personas encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las personas.', null, $e->getMessage());
        }
    }

    public static function getPersonaDetalle($datos)
    {
        // Obtener datos de la persona
        $qryPersona = <<<SQL
            SELECT
                P.ID,
                P.NOMBRE,
                P.APELLIDO_1,
                P.APELLIDO_2,
                P.RFC,
                P.CURP,
                TO_CHAR(P.FECHA_NACIMIENTO, 'YYYY-MM-DD') AS FECHA_NACIMIENTO,
                P.SEXO,
                P.ESTATUS,
                CASE 
                    WHEN P.FOTO IS NOT NULL THEN P.ID
                    ELSE NULL
                END AS FOTO
            FROM
                PERSONA P
            WHERE
                P.ID = :id
        SQL;

        // Obtener usuarios asociados
        $qryUsuarios = <<<SQL
            SELECT
                U.ID,
                U.USUARIO,
                U.ESTATUS,
                U.SUCURSAL,
                S.NOMBRE AS SUCURSAL_NOMBRE
            FROM
                USUARIO U
                LEFT JOIN SUCURSAL S ON U.SUCURSAL = S.ID
            WHERE
                U.PERSONA = :id
            ORDER BY
                U.ID
        SQL;

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();

            $persona = $db->queryOne($qryPersona, $params);
            if (!$persona) {
                return self::resultado(false, 'Persona no encontrada.');
            }

            $usuarios = $db->queryAll($qryUsuarios, $params);

            $resultado = [
                'persona' => $persona,
                'usuarios' => $usuarios
            ];

            return self::resultado(true, 'Detalle obtenido correctamente.', $resultado);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el detalle de la persona.', null, $e->getMessage());
        }
    }

    public static function guardarPersona($datos, $fotoData = null)
    {
        $id = $datos['id'] ?? '';
        $nombre = $datos['nombre'] ?? '';
        $apellido1 = $datos['apellido1'] ?? '';
        $apellido2 = $datos['apellido2'] ?? '';
        $rfc = $datos['rfc'] ?? '';
        $curp = $datos['curp'] ?? '';
        $fechaNacimiento = $datos['fechaNacimiento'] ?? '';
        $sexo = $datos['sexo'] ?? '';
        $usuario = $datos['usuario'] ?? '';
        $pass = $datos['pass'] ?? '';
        $sucursal = $datos['sucursal'] ?? '';
        $perfil = $datos['perfil'] ?? '';

        try {
            $db = new Database();
            $db->beginTransaction();

            if (empty($id)) {
                // Insertar nueva persona
                $camposFoto = $fotoData ? ', FOTO' : '';
                $valoresFoto = $fotoData ? ', EMPTY_BLOB()' : '';

                $qryPersona = <<<SQL
                    INSERT INTO PERSONA (NOMBRE, APELLIDO_1, APELLIDO_2, RFC, CURP, FECHA_NACIMIENTO, SEXO, ESTATUS{$camposFoto})
                    VALUES (:nombre, :apellido1, :apellido2, :rfc, :curp, TO_DATE(:fechaNacimiento, 'YYYY-MM-DD'), :sexo, 1{$valoresFoto})
                    RETURNING ID, FOTO INTO :id, :foto
                SQL;

                $paramsPersona = [
                    'nombre' => strtoupper($nombre),
                    'apellido1' => strtoupper($apellido1),
                    'apellido2' => strtoupper($apellido2),
                    'rfc' => strtoupper($rfc),
                    'curp' => strtoupper($curp),
                    'fechaNacimiento' => $fechaNacimiento,
                    'sexo' => strtoupper($sexo)
                ];

                $retPersona = [
                    'id' => [
                        'valor' => '',
                        'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                        'largo' => 40
                    ],
                    'foto' => [
                        'valor' => $fotoData['foto'],
                        'tipo' => \PDO::PARAM_LOB
                    ]
                ];

                $db->CRUD($qryPersona, $paramsPersona, $retPersona);

                // Insertar usuario asociado
                if (!empty($usuario) && !empty($pass)) {
                    // Obtener el siguiente ID para el usuario
                    $qryNextUserId = "SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM USUARIO";
                    $nextUserIdResult = $db->queryOne($qryNextUserId);
                    $usuarioId = $nextUserIdResult['NEXT_ID'];

                    $qryUsuario = <<<SQL
                        INSERT INTO USUARIO (PERSONA, USUARIO, PASS, SUCURSAL, PERFIL, ESTATUS)
                        VALUES (:persona, :usuario, :pass, :sucursal, :perfil, 1)
                    SQL;

                    $paramsUsuario = [
                        'persona' => $retPersona['id']['valor'],
                        'usuario' => $usuario,
                        'pass' => password_hash($pass, PASSWORD_DEFAULT),
                        'sucursal' => $sucursal,
                        'perfil' => $perfil
                    ];

                    $db->CRUD($qryUsuario, $paramsUsuario);
                }

                $db->commit();
                return self::resultado(true, 'Persona registrada correctamente.');
            } else {
                // Actualizar persona existente
                $camposFoto = $fotoData ? ', FOTO = :foto' : '';

                $qryPersona = <<<SQL
                    UPDATE PERSONA SET
                        NOMBRE = :nombre,
                        APELLIDO_1 = :apellido1,
                        APELLIDO_2 = :apellido2,
                        RFC = :rfc,
                        CURP = :curp,
                        FECHA_NACIMIENTO = TO_DATE(:fechaNacimiento, 'YYYY-MM-DD'),
                        SEXO = :sexo{$camposFoto}
                    WHERE ID = :id
                SQL;

                $paramsPersona = [
                    'id' => $id,
                    'nombre' => strtoupper($nombre),
                    'apellido1' => strtoupper($apellido1),
                    'apellido2' => strtoupper($apellido2),
                    'rfc' => strtoupper($rfc),
                    'curp' => strtoupper($curp),
                    'fechaNacimiento' => $fechaNacimiento,
                    'sexo' => strtoupper($sexo)
                ];

                // Agregar datos de la foto si existe
                if ($fotoData) {
                    $paramsPersona['foto'] = $fotoData['foto'];
                }

                $db->CRUD($qryPersona, $paramsPersona);

                $db->commit();
                return self::resultado(true, 'Persona actualizada correctamente.');
            }
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al guardar la persona.', null, $e->getMessage());
        }
    }

    public static function eliminarPersona($datos)
    {
        $qry = <<<SQL
            UPDATE PERSONA SET
                ESTATUS = 0
            WHERE ID = :id
        SQL;

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Persona eliminada correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar la persona.', null, $e->getMessage());
        }
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

    public static function getUsuarioDetalle($datos)
    {
        $qry = <<<SQL
            SELECT
                U.ID,
                U.PERSONA,
                U.USUARIO,
                U.PERFIL,
                U.ESTATUS,
                R.EMPRESA,
                S.REGION,
                U.SUCURSAL
            FROM
                USUARIO U
                LEFT JOIN SUCURSAL S ON U.SUCURSAL = S.ID
                LEFT JOIN REGION R ON S.REGION = R.ID
                LEFT JOIN EMPRESA E ON R.EMPRESA = E.ID
            WHERE
                U.ID = :id
        SQL;

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();
            $usuario = $db->queryOne($qry, $params);

            if (!$usuario) {
                return self::resultado(false, 'Usuario no encontrado.');
            }

            return self::resultado(true, 'Usuario encontrado.', $usuario);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el usuario.', null, $e->getMessage());
        }
    }

    public static function guardarUsuario($datos)
    {
        $id = $datos['id'] ?? '';
        $persona = $datos['persona'] ?? '';
        $usuario = $datos['usuario'] ?? '';
        $pass = $datos['pass'] ?? '';
        $sucursal = $datos['sucursal'] ?? '';
        $empresa = $datos['empresa'] ?? '';
        $perfil = $datos['perfil'] ?? '';
        $estatus = $datos['estatus'] ?? 1;

        try {
            $db = new Database();
            $db->beginTransaction();

            if (!empty($id)) {
                // Actualizar usuario existente
                $qry = <<<SQL
                    UPDATE USUARIO SET
                        USUARIO = :usuario,
                        SUCURSAL = :sucursal,
                        EMPRESA = :empresa,
                        PERFIL = :perfil,
                        ESTATUS = :estatus
                SQL;

                $params = [
                    'usuario' => $usuario,
                    'sucursal' => $sucursal,
                    'empresa' => $empresa,
                    'perfil' => $perfil,
                    'estatus' => $estatus
                ];

                // Solo actualizar contraseña si se proporcionó
                if (!empty($pass)) {
                    $qry .= ", PASS = :pass";
                    $params['pass'] = password_hash($pass, PASSWORD_DEFAULT);
                }

                $qry .= " WHERE ID = :id";
                $params['id'] = $id;

                $db->CRUD($qry, $params);
                $mensaje = 'Usuario actualizado correctamente.';
            } else {
                // Crear nuevo usuario
                $qryNextId = "SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM USUARIO";
                $nextIdResult = $db->queryOne($qryNextId);
                $usuarioId = $nextIdResult['NEXT_ID'];

                $qry = <<<SQL
                    INSERT INTO USUARIO (ID, PERSONA, USUARIO, PASS, SUCURSAL, EMPRESA, PERFIL, ESTATUS)
                    VALUES (:id, :persona, :usuario, :pass, :sucursal, :empresa, :perfil, :estatus)
                SQL;

                $params = [
                    'id' => $usuarioId,
                    'persona' => $persona,
                    'usuario' => $usuario,
                    'pass' => password_hash($pass, PASSWORD_DEFAULT),
                    'sucursal' => $sucursal,
                    'empresa' => $empresa,
                    'perfil' => $perfil,
                    'estatus' => $estatus
                ];

                $db->CRUD($qry, $params);
                $mensaje = 'Usuario creado correctamente.';
            }

            $db->commit();
            return self::resultado(true, $mensaje);
        } catch (\Exception $e) {
            $db->rollback();
            return self::resultado(false, 'Error al guardar el usuario.', null, $e->getMessage());
        }
    }

    public static function cambiarEstatusUsuario($datos)
    {
        $qry = <<<SQL
            UPDATE USUARIO SET
                ESTATUS = :estatus
            WHERE ID = :id
        SQL;

        $params = [
            'id' => $datos['id'] ?? 0,
            'estatus' => $datos['estatus'] ?? 1
        ];

        try {
            $db = new Database();
            $db->CRUD($qry, $params);

            $mensaje = $params['estatus'] == 1 ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
            return self::resultado(true, $mensaje);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al cambiar el estatus del usuario.', null, $e->getMessage());
        }
    }

    public static function eliminarUsuario($datos)
    {
        $qry = <<<SQL
            DELETE FROM USUARIO
            WHERE ID = :id
        SQL;

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar el usuario.', null, $e->getMessage());
        }
    }

    public static function getFotoPersona($datos)
    {
        $qry = <<<SQL
            SELECT
                FOTO
            FROM
                PERSONA
            WHERE
                ID = :personaId
                AND FOTO IS NOT NULL
        SQL;

        $params = [
            'personaId' => $datos['personaId'] ?? 0
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $params);
            if (!$r) {
                return self::resultado(false, 'Foto no encontrada.');
            }
            return self::resultado(true, 'Foto obtenida correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener la foto.', null, $e->getMessage());
        }
    }
}
