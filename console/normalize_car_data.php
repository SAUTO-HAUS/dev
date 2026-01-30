<?php
/**
 * Script to normalize existing car data
 * Run: php normalize_car_data.php [--dry-run]
 */

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/arrays.php';
require_once __DIR__ . '/../App/Core/Container.php';
require_once __DIR__ . '/../App/Helper/CarValidator.php';

use App\Helper\CarValidator;

\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', $prefx);

$dryRun = in_array('--dry-run', $argv ?? []);

echo "=== НОРМАЛИЗАЦИЯ ДАННЫХ АВТОМОБИЛЕЙ ===\n";
echo "Дата: " . date('Y-m-d H:i:s') . "\n";
echo "Режим: " . ($dryRun ? "ТЕСТ (без изменений)" : "БОЕВОЙ") . "\n\n";

$sql = "SELECT id, br_nm, mo_nm, yr, vol, hp, mlg, sts, prc, bt, fl, tra, wd, clr, gr, cur FROM {$prefx}_car_ctlg WHERE act = '1'";
$stmt = $db->prepare($sql);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Всего записей: " . count($cars) . "\n\n";

$fixed = 0;
$flagged = 0;
$updates = [];

foreach ($cars as $car) {
    $carId = $car['id'];
    $carName = ($car['br_nm'] ?? '') . ' ' . ($car['mo_nm'] ?? '') . " (ID: $carId)";
    
    $validation = CarValidator::validate($car);
    
    if (!$validation['valid']) {
        $canFix = true;
        $fixedFields = [];
        $unfixableFields = [];
        
        foreach ($validation['errors'] as $field => $error) {
            $originalValue = $car[$field] ?? null;
            $normalizedValue = $validation['normalized'][$field] ?? null;
            
            if ($normalizedValue !== null && $normalizedValue !== $originalValue) {
                $fixedFields[$field] = [
                    'from' => $originalValue,
                    'to' => $normalizedValue
                ];
            } else {
                $canFix = false;
                $unfixableFields[$field] = [
                    'value' => $originalValue,
                    'error' => $error
                ];
            }
        }
        
        if (!empty($fixedFields)) {
            echo "[$carName]\n";
            echo "  Автоисправление:\n";
            foreach ($fixedFields as $field => $change) {
                echo "    $field: '{$change['from']}' -> '{$change['to']}'\n";
            }
            
            if (!$dryRun) {
                $setClauses = [];
                $params = ['id' => $carId];
                foreach ($fixedFields as $field => $change) {
                    $setClauses[] = "`$field` = :$field";
                    $params[$field] = $change['to'];
                }
                
                $updateSql = "UPDATE {$prefx}_car_ctlg SET " . implode(', ', $setClauses) . " WHERE id = :id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute($params);
            }
            
            $fixed++;
        }
        
        if (!empty($unfixableFields)) {
            if (empty($fixedFields)) {
                echo "[$carName]\n";
            }
            echo "  ТРЕБУЕТ РУЧНОГО ИСПРАВЛЕНИЯ:\n";
            foreach ($unfixableFields as $field => $info) {
                echo "    $field: '{$info['value']}' ({$info['error']})\n";
            }
            $flagged++;
        }
        
        echo "\n";
    }
}

echo "=== ИТОГО ===\n";
echo "Автоисправлено: $fixed записей\n";
echo "Требует ручного исправления: $flagged записей\n";

if ($dryRun) {
    echo "\nЭто был ТЕСТОВЫЙ запуск. Изменения не применены.\n";
    echo "Запустите без --dry-run для применения изменений.\n";
}
