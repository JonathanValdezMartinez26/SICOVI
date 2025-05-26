<?php

namespace Models;

use Core\Model;
use Core\Database;

class Login extends Model
{
    public static function validaUsuario($datos)
    {
        $query = <<<SQL
            SELECT
                USUARIO.USUARIO
                , CONCATENA_NOMBRE(PERSONA.NOMBRE, '', PERSONA.APELLIDO_1, PERSONA.APELLIDO_2) AS NOMBRE
                , PERFIL.ID AS PERFIL_ID
                , PERFIL.NOMBRE AS PERFIL_NOMBRE
                , SUCURSAL.ID AS SUCURSAL_ID
                , SUCURSAL.NOMBRE AS SUCURSAL_NOMBRE
            FROM
                USUARIO
                LEFT JOIN PERSONA ON PERSONA.ID = USUARIO.PERSONA
                LEFT JOIN SUCURSAL ON SUCURSAL.ID = USUARIO.SUCURSAL
                LEFT JOIN PERFIL ON PERFIL.ID = USUARIO.PERFIL
            WHERE
                USUARIO.ESTATUS = 1
                AND USUARIO.USUARIO = :usuario
                AND USUARIO.PASS = CIFRA_PASS(:password)
        SQL;

        $params = [
            'usuario' => $datos['usuario'],
            'password' => $datos['password']
        ];

        try {
            $db = new Database();
            $r = $db->queryOne($query, $params);
            if ($r === null) return self::resultado(false, 'Credenciales incorrectas.');
            return self::resultado(true, 'Credenciales correctas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }
}
