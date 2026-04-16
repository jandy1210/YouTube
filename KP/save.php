<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$baseFile = 'background.png';
$backupDir = 'backups/';
$cleanTemplate = 'background_Backup.png';

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// 新增：檢查底圖狀態
if (isset($data['action']) && $data['action'] === 'check') {
    $lastMod = file_exists($baseFile) ? filemtime($baseFile) : 0;
    echo json_encode(['success' => true, 'lastModified' => $lastMod]);
    exit;
}

// 處理重設底圖
if (isset($data['action']) && $data['action'] === 'reset') {
    if (!file_exists($cleanTemplate)) {
        echo json_encode(['success' => false, 'message' => '找不到 background_Backup.png']);
        exit;
    }
    if (file_exists($baseFile)) {
        rename($baseFile, $backupDir . 'full_bak_' . date('Ymd_His') . '.png');
    }
    if (copy($cleanTemplate, $baseFile)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '重設失敗']);
    }
    exit;
}

// 處理簽名存檔
if (isset($data['image'])) {
    $img = $data['image'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $fileData = base64_decode($img);

    if (file_exists($baseFile)) {
        rename($baseFile, $backupDir . 'bak_' . date('Ymd_His') . '_' . uniqid() . '.png');
    }

    if (file_put_contents($baseFile, $fileData)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '寫入失敗']);
    }
}
?>