<?php

use PHPUnit\Framework\TestCase;
use App\Services\RuleEngine\ValueExtractor;
use App\Services\RuleEngine\Rules\MasaPercobaanRule;
use App\Services\RuleEngine\Rules\DurasiPkwtRule;
use App\Services\RuleEngine\Rules\LemburRule;
use App\Services\RuleEngine\Rules\UpahMinimumRule;
use App\Services\RuleEngine\Rules\CutiTahunanRule;
use App\Services\RuleEngine\Rules\PesangonRule;
use App\Services\RuleEngine\RuleEngineRunner;
use App\Services\RuleEngine\Violation;

class RuleEngineTest extends TestCase {
    
    public function testValueExtractor() {
        $extractor = new ValueExtractor();
        
        // Masa percobaan
        $this->assertEquals(['durasi_bulan' => 3], $extractor->extract('masa_percobaan', 'Masa percobaan selama 3 bulan.'));
        $this->assertEquals(['durasi_bulan' => 3], $extractor->extract('masa_percobaan', 'Masa percobaan selama 3 (tiga) bulan.'));
        $this->assertEquals(['durasi_bulan' => null], $extractor->extract('masa_percobaan', 'Masa percobaan sesuai kesepakatan.'));
        
        // Durasi PKWT
        $this->assertEquals(['durasi_bulan' => 24], $extractor->extract('durasi_pkwt', 'Jangka waktu 2 tahun.'));
        $this->assertEquals(['durasi_bulan' => 24], $extractor->extract('durasi_pkwt', 'Jangka waktu 2 (dua) tahun.'));
        $this->assertEquals(['durasi_bulan' => 6], $extractor->extract('durasi_pkwt', 'Berlaku selama 6 bulan.'));
        $this->assertEquals(['durasi_bulan' => null], $extractor->extract('durasi_pkwt', 'Berlaku sampai proyek selesai.'));
        
        // Lembur
        $this->assertEquals(['jam_per_hari' => 4], $extractor->extract('lembur', 'Waktu lembur maksimal 4 jam sehari.'));
        
        // Upah
        $this->assertEquals(['nominal' => 3000000], $extractor->extract('upah', 'Upah sebesar Rp 3.000.000 per bulan.'));
        $this->assertEquals(['nominal' => 5000000], $extractor->extract('upah', 'Gaji sebesar Rp5.000.000.'));
        $this->assertEquals(['nominal' => 3000000], $extractor->extract('upah', 'Gaji 3 juta per bulan'));
        $this->assertEquals(['nominal' => 1500000], $extractor->extract('upah', 'Sebesar 1500 ribu'));
        
        // Cuti
        $this->assertEquals(['hari_per_tahun' => 10], $extractor->extract('cuti', 'Cuti selama 10 hari.'));
        
        // Pesangon
        $this->assertEquals(['multiplier' => 1.5], $extractor->extract('pesangon', 'Pesangon sebesar 1.5 kali gaji.'));
        $this->assertEquals(['multiplier' => 2.0], $extractor->extract('pesangon', 'Pesangon 2 bulan gaji.'));
    }
    
    public function testMasaPercobaanRule() {
        $rule = new MasaPercobaanRule();
        
        $violation = $rule->evaluate(['category' => 'masa_percobaan', 'extracted_values' => ['durasi_bulan' => 3]], 'PKWT');
        $this->assertInstanceOf(Violation::class, $violation);
        
        $this->assertNull($rule->evaluate(['category' => 'masa_percobaan', 'extracted_values' => ['durasi_bulan' => 3]], 'PKWTT'));
    }
    
    public function testDurasiPkwtRule() {
        $rule = new DurasiPkwtRule();
        
        $violation = $rule->evaluate(['category' => 'durasi_pkwt', 'extracted_values' => ['durasi_bulan' => 61]], 'PKWT');
        $this->assertInstanceOf(Violation::class, $violation);
        $this->assertEquals('high', $violation->severity);
        
        $this->assertNull($rule->evaluate(['category' => 'durasi_pkwt', 'extracted_values' => ['durasi_bulan' => 60]], 'PKWT'));
        
        $violation2 = $rule->evaluate(['category' => 'durasi_pkwt', 'extracted_values' => ['durasi_bulan' => null]], 'PKWT');
        $this->assertInstanceOf(Violation::class, $violation2);
        $this->assertEquals('low', $violation2->severity);
    }
    
    public function testLemburRule() {
        $rule = new LemburRule();
        
        $violation = $rule->evaluate(['category' => 'lembur', 'extracted_values' => ['jam_per_hari' => 4]], 'PKWTT');
        $this->assertInstanceOf(Violation::class, $violation);
        
        $this->assertNull($rule->evaluate(['category' => 'lembur', 'extracted_values' => ['jam_per_hari' => 3]], 'PKWTT'));
    }
    
    public function testUpahMinimumRule() {
        $rule = new UpahMinimumRule();
        
        $violation = $rule->evaluate(['category' => 'upah', 'extracted_values' => ['nominal' => 2000000]], 'PKWT');
        $this->assertInstanceOf(Violation::class, $violation);
        
        $this->assertNull($rule->evaluate(['category' => 'upah', 'extracted_values' => ['nominal' => 2500000]], 'PKWT'));
    }
    
    public function testCutiTahunanRule() {
        $rule = new CutiTahunanRule();
        
        $violation = $rule->evaluate(['category' => 'cuti', 'extracted_values' => ['hari_per_tahun' => 10]], 'PKWT');
        $this->assertInstanceOf(Violation::class, $violation);
        
        $this->assertNull($rule->evaluate(['category' => 'cuti', 'extracted_values' => ['hari_per_tahun' => 12]], 'PKWT'));
    }
    
    public function testPesangonRule() {
        $rule = new PesangonRule();
        
        $violation = $rule->evaluate(['category' => 'pesangon', 'extracted_values' => ['multiplier' => 2.0]], 'PKWTT');
        $this->assertInstanceOf(Violation::class, $violation);
        $this->assertEquals('low', $violation->severity);
        
        $this->assertNull($rule->evaluate(['category' => 'pesangon', 'extracted_values' => ['multiplier' => 2.0]], 'PKWT'));
    }
    
    public function testRunnerSortsBySeverityAndCatchesNoCuti() {
        $runner = new RuleEngineRunner();
        $clauses = [
            ['category' => 'upah', 'raw_text' => 'Gaji sebesar Rp 1.500.000'],
            ['category' => 'lembur', 'raw_text' => 'Lembur 4 jam'],
        ];
        
        $violations = $runner->run($clauses, 'PKWTT');
        
        $this->assertCount(3, $violations);
        
        $this->assertEquals('high', $violations[0]->severity);
        $this->assertEquals('upah', $violations[0]->category);
        
        $this->assertEquals('medium', $violations[1]->severity);
        $this->assertEquals('medium', $violations[2]->severity);
        
        $missingCutiFound = false;
        foreach($violations as $v) {
            if ($v->category === 'cuti' && $v->message === 'Hak cuti tahunan tidak dicantumkan dalam kontrak.') {
                $missingCutiFound = true;
            }
        }
        $this->assertTrue($missingCutiFound);
    }
}
