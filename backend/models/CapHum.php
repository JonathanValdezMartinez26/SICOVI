<?php

namespace Models;

use Core\Model;
use Core\Database;
use Error;

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

    private static function getErrorMessage($resultado)
    {
        if ($resultado['error']) return $resultado['error'];
        return $resultado['mensaje'] ?? null;
    }

    public static function guardarPersona($datos, $fotoData = null)
    {
        try {
            $db = new Database();
            $db->beginTransaction();

            // 1) Crear persona
            $crea = self::crearPersona($datos, $fotoData, $db);
            if (!$crea['success']) throw new \Exception(self::getErrorMessage($crea) ?? 'Error creando persona');
            $personaId = $crea['datos']['id'];

            // 2) Telefonos persona
            $telefonos = [
                ['1', $datos['telefonoPrincipal'] ?? null],
                ['2', $datos['telefonoAlterno'] ?? null]
            ];
            $insTel = self::insertarTelefonosPersona($personaId, $telefonos, $db);
            if (!$insTel['success']) throw new \Exception(self::getErrorMessage($insTel) ?? 'Error insertando teléfonos persona');

            // 3) Emails persona
            $emails[] = ['1', $datos['correoPrincipal'] ?? null];
            $laborales = explode(',', $datos['correoLaboral'] ?? '');
            foreach ($laborales as $email) {
                $emails[] = ['4', $email];
            }
            $insEmail = self::insertarEmailsPersona($personaId, $emails, $db);
            if (empty($insEmail['success'])) throw new \Exception(self::getErrorMessage($insEmail) ?? 'Error insertando emails persona');

            // 4) Nomina
            $creaNom = self::crearNomina($personaId, $datos, $db);
            if (empty($creaNom['success'])) throw new \Exception(self::getErrorMessage($creaNom) ?? 'Error creando nomina');

            // 5) Datos bancarios
            $insBanco = self::insertarDatosBancarios($personaId, $datos, $db);
            if (empty($insBanco['success'])) throw new \Exception(self::getErrorMessage($insBanco) ?? 'Error datos bancarios');

            // 6) Contacto emergencia
            $creaContacto = self::crearContactoEmergencia($personaId, $datos, $db);
            if (empty($creaContacto['success'])) throw new \Exception(self::getErrorMessage($creaContacto) ?? 'Error creando contacto');

            // 7) Registro de usuario principal
            $creaUsuario = self::crearUsuario($personaId, $datos, $db);
            if (empty($creaUsuario['success'])) throw new \Exception(self::getErrorMessage($creaUsuario) ?? 'Error creando usuario');

            $db->commit();
            return self::resultado(true, 'Registro completado correctamente.');
        } catch (\Exception $e) {
            $db->rollBack();
            return self::resultado(false, 'Error al registrar persona.', null, $e->getMessage());
        }
    }

    // Crea la fila en PERSONA y devuelve el id
    public static function crearPersona($datos, $fotoData = null, $db = null)
    {
        if ($fotoData) {
            $retFoto = [
                'foto' => [
                    'valor' => $fotoData['foto'] ?? null,
                    'tipo' => \PDO::PARAM_LOB
                ]
            ];
            $qryFoto = [
                'col' => ', FOTO',
                'val' => ', EMPTY_BLOB()',
                'ret' => ', :foto'
            ];
        }


        $qry = <<<SQL
            INSERT INTO PERSONA (
                NOMBRE, APELLIDO_1, APELLIDO_2, RFC, CURP, FECHA_NACIMIENTO,
                SEXO, ESTADO_CIVIL, NACIONALIDAD, NSS, CALLE_NUMERO, CP,
                ESTADO, MUNICIPIO, COLONIA,
                CONDICIONES_MEDICAS, OTROS_DATOS_RELEVANTES{$qryFoto['col']}
            )
            VALUES (
                :nombre, :apellido1, :apellido2, :rfc, :curp, TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'),
                :sexo, :estado_civil, :nacionalidad, :nss, :calle_numero, :cp,
                :estado, :municipio, :colonia,
                :condiciones_medicas, :informacion_adicional{$qryFoto['val']}
            )
            RETURNING ID{$qryFoto['col']} INTO :id{$qryFoto['ret']}
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
            'informacion_adicional' => $datos['informacion_adicional'] ?? null
        ];

        $ret = [
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];
        if ($fotoData) $ret = array_merge($ret, $retFoto);

        try {
            if (!$db) $db = new Database();
            $res = $db->CRUD($qry, $params, $ret);
            if (!$res || !isset($ret['id'])) return self::resultado(false, 'No se pudo insertar persona.');

            $id = $ret['id']['valor'];
            return self::resultado(true, "Persona registrada", ['id' => $id]);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al registrar persona: ' . $e->getMessage());
        }
    }

    public static function insertarTelefonosPersona($personaId, $telefonos, $db = null)
    {
        $qry = "INSERT INTO PERSONA_TELEFONO (PERSONA, NUMERO, TIPO) VALUES (:persona, :numero, :tipo)";
        try {
            if (!$db) $db = new Database();
            foreach ($telefonos as $telefono) {
                if (!$telefono || is_array($telefono)) continue;
                $params = ['persona' => $personaId, 'numero' => $telefono[1], 'tipo' => $telefono[0]];
                $db->CRUD($qry, $params);
            }

            return self::resultado(true, "Teléfonos insertados");
        } catch (\Exception $e) {
            return self::resultado(false, 'No se pudo insertar teléfonos persona. ', null, $e->getMessage());
        }
    }

    public static function insertarEmailsPersona($personaId, $emails, $db = null)
    {
        $qry = "INSERT INTO PERSONA_EMAIL (PERSONA, DIRECCION, TIPO) VALUES (:persona, :direccion, :tipo)";
        try {
            if (!$db) $db = new Database();
            foreach ($emails as $email) {
                if (!$email || is_array($email)) continue;
                $params = ['persona' => $personaId, 'direccion' => $email[1], 'tipo' => $email[0]];
                $db->CRUD($qry, $params);
            }

            return self::resultado(true, "Emails insertados");
        } catch (\Exception $e) {
            return self::resultado(false, 'No se pudo insertar emails persona. ', null, $e->getMessage());
        }
    }

    public static function crearNomina($personaId, $nomina, $db = null)
    {
        $qry = <<<SQL
            INSERT INTO NOMINA (
                PERSONA, SUCURSAL, JEFE, PUESTO, INGRESO, NOMINA, TIPO, NUMERO
            )
            VALUES (
                :persona, :sucursal, :jefe, :puesto, TO_DATE(:fecha_ingreso, 'YYYY-MM-DD'), :nomina, :tipo, :numero
            )
            SQL;

        $params = [
            'persona' => $personaId,
            'sucursal' => $nomina['sucursal'] ?? null,
            'jefe' => 2, //$nomina['jefeInmediato'] ?? -1,
            'puesto' => $nomina['puesto'] ?? null,
            'fecha_ingreso' => $nomina['fechaIngreso'] ?? null,
            'nomina' => $nomina['nomina'] ?? null,
            'tipo' => $nomina['tipoNomina'] ?? null,
            'numero' => $nomina['numeroNomina'] ?? null
        ];

        try {
            if (!$db) $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Nómina creada');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al crear nómina', null, $e->getMessage());
        }
    }

    public static function insertarDatosBancarios($personaId, $datosBancarios, $db = null)
    {
        $qry = "INSERT INTO PERSONA_DATOS_BANCARIOS (PERSONA, ID_BANCO, NUMERO, TIPO_NUMERO) VALUES (:persona, :banco, :numero, :tipo)";
        $paramsTarjeta = [
            'persona' => $personaId,
            'banco' => $datosBancarios['banco'] ?? null,
            'numero' => $datosBancarios['noTarjeta'] ?? null,
            'tipo' => 2,
        ];
        $paramsCuenta = [
            'persona' => $personaId,
            'banco' => $datosBancarios['banco'] ?? null,
            'numero' => $datosBancarios['cuentaBancaria'] ?? null,
            'tipo' => 1,
        ];

        try {
            if (!$db) $db = new Database();
            $db->CRUD($qry, $paramsTarjeta);
            $db->CRUD($qry, $paramsCuenta);
            return self::resultado(true, 'Datos bancarios registrados correctamente');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al registrar datos bancarios', null, $e->getMessage());
        }
    }

    public static function crearContactoEmergencia($personaId, $contacto, $db = null)
    {
        $qry = "INSERT INTO PERSONA_CONTACTO_EMERGENCIA (PERSONA, NOMBRE, PARENTESCO, TELEFONO) VALUES (:persona, :nombre, :parentesco, :telefono)";
        $params = [
            'persona' => $personaId,
            'nombre' => $contacto['contactoEmergenciaNombre'] ?? null,
            'parentesco' => $contacto['contactoEmergenciaParentesco'] ?? null,
            'telefono' => $contacto['contactoEmergenciaTelefono'] ?? null
        ];

        try {
            if (!$db) $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Contacto de emergencia creado');
        } catch (\Exception $e) {
            return self::resultado(false, 'No se pudo registrar el contacto de emergencia', null, $e->getMessage());
        }
    }

    public static function crearUsuario($personaId, $datos, $db = null)
    {
        $qry = "INSERT INTO USUARIO (PERSONA, SUCURSAL, USUARIO, PASS, PERFIL) VALUES (:persona, :sucursal, :usuario, :pass, :perfil)";
        $params = [
            'persona' => $personaId,
            'sucursal' => $datos['sucursal'] ?? null,
            'usuario' => $datos['usuario'] ?? null,
            'pass' => $datos['pass'] ?? null,
            'perfil' => $datos['perfil'] ?? null
        ];

        try {
            if (!$db) $db = new Database();
            $db->CRUD($qry, $params);
            return self::resultado(true, 'Usuario creado');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al crear usuario', null, $e->getMessage());
        }
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
