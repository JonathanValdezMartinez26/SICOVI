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
                , RS.SUCURSAL AS SUCURSAL_ID
                , RS.SUCURSAL_NOMBRE AS SUCURSAL_NOMBRE
                , RS.REGION AS REGION_ID
                , RS.REGION_NOMBRE AS REGION_NOMBRE
                , RS.EMPRESA AS EMPRESA_ID
                , RS.EMPRESA_NOMBRE AS EMPRESA_NOMBRE
                , USUARIO.AUTORIZADOR AS AUTORIZADOR_ID
                , GET_NOMBRE_PERSONA(USUARIO.AUTORIZADOR) AS AUTORIZADOR_NOMBRE
                , PERFIL.AUTORIZACION_PROPIA
                , CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM (
                            SELECT JEFE AS ID FROM NOMINA WHERE ESTATUS = 1
                            UNION ALL
                            SELECT AUTORIZADOR AS ID FROM USUARIO WHERE ESTATUS = 1
                        ) CONTEO WHERE CONTEO.ID = PERSONA.ID
                    ) > 0 THEN 1
                    ELSE 0
                END AS ES_JEFE
            FROM
                USUARIO
                LEFT JOIN PERSONA ON PERSONA.ID = USUARIO.PERSONA
                LEFT JOIN PERFIL ON PERFIL.ID = USUARIO.PERFIL
                LEFT JOIN SUCURSALES_REGIONES RS ON RS.EMPRESA = USUARIO.EMPRESA AND RS.REGION = USUARIO.REGION AND RS.SUCURSAL = USUARIO.SUCURSAL
                LEFT JOIN NOMINA ON NOMINA.PERSONA = PERSONA.ID
            WHERE
                USUARIO.ESTATUS = 1
                AND NOMINA.ESTATUS = 1
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
