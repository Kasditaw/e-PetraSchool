<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("DESCRIBE rpp_dokumen");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "Field: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']}\n";
}
?>
