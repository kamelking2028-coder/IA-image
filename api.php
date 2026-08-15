<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$config = include("config.php");
$HF_KEY = $config["HF_KEY"] ?? null;

if (!$HF_KEY) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "no HF_KEY in config.php"]);
    exit;
}

// --- Lire le JSON envoyé ---
$input = json_decode(file_get_contents("php://input"), true);
$prompt = $input["prompt"] ?? null;
$model  = $input["model"]  ?? "openjourney"; // valeur par défaut

if (!$prompt) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "no prompt"]);
    exit;
}

// --- Choix du modèle HuggingFace ---
switch ($model) {
    case "openjourney":
        // binaire (image brute)
        $api_url   = "https://api-inference.huggingface.co/models/prompthero/openjourney-v4";
        $response_type = "binary";
        break;

    case "sd3":
        // JSON (SD3-medium)
        $api_url   = "https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-3-medium";
        $response_type = "json_sd3";
        break;

    case "sd15":
        // binaire (si jamais réactivé)
        $api_url   = "https://api-inference.huggingface.co/models/runwayml/stable-diffusion-v1-5";
        $response_type = "binary";
        break;

    default:
        header("Content-Type: application/json");
        echo json_encode(["error" => "unknown model", "model" => $model]);
        exit;
}

$headers = [
    "Authorization: Bearer $HF_KEY",
    "Content-Type: application/json"
];

// ?? HuggingFace attend "inputs", pas "prompt"
$data = ["inputs" => $prompt];

// --- cURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($result === false) {
    $curlError = curl_error($ch);
    header("Content-Type: application/json");
    echo json_encode([
        "error" => "curl_exec failed",
        "code"  => $httpCode,
        "curl"  => $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    header("Content-Type: application/json");
    echo json_encode([
        "error"   => "HuggingFace API error",
        "code"    => $httpCode,
        "details" => $result
    ]);
    exit;
}

// --- Traitement selon le type de réponse ---
if ($response_type === "binary") {
    // Image brute ? base64 direct
    $image_base64 = base64_encode($result);

    header("Content-Type: application/json");
    echo json_encode([
        "model"        => $model,
        "image_base64" => $image_base64
    ]);
    exit;
}

if ($response_type === "json_sd3") {
    // SD3-medium renvoie du JSON
    $json = json_decode($result, true);

    if (!is_array($json)) {
        header("Content-Type: application/json");
        echo json_encode([
            "error"   => "invalid JSON from SD3",
            "raw"     => $result
        ]);
        exit;
    }

    // SD3 renvoie souvent un tableau avec un champ "generated_image"
    $image_base64 = $json[0]["generated_image"] ?? null;

    if (!$image_base64) {
        header("Content-Type: application/json");
        echo json_encode([
            "error"   => "no generated_image in SD3 response",
            "json"    => $json
        ]);
        exit;
    }

    header("Content-Type: application/json");
    echo json_encode([
        "model"        => $model,
        "image_base64" => $image_base64
    ]);
    exit;
}
