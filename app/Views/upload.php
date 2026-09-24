<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KontrakAman - Analisis Kontrak Kerja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .loading-spinner {
            border-top-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col items-center justify-center">
    <div class="max-w-md w-full px-6 py-8 bg-white rounded-xl shadow-lg border border-slate-100">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-700 tracking-tight mb-2">KontrakAman</h1>
            <p class="text-sm text-slate-500 leading-relaxed">Verifikasi kontrak kerja PKWT/PKWTT kamu secara instan terhadap UU Ketenagakerjaan.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded text-red-700 text-sm">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/upload" method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-6">
            <div class="space-y-2">
                <label for="file" class="block text-sm font-medium text-slate-700">Upload Kontrak Kerja (PDF)</label>
                <input type="file" name="file" id="file" accept=".pdf" required
                    class="block w-full text-sm text-slate-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100
                    cursor-pointer border border-slate-200 rounded-lg p-1">
                <p class="text-xs text-slate-400 mt-1">Maksimal ukuran file: 5MB.</p>
            </div>

            <button type="submit" id="submitBtn"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <span id="btnText">Analisis Kontrak</span>
                <svg id="loadingSpinner" class="hidden animate-spin ml-2 h-5 w-5 text-white loading-spinner border-2 border-white rounded-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"></svg>
            </button>
            <p id="loadingHint" class="hidden text-xs text-center text-slate-500 mt-2">
                Proses analisis memakan waktu 10-30 detik...
            </p>
        </form>
    </div>
    
    <div class="mt-8 text-center text-xs text-slate-400">
        <p>KontrakAman adalah alat bantu awal, bukan pengganti konsultasi hukum resmi.</p>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');
            const hint = document.getElementById('loadingHint');
            
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            text.innerText = 'Menganalisis...';
            spinner.classList.remove('hidden');
            hint.classList.remove('hidden');
        });
    </script>
</body>
</html>
