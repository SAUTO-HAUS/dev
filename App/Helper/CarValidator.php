<?php

namespace App\Helper;

class CarValidator
{
    private static $currentYear;
    
    private static $numericRules = [
        'yr' => ['min' => 1980, 'max' => null, 'digits' => 4, 'required' => true],
        'vol' => ['min' => 700, 'max' => 10000, 'required' => true],
        'hp' => ['min' => 30, 'max' => 2000, 'required' => true],
        'mlg' => ['min' => 1, 'max' => 999999, 'required' => true],
        'sts' => ['min' => 2, 'max' => 60, 'required' => true],
        'prc' => ['min' => 100, 'max' => 10000000, 'required' => true, 'special' => [1]]
    ];
    
    private static $enumRules = [
        'bt' => [
            'values' => ['sdn', 'suv', 'hbk', 'unv', 'cup', 'crv', 'mnv', 'pkp', 'van', 'mbs', 'cbr', 'cmb', 'rod', 'frg', 'crr'],
            'required' => true
        ],
        'fl' => [
            'values' => ['gsl', 'gmn', 'gpn', 'hbd', 'dsl', 'pih', 'elc', 'gas'],
            'required' => true
        ],
        'tra' => [
            'values' => ['tpt', 'atm', 'mnl', 'rbt', 'vrr'],
            'required' => true
        ],
        'wd' => [
            'values' => ['44', 're', 'fr'],
            'required' => true
        ],
        'clr' => [
            'values' => ['l_grn', 'blu', 'brn', 'cmn', 'cml', 'bge', 'wht', 'vns', 'azr', 'ylw', 'grn', 'gld', 'red', 'orn', 'pnk', 'slv', 'gra', 'd_grn', 'prp', 'blk', 'wap', 'snd'],
            'required' => true
        ],
        'gr' => [
            'values' => ['car', 'com'],
            'required' => true
        ],
        'cur' => [
            'values' => ['EUR', 'USD', 'MDL'],
            'required' => true
        ]
    ];
    
    private static function init()
    {
        if (self::$currentYear === null) {
            self::$currentYear = (int)date('Y');
            self::$numericRules['yr']['max'] = self::$currentYear + 1;
        }
    }
    
    public static function validate(array $data): array
    {
        self::init();
        
        $errors = [];
        $normalized = $data;
        
        foreach (self::$numericRules as $field => $rule) {
            $fieldRule = $rule;
            if ($field === 'vol' && ($data['fl'] ?? '') === 'elc') {
                $fieldRule['special'] = [0];
            }
            $result = self::validateNumeric($data[$field] ?? null, $fieldRule, $field);
            if (!$result['valid']) {
                $errors[$field] = $result['error'];
            } else {
                $normalized[$field] = $result['value'];
            }
        }
        
        foreach (self::$enumRules as $field => $rule) {
            $result = self::validateEnum($data[$field] ?? null, $rule, $field);
            if (!$result['valid']) {
                $errors[$field] = $result['error'];
            } else {
                $normalized[$field] = $result['value'];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized' => $normalized
        ];
    }
    
    public static function validateNumeric($value, array $rule, string $field): array
    {
        self::init();
        
        if ($value === null || $value === '') {
            if ($rule['required'] ?? false) {
                return ['valid' => false, 'error' => 'required', 'value' => null];
            }
            return ['valid' => true, 'value' => null];
        }
        
        $cleaned = preg_replace('/[^\d]/', '', (string)$value);
        
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return ['valid' => false, 'error' => 'not_numeric', 'value' => $value];
        }
        
        $numValue = (int)$cleaned;
        
        if (isset($rule['special']) && in_array($numValue, $rule['special'])) {
            return ['valid' => true, 'value' => $numValue];
        }
        
        if (isset($rule['digits']) && strlen((string)$numValue) !== $rule['digits']) {
            return ['valid' => false, 'error' => 'invalid_digits', 'value' => $value];
        }
        
        $min = $rule['min'] ?? PHP_INT_MIN;
        $max = $rule['max'] ?? PHP_INT_MAX;
        
        if ($numValue < $min || $numValue > $max) {
            return ['valid' => false, 'error' => 'out_of_range', 'value' => $value];
        }
        
        return ['valid' => true, 'value' => $numValue];
    }
    
    public static function validateEnum($value, array $rule, string $field): array
    {
        if ($value === null || $value === '') {
            if ($rule['required'] ?? false) {
                return ['valid' => false, 'error' => 'required', 'value' => null];
            }
            return ['valid' => true, 'value' => null];
        }
        
        $value = trim((string)$value);
        
        if (!in_array($value, $rule['values'], true)) {
            return ['valid' => false, 'error' => 'invalid_value', 'value' => $value];
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    public static function validateField(string $field, $value): array
    {
        self::init();
        
        if (isset(self::$numericRules[$field])) {
            return self::validateNumeric($value, self::$numericRules[$field], $field);
        }
        
        if (isset(self::$enumRules[$field])) {
            return self::validateEnum($value, self::$enumRules[$field], $field);
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    public static function getNumericRules(): array
    {
        self::init();
        return self::$numericRules;
    }
    
    public static function getEnumRules(): array
    {
        return self::$enumRules;
    }
    
    public static function getValidationRulesJson(): string
    {
        self::init();
        return json_encode([
            'numeric' => self::$numericRules,
            'enum' => self::$enumRules
        ]);
    }
}
