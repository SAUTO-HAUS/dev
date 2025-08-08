<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once(__DIR__ . '/lang_tel.php');

?>

<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/telegram/telegram.css?d=<?php echo date('GYimsd', filemtime(__DIR__ . '/telegram.css')); ?>">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KRRLB4X" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<div class="telegram-wrapper">
    <div class="telegram-container">

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
        
        <div class="telegram-image">
            <img src="/content/site/page/new_pages/telegram/telegram-media/telegram-img.png" alt="Auto Moldova Telegram" />
        </div>
        
        <h1 class="telegram-title">
            <?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?>
        </h1>
        
        <p class="telegram-subtitle">
            <?php echo $telegram_lang[$_COOKIE['lang']]['subtitle']; ?>
        </p>
        
        
        <div class="telegram-description">
            <?php echo $telegram_lang[$_COOKIE['lang']]['description']; ?>
        </div>
        
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

    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            'page_title': 'Telegram Landing Page',
            'page_location': window.location.href,
            'page_language': '<?php echo $_COOKIE['lang']; ?>'
        });
    }
</script>
