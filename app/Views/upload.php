<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KontrakAman - Analisis Kontrak Kerja</title>
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
    <style>
        .loading-spinner { border-top-color: transparent; }
    </style>
</head>
<body class="bg-document text-ink font-sans antialiased min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full px-8 py-10 bg-document border-4 border-ink">
        <div class="text-center mb-10 border-b-2 border-ink pb-6">
            <h1 class="text-3xl font-bold text-ink tracking-tight uppercase mb-2">KontrakAman</h1>
            <p class="text-sm text-slateUI font-medium">Verifikasi dokumen perjanjian kerja (PKWT/PKWTT) terhadap ketentuan perundang-undangan.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 border-2 border-ink-red text-ink-red text-sm font-medium">
                <span class="font-bold uppercase tracking-wider">Error:</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/upload" method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-8">
            <div class="space-y-3">
                <label for="file" class="block text-sm font-bold uppercase tracking-wide text-ink">Unggah Dokumen (PDF)</label>
                <div class="relative border-2 border-dashed border-ink hover:bg-slate-100 transition-colors p-4 flex justify-center">
                    <input type="file" name="file" id="file" accept=".pdf" required
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-ink mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm font-medium text-slateUI">Pilih file atau seret ke sini</p>
                        <p class="text-xs text-slateUI mt-1">Maks 5MB</p>
                    </div>
                </div>
            </div>

            <button type="submit" id="submitBtn"
                class="w-full flex justify-center items-center py-4 px-4 border-2 border-ink text-sm font-bold uppercase tracking-wider text-document bg-ink hover:bg-slateUI transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ink">
                <span id="btnText">Proses Analisis Hukum</span>
                <svg id="loadingSpinner" class="hidden animate-spin ml-3 h-5 w-5 text-document loading-spinner border-2 border-document rounded-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"></svg>
            </button>
            <p id="loadingHint" class="hidden text-xs text-center text-slateUI font-medium mt-2">
                Pemeriksaan sedang berlangsung, harap tunggu...
            </p>
        </form>
    </div>
    
    <div class="mt-8 text-center text-xs font-medium text-slateUI uppercase tracking-wider">
        <p>Alat bantu awal &bull; Bukan pengganti legal opini resmi</p>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');
            const hint = document.getElementById('loadingHint');
            const fileInput = document.getElementById('file');
            
            if (fileInput.files.length > 0) {
                btn.classList.add('opacity-90', 'cursor-not-allowed');
                text.innerText = 'Menganalisis...';
                spinner.classList.remove('hidden');
                hint.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
