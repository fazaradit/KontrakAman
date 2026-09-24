<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Kontrak - KontrakAman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        document: '#FAFAF7',
                        ink: '#1A1A1A',
                        slateUI: '#374151',
                        'ink-red': '#B91C1C',
                        'ink-green': '#166534',
                        'ink-amber': '#92400E'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Lora', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-document text-ink font-sans antialiased min-h-screen pb-12">
    <div class="max-w-4xl mx-auto px-4 py-10">
        
        <!-- Header -->
        <div class="border-b-4 border-ink pb-6 mb-8 flex flex-col md:flex-row md:items-end justify-between">
            <div>
                <h1 class="text-3xl font-bold text-ink uppercase tracking-tight mb-2">KontrakAman</h1>
                <p class="text-sm font-medium text-slateUI">Dokumen: <span class="text-ink font-bold"><?= htmlspecialchars($contract['filename']) ?></span></p>
            </div>
            <div class="mt-4 md:mt-0 md:text-right">
                <span class="inline-block border-2 border-ink px-4 py-1 text-sm font-bold uppercase tracking-wider text-ink">
                    Jenis: <?= htmlspecialchars($contract['contract_type']) ?>
                </span>
                <p class="text-xs text-slateUI mt-2 font-medium">Dianalisis: <?= date('d M Y H:i', strtotime($contract['uploaded_at'])) ?></p>
            </div>
        </div>

        <?php
            $violationsCount = count(array_filter($findings, fn($f) => $f['verdict'] === 'violation'));
            $ambiguousCount = count(array_filter($findings, fn($f) => $f['verdict'] === 'ambiguous'));
            $totalIssues = $violationsCount + $ambiguousCount;
        ?>

        <!-- Warnings -->
        <?php if ($contract['contract_type'] === 'UNKNOWN'): ?>
        <div class="border-2 border-ink-amber bg-amber-50 p-4 mb-6 font-sans">
            <p class="text-sm text-ink-amber font-medium">
                <span class="font-bold uppercase tracking-wider">/!\ Peringatan:</span> Jenis kontrak (PKWT/PKWTT) tidak terdeteksi otomatis dari dokumen ini. Beberapa pengecekan mungkin memerlukan verifikasi manual tambahan.
            </p>
        </div>
        <?php endif; ?>

        <div class="border-2 border-ink p-4 mb-10 font-sans text-center bg-white">
            <p class="text-lg font-bold uppercase tracking-wide <?= $totalIssues > 0 ? 'text-ink-red' : 'text-ink-green' ?>">
                Ditemukan <?= $totalIssues ?> dari <?= $totalClauses ?> klausul berpotensi bermasalah
            </p>
        </div>

        <h2 class="text-xl font-bold font-sans uppercase tracking-wider text-ink mb-8 border-b-2 border-ink pb-2">Kutipan Evaluasi Dokumen</h2>

        <!-- Findings List -->
        <div class="space-y-12">
            <?php if (empty($findings)): ?>
                <div class="border-2 border-ink-green p-8 text-center bg-green-50">
                    <h3 class="text-lg font-bold text-ink-green uppercase tracking-wider">Tidak ada masalah ditemukan!</h3>
                    <p class="text-sm text-ink-green mt-2 font-medium">Berdasarkan <?= $totalClauses ?> klausul yang dievaluasi, semuanya terlihat sesuai dengan ketentuan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($findings as $finding): 
                    $severity = $finding['severity'] ?? 'low';
                    $verdict = $finding['verdict'] ?? 'violation';
                    
                    // Determine styling
                    if ($verdict === 'compliant') {
                        $borderColor = 'border-ink-green';
                        $textColor = 'text-ink-green';
                        $label = 'SESUAI';
                    } else if ($verdict === 'ambiguous') {
                        $borderColor = 'border-ink-amber';
                        $textColor = 'text-ink-amber';
                        $label = 'AMBIGU / REVIEW';
                    } else {
                        $borderColor = 'border-ink-red';
                        $textColor = 'text-ink-red';
                        $label = 'PELANGGARAN';
                    }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 relative border-t-2 border-slate-300 pt-8">
                    <!-- Annotation Margin (Left side on desktop) -->
                    <div class="md:col-span-4 font-sans flex flex-col space-y-4 relative">
                        <div class="inline-block border-2 <?= $borderColor ?> <?= $textColor ?> px-3 py-1 text-sm font-bold uppercase w-max tracking-wider">
                            <?= $label ?>
                        </div>
                        
                        <div>
                            <p class="text-xs font-bold text-slateUI uppercase tracking-wider mb-1">Dasar Hukum</p>
                            <p class="text-sm text-ink font-bold"><?= htmlspecialchars($finding['legal_basis'] ?? 'Tidak ada acuan') ?></p>
                        </div>
                        
                        <div>
                            <p class="text-xs font-bold text-slateUI uppercase tracking-wider mb-1">Catatan Legal</p>
                            <p class="text-sm text-ink font-medium leading-relaxed"><?= htmlspecialchars($finding['explanation']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Clause Text (Right side on desktop) -->
                    <div class="md:col-span-8">
                        <div class="border-l-4 <?= $borderColor ?> pl-6">
                            <p class="text-sm text-slateUI font-sans font-bold uppercase tracking-wider mb-3">(<?= htmlspecialchars($finding['clause_number']) ?>)</p>
                            
                            <?php if(!empty($finding['clause_text'])): ?>
                            <div class="text-lg font-serif text-ink leading-relaxed italic">
                                "<?= nl2br(htmlspecialchars($finding['clause_text'])) ?>"
                            </div>
                            <?php else: ?>
                            <div class="text-lg font-serif text-slateUI leading-relaxed italic">
                                Klausul tidak tertulis di dalam dokumen.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-16 flex flex-col items-center border-t-2 border-ink pt-8">
            <a href="/upload" class="inline-flex items-center justify-center px-6 py-3 border-2 border-ink text-sm font-bold uppercase tracking-wider text-document bg-ink hover:bg-slateUI transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ink">
                Analisis Kontrak Lainnya
            </a>
            <p class="mt-4 text-xs font-medium text-slateUI uppercase tracking-wider text-center">
                Alat bantu awal &bull; Bukan pengganti legal opini resmi
            </p>
        </div>
    </div>
</body>
</html>
