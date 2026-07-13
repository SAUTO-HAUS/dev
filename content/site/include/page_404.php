<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Generic 404 page content, rendered INSIDE the normal site layout (menu + footer).
// Used for any not-found page except car detail pages (which use car_404.php with cards).

http_response_code(404);

$p4_lang = $_COOKIE['lang'] ?? 'ro';
$p4_msg  = [
    'ro' => 'Ne pare rău, dar această pagină nu a fost găsită.',
    'ru' => 'Сожалеем, но данная страница не найдена.',
    'en' => 'Sorry, this page could not be found.',
][$p4_lang] ?? 'Ne pare rău, dar această pagină nu a fost găsită.';
$p4_btn  = [
    'ro' => 'Înapoi la pagina principală',
    'ru' => 'Вернуться на главную',
    'en' => 'Back to homepage',
][$p4_lang] ?? 'Înapoi la pagina principală';
?>
<div class="gr car404 page404">
    <div class="c404_head">
        <img class="c404_logo" src="/<?php echo _SITE_IMG;?>/logo.png" alt="Sauto" />
        <div class="c404_big">404</div>
        <p class="c404_msg"><?php echo $p4_msg; ?></p>
        <a class="c404_cta" href="/<?php echo $p4_lang; ?>"><?php echo $p4_btn; ?></a>
    </div>
</div>
<style>
.car404 .c404_head {text-align:center; margin:2rem auto 3rem; max-width:680px;}
.car404 .c404_logo {display:block; width:150px; max-width:45%; height:auto; margin:0 auto 1rem; filter:grayscale(1); opacity:.85;}
.car404 .c404_big {font-family:"def_l",Arial,sans-serif; font-weight:800; font-size:6rem; line-height:1; color:#e2001a; margin:0 0 1rem; letter-spacing:2px;}
.car404 .c404_msg {font-size:1.3rem; line-height:1.5; color:#333; font-weight:600; margin:0 0 1.6rem;}
.car404 .c404_cta {display:inline-block; background:#e2001a; color:#fff; font-weight:700; font-size:1rem; text-transform:uppercase; letter-spacing:.5px; text-decoration:none; padding:.9rem 2.2rem; border-radius:.5rem; transition:background .2s, transform .15s;}
.car404 .c404_cta:hover {background:#c40017; color:#fff; transform:translateY(-2px);}
@media (max-width:767px){
  .car404 .c404_head {margin:1.2rem auto 2rem; padding:0 1rem;}
  .car404 .c404_logo {width:110px; margin-bottom:.8rem;}
  .car404 .c404_big {font-size:3.4rem; margin-bottom:.6rem;}
  .car404 .c404_msg {font-size:1rem; line-height:1.45; margin-bottom:1.2rem;}
  .car404 .c404_cta {font-size:.9rem; padding:.8rem 1.6rem;}
}
</style>
