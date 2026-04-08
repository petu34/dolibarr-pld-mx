<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldbeneficiario.class.php';

class PLDBeneficiarioTest extends TestCase
{
    private $db;
    private $beneficiario;
    
    protected function setUp(): void
    {
        global $db;
        require_once __DIR__ . '/../../htdocs/master.inc.php';
        
        $this->db = $db;
        $this->beneficiario = new PLDBeneficiario($this->db);
    }
    
    public function testValidarPorcentajesValido()
    {
        $fk_societe = 999;
        $porcentaje = 25.50;
        
        $resultado = $this->beneficiario->validarPorcentajes($fk_societe, $porcentaje);
        
        $this->assertTrue($resultado);
    }
    
    public function testObtenerNombreCompleto()
    {
        $this->beneficiario->nombre = 'Juan';
        $this->beneficiario->apellido_paterno = 'Pérez';
        $this->beneficiario->apellido_materno = 'García';
        
        $nombre_completo = $this->beneficiario->obtenerNombreCompleto();
        
        $this->assertEquals('Juan Pérez García', $nombre_completo);
    }
    
    public function testObtenerNombreCompletoSinApellidoMaterno()
    {
        $this->beneficiario->nombre = 'María';
        $this->beneficiario->apellido_paterno = 'López';
        $this->beneficiario->apellido_materno = '';
        
        $nombre_completo = $this->beneficiario->obtenerNombreCompleto();
        
        $this->assertEquals('María López', $nombre_completo);
    }
    
    public function testObtenerTotalPorcentajes()
    {
        $fk_societe = 999;
        
        $total = $this->beneficiario->obtenerTotalPorcentajes($fk_societe);
        
        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual(0, $total);
        $this->assertLessThanOrEqual(100, $total);
    }
    
    public function testEstadoInicialActivo()
    {
        $beneficiario = new PLDBeneficiario($this->db);
        $this->assertEquals(1, $beneficiario->activo);
    }
    
    public function testEstadoInicialNoVerificado()
    {
        $beneficiario = new PLDBeneficiario($this->db);
        $this->assertEquals(0, $beneficiario->verificado);
    }
    
    public function testEstadoInicialNoPep()
    {
        $beneficiario = new PLDBeneficiario($this->db);
        $this->assertEquals(0, $beneficiario->es_pep);
    }
}
