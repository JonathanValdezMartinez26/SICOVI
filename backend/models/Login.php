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
                U.ID AS USUARIO_ID
                , CASE WHEN P.FOTO IS NULL THEN 0 ELSE P.ID END AS FOTO
                , GET_NOMBRE_USUARIO(U.ID) AS USUARIO_NOMBRE
                , PFL.ID AS PERFIL_ID
                , PFL.NOMBRE AS PERFIL_NOMBRE
                , P.ID AS PERSONA_ID
                , P.NOMBRE AS PERSONA_NOMBRE
                , RS.SUCURSAL AS SUCURSAL_ID
                , RS.SUCURSAL_NOMBRE AS SUCURSAL_NOMBRE
                , RS.REGION AS REGION_ID
                , RS.REGION_NOMBRE AS REGION_NOMBRE
                , RS.EMPRESA AS EMPRESA_ID
                , RS.EMPRESA_NOMBRE AS EMPRESA_NOMBRE
                , U.AUTORIZADOR AS AUTORIZADOR_ID
                , GET_NOMBRE_PERSONA(U.AUTORIZADOR) AS AUTORIZADOR_NOMBRE
                , PFL.AUTORIZACION_PROPIA
                , CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM (
                            SELECT NVL(JEFE, 0) AS ID FROM NOMINA WHERE ESTATUS = 1
                            UNION ALL
                            SELECT NVL(AUTORIZADOR, 0) AS ID FROM USUARIO WHERE ESTATUS = 1
                        ) CONTEO WHERE CONTEO.ID = P.ID
                    ) > 0 THEN 1
                    ELSE 0
                END AS ES_JEFE,
                USUARIO
            FROM
                USUARIO U
                LEFT JOIN PERSONA P ON P.ID = U.PERSONA
                LEFT JOIN PERFIL PFL ON PFL.ID = U.PERFIL
                LEFT JOIN SUCURSALES_REGIONES RS ON RS.EMPRESA = U.EMPRESA AND RS.REGION = U.REGION AND RS.SUCURSAL = U.SUCURSAL
                LEFT JOIN NOMINA N ON N.PERSONA = P.ID
            WHERE
                U.ESTATUS = 1
                AND N.ESTATUS = 1
                AND U.USUARIO = :usuario
                AND U.PASS = CIFRA_PASS(:password)
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

    public static function empresasHabilitadas($datos)
    {
        $query = <<<SQL
            SELECT
                ID_EMPRESA AS EMPRESA
            FROM
                CAT_PERSONA_EMPRESA_COMPRUEBA_PERMISO
            WHERE
                ID_PERSONA = :persona
        SQL;

        $params = [
            'persona' => $datos['persona_id']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($query, $params);
            return self::resultado(true, 'Empresas obtenidas correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }
}
