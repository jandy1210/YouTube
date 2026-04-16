<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$baseFile = 'background.png';
$backupDir = 'backups/';
$cleanTemplate = 'background_Backup.png'; // 預先準備好的乾淨底圖

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// --- 情境一：處理「重設底圖」請求 ---
if (isset($data['action']) && $data['action'] === 'reset') {
    if (!file_exists($cleanTemplate)) {
        echo json_encode(['success' => false, 'message' => '找不到 background_Backup.png 檔案']);
        exit;
    }

    // 1. 備份目前的滿載底圖
    if (file_exists($baseFile)) {
        $backupName = $backupDir . 'full_background_bak_' . date('Ymd_His') . '.png';
        rename($baseFile, $backupName);
    }

    // 2. 將乾淨底圖複製回來
    if (copy($cleanTemplate, $baseFile)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '重設失敗，請檢查權限']);
    }
    exit;
}

// --- 情境二：處理「簽名存檔」請求 (原邏輯) ---
if (isset($data['image'])) {
    $img = $data['image'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $fileData = base64_decode($img);

    $backupName = $backupDir . 'background_bak_' . date('Ymd_His') . '.png';

    if (file_exists($baseFile)) {
        rename($baseFile, $backupName);
    }

    if (file_put_contents($baseFile, $fileData)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '寫入失敗']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '無效的資料']);
}
?>