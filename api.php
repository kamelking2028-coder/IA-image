<?php
header("Access-Control-Allow-Origin: https://kamelking2028-coder.github.io");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}






/*
|--------------------------------------------------------------------------
| Lecture du prompt (FormData OU JSON)
|--------------------------------------------------------------------------
*/

$prompt = null;
$model  = "openjourney"; // valeur par défaut

// 1) FormData (POST classique)
if (isset($_POST["prompt"])) {
    $prompt = $_POST["prompt"];
}

// 2) JSON (fetch avec Content-Type: application/json)
$input = json_decode(file_get_contents("php://input"), true);

if (is_array($input)) {
    if (!$prompt && isset($input["prompt"])) {
        $prompt = $input["prompt"];
    }
    if (isset($input["model"])) {
        $model = $input["model"];
    }
}

if (!$prompt) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "no prompt"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Choix du modèle HuggingFace
|--------------------------------------------------------------------------
*/

switch ($model) {
    case "openjourney":
        $api_url        = "https://api-inference.huggingface.co/models/prompthero/openjourney-v4";
        $response_type  = "binary";
        break;

    case "sd3":
        $api_url        = "https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-3-medium";
        $response_type  = "json_sd3";
        break;

    case "sd15":
        $api_url        = "https://api-inference.huggingface.co/models/runwayml/stable-diffusion-v1-5";
        $response_type  = "binary";
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

// HuggingFace attend "inputs"
$data = ["inputs" => $prompt];

/*
|--------------------------------------------------------------------------
| cURL vers HuggingFace
|--------------------------------------------------------------------------
*/

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
    header("Content-Type: application/json");
    echo json_encode([
        "error" => "curl_exec failed",
        "curl"  => curl_error($ch)
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

/*
|--------------------------------------------------------------------------
| Traitement de la réponse HuggingFace
|--------------------------------------------------------------------------
*/

if ($response_type === "binary") {
    $image_base64 = base64_encode($result);

    header("Content-Type: application/json");
    echo json_encode([
        "model"        => $model,
        "image_base64" => $image_base64
    ]);
    exit;
}

if ($response_type === "json_sd3") {
    $json = json_decode($result, true);

    if (!is_array($json)) {
        header("Content-Type: application/json");
        echo json_encode([
            "error" => "invalid JSON from SD3",
            "raw"   => $result
        ]);
        exit;
    }

    $image_base64 = $json[0]["generated_image"] ?? null;

    if (!$image_base64) {
        header("Content-Type: application/json");
        echo json_encode([
            "error" => "no generated_image in SD3 response",
            "json"  => $json
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
