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

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KRRLB4X');</script>
    <!-- End Google Tag Manager -->

    <!-- Google tag (Ads) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-964347386"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'AW-964347386');
    </script>

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-TP4GJ51GSL');
    </script>

    <!-- Facebook Pixel Code -->
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '701415057290990');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=701415057290990&ev=PageView&noscript=1"/></noscript>
    <!-- End Facebook Pixel Code -->
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KRRLB4X" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

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
    // Track page view
    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            'page_title': 'Telegram Landing Page',
            'page_location': window.location.href,
              'page_language': '<?php echo $lang; ?>'
        });
    }
    </script>
</body>
</html>
