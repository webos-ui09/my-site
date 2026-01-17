<?php
// Endpoint to receive assistant leads (POST JSON)
// Stores leads to a JSON file and forwards to Telegram if configured.

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if(!is_array($data)){
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'invalid_json']);
    exit;
}

$name = isset($data['name']) ? trim($data['name']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$query = isset($data['query']) ? trim($data['query']) : '';

if(!$phone){
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'missing_phone']);
    exit;
}

// load config
$cfgPath = __DIR__ . '/config/assistant_config.php';
$cfg = file_exists($cfgPath) ? include $cfgPath : [];
$storage = isset($cfg['storage_file']) ? $cfg['storage_file'] : (__DIR__ . '/storage/assistant_leads.json');

// ensure storage directory exists
$dir = dirname($storage);
if(!is_dir($dir)) @mkdir($dir, 0755, true);

$lead = [
    'name' => $name,
    'phone' => $phone,
    'query' => $query,
    'time' => date('c')
];

// append to file
$all = [];
if(file_exists($storage)){
    $raw = @file_get_contents($storage);
    $all = $raw ? json_decode($raw, true) : [];
    if(!is_array($all)) $all = [];
}
$all[] = $lead;
@file_put_contents($storage, json_encode($all, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

// forward to Telegram if configured
if(!empty($cfg['telegram_bot_token']) && !empty($cfg['telegram_chat_id'])){
    $text = "Новая заявка от ассистента:\n".
            "Имя: " . ($name ?: '-')."\n".
            "Телефон: " . $phone . "\n".
            "Запрос: " . ($query ?: '-') . "\n".
            "Время: " . $lead['time'];

    $url = 'https://api.telegram.org/bot' . urlencode($cfg['telegram_bot_token']) . '/sendMessage';
    $post = http_build_query(['chat_id'=>$cfg['telegram_chat_id'],'text'=>$text]);
    // best-effort send
    $opts = ['http'=>['method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n","content"=>$post,'timeout'=>5]];
    $context = stream_context_create($opts);
    @file_get_contents($url, false, $context);
}

echo json_encode(['success'=>true]);
