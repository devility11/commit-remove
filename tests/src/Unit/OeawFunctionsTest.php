<?php


declare(strict_types=1);

namespace Drupal\Tests\oeaw\Unit;

use PHPUnit\Framework\TestCase;

include('/var/www/html/drupal/modules/oeaw/src/OeawFunctions.php');

/**
 * @coversDefaultClass \Drupal\oeaw\OeawFunctions
 * @group oeaw
 */

class OeawFunctions extends \PHPUnit\Framework\TestCase {
 
    private $oeawFunctions;
    private $cfgDir;
    private $acdhId = 'https://id.acdh.oeaw.ac.at/myidentifier';
    private $acdhUUID = 'https://id.acdh.oeaw.ac.at/uuid/myidentifier';
    private $pid = 'http://hdl.handle.net/21.11115/0000-0000';
    
    
    protected function setUp() {
        $this->cfgDir = $_SERVER['TRAVIS_BUILD_DIR']."/drupal/modules/oeaw/config.unittest.ini";
        $this->oeawFunctions = new \Drupal\oeaw\OeawFunctions($this->cfgDir);
    }
    
    public function testCreateDetailViewUrlAcdhID(){
        $data = array("title" => "my example title", "identifier" => $this->acdhId.",".$this->acdhUUID);
        $this->assertArrayHasKey('identifier', $data);
        
        $idRes = $this->oeawFunctions->createDetailViewUrl($data);
        $this->assertContains('http', $idRes);
    }
    
    public function testCreateDetailViewUrlPID(){
        $data = array("title" => "my example title", "identifier" => $this->acdhId.",".$this->acdhUUID, "pid" => $this->pid);
        $this->assertArrayHasKey('identifier', $data);
        
        $idRes = $this->oeawFunctions->createDetailViewUrl($data);
        $this->assertContains('handle', $idRes);
    }
    
    public function testCreateDetailViewUrlEmptyID(){
        $data = array("title" => "my example title", "identifier" => "");
        $this->assertArrayHasKey('identifier', $data);        
        $this->assertEmpty($data['identifier'], 'this is empty!');
    }
    
    public function testInitFedora()
    {
        $fd = $this->oeawFunctions->initFedora();
        
        
        
        
        
    }
    
    
}
    




//$this->expectException(\ErrorException::class);