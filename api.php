<?php
// --- Autorisations CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization");
$HF_KEY = getenv("HF_KEY");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$prompt = $_POST["prompt"] ?? $_GET["prompt"] ?? null;

if (!$prompt) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "no prompt"]);
    exit;
}

$api_url = "https://api-inference.huggingface.co/models/black-forest-labs/FLUX.1-schnell"; // modèle rapide
$headers = [
    "Authorization: Bearer $HF_KEY",
    "Content-Type: application/json"
];
$data = ["inputs" => $prompt];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- Vérifie si HuggingFace renvoie une erreur ---
if ($httpCode !== 200 || !$result) {
    header("Content-Type: application/json");
    echo json_encode([
        "error" => "HuggingFace API error",
        "code" => $httpCode,
        "details" => $result
    ]);
    exit;
}

// --- Convertit l'image binaire en base64 ---
$image_base64 = base64_encode($result);

// --- Réponse JSON + CORS ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
echo json_encode(["image_base64" => $image_base64]);
exit;
