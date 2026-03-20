#!/usr/bin/env php
<?php
/**
 * @file    scripts/seed_datos_prueba.php
 * @brief   Carga 30 operaciones PLD de prueba en 3 grupos:
 *          A) 10 que SÍ superan el umbral individualmente (vehículos nuevos)
 *          B) 10 que NO superan el umbral (vehículos usados, montos bajos)
 *          C) 10 que superan el umbral de forma ACUMULADA (mismo cliente, 6 meses)
 *
 * Uso:
 *   docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/seed_datos_prueba.php
 *
 * RFC/CURP generados siguiendo el algoritmo oficial SAT:
 *   Pos 1-4  : 1a letra ap.paterno + 1a vocal interna ap.paterno + 1a letra ap.materno + 1a letra nombre
 *   Pos 5-10 : fecha nacimiento YYMMDD
 *   Pos 11-13: homoclave (3 chars alfanuméricos, ficticia pero con formato válido)
 *
 * Umbrales vigentes (LFPIORPI Art.17 Fracc.VIII — 2026):
 *   Vehículo nuevo : $377,778.20 MXN
 *   Vehículo usado : $117,310.00 MXN
 *
 * NOTA sobre Grupo C:
 *   Las 10 operaciones acumuladas tienen requiere_aviso=0 individualmente.
 *   La detección de estructuración/acumulación requiere un proceso batch
 *   separado (pendiente de Fase 4 o proceso manual del oficial de cumplimiento).
 */

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}
if (!defined('NOLOGIN')) {
    define('NOLOGIN', '1');   // Script CLI: carga usuario manualmente debajo
}

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: usar PHP CLI, no CGI.\n";
    exit(-1);
}

@set_time_limit(0);
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

// Bootstrap Dolibarr
$res = 0;
$paths = array(
    '/var/www/html/main.inc.php',
    __DIR__.'/../../../main.inc.php',
    __DIR__.'/../../../../main.inc.php',
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) {
        $res = @include $p;
        if ($res) break;
    }
}
if (!$res) {
    die("No se encontró main.inc.php\n");
}

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once __DIR__.'/../class/pldoperacion.class.php';

// -----------------------------------------------------------------------
// Cargar usuario administrador (id=1)
// -----------------------------------------------------------------------
$user->fetch(1);
$user->getrights();

echo "\n========================================\n";
echo " SEED: Datos de prueba PLD — 30 ops\n";
echo "========================================\n\n";

// -----------------------------------------------------------------------
// DATOS — 10 clientes personas físicas
// Formato RFC PF: [ap1][vocal_interna_ap1][ap2][nom1][YYMMDD][homoclave]
// Formato CURP  : mismos 4 chars + YYMMDD + H/M + estado + 3 consonantes + 2 chars
// -----------------------------------------------------------------------
$clientes = array(
    // nombre, apellido_paterno, apellido_materno, rfc, curp, fecha_nac, sex, estado, tel, cp, municipio, colonia, calle, num_ext, actividad
    array('JOSE ELIAS',    'MORENO',    'VALLE',    'MOVJ890516T71', 'MOVJ890516HDFRLS01', '1989-05-16', 'H', 'CDMX',      '5512345678', '06600', 'Cuauhtémoc',      'Doctores',        'Calle Violante',      '45',  '6117000'),
    array('MARIA GUADALUPE','SANTOS',   'RAMIREZ',  'SARM750310KL5', 'SARM750310MJCNMR02', '1975-03-10', 'M', 'Jalisco',   '3312345678', '44100', 'Guadalajara',     'Americana',       'Av. Chapultepec',     '210', '6117000'),
    array('CARLOS ALBERTO', 'HERNANDEZ','TORRES',   'HETC820714RN3', 'HETC820714HNLRRR05', '1982-07-14', 'H', 'Nuevo León','8112345678', '64000', 'Monterrey',       'Centro',          'Padre Mier',          '300', '6117000'),
    array('ANA LUCIA',      'GARCIA',   'MENDEZ',   'GAMA950203PD2', 'GAMA950203MQTRNN08', '1995-02-03', 'M', 'Querétaro', '4421234567', '76000', 'Querétaro',       'Centro Histórico','Andador Libertad',    '12',  '6117000'),
    array('ROBERTO ALEJANDRO','LOPEZ',  'JIMENEZ',  'LOJR780822SA6', 'LOJR780822HJCPMB07', '1978-08-22', 'H', 'Jalisco',   '3398765432', '45000', 'Zapopan',         'Jardines del Sol','Paseo Royal Country', '150', '6117000'),
    array('PATRICIA ELENA', 'MARTINEZ', 'SUAREZ',   'MASP681130TF8', 'MASP681130MVZRRT04', '1968-11-30', 'M', 'Veracruz',  '2291234567', '91700', 'Veracruz',        'Centro',          'Independencia',       '88',  '6117000'),
    array('FERNANDO ANTONIO','REYES',   'GOMEZ',    'REGF920415HN9', 'REGF920415HPLYMR06', '1992-04-15', 'H', 'Puebla',    '2221234567', '72000', 'Puebla',          'El Mirador',      'Blvd. Atlixcáyotl',   '4500','6117000'),
    array('LUCIA ISABEL',   'VARGAS',   'CASTILLO', 'VACL850720GH2', 'VACL850720MDFRSC09', '1985-07-20', 'M', 'CDMX',      '5587654321', '11000', 'Miguel Hidalgo',  'Polanco',         'Masaryk',             '111', '6117000'),
    array('MIGUEL ANGEL',   'FLORES',   'RAMOS',    'FORM710615PK4', 'FORM710615HDFRLMG03','1971-06-15', 'H', 'CDMX',      '5543219876', '07700', 'Gustavo A. Madero','San Juan Ixhuatepec','Cerrada de la Palma','8',  '6117000'),
    array('SILVIA CECILIA', 'TORRES',   'MEDINA',   'TOMS930901WN7', 'TOMS930901MNLRDL05', '1993-09-01', 'M', 'Nuevo León','8187654321', '66400', 'San Nicolás',     'Las Puentes',     'Calle Pino',          '30',  '6117000'),
);

// -----------------------------------------------------------------------
// DATOS — Vehículos
// Formato: marca, modelo, anio, vin(17), tipo(terrestre), estado(nuevo/usado), origen
// VIN: exactamente 17 caracteres alfanuméricos (sin I, O, Q)
// -----------------------------------------------------------------------

// Grupo A — 10 vehículos NUEVOS (un vehículo por operación de alto valor)
$vehiculos_nuevos = array(
    array('TOYOTA',     'CAMRY',          '2024', '4T1BF1FK5EU123001', 'nuevo', 480000.00),
    array('BMW',        '320I',           '2024', 'WBA5A5C55EU234002', 'nuevo', 680000.00),
    array('MERCEDES',   'C200',           '2024', 'WDDWF4KB1FR345003', 'nuevo', 750000.00),
    array('AUDI',       'A4',             '2024', 'WAUZZZ8K7EA456004', 'nuevo', 695000.00),
    array('MAZDA',      'CX-5',           '2024', 'JM3KFBCM9E0567005', 'nuevo', 480000.00),
    array('HONDA',      'CR-V',           '2024', '2HKRM4H78FH678006', 'nuevo', 435000.00),
    array('CHEVROLET',  'TAHOE',          '2024', '1GNSCCKC8FR789007', 'nuevo', 1200000.00),
    array('FORD',       'EXPLORER',       '2024', '1FMHK7F82FGA89008', 'nuevo', 550000.00),
    array('NISSAN',     'FRONTIER',       '2024', '1N6AD0EV5FN901009', 'nuevo', 420000.00),
    array('JEEP',       'GRAND CHEROKEE', '2024', '1C4RJFAG8FC012010', 'nuevo', 510000.00),
);

// Grupo B — 10 vehículos USADOS (montos bajo el umbral de $117,310)
$vehiculos_usados_b = array(
    array('NISSAN',    'TSURU',    '2015', '3N1FB3DD1FK013001', 'usado',  85000.00),
    array('VOLKSWAGEN','POINTER',  '2012', '9BWZZZ9Z4CP014002', 'usado',  45000.00),
    array('CHEVROLET', 'AVEO',     '2018', 'KL1TB5AE9AB015003', 'usado',  98000.00),
    array('FORD',      'FIESTA',   '2016', '3FADP4EJ3GM016004', 'usado',  75000.00),
    array('TOYOTA',    'YARIS',    '2017', 'JTDBT4K30E1017005', 'usado', 110000.00),
    array('HYUNDAI',   'ACCENT',   '2019', 'KMHCM4AC2BU018006', 'usado',  65000.00),
    array('SEAT',      'IBIZA',    '2018', 'VSSZZZ6JZJ2019007', 'usado',  88000.00),
    array('KIA',       'RIO',      '2020', 'KNADN5A38B6020008', 'usado',  95000.00),
    array('RENAULT',   'SANDERO',  '2017', 'VF1BS1BA6FW021009', 'usado',  55000.00),
    array('HONDA',     'FIT',      '2016', 'JHMGD1840ES022010', 'usado',  72000.00),
);

// Grupo C — 10 vehículos USADOS baratos para el mismo cliente (FLORES RAMOS)
// Acumulado total: $142,500 MXN > umbral $117,310
// Individualmente: cada uno < $117,310 → supera_umbral=0 por operación
$vehiculos_acumulados = array(
    // marca, modelo, anio, vin, estado, monto, mes_reportado, fecha_op
    array('NISSAN',    'TSURU',    '2010', '3N1FB3DD1FK031001', 'usado', 12000.00, '202501', '2025-01-08'),
    array('VOLKSWAGEN','GOL',      '2012', '9BWZZZ9Z4CP032002', 'usado', 14500.00, '202501', '2025-01-22'),
    array('CHEVROLET', 'SPARK',    '2014', 'KL1TB5AE9AB033003', 'usado', 18000.00, '202502', '2025-02-05'),
    array('FORD',      'KA',       '2013', '3FADP4EJ3GM034004', 'usado', 11500.00, '202502', '2025-02-19'),
    array('TOYOTA',    'YARIS',    '2015', 'JTDBT4K30E1035005', 'usado', 15000.00, '202503', '2025-03-04'),
    array('HYUNDAI',   'I10',      '2016', 'KMHCM4AC2BU036006', 'usado', 13500.00, '202503', '2025-03-18'),
    array('SEAT',      'IBIZA',    '2017', 'VSSZZZ6JZJ2037007', 'usado', 16000.00, '202504', '2025-04-03'),
    array('KIA',       'PICANTO',  '2018', 'KNADN5A38B6038008', 'usado', 12500.00, '202504', '2025-04-17'),
    array('RENAULT',   'LOGAN',    '2015', 'VF1BS1BA6FW039009', 'usado', 14000.00, '202505', '2025-05-07'),
    array('HONDA',     'FIT',      '2015', 'JHMGD1840ES040010', 'usado', 15500.00, '202506', '2025-06-11'),
);

// -----------------------------------------------------------------------
// HELPERS
// -----------------------------------------------------------------------
$stats = array('clientes' => 0, 'vehiculos' => 0, 'ops_a' => 0, 'ops_b' => 0, 'ops_c' => 0, 'errores' => 0);

function crearCliente($db, $user, $data, &$stats)
{
    // $data: [nombre, ap_paterno, ap_materno, rfc, curp, fecha_nac, sex, estado, tel, cp, municipio, colonia, calle, num_ext, actividad]
    list($nombre, $ap_pat, $ap_mat, $rfc, $curp, $fecha_nac, $sex, $estado, $tel, $cp, $municipio, $colonia, $calle, $num_ext, $actividad) = $data;

    // Verificar si ya existe por RFC extrafield (evitar duplicados)
    $sql = "SELECT ef.fk_object FROM ".MAIN_DB_PREFIX."societe_extrafields ef"
        ." WHERE ef.pld_rfc_validado = '".$db->escape($rfc)."'";
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $row = $db->fetch_object($res);
        echo "  [SKIP] Cliente RFC $rfc ya existe (societe #".$row->fk_object.")\n";
        $soc = new Societe($db);
        $soc->fetch($row->fk_object);
        return $soc;
    }

    $soc = new Societe($db);
    $soc->name      = strtoupper($ap_pat.' '.$ap_mat.', '.$nombre);
    $soc->firstname = strtoupper($nombre);
    $soc->client    = 1;   // cliente
    $soc->fournisseur = 0;
    $soc->phone     = $tel;
    $soc->zip       = $cp;
    $soc->town      = $municipio;
    $soc->address   = $calle.' #'.$num_ext.', '.$colonia;
    $soc->country_id = 1; // México

    $result = $soc->create($user);
    if ($result <= 0) {
        echo "  [ERROR] crear cliente $rfc: ".implode(', ', $soc->errors)."\n";
        $stats['errores']++;
        return null;
    }

    // Extrafields PLD
    $soc->array_options['options_pld_tipo_persona']      = 'fisica';
    $soc->array_options['options_pld_rfc_validado']      = $rfc;
    $soc->array_options['options_pld_curp']              = $curp;
    $soc->array_options['options_pld_fecha_nacimiento']  = $fecha_nac;
    $soc->array_options['options_pld_nacionalidad']      = 'MX';
    $soc->array_options['options_pld_pais_nacimiento']   = 'MX';
    $soc->array_options['options_pld_actividad_economica'] = $actividad;
    $soc->array_options['options_pld_calle']             = strtoupper($calle);
    $soc->array_options['options_pld_numero_exterior']   = $num_ext;
    $soc->array_options['options_pld_colonia']           = strtoupper($colonia);
    $soc->array_options['options_pld_codigo_postal']     = $cp;
    $soc->array_options['options_pld_municipio']         = strtoupper($municipio);
    $soc->array_options['options_pld_estado']            = strtoupper($estado);
    $soc->array_options['options_pld_pais']              = 'MX';
    $soc->array_options['options_pld_cliente_identificado'] = 1;
    $soc->array_options['options_pld_expediente_completo']  = 1;
    $soc->array_options['options_pld_tiene_beneficiario']   = 0;
    $soc->array_options['options_pld_es_pep']               = 0;
    $soc->insertExtraFields();

    $stats['clientes']++;
    echo "  [OK] Cliente #".$soc->id." — $rfc — ".strtoupper($nombre.' '.$ap_pat.' '.$ap_mat)."\n";
    return $soc;
}

function crearVehiculo($db, $user, $marca, $modelo, $anio, $vin, $estado_veh, &$stats)
{
    // Verificar si ya existe por VIN
    $sql = "SELECT ef.fk_object FROM ".MAIN_DB_PREFIX."product_extrafields ef"
        ." WHERE ef.pld_vin = '".$db->escape($vin)."'";
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $row = $db->fetch_object($res);
        echo "  [SKIP] Vehículo VIN $vin ya existe (product #".$row->fk_object.")\n";
        $prod = new Product($db);
        $prod->fetch($row->fk_object);
        return $prod;
    }

    $prod = new Product($db);
    $prod->ref     = 'VEH-'.substr($vin, -8);   // ref única basada en últimos 8 chars del VIN
    $prod->label   = strtoupper($marca.' '.$modelo.' '.$anio).' [VIN:'.$vin.']';
    $prod->type    = 0;  // producto físico
    $prod->tosell  = 1;
    $prod->tobuy   = 0;

    $result = $prod->create($user);
    if ($result <= 0) {
        echo "  [ERROR] crear vehículo VIN $vin: ".implode(', ', $prod->errors)." DB:".$db->lasterror()."\n";
        $stats['errores']++;
        return null;
    }

    $prod->array_options['options_pld_tipo_vehiculo']  = 'terrestre';
    $prod->array_options['options_pld_marca']          = strtoupper($marca);
    $prod->array_options['options_pld_modelo']         = strtoupper($modelo);
    $prod->array_options['options_pld_anio_modelo']    = $anio;
    $prod->array_options['options_pld_vin']            = strtoupper($vin);
    $prod->array_options['options_pld_origen']         = 'importado';
    $prod->array_options['options_pld_estado_vehiculo'] = $estado_veh;
    $prod->array_options['options_pld_uso_destino']    = 'particular';
    $prod->array_options['options_pld_nivel_blindaje'] = '0';
    $prod->insertExtraFields();

    $stats['vehiculos']++;
    echo "  [OK] Vehículo #".$prod->id." — $vin — ".strtoupper($marca.' '.$modelo.' '.$anio)."\n";
    return $prod;
}

function crearOperacion($db, $user, $societe, $product, $monto, $tipo_veh, $mes, $fecha_op, $grupo, &$stats)
{
    $op = new PLDOperacion($db);
    $op->fk_societe              = $societe->id;
    $op->fk_product              = $product->id;
    $op->tipo_operacion          = 'venta_vehiculo';
    $op->tipo_actividad_vulnerable = 'VIII';
    $op->fecha_operacion         = $fecha_op;
    $op->mes_reportado           = $mes;
    $op->moneda                  = 'MXN';
    $op->monto_mxn               = (float)$monto;
    $op->cliente_identificado    = 1;
    $op->documentacion_completa  = 1;
    $op->aviso_presentado        = 0;
    $op->genera_alerta           = 0;
    $op->estado                  = 'pendiente';

    // Evaluar umbral
    $eval = $op->evaluarUmbral($tipo_veh);
    $op->generarFolioInterno();

    $result = $op->create($user);
    if ($result <= 0) {
        echo "  [ERROR] crear operación: ".implode(', ', $op->errors)."\n";
        $stats['errores']++;
        return null;
    }

    $umbral_label = $eval['supera_umbral'] ? 'SUPERA UMBRAL → aviso requerido' : 'bajo umbral';
    $stats['ops_'.$grupo]++;
    echo "  [OK] Op #".$op->id." {$op->folio_interno} — MXN ".number_format($monto, 0, '.', ',')." — $umbral_label\n";
    return $op;
}

// -----------------------------------------------------------------------
// CREAR CLIENTES
// -----------------------------------------------------------------------
echo "── CLIENTES ─────────────────────────────\n";
$societes = array();
foreach ($clientes as $c) {
    $soc = crearCliente($db, $user, $c, $stats);
    $societes[] = $soc;
}
$cliente_acumulado = $societes[8]; // MIGUEL ANGEL FLORES RAMOS (índice 8)

// -----------------------------------------------------------------------
// GRUPO A — 10 ops que SÍ superan umbral individualmente
// -----------------------------------------------------------------------
echo "\n── GRUPO A: Superan umbral individualmente (vehículos nuevos) ─\n";
$fechas_a = array(
    '2024-11-04','2024-11-06','2024-11-08','2024-11-12','2024-11-14',
    '2024-11-18','2024-11-20','2024-11-22','2024-11-26','2024-11-28',
);
foreach ($vehiculos_nuevos as $i => $veh) {
    list($marca, $modelo, $anio, $vin, $estado_veh, $monto) = $veh;
    $soc = $societes[$i] ?? $societes[0];
    if (!$soc) continue;
    $prod = crearVehiculo($db, $user, $marca, $modelo, $anio, $vin, $estado_veh, $stats);
    if (!$prod) continue;
    crearOperacion($db, $user, $soc, $prod, $monto, 'nuevo', '202411', $fechas_a[$i], 'a', $stats);
}

// -----------------------------------------------------------------------
// GRUPO B — 10 ops que NO superan umbral (vehículos usados, montos bajos)
// -----------------------------------------------------------------------
echo "\n── GRUPO B: No superan umbral individualmente (vehículos usados) ─\n";
$fechas_b = array(
    '2024-12-03','2024-12-05','2024-12-09','2024-12-11','2024-12-13',
    '2024-12-17','2024-12-18','2024-12-19','2024-12-20','2024-12-23',
);
foreach ($vehiculos_usados_b as $i => $veh) {
    list($marca, $modelo, $anio, $vin, $estado_veh, $monto) = $veh;
    $soc = $societes[$i] ?? $societes[0];
    if (!$soc) continue;
    $prod = crearVehiculo($db, $user, $marca, $modelo, $anio, $vin, $estado_veh, $stats);
    if (!$prod) continue;
    crearOperacion($db, $user, $soc, $prod, $monto, 'usado', '202412', $fechas_b[$i], 'b', $stats);
}

// -----------------------------------------------------------------------
// GRUPO C — 10 ops acumuladas del mismo cliente (FLORES RAMOS)
// Total: $142,500 MXN > umbral $117,310
// -----------------------------------------------------------------------
echo "\n── GRUPO C: Acumuladas — cliente FLORES RAMOS (6 meses) ─\n";
echo "   (Individualmente bajo umbral; acumulado = \$142,500 > \$117,310)\n";
foreach ($vehiculos_acumulados as $veh) {
    list($marca, $modelo, $anio, $vin, $estado_veh, $monto, $mes, $fecha_op) = $veh;
    if (!$cliente_acumulado) continue;
    $prod = crearVehiculo($db, $user, $marca, $modelo, $anio, $vin, $estado_veh, $stats);
    if (!$prod) continue;
    crearOperacion($db, $user, $cliente_acumulado, $prod, $monto, 'usado', $mes, $fecha_op, 'c', $stats);
}

// -----------------------------------------------------------------------
// RESUMEN
// -----------------------------------------------------------------------
echo "\n========================================\n";
echo " RESUMEN FINAL\n";
echo "========================================\n";
echo " Clientes creados   : ".$stats['clientes']."\n";
echo " Vehículos creados  : ".$stats['vehiculos']."\n";
echo " Operaciones Grupo A: ".$stats['ops_a']." (superan umbral → requiere_aviso=1)\n";
echo " Operaciones Grupo B: ".$stats['ops_b']." (bajo umbral → requiere_aviso=0)\n";
echo " Operaciones Grupo C: ".$stats['ops_c']." (acumuladas → requiere_aviso=0 individual)\n";
echo " Errores            : ".$stats['errores']."\n";
echo "\n NOTA Grupo C: La detección de estructuración/acumulación requiere\n";
echo " proceso batch del oficial de cumplimiento (pendiente Fase 4).\n";
echo " Para marcar estas ops como 'requiere_aviso=1' manualmente:\n";
echo "   UPDATE llx_pld_operacion SET requiere_aviso=1\n";
echo "   WHERE fk_societe = ".(($cliente_acumulado) ? $cliente_acumulado->id : '?')." AND mes_reportado BETWEEN '202501' AND '202506';\n";
echo "\n";

$db->close();
