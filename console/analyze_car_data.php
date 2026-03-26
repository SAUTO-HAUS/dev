<?php
/**
 * Script to analyze car data and find garbage values
 * Run: php analyze_car_data.php
 */

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/arrays.php';
require_once __DIR__ . '/../App/Core/Container.php';

\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', $prefx);

$currentYear = (int)date('Y');

$numericRules = [
    'yr' => ['min' => 1980, 'max' => $currentYear + 1, 'digits' => 4],
    'vol' => ['min' => 500, 'max' => 10000],
    'hp' => ['min' => 30, 'max' => 2000],
    'mlg' => ['min' => 0, 'max' => 1000000],
    'sts' => ['min' => 1, 'max' => 60],
    'prc' => ['min' => 100, 'max' => 10000000]
];

$enumRules = [
    'bt' => ['sdn', 'suv', 'hbk', 'unv', 'cup', 'crv', 'mnv', 'pkp', 'van', 'mbs', 'cbr', 'cmb', 'rod', 'frg', 'crr'],
    'fl' => ['gsl', 'gmn', 'gpn', 'hbd', 'dsl', 'pih', 'pid', 'elc', 'gas'],
    'tra' => ['tpt', 'atm', 'mnl', 'rbt', 'vrr'],
    'wd' => ['44', 're', 'fr'],
    'clr' => ['l_grn', 'blu', 'brn', 'cmn', 'cml', 'bge', 'wht', 'vns', 'azr', 'ylw', 'grn', 'gld', 'red', 'orn', 'pnk', 'slv', 'gra', 'd_grn', 'prp', 'blk', 'wap', 'snd'],
    'gr' => ['car', 'com'],
    'cur' => ['EUR', 'USD', 'MDL']
];

echo "=== АНАЛИЗ ДАННЫХ АВТОМОБИЛЕЙ ===\n";
echo "Дата: " . date('Y-m-d H:i:s') . "\n\n";

$sql = "SELECT * FROM {$prefx}_car_ctlg WHERE act = '1'";
$stmt = $db->prepare($sql);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Всего активных записей: " . count($cars) . "\n\n";

$issues = [];
$fieldStats = [];

foreach ($cars as $car) {
    $carId = $car['id'];
    $carName = ($car['br_nm'] ?? '') . ' ' . ($car['mo_nm'] ?? '') . " (ID: $carId)";
    
    foreach ($numericRules as $field => $rule) {
        $value = $car[$field] ?? null;
        
        if (!isset($fieldStats[$field])) {
            $fieldStats[$field] = ['values' => [], 'invalid' => []];
        }
        $fieldStats[$field]['values'][$value] = ($fieldStats[$field]['values'][$value] ?? 0) + 1;
        
        if ($value === null || $value === '') continue;
        
        if (!is_numeric($value)) {
            $issues[] = ['car' => $carName, 'field' => $field, 'value' => $value, 'issue' => 'not_numeric'];
            $fieldStats[$field]['invalid'][$value] = ($fieldStats[$field]['invalid'][$value] ?? 0) + 1;
            continue;
        }
        
        $numValue = (int)$value;
        
        if (isset($rule['digits']) && strlen((string)$numValue) !== $rule['digits']) {
            $issues[] = ['car' => $carName, 'field' => $field, 'value' => $value, 'issue' => 'invalid_digits'];
            $fieldStats[$field]['invalid'][$value] = ($fieldStats[$field]['invalid'][$value] ?? 0) + 1;
            continue;
        }
        
        if ($numValue < $rule['min'] || $numValue > $rule['max']) {
            $issues[] = ['car' => $carName, 'field' => $field, 'value' => $value, 'issue' => 'out_of_range'];
            $fieldStats[$field]['invalid'][$value] = ($fieldStats[$field]['invalid'][$value] ?? 0) + 1;
        }
    }
    
    foreach ($enumRules as $field => $allowedValues) {
        $value = $car[$field] ?? null;
        
        if (!isset($fieldStats[$field])) {
            $fieldStats[$field] = ['values' => [], 'invalid' => []];
        }
        $fieldStats[$field]['values'][$value] = ($fieldStats[$field]['values'][$value] ?? 0) + 1;
        
        if ($value === null || $value === '') continue;
        
        if (!in_array($value, $allowedValues)) {
            $issues[] = ['car' => $carName, 'field' => $field, 'value' => $value, 'issue' => 'invalid_enum'];
            $fieldStats[$field]['invalid'][$value] = ($fieldStats[$field]['invalid'][$value] ?? 0) + 1;
        }
    }
}

echo "=== ЧИСЛОВЫЕ ПОЛЯ ===\n\n";

foreach ($numericRules as $field => $rule) {
    echo "--- $field (диапазон: {$rule['min']}-{$rule['max']}" . (isset($rule['digits']) ? ", {$rule['digits']} цифр" : "") . ") ---\n";
    
    if (isset($fieldStats[$field])) {
        $values = $fieldStats[$field]['values'];
        ksort($values);
        
        $suspicious = [];
        foreach ($values as $val => $count) {
            if ($val === null || $val === '') continue;
            if (!is_numeric($val)) {
                $suspicious[$val] = $count;
                continue;
            }
            $numVal = (int)$val;
            if (isset($rule['digits']) && strlen((string)$numVal) !== $rule['digits']) {
                $suspicious[$val] = $count;
            } elseif ($numVal < $rule['min'] || $numVal > $rule['max']) {
                $suspicious[$val] = $count;
            }
        }
        
        if (!empty($suspicious)) {
            echo "НЕВАЛИДНЫЕ ЗНАЧЕНИЯ:\n";
            foreach ($suspicious as $val => $count) {
                echo "  - '$val' ($count записей)\n";
            }
        } else {
            echo "Невалидных значений не найдено.\n";
        }
    }
    echo "\n";
}

echo "=== ENUM ПОЛЯ ===\n\n";

foreach ($enumRules as $field => $allowedValues) {
    echo "--- $field ---\n";
    
    if (isset($fieldStats[$field])) {
        $values = $fieldStats[$field]['values'];
        
        $invalid = [];
        foreach ($values as $val => $count) {
            if ($val === null || $val === '') continue;
            if (!in_array($val, $allowedValues)) {
                $invalid[$val] = $count;
            }
        }
        
        if (!empty($invalid)) {
            echo "НЕВАЛИДНЫЕ ЗНАЧЕНИЯ:\n";
            foreach ($invalid as $val => $count) {
                echo "  - '$val' ($count записей)\n";
            }
        } else {
            echo "Невалидных значений не найдено.\n";
        }
    }
    echo "\n";
}

if (!empty($issues)) {
    echo "=== ВСЕГО ПРОБЛЕМ: " . count($issues) . " ===\n\n";
    
    $byField = [];
    foreach ($issues as $issue) {
        $byField[$issue['field']][] = $issue;
    }
    
    foreach ($byField as $field => $fieldIssues) {
        echo "--- $field (" . count($fieldIssues) . " проблем) ---\n";
        foreach (array_slice($fieldIssues, 0, 10) as $issue) {
            echo "  [{$issue['car']}] значение='{$issue['value']}' - {$issue['issue']}\n";
        }
        if (count($fieldIssues) > 10) {
            echo "  ... и ещё " . (count($fieldIssues) - 10) . "\n";
        }
        echo "\n";
    }
} else {
    echo "=== ПРОБЛЕМ НЕ НАЙДЕНО ===\n";
}

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
$reportFile = __DIR__ . '/logs/car_data_analysis_' . date('Y-m-d_H-i-s') . '.txt';
file_put_contents($reportFile, ob_get_contents());
echo "\nОтчёт сохранён в: $reportFile\n";
