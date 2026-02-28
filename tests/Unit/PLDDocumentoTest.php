<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/plddocumento.class.php';

class PLDDocumentoTest extends TestCase
{
    private $db;
    private $documento;
    
    protected function setUp(): void
    {
        global $db;
        require_once __DIR__ . '/../../htdocs/master.inc.php';
        
        $this->db = $db;
        $this->documento = new PLDDocumento($this->db);
    }
    
    public function testConstanteAniosRetencion()
    {
        $this->assertEquals(5, PLDDocumento::ANIOS_RETENCION);
    }
    
    public function testCalcularFechaRetencion()
    {
        $this->documento->fecha_emision = '2026-02-27';
        $this->documento->calcularFechaRetencion();
        
        $this->assertEquals('2031-02-27', $this->documento->fecha_retencion_hasta);
    }
    
    public function testCalcularFechaRetencionSinFechaEmision()
    {
        $this->documento->fecha_emision = '';
        $this->documento->calcularFechaRetencion();
        
        $this->assertEmpty($this->documento->fecha_retencion_hasta);
    }
    
    public function testEstaVencidoConFechaFutura()
    {
        $this->documento->fecha_vencimiento = date('Y-m-d', strtotime('+1 year'));
        
        $this->assertFalse($this->documento->estaVencido());
    }
    
    public function testEstaVencidoConFechaPasada()
    {
        $this->documento->fecha_vencimiento = date('Y-m-d', strtotime('-1 day'));
        
        $this->assertTrue($this->documento->estaVencido());
    }
    
    public function testEstaVencidoSinFecha()
    {
        $this->documento->fecha_vencimiento = '';
        
        $this->assertFalse($this->documento->estaVencido());
    }
    
    public function testEstadoInicialNoVerificado()
    {
        $documento = new PLDDocumento($this->db);
        $this->assertEquals(0, $documento->verificado);
    }
}
