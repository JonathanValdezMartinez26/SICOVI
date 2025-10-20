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

    public static function getPersonas($datos)
    {
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
                P.ID
                , GET_NOMBRE_PERSONA(P.ID) AS NOMBRE_COMPLETO
                , P.RFC
                , P.CURP
                , TO_CHAR(P.FECHA_NACIMIENTO, 'YYYY-MM-DD') AS FECHA_NACIMIENTO
                , P.SEXO
                , P.ESTATUS
                , CASE WHEN P.FOTO IS NOT NULL THEN P.ID ELSE NULL END AS FOTO
                , TO_CHAR(N.INGRESO, 'YYYY-MM-DD') AS FECHA_INGRESO
                , CP.DESCRIPCION AS PUESTO
                , SR.EMPRESA
                , SR.EMPRESA_NOMBRE
            FROM
                PERSONA P
                LEFT JOIN NOMINA N ON N.PERSONA = P.ID
                LEFT JOIN CAT_PUESTOS CP ON CP.ID_PUESTO = N.PUESTO
                LEFT JOIN SUCURSALES_REGIONES SR ON SR.EMPRESA = N.EMPRESA AND SR.REGION = N.REGION AND SR.SUCURSAL = N.SUCURSAL
            WHERE
                FILTROS
            ORDER BY P.NOMBRE, P.APELLIDO_1, P.APELLIDO_2
            SQL;

        $where = ['P.ESTATUS = 1'];
        $params = [];

        if ($datos['filtroColaborador']) {
            $filtro = $datos['filtroColaborador'];
            $where[] = "(UPPER(P.NOMBRE) LIKE UPPER(:filtro)"
                . " OR UPPER(P.APELLIDO_1) LIKE UPPER(:filtro)"
                . " OR UPPER(P.APELLIDO_2) LIKE UPPER(:filtro)"
                . " OR UPPER(P.RFC) LIKE UPPER(:filtro)"
                . " OR UPPER(P.CURP) LIKE UPPER(:filtro))";
            $params['filtro'] = "%{$filtro}%";
        }

        if ($datos['empresa']) {
            $where[] = "SR.EMPRESA = :empresa";
            $params['empresa'] = $datos['empresa'];
        }

        if ($datos['region']) {
            $where[] = "SR.REGION = :region";
            $params['region'] = $datos['region'];
        }

        if ($datos['sucursal']) {
            $where[] = "SR.SUCURSAL = :sucursal";
            $params['sucursal'] = $datos['sucursal'];
        }

        $qry = str_replace('FILTROS', implode(' AND ', $where), $qry);

        try {
            $db = new Database();
            $r = $db->queryAll($qry, $params);
            return self::resultado(true, 'Personas encontradas.', $r);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las personas.', null, $e->getMessage());
        }
    }

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
            SELECT U.ID, U.USUARIO, U.ESTATUS, SR.SUCURSAL, SR.SUCURSAL_NOMBRE, U.AUTORIZADOR, P.ID AS PERFIL, P.NOMBRE AS PERFIL_NOMBRE
            FROM USUARIO U
            LEFT JOIN SUCURSALES_REGIONES SR ON SR.EMPRESA = U.EMPRESA AND SR.REGION = U.REGION AND SR.SUCURSAL = U.SUCURSAL
            LEFT JOIN PERFIL P ON P.ID = U.PERFIL
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
        $empHab = 'SELECT ID_EMPRESA AS EMPRESA FROM CAT_PERSONA_EMPRESA_COMPRUEBA_PERMISO WHERE ID_PERSONA = :id';
        $qryBancos = "SELECT ID_BANCO, CB.NOMBRE, NUMERO, TIPO_NUMERO FROM PERSONA_DATOS_BANCARIOS PDB JOIN CAT_BANCO CB ON CB.ID = PDB.ID_BANCO WHERE PERSONA = :id AND PDB.ESTATUS = 'A'";
        $qryTelefonos = "SELECT NUMERO, TIPO FROM PERSONA_TELEFONO WHERE PERSONA = :id AND ESTATUS = 1";
        $qryEmails = "SELECT DIRECCION, TIPO FROM PERSONA_EMAIL WHERE PERSONA = :id AND ESTATUS = 1";
        $qryContactos = "SELECT PCE.ID, PCE.NOMBRE, PCE.TELEFONO, CP.ID_PARENTESCO AS PARENTESCO, CP.DESCRIPCION AS PARENTESCO_NOMBRE FROM PERSONA_CONTACTO_EMERGENCIA PCE JOIN CAT_PARENTESCO_EMERGENCIA CP ON CP.ID_PARENTESCO = PCE.PARENTESCO WHERE PCE.PERSONA = :id AND PCE.ESTATUS = 'A'";

        $params = [
            'id' => $datos['id'] ?? 0
        ];

        try {
            $db = new Database();

            $persona = $db->queryOne($qryPersona, $params);
            if (!$persona) return self::resultado(false, 'Persona no encontrada.');

            $nomina = $db->queryOne($qryNomina, $params);
            $empresa = $db->queryOne($qryEmpresa, $params);
            $empresasHabilitadas = $db->queryAll($empHab, $params);
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
                'empresa' => $empresa,
                'empresasHabilitadas' => $empresasHabilitadas
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
            $qrys = [];
            $params = [];

            // 1) Validar o Crear persona
            if (!$datos['id']) {
                $crea = self::crearPersona($datos, $fotoData, $db);
                if (!$crea['success']) throw new \Exception(self::getErrorMessage($crea) ?? 'Error creando persona');
                $personaId = $crea['datos']['id'];
            } else {
                $personaId = $datos['id'];
                $result = self::actualizarPersona($datos, $fotoData, $db);
                if (!$result['success']) throw new \Exception(self::getErrorMessage($result) ?? 'Error actualizando persona');
            }

            // 2) Telefonos del colaborador
            [$q, $p] = self::upsertTelefonosPersona($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 3) Emails del colaborador
            [$q, $p] = self::upsertEmailsPersona($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 4) Nomina
            [$q, $p] = self::upsertNomina($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 5) Empresas habilitadas
            [$q, $p] = self::upsertEmpresas($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 6) Datos bancarios
            [$q, $p] = self::upsertDatosBancarios($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 7) Contacto emergencia
            [$q, $p] = self::upsertContactoEmergencia($personaId, $datos);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            // 7) Registro de usuario principal para el sistema
            [$q, $p] = self::upsertUsuario($personaId, $datos, $db);
            $qrys = array_merge($qrys, $q);
            $params = array_merge($params, $p);

            $db->CRUD_multiple($qrys, $params);
            return self::resultado(true, 'Registro completado correctamente.');
        } catch (\Exception $e) {
            if (!$datos['id'] && isset($personaId)) {
                try {
                    $dbDel = new Database();
                    $dbDel->beginTransaction();
                    $dbDel->CRUD("DELETE FROM PERSONA WHERE ID = :id", ['id' => $personaId]);
                    $dbDel->commit();
                } catch (\Exception $ex) {
                    // No hacer nada, ya se está en un error
                }
            }
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
            $db->beginTransaction();
            $res = $db->CRUD($qry, $params, $ret);
            if (!$res || !isset($ret['id'])) return self::resultado(false, 'No se pudo insertar persona.');

            $db->commit();
            return self::resultado(true, "Persona registrada", ['id' => $ret['id']['valor']]);
        } catch (\Exception $e) {
            $db->rollBack();
            return self::resultado(false, 'Error al registrar persona: ' . $e->getMessage());
        }
    }

    public static function actualizarPersona($datos, $fotoData = null, $db = null)
    {
        $qry = <<<SQL
            UPDATE PERSONA SET
                NOMBRE = :nombre,
                APELLIDO_1 = :apellido1,
                APELLIDO_2 = :apellido2,
                RFC = :rfc,
                CURP = :curp,
                FECHA_NACIMIENTO = TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'),
                SEXO = :sexo,
                ESTADO_CIVIL = :estado_civil,
                NACIONALIDAD = :nacionalidad,
                NSS = :nss,
                INFONAVIT = :infonavit,
                CALLE_NUMERO = :calle_numero,
                CP = :cp,
                ESTADO = :estado,
                MUNICIPIO = :municipio,
                COLONIA = :colonia,
                CONDICIONES_MEDICAS = :condiciones_medicas,
                OTROS_DATOS_RELEVANTES = :informacion_adicional,
                FOTO = EMPTY_BLOB()
            WHERE ID = :id
            RETURNING FOTO INTO :foto
        SQL;

        $params = [
            'id' => $datos['id'] ?? null,
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
            'foto' => [
                'valor' => $fotoData['foto'] ?? null,
                'tipo' => \PDO::PARAM_LOB
            ]
        ];

        try {
            if (!$db) $db = new Database();
            $db->beginTransaction();
            $db->CRUD($qry, $params, $ret);
            $db->commit();
            return self::resultado(true, 'Foto actualizada correctamente.');
        } catch (\Exception $e) {
            $db->rollBack();
            return self::resultado(false, 'Error al actualizar la foto.', null, $e->getMessage());
        }
    }

    public static function upsertTelefonosPersona($personaId, $datos)
    {
        $qrys[] = "UPDATE PERSONA_TELEFONO SET ESTATUS = 0 WHERE PERSONA = :persona";
        $params[] = ['persona' => $personaId];

        $qry = <<<SQL
            MERGE INTO PERSONA_TELEFONO PT
            USING (SELECT :persona AS PERSONA, :numero AS NUMERO, :tipo AS TIPO FROM DUAL) SRC
            ON (PT.PERSONA = SRC.PERSONA AND PT.TIPO = SRC.TIPO AND PT.NUMERO = SRC.NUMERO)
            WHEN MATCHED THEN
                UPDATE SET ESTATUS = 1
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, NUMERO, TIPO) VALUES (SRC.PERSONA, SRC.NUMERO, SRC.TIPO)
        SQL;

        $telefonos = [
            ['1', $datos['telefonoPrincipal'] ?? null],
            ['2', $datos['telefonoAlterno'] ?? null]
        ];

        foreach ($telefonos as $telefono) {
            if (!empty($telefono[1]) && !is_null($telefono[1])) {
                $qrys[] = $qry;
                $params[] = [
                    'persona' => $personaId,
                    'numero' => $telefono[1],
                    'tipo' => $telefono[0]
                ];
            }
        }

        return [$qrys, $params];
    }

    public static function upsertEmailsPersona($persona, $datos)
    {
        $qrys[] = "UPDATE PERSONA_EMAIL SET ESTATUS = 0 WHERE PERSONA = :persona";
        $params[] = ['persona' => $persona];

        $qry = <<<SQL
            MERGE INTO PERSONA_EMAIL PE
            USING (SELECT :persona AS PERSONA, :direccion AS DIRECCION, :tipo AS TIPO FROM DUAL) SRC
            ON (PE.PERSONA = SRC.PERSONA AND PE.TIPO = SRC.TIPO AND PE.DIRECCION = SRC.DIRECCION)
            WHEN MATCHED THEN
                UPDATE SET ESTATUS = 1
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, DIRECCION, TIPO) VALUES (SRC.PERSONA, SRC.DIRECCION, SRC.TIPO)
        SQL;

        $emails = [
            ['1', $datos['correoPrincipal'] ?? null]
        ];
        $laborales = explode(',', $datos['correoLaboral'] ?? '');
        foreach ($laborales as $email) {
            $emails[] = ['4', $email];
        }

        foreach ($emails as $email) {
            if (!empty($email[1]) && !is_null($email[1])) {
                $qrys[] = $qry;
                $params[] = [
                    'persona' => $persona,
                    'direccion' => $email[1],
                    'tipo' => $email[0]
                ];
            }
        }

        return [$qrys, $params];
    }

    public static function upsertNomina($personaId, $datos)
    {
        $qry = <<<SQL
            MERGE INTO NOMINA N
            USING (SELECT :persona AS PERSONA, :empresa AS EMPRESA, :region AS REGION, :sucursal AS SUCURSAL FROM DUAL) SRC
            ON (N.PERSONA = SRC.PERSONA)
            WHEN MATCHED THEN
                UPDATE SET
                    N.EMPRESA = :empresa,
                    N.REGION = :region,
                    N.SUCURSAL = :sucursal,
                    N.JEFE = :jefe,
                    N.PUESTO = :puesto,
                    N.INGRESO = TO_DATE(:fecha_ingreso, 'YYYY-MM-DD'),
                    N.PROVEEDOR = :proveedor,
                    N.TIPO = :tipo,
                    N.NUMERO = :numero
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, EMPRESA, REGION, SUCURSAL, INGRESO, JEFE, PUESTO, PROVEEDOR, TIPO, NUMERO, ESTATUS)
                VALUES (SRC.PERSONA, SRC.EMPRESA, SRC.REGION, SRC.SUCURSAL, TO_DATE(:fecha_ingreso, 'YYYY-MM-DD'), :jefe, :puesto, :proveedor, :tipo, :numero, 1)
        SQL;

        $params = [
            'persona' => $personaId,
            'empresa' => $datos['empresa'] ?? null,
            'region' => $datos['region'] ?? null,
            'sucursal' => $datos['sucursal'] ?? null,
            'jefe' => $datos['jefeInmediato'] ?? -1,
            'puesto' => $datos['puesto'] ?? null,
            'fecha_ingreso' => $datos['fechaIngreso'] ?? null,
            'proveedor' => $datos['proveedor'] ?? null,
            'tipo' => $datos['tipoNomina'] ?? null,
            'numero' => $datos['numeroNomina'] ?? null
        ];

        return [[$qry], [$params]];
    }

    public static function upsertEmpresas($personaId, $datos)
    {
        $qrys[] = 'DELETE FROM CAT_PERSONA_EMPRESA_COMPRUEBA_PERMISO WHERE ID_PERSONA = :persona';
        $params[] = ['persona' => $personaId];

        $qry = 'INSERT INTO CAT_PERSONA_EMPRESA_COMPRUEBA_PERMISO (ID_PERSONA, ID_EMPRESA, USUARIO_ALTA) VALUES (:persona, :empresa, :usuario)';

        foreach (explode(',', $datos['empresasHabilitadas']) as $empresa) {
            if (!empty($empresa)) {
                $qrys[] = $qry;
                $params[] = ['persona' => $personaId, 'empresa' => $empresa, 'usuario' => $_SESSION['usuario_id'] ?? null];
            }
        }

        return [$qrys, $params];
    }

    public static function upsertDatosBancarios($personaId, $datos)
    {
        $qrys[] = "UPDATE PERSONA_DATOS_BANCARIOS SET ESTATUS = 'I' WHERE PERSONA = :persona";
        $params[] = ['persona' => $personaId];

        $qry = <<<SQL
            MERGE INTO PERSONA_DATOS_BANCARIOS PDB
            USING (SELECT :persona AS PERSONA, :banco AS ID_BANCO, :numero AS NUMERO, :tipo AS TIPO_NUMERO FROM DUAL) SRC
            ON (PDB.PERSONA = SRC.PERSONA AND PDB.ID_BANCO = SRC.ID_BANCO AND PDB.NUMERO = SRC.NUMERO AND PDB.TIPO_NUMERO = SRC.TIPO_NUMERO)
            WHEN MATCHED THEN
                UPDATE SET ESTATUS = 'A'
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, ID_BANCO, NUMERO, TIPO_NUMERO) VALUES (SRC.PERSONA, SRC.ID_BANCO, SRC.NUMERO, SRC.TIPO_NUMERO)
        SQL;

        $qrys[] = $qry;
        $params[] = [
            'persona' => $personaId,
            'banco' => $datos['banco'] ?? null,
            'numero' => $datos['cuentaBancaria'] ?? null,
            'tipo' => 1,
        ];

        $qrys[] = $qry;
        $params[] = [
            'persona' => $personaId,
            'banco' => $datos['banco'] ?? null,
            'numero' => $datos['tarjeta'] ?? null,
            'tipo' => 2,
        ];

        return [$qrys, $params];
    }

    public static function upsertContactoEmergencia($personaId, $datos)
    {
        $qrys[] = "UPDATE PERSONA_CONTACTO_EMERGENCIA SET ESTATUS = 'I' WHERE PERSONA = :persona";
        $params[] = ['persona' => $personaId];

        if (empty($datos['contactoEmergenciaNombre']) && empty($datos['contactoEmergenciaParentesco']) && empty($datos['contactoEmergenciaTelefono']))
            return [$qrys, $params];

        $qrys[] = <<<SQL
            MERGE INTO PERSONA_CONTACTO_EMERGENCIA PCE
            USING (SELECT :persona AS PERSONA, :nombre AS NOMBRE, :parentesco AS PARENTESCO, :telefono AS TELEFONO FROM DUAL) SRC
            ON (PCE.PERSONA = SRC.PERSONA AND PCE.NOMBRE = SRC.NOMBRE AND PCE.PARENTESCO = SRC.PARENTESCO AND PCE.TELEFONO = SRC.TELEFONO)
            WHEN MATCHED THEN
                UPDATE SET ESTATUS = 'A'
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, NOMBRE, PARENTESCO, TELEFONO) VALUES (SRC.PERSONA, SRC.NOMBRE, SRC.PARENTESCO, SRC.TELEFONO)
        SQL;

        $params[] = [
            'persona' => $personaId,
            'nombre' => $datos['contactoEmergenciaNombre'] ?? null,
            'parentesco' => $datos['contactoEmergenciaParentesco'] ?? null,
            'telefono' => $datos['contactoEmergenciaTelefono'] ?? null
        ];

        return [$qrys, $params];
    }

    public static function upsertUsuario($personaId, $datos)
    {
        $qry = <<<SQL
            MERGE INTO USUARIO U
            USING (SELECT :persona AS PERSONA, :empresa AS EMPRESA, :region AS REGION, :sucursal AS SUCURSAL FROM DUAL) SRC
            ON (U.PERSONA = SRC.PERSONA AND U.EMPRESA = SRC.EMPRESA AND U.REGION = SRC.REGION AND U.SUCURSAL = SRC.SUCURSAL)
            WHEN MATCHED THEN
                UPDATE SET
                    U.USUARIO = :usuario,
                    U.PERFIL = :perfil,
                    U.AUTORIZADOR = :autorizador,
                    U.PASS = CASE WHEN :pass IS NULL OR :pass = '' THEN U.PASS ELSE :pass END
            WHEN NOT MATCHED THEN
                INSERT (PERSONA, EMPRESA, REGION, SUCURSAL, USUARIO, PASS, PERFIL, AUTORIZADOR)
                VALUES (SRC.PERSONA, SRC.EMPRESA, SRC.REGION, SRC.SUCURSAL, :usuario, :pass, :perfil, :autorizador)
        SQL;

        $params = [
            'persona' => $personaId,
            'empresa' => $datos['empresa'] ?? null,
            'region' => $datos['region'] ?? null,
            'sucursal' => $datos['sucursal'] ?? null,
            'usuario' => $datos['usuario'] ?? null,
            'pass' => $datos['password'] ?? null,
            'perfil' => $datos['perfil'] ?? null,
            'autorizador' => $datos['reporta'] ?? 0
        ];

        return [[$qry], [$params]];
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

    public static function getSolicitudesRecuperacion()
    {
        $qry = <<<SQL
            SELECT
                VR.ID
                , VR.VIATICOS
                , GET_NOMBRE_USUARIO(V.USUARIO) AS USUARIO_NOMBRE
                , TO_CHAR(VR.FECHA_REGISTRO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_REGISTRO
                , VR.MOTIVO
                , CVR.CODIGO AS ESTATUS_NOMBRE
                , CVR.DESCRIPCION AS ESTATUS_DESCRIPCION
                , NVL(V.COMPROBACION_MONTO, 0) - NVL(V.ENTREGA_MONTO, 0) AS DIFERENCIA
                , V.EMPRESA
                , E.NOMBRE AS EMPRESA_NOMBRE
            FROM
                VIATICOS_RECUPERACION VR
                LEFT JOIN CAT_VIATICOS_RECUPERACION CVR ON VR.ESTATUS = CVR.ID
                LEFT JOIN VIATICOS V ON V.ID = VR.VIATICOS
                LEFT JOIN EMPRESA E ON E.ID = V.EMPRESA
            WHERE
                VR.FECHA_SOLUCION IS NULL
            ORDER BY
                VR.FECHA_REGISTRO ASC
        SQL;

        try {
            $db = new Database();
            $res = $db->queryAll($qry);
            return self::resultado(true, 'Solicitudes obtenidas correctamente.', $res);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al obtener las solicitudes.', null, $e->getMessage());
        }
    }

    public static function recuperacionPorNomina($datos)
    {
        $qrys[] = <<<SQL
            UPDATE
                VIATICOS_RECUPERACION
            SET
                ESTATUS = (SELECT ID FROM CAT_VIATICOS_RECUPERACION WHERE CODIGO = 'RCH')
                , FECHA_SOLUCION = SYSDATE
                , SOLUCION = 'Descuento vía nómina'
                , USUARIO_SOLUCION = :usuario
            WHERE ID = :id
        SQL;

        $params[] = [
            'id' => $datos['caso'],
            'usuario' => $datos['usuario']
        ];

        $qrys[] = <<<SQL
            INSERT INTO VIATICOS_OBSERVACIONES (OBSERVACION, USUARIO, VIATICOS, ESTATUS)
            VALUES ((SELECT DESCRIPCION FROM CAT_VIATICOS_RECUPERACION WHERE CODIGO = 'RCH'), :usuario, :viaticos, (SELECT ESTATUS FROM VIATICOS WHERE ID = :viaticos))
        SQL;

        $params[] = [
            'viaticos' => $datos['viaticos'],
            'usuario' => $datos['usuario']
        ];

        $qrys[] = <<<SQL
            UPDATE
                VIATICOS
            SET
                DIFERENCIA_MONTO = COMPROBACION_MONTO - NVL(ENTREGA_MONTO, 0)   
                , DIFERENCIA_FECHA = SYSDATE
                , DIFERENCIA_USUARIO = :usuario
                , DIFERENCIA_EMPRESA = :empresa
                , DIFERENCIA_REGION = :region
                , DIFERENCIA_SUCURSAL = :sucursal
                , DIFERENCIA_OBSERVACION = (SELECT ID FROM VIATICOS_OBSERVACIONES WHERE VIATICOS = :id ORDER BY ID DESC FETCH FIRST 1 ROW ONLY)
                , ESTATUS = (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'FINALIZADA')
            WHERE
                ID = :id
        SQL;

        $params[] = [
            'id' => $datos['viaticos'],
            'usuario' => $datos['usuario'],
            'empresa' => $datos['empresa'],
            'region' => $datos['region'],
            'sucursal' => $datos['sucursal']
        ];

        try {
            $db = new Database();
            $db->CRUD_multiple($qrys, $params);
            return self::resultado(true, 'Estatus de la solicitud actualizado correctamente.');
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al actualizar el estatus de la solicitud.', null, $e->getMessage());
        }
    }

    public static function delegarSaldoTS($datos)
    {
        $qrys[] = <<<SQL
            UPDATE
                VIATICOS
            SET
                ESTATUS = (SELECT ID FROM CAT_VIATICOS_ESTATUS WHERE NOMBRE = 'VALIDADA')
            WHERE
                ID = :id
        SQL;

        $params[] = [
            'id' => $datos['viaticos']
        ];

        $qrys[] = <<<SQL
            UPDATE
                VIATICOS_RECUPERACION
            SET
                ESTATUS = (SELECT ID FROM CAT_VIATICOS_RECUPERACION WHERE CODIGO = 'CCC')
                , FECHA_SOLUCION = SYSDATE
                , SOLUCION = 'Turnado a Tesorería.'
                , USUARIO_SOLUCION = :usuario
            WHERE
                ID = :id
        SQL;

        $params[] = [
            'id' => $datos['caso'],
            'usuario' => $datos['usuario']
        ];

        try {
            $db = new Database();
            $result = $db->CRUD_multiple($qrys, $params);
            if (!$result) return self::resultado(false, 'No se encontró la solicitud a delegar.');
            return self::resultado(true, 'Solicitud delegada correctamente.', $result);
        } catch (\Exception $e) {
            return self::resultado(false, 'Error al delegar la solicitud.', null, $e->getMessage());
        }
    }
}
