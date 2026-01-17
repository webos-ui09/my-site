<?php
require_once __DIR__ . '/inc_auth.php';
// simple admin viewer for assistant leads
$storage = __DIR__ . '/../storage/assistant_leads.json';
if(!file_exists($storage)) $leads = [];
else { $raw = file_get_contents($storage); $leads = $raw ? json_decode($raw,true) : []; if(!is_array($leads)) $leads = []; }

if(isset($_GET['action']) && $_GET['action']==='clear'){
    @file_put_contents($storage, json_encode([], JSON_UNESCAPED_UNICODE));
    header('Location: assistant_leads.php'); exit;
}

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Лиды ассистента</title>
<style>body{font-family:Arial, sans-serif;padding:20px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>
</head><body>
<h1>Лиды онлайн-ассистента (<?php echo count($leads); ?>)</h1>
<p><a href="assistant_leads.php?action=clear" onclick="return confirm('Очистить все лиды?')">Очистить всё</a></p>
<?php if(empty($leads)): ?><p>Нет лидов.</p><?php else: ?>
<table>
<thead><tr><th>#</th><th>Время</th><th>Имя</th><th>Телефон</th><th>Запрос</th></tr></thead>
<tbody>
<?php foreach($leads as $i=>$l): ?>
<tr>
<td><?php echo $i+1; ?></td>
<td><?php echo htmlspecialchars($l['time'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($l['name'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($l['phone'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($l['query'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?></body></html>
