<?php defined('_DOIT') or die('Restricted access');
// Load translations before any output
$telegram_lang = require __DIR__ . '/lang_tel.php';

// Determine locale from request URI
$uriParts = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
$lang = $uriParts[0] ?? 'ru';
$availableLangs = array_keys($telegram_lang);
if (!in_array($lang, $availableLangs)) {
    $lang = 'ru';
}

// Fallback order for missing keys
$fallbackOrder = ['ru', 'ro', 'en'];

if (!function_exists('telegram_adv_t')) {
    function telegram_adv_t(string $key) {
        global $lang, $telegram_lang, $fallbackOrder;
        if (isset($telegram_lang[$lang][$key])) {
            return $telegram_lang[$lang][$key];
        }
        foreach ($fallbackOrder as $fb) {
            if (isset($telegram_lang[$fb][$key])) {
                return $telegram_lang[$fb][$key];
            }
        }
        return '';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram</title>
    <meta name="description" content="<?php echo telegram_adv_t('meta_description'); ?>">
    <meta name="keywords" content="telegram, auto moldova, mașini, chișinău, canal telegram, auto în vânzare">
    <meta property="og:title" content="<?php echo telegram_adv_t('title'); ?>">
    <meta property="og:description" content="<?php echo telegram_adv_t('meta_description'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sauto.md/<?php echo $lang; ?>/telegram_adv">
    <meta property="og:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo telegram_adv_t('title'); ?>">
    <meta name="twitter:description" content="<?php echo telegram_adv_t('meta_description'); ?>">
    <meta name="twitter:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">

    <link rel="canonical" href="https://www.sauto.md/<?php echo $lang; ?>/telegram_adv">

    <link rel="stylesheet" type="text/css" href="/content/default/css/default.css?d=<?php echo date('GYimsd', filemtime(_DEFAULT.'/css/default.css')); ?>">
    <link rel="stylesheet" type="text/css" href="/content/site/css/style.css?d=<?php echo date('GYimsd', filemtime(_SITE.'/css/style.css')); ?>">
    <link rel="stylesheet" type="text/css" href="/content/site/css/media.css?d=<?php echo date('GYimsd', filemtime(_SITE.'/css/media.css')); ?>">
    <link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/telegram_adv/telegram_adv.css?d=<?php echo date('GYimsd', filemtime(__DIR__ . '/telegram_adv.css')); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <script src="/<?php echo _DEFAULT; ?>/js/js.cookie.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
    <script src="/<?php echo _DEFAULT; ?>/js/sitescripts.js?d=<?php echo date('GYimsd', filemtime(_DEFAULT.'/js/sitescripts.js')); ?>"></script>
    <script src="/<?php echo _SITE; ?>/js/sitescripts.js?d=<?php echo date('GYimsd', filemtime(_SITE.'/js/sitescripts.js')); ?>"></script>

    <!-- This landing bypasses the main layout, so it loads the consent engine
         itself. GTM/GA/Ads/Pixel are injected by consent.js only after consent. -->
    <script data-cfasync="false" src="/<?php echo _SITE; ?>/js/consent.js?d=<?php echo date('GYimsd', filemtime(_SITE.'/js/consent.js')); ?>"></script>
    <link rel="stylesheet" href="/<?php echo _SITE; ?>/css/consent.css?d=<?php echo date('GYimsd', filemtime(_SITE.'/css/consent.css')); ?>">
</head>
<body>
    <?php include(_SITE_INCL.'/consent.php'); ?>

    <main class="tg-landing wrap">
      <section class="card" role="region" aria-labelledby="title">
        <div class="hero">
          <div class="logo" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M44 24C44 35.046 35.046 44 24 44C12.954 44 4 35.046 4 24C4 12.954 12.954 4 24 4C35.046 4 44 12.954 44 24Z" fill="#38BDF8"/>
              <path d="M34.3 15.7L11.2 24.2c-1 .4-.96 1.9.08 2.2l5.4 1.7 13.1-9.8c.2-.15.44.12.27.31l-10.6 11.7v3.9c0 1.1 1.3 1.6 2 .85l3.1-3.5 5.8 3.9c.83.56 1.95.14 2.22-.84l4.4-16c.26-.95-.66-1.82-1.75-1.38Z" fill="white"/>
            </svg>
          </div>
          <div>
            <h1 id="title"><?php echo telegram_adv_t('title'); ?></h1>
            <p class="sub"><?php echo telegram_adv_t('sub'); ?></p>
          </div>
        </div>

        <ul class="list" aria-label="<?php echo telegram_adv_t('list_label'); ?>">
          <?php 
          $list_items = telegram_adv_t('list');
          if (is_array($list_items) && !empty($list_items)): 
            foreach ($list_items as $item): ?>
            <li><span class="dot"><?php echo $item['icon']; ?></span><span><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></span></li>
          <?php endforeach; 
          else: ?>
            <li>Error: List items not found</li>
          <?php endif; ?>
        </ul>

        <div class="cta">
          <a class="btn" href="https://t.me/+9ISpx4Lrvoc3NzIy" rel="noopener" target="_blank" aria-label="<?php echo telegram_adv_t('button_aria'); ?>"><?php echo telegram_adv_t('button_text'); ?></a>
        </div>
      </section>
    </main>


    <script>
    SautoConsent.onAnalytics(function () {
        gtag('event', 'page_view', {
            'page_title': 'Telegram Landing Page',
            'page_location': window.location.href,
            'page_language': '<?php echo $lang; ?>'
        });
    });
    </script>
</body>
</html>
