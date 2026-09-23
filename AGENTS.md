# AGENTS.md — KontrakAman

Konteks permanen untuk AI coding agent (Antigravity, OpenCode, atau tool lain yang membaca file ini). Baca file ini di awal setiap task sebelum mengeksekusi instruksi apa pun.

## 1. Tentang Project

**KontrakAman** — agent berbasis Hermes yang membaca kontrak kerja (PKWT/PKWTT) yang diupload user, memecahnya per klausul (Pasal/Ayat), memeriksa tiap klausul terhadap UU Ketenagakerjaan Indonesia menggunakan kombinasi rule engine deterministik + RAG, lalu menghasilkan laporan berbahasa awam yang menandai klausul bermasalah beserta rujukan pasal UU yang relevan.

**Target user:** pekerja individu (fresh graduate, pekerja informal/kontrak) yang ingin memverifikasi kontrak kerja mereka tanpa perlu konsultasi hukum berbayar.

**Konteks:** dikembangkan untuk kompetisi IDwebhost x AICLUB.ID AI Agent Competition, kategori Digital Safety & Public Good / subkategori Public Service, dengan framework Hermes Agent di atas VPS CloudBaik.

**Diferensiasi dari yang sudah ada:** berbeda dari HRIS (Mekari Talenta, Gadjian — untuk perusahaan) dan Veritask/AiYU (platform legal AI berbayar untuk firma hukum/UMKM) — KontrakAman gratis, spesifik untuk pekerja individu, scope sempit ke 6 kategori pelanggaran paling umum.

## 2. Tech Stack

- **Backend:** PHP native MVC (tanpa framework), PHP 8.2
- **Database:** PostgreSQL 16 + ekstensi pgvector
- **Embedding & LLM:** Gemini API — model embedding **`gemini-embedding-001`** (BUKAN `text-embedding-004`, sudah dideprecate Google sejak Feb 2026)
- **Agent orchestration:** Hermes Agent, dipasang di VPS saat jendela akses tanggal 16-20
- **Environment:** Docker Compose (image `pgvector/pgvector:pg16` sebagai base DB, bukan postgres official)
- **Testing:** PHPUnit

## 3. Struktur Folder (WAJIB diikuti persis)

```
kontrakaman/
├── docker-compose.yml
├── .env.example
├── public/
│   └── index.php
├── app/
│   ├── Config/          (Database.php, Gemini.php)
│   ├── Core/            (Router.php, Controller.php, Request.php, Response.php)
│   ├── Controllers/
│   ├── Services/
│   │   ├── Ingestion/       (PdfExtractor, OcrFallback, TextNormalizer)
│   │   ├── Segmentation/    (ClauseSegmenter, AyatSegmenter, ClauseCategorizer)
│   │   ├── RuleEngine/      (ValueExtractor, Violation, Rules/*)
│   │   ├── Retrieval/       (EmbeddingClient, HybridSearch, RegulationRepository)
│   │   ├── Reasoning/       (GeminiClient, PromptBuilder, ResponseParser)
│   │   └── HermesGateway/   (AgentToolEndpoint)
│   ├── Repositories/
│   ├── Models/
│   └── Views/
├── database/
│   ├── migrations/
│   └── seeders/
│       └── regulations_source/   (6 file .txt, satu per kategori klausul)
├── scripts/          (migrate.php, ingest_regulations.php)
├── tests/
│   └── fixtures/
└── hermes-skill/     (SKILL.md, definisi skill untuk Hermes di VPS)
```

**Penamaan wajib "kontrakaman"** di semua tempat — container_name, network Docker, composer package name, dan seterusnya. JANGAN pakai nama lama "worker-rights-agent" di file mana pun.

## 4. Skema Database

```sql
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE contracts (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    contract_type VARCHAR(10) DEFAULT 'UNKNOWN',  -- 'PKWT' | 'PKWTT' | 'UNKNOWN'
    raw_text TEXT,
    uploaded_at TIMESTAMP DEFAULT now(),
    status VARCHAR(20) DEFAULT 'processing'
);

CREATE TABLE contract_clauses (
    id SERIAL PRIMARY KEY,
    contract_id INT REFERENCES contracts(id) ON DELETE CASCADE,
    clause_number VARCHAR(20),        -- "Pasal 5 Ayat 2"
    raw_text TEXT NOT NULL,
    category VARCHAR(50),             -- salah satu dari 6 kategori (lihat bawah)
    extracted_values JSONB,
    embedding VECTOR(768),
    position_order INT
);

CREATE TABLE regulation_chunks (
    id SERIAL PRIMARY KEY,
    source_law VARCHAR(50) NOT NULL,  -- 'UU 13/2003', 'PP 35/2021', dst
    pasal VARCHAR(20),
    ayat VARCHAR(20),
    full_text TEXT NOT NULL,
    topic_tags TEXT[],
    embedding VECTOR(768)
);

CREATE TABLE analysis_results (
    id SERIAL PRIMARY KEY,
    clause_id INT REFERENCES contract_clauses(id) ON DELETE CASCADE,
    matched_regulation_ids INT[],
    verdict VARCHAR(20),      -- 'compliant' | 'violation' | 'ambiguous'
    severity VARCHAR(10),     -- 'high' | 'medium' | 'low'
    source VARCHAR(10),       -- 'rule_engine' | 'llm'
    explanation TEXT,
    confidence NUMERIC(3,2),
    created_at TIMESTAMP DEFAULT now()
);
```

## 5. Scope MVP — 6 Kategori Klausul (JANGAN keluar dari scope ini)

| Kategori | Dasar Hukum Benar | Catatan |
|---|---|---|
| `masa_percobaan` | Pasal 58 UU 13/2003 (PKWT) / Pasal 60 UU 13/2003 (PKWTT, max 3 bulan) | PKWT tidak boleh punya masa percobaan sama sekali |
| `durasi_pkwt` | Pasal 8 PP 35/2021 | Max 5 tahun termasuk perpanjangan |
| `upah` | Pasal 88E UU 13/2003, Pasal 23 PP 36/2021 | Bandingkan ke UMP/UMK — MVP masih hardcode 1 nilai referensi, ini keterbatasan yang diketahui |
| `lembur` | **Pasal 26 PP 35/2021** — max **4 jam/hari DAN 18 jam/minggu** | JANGAN pakai Kepmenaker No. 102/MEN/VI/2004 (3 jam/hari) — itu aturan lama yang sudah digantikan |
| `cuti` | Pasal 79 UU 13/2003, Pasal 21 PP 35/2021 | Minimal 12 hari kerja/tahun setelah 12 bulan kerja terus-menerus |
| `pesangon` | **Pasal 40 ayat (2) PP 35/2021** untuk tabel pesangon standar. **Pasal 43 PP 35/2021** khusus PHK karena efisiensi/kerugian perusahaan (pesangon 0,5x dari Pasal 40 ayat 2) — JANGAN tertukar dua pasal ini | |

**Di luar scope (jangan dikerjakan tanpa diminta eksplisit):** kontrak selain PKWT/PKWTT, kategori klausul di luar 6 di atas, analisis kontrak berbahasa asing, OCR untuk kontrak hasil scan (masih stub di MVP).

## 6. Aturan Kerja Wajib

1. **Anti-halusinasi hukum:** JANGAN PERNAH mengarang nomor pasal, ayat, atau isi UU. Semua kutipan pasal di kode maupun di `regulations_source/*.txt` harus sesuai teks resmi (JDIH Kemnaker / peraturan.go.id). Kalau tidak yakin, tandai dengan komentar `// TODO: verifikasi manual` — jangan menebak.
2. **Testing wajib sebelum commit:** jalankan PHPUnit setelah tiap implementasi. Kalau ada test merah, perbaiki dulu — JANGAN commit kode dengan test gagal.
3. **Commit di akhir tiap task**, dengan pesan jelas format `feat: <deskripsi> (Task N)` atau `fix: <deskripsi>`. **JANGAN PERNAH jalankan `git push`** — itu selalu dilakukan manual oleh user.
4. **Cek credential sebelum commit:** pastikan tidak ada API key/secret hardcode di kode (harus lewat `.env`).
5. **Keterbatasan MVP yang harus ditandai eksplisit di kode**, bukan disembunyikan: nilai UMP/UMK hardcode satu daerah, OCR belum diimplementasikan, formula pesangon PHK di luar 2 kategori dasar (Pasal 40/43) perlu verifikasi manual.

## 7. Alur Kerja End-to-End (referensi arsitektur)

```
Upload kontrak (PDF/DOCX)
  → Ekstraksi teks + normalisasi (TextNormalizer)
  → Segmentasi per Pasal/Ayat (ClauseSegmenter, AyatSegmenter)
  → Kategorisasi klausul (ClauseCategorizer — keyword matching)
  → Ekstraksi nilai (ValueExtractor — durasi, nominal, jam, dst)
  → Rule engine deterministik (6 kategori di atas)
  → Untuk klausul ambigu: hybrid retrieval (tag filter + vector similarity via pgvector) ke regulation_chunks
  → LLM grounded reasoning (Gemini, WAJIB cite pasal hanya dari hasil retrieval, tidak boleh dari pengetahuan umum model)
  → Agregasi hasil + laporan bahasa awam, urut berdasarkan severity
```
