<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include language file for telegram page
include_once(__DIR__ . '/lang_tel.php');

?>
<!DOCTYPE html>
<html lang="<?php echo $_COOKIE['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Telegram page SEO -->
    <title>Telegram | Sauto.md</title>
    <meta name="description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta name="keywords" content="telegram, auto moldova, mașini, chișinău, canal telegram, auto în vânzare">
    <meta property="og:title" content="<?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?> | Sauto.md">
    <meta property="og:description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sauto.md/<?php echo $_COOKIE['lang']; ?>/telegram">
    <meta property="og:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?> | Sauto.md">
    <meta name="twitter:description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta name="twitter:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtag/js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KRRLB4X');</script>
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-TP4GJ51GSL');
    </script>

    <!-- Telegram page specific CSS -->
    <link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/telegram/telegram.css?d=<?php echo date('GYimsd', filemtime(__DIR__ . '/telegram.css')); ?>">
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KRRLB4X" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<div class="telegram-wrapper">
    <div class="telegram-container">
        <!-- Language Switcher -->
        <div class="language-switcher">
            <?php 
            $telegram_langs = ['ro', 'ru', 'en'];
            foreach($telegram_langs as $lang_code): 
            ?>
                <a href="/<?php echo $lang_code; ?>/telegram" 
                   class="lang-btn <?php echo ($_COOKIE['lang'] == $lang_code) ? 'active' : ''; ?>">
                    <?php echo strtoupper($lang_code); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Telegram Image -->
        <div class="telegram-image">
            <img src="/content/site/page/new_pages/telegram/telegram-media/telegram-img.png" alt="Auto Moldova Telegram" />
        </div>
        
        <!-- Content -->
        <h1 class="telegram-title">
            <?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?>
        </h1>
        
        <p class="telegram-subtitle">
            <?php echo $telegram_lang[$_COOKIE['lang']]['subtitle']; ?>
        </p>
        
        
        <div class="telegram-description">
            <?php echo $telegram_lang[$_COOKIE['lang']]['description']; ?>
        </div>
        
        <!-- CTA Button -->
        <a href="https://t.me/+9ISpx4Lrvoc3NzIy" 
           class="telegram-button" 
           target="_blank" 
           rel="noopener noreferrer"
           onclick="gtag('event', 'click', {'event_category': 'telegram', 'event_label': 'join_channel'});">
            <?php echo $telegram_lang[$_COOKIE['lang']]['button_text']; ?>
        </a>
    </div>
</div>

<script>
    // Track page view
    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            'page_title': 'Telegram Landing Page',
            'page_location': window.location.href,
            'page_language': '<?php echo $_COOKIE['lang']; ?>'
        });
    }
</script>

</body>
</html>
