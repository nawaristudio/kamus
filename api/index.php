<?php
$result = null;
$error = null;
$direction = $_POST['direction'] ?? 'id-to-en';
$input = trim($_POST['kata'] ?? '');

$env = parse_ini_file(__DIR__ . '/.env');
$API_KEY = $env['GEMINI_API_KEY'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input !== '') {
    $from = ($direction === 'id-to-en') ? 'Indonesian' : 'English';
    $to   = ($direction === 'id-to-en') ? 'English' : 'Indonesian';

    $prompt = <<<PROMPT
You are a $from → $to dictionary.
Respond ONLY with valid JSON (no markdown):

{
  "word": "$input",
  "translations": ["translation1", "translation2", "translation3"],
  "examples": {
    "present": { "en": "Present tense example.", "id": "Contoh kalimat present." },
    "past":    { "en": "Past tense example.",    "id": "Contoh kalimat past." },
    "future":  { "en": "Future tense example.",  "id": "Contoh kalimat future." }
  }
}
PROMPT;

    $payload = json_encode(["contents" => [[ "parts" => [["text" => $prompt]] ]]], JSON_UNESCAPED_SLASHES);

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$API_KEY");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    $json = json_decode($response, true);
    $rawText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? null;

    if (!$rawText) {
        $error = "Gemini tidak merespons. Coba lagi dalam beberapa detik.";
    } else {
        $clean = trim(preg_replace('/^```json\s*|```$/m', '', $rawText));
        $result = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = "Respons Gemini bukan JSON yang valid.";
        } else {
            $result['direction'] = $direction;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamus Indonesia ↔ Inggris</title>
    <meta name="theme-color" content="#6D28D9">

    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        .gradient-bg { background: linear-gradient(135deg, #faf5ff 0%, #e0e7ff 100%); }
        .loading-ring { border: 4px solid #e5e7eb; border-top: 4px solid #2828d9ff; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .fade-in { animation: fadeIn 0.6s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col items-center justify-center px-4 py-12">

<div class="w-full max-w-2xl">

    <!-- Header -->
    <div class="text-center mb-10 animate-fadeIn">
        <h1 class="text-5xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-blue-600">
            Kamus ID ↔ EN
        </h1>
        <p class="text-gray-600 mt-3 text-lg">Didukung oleh Gemini 2.0 Flash • Cepat & Akurat</p>
    </div>

    <!-- Search Card -->
    <div class="glass rounded-3xl shadow-2xl p-8 md:p-10 relative overflow-hidden">
        
        <form method="POST" id="searchForm" class="space-y-8">

            <!-- Direction Toggle -->
            <div class="flex justify-center">
                <div class="inline-flex rounded-full bg-white shadow-lg p-1.5">
                    <button type="button" onclick="setDir('id-to-en')"
                        class="px-8 py-3 rounded-full font-semibold transition-all <?= $direction==='id-to-en' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md' : 'text-gray-600' ?>">
                        Indonesia → English
                    </button>
                    <button type="button" onclick="setDir('en-to-id')"
                        class="px-8 py-3 rounded-full font-semibold transition-all <?= $direction==='en-to-id' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md' : 'text-gray-600' ?>">
                        English → Indonesia
                    </button>
                </div>
            </div>

            <input type="hidden" name="direction" id="directionInput" value="<?= htmlspecialchars($direction) ?>">

          <!-- Input + Tombol Cari - Sekarang 100% Responsif di HP -->
        <div class="flex flex-col sm:flex-row gap-4">
            <input type="text" name="kata" id="kataInput" requigreen autocomplete="off"
                class="flex-1 px-6 py-5 text-xl rounded-2xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none transition-all shadow-sm order-1"
                placeholder="" value="<?= htmlspecialchars($input) ?>">

            <button type="submit"
                class="group flex items-center justify-center gap-3 px-8 py-5 sm:px-10 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-2xl shadow-lg transform hover:scale-105 active:scale-95 transition-all duration-200 whitespace-nowrap order-2">
                
                <!-- SVG Icon Cari (Magnifying Glass) -->
                <svg class="w-6 h-6 sm:w-7 sm:h-7 transition-transform group-hover:scale-110" 
                    fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" 
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                
                <span class="tracking-wide" id="cari">
                    'Cari'
                </span>
            </button>
        </div>

            <!-- Loading -->
            <div id="loading" class="hidden absolute inset-0 bg-white/95 rounded-3xl flex flex-col items-center justify-center gap-4 z-10">
                <div class="loading-ring"></div>
                <p class="text-blue-700 font-medium">Mencari arti kata...</p>
            </div>
        </form>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="mt-8 glass rounded-2xl p-6 border-l-4 border-green-500 text-green-700 fade-in">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Result Card -->
    <?php if ($result): ?>
    <div class="mt-10 glass rounded-3xl shadow-2xl overflow-hidden fade-in">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8 text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold"><?= htmlspecialchars($result['word']) ?></h2>
            <p class="mt-2 text-blue-100 text-lg font-medium">
                <?= $result['direction']==='id-to-en' ? 'Indonesia → English' : 'English → Indonesia' ?>
            </p>
        </div>

        <div class="p-8 md:p-10 space-y-10">

            <!-- Translations -->
            <div>
                <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5 4.756 5 2.5 7.209 2.5 10c0 2.791 2.256 5 5 5 1.746 0 3.332-.477 4.5-1.253M12 6.253c1.168-.776 2.754-1.253 4.5-1.253 2.744 0 5 2.209 5 5 0 2.791-2.256 5-5 5-1.746 0-3.332-.477-4.5-1.253"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10c0-4.694-3.806-8.5-8.5-8.5S2.5 5.306 2.5 10s3.806 8.5 8.5 8.5c1.7 0 3.29-.498 4.617-1.356.913-2.356 3.383-4.144 6.383-4.144z"/>
                    </svg>
                    <span class="text-blue-600">Terjemahan</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($result['translations'] as $t): ?>
                        <div class="bg-blue-50 text-blue-800 px-6 py-4 rounded-xl font-medium text-lg border border-blue-200">
                            <?= htmlspecialchars($t) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Examples — Versi Super Jelas & Cantik -->
            <div class="mt-10">
                <h3 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-blue-600">Contoh Kalimat</span>
                </h3>

                <div class="space-y-10">
                    <?php foreach ($result['examples'] as $tense => $ex): ?>
                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden border border-blue-100">
                        <!-- Header Tense -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex items-center gap-3">
                            <?php if ($tense === 'present'): ?>
                                <span class="text-2xl">Present</span>
                            <?php elseif ($tense === 'past'): ?>
                                <span class="text-2xl">Past</span>
                            <?php else: ?>
                                <span class="text-2xl">Future</span>
                            <?php endif; ?>
                            <span class="text-sm opacity-90">Tense</span>
                        </div>

                        <!-- Kalimat EN & ID -->
                        <div class="p-6 md:p-8 grid md:grid-cols-2 gap-6">
                            <!-- English -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="font-bold text-blue-700 text-lg">English</span>
                                </div>
                                <p class="text-gray-800 text-lg leading-relaxed bg-blue-50 px-5 py-4 rounded-2xl border-l-4 border-blue-600">
                                    <?= htmlspecialchars($ex['en']) ?>
                                </p>
                            </div>

                            <!-- Indonesia -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="font-bold text-green-700 text-lg">Indonesia</span>
                                </div>
                                <p class="text-gray-800 text-lg leading-relaxed bg-green-50 px-5 py-4 rounded-2xl border-l-4 border-green-600">
                                    <?= htmlspecialchars($ex['id']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Install Button -->
<button id="btnInstall" class="hidden fixed bottom-8 right-8 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-4 rounded-full shadow-2xl font-bold text-lg z-50 transform hover:scale-110 transition-all">
    Install Aplikasi
</button>

<script>
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("service-worker.js");
}

// Update placeholder & tombol aktif
function updateUI() {
    const dir = document.getElementById("directionInput").value;
    document.getElementById("kataInput").placeholder = 
        dir === 'id-to-en' ? 'Ketik kata dalam bahasa Indonesia...' : 'Type an English word...';

    // Update teks tombol "Cari" ↔ "Search"
    document.getElementById("cari").textContent = dir === 'id-to-en' ? 'Cari' : 'Search';
}
updateUI();

function setDir(dir) {
    document.getElementById("directionInput").value = dir;
    updateUI();
    document.querySelectorAll('[onclick^="setDir"]').forEach(b => {
        b.classList.remove('bg-gradient-to-r', 'from-blue-600', 'to-blue-700', 'text-white', 'shadow-md');
        b.classList.add('text-gray-600');
    });
    event.target.classList.add('bg-gradient-to-r', 'from-blue-600', 'to-blue-700', 'text-white', 'shadow-md');
    event.target.classList.remove('text-gray-600');
}

// Loading
document.getElementById("searchForm").addEventListener("submit", () => {
    document.getElementById("loading").classList.remove("hidden");
});

// PWA Install
let defergreenPrompt;
const btn = document.getElementById("btnInstall");
window.addEventListener("beforeinstallprompt", e => {
    e.preventDefault();
    defergreenPrompt = e;
    btn.classList.remove("hidden");
});
btn.addEventListener("click", async () => {
    defergreenPrompt.prompt();
    await defergreenPrompt.userChoice;
    defergreenPrompt = null;
    btn.classList.add("hidden");
});
</script>
</body>
</html>