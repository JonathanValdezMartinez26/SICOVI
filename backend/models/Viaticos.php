<?php

namespace Models;

use Core\Model;
use Core\Database;

class Viaticos extends Model
{
    public static function registraSolicitudViaticos($datos)
    {
        $query = <<<SQL
            INSERT INTO viaticos (tipo, usuario, proyecto, desde, hasta, monto)
            VALUES (:tipo, :usuario, :proyecto, :fechaI, :fechaF, :monto)
        SQL;

        $params = [
            'tipo' => $datos['tipo'],
            'proyecto' => $datos['proyecto'],
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF'],
            'monto' => $datos['monto'],
            'usuario' => $datos['usuario']
        ];

        return self::resultado(true, 'Solicitud registrada correctamente.', null, null);
        exit;

        try {
            $db = new Database();
            $r = $db->queryOne($query, $params);
            return self::resultado(true, 'Usuario validado correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }

    public static function getSolicitudesUsuario($datos)
    {
        $query = <<<SQL
        SQL;

        $params = [
            'usuario' => $datos['usuario'],
            'password' => $datos['password']
        ];

        try {
            $db = new Database();
            $r = $db->queryAll($query, $params);
            return self::resultado(true, 'Usuario validado correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al procesar la solicitud.', null, $e->getMessage());
        }
    }
}
