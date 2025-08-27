<?php

namespace Models;

use Core\Model;
use Core\Database;

class CapHum extends Model
{
    // Lista personas con filtro opcional
    public static function getPersonas($datos)
    {
        $filtro = $datos['filtro'] ?? '';
        $where = '';
        $params = [];

        if (!empty($filtro)) {
            $where = "(UPPER(P.NOMBRE) LIKE UPPER(:filtro)"
                . " OR UPPER(P.APELLIDO_1) LIKE UPPER(:filtro)"
                . " OR UPPER(P.APELLIDO_2) LIKE UPPER(:filtro)"
                . " OR UPPER(P.RFC) LIKE UPPER(:filtro)"
                . " OR UPPER(P.CURP) LIKE UPPER(:filtro))";
            $params['filtro'] = "%{$filtro}%";
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
                CASE WHEN P.FOTO IS NOT NULL THEN P.ID ELSE NULL END AS FOTO
            FROM PERSONA P
            WHERE $where
            ORDER BY P.NOMBRE, P.APELLIDO_1, P.APELLIDO_2
            SQL;

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $params);
            return self::resultado(true, 'Personas encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las personas.', null, $e->getMessage());
        }
    }

    // Detalle completo de persona y sus usuarios
    public static function getPersonaDetalle($datos)
    {
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
                P.ESTADO_CIVIL,
                P.NACIONALIDAD,
                P.NSS,
                P.CALLE_NUMERO,
                P.CP,
                P.ESTADO,
                P.MUNICIPIO,
                P.COLONIA,
                P.CONTACTO_EMERGENCIA_NOMBRE,
                P.CONTACTO_EMERGENCIA_PARENTESCO,
                P.CONTACTO_EMERGENCIA_TELEFONO,
                P.CONDICIONES_MEDICAS,
                P.INFORMACION_ADICIONAL,
                P.ESTATUS,
                CASE WHEN P.FOTO IS NOT NULL THEN P.ID ELSE NULL END AS FOTO
            FROM PERSONA P
            WHERE P.ID = :id
            SQL;

        $qryUsuarios = <<<SQL
            SELECT U.ID, U.USUARIO, U.ESTATUS, U.SUCURSAL, S.NOMBRE AS SUCURSAL_NOMBRE
            FROM USUARIO U
            LEFT JOIN SUCURSAL S ON U.SUCURSAL = S.ID
            WHERE U.PERSONA = :id
            ORDER BY U.ID
            SQL;

        $params = ['id' => $datos['id'] ?? 0];

        try {
            $db = new Database();
            $persona = $db->queryOne($qryPersona, $params);
            if (!$persona) return self::resultado(false, 'Persona no encontrada.');
            $usuarios = $db->queryAll($qryUsuarios, $params);
            return self::resultado(true, 'Detalle obtenido correctamente.', ['persona' => $persona, 'usuarios' => $usuarios]);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el detalle de la persona.', null, $e->getMessage());
        }
    }

    // Orquestador: registra persona y datos relacionados en una transacción
    public static function guardarPersona($datos, $fotoData = null)
    {
        $db = new Database();

        try {
            $db->beginTransaction();

            // 1) Crear persona
            $crea = self::crearPersona($datos, $fotoData, $db);
            if (!is_array($crea) || empty($crea['success']) || !$crea['success']) {
                throw new \Exception($crea['mensaje'] ?? 'Error creando persona');
            }
            $personaId = $crea['id'];

            // 2) Telefonos persona
            if (!empty($datos['telefonos']) && is_array($datos['telefonos'])) {
                $insTel = self::insertarTelefonosPersona($personaId, $datos['telefonos'], $db);
                if (empty($insTel['success'])) throw new \Exception($insTel['mensaje'] ?? 'Error insertando telefonos persona');
            }

            // 3) Emails persona
            if (!empty($datos['emails']) && is_array($datos['emails'])) {
                $insEmail = self::insertarEmailsPersona($personaId, $datos['emails'], $db);
                if (empty($insEmail['success'])) throw new \Exception($insEmail['mensaje'] ?? 'Error insertando emails persona');
            }

            // 4) Contacto emergencia
            if (!empty($datos['contacto_emergencia']) && is_array($datos['contacto_emergencia'])) {
                $creaContacto = self::crearContactoEmergencia($personaId, $datos['contacto_emergencia'], $db);
                if (empty($creaContacto['success'])) throw new \Exception($creaContacto['mensaje'] ?? 'Error creando contacto');
                $contactoId = $creaContacto['id'];
                if (!empty($datos['contacto_emergencia']['telefonos']) && is_array($datos['contacto_emergencia']['telefonos'])) {
                    $insTelContacto = self::insertarTelefonosContacto($contactoId, $datos['contacto_emergencia']['telefonos'], $db);
                    if (empty($insTelContacto['success'])) throw new \Exception($insTelContacto['mensaje'] ?? 'Error telefono contacto');
                }
            }

            // 5) Nomina
            if (!empty($datos['nomina']) && is_array($datos['nomina'])) {
                $creaNom = self::crearNomina($personaId, $datos['nomina'], $db);
                if (empty($creaNom['success'])) throw new \Exception($creaNom['mensaje'] ?? 'Error creando nomina');
            }

            // 6) Datos bancarios
            if (!empty($datos['datos_bancarios']) && is_array($datos['datos_bancarios'])) {
                $insBanco = self::insertarDatosBancarios($personaId, $datos['datos_bancarios'], $db);
                if (empty($insBanco['success'])) throw new \Exception($insBanco['mensaje'] ?? 'Error datos bancarios');
            }

            $db->commit();
            return self::resultado(true, 'Registro completado correctamente.', ['persona_id' => $personaId]);
        } catch (\Exception $e) {
            try {
                $db->rollBack();
            } catch (\Exception $__) {
            }
            return self::resultado(true, 'Error al registrar persona: ' . $e->getMessage());
        }
    }

    // Crea la fila en PERSONA y devuelve el id
    public static function crearPersona($datos, $fotoData = null, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = <<<SQL
            INSERT INTO PERSONA (
                NOMBRE, APELLIDO_1, APELLIDO_2, RFC, CURP, FECHA_NACIMIENTO,
                SEXO, ESTADO_CIVIL, NACIONALIDAD, NSS, CALLE_NUMERO, CP,
                ESTADO, MUNICIPIO, COLONIA,
                CONDICIONES_MEDICAS, OTROS_DATOS_RELEVANTES, ESTATUS, FOTO
            )
            VALUES (
                :nombre, :apellido1, :apellido2, :rfc, :curp, TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'),
                :sexo, :estado_civil, :nacionalidad, :nss, :calle_numero, :cp,
                :estado, :municipio, :colonia,
                :condiciones_medicas, :informacion_adicional, :estatus, EMPTY_BLOB()
            )
            RETURNING ID, FOTO INTO :id, :foto
        SQL;

        $params = [
            'nombre' => $datos['nombre'] ?? null,
            'apellido1' => $datos['apellido1'] ?? null,
            'apellido2' => $datos['apellido2'] ?? null,
            'rfc' => $datos['rfc'] ?? null,
            'curp' => $datos['curp'] ?? null,
            'fecha_nacimiento' => $datos['fechaNacimiento'] ?? null,
            'sexo' => $datos['sexo'] ?? null,
            'estado_civil' => $datos['estado_civil'] ?? null,
            'nacionalidad' => $datos['nacionalidad'] ?? null,
            'nss' => $datos['nss'] ?? null,
            'calle_numero' => $datos['calle_numero'] ?? null,
            'cp' => $datos['cp'] ?? null,
            'estado' => $datos['estado'] ?? null,
            'municipio' => $datos['municipio'] ?? null,
            'colonia' => $datos['colonia'] ?? null,
            'condiciones_medicas' => $datos['condiciones_medicas'] ?? null,
            'informacion_adicional' => $datos['informacion_adicional'] ?? null,
            'estatus' => $datos['estatus'] ?? 1
        ];

        $ret = [
            'foto' => [
                'valor' => $fotoData['foto'],
                'tipo' => \PDO::PARAM_LOB
            ],
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        $res = $db->CRUD($qry, $params, $ret);
        if (!$res || !isset($ret['id'])) {
            throw new \Exception('No se pudo insertar persona.');
        }

        $id = $ret['id'];

        if ($closeDb) { /* no-op */
        }
        return ['success' => true, 'id' => $id];
    }

    public static function insertarTelefonosPersona($personaId, $telefonos, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = "INSERT INTO PERSONA_TELEFONO (PERSONA, TIPO, TELEFONO) VALUES (:persona, :tipo, :telefono)";
        foreach ($telefonos as $t) {
            $params = ['persona' => $personaId, 'tipo' => $t['tipo'] ?? 'PRINCIPAL', 'telefono' => $t['telefono'] ?? null];
            $db->queryOne($qry, $params);
        }

        if ($closeDb) { /* no-op */
        }
        return ['success' => true];
    }

    public static function insertarEmailsPersona($personaId, $emails, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = "INSERT INTO PERSONA_EMAIL (PERSONA, TIPO, EMAIL) VALUES (:persona, :tipo, :email)";
        foreach ($emails as $e) {
            $params = ['persona' => $personaId, 'tipo' => $e['tipo'] ?? 'PRINCIPAL', 'email' => $e['email'] ?? null];
            $db->queryOne($qry, $params);
        }

        if ($closeDb) { /* no-op */
        }
        return ['success' => true];
    }

    public static function crearContactoEmergencia($personaId, $contacto, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = "INSERT INTO PERSONA_CONTACTO_EMERGENCIA (PERSONA, NOMBRE, PARENTESCO, TELEFONO) VALUES (:persona, :nombre, :parentesco, :telefono) RETURNING ID";
        $params = ['persona' => $personaId, 'nombre' => $contacto['nombre'] ?? null, 'parentesco' => $contacto['parentesco'] ?? null, 'telefono' => $contacto['telefono'] ?? null];
        $res = $db->queryOne($qry, $params);
        $id = $res['id'] ?? $res['ID'] ?? null;

        if (!$id) throw new \Exception('No se pudo insertar contacto de emergencia.');

        if ($closeDb) { /* no-op */
        }
        return ['success' => true, 'id' => $id];
    }

    public static function insertarTelefonosContacto($contactoId, $telefonos, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = "INSERT INTO PERSONA_CONTACTO_TELEFONO (CONTACTO_ID, TIPO, TELEFONO) VALUES (:contacto, :tipo, :telefono)";
        foreach ($telefonos as $t) {
            $params = ['contacto' => $contactoId, 'tipo' => $t['tipo'] ?? 'PRINCIPAL', 'telefono' => $t['telefono'] ?? null];
            $db->queryOne($qry, $params);
        }

        if ($closeDb) { /* no-op */
        }
        return ['success' => true];
    }

    public static function crearNomina($personaId, $nomina, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = <<<SQL
            INSERT INTO NOMINA (
                PERSONA, SUCURSAL, PUESTO, FECHA_INGRESO, TIPO_CONTRATO,
                SALARIO, FRECUENCIA_PAGO, CLAVE_IMSS
            )
            VALUES (
                :persona, :sucursal, :puesto, :fecha_ingreso, :tipo_contrato,
                :salario, :frecuencia_pago, :clave_imss
            )
            RETURNING ID
            SQL;

        $params = [
            'persona' => $personaId,
            'sucursal' => $nomina['sucursal'] ?? null,
            'puesto' => $nomina['puesto'] ?? null,
            'fecha_ingreso' => $nomina['fecha_ingreso'] ?? null,
            'tipo_contrato' => $nomina['tipo_contrato'] ?? null,
            'salario' => $nomina['salario'] ?? null,
            'frecuencia_pago' => $nomina['frecuencia_pago'] ?? null,
            'clave_imss' => $nomina['clave_imss'] ?? null,
        ];

        $res = $db->queryOne($qry, $params);
        $id = $res['id'] ?? $res['ID'] ?? null;
        if (!$id) throw new \Exception('No se pudo insertar registro de nómina.');

        if ($closeDb) { /* no-op */
        }
        return ['success' => true, 'id' => $id];
    }

    public static function insertarDatosBancarios($personaId, $datosBancarios, $db = null)
    {
        $closeDb = false;
        if (!$db) {
            $db = new Database();
            $closeDb = true;
        }

        $qry = "INSERT INTO PERSONA_DATOS_BANCARIOS (PERSONA, BANCO, CLABE, NUMERO_CUENTA, TITULAR) VALUES (:persona, :banco, :clabe, :numero_cuenta, :titular)";
        $params = [
            'persona' => $personaId,
            'banco' => $datosBancarios['banco'] ?? null,
            'clabe' => $datosBancarios['clabe'] ?? null,
            'numero_cuenta' => $datosBancarios['numero_cuenta'] ?? null,
            'titular' => $datosBancarios['titular'] ?? null,
        ];

        $db->queryOne($qry, $params);

        if ($closeDb) { /* no-op */
        }
        return ['success' => true];
    }

    public static function eliminarPersona($datos)
    {
        $qry = <<<SQL
            UPDATE PERSONA SET
                ESTATUS = 0
            WHERE ID = :id
            SQL;

        $params = ['id' => $datos['id'] ?? 0];

        try {
            $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Persona eliminada correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al eliminar la persona.', null, $e->getMessage());
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

        $params = ['id' => $datos['id'] ?? 0];

        try {
            $db = new Database();
            $usuario = $db->queryOne($qry, $params);
            if (!$usuario) return self::resultado(false, 'Usuario no encontrado.');
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
                $qry = <<<SQL
                    UPDATE USUARIO SET
                        USUARIO = :usuario,
                        SUCURSAL = :sucursal,
                        EMPRESA = :empresa,
                        PERFIL = :perfil,
                        ESTATUS = :estatus
                    SQL;

                $params = ['usuario' => $usuario, 'sucursal' => $sucursal, 'empresa' => $empresa, 'perfil' => $perfil, 'estatus' => $estatus];
                if (!empty($pass)) {
                    $qry .= ", PASS = :pass";
                    $params['pass'] = password_hash($pass, PASSWORD_DEFAULT);
                }
                $qry .= " WHERE ID = :id";
                $params['id'] = $id;
                $db->CRUD($qry, $params);
                $mensaje = 'Usuario actualizado correctamente.';
            } else {
                $qryNextId = "SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM USUARIO";
                $nextIdResult = $db->queryOne($qryNextId);
                $usuarioId = $nextIdResult['NEXT_ID'];

                $qry = <<<SQL
                    INSERT INTO USUARIO (ID, PERSONA, USUARIO, PASS, SUCURSAL, EMPRESA, PERFIL, ESTATUS)
                    VALUES (:id, :persona, :usuario, :pass, :sucursal, :empresa, :perfil, :estatus)
                    SQL;

                $params = ['id' => $usuarioId, 'persona' => $persona, 'usuario' => $usuario, 'pass' => password_hash($pass, PASSWORD_DEFAULT), 'sucursal' => $sucursal, 'empresa' => $empresa, 'perfil' => $perfil, 'estatus' => $estatus];
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

        $params = ['id' => $datos['id'] ?? 0, 'estatus' => $datos['estatus'] ?? 1];

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

        $params = ['id' => $datos['id'] ?? 0];

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

        $params = ['personaId' => $datos['personaId'] ?? 0];

        try {
            $db = new Database();
            $r = $db->queryOne($qry, $params);
            if (!$r) return self::resultado(false, 'Foto no encontrada.');
            return self::resultado(true, 'Foto obtenida correctamente.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener la foto.', null, $e->getMessage());
        }
    }
}
