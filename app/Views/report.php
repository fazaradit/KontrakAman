<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Kontrak - KontrakAman</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen pb-12">
    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-1">Hasil Analisis Kontrak</h1>
                    <p class="text-sm text-slate-500">File: <span class="font-semibold text-slate-700"><?= htmlspecialchars($contract['filename']) ?></span></p>
                </div>
                <div class="mt-4 md:mt-0 text-left md:text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        Jenis: <?= htmlspecialchars($contract['contract_type']) ?>
                    </span>
                    <p class="text-xs text-slate-400 mt-2">Dianalisis pada: <?= date('d M Y H:i', strtotime($contract['uploaded_at'])) ?></p>
                </div>
            </div>
            
            <?php
                $violationsCount = count(array_filter($findings, fn($f) => $f['verdict'] === 'violation'));
                $ambiguousCount = count(array_filter($findings, fn($f) => $f['verdict'] === 'ambiguous'));
                $totalIssues = $violationsCount + $ambiguousCount;
            ?>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 text-center">
                <p class="text-lg font-medium <?= $totalIssues > 0 ? 'text-rose-600' : 'text-emerald-600' ?>">
                    Ditemukan <?= $totalIssues ?> dari <?= $totalClauses ?> klausul berpotensi bermasalah atau melanggar aturan.
                </p>
            </div>
        </div>

        <!-- Findings List -->
        <div class="space-y-4">
            <?php if (empty($findings)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-emerald-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-emerald-800">Tidak ada masalah ditemukan!</h3>
                    <p class="text-sm text-emerald-600 mt-1">Berdasarkan <?= $totalClauses ?> klausul yang dievaluasi, semuanya terlihat sesuai dengan ketentuan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($findings as $finding): 
                    $severity = $finding['severity'] ?? 'low';
                    $verdict = $finding['verdict'] ?? 'violation';
                    
                    // Determine styling
                    if ($verdict === 'compliant') {
                        $border = 'border-emerald-300';
                        $bg = 'bg-emerald-50';
                        $badge = 'bg-emerald-100 text-emerald-800';
                        $icon = '<svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                    } else {
                        if ($severity === 'high') {
                            $border = 'border-rose-300';
                            $bg = 'bg-rose-50';
                            $badge = 'bg-rose-100 text-rose-800';
                            $icon = '<svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                        } elseif ($severity === 'medium') {
                            $border = 'border-amber-300';
                            $bg = 'bg-amber-50';
                            $badge = 'bg-amber-100 text-amber-800';
                            $icon = '<svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                        } else {
                            $border = 'border-slate-300';
                            $bg = 'bg-slate-50';
                            $badge = 'bg-slate-200 text-slate-800';
                            $icon = '<svg class="w-5 h-5 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                        }
                    }
                ?>
                <div class="rounded-xl border <?= $border ?> <?= $bg ?> shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/40 flex justify-between items-start">
                        <div class="flex items-center space-x-2">
                            <?= $icon ?>
                            <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($finding['clause_number']) ?></h3>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?> uppercase tracking-wide">
                            <?= $verdict === 'compliant' ? 'Sesuai' : htmlspecialchars($severity) ?>
                        </span>
                    </div>
                    
                    <div class="px-6 py-4 bg-white/60">
                        <div class="mb-3">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dasar Hukum</span>
                            <p class="text-sm font-medium text-slate-800 mt-1"><?= htmlspecialchars($finding['legal_basis'] ?? 'Tidak ada acuan') ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Penjelasan</span>
                            <p class="text-sm text-slate-700 mt-1 leading-relaxed"><?= htmlspecialchars($finding['explanation']) ?></p>
                        </div>
                        
                        <?php if(!empty($finding['clause_text'])): ?>
                        <div class="mt-4 p-3 bg-slate-100 rounded text-xs text-slate-600 border border-slate-200">
                            <span class="font-semibold block mb-1">Teks Asli Klausul:</span>
                            <?= nl2br(htmlspecialchars($finding['clause_text'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 flex flex-col items-center">
            <a href="/upload" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Analisis Kontrak Lainnya
            </a>
            <p class="mt-4 text-xs text-slate-400 text-center">
                KontrakAman adalah alat bantu awal, bukan pengganti konsultasi hukum resmi.
            </p>
        </div>
    </div>
</body>
</html>
