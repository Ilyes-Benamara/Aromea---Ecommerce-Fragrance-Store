<?php

require_once '../config.php'; // gives us $conn

// ── 1. OPENROUTER KEY ──────────────────────────────────
// Put your real key here. This file stays on the server,
// the browser never sees this string.
define('OPENROUTER_KEY', getenv('OPENROUTER_KEY'));

// ── 2. CORS — allow the frontend to call this file ─────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');          // tighten this to your domain in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 3. ONLY ACCEPT POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── 4. READ THE REQUEST BODY ─────────────────────────────────
// The JS will send: { "answers": { gender, occasion, family, season } }
$body = json_decode(file_get_contents('php://input'), true);

if (!isset($body['answers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing answers']);
    exit;
}

$answers = $body['answers'];

// ── 5. FETCH YOUR FRAGRANCES FROM THE DATABASE ───────────────
// We pull everything the AI needs to make smart decisions:
// name, brand, gender, occasion, season flags, accords, notes
$sql = "
    SELECT
        f.id,
        f.name,
        f.brand,
        f.price,
        f.gender,
        f.occasion,
        f.season_summer,
        f.season_spring,
        f.season_fall,
        f.season_winter,
        f.longevity,
        f.sillage,
        f.stock,
        GROUP_CONCAT(DISTINCT a.name ORDER BY fa.strength DESC SEPARATOR ', ') AS accords,
        GROUP_CONCAT(DISTINCT CASE WHEN fn.tier='top'   THEN n.name END SEPARATOR ', ') AS top_notes,
        GROUP_CONCAT(DISTINCT CASE WHEN fn.tier='heart' THEN n.name END SEPARATOR ', ') AS heart_notes,
        GROUP_CONCAT(DISTINCT CASE WHEN fn.tier='base'  THEN n.name END SEPARATOR ', ') AS base_notes
    FROM fragrances f
    LEFT JOIN fragrance_accords fa ON fa.fragrance_id = f.id
    LEFT JOIN accords a            ON a.id = fa.accord_id
    LEFT JOIN fragrance_notes fn   ON fn.fragrance_id = f.id
    LEFT JOIN notes n              ON n.id = fn.note_id
    WHERE f.stock > 0
    GROUP BY f.id
    ORDER BY f.rating DESC
    LIMIT 40
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$fragrances = [];
while ($row = $result->fetch_assoc()) {
    // Build a clean season string so the AI can read it easily
    $seasons = [];
    if ($row['season_summer']) $seasons[] = 'summer';
    if ($row['season_spring']) $seasons[] = 'spring';
    if ($row['season_fall'])   $seasons[] = 'fall';
    if ($row['season_winter']) $seasons[] = 'winter';

    $fragrances[] = [
        'id'          => (int) $row['id'],
        'name'        => $row['name'],
        'brand'       => $row['brand'],
        'price'       => (float) $row['price'],
        'gender'      => $row['gender'],             // 'him' | 'her' | 'unisex'
        'occasion'    => $row['occasion'],
        'seasons'     => implode(', ', $seasons),
        'accords'     => $row['accords'] ?? '',
        'top_notes'   => $row['top_notes'] ?? '',
        'heart_notes' => $row['heart_notes'] ?? '',
        'base_notes'  => $row['base_notes'] ?? '',
        'longevity'   => $row['longevity'],
        'sillage'     => $row['sillage'],
    ];
}

if (empty($fragrances)) {
    echo json_encode(['recommendations' => [], 'message' => 'No fragrances in stock.']);
    exit;
}

// ── 6. BUILD THE PROMPT FOR THE AI ──────────────────────────
// We tell it the user's preferences and hand it the catalog.
// We ask for JSON back so we can parse it cleanly.

$catalogJson = json_encode($fragrances, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$prompt = <<<PROMPT
You are a luxury fragrance consultant for Aromea, a premium perfume boutique.

A customer has answered a short questionnaire:
- Shopping for: {$answers['gender']}
- Scent family preference: {$answers['family']}
- Budget: {$answers['budget']}
- Occasion: {$answers['occasion']}
- Season: {$answers['season']}
- Owner's age group: {$answers['age']}
- Owner's occupation: {$answers['occupation']}

Below is the full Aromea product catalog (only items in stock):
{$catalogJson}

Your task:
1. Pick the 3 best matching fragrances for this customer.
2. Prioritize: gender match → budget (only recommend fragrances within their price range) → season match → occasion match → scent family / accords.
3. Factor in age and occupation — a student under 20 needs something different than an office worker over 40.
4. Return ONLY a valid JSON array — no extra text, no markdown, no code fences.

Format exactly like this:
[
  {
    "id": 12,
    "name": "Fragrance Name",
    "brand": "Brand",
    "price": 89.99,
    "reason": "One sentence explaining why this matches the customer perfectly.",
    "match_score": 92
  }
]

match_score is a number from 0-100 reflecting how well it fits. Return exactly 3 items, sorted by match_score descending.
PROMPT;

// ── 7. CALL OPENROUTER API ───────────────────────────────────
// OpenRouter uses the same format as OpenAI's API.
// We're using google/gemini-flash-1.5 here — fast and cheap.
// You can change the model to any OpenRouter model you like.

$payload = json_encode([
    'model' => 'google/gemini-2.5-flash',
    'max_tokens'  => 800,
    'temperature' => 0.3,
    'messages'    => [
        [
            'role'    => 'user',
            'content' => $prompt,
        ]
    ],
]);

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_KEY,
        'HTTP-Referer: https://yourdomain.com',   // optional but good practice
        'X-Title: Aromea Chatbot',                // shows in your OpenRouter dashboard
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpStatus !== 200) {
    http_response_code(500);
    echo json_encode([
    'error' => 'OpenRouter error',
    'status' => $httpStatus,
    'details' => json_decode($response, true)
]);
    exit;
}

// ── 8. PARSE AND RETURN THE RESULT ──────────────────────────
$data = json_decode($response, true);
$aiText = $data['choices'][0]['message']['content'] ?? '';

// Strip any accidental markdown fences the AI might add
$aiText = preg_replace('/```json|```/i', '', $aiText);
$aiText = trim($aiText);

$recommendations = json_decode($aiText, true);

if (!is_array($recommendations)) {
    http_response_code(500);
    echo json_encode(['error' => 'AI returned invalid JSON', 'raw' => $aiText]);
    exit;
}

// Return the recommendations to the frontend
echo json_encode([
    'recommendations' => $recommendations,
]);
