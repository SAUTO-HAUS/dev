<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Multilingual titles
$page_titles = [
    'ro' => 'Descrieri SEO pentru paginile brandurilor',
    'ru' => 'SEO-описания для страниц брендов',
    'en' => 'SEO Descriptions for Brand Pages'
];

$page_descriptions = [
    'ro' => '',
    'ru' => '',
    'en' => ''
];

$editor_titles = [
    'ro' => 'Editează descrierea SEO pentru:',
    'ru' => 'Редактировать SEO-описание для:',
    'en' => 'Edit SEO Description for:'
];

$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
$page_title = $page_titles[$current_lang] ?? $page_titles['ro'];
$page_description = $page_descriptions[$current_lang] ?? $page_descriptions['ro'];
$editor_title = $editor_titles[$current_lang] ?? $editor_titles['ro'];

$brands = [];
try {
    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_brands_seo ORDER BY `brand_name` ASC');
    $pdo->execute();
    $brands = $pdo->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rtrn .= '<div style="padding: 20px; background: #ffebee; color: #c62828; border-radius: 4px; margin: 20px 0;">
        <strong>Error:</strong> Table gh3sp_brands_seo does not exist. Please run the SQL script: sql_scripts/create_brands_seo_table.sql
    </div>';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand_seo'])) {
    $brand_id = intval($_POST['brand_id']);
    $description_ro = $_POST['description_ro'] ?? '';
    $description_ru = $_POST['description_ru'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    
    try {
        $pdo = $db->prepare('UPDATE '.$prefx.'_brands_seo SET 
            `description_ro` = :description_ro,
            `description_ru` = :description_ru,
            `description_en` = :description_en
            WHERE `id` = :id');
        $pdo->execute([
            'id' => $brand_id,
            'description_ro' => $description_ro,
            'description_ru' => $description_ru,
            'description_en' => $description_en
        ]);
        
        $rtrn .= '<div style="padding: 15px; background: #e8f5e9; color: #2e7d32; border-radius: 4px; margin: 20px 0;">
            <strong>Success!</strong> SEO description saved successfully.
        </div>';
        
        // Refresh brands data
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_brands_seo ORDER BY `brand_name` ASC');
        $pdo->execute();
        $brands = $pdo->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $rtrn .= '<div style="padding: 15px; background: #ffebee; color: #c62828; border-radius: 4px; margin: 20px 0;">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}
?>

<style>
    .brands-seo-container {
        padding: 20px;
        max-width: 1400px;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
        border-bottom: 2px solid #ff0000;
        padding-bottom: 10px;
    }
    
    .brands-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        margin-bottom: 30px;
    }
    
    .brand-card {
        background: #f5f5f5;
        padding: 10px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    
    .brand-card:hover {
        background: #fff;
        border-color: #ff0000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .brand-card.active {
        background: #fff;
        border-color: #ff0000;
    }
    
    .brand-name {
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .brand-code {
        font-size: 10px;
        color: #999;
        margin-bottom: 4px;
    }
    
    .brand-status {
        font-size: 10px;
        margin-top: 5px;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-block;
    }
    
    .status-filled {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .status-empty {
        background: #fff3e0;
        color: #e65100;
    }
    
    .editor-container {
        background: #fff;
        padding: 20px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: none;
    }
    
    .editor-container.active {
        display: block;
    }
    
    .editor-header {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
    }
    
    .lang-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
    }
    
    .lang-tab {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    
    .lang-tab:hover {
        color: #ff0000;
    }
    
    .lang-tab.active {
        color: #ff0000;
        border-bottom-color: #ff0000;
    }
    
    .lang-content {
        display: none;
    }
    
    .lang-content.active {
        display: block;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }
    
    .form-textarea {
        width: 100%;
        min-height: 300px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: monospace;
        font-size: 13px;
        resize: vertical;
    }
    
    .form-textarea:focus {
        outline: none;
        border-color: #ff0000;
    }
    
    .btn-save {
        background: #ff0000;
        color: #fff;
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        background: #cc0000;
    }
    
    .help-text {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        font-style: italic;
    }
    
    .preview-btn {
        background: #2196F3;
        color: #fff;
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        margin-left: 10px;
        transition: all 0.3s;
    }
    
    .preview-btn:hover {
        background: #1976D2;
    }
</style>

<div class="brands-seo-container">
    <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
    
    <p style="margin-bottom: 20px; color: #666;">
        <?= $page_description ?>
    </p>
    
    <div class="brands-grid">
        <?php foreach ($brands as $brand): 
            $has_content = !empty(trim($brand['description_ro'])) || 
                          !empty(trim($brand['description_ru'])) || 
                          !empty(trim($brand['description_en']));
        ?>
            <div class="brand-card" onclick="selectBrand(<?= $brand['id'] ?>, '<?= htmlspecialchars($brand['brand_name']) ?>')">
                <div class="brand-name"><?= htmlspecialchars($brand['brand_name']) ?></div>
                <div class="brand-code">Code: <?= htmlspecialchars($brand['brand_code']) ?></div>
                <div class="brand-status <?= $has_content ? 'status-filled' : 'status-empty' ?>">
                    <?= $has_content ? '✓ Has content' : '○ Empty' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php foreach ($brands as $brand): ?>
        <div class="editor-container" id="editor-<?= $brand['id'] ?>">
            <div class="editor-header">
                <?= htmlspecialchars($editor_title) ?> <?= htmlspecialchars($brand['brand_name']) ?>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="brand_id" value="<?= $brand['id'] ?>">
                <input type="hidden" name="save_brand_seo" value="1">
                
                <div class="lang-tabs">
                    <button type="button" class="lang-tab active" onclick="switchLang(<?= $brand['id'] ?>, 'ro')">
                        Romanian
                    </button>
                    <button type="button" class="lang-tab" onclick="switchLang(<?= $brand['id'] ?>, 'ru')">
                        Russian
                    </button>
                    <button type="button" class="lang-tab" onclick="switchLang(<?= $brand['id'] ?>, 'en')">
                        English
                    </button>
                </div>
                
                <div class="lang-content active" id="lang-ro-<?= $brand['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">Romanian Description (HTML)</label>
                        <textarea name="description_ro" class="form-textarea"><?= htmlspecialchars($brand['description_ro'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="lang-content" id="lang-ru-<?= $brand['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">Russian Description (HTML)</label>
                        <textarea name="description_ru" class="form-textarea"><?= htmlspecialchars($brand['description_ru'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="lang-content" id="lang-en-<?= $brand['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">English Description (HTML)</label>
                        <textarea name="description_en" class="form-textarea"><?= htmlspecialchars($brand['description_en'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">💾 Save Changes</button>
                <button type="button" class="preview-btn" onclick="window.open('/ro/cars/<?= str_replace('_', '-', $brand['brand_code']) ?>', '_blank')">
                    👁️ Preview Page
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<script>
function selectBrand(brandId, brandName) {
    // Hide all editors
    document.querySelectorAll('.editor-container').forEach(el => {
        el.classList.remove('active');
    });
    
    // Remove active class from all cards
    document.querySelectorAll('.brand-card').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected editor
    const editor = document.getElementById('editor-' + brandId);
    if (editor) {
        editor.classList.add('active');
        editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Mark selected card as active
    event.currentTarget.classList.add('active');
}

function switchLang(brandId, lang) {
    // Remove active class from all tabs
    const container = document.getElementById('editor-' + brandId);
    container.querySelectorAll('.lang-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Hide all content
    container.querySelectorAll('.lang-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Activate selected tab and content
    event.currentTarget.classList.add('active');
    document.getElementById('lang-' + lang + '-' + brandId).classList.add('active');
}
</script>
