<?php defined( '_DOIT' ) or die( 'Restricted access' );
/**
 * Footer cookie-settings shortcut (Legea 195/2024, Cap. 2 + 5).
 *
 * Withdrawing consent must be as easy as giving it, so this button sits in the
 * footer of every page and reopens the banner.
 *
 * The controller identity is NOT repeated here: the footer already carries the
 * address, phone and e-mail in the contacts column, and the legal name plus
 * IDNO on the copyright line. Duplicating it would only make the footer longer.
 */

$fo_lang = $_COOKIE['lang'] ?? 'ro';

$fo_cookies = [
	'ro' => 'Setări cookie-uri',
	'ru' => 'Настройки cookie',
	'en' => 'Cookie settings',
];
?>
<p class="footer-operator">
	<button type="button" class="footer-cookie-link" data-consent-action="manage"><?php echo $fo_cookies[$fo_lang] ?? $fo_cookies['ro']; ?></button>
</p>
