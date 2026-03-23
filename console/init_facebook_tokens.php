<?php
require_once __DIR__ . '/../environment.php';

try {
    $db = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
        SQL_USER,
        SQL_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $prefx = 'gh3sp';
    
    $userToken = 'EAAQKXRYOsxABRN90JbeJHZCV1jZAHgZAwZANLcdv3G4ZAP3SQApsFEowZBNTbx8ItnOMAlxBmBClUN3P05vNBzH2maQ3gDSwfe5SG1ZBnyFhlO9FFPXE11B9IXiKzc0YLeA1ZCDzI5Da9InYOpFOz7uM4AAEfmweQA9CcI2PsRlcnxy6kmq9ZCreP4vsI07ddZApMsqIoQUNJ07bcad9giTJSNTUaViGOYVnI5zEhHZAHAF9raS';
    
    $location1Token = 'EAAQKXRYOsxABRPOIOY5DK3IrMjJVfyvZAF2AK6j0ABT6apXBiDZCLnTpLFhNf3EJqYPOvzoUQMMi8HA6IiBv9XuK2Wul9WTd8UsF2XjQOwSTWJcw18Q1sZBeyQF85VUV3bjpCp27rTlerO9zbrC6NM441HZAffZBQMtKWRA8AN1iOfJ66ZB82m9n9HrwZA7y9lfwdJH91i7';
    
    $location2Token = 'EAAQKXRYOsxABRGPCSpeQ7DbfD8JGxSr2ZB3YUWfl2tvBa1Nc0AEhZBLORqciCWHtYIsrIEdDao4bMGQjO4LjtbaaLcbBChZCO2uwOEfrOtqyQKvGuwAm8BNS0a01Gvvl6PZCdPeTBWxKynXnsPAbxsnyDAm70lHcRXgwAVNUW83Yc1tJrAtcPHx4yn8uHuruhuJE5asU';
    
    $tokens = [
        'location_1_facebook_user_token' => $userToken,
        'location_1_facebook_token' => $location1Token,
        'location_2_facebook_token' => $location2Token
    ];
    
    foreach ($tokens as $name => $value) {
        $stmt = $db->prepare("UPDATE {$prefx}_settings SET value = ? WHERE name = ?");
        $stmt->execute([$value, $name]);
        
        // Dacă nu există, INSERT
        if ($stmt->rowCount() == 0) {
            $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)");
            $stmt->execute([$name, $value]);
            echo "✅ Inserted: {$name}\n";
        } else {
            echo "✅ Updated: {$name}\n";
        }
    }
    
    echo "\n🎉 Toate token-urile au fost salvate cu succes!\n";
    echo "\nToken-uri salvate:\n";
    echo "- User Access Token (location_1_facebook_user_token)\n";
    echo "- Location 1 Page Token (SAUTO - 725963964220309)\n";
    echo "- Location 2 Page Token (Vânzări automobile Piața Pruncu - 482777831588669)\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
