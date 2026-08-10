<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$prompt = $_POST["prompt"];
echo "PROMPT_RECU: " . $prompt;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Bearer TA_CLE_API"
));

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-image-1",
    "prompt" => $prompt,
    "size" => "1024x1024"
]));

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
