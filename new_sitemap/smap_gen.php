<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * SAUTO New Sitemap Generator - Web Interface
 * Compatible with SAUTO architecture
 */

echo '<div style="max-width: 1200px; margin: 20px auto; padding: 20px; font-family: Arial, sans-serif;">';
echo '<h1 style="color: #2c3e50; text-align: center;">SAUTO Sitemap Generator - New System</h1>';

// Include the new sitemap generation functionality
$new_sitemap_dir = __DIR__;

echo '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">';
echo '<h2 style="color: #3498db;">🔧 Acțiuni Disponibile</h2>';

echo '<div style="display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;">';

// Button to generate sitemap
echo '<a href="?generate=1" style="background: #27ae60; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        📝 Generează Sitemap Nou
      </a>';

// Button to validate sitemap
echo '<a href="?validate=1" style="background: #e74c3c; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        ✅ Validează Sitemap
      </a>';

// Button to view sitemap
echo '<a href="/sitemap.xml" target="_blank" style="background: #3498db; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        👁️ Vezi Sitemap
      </a>';

echo '</div>';
echo '</div>';

// Handle actions
if (isset($_GET['generate'])) {
    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<h3 style="color: #155724;">🚀 Generare Sitemap în curs...</h3>';
    
    // Include and run the new sitemap generator
    if (file_exists($new_sitemap_dir . '/generate_sitemap_safe_real.php')) {
        ob_start();
        include($new_sitemap_dir . '/generate_sitemap_safe_real.php');
        $output = ob_get_clean();
        
        echo '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">';
        echo htmlspecialchars($output);
        echo '</pre>';
    } else {
        echo '<p style="color: #721c24;">❌ Fișierul generator nu a fost găsit!</p>';
    }
    
    echo '</div>';
}

if (isset($_GET['validate'])) {
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<h3 style="color: #856404;">🔍 Validare Sitemap în curs...</h3>';
    
    // Include and run the sitemap validator
    if (file_exists($new_sitemap_dir . '/validate_sitemap.php')) {
        ob_start();
        include($new_sitemap_dir . '/validate_sitemap.php');
        $output = ob_get_clean();
        
        echo '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">';
        echo htmlspecialchars($output);
        echo '</pre>';
    } else {
        echo '<p style="color: #721c24;">❌ Fișierul validator nu a fost găsit!</p>';
    }
    
    echo '</div>';
}

// Show current sitemap status
echo '<div style="background: #e3f2fd; border: 1px solid #bbdefb; padding: 15px; border-radius: 5px; margin: 20px 0;">';
echo '<h3 style="color: #1565c0;">📊 Status Sitemap Curent</h3>';

$sitemap_files = [
    '../sitemap.xml' => 'Sitemap Principal (Index)',
    '../sitemap-1.xml' => 'Sitemap Conținut'
];

foreach ($sitemap_files as $file => $description) {
    $full_path = $new_sitemap_dir . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        $modified = date('Y-m-d H:i:s', filemtime($full_path));
        echo '<p>✅ <strong>' . $description . '</strong><br>';
        echo '&nbsp;&nbsp;&nbsp;📁 Mărime: ' . number_format($size) . ' bytes<br>';
        echo '&nbsp;&nbsp;&nbsp;🕒 Modificat: ' . $modified . '</p>';
    } else {
        echo '<p>❌ <strong>' . $description . '</strong> - Nu există</p>';
    }
}

echo '</div>';

echo '<div style="background: #f1f3f4; padding: 15px; border-radius: 5px; margin: 20px 0; font-size: 14px;">';
echo '<h4 style="color: #5f6368;">ℹ️ Informații</h4>';
echo '<p>Acest generator folosește noul sistem de sitemap din directorul <code>new_sitemap/</code>.</p>';
echo '<p>Sitemap-ul generat respectă standardele XML Sitemap Protocol și include toate paginile active din site.</p>';
echo '</div>';

echo '</div>';
?>
