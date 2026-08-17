<?php defined( '_DOIT' ) or die( 'Restricted access' );
/**
 * Cookie consent banner (Legea 195/2024, Cap. 2).
 *
 * "Refuz" and "Accept" carry identical visual weight — the law requires refusing
 * to be as easy as accepting, so neither button may be styled as the obvious
 * choice. The banner sits at the bottom and never blocks navigation.
 *
 * All tracker loading is done by /content/site/js/consent.js; this file is UI only.
 */

$cons_lang = $_COOKIE['lang'] ?? 'ro';

$cons_t = [
	'ro' => [
		'title'     => 'Cookie-uri',
		'p1'        => 'Folosim cookie-uri ca să înțelegem cum e folosit site-ul și să îți arătăm oferte auto relevante. <b>Pornesc doar cu acordul tău.</b>',
		'p2'        => 'Refuzul nu îți limitează cu nimic accesul la site. Îți poți schimba alegerea oricând din subsolul paginii.',
		'policy'    => 'Politica de Confidențialitate',
		'cookies'   => 'Politica de cookie-uri',
		'reject'    => 'Refuz',
		'accept'    => 'Accept',
		'settings'  => 'Setări',
		'save'      => 'Salvează alegerea',
		'back'      => 'Înapoi',
		'modal_ttl' => 'Setări cookie-uri',
		'modal_sub' => 'Alege ce categorii permiți. Poți reveni oricând.',
		'required'  => 'Mereu active',
		'cat' => [
			'essential' => ['Cookie-uri esențiale', 'Necesare pentru funcționarea site-ului: limba aleasă, sesiunea de autentificare, coșul de favorite. Nu te urmăresc.'],
			'analytics' => ['Analiză trafic', 'Ne arată cum este folosit site-ul, ca să îl îmbunătățim (Google Analytics, Yandex Metrica).'],
			'marketing' => ['Marketing', 'Ne permit să îți arătăm oferte auto relevante în afara site-ului (Facebook Pixel, Google Ads, TikTok Pixel).'],
		],
	],
	'ru' => [
		'title'     => 'Cookie-файлы',
		'p1'        => 'Мы используем cookie, чтобы понимать, как используется сайт, и показывать релевантные автопредложения. <b>Они включаются только с вашего согласия.</b>',
		'p2'        => 'Отказ никак не ограничивает ваш доступ к сайту. Изменить выбор можно в любой момент внизу страницы.',
		'policy'    => 'Политика конфиденциальности',
		'cookies'   => 'Политика использования cookie',
		'reject'    => 'Отказ',
		'accept'    => 'Принять',
		'settings'  => 'Настройки',
		'save'      => 'Сохранить выбор',
		'back'      => 'Назад',
		'modal_ttl' => 'Настройки cookie',
		'modal_sub' => 'Выберите разрешённые категории. Вернуться можно в любой момент.',
		'required'  => 'Всегда активны',
		'cat' => [
			'essential' => ['Основные cookie', 'Необходимы для работы сайта: выбранный язык, сессия авторизации, избранное. Они вас не отслеживают.'],
			'analytics' => ['Аналитика трафика', 'Показывают нам, как используется сайт, чтобы мы могли его улучшать (Google Analytics, Yandex Metrica).'],
			'marketing' => ['Маркетинг', 'Позволяют показывать вам релевантные автопредложения за пределами сайта (Facebook Pixel, Google Ads, TikTok Pixel).'],
		],
	],
	'en' => [
		'title'     => 'Cookies',
		'p1'        => 'We use cookies to understand how the site is used and to show you relevant car offers. <b>They start only with your consent.</b>',
		'p2'        => 'Refusing does not limit your access to the site in any way. You can change your choice at any time from the page footer.',
		'policy'    => 'Privacy Policy',
		'cookies'   => 'Cookie Policy',
		'reject'    => 'Reject',
		'accept'    => 'Accept',
		'settings'  => 'Settings',
		'save'      => 'Save choice',
		'back'      => 'Back',
		'modal_ttl' => 'Cookie settings',
		'modal_sub' => 'Choose which categories you allow. You can come back any time.',
		'required'  => 'Always on',
		'cat' => [
			'essential' => ['Essential cookies', 'Required for the site to work: chosen language, login session, favourites. They do not track you.'],
			'analytics' => ['Traffic analytics', 'Show us how the site is used so we can improve it (Google Analytics, Yandex Metrica).'],
			'marketing' => ['Marketing', 'Let us show you relevant car offers outside the site (Facebook Pixel, Google Ads, TikTok Pixel).'],
		],
	],
];

$ct = $cons_t[$cons_lang] ?? $cons_t['ro'];
$cons_policy_url  = '/'.$cons_lang.'/privacy';
$cons_cookies_url = '/'.$cons_lang.'/cookies';
?>

<div id="cons_bx" class="gdpr-banner" style="display:none;" role="dialog" aria-live="polite" aria-label="<?php echo htmlspecialchars($ct['title'], ENT_QUOTES); ?>">
	<div class="gdpr-banner__text">
		<strong class="gdpr-banner__ttl"><?php echo $ct['title']; ?></strong>
		<p><?php echo $ct['p1']; ?></p>
		<p class="gdpr-banner__sub">
			<?php echo $ct['p2']; ?>
			<span class="gdpr-links">
				<a href="<?php echo $cons_policy_url; ?>" target="_blank"><?php echo $ct['policy']; ?></a><span class="gdpr-sep"> · </span><a href="<?php echo $cons_cookies_url; ?>" target="_blank"><?php echo $ct['cookies']; ?></a>
			</span>
		</p>
	</div>
	<?php // Accept and Refuz keep the same box: same size, padding, font and
	      // position, so refusing stays exactly as easy and as visible as
	      // accepting. Only the fill differs. ?>
	<div class="gdpr-banner__actions">
		<button type="button" class="gdpr-btn gdpr-btn--outline" data-consent-action="reject"><?php echo $ct['reject']; ?></button>
		<button type="button" class="gdpr-btn gdpr-btn--solid" data-consent-action="accept"><?php echo $ct['accept']; ?></button>
		<button type="button" class="gdpr-btn gdpr-btn--link" data-consent-action="settings"><?php echo $ct['settings']; ?></button>
	</div>
</div>

<div id="cons_pref_bx" class="gdpr-modal" role="dialog" aria-modal="true" aria-label="<?php echo htmlspecialchars($ct['modal_ttl'], ENT_QUOTES); ?>">
	<div class="gdpr-modal__box">
		<div class="gdpr-modal__head">
			<h2><?php echo $ct['modal_ttl']; ?></h2>
			<p><?php echo $ct['modal_sub']; ?></p>
		</div>
		<div class="gdpr-modal__body">
			<div class="gdpr-cat">
				<div class="gdpr-cat__txt">
					<span class="gdpr-cat__ttl"><?php echo $ct['cat']['essential'][0]; ?> <em><?php echo $ct['required']; ?></em></span>
					<span class="gdpr-cat__dsc"><?php echo $ct['cat']['essential'][1]; ?></span>
				</div>
				<input type="checkbox" checked disabled aria-label="<?php echo htmlspecialchars($ct['cat']['essential'][0], ENT_QUOTES); ?>">
			</div>
			<div class="gdpr-cat">
				<div class="gdpr-cat__txt">
					<span class="gdpr-cat__ttl"><?php echo $ct['cat']['analytics'][0]; ?></span>
					<span class="gdpr-cat__dsc"><?php echo $ct['cat']['analytics'][1]; ?></span>
				</div>
				<input type="checkbox" id="cons_ck_analytics" aria-label="<?php echo htmlspecialchars($ct['cat']['analytics'][0], ENT_QUOTES); ?>">
			</div>
			<div class="gdpr-cat">
				<div class="gdpr-cat__txt">
					<span class="gdpr-cat__ttl"><?php echo $ct['cat']['marketing'][0]; ?></span>
					<span class="gdpr-cat__dsc"><?php echo $ct['cat']['marketing'][1]; ?></span>
				</div>
				<input type="checkbox" id="cons_ck_marketing" aria-label="<?php echo htmlspecialchars($ct['cat']['marketing'][0], ENT_QUOTES); ?>">
			</div>
		</div>
		<div class="gdpr-modal__actions">
			<button type="button" class="gdpr-btn gdpr-btn--outline" data-consent-action="close-settings"><?php echo $ct['back']; ?></button>
			<button type="button" class="gdpr-btn gdpr-btn--solid" data-consent-action="save"><?php echo $ct['save']; ?></button>
		</div>
		<a class="gdpr-modal__policy" href="<?php echo $cons_policy_url; ?>" target="_blank"><?php echo $ct['policy']; ?></a>
	</div>
</div>
