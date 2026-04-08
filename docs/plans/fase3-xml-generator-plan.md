# Plan Fase 3: Compliance PLD México - Generador de XML y Envío Automático

> **Actualizado:** 2026-03-19
> **Estado:** 🟡 PARCIALMENTE COMPLETADA — commit `90affe8` en `fase1/extrafields`

## ✅ Implementado (2026-03-19)

| Componente | Archivo | Estado |
|------------|---------|--------|
| 4 métodos fetch prerequisito (`fetchCliente`, `fetchVehiculo`, `fetchBeneficiarios`, `fetchFormasPago`) | `class/pldoperacion.class.php` | ✅ |
| Motor de generación XML (`veh.xsd`) | `class/pldxmlgenerator.class.php` | ✅ |
| Integración e.firma (firma digital, cadena original, sello) | `class/pldefirmaintegration.class.php` | ✅ |
| UI generador (mes, firma opcional, preview, historial, descarga) | `xml_generator.php` | ✅ |
| Constantes configuración e.firma en admin | `admin/setup.php` | ✅ |
| Panel estado e.firma en about | `admin/about.php` | ✅ |
| Instrucciones instalación e.firma | `README.md` | ✅ |
| Cadenas de idioma | `langs/es_MX/modulecompliancepld.lang` | ✅ |

## ⏳ Pendiente

| Componente | Notas |
|------------|-------|
| Validador XML contra XSD offline | Requiere descargar XSDs del SAT a `xsd/` |
| Gestor de catálogos SAT | Sincronización de catálogos (forma_pago, tipo_vehiculo, etc.) |
| API Cliente SPPLD | Envío automático al portal SAT (requiere credenciales SPPLD) |
| Sistema de acuses | Almacenar respuesta/folio SAT en `llx_pld_aviso` |
| Verificación flujo ECM end-to-end | Pendiente de Fase 2 |
| Soporte multi-actividad (V, XI, XII, XIII, XV) | Pospuesto a **Fase 4** (ver `fase4-multi-actividad-plan.md`) |

## ⚠️ Decisiones técnicas tomadas

- **Scope Fase 3 = solo `veh.xsd` (Fracción VIII)**. Soporte para otras actividades
  vulnerables requiere refactorización a `PLDXMLGeneratorBase` + subclases.
  Documentado en `docs/plans/fase4-multi-actividad-plan.md`.
- **e.firma: instalación manual en servidor** (sin upload desde UI) por seguridad.
  Instrucciones en `README.md`.
- `TINYINT(1)` en DDL (no `BOOLEAN`) — consistente con DDL Rules MySQL-first.

## 🎯 Objetivo de la Fase 3

Desarrollar un **sistema completo de generación, validación y envío** de archivos XML de avisos PLD al portal SPPLD del SAT, cumpliendo con las especificaciones técnicas oficiales (XSD) de la SHCP.

---

## 📊 Alcance de la Fase 3

### Prerequisites
✅ Fase 1 completada (extrafields implementados)
✅ Fase 2 tablas y CRUD completados
✅ Fase 2.1 UI completada (listas, cards, dashboard, tab PLD en factura)
⏳ Pendiente de Fase 2: métodos `fetchCliente()`, `fetchVehiculo()`, `fetchBeneficiarios()`, `fetchFormasPago()` en `PLDOperacion`
⏳ Pendiente de Fase 2: verificación flujo ECM end-to-end

### Módulo objetivo
- **Nombre interno:** `modulecompliancepld`
- **Ruta:** `htdocs/custom/modulecompliancepld/`
- **BD:** PostgreSQL (no MySQL)

### Componentes Principales
1. **Motor de Generación XML** - Construcción de XML según XSD oficial
2. **Validador XML** - Validación contra esquemas SAT
3. **Gestor de Catálogos** - Sincronización con catálogos oficiales
4. **Integración e.firma** - Firma digital de XMLs
5. **API Cliente SPPLD** - Envío automático al portal SAT
6. **Sistema de Acuses** - Gestión y almacenamiento de respuestas
7. **Motor de Reportes** - Generación de reportes de cumplimiento

---

## 📐 PARTE 1: Especificaciones Técnicas del XML

### Estructura XSD Oficial - Vehículos (VEH)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
           targetNamespace="http://www.uif.shcp.gob.mx/recepcion/veh"
           xmlns="http://www.uif.shcp.gob.mx/recepcion/veh"
           elementFormDefault="qualified">
  
  <!-- Namespace: http://www.uif.shcp.gob.mx/recepcion/veh -->
  <!-- XSD Location: https://sppld.sat.gob.mx/pld/documentos/links/xsd/veh.xsd -->
  
  <xs:element name="archivo">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="informe" maxOccurs="unbounded">
          <xs:complexType>
            <xs:sequence>
              <xs:element name="mes_reportado" type="xs:string"/>
              <xs:element name="sujeto_obligado">
                <xs:complexType>
                  <xs:sequence>
                    <xs:element name="clave_sujeto_obligado" type="xs:string"/>
                    <xs:element name="clave_actividad" type="xs:string"/>
                  </xs:sequence>
                </xs:complexType>
              </xs:element>
              <xs:element name="aviso" maxOccurs="unbounded">
                <!-- Estructura del aviso -->
              </xs:element>
            </xs:sequence>
          </xs:complexType>
        </xs:element>
      </xs:sequence>
    </xs:complexType>
  </xs:element>
  
</xs:schema>
```

### Ejemplo XML Completo - Venta de Vehículo

```xml
<?xml version="1.0" encoding="UTF-8"?>
<archivo xmlns="http://www.uif.shcp.gob.mx/recepcion/veh"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.uif.shcp.gob.mx/recepcion/veh https://sppld.sat.gob.mx/pld/documentos/links/xsd/veh.xsd">
  <informe>
    <mes_reportado>202602</mes_reportado>
    <sujeto_obligado>
      <clave_sujeto_obligado>ABC123456ABC</clave_sujeto_obligado>
      <clave_actividad>VIII</clave_actividad>
    </sujeto_obligado>
    
    <aviso>
      <referencia_aviso>VEH-202602-0001</referencia_aviso>
      <prioridad>1</prioridad>
      
      <alerta>
        <tipo_alerta>01</tipo_alerta>
      </alerta>
      
      <!-- DATOS DEL CLIENTE -->
      <persona_aviso>
        <tipo_persona>
          <persona_fisica>
            <nombre>JOSE ELIAS</nombre>
            <apellido_paterno>MORENO</apellido_paterno>
            <apellido_materno>VALLE</apellido_materno>
            <fecha_nacimiento>19890516</fecha_nacimiento>
            <pais_nacimiento>MX</pais_nacimiento>
            <nacionalidad>MX</nacionalidad>
            <rfc>MOVJ890516ABC</rfc>
            <curp>MOVJ890516HDFRLS01</curp>
            <actividad_economica>6117000</actividad_economica>
          </persona_fisica>
        </tipo_persona>
        
        <tipo_domicilio>
          <nacional>
            <colonia>SAN SIMON TOLNAHUAC</colonia>
            <calle>VIOLANTE</calle>
            <numero_exterior>45</numero_exterior>
            <numero_interior>B</numero_interior>
            <codigo_postal>06920</codigo_postal>
            <localidad>CIUDAD DE MEXICO</localidad>
            <municipio>CUAUHTEMOC</municipio>
            <entidad_federativa>CDMX</entidad_federativa>
          </nacional>
        </tipo_domicilio>
        
        <telefono>
          <clave_pais>52</clave_pais>
          <numero_telefono>5512345678</numero_telefono>
        </telefono>
      </persona_aviso>
      
      <!-- BENEFICIARIO CONTROLADOR (si aplica) -->
      <dueno_beneficiario>
        <tipo_persona>
          <persona_fisica>
            <nombre>MARIA</nombre>
            <apellido_paterno>GONZALEZ</apellido_paterno>
            <apellido_materno>LOPEZ</apellido_materno>
            <fecha_nacimiento>19750310</fecha_nacimiento>
            <rfc>GOLM750310XYZ</rfc>
            <porcentaje_participacion>100</porcentaje_participacion>
          </persona_fisica>
        </tipo_persona>
      </dueno_beneficiario>
      
      <!-- DATOS DE LA OPERACIÓN -->
      <acto_operacion>
        <fecha_operacion>20260215</fecha_operacion>
        <fecha_deteccion_operacion>20260215</fecha_deteccion_operacion>
        <monto_operacion>500000.00</monto_operacion>
        <moneda>MXN</moneda>
        <tipo_cambio>1.0000</tipo_cambio>
        <forma_pago>04</forma_pago> <!-- Transferencia -->
        <instrumento_monetario>01</instrumento_monetario>
        <numero_cuenta>1234</numero_cuenta>
        <institucion_financiera>BBVA MEXICO</institucion_financiera>
        
        <!-- DATOS DEL VEHÍCULO -->
        <vehiculo>
          <tipo_vehiculo>01</tipo_vehiculo> <!-- Terrestre -->
          <clase_vehiculo>01</clase_vehiculo> <!-- Automóvil -->
          <marca>TOYOTA</marca>
          <modelo>CAMRY</modelo>
          <anio>2024</anio>
          <numero_serie>1HGBH41JXMN109186</numero_serie>
          <numero_motor>G16E1234567</numero_motor>
          <origen>01</origen> <!-- Nacional -->
          <uso>01</uso> <!-- Particular -->
        </vehiculo>
        
        <descripcion_acto>Compra de vehículo nuevo marca Toyota modelo Camry 2024</descripcion_acto>
      </acto_operacion>
    </aviso>
  </informe>
</archivo>
```

---

## ⚠️ Notas de Compatibilidad (2026-03-16)

### Módulo correcto
Todos los `require_once` y referencias a clases deben usar:
```php
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';
// NO: '/custom/pldvehiculos/class/...' (nombre incorrecto)
```

### Constantes de configuración
Usar prefijo `MODULECOMPLIANCEPLD_*` (definidas en `admin/setup.php`):
```php
$conf->global->MODULECOMPLIANCEPLD_RFC_SUJETO   // RFC del sujeto obligado
$conf->global->MODULECOMPLIANCEPLD_UMA_VALOR     // Valor UMA vigente
$conf->global->MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE // Fracción Art. 17
```

### Base de datos PostgreSQL
- Usar `$db->escape()` para strings, `$db->plimit()` para paginación
- No usar `DATE_FORMAT()` → usar `dol_print_date()` en PHP
- No usar `GROUP_CONCAT` → usar `string_agg()` o procesar en PHP
- No usar `BOOLEAN` en DDL → usar `TINYINT(1)` (sintaxis MySQL-first); interpretar el valor en PHP si es necesario

### Fuente de datos para el XML
El generador construye cada `<aviso>` combinando:

| Nodo XML | Fuente de datos |
|----------|----------------|
| `mes_reportado`, `monto_operacion`, `fecha_operacion` | `llx_pld_operacion` |
| Datos del cliente (nombre, RFC, CURP, domicilio) | `llx_societe` + `llx_societe_extrafields` (pld_*) |
| Datos del vehículo (VIN, marca, modelo) | `llx_product` + `llx_product_extrafields` (pld_*) |
| Beneficiario controlador | `llx_pld_beneficiario` |
| Forma de pago, banco | `llx_paiement` + `llx_paiement_extrafields` (pld_*) |
| Referencia aviso, folio SAT | `llx_pld_aviso` |

> **Nota:** `fecha_operacion` se puede poblar automáticamente en `llx_pld_operacion`
> desde el trigger `PAYMENT_CUSTOMER_CREATE` que ya escribe en el extrafield
> `pld_fecha_operacion` de la factura (implementado en commit `98d4019`).

### Métodos pendientes en `PLDOperacion`
Antes de implementar el generador XML, agregar a `pldoperacion.class.php`:
- `fetchCliente()` — carga `Societe` + extrafields PLD
- `fetchVehiculo()` — carga `Product` + extrafields PLD
- `fetchBeneficiarios()` — carga todos los `PLDBeneficiario` de la empresa
- `fetchFormasPago()` — carga pagos + extrafields PLD de `paiement`

---

## 💻 PARTE 2: Arquitectura del Sistema

### Componentes del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                   INTERFAZ USUARIO                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  Dashboard   │  │   Avisos     │  │   Reportes   │  │
│  │  Cumplimiento│  │  Pendientes  │  │      SAT     │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                  CAPA DE NEGOCIO                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  Selector    │  │  Generador   │  │  Validador   │  │
│  │  Operaciones │  │     XML      │  │     XML      │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │   Firmador   │  │   Cliente    │  │   Gestor     │  │
│  │   e.firma    │  │   SPPLD      │  │   Acuses     │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                  CAPA DE DATOS                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │          Base de Datos Dolibarr (MySQL)          │  │
│  │  - llx_pld_operacion                             │  │
│  │  - llx_pld_aviso                                 │  │
│  │  - llx_pld_beneficiario                          │  │
│  │  - llx_societe (+ extrafields)                   │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│               SISTEMAS EXTERNOS                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │   Portal     │  │  Catálogos   │  │   Servicio   │  │
│  │    SPPLD     │  │     SAT      │  │    e.firma   │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 🏗️ PARTE 3: Clases PHP del Sistema

### Clase Principal: PLDXMLGenerator

```php
<?php
/**
 * Generador de XML para avisos PLD/LFPIORPI
 * Actividad Vulnerable: Vehículos (Fracción VIII)
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';

class PLDXMLGenerator
{
    private $db;
    private $error;
    private $errors = array();
    
    // Configuración
    private $namespace = 'http://www.uif.shcp.gob.mx/recepcion/veh';
    private $xsd_location = 'https://sppld.sat.gob.mx/pld/documentos/links/xsd/veh.xsd';
    
    // Datos del sujeto obligado
    private $rfc_sujeto;
    private $clave_actividad = 'VIII'; // Vehículos
    
    /**
     * Constructor
     */
    public function __construct($db)
    {
        $this->db = $db;
        
        global $conf;
        $this->rfc_sujeto = getDolGlobalString('MODULECOMPLIANCEPLD_RFC_SUJETO');
    }
    
    /**
     * Generar XML para un período mensual
     * 
     * @param string $mes_reportado Formato: YYYYMM
     * @return string|false XML generado o false si error
     */
    public function generarXMLMensual($mes_reportado)
    {
        dol_syslog(__METHOD__ . " Generando XML para mes: " . $mes_reportado, LOG_INFO);
        
        // 1. Obtener operaciones del mes
        $operaciones = $this->getOperacionesMes($mes_reportado);
        
        if (empty($operaciones)) {
            $this->error = "No hay operaciones para el mes " . $mes_reportado;
            return false;
        }
        
        // 2. Crear documento XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        
        // 3. Elemento raíz: archivo
        $archivo = $dom->createElementNS($this->namespace, 'archivo');
        $archivo->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            $this->namespace . ' ' . $this->xsd_location
        );
        $dom->appendChild($archivo);
        
        // 4. Elemento: informe
        $informe = $dom->createElement('informe');
        $archivo->appendChild($informe);
        
        // 5. Mes reportado
        $mes = $dom->createElement('mes_reportado', $mes_reportado);
        $informe->appendChild($mes);
        
        // 6. Sujeto obligado
        $sujeto = $this->crearSujetoObligado($dom);
        $informe->appendChild($sujeto);
        
        // 7. Avisos (uno por operación)
        foreach ($operaciones as $operacion) {
            $aviso = $this->crearAviso($dom, $operacion);
            if ($aviso) {
                $informe->appendChild($aviso);
            }
        }
        
        // 8. Generar XML string
        $xml = $dom->saveXML();
        
        return $xml;
    }
    
    /**
     * Crear nodo sujeto_obligado
     */
    private function crearSujetoObligado($dom)
    {
        $sujeto = $dom->createElement('sujeto_obligado');
        
        $clave = $dom->createElement('clave_sujeto_obligado', $this->rfc_sujeto);
        $sujeto->appendChild($clave);
        
        $actividad = $dom->createElement('clave_actividad', $this->clave_actividad);
        $sujeto->appendChild($actividad);
        
        return $sujeto;
    }
    
    /**
     * Crear nodo aviso completo
     */
    private function crearAviso($dom, $operacion)
    {
        $aviso = $dom->createElement('aviso');
        
        // Referencia del aviso
        $referencia = $dom->createElement('referencia_aviso', $operacion->referencia_aviso);
        $aviso->appendChild($referencia);
        
        // Prioridad (1=normal, 2=alta)
        $prioridad = $dom->createElement('prioridad', '1');
        $aviso->appendChild($prioridad);
        
        // Alerta
        $alerta = $this->crearAlerta($dom, $operacion);
        $aviso->appendChild($alerta);
        
        // Persona del aviso (cliente)
        $persona = $this->crearPersonaAviso($dom, $operacion);
        $aviso->appendChild($persona);
        
        // Beneficiario controlador (si aplica)
        if ($operacion->tiene_beneficiario) {
            $beneficiario = $this->crearBeneficiario($dom, $operacion);
            if ($beneficiario) {
                $aviso->appendChild($beneficiario);
            }
        }
        
        // Acto u operación
        $acto = $this->crearActoOperacion($dom, $operacion);
        $aviso->appendChild($acto);
        
        return $aviso;
    }
    
    /**
     * Crear nodo persona_aviso
     */
    private function crearPersonaAviso($dom, $operacion)
    {
        $persona_aviso = $dom->createElement('persona_aviso');
        
        // Tipo de persona
        $tipo_persona = $dom->createElement('tipo_persona');
        
        if ($operacion->cliente->tipo_persona == 'fisica') {
            $pf = $this->crearPersonaFisica($dom, $operacion->cliente);
            $tipo_persona->appendChild($pf);
        } else {
            $pm = $this->crearPersonaMoral($dom, $operacion->cliente);
            $tipo_persona->appendChild($pm);
        }
        
        $persona_aviso->appendChild($tipo_persona);
        
        // Domicilio
        $domicilio = $this->crearDomicilio($dom, $operacion->cliente);
        $persona_aviso->appendChild($domicilio);
        
        // Teléfono
        if (!empty($operacion->cliente->phone)) {
            $telefono = $this->crearTelefono($dom, $operacion->cliente->phone);
            $persona_aviso->appendChild($telefono);
        }
        
        return $persona_aviso;
    }
    
    /**
     * Crear nodo persona_fisica
     */
    private function crearPersonaFisica($dom, $cliente)
    {
        $pf = $dom->createElement('persona_fisica');
        
        // Nombre
        $nombre = $dom->createElement('nombre', $this->cleanXML($cliente->nombre));
        $pf->appendChild($nombre);
        
        // Apellido paterno
        $ap_pat = $dom->createElement('apellido_paterno', $this->cleanXML($cliente->apellido_paterno));
        $pf->appendChild($ap_pat);
        
        // Apellido materno
        $ap_mat = $dom->createElement('apellido_materno', $this->cleanXML($cliente->apellido_materno));
        $pf->appendChild($ap_mat);
        
        // Fecha de nacimiento (YYYYMMDD)
        $fecha_nac = $dom->createElement('fecha_nacimiento', 
            date('Ymd', strtotime($cliente->fecha_nacimiento)));
        $pf->appendChild($fecha_nac);
        
        // País de nacimiento
        $pais_nac = $dom->createElement('pais_nacimiento', $cliente->pais_nacimiento);
        $pf->appendChild($pais_nac);
        
        // Nacionalidad
        $nacionalidad = $dom->createElement('nacionalidad', $cliente->nacionalidad);
        $pf->appendChild($nacionalidad);
        
        // RFC
        $rfc = $dom->createElement('rfc', strtoupper($cliente->rfc));
        $pf->appendChild($rfc);
        
        // CURP
        $curp = $dom->createElement('curp', strtoupper($cliente->curp));
        $pf->appendChild($curp);
        
        // Actividad económica
        $actividad = $dom->createElement('actividad_economica', $cliente->actividad_economica);
        $pf->appendChild($actividad);
        
        return $pf;
    }
    
    /**
     * Crear nodo acto_operacion
     */
    private function crearActoOperacion($dom, $operacion)
    {
        $acto = $dom->createElement('acto_operacion');
        
        // Fecha operación (YYYYMMDD)
        $fecha = $dom->createElement('fecha_operacion',
            date('Ymd', strtotime($operacion->fecha_operacion)));
        $acto->appendChild($fecha);
        
        // Fecha detección
        $fecha_det = $dom->createElement('fecha_deteccion_operacion',
            date('Ymd', strtotime($operacion->fecha_operacion)));
        $acto->appendChild($fecha_det);
        
        // Monto
        $monto = $dom->createElement('monto_operacion',
            number_format($operacion->monto_mxn, 2, '.', ''));
        $acto->appendChild($monto);
        
        // Moneda
        $moneda = $dom->createElement('moneda', $operacion->moneda);
        $acto->appendChild($moneda);
        
        // Tipo de cambio
        $tc = $dom->createElement('tipo_cambio',
            number_format($operacion->tipo_cambio, 4, '.', ''));
        $acto->appendChild($tc);
        
        // Forma de pago
        $forma_pago = $this->getCatalogoFormaPago($operacion->forma_pago_principal);
        $fp = $dom->createElement('forma_pago', $forma_pago);
        $acto->appendChild($fp);
        
        // Instrumento monetario
        $instrumento = $dom->createElement('instrumento_monetario', '01');
        $acto->appendChild($instrumento);
        
        // Datos bancarios (si aplica)
        if ($operacion->usa_transferencia) {
            $cuenta = $dom->createElement('numero_cuenta', $operacion->cuenta_destino);
            $acto->appendChild($cuenta);
            
            $banco = $dom->createElement('institucion_financiera',
                $this->cleanXML($operacion->banco_destino));
            $acto->appendChild($banco);
        }
        
        // DATOS DEL VEHÍCULO
        $vehiculo = $this->crearVehiculo($dom, $operacion);
        $acto->appendChild($vehiculo);
        
        // Descripción
        $desc = $dom->createElement('descripcion_acto',
            $this->cleanXML($operacion->descripcion_operacion));
        $acto->appendChild($desc);
        
        return $acto;
    }
    
    /**
     * Crear nodo vehiculo
     */
    private function crearVehiculo($dom, $operacion)
    {
        $vehiculo = $dom->createElement('vehiculo');
        
        // Tipo de vehículo (01=terrestre, 02=aéreo, 03=marítimo)
        $tipo = $dom->createElement('tipo_vehiculo',
            $this->getCatalogoTipoVehiculo($operacion->vehiculo->tipo_vehiculo));
        $vehiculo->appendChild($tipo);
        
        // Clase (01=auto, 02=camioneta, 03=motocicleta, etc.)
        $clase = $dom->createElement('clase_vehiculo',
            $this->getCatalogoClaseVehiculo($operacion->vehiculo->subtipo));
        $vehiculo->appendChild($clase);
        
        // Marca
        $marca = $dom->createElement('marca',
            strtoupper($this->cleanXML($operacion->vehiculo->marca)));
        $vehiculo->appendChild($marca);
        
        // Modelo
        $modelo = $dom->createElement('modelo',
            strtoupper($this->cleanXML($operacion->vehiculo->modelo)));
        $vehiculo->appendChild($modelo);
        
        // Año
        $anio = $dom->createElement('anio', $operacion->vehiculo->anio_modelo);
        $vehiculo->appendChild($anio);
        
        // Número de serie (VIN)
        $serie = $dom->createElement('numero_serie',
            strtoupper($operacion->vehiculo->vin));
        $vehiculo->appendChild($serie);
        
        // Número de motor
        $motor = $dom->createElement('numero_motor',
            strtoupper($operacion->vehiculo->numero_motor));
        $vehiculo->appendChild($motor);
        
        // Origen (01=nacional, 02=importado)
        $origen = $dom->createElement('origen',
            $operacion->vehiculo->origen == 'nacional' ? '01' : '02');
        $vehiculo->appendChild($origen);
        
        // Uso (01=particular, 02=comercial, 03=transporte)
        $uso = $dom->createElement('uso',
            $this->getCatalogoUsoVehiculo($operacion->vehiculo->uso_destino));
        $vehiculo->appendChild($uso);
        
        return $vehiculo;
    }
    
    /**
     * Limpiar texto para XML
     */
    private function cleanXML($text)
    {
        // Remover caracteres especiales
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Obtener operaciones del mes
     */
    private function getOperacionesMes($mes_reportado)
    {
        $sql = "SELECT o.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "pld_operacion as o";
        $sql .= " WHERE o.mes_reportado = '" . $this->db->escape($mes_reportado) . "'";
        $sql .= " AND o.requiere_aviso = 1";
        $sql .= " AND o.aviso_presentado = 0";
        $sql .= " AND o.estado != 'cancelada'";
        $sql .= " ORDER BY o.fecha_operacion ASC";
        
        $result = $this->db->query($sql);
        
        $operaciones = array();
        if ($result) {
            $num = $this->db->num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $obj = $this->db->fetch_object($result);
                
                // Cargar operación completa
                $operacion = new PLDOperacion($this->db);
                $operacion->fetch($obj->rowid);
                $operacion->fetchCliente();
                $operacion->fetchVehiculo();
                $operacion->fetchBeneficiarios();
                $operacion->fetchFormasPago();
                
                $operaciones[] = $operacion;
            }
        }
        
        return $operaciones;
    }
}
```

---

## 🔒 PARTE 4: Integración e.firma (Firma Digital)

### Clase: PLDEFirmaIntegration

```php
<?php
/**
 * Integración con e.firma del SAT
 * Firma digital de XMLs PLD
 */

class PLDEFirmaIntegration
{
    private $db;
    private $error;
    
    // Rutas de certificados
    private $cert_path;
    private $key_path;
    private $password;
    
    /**
     * Constructor
     */
    public function __construct($db)
    {
        $this->db = $db;
        
        global $conf;
        $this->cert_path = $conf->global->PLD_EFIRMA_CERT_PATH;
        $this->key_path = $conf->global->PLD_EFIRMA_KEY_PATH;
        $this->password = $conf->global->PLD_EFIRMA_PASSWORD;
    }
    
    /**
     * Firmar XML con e.firma
     * 
     * @param string $xml_content Contenido XML a firmar
     * @return array|false Array con firma digital o false
     */
    public function firmarXML($xml_content)
    {
        dol_syslog(__METHOD__ . " Firmando XML", LOG_INFO);
        
        // 1. Validar certificados
        if (!$this->validarCertificados()) {
            return false;
        }
        
        // 2. Cargar certificado
        $cert_content = file_get_contents($this->cert_path);
        if (!$cert_content) {
            $this->error = "No se pudo leer el certificado";
            return false;
        }
        
        // 3. Cargar llave privada
        $key_content = file_get_contents($this->key_path);
        if (!$key_content) {
            $this->error = "No se pudo leer la llave privada";
            return false;
        }
        
        // 4. Generar cadena original
        $cadena_original = $this->generarCadenaOriginal($xml_content);
        
        // 5. Firmar cadena
        $private_key = openssl_pkey_get_private($key_content, $this->password);
        if (!$private_key) {
            $this->error = "No se pudo cargar la llave privada. Verifique la contraseña.";
            return false;
        }
        
        $signature = '';
        $result = openssl_sign($cadena_original, $signature, $private_key, OPENSSL_ALGO_SHA256);
        
        if (!$result) {
            $this->error = "Error al generar la firma digital";
            return false;
        }
        
        // 6. Codificar firma en base64
        $firma_base64 = base64_encode($signature);
        
        // 7. Obtener número de certificado
        $cert_data = openssl_x509_parse($cert_content);
        $numero_certificado = $cert_data['serialNumber'];
        
        return array(
            'sello_digital' => $firma_base64,
            'cadena_original' => $cadena_original,
            'numero_certificado' => $numero_certificado,
            'fecha_firma' => dol_now()
        );
    }
    
    /**
     * Generar cadena original para firma
     */
    private function generarCadenaOriginal($xml_content)
    {
        // Aplicar transformación XSL según especificaciones SAT
        $dom = new DOMDocument();
        $dom->loadXML($xml_content);
        
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('veh', 'http://www.uif.shcp.gob.mx/recepcion/veh');
        
        // Extraer valores clave para la cadena
        $mes = $xpath->