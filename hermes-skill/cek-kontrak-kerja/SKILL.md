---
name: cek-kontrak-kerja
description: Analisis kontrak kerja (PKWT/PKWTT) untuk mendeteksi klausul yang berpotensi melanggar UU Ketenagakerjaan Indonesia
version: 1.0.0
metadata:
  hermes:
    tags: [legal, ketenagakerjaan, kontrak-kerja]
    category: kontrakaman
---

# Cek Kontrak Kerja

## When to Use
Gunakan skill ini ketika user mengirim/upload file kontrak kerja (PDF) dan 
meminta dicek hak-haknya, atau bertanya apakah kontrak kerjanya sudah sesuai 
hukum ketenagakerjaan Indonesia.

## Procedure
1. Minta user melampirkan file kontrak kerja dalam format PDF jika belum ada.
2. Kirim file tersebut ke endpoint analisis dengan curl:
   curl -X POST http://<host-vps>/api/agent/analyze-contract \
     -F "file=@<path-file-kontrak>"
3. Parse response JSON yang berisi field: contract_type, total_clauses_checked, 
   violations_found, findings[], compliant_summary.
4. Sajikan hasil ke user dalam bahasa percakapan natural (bahasa Indonesia awam), 
   urutkan temuan dari severity "high" ke "low". Untuk tiap finding, sebutkan 
   pasal yang dilanggar (legal_basis) dan penjelasan singkatnya.
5. Tutup dengan pengingat bahwa ini alat bantu awal, bukan pengganti konsultasi 
   hukum resmi.

## Pitfalls
- Jangan pernah menambahkan nomor pasal atau penjelasan hukum sendiri di luar 
  yang dikembalikan endpoint — endpoint sudah punya validasi anti-halusinasi, 
  jangan dirusak dengan menambah interpretasi bebas dari Hermes.
- Kalau endpoint mengembalikan error, sampaikan apa adanya ke user (misal 
  "file gagal diproses"), jangan tebak-tebak isi kontraknya sendiri.

## Verification
Pastikan response endpoint mengandung field "findings" sebagai array 
(boleh kosong kalau kontrak sepenuhnya sesuai) sebelum menyajikan ke user.