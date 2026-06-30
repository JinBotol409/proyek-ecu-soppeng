<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$question = trim($data['question'] ?? '');

if ($question === '') {
    echo json_encode(['answer' => 'Pertanyaan kosong.']);
    exit;
}

$apiKey = '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';

$knowledgeFile = __DIR__ . '/knowledge.txt';
$knowledge = file_exists($knowledgeFile) ? file_get_contents($knowledgeFile) : '';

if (!function_exists('curl_init')) {
    echo json_encode(['answer' => 'PHP cURL belum aktif di server. Install/aktifkan php-curl.']);
    exit;
}

$payload = [
    "model" => "gpt-4.1-mini",
    "input" => "Anda adalah AI Diagnosa ECU IDS Repair. Jawab bahasa Indonesia. Konteks: $knowledge\n\nPertanyaan user: $question",
    "max_output_tokens" => 700
];

$ch = curl_init('https://api.openai.com/v1/responses');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo json_encode(['answer' => 'cURL error: ' . $error]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    echo json_encode([
        'answer' => 'OpenAI API error HTTP '.$httpCode.': '.$response
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$answer = $result['output'][0]['content'][0]['text'] ?? '';

if (!$answer) {
    echo json_encode(['answer' => 'Response OpenAI kosong: ' . $response], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['answer' => $answer], JSON_UNESCAPED_UNICODE);