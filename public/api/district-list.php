<?php

ob_start();

require_once __DIR__ . '/../../app/bootstrap.php';

if (ob_get_length() !== false) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

$provinceId = (int) ($_GET['province_id'] ?? 0);

if ($provinceId <= 0) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Geçerli bir il ID değeri gönderilmelidir.',
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $stmt = db()->prepare("
        SELECT id, name
        FROM districts
        WHERE province_id = :province_id
        ORDER BY name ASC
    ");

    $stmt->execute([
        'province_id' => $provinceId,
    ]);

    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'İlçe listesi getirildi.',
        'data' => $districts,
    ], JSON_UNESCAPED_UNICODE);

    exit;
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'İlçe listesi alınırken bir hata oluştu.',
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);

    exit;
}