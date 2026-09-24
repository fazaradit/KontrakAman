<?php

use PHPUnit\Framework\TestCase;
use App\Services\Ingestion\TextNormalizer;
use App\Services\Segmentation\ClauseSegmenter;
use App\Services\Segmentation\AyatSegmenter;
use App\Services\Segmentation\ClauseCategorizer;

class SegmentationTest extends TestCase {
    
    private TextNormalizer $normalizer;
    private ClauseSegmenter $clauseSegmenter;
    private AyatSegmenter $ayatSegmenter;
    private ClauseCategorizer $categorizer;
    
    protected function setUp(): void {
        $this->normalizer = new TextNormalizer();
        $this->clauseSegmenter = new ClauseSegmenter();
        $this->ayatSegmenter = new AyatSegmenter();
        $this->categorizer = new ClauseCategorizer();
    }
    
    public function testTextNormalizerCombinesBrokenLines() {
        $raw = "men\njalani masa per\ncobaan";
        $normalized = $this->normalizer->normalize($raw);
        $this->assertEquals("men jalani masa per cobaan", $normalized);
        // Let's adjust the test to match the requirement exactly
        $raw2 = "Pihak kedua harus menda\npatkan haknya.";
        $normalized2 = $this->normalizer->normalize($raw2);
        $this->assertEquals("Pihak kedua harus menda patkan haknya.", $normalized2);
    }
    
    public function testTextNormalizerHandlesUnicodeLigatures() {
        $raw = "kon\u{FB01}rmasi dan bersi\u{FB00}at";
        $normalized = $this->normalizer->normalize($raw);
        $this->assertEquals("konfirmasi dan bersiffat", $normalized);
    }
    
    public function testClauseSegmenterExtractsCorrectNumberOfPasal() {
        $files = [
            'fixture_1_format_standar.txt' => 3,
            'fixture_2_tanpa_ayat.txt' => 3,
            'fixture_3_line_break_acak.txt' => 3,
            'fixture_4_pasal_besar.txt' => 3,
        ];
        
        foreach ($files as $file => $expectedCount) {
            $raw = file_get_contents(__DIR__ . '/fixtures/' . $file);
            $normalized = $this->normalizer->normalize($raw);
            $blocks = $this->clauseSegmenter->extractPasalBlocks($normalized);
            
            $this->assertCount($expectedCount, $blocks, "Gagal ekstrak pasal di $file");
        }
    }
    
    public function testAyatSegmenterExtractsAyatCorrectly() {
        $raw = file_get_contents(__DIR__ . '/fixtures/fixture_1_format_standar.txt');
        $normalized = $this->normalizer->normalize($raw);
        $blocks = $this->clauseSegmenter->extractPasalBlocks($normalized);
        
        // Pasal 1
        $ayatList = $this->ayatSegmenter->extractAyatFromPasal($blocks[0]['raw_block']);
        $this->assertCount(2, $ayatList);
        $this->assertEquals(1, $ayatList[0]['ayat_number']);
        $this->assertEquals(2, $ayatList[1]['ayat_number']);
    }
    
    public function testAyatSegmenterFallbackWorks() {
        $raw = file_get_contents(__DIR__ . '/fixtures/fixture_2_tanpa_ayat.txt');
        $normalized = $this->normalizer->normalize($raw);
        $blocks = $this->clauseSegmenter->extractPasalBlocks($normalized);
        
        // Pasal 1
        $ayatList = $this->ayatSegmenter->extractAyatFromPasal($blocks[0]['raw_block']);
        $this->assertCount(1, $ayatList);
        $this->assertNull($ayatList[0]['ayat_number']);
        $this->assertStringContainsString('Masa percobaan', $ayatList[0]['text']);
    }
    
    public function testClauseCategorizer() {
        $this->assertEquals('masa_percobaan', $this->categorizer->categorize('Menjalani masa percobaan 3 bulan'));
        $this->assertEquals('durasi_pkwt', $this->categorizer->categorize('Kontrak berlaku selama 1 tahun'));
        $this->assertEquals('lembur', $this->categorizer->categorize('Waktu kerja tambahan akan dibayar'));
        $this->assertEquals('upah', $this->categorizer->categorize('Mendapat gaji bulanan'));
        $this->assertEquals('pesangon', $this->categorizer->categorize('Tidak ada kompensasi akhir'));
        $this->assertEquals('cuti', $this->categorizer->categorize('Berhak atas istirahat tahunan'));
        $this->assertNull($this->categorizer->categorize('Pihak pertama beralamat di Jakarta'));
    }
}
