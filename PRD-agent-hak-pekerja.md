# PRD — Agent Pembaca Hak Pekerja dari Kontrak Kerja

**Kompetisi:** IDwebhost x AICLUB.ID AI Agent Competition
**Framework:** Hermes Agent (di atas VPS CloudBaik)
**Stack:** PHP native MVC + PostgreSQL (pgvector) + Gemini API
**Versi dokumen:** Draft v1

---

## 1. Problem Statement

Banyak pekerja di Indonesia — terutama yang baru masuk dunia kerja atau bekerja di perusahaan kecil/menengah — menandatangani kontrak kerja (PKWT/PKWTT) tanpa memahami sepenuhnya apakah klausul di dalamnya sesuai dengan UU Ketenagakerjaan. Bahasa hukum yang berbelit dan biaya konsultasi hukum yang tidak terjangkau membuat banyak pelanggaran hak (masa percobaan ilegal di PKWT, jam lembur di luar batas, klausul pesangon yang tidak sesuai) lolos tanpa disadari.

## 2. Target User

Pekerja individu (terutama fresh graduate dan pekerja informal/kontrak) yang ingin memverifikasi kontrak kerja mereka sebelum menandatangani atau selama masa kerja berlangsung, tanpa perlu konsultasi hukum berbayar.

## 3. Solusi

Agent berbasis Hermes yang menerima upload kontrak kerja (PDF/DOCX), memecahnya per klausul (Pasal/Ayat), memeriksa tiap klausul terhadap UU Ketenagakerjaan menggunakan kombinasi rule engine deterministik + RAG (retrieval-augmented generation), lalu menghasilkan laporan berbahasa awam yang menandai klausul bermasalah beserta rujukan pasal UU yang relevan — dengan mekanisme grounded citation agar tidak ada pasal yang "dikarang".

## 4. Scope MVP — 6 Kategori Klausul

Yang **dicakup** di versi kompetisi:
1. Masa percobaan di PKWT (seharusnya tidak boleh ada)
2. Durasi PKWT (maksimal termasuk perpanjangan, sesuai PP 35/2021)
3. Upah di bawah UMP/UMK
4. Jam lembur & kompensasi lembur
5. Hak cuti tahunan tidak dicantumkan
6. Klausul pesangon PHK

Yang **tidak dicakup** di versi ini (eksplisit di luar scope):
- Kontrak selain PKWT/PKWTT (freelance, outsourcing, magang punya aturan beda)
- Klausul di luar 6 kategori di atas
- Analisis kontrak berbahasa asing
- Validasi hukum final — agent bersifat alat bantu awal, bukan pengganti konsultasi hukum resmi

## 5. Alur Kerja End-to-End

```
Upload kontrak (PDF/DOCX)
  → Ekstraksi teks + normalisasi
  → Segmentasi per Pasal/Ayat (regex)
  → Kategorisasi klausul (keyword matching)
  → Rule engine deterministik (6 kategori di atas)
  → Untuk klausul ambigu: hybrid retrieval (vector + tag) ke KB UU Ketenagakerjaan
  → LLM grounded reasoning (Gemini, cite pasal dari konteks retrieval saja)
  → Agregasi hasil + laporan bahasa awam, urut berdasarkan severity
```

## 6. Kriteria Sukses (untuk tahu kapan berhenti nge-tuning)

- Rule engine mendeteksi dengan benar 6 kategori pelanggaran di atas pada set uji internal (target: seluruh kasus jelas di fixture test lolos)
- Tidak ada halusinasi nomor pasal pada output LLM (setiap pasal yang disebut harus tertelusur ke `regulation_chunks`)
- Pipeline end-to-end berjalan dari upload sampai laporan tanpa error manual intervention, siap didemokan live di video

## 7. Batasan & Constraint Diketahui

- **Jendela akses VPS + Hermes hanya tanggal 16-20**, sementara deadline submission tanggal 30. Implikasi: seluruh development & testing harus selesai lokal (Docker Compose) sebelum tanggal 16; jendela VPS dipakai untuk deploy, integrasi Hermes, testing akhir, dan rekam video.
- Solo development — scope sengaja dibatasi 6 kategori klausul agar realistis dikerjakan sendiri dalam waktu terbatas.
- Video demo wajib menunjukkan environment VPS AI Hosting (dashboard & terminal), menyebut AI Hosting dan IDwebhost, dengan watermark IDwebhost.

## 8. Rubrik Penilaian (acuan prioritas kerja)

| Kriteria | Bobot |
|---|---|
| Relevansi & kejelasan masalah | 20% |
| Efektivitas solusi | 30% |
| Kreativitas & orisinalitas | 15% |
| Kualitas eksekusi teknis | 20% |
| Kualitas storytelling video & artikel | 15% |

**Catatan prioritas:** karena "efektivitas solusi" berbobot paling besar (30%), pastikan demo menunjukkan hasil analisis yang benar-benar akurat pada kontrak nyata — bukan cuma UI yang rapi.
