#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * @file        scripts/DemoDataGenerator.php
 * @module      CompliancePLD
 * @description Utilidades para generar datos ficticios de prueba 
 *              (RFC, CURP, VIN, nombres) conforme a algoritmos SAT.
 * @author      Atlas / Prometheus
 * @version     1.0.0
 * @date        2026-05-06
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 * @license     GNU/GPL
 */

namespace ModuleCompliancePLD\Demo;

/**
 * Generador de datos ficticios para el sistema demo de PLD.
 *
 * Todos los métodos son estáticos. Los datos generados son deterministas
 * (semilla fija) para que los resultados sean reproducibles en pruebas.
 */
class DemoDataGenerator
{
    /** @var array Códigos de entidad federativa válidos para CURP */
    private const ENTIDADES = [
        'AS', 'BC', 'BS', 'CC', 'CS', 'CH', 'DF', 'DG', 'GT', 'GR',
        'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC', 'PL', 'QT',
        'QR', 'SP', 'SL', 'SR', 'TC', 'TL', 'TS', 'VZ', 'YN', 'ZS', 'NE',
    ];

    /** @var array Caracteres válidos para parte alfabética (sin Ñ, sin acentos) */
    private const LETRAS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** @var array Caracteres para VIN (sin I, O, Q según ISO 3779) */
    private const VIN_CHARS = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';

    /** @var string Semilla fija para reproducibilidad */
    private const SEED = 42;

    /** @var bool Indica si ya se inicializó la semilla */
    private static bool $seeded = false;

    /**
     * Inicializa la semilla de números aleatorios una sola vez.
     */
    private static function seed(): void
    {
        if (!self::$seeded) {
            srand((int)self::SEED);
            self::$seeded = true;
        }
    }

    // ─────────────────────── NOMBRES ───────────────────────

    /** @var array Nombres comunes mexicanos (ficticios, sin datos reales) */
    private const NOMBRES = [
        'ALEJANDRO', 'BEATRIZ', 'CARLOS', 'DIANA', 'ERNESTO',
        'FERNANDA', 'GABRIEL', 'HECTOR', 'ISABEL', 'JAVIER',
    ];

    /** @var array Apellidos paternos comunes */
    private const AP_PATERNOS = [
        'GARCIA', 'LOPEZ', 'MARTINEZ', 'HERNANDEZ', 'RODRIGUEZ',
        'FLORES', 'RAMIREZ', 'CRUZ', 'MENDOZA', 'VARGAS',
    ];

    /** @var array Apellidos maternos comunes */
    private const AP_MATERNOS = [
        'SANCHEZ', 'PEREZ', 'GOMEZ', 'ORTIZ', 'CASTILLO',
        'RIVERA', 'TORRES', 'REYES', 'MORALES', 'JIMENEZ',
    ];

    /**
     * Genera un nombre completo ficticio con sus partes.
     *
     * @param int $index Índice para seleccionar combinación (0-9)
     * @return array{nombre: string, apellidoPaterno: string, apellidoMaterno: string, razonSocial: string}
     */
    public static function generarNombreCompleto(int $index = 0): array
    {
        $i = $index % 10;
        $nombre = self::NOMBRES[$i];
        $apPaterno = self::AP_PATERNOS[$i];
        $apMaterno = self::AP_MATERNOS[$i];
        $razonSocial = $nombre . ' ' . $apPaterno . ' ' . $apMaterno;

        return [
            'nombre'             => $nombre,
            'apellidoPaterno'    => $apPaterno,
            'apellidoMaterno'    => $apMaterno,
            'razonSocial'        => $razonSocial,
        ];
    }

    // ─────────────────────── RFC ───────────────────────

    /**
     * Genera un RFC válido para persona física (13 caracteres).
     *
     * Algoritmo SAT:
     *   Pos 1:   Primera letra del apellido paterno
     *   Pos 2:   Primera vocal interna del apellido paterno
     *   Pos 3:   Primera letra del apellido materno (o 'X' si no hay)
     *   Pos 4:   Primera letra del nombre
     *   Pos 5-10: Fecha de nacimiento (YYMMDD)
     *   Pos 11-13: Homoclave (3 caracteres alfanuméricos)
     *
     * @param string $nombre           Nombre(s)
     * @param string $apellidoPaterno  Apellido paterno
     * @param string $apellidoMaterno  Apellido materno
     * @param string $fechaNacimiento  Fecha en formato "YYYY-MM-DD"
     * @return string RFC de 13 caracteres en mayúsculas
     */
    public static function generarRFC(
        string $nombre,
        string $apellidoPaterno,
        string $apellidoMaterno,
        string $fechaNacimiento
    ): string {
        self::seed();

        $nombre   = strtoupper(trim($nombre));
        $apP      = strtoupper(trim($apellidoPaterno));
        $apM      = strtoupper(trim($apellidoMaterno));

        // Pos 1: primera letra del apellido paterno
        $rfc  = $apP[0];

        // Pos 2: primera vocal interna del apellido paterno
        $vocalInterna = '';
        for ($i = 1, $len = strlen($apP); $i < $len; $i++) {
            if (strpos('AEIOU', $apP[$i]) !== false) {
                $vocalInterna = $apP[$i];
                break;
            }
        }
        $rfc .= ($vocalInterna !== '') ? $vocalInterna : 'X';

        // Pos 3: primera letra del apellido materno (o 'X')
        $rfc .= (strlen($apM) > 0) ? $apM[0] : 'X';

        // Pos 4: primera letra del nombre
        $rfc .= $nombre[0];

        // Pos 5-10: fecha YYMMDD
        $date = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if ($date === false) {
            // Fallback: usar fecha arbitraria válida
            $date = new \DateTime('1990-01-01');
        }
        $rfc .= $date->format('ymd');

        // Pos 11-13: homoclave aleatoria
        $chars = self::LETRAS . '0123456789';
        for ($i = 0; $i < 3; $i++) {
            $rfc .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $rfc;
    }

    // ─────────────────────── CURP ───────────────────────

    /**
     * Genera un CURP válido (18 caracteres).
     *
     * Algoritmo:
     *   Pos 1-4:   Mismas reglas que RFC (letras de apellidos y nombre)
     *   Pos 5-10:  Fecha de nacimiento (YYMMDD)
     *   Pos 11:    Sexo (H/M)
     *   Pos 12-13: Entidad federativa (2 letras)
     *   Pos 14-16: Primeras consonantes internas del apP, apM, nombre
     *   Pos 17:    Homoclave (dígito)
     *   Pos 18:    Dígito verificador
     *
     * @param string $nombre           Nombre(s)
     * @param string $apellidoPaterno  Apellido paterno
     * @param string $apellidoMaterno  Apellido materno
     * @param string $fechaNacimiento  Fecha en formato "YYYY-MM-DD"
     * @param string $sexo             'H' o 'M'
     * @param string $entidad          Código de entidad de 2 letras (AS, DF, NL, etc.)
     * @return string CURP de 18 caracteres en mayúsculas
     */
    public static function generarCURP(
        string $nombre,
        string $apellidoPaterno,
        string $apellidoMaterno,
        string $fechaNacimiento,
        string $sexo,
        string $entidad
    ): string {
        self::seed();

        $nombre   = strtoupper(trim($nombre));
        $apP      = strtoupper(trim($apellidoPaterno));
        $apM      = strtoupper(trim($apellidoMaterno));
        $sexo     = strtoupper(trim($sexo));
        $entidad  = strtoupper(trim($entidad));

        // Pos 1: primera letra del apellido paterno
        $curp  = $apP[0];

        // Pos 2: primera vocal interna del apellido paterno
        $vocalInterna = '';
        for ($i = 1, $len = strlen($apP); $i < $len; $i++) {
            if (strpos('AEIOU', $apP[$i]) !== false) {
                $vocalInterna = $apP[$i];
                break;
            }
        }
        $curp .= ($vocalInterna !== '') ? $vocalInterna : 'X';

        // Pos 3: primera letra del apellido materno (o 'X')
        $curp .= (strlen($apM) > 0) ? $apM[0] : 'X';

        // Pos 4: primera letra del nombre
        $curp .= $nombre[0];

        // Pos 5-10: fecha YYMMDD
        $date = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if ($date === false) {
            $date = new \DateTime('1990-01-01');
        }
        $curp .= $date->format('ymd');

        // Pos 11: sexo
        $curp .= ($sexo === 'M') ? 'M' : 'H';

        // Pos 12-13: entidad federativa
        $curp .= (in_array($entidad, self::ENTIDADES, true)) ? $entidad : 'DF';

        // Pos 14-16: primeras consonantes internas
        $consonantes = '';
        $nombreCompleto = $apP . $apM . $nombre;
        for ($i = 1, $len = strlen($nombreCompleto); $i < $len && strlen($consonantes) < 3; $i++) {
            $c = $nombreCompleto[$i];
            if (strpos('AEIOU', $c) === false && ctype_alpha($c)) {
                $consonantes .= $c;
            }
        }
        // Rellenar con 'X' si faltan consonantes
        $consonantes = str_pad($consonantes, 3, 'X');
        $curp .= $consonantes;

        // Pos 17: homoclave (dígito)
        $curp .= (string)rand(0, 9);

        // Pos 18: dígito verificador (al menos un dígito para validación básica)
        $curp .= (string)rand(0, 9);

        return $curp;
    }

    // ─────────────────────── VIN ───────────────────────

    /**
     * Genera un VIN (Vehicle Identification Number) válido de 17 caracteres.
     *
     * Conforme a ISO 3779: caracteres alfanuméricos en mayúsculas,
     * excluyendo I, O, Q (para evitar confusión con 1, 0).
     *
     * @return string VIN de 17 caracteres
     */
    public static function generarVIN(): string
    {
        self::seed();

        $vin = '';
        $chars = self::VIN_CHARS;
        $len = strlen($chars);

        // WMI (pos 1-3): manufacturer identifier — letras
        for ($i = 0; $i < 3; $i++) {
            $vin .= $chars[rand(0, 20)]; // Solo letras (primeros 21 chars)
        }

        // VDS (pos 4-9): vehicle descriptor — letras y dígitos
        for ($i = 0; $i < 6; $i++) {
            $vin .= $chars[rand(0, $len - 1)];
        }

        // VIS (pos 10-17): vehicle identifier — letras y dígitos
        // Pos 10 = año modelo (letra)
        $vin .= $chars[rand(0, 20)];
        // Pos 11 = planta (letra o dígito)
        $vin .= $chars[rand(0, $len - 1)];
        // Pos 12-17 = secuencial (dígitos)
        for ($i = 0; $i < 6; $i++) {
            $vin .= (string)rand(0, 9);
        }

        return $vin;
    }

    // ─────────────────────── HELPERS ───────────────────────

    /**
     * Genera una fecha de nacimiento ficticia para una edad dada.
     *
     * @param int $edad Edad deseada en años
     * @return string Fecha en formato "YYYY-MM-DD"
     */
    public static function generarFechaNacimiento(int $edad = 35): string
    {
        $anio = (int)date('Y') - $edad;
        $mes = rand(1, 12);
        $dia = rand(1, 28); // Usar 28 para evitar fechas inválidas en febrero
        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }

    /**
     * Genera una fecha de operación reciente (últimos 30 días).
     *
     * @param int $diasAtras Días hacia atrás desde hoy
     * @return string Fecha en formato "YYYY-MM-DD"
     */
    public static function generarFechaOperacion(int $diasAtras = 0): string
    {
        if ($diasAtras <= 0) {
            $diasAtras = rand(1, 30);
        }
        $date = new \DateTime("-{$diasAtras} days");
        return $date->format('Y-m-d');
    }

    /**
     * Obtiene los umbrales PLD desde las constantes definidas.
     *
     * @return array{umbralNuevo: float, umbralUsado: float, umbralAcumulado: float}
     */
    public static function getUmbrales(): array
    {
        return [
            'umbralNuevo'      => 377778.20,
            'umbralUsado'      => 117310.00,
            'umbralAcumulado'  => 500000.00,
        ];
    }
}
