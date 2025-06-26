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
                USUARIO.ID AS USUARIO_ID
                , GET_NOMBRE_USUARIO(USUARIO.ID) AS USUARIO_NOMBRE
                , PERFIL.ID AS PERFIL_ID
                , PERFIL.NOMBRE AS PERFIL_NOMBRE
                , SUCURSAL.ID AS SUCURSAL_ID
                , SUCURSAL.NOMBRE AS SUCURSAL_NOMBRE
                , REGION.ID AS REGION_ID
                , REGION.NOMBRE AS REGION_NOMBRE
                , EMPRESA.ID AS EMPRESA_ID
                , EMPRESA.NOMBRE AS EMPRESA_NOMBRE
                , USUARIO.AUTORIZADOR AS AUTORIZADOR_ID
                , GET_NOMBRE_PERSONA(USUARIO.AUTORIZADOR) AS AUTORIZADOR_NOMBRE
                , PERFIL.AUTORIZACION_PROPIA
            FROM
                USUARIO
                LEFT JOIN PERSONA ON PERSONA.ID = USUARIO.PERSONA
                LEFT JOIN PERFIL ON PERFIL.ID = USUARIO.PERFIL
                LEFT JOIN SUCURSAL ON SUCURSAL.ID = USUARIO.SUCURSAL
                LEFT JOIN REGION ON REGION.ID = SUCURSAL.REGION
                LEFT JOIN EMPRESA ON EMPRESA.ID = REGION.EMPRESA
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
