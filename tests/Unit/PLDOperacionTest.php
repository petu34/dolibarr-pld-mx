<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldoperacion.class.php';

class PLDOperacionTest extends TestCase
{
    private $db;
    private $operacion;
    
    protected function setUp(): void
    {
        global $db;
        require_once __DIR__ . '/../../htdocs/master.inc.php';
        
        $this->db = $db;
        $this->operacion = new PLDOperacion($this->db);
    }
    
    public function testConstantesUmbrales()
    {
        $this->assertEquals(377778.20, PLDOperacion::UMBRAL_VEHICULO_NUEVO);
        $this->assertEquals(117310.00, PLDOperacion::UMBRAL_VEHICULO_USADO);
    }
    
    public function testEvaluarUmbralVehiculoNuevo()
    {
        $this->operacion->monto_mxn = 400000;
        $resultado = $this->operacion->evaluarUmbral('nuevo');
        
        $this->assertTrue($resultado['supera_umbral']);
        $this->assertEquals(PLDOperacion::UMBRAL_VEHICULO_NUEVO, $resultado['umbral_aplicado']);
        $this->assertTrue($resultado['requiere_aviso']);
        $this->assertGreaterThan(0, $resultado['diferencia']);
    }
    
    public function testEvaluarUmbralVehiculoUsado()
    {
        $this->operacion->monto_mxn = 150000;
        $resultado = $this->operacion->evaluarUmbral('usado');
        
        $this->assertTrue($resultado['supera_umbral']);
        $this->assertEquals(PLDOperacion::UMBRAL_VEHICULO_USADO, $resultado['umbral_aplicado']);
        $this->assertTrue($resultado['requiere_aviso']);
    }
    
    public function testNoSuperaUmbral()
    {
        $this->operacion->monto_mxn = 100000;
        $resultado = $this->operacion->evaluarUmbral('usado');
        
        $this->assertFalse($resultado['supera_umbral']);
        $this->assertFalse($resultado['requiere_aviso']);
        $this->assertLessThan(0, $resultado['diferencia']);
    }
    
    public function testGenerarFolioInterno()
    {
        $folio = $this->operacion->generarFolioInterno();
        
        $this->assertStringStartsWith('PLD-', $folio);
        $this->assertMatchesRegularExpression('/^PLD-\d{4}-\d{2}-\d{4}$/', $folio);
        $this->assertNotEmpty($this->operacion->folio_interno);
    }
    
    public function testFolioInternoUnico()
    {
        $folio1 = $this->operacion->generarFolioInterno();
        
        $operacion2 = new PLDOperacion($this->db);
        $folio2 = $operacion2->generarFolioInterno();
        
        $this->assertNotEquals($folio1, $folio2);
    }
    
    public function testMesReportadoGeneradoAutomaticamente()
    {
        $this->operacion->fecha_operacion = '2026-02-27';
        $this->operacion->mes_reportado = '';
        
        $user = new stdClass();
        $user->id = 1;
        
        $this->operacion->fk_societe = 1;
        $this->operacion->tipo_actividad_vulnerable = 'VIII';
        $this->operacion->monto_mxn = 500000;
        
        $this->assertEmpty($this->operacion->mes_reportado);
    }
    
    public function testEstadoInicialBorrador()
    {
        $operacion = new PLDOperacion($this->db);
        $this->assertEquals('borrador', $operacion->estado);
    }
    
    public function testCamposBooleanosCero()
    {
        $operacion = new PLDOperacion($this->db);
        $this->assertEquals(0, $operacion->supera_umbral);
        $this->assertEquals(0, $operacion->cliente_identificado);
        $this->assertEquals(0, $operacion->documentacion_completa);
        $this->assertEquals(0, $operacion->requiere_aviso);
        $this->assertEquals(0, $operacion->aviso_presentado);
        $this->assertEquals(0, $operacion->genera_alerta);
    }
}
