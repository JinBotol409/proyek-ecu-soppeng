<?php
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$qOriginal = trim($data['question'] ?? '');

if (!$qOriginal) {
    echo json_encode(["answer" => "Pertanyaan kosong."], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ======================================================
   AI ECU LOCAL SMART PARSER
   File: /ai-ecu/ai_replay.php
   Wajib ada file: /ai-ecu/knowledge.php
====================================================== */

function normalizeText($text){
    $text = strtolower($text);

$replace = require __DIR__ . '/smart_words.php';

    foreach($replace as $wrong => $right){
        $text = str_replace($wrong, $right, $text);
    }

    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function hasAny($text, $words){
    foreach($words as $w){
        if(strpos($text, $w) !== false) return true;
    }
    return false;
}

function wordScore($question, $keys){
    $q = normalizeText($question);
    $qWords = array_filter(explode(' ', $q));
    $bestScore = 0;

    foreach($keys as $key){
        $k = normalizeText($key);

        if(strpos($q, $k) !== false){
            return 100;
        }

        $kWords = array_filter(explode(' ', $k));
        $hit = 0;

        foreach($kWords as $kw){
            foreach($qWords as $qw){
                if($kw === $qw){
                    $hit += 3;
                    break;
                }

                similar_text($qw, $kw, $percent);
                if($percent >= 78){
                    $hit += 2;
                    break;
                }
            }
        }

        if(count($kWords) > 0){
            $score = ($hit / (count($kWords) * 3)) * 100;
            if($score > $bestScore) $bestScore = $score;
        }
    }

    return $bestScore;
}

function detectSignals($q){
    $q = normalizeText($q);
    $signals = [];

    $rules = [
        'no_start' => ['no start','tidak hidup','starter','hard start','susah hidup'],
        'no_comm' => ['no communication','tidak communication','scanner tidak','obd tidak','ecu tidak terbaca','tidak bisa scan'],
        'injector' => ['injector','bahan bakar tidak keluar','solar tidak keluar','bensin tidak keluar'],
        'ckp' => ['ckp','rpm 0','rpm scanner 0','tidak ada rpm'],
        'cmp' => ['cmp','camshaft','sensor cam'],
        'immo' => ['immo','immobilizer','security','kunci','key not detected'],
        'battery' => ['aki','tegangan','battery','drop','low voltage'],
        'alternator' => ['alternator','charging','dinamo ampere','pengisian'],
        'can' => ['can high','can low','can bus','jalur can','u0100'],
        'misfire' => ['misfire','brebet','pincang','idle kasar','rpm naik turun'],
        'limp' => ['limp','tenaga hilang','ngempos','tidak bertenaga','rpm mentok'],
        'smoke' => ['asap hitam','ngebul','solar boros','bbm boros'],
        'write' => ['write','flash','programming','file corrupt','checksum'],
        'remap' => ['remap','stage 1','tuning','tambah tenaga'],
        'fuel_pressure' => ['fuel pressure','rail pressure','p0087','p0088','tekanan solar'],
        'throttle' => ['throttle','pedal gas','gas tidak respon','tps','p2135'],
        'fan' => ['kipas','fan','overheat','suhu tinggi']
    ];

    foreach($rules as $name => $words){
        if(hasAny($q, $words)) $signals[] = $name;
    }

    preg_match_all('/\b[pucb][0-9]{4}\b/i', $q, $m);
    foreach($m[0] as $code){
        $signals[] = strtolower($code);
    }

    return array_values(array_unique($signals));
}

function dynamicReasoning($question, $signals){
    $q = normalizeText($question);
    $parts = [];

    if(in_array('battery', $signals)){
        if(strpos($q, 'berapa') !== false || strpos($q, 'volt') !== false || strpos($q, 'tegangan') !== false){
            $parts[] = "Dari pertanyaan Anda, fokus utamanya adalah tegangan aki. Tegangan aki normal mesin mati sekitar 12.4V - 12.8V. Jika di bawah 12.2V aki mulai lemah. Saat starter sebaiknya tidak turun di bawah 9.6V. Saat mesin hidup charging normal sekitar 13.8V - 14.5V.";
        } else {
            $parts[] = "Aki drop bisa membuat supply ECU tidak stabil. Dampaknya ECU bisa restart, scanner putus komunikasi, injector tidak aktif, relay tidak stabil, atau muncul DTC voltage seperti P0560.";
        }
    }

    if(in_array('no_comm', $signals)){
        $parts[] = "Karena ada gejala no communication, cek dulu jalur dasar: fuse ECU/EFI, main relay, power ECU, ground ECU, soket OBD, dan CAN High/CAN Low.";
    }

    if(in_array('no_start', $signals)){
        $parts[] = "Untuk gejala no start, data penting yang harus dilihat adalah RPM scanner saat starter, status immobilizer, supply injector, pengapian, dan tekanan bahan bakar.";
    }

    if(in_array('ckp', $signals)){
        $parts[] = "Jika RPM scanner 0 saat starter, sensor CKP atau jalurnya sangat dicurigai. CKP yang tidak terbaca bisa membuat injector dan pengapian tidak aktif.";
    }

    if(in_array('injector', $signals)){
        $parts[] = "Jika injector tidak keluar sinyal, penyebab umum adalah CKP/CMP tidak terbaca, immobilizer aktif, supply injector hilang, atau driver injector ECU rusak.";
    }

    if(in_array('immo', $signals)){
        $parts[] = "Jika immobilizer aktif, mesin bisa starter tetapi injector tidak aktif atau mesin hidup sebentar lalu mati. Cek lampu security, kunci, antena immo, dan sinkronisasi ECU.";
    }

    if(in_array('can', $signals)){
        $parts[] = "Jika berkaitan dengan CAN bus, cek apakah CAN High dan CAN Low short ke ground/12V, putus, atau ada salah satu modul yang menarik jaringan CAN.";
    }

    if(in_array('misfire', $signals)){
        $parts[] = "Untuk mesin brebet/pincang, cek busi/coil, injector, kompresi, vacuum leak, MAF/MAP, fuel pressure, dan DTC misfire.";
    }

    if(in_array('limp', $signals)){
        $parts[] = "Untuk tenaga hilang/limp mode, cek DTC boost, MAF/MAP, fuel pressure, throttle, EGR/DPF, dan live data saat mesin digas.";
    }

    if(in_array('smoke', $signals)){
        $parts[] = "Asap hitam biasanya terjadi karena bahan bakar terlalu banyak atau udara kurang. Cek MAF/MAP, filter udara, turbo boost, injector, EGR, dan file remap.";
    }

    if(in_array('fuel_pressure', $signals)){
        $parts[] = "Masalah fuel pressure bisa membuat no start, brebet, atau limp mode. Cek filter, pompa, regulator/SCV, sensor rail, dan live data rail pressure.";
    }

    if(in_array('throttle', $signals)){
        $parts[] = "Masalah throttle/pedal gas bisa membuat gas tidak respons, idle tidak normal, atau limp mode. Cek TPS/APP, supply 5V, ground, soket, dan adaptasi throttle.";
    }

    if(in_array('write', $signals)){
        $parts[] = "Untuk proses write ECU, risiko utama adalah aki drop, koneksi putus, file salah, checksum salah, dan ECU brick. Backup file original wajib dilakukan.";
    }

    if(in_array('remap', $signals)){
        $parts[] = "Untuk remap ECU, file harus sesuai software number. Remap asal bisa menyebabkan knocking, asap berlebih, overboost, boros, atau limp mode.";
    }

    if(count($parts) >= 2){
        return "Analisa dari pertanyaan Anda:\n\n" . implode("\n\n", $parts) . "\n\nSaran urutan cek:\n1. Scan DTC lengkap\n2. Cek aki, fuse, relay, power dan ground ECU\n3. Cek live data sesuai gejala\n4. Baru putuskan apakah masalah di sensor, wiring, software, atau ECU.";
    }

    if(count($parts) === 1){
        return "Analisa dari pertanyaan Anda:\n\n" . $parts[0];
    }

    return "";
}

function searchDuck($question){
    $search = urlencode($question . " ECU repair");
    $url = "https://api.duckduckgo.com/?q={$search}&format=json&no_html=1&skip_disambig=1";
    $response = @file_get_contents($url);

    if($response){
        $json = json_decode($response, true);
        $text = trim($json['AbstractText'] ?? '');
        if($text) return $text;
    }

    return "";
}

$dbFile = __DIR__ . '/knowledge.php';
$db = file_exists($dbFile) ? require $dbFile : [];

$signals = detectSignals($qOriginal);
$dynamic = dynamicReasoning($qOriginal, $signals);

$bestItem = null;
$bestScore = 0;

foreach($db as $item){
    if(!isset($item["keys"], $item["answer"])) continue;
    $score = wordScore($qOriginal, $item["keys"]);

    // bonus kalau sinyal intent sama dengan key
    foreach($signals as $signal){
        if(wordScore($signal, $item["keys"]) >= 60) $score += 10;
    }

    if($score > $bestScore){
        $bestScore = $score;
        $bestItem = $item;
    }
}

if($dynamic && $bestItem && $bestScore >= 32){
    echo json_encode([
        "answer" =>
        $dynamic .
        "\n\nTambahan dari database IDS ECU REPAIR:\n\n" .
        $bestItem["answer"] .
        "\n\nCatatan:\nUntuk hasil akurat, kirim tipe mobil, tipe ECU, kode DTC, dan gejala lengkap ke admin IDS ECU REPAIR."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if($dynamic){
    echo json_encode([
        "answer" =>
        $dynamic .
        "\n\nCatatan:\nUntuk hasil akurat, kirim tipe mobil, tipe ECU, kode DTC, dan gejala lengkap ke admin IDS ECU REPAIR."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if($bestItem && $bestScore >= 38){
    echo json_encode([
        "answer" =>
        "Berdasarkan database IDS ECU REPAIR:\n\n" .
        $bestItem["answer"] .
        "\n\nCatatan:\nUntuk hasil akurat, kirim tipe mobil, tipe ECU, kode DTC, dan gejala lengkap ke admin IDS ECU REPAIR."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$duck = searchDuck($qOriginal);
if($duck){
    echo json_encode([
        "answer" =>
        "Hasil pencarian DuckDuckGo:\n\n" .
        $duck .
        "\n\nCatatan:\nIni hasil pencarian umum. Untuk diagnosa akurat, kirim data kendaraan ke admin IDS ECU REPAIR."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "answer" =>
    "Saya belum bisa memastikan dari kalimat itu.\n\nCoba tulis dengan format:\n- jenis mobil\n- tipe ECU\n- kode DTC\n- gejala kendaraan\n\nContoh: Toyota Hilux no start, DTC P0335, RPM scanner 0, injector tidak keluar."
], JSON_UNESCAPED_UNICODE);
?>
