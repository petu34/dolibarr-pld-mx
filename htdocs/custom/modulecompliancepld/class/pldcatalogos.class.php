<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

/**
 * Catálogos SAT para avisos PLD (SPPLD)
 * Fuente: XSDs de veh.xsd — https://sppld.sat.gob.mx/pld/documentos/links/xsd/
 *
 * Todos los métodos son estáticos; no requiere instancia.
 */
class PLDCatalogos
{
    // -----------------------------------------------------------------------
    // Forma de pago — Art. 17 fracción VIII + catálogo SAT SPPLD veh.xsd
    // 01=Efectivo, 02=Cheque nominativo, 03=Transferencia, 04=Tarjeta de crédito
    // 05=Tarjeta de débito, 06=Monedero electrónico, 07=Dinero electrónico, 08=Vales
    // -----------------------------------------------------------------------

    /** @var array<string,string> Mapa valor_interno → código SAT */
    private static array $FORMA_PAGO = [
        // Efectivo
        '01' => '01', 'efectivo' => '01', '1' => '01',
        // Cheque nominativo
        '02' => '02', 'cheque' => '02', '2' => '02', 'cheque_nominativo' => '02',
        // Transferencia electrónica
        '03' => '03', 'transferencia' => '03', '3' => '03',
        // Tarjeta de crédito
        '04' => '04', 'tarjeta_credito' => '04', '4' => '04',
        // Tarjeta de débito
        '05' => '05', 'tarjeta_debito' => '05', '5' => '05',
        // Monedero electrónico
        '06' => '06', 'monedero' => '06', '6' => '06',
        // Dinero electrónico
        '07' => '07', 'dinero_electronico' => '07', '7' => '07',
        // Vales de despensa u otros
        '08' => '08', 'vales' => '08', '8' => '08',
    ];

    // -----------------------------------------------------------------------
    // Tipo de vehículo
    // 01=Terrestre, 02=Aéreo, 03=Marítimo
    // -----------------------------------------------------------------------

    private static array $TIPO_VEHICULO = [
        '01' => '01', 'terrestre' => '01',
        '02' => '02', 'aereo' => '02', 'aéreo' => '02',
        '03' => '03', 'maritimo' => '03', 'marítimo' => '03',
    ];

    // -----------------------------------------------------------------------
    // Clase de vehículo (primer nivel por tipo)
    // Para terrestre: 01=Automóvil, 02=Camioneta pick-up, 03=Camioneta SUV,
    //   04=Microbús, 05=Autobús, 06=Camión de carga, 07=Tractocamión,
    //   08=Motocicleta, 09=Cuatrimoto, 10=Vehículo especial
    // Para aéreo: 01=Avioneta, 02=Helicóptero, 03=Avión
    // Para marítimo: 01=Lancha, 02=Yate, 03=Barco
    // -----------------------------------------------------------------------

    private static array $CLASE_VEHICULO = [
        // Terrestre
        'automovil'     => '01', 'automóvil' => '01', 'auto' => '01',
        'camioneta_pickup' => '02', 'pick-up' => '02', 'pickup' => '02',
        'suv'           => '03', 'camioneta_suv' => '03',
        'microbus'      => '04', 'microbús' => '04',
        'autobus'       => '05', 'autobús' => '05', 'bus' => '05',
        'camion_carga'  => '06', 'camión' => '06',
        'tractocamion'  => '07', 'tractocamión' => '07',
        'motocicleta'   => '08', 'moto' => '08',
        'cuatrimoto'    => '09',
        'especial'      => '10',
        // Aéreo
        'avioneta'      => '01',
        'helicoptero'   => '02', 'helicóptero' => '02',
        'avion'         => '03', 'avión' => '03',
        // Marítimo
        'lancha'        => '01',
        'yate'          => '02',
        'barco'         => '03',
        // Pass-through para códigos ya correctos
        '01' => '01', '02' => '02', '03' => '03', '04' => '04',
        '05' => '05', '06' => '06', '07' => '07', '08' => '08',
        '09' => '09', '10' => '10',
    ];

    // -----------------------------------------------------------------------
    // Uso del vehículo
    // 01=Particular, 02=Comercial, 03=Transporte público
    // -----------------------------------------------------------------------

    private static array $USO_VEHICULO = [
        '01' => '01', 'particular' => '01',
        '02' => '02', 'comercial' => '02',
        '03' => '03', 'transporte' => '03', 'transporte_publico' => '03',
    ];

    // -----------------------------------------------------------------------
    // Origen del vehículo
    // 01=Nacional, 02=Importado
    // -----------------------------------------------------------------------

    private static array $ORIGEN_VEHICULO = [
        '01' => '01', 'nacional' => '01',
        '02' => '02', 'importado' => '02', 'extranjero' => '02',
    ];

    // -----------------------------------------------------------------------
    // Métodos de resolución
    // -----------------------------------------------------------------------

    /**
     * Resuelve el código SAT de forma de pago.
     *
     * @param string $valor  Código o nombre interno (ej: '04', 'transferencia')
     * @return string  Código SAT de 2 dígitos (default '01' si no reconocido)
     */
    public static function formaPago(string $valor): string
    {
        $key = strtolower(trim($valor));
        return self::$FORMA_PAGO[$key] ?? '01';
    }

    /**
     * Resuelve el código SAT del tipo de vehículo.
     *
     * @param string $valor  'terrestre', 'aereo', 'maritimo' o código '01'-'03'
     * @return string  Código SAT de 2 dígitos (default '01')
     */
    public static function tipoVehiculo(string $valor): string
    {
        $key = strtolower(trim($valor));
        return self::$TIPO_VEHICULO[$key] ?? '01';
    }

    /**
     * Resuelve el código SAT de clase de vehículo.
     * Para el XSD de vehículos, el campo clase depende del tipo:
     * si no se conoce el subtipo exacto, devuelve '01' (automóvil/avioneta/lancha).
     *
     * @param string $subtipo   Subtipo interno (ej: 'suv', 'motocicleta', '08')
     * @return string  Código SAT de 2 dígitos (default '01')
     */
    public static function claseVehiculo(string $subtipo): string
    {
        $key = strtolower(trim($subtipo));
        return self::$CLASE_VEHICULO[$key] ?? '01';
    }

    /**
     * Resuelve el código SAT del uso del vehículo.
     *
     * @param string $valor  'particular', 'comercial', 'transporte' o código
     * @return string  Código SAT de 2 dígitos (default '01')
     */
    public static function usoVehiculo(string $valor): string
    {
        $key = strtolower(trim($valor));
        return self::$USO_VEHICULO[$key] ?? '01';
    }

    /**
     * Resuelve el código SAT del origen del vehículo.
     *
     * @param string $valor  'nacional', 'importado' o código '01'/'02'
     * @return string  Código SAT de 2 dígitos (default '01')
     */
    public static function origenVehiculo(string $valor): string
    {
        $key = strtolower(trim($valor));
        return self::$ORIGEN_VEHICULO[$key] ?? '01';
    }

    // -----------------------------------------------------------------------
    // Tablas de referencia para UI (admin/setup.php)
    // -----------------------------------------------------------------------

    /** @return array<string,string> Código SAT => etiqueta */
    public static function tablaFormaPago(): array
    {
        return [
            '01' => 'Efectivo',
            '02' => 'Cheque nominativo',
            '03' => 'Transferencia electrónica de fondos',
            '04' => 'Tarjeta de crédito',
            '05' => 'Tarjeta de débito',
            '06' => 'Monedero electrónico',
            '07' => 'Dinero electrónico',
            '08' => 'Vales de despensa u otros medios',
        ];
    }

    /** @return array<string,string> Código SAT => etiqueta */
    public static function tablaTipoVehiculo(): array
    {
        return [
            '01' => 'Terrestre',
            '02' => 'Aéreo',
            '03' => 'Marítimo',
        ];
    }

    /** @return array<string,string> Código SAT => etiqueta */
    public static function tablaClaseVehiculo(): array
    {
        return [
            '01' => 'Automóvil / Avioneta / Lancha',
            '02' => 'Camioneta pick-up / Helicóptero / Yate',
            '03' => 'Camioneta SUV / Avión / Barco',
            '04' => 'Microbús',
            '05' => 'Autobús',
            '06' => 'Camión de carga',
            '07' => 'Tractocamión',
            '08' => 'Motocicleta',
            '09' => 'Cuatrimoto',
            '10' => 'Vehículo especial',
        ];
    }

    /** @return array<string,string> Código SAT => etiqueta */
    public static function tablaUsoVehiculo(): array
    {
        return [
            '01' => 'Particular',
            '02' => 'Comercial',
            '03' => 'Transporte público',
        ];
    }

    /** @return array<string,string> Código SAT => etiqueta */
    public static function tablaOrigenVehiculo(): array
    {
        return [
            '01' => 'Nacional',
            '02' => 'Importado',
        ];
    }
}
