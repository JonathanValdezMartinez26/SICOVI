<?php

namespace Core;

use PDO;

class Database
{
    private $db;

    function __construct($servidor_ = null, $puerto_ = null, $esquema_ = null, $usuario_ = null, $password_ = null)
    {
        $servidor = $servidor_ ?? CONFIGURACION['SERVIDOR'];
        $puerto = $puerto_ ?? CONFIGURACION['PUERTO'];
        $esquema = $esquema_ ?? CONFIGURACION['ESQUEMA'];

        $cadena = "oci:dbname=//$servidor:$puerto/$esquema;charset=UTF8";
        $usuario = $usuario_ ?? CONFIGURACION['USUARIO'];
        $password = $password_ ?? CONFIGURACION['PASSWORD'];

        try {
            $this->db = new PDO($cadena, $usuario, $password);
        } catch (\PDOException $e) {
            self::baseNoDisponible("{$e->getMessage()}\nDatos de conexión: $cadena");
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

    private function getError($e, $sql = null, $valores = null, $retorno = null)
    {
        $error = "Error en DB: {$e->getMessage()}\n";

        if ($sql != null) $error .= "Query: $sql\n";
        if ($valores != null) $error .= 'Datos: ' . print_r($valores, 1);
        if ($retorno != null) $error .= 'Retorno: ' . print_r($retorno, 1);
        return $error;
    }

    public function beginTransaction()
    {
        if ($this->db == null) throw new \Exception("No se ha establecido una conexión a la base de datos.");
        if (!is_object($this->db)) throw new \Exception("La conexión a la base de datos no es válida.");
        if ($this->db->inTransaction()) return;

        try {
            $this->db->beginTransaction();
        } catch (\PDOException $e) {
            throw new \Exception($this->getError($e));
        }
    }

    public function commit()
    {
        if ($this->db == null) throw new \Exception("No se ha establecido una conexión a la base de datos.");
        if (!is_object($this->db)) throw new \Exception("La conexión a la base de datos no es válida.");
        if ($this->db->inTransaction() === false) return;

        try {
            $this->db->commit();
        } catch (\PDOException $e) {
            throw new \Exception($this->getError($e));
        }
    }

    public function rollback()
    {
        if ($this->db == null) throw new \Exception("No se ha establecido una conexión a la base de datos.");
        if (!is_object($this->db)) throw new \Exception("La conexión a la base de datos no es válida.");
        if ($this->db->inTransaction() === false) return;

        try {
            $this->db->rollBack();
        } catch (\PDOException $e) {
            throw new \Exception($this->getError($e));
        }
    }

    private function runQuery($sql, $valores = null, &$retorno = null)
    {
        if ($this->db == null) return false;
        if (!is_object($this->db)) return false;
        if (!is_null($valores) && !is_array($valores)) throw new \Exception("Los parámetros deben ser un array.");

        try {
            $stmt = $this->db->prepare($sql);
            if ($stmt === false) throw new \Exception("Error al preparar la consulta SQL.");

            if (is_array($valores) && count($valores) > 0) {
                foreach ($valores as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }
            }

            if (is_array($retorno) && count($retorno) > 0) {
                foreach ($retorno as $key => &$value) {
                    $stmt->bindParam(":$key", $value['valor'], $value['tipo'], $value['largo'] ?? null);
                }
            }

            $stmt->execute();
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception($this->getError($e, $sql, $valores, $retorno));
        } catch (\Exception $e) {
            throw new \Exception($this->getError($e, $sql, $valores, $retorno));
        }
    }

    public function queryOne($sql, $valores = null)
    {
        try {
            $stmt = $this->runQuery($sql, $valores);
            if ($stmt === false) throw new \Exception("Error de conexión a la base de datos.");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) return null;
            return $row;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function queryAll($sql, $valores = null)
    {
        try {
            $stmt = $this->runQuery($sql, $valores);
            if ($stmt === false) throw new \Exception("Error de conexión a la base de datos.");

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows === false) return [];
            return $rows;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function CRUD($sql, $valores = null, &$retorno = null)
    {
        try {
            $stmt = $this->runQuery($sql, $valores, $retorno);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function CRUD_multiple($sql, $valores, &$retorno = null)
    {
        try {
            $this->beginTransaction();

            foreach ($sql as $key => $query) {
                $ret = $retorno[$key] ?? null;
                $stmt = $this->runQuery($query, $valores[$key], $ret);
                if (!$stmt) throw new \Exception("Error de conexión a la base de datos.");
                if ($retorno[$key] !== null) $retorno[$key] = $ret;
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
