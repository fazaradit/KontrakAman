<?php
namespace App\Services\Reasoning;

class PromptBuilder {
    public function build(array $clause, array $retrievedRegulations): string {
        $clauseText = $clause['raw_text'] ?? '';
        $clauseCategory = $clause['category'] ?? 'unknown';
        
        $retrievedListFormatted = "";
        foreach ($retrievedRegulations as $reg) {
            $source = $reg['source_law'] ?? 'Unknown Source';
            $pasal = $reg['pasal'] ?? 'Unknown Pasal';
            $ayat = $reg['ayat'] ?? '';
            $text = $reg['full_text'] ?? '';
            
            $pasalFull = trim($pasal . ($ayat ? " $ayat" : ""));
            
            $retrievedListFormatted .= "Sumber: {$source}\nPasal: {$pasalFull}\nTeks: {$text}\n\n";
        }
        
        if (empty($retrievedListFormatted)) {
            $retrievedListFormatted = "- Tidak ada referensi relevan -";
        }
        
        return <<<EOT
Kamu adalah asisten hukum ketenagakerjaan. Analisis klausul kontrak berikut 
HANYA berdasarkan pasal yang diberikan di KONTEKS. Jangan mengarang nomor 
pasal atau isi UU yang tidak ada di konteks.

KLAUSUL KONTRAK:
"{$clauseText}"

KATEGORI: {$clauseCategory}

PASAL RELEVAN (konteks, HANYA sumber yang boleh dikutip):
{$retrievedListFormatted}

Jika tidak ada pasal relevan di KONTEKS yang cukup mendukung analisis, 
jawab verdict "ambiguous" dan jelaskan bahwa perlu review manual — 
JANGAN menebak atau mengisi dengan pengetahuan umum di luar konteks.

Output HARUS berupa JSON valid dengan struktur persis berikut, tanpa teks 
tambahan di luar JSON:
{
  "verdict": "compliant" | "violation" | "ambiguous",
  "cited_pasal": "nama pasal persis dari konteks, atau null jika ambiguous",
  "cited_source_law": "nama UU/PP persis dari konteks, atau null",
  "explanation_plain_language": "penjelasan 2-3 kalimat bahasa awam untuk pekerja",
  "severity": "high" | "medium" | "low"
}
EOT;
    }
}
