<?php

namespace Core;

use PDO;

/**
 * @class Conn
 */

class Database
{
    public $db;

    function __construct($servidor_ = null, $puerto_ = null, $esquema_ = null, $usuario_ = null, $password = null)
    {
        $servidor = $servidor_ ?? CONFIGURACION['SERVIDOR'];
        $puerto = $puerto_ ?? CONFIGURACION['PUERTO'];
        $esquema = $esquema_ ?? CONFIGURACION['ESQUEMA'];

        $cadena = "oci:dbname=//$servidor:$puerto/$esquema;charset=UTF8";
        $usuario = $usuario_ ?? CONFIGURACION['USUARIO'];
        $password = $password ?? CONFIGURACION['PASSWORD'];

        try {
            $this->db = new PDO($cadena, $usuario, $password);
        } catch (\PDOException $e) {
            self::baseNoDisponible("{$e->getMessage()}\nDatos de conexión: $cadena"); //\nUsuario: $usuario\nPassword: $password");
            $this->db = null;
        }
    }

    private function baseNoDisponible($mensaje)
    {
        http_response_code(503);
        echo <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Sistema fuera de línea</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        background-color: #f4f4f4;
                        color: #333;
                        margin: 0;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                    }
                    .container {
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    }
                    h1 {
                        font-size: 2em;
                        color: #d9534f;
                    }
                    p {
                        font-size: 1.2em;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Sistema fuera de línea</h1>
                    <p>Estamos trabajando para resolver la situación. Por favor, vuelva a intentarlo más tarde.</p>
                </div>
            </body>
            <script>
                window.onload = () => {
                    console.log("$mensaje")
                }
            </script>
            </html>
        HTML;
        exit();
    }

    private function getError($e, $sql = null, $parametros = null)
    {
        $error = "Error en DB: {$e->getMessage()}\n";

        if ($sql != null) $error .= "Sql: $sql\n";
        if ($parametros != null) $error .= 'Datos: ' . print_r($parametros, 1);
        //echo $error . "\n";
        return $error;
    }

    public function runQuery($sql, $parametros = [])
    {
        if ($this->db == null) return false;
        if (!is_object($this->db)) return false;
        if (!is_array($parametros)) throw new \Exception("Los parámetros deben ser un array.");

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($parametros);
            $stmt->execute();
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception($this->getError($e, $sql, $parametros));
        } catch (\Exception $e) {
            throw new \Exception($this->getError($e, $sql, $parametros));
        }
    }

    public function queryOne($sql, $parametros = null)
    {
        try {
            $stmt = $this->runQuery($sql, $parametros);
            if ($stmt === false) throw new \Exception("Error de conexión a la base de datos.");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) return null;
            return $row;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function queryAll($sql, $parametros = null)
    {
        try {
            $stmt = $this->runQuery($sql, $parametros);
            if ($stmt === false) throw new \Exception("Error de conexión a la base de datos.");

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows === false) return [];
            return $rows;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function IDU($sql, $parametros = null)
    {
        try {
            $stmt = $this->runQuery($sql, $parametros);
            return $stmt->lastInsertId();
            // return $stmt->rowCount();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
