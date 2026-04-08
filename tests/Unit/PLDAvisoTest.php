<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldaviso.class.php';

class PLDAvisoTest extends TestCase
{
    private $db;
    private $aviso;
    
    protected function setUp(): void
    {
        global $db;
        require_once __DIR__ . '/../../htdocs/master.inc.php';
        
        $this->db = $db;
        $this->aviso = new PLDAviso($this->db);
    }
    
    public function testGenerarReferenciaUnica()
    {
        $this->aviso->mes_reportado = '202602';
        $referencia = $this->aviso->generarReferenciaUnica();
        
        $this->assertStringStartsWith('AVISO-', $referencia);
        $this->assertMatchesRegularExpression('/^AVISO-\d{4}-\d{2}-\d{4}$/', $referencia);
        $this->assertNotEmpty($this->aviso->referencia_aviso);
    }
    
    public function testReferenciaUnicaDiferente()
    {
        $this->aviso->mes_reportado = '202602';
        $ref1 = $this->aviso->generarReferenciaUnica();
        
        $aviso2 = new PLDAviso($this->db);
        $aviso2->mes_reportado = '202602';
        $ref2 = $aviso2->generarReferenciaUnica();
        
        $this->assertNotEquals($ref1, $ref2);
    }
    
    public function testCalcularHashXML()
    {
        $xml_content = '<?xml version="1.0"?><aviso>test</aviso>';
        $hash = $this->aviso->calcularHashXML($xml_content);
        
        $this->assertNotEmpty($hash);
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
    
    public function testHashXMLConsistente()
    {
        $xml_content = '<test>contenido</test>';
        
        $hash1 = $this->aviso->calcularHashXML($xml_content);
        $hash2 = $this->aviso->calcularHashXML($xml_content);
        
        $this->assertEquals($hash1, $hash2);
    }
    
    public function testEstadoInicialBorrador()
    {
        $aviso = new PLDAviso($this->db);
        $this->assertEquals('borrador', $aviso->estado);
    }
    
    public function testEstadoInicialNoPresentado()
    {
        $aviso = new PLDAviso($this->db);
        $this->assertEquals(0, $aviso->presentado);
    }
    
    public function testContadoresInicialesCero()
    {
        $aviso = new PLDAviso($this->db);
        $this->assertEquals(0, $aviso->numero_operaciones);
        $this->assertEquals(0, $aviso->monto_total_operaciones);
    }
}
