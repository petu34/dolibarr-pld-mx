<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldalerta.class.php';

class PLDAlertaTest extends TestCase
{
    private $db;
    private $alerta;
    
    protected function setUp(): void
    {
        global $db;
        require_once __DIR__ . '/../../htdocs/master.inc.php';
        
        $this->db = $db;
        $this->alerta = new PLDAlerta($this->db);
    }
    
    public function testEstadoInicialNueva()
    {
        $alerta = new PLDAlerta($this->db);
        $this->assertEquals('nueva', $alerta->estado);
    }
    
    public function testEstadoInicialRequiereAnalisis()
    {
        $alerta = new PLDAlerta($this->db);
        $this->assertEquals(1, $alerta->requiere_analisis);
    }
    
    public function testEstadoInicialNoInvolucraPep()
    {
        $alerta = new PLDAlerta($this->db);
        $this->assertEquals(0, $alerta->involucra_pep);
    }
}
