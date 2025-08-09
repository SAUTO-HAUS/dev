<?php defined('_DOIT') or die('Restricted access');
?>
<!DOCTYPE html>
<html lang="<?php echo $_COOKIE['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram</title>
    <meta name="description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta name="keywords" content="telegram, auto moldova, mașini, chișinău, canal telegram, auto în vânzare">
    <meta property="og:title" content="<?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?>">
    <meta property="og:description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sauto.md/<?php echo $_COOKIE['lang']; ?>/telegram_adv">
    <meta property="og:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $telegram_lang[$_COOKIE['lang']]['title']; ?>">
    <meta name="twitter:description" content="<?php echo $telegram_lang[$_COOKIE['lang']]['meta_description']; ?>">
    <meta name="twitter:image" content="https://sauto.md/content/site/page/new_pages/telegram/telegram-media/telegram-img.png">

    <link rel="stylesheet" type="text/css" href="/content/default/css/default.css?d=<?php echo date('GYimsd', filemtime(_DEFAULT.'/css/default.css')); ?>">
    <link rel="stylesheet" type="text/css" href="/content/site/css/style.css?d=<?php echo date('GYimsd', filemtime(_SITE.'/css/style.css')); ?>">
    <link rel="stylesheet" type="text/css" href="/content/site/css/media.css?d=<?php echo date('GYimsd', filemtime(_SITE.'/css/media.css')); ?>">
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

    <div class="telegram-wrapper">
        <div class="telegram-container">
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
