<?php

namespace Models;

use Core\Model;
use Core\Database;

class Rh extends Model
{
    public static function getPersonas($datos)
    {
        $filtro = $datos['filtro'] ?? '';

        $where = '';
        $params = [];

        if (!empty($filtro)) {
            $where = "AND (UPPER(P.NOMBRE) LIKE UPPER(:filtro) 
                     OR UPPER(P.APELLIDO_1) LIKE UPPER(:filtro)
                     OR UPPER(P.APELLIDO_2) LIKE UPPER(:filtro)
                     OR UPPER(P.RFC) LIKE UPPER(:filtro)
                     OR UPPER(P.CURP) LIKE UPPER(:filtro))";
            $params['filtro'] = "%$filtro%";
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
                P.ESTATUS
            FROM
                PERSONA P
            WHERE
                P.ESTATUS = 1
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
        $id = $datos['id'] ?? 0;

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
                P.ESTATUS
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
                U.ESTATUS
            FROM
                USUARIO U
            WHERE
                U.PERSONA = :id
            ORDER BY
                U.ID
        SQL;

        try {
            $db = new Database();

            $persona = $db->queryOne($qryPersona, ['id' => $id]);
            if (!$persona) {
                return self::resultado(false, 'Persona no encontrada.');
            }

            $usuarios = $db->queryAll($qryUsuarios, ['id' => $id]);

            $resultado = [
                'persona' => $persona,
                'usuarios' => $usuarios
            ];

            return self::resultado(true, 'Detalle obtenido correctamente.', $resultado);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el detalle de la persona.', null, $e->getMessage());
        }
    }

    public static function guardarPersona($datos)
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

        try {
            $db = new Database();
            $db->beginTransaction();

            if (empty($id)) {
                // Obtener el siguiente ID para la persona
                $qryNextId = "SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM PERSONA";
                $nextIdResult = $db->queryOne($qryNextId);
                $personaId = $nextIdResult['NEXT_ID'];

                // Insertar nueva persona
                $qryPersona = <<<SQL
                    INSERT INTO PERSONA (NOMBRE, APELLIDO_1, APELLIDO_2, RFC, CURP, FECHA_NACIMIENTO, SEXO, ESTATUS)
                    VALUES (:nombre, :apellido1, :apellido2, :rfc, :curp, TO_DATE(:fechaNacimiento, 'YYYY-MM-DD'), :sexo, 1)
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

                $db->CRUD($qryPersona, $paramsPersona);

                // Insertar usuario asociado
                if (!empty($usuario) && !empty($pass)) {
                    // Obtener el siguiente ID para el usuario
                    $qryNextUserId = "SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM USUARIO";
                    $nextUserIdResult = $db->queryOne($qryNextUserId);
                    $usuarioId = $nextUserIdResult['NEXT_ID'];

                    $qryUsuario = <<<SQL
                        INSERT INTO USUARIO (PERSONA, USUARIO, PASS, ESTATUS)
                        VALUES (:persona, :usuario, :pass, 1)
                    SQL;

                    $paramsUsuario = [
                        'persona' => $personaId,
                        'persona' => $personaId,
                        'usuario' => $usuario,
                        'pass' => password_hash($pass, PASSWORD_DEFAULT)
                    ];

                    $db->CRUD($qryUsuario, $paramsUsuario);
                }

                $db->commit();
                return self::resultado(true, 'Persona registrada correctamente.');
            } else {
                // Actualizar persona existente
                $qryPersona = <<<SQL
                    UPDATE PERSONA SET
                        NOMBRE = :nombre,
                        APELLIDO_1 = :apellido1,
                        APELLIDO_2 = :apellido2,
                        RFC = :rfc,
                        CURP = :curp,
                        FECHA_NACIMIENTO = TO_DATE(:fechaNacimiento, 'YYYY-MM-DD'),
                        SEXO = :sexo
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
        $id = $datos['id'] ?? 0;

        $qry = <<<SQL
            UPDATE PERSONA SET
                ESTATUS = 0
            WHERE ID = :id
        SQL;

        try {
            $db = new Database();
            $db->CRUD($qry, ['id' => $id]);
            return self::resultado(true, 'Persona eliminada correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar la persona.', null, $e->getMessage());
        }
    }
}
