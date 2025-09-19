<?php

namespace Models;

use Core\Model;
use Core\Database;
use Error;

class CapHum extends Model
{
    private static function getErrorMessage($resultado)
    {
        if ($resultado['error']) return $resultado['error'];
        return $resultado['mensaje'] ?? null;
    }

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
                P.INFONAVIT,
                P.CALLE_NUMERO,
                P.CP,
                P.ESTADO,
                P.MUNICIPIO,
                P.COLONIA,
                P.CONDICIONES_MEDICAS,
                P.OTROS_DATOS_RELEVANTES,
                P.ESTATUS,
                CASE WHEN P.FOTO IS NOT NULL THEN P.ID ELSE NULL END AS FOTO
            FROM PERSONA P
            WHERE P.ID = :id
        SQL;

        $qryUsuarios = <<<SQL
            SELECT U.ID, U.USUARIO, U.ESTATUS, SR.SUCURSAL, SR.SUCURSAL_NOMBRE
            FROM USUARIO U
            LEFT JOIN SUCURSALES_REGIONES SR ON SR.EMPRESA = U.EMPRESA AND SR.REGION = U.REGION AND SR.SUCURSAL = U.SUCURSAL
            WHERE U.PERSONA = :id
            ORDER BY U.ID
        SQL;

        $qryEmpresa = <<<SQL
            SELECT 
                SR.EMPRESA,
                SR.EMPRESA_NOMBRE,
                SR.REGION,
                SR.REGION_NOMBRE,
                SR.SUCURSAL,
                SR.SUCURSAL_NOMBRE
            FROM NOMINA n
            JOIN SUCURSALES_REGIONES SR ON SR.EMPRESA = n.EMPRESA AND SR.REGION = n.REGION AND SR.SUCURSAL = n.SUCURSAL
            WHERE n.PERSONA = :id
        SQL;

        $qryNomina = <<<SQL
            SELECT
                TO_CHAR(N.INGRESO, 'YYYY-MM-DD') AS INGRESO,
                N.TIPO AS TIPO_NOMINA,
                N.NUMERO AS NUMERO_NOMINA,
                N.JEFE AS JEFE,
                GET_NOMBRE_PERSONA(N.JEFE) AS JEFE_NOMBRE,
                CNP.ID_PROVEEDOR AS PROVEEDOR,
                CNP.NOMBRE_PROVEEDOR AS PROVEEDOR_NOMBRE,
                CP.ID_PUESTO AS PUESTO,
                CP.DESCRIPCION AS PUESTO_NOMBRE
            FROM
                NOMINA N
                JOIN CAT_NOMINAS_PROVEEDOR CNP ON CNP.ID_PROVEEDOR = N.PROVEEDOR
                JOIN CAT_PUESTOS CP ON CP.ID_PUESTO = N.PUESTO
            WHERE
                N.PERSONA = :id
        SQL;
        $qryBancos = "SELECT ID_BANCO, CB.NOMBRE, NUMERO, TIPO_NUMERO FROM PERSONA_DATOS_BANCARIOS PDB JOIN CAT_BANCO CB ON CB.ID = PDB.ID_BANCO WHERE PERSONA = :id";
        $qryTelefonos = "SELECT NUMERO, TIPO FROM PERSONA_TELEFONO WHERE PERSONA = :id";
        $qryEmails = "SELECT DIRECCION, TIPO FROM PERSONA_EMAIL WHERE PERSONA = :id";
        $qryContactos = "SELECT PCE.ID, PCE.NOMBRE, PCE.TELEFONO, CP.DESCRIPCION AS PARENTESCO FROM PERSONA_CONTACTO_EMERGENCIA PCE JOIN CAT_PARENTESCO_EMERGENCIA CP ON CP.ID_PARENTESCO = PCE.PARENTESCO WHERE PCE.PERSONA = :id";

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();

            $persona = $db->queryOne($qryPersona, $params);
            if (!$persona) return self::resultado(false, 'Persona no encontrada.');

            $nomina = $db->queryOne($qryNomina, $params);
            $empresa = $db->queryOne($qryEmpresa, $params);
            $bancos = $db->queryAll($qryBancos, $params);
            $usuarios = $db->queryAll($qryUsuarios, $params);
            $telefonos = $db->queryAll($qryTelefonos, $params);
            $emails = $db->queryAll($qryEmails, $params);
            $contactos = $db->queryAll($qryContactos, $params);

            $resultado = [
                'persona' => $persona,
                'usuarios' => $usuarios,
                'telefonos' => $telefonos,
                'emails' => $emails,
                'nomina' => $nomina,
                'bancos' => $bancos,
                'contactos' => $contactos,
                'empresa' => $empresa
            ];

            return self::resultado(true, 'Detalle obtenido correctamente.', $resultado);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el detalle de la persona.', null, $e->getMessage());
        }
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
            $emails = [
                ['1', $datos['correoPrincipal'] ?? null]
            ];
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
                SEXO, ESTADO_CIVIL, NACIONALIDAD, NSS, INFONAVIT, CALLE_NUMERO, CP,
                ESTADO, MUNICIPIO, COLONIA,
                CONDICIONES_MEDICAS, OTROS_DATOS_RELEVANTES{$qryFoto['col']}
            )
            VALUES (
                :nombre, :apellido1, :apellido2, :rfc, :curp, TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'),
                :sexo, :estado_civil, :nacionalidad, :nss, :infonavit, :calle_numero, :cp,
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
            'estado_civil' => $datos['estadoCivil'] ?? null,
            'nacionalidad' => $datos['nacionalidad'] ?? null,
            'nss' => $datos['nss'] ?? null,
            'infonavit' => $datos['infonavit'] ?? null,
            'calle_numero' => $datos['calle'] ?? null,
            'cp' => $datos['codigoPostal'] ?? null,
            'estado' => $datos['estado'] ?? null,
            'municipio' => $datos['municipio'] ?? null,
            'colonia' => $datos['colonia'] ?? null,
            'condiciones_medicas' => $datos['condicionesMedicas'] ?? null,
            'informacion_adicional' => $datos['informacionAdicional'] ?? null
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
                if (is_array($telefono) && !empty($telefono[1]) && !is_null($telefono[1])) {
                    $params = ['persona' => $personaId, 'numero' => $telefono[1], 'tipo' => $telefono[0]];
                    $db->CRUD($qry, $params);
                }
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
                if (is_array($email) && !empty($email[1]) && !is_null($email[1])) {
                    $params = ['persona' => $personaId, 'direccion' => $email[1], 'tipo' => $email[0]];
                    $db->CRUD($qry, $params);
                }
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
                PERSONA, EMPRESA, REGION, SUCURSAL, JEFE, PUESTO, INGRESO, PROVEEDOR, TIPO, NUMERO
            )
            VALUES (
                :persona, :empresa, :region, :sucursal, :jefe, :puesto, TO_DATE(:fecha_ingreso, 'YYYY-MM-DD'), :proveedor, :tipo, :numero
            )
            SQL;

        $params = [
            'persona' => $personaId,
            'empresa' => $nomina['empresa'] ?? null,
            'region' => $nomina['region'] ?? null,
            'sucursal' => $nomina['sucursal'] ?? null,
            'jefe' => $nomina['jefeInmediato'] ?? -1,
            'puesto' => $nomina['puesto'] ?? null,
            'fecha_ingreso' => $nomina['fechaIngreso'] ?? null,
            'proveedor' => $nomina['proveedor'] ?? null,
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
        $qry = "INSERT INTO USUARIO (PERSONA, EMPRESA, REGION, SUCURSAL, USUARIO, PASS, PERFIL) VALUES (:persona, :empresa, :region, :sucursal, :usuario, :pass, :perfil)";
        $params = [
            'persona' => $personaId,
            'empresa' => $datos['empresa'] ?? null,
            'region' => $datos['region'] ?? null,
            'sucursal' => $datos['sucursal'] ?? null,
            'usuario' => $datos['usuario'] ?? null,
            'pass' => $datos['password'] ?? null,
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
                SR.EMPRESA,
                SR.REGION,
                SR.SUCURSAL
            FROM
                USUARIO U
                LEFT JOIN SUCURSALES_REGIONES SR ON SR.EMPRESA = U.EMPRESA AND SR.REGION = U.REGION AND SR.SUCURSAL = U.SUCURSAL
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
        $empresa = $datos['empresa'] ?? '';
        $region = $datos['region'] ?? '';
        $sucursal = $datos['sucursal'] ?? '';
        $perfil = $datos['perfil'] ?? '';
        $estatus = $datos['estatus'] ?? 1;

        try {
            $db = new Database();
            $db->beginTransaction();

            if (!empty($id)) {
                $qry = <<<SQL
                    UPDATE USUARIO SET
                        USUARIO = :usuario,
                        EMPRESA = :empresa,
                        REGION = :region,
                        SUCURSAL = :sucursal,
                        PERFIL = :perfil,
                        ESTATUS = :estatus
                    SQL;

                $params = ['usuario' => $usuario, 'empresa' => $empresa, 'region' => $region, 'sucursal' => $sucursal, 'perfil' => $perfil, 'estatus' => $estatus];
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
                    INSERT INTO USUARIO (ID, PERSONA, USUARIO, PASS, EMPRESA, REGION, SUCURSAL, PERFIL, ESTATUS)
                    VALUES (:id, :persona, :usuario, :pass, :empresa, :region, :sucursal, :perfil, :estatus)
                    SQL;

                $params = ['id' => $usuarioId, 'persona' => $persona, 'usuario' => $usuario, 'pass' => password_hash($pass, PASSWORD_DEFAULT), 'sucursal' => $sucursal, 'empresa' => $empresa, 'region' => $region, 'perfil' => $perfil, 'estatus' => $estatus];
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

    public static function getListaFormatosMCM($datos)
    {
        $qry = <<<SQL
            SELECT
                ID
                , NOMBRE
                , TO_CHAR(FECHA_SUBIDA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_SUBIDA
                , TO_CHAR(VIGENCIA_FIN, 'YYYY-MM-DD') AS VIGENCIA_FIN
                , ACCESO
            FROM
                REPOSITORIO_CAPITALH@DB_MCM
            WHERE
                TRUNC(FECHA_SUBIDA) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF, 'YYYY-MM-DD')
            ORDER BY
                FECHA_SUBIDA DESC
        SQL;

        $val = [
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $res = $db->queryAll($qry, $val);
            return self::resultado(true, 'Formatos obtenidos correctamente.', $res);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los formatos.', null, $e->getMessage());
        }
    }

    public static function getFormatoMCM($datos)
    {
        $qry = "SELECT ARCHIVO, TIPO, NOMBRE FROM REPOSITORIO_CAPITALH@DB_MCM WHERE ID = :idFormato";

        $val = [
            'idFormato' => $datos['idFormato']
        ];

        try {
            $db = new Database();
            $res = $db->queryOne($qry, $val);
            if (!$res) throw new \Exception("Formato no encontrado.");
            return self::resultado(true, 'Formato obtenido correctamente.', $res);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el formato.', null, $e->getMessage());
        }
    }

    public static function registraFormatoMCM($datos)
    {
        $qryA = <<<SQL
            INSERT INTO REPOSITORIO_CAPITALH (ARCHIVO, NOMBRE, TIPO)
            VALUES (EMPTY_BLOB(), :nombre, :tipo)
            RETURNING ARCHIVO, ID INTO :archivo, :id
        SQL;

        $valA = [
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo']
        ];

        $retA = [
            'archivo' => [
                'valor' => $datos['archivo'],
                'tipo' => \PDO::PARAM_LOB
            ],
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database('mcm');
            $db->beginTransaction();
            $db->CRUD($qryA, $valA, $retA);

            if (!$retA['id']['valor']) throw new \Exception("Error al insertar el formato.");

            $db->commit();
            return self::resultado(true, 'Formato registrado correctamente.', ['formatoId' => $retA['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollBack();
            return self::resultado(false, 'Error al registrar el formato.', null, $e->getMessage());
        }
    }

    public static function getListaFormatosCultiva($datos)
    {
        $qry = <<<SQL
            SELECT
                ID
                , NOMBRE
                , TO_CHAR(FECHA_SUBIDA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_SUBIDA
                , TO_CHAR(VIGENCIA_FIN, 'YYYY-MM-DD') AS VIGENCIA_FIN
                , ACCESO
            FROM
                REPOSITORIO_CAPITALH@DB_CULTIVA
            WHERE
                TRUNC(FECHA_SUBIDA) BETWEEN TO_DATE(:fechaI, 'YYYY-MM-DD') AND TO_DATE(:fechaF, 'YYYY-MM-DD')
            ORDER BY
                FECHA_SUBIDA DESC
        SQL;

        $val = [
            'fechaI' => $datos['fechaI'],
            'fechaF' => $datos['fechaF']
        ];

        try {
            $db = new Database();
            $res = $db->queryAll($qry, $val);
            return self::resultado(true, 'Formatos obtenidos correctamente.', $res);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener los formatos.', null, $e->getMessage());
        }
    }

    public static function getFormatoCultiva($datos)
    {
        $qry = "SELECT ARCHIVO, TIPO, NOMBRE FROM REPOSITORIO_CAPITALH@DB_CULTIVA WHERE ID = :idFormato";

        $val = [
            'idFormato' => $datos['idFormato']
        ];

        try {
            $db = new Database();
            $res = $db->queryOne($qry, $val);
            if (!$res) throw new \Exception("Formato no encontrado.");
            return self::resultado(true, 'Formato obtenido correctamente.', $res);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener el formato.', null, $e->getMessage());
        }
    }

    public static function registraFormatoCultiva($datos)
    {
        $qryA = <<<SQL
            INSERT INTO REPOSITORIO_CAPITALH (ARCHIVO, NOMBRE, TIPO)
            VALUES (EMPTY_BLOB(), :nombre, :tipo)
            RETURNING ARCHIVO, ID INTO :archivo, :id
        SQL;

        $valA = [
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo']
        ];

        $retA = [
            'archivo' => [
                'valor' => $datos['archivo'],
                'tipo' => \PDO::PARAM_LOB
            ],
            'id' => [
                'valor' => '',
                'tipo' => \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT,
                'largo' => 40
            ]
        ];

        try {
            $db = new Database('cultiva');
            $db->beginTransaction();
            $db->CRUD($qryA, $valA, $retA);

            if (!$retA['id']['valor']) throw new \Exception("Error al insertar el formato.");

            $db->commit();
            return self::resultado(true, 'Formato registrado correctamente.', ['formatoId' => $retA['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollBack();
            return self::resultado(false, 'Error al registrar el formato.', null, $e->getMessage());
        }
    }
}
