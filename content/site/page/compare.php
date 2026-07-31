<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/compare — side-by-side comparison. The list lives in localStorage
 * ('sauto_compare'); the JS fetches the comparison table (fn=compare_cars ->
 * compare_table_html) and drops it in. Each column has a remove (×) button.
 */

$lang = $_COOKIE['lang'] ?? 'ro';

$titles = ['ro' => 'Comparare', 'ru' => 'Сравнение', 'en' => 'Compare'];
$empty  = [
	'ro' => 'Nu ai adăugat nicio mașină la comparare',
	'ru' => 'Вы не добавили ни одного авто для сравнения',
	'en' => 'You have not added any cars to compare',
];
$hints  = [
	'ro' => 'Glisează lateral pentru celelalte mașini',
	'ru' => 'Пролистайте вбок, чтобы увидеть остальные авто',
	'en' => 'Swipe sideways to see the other cars',
];
$ttl = $titles[$lang] ?? $titles['ro'];
$emp = $empty[$lang]  ?? $empty['ro'];
$hnt = $hints[$lang]  ?? $hints['ro'];

$sa['meta']['h1']  = $ttl;
$sa['meta']['ttl'] = $ttl . ' | Sauto.md';

// The comparison list itself is client-side (localStorage); only the surrounding
// chrome differs by audience. A logged-in partner keeps the cabinet hero + nav so
// clicking the "Comparare" card doesn't drop them out of the cabinet; guests get
// the plain collection page like /favorites.
$compareBody =
      '<div id="compare_loading" class="fav-loading" style="display:none;">…</div>'
    . '<div id="compare_empty" class="fav-empty" style="display:none;">'.$emp.'</div>'
    . '<div id="compare_hint" class="cmp-hint"><span>'.$hnt.' →</span></div>'
    . '<div id="compare_container"></div>';

if (function_exists('b2b_is_client') && b2b_is_client()) {
    include_once( __DIR__ . '/b2b/_layout.php' );
    echo b2b_assets();
    echo b2b_cabinet_hero($db, b2b_user(), $lang, 'compare');
    echo '<div class="b2b-panel">'.$compareBody.'</div></div>';
} else {
    echo '<div class="gr fav-page">';
    echo '<h1>'.$ttl.'</h1>';
    echo $compareBody;
    echo '</div>';
}
?>
<style>
/* The table scrolls inside its own box: the spec-label column sticks to the left,
   so a row always keeps its meaning while you swipe sideways through the cars.
   border-collapse must stay `separate` — collapsed borders vanish on sticky cells. */
.cmp-wrap{ width:100%; max-width:100%; min-width:0; margin-top:1rem; overflow:auto; -webkit-overflow-scrolling:touch; overscroll-behavior-x:contain; border:1px solid #eee; border-radius:14px; background:#fff; }
.cmp-table{ border-collapse:separate; border-spacing:0; width:100%; min-width:560px; font-family:"def_l"; font-size:.95rem; }
.cmp-table th, .cmp-table td{ padding:.7rem 1rem; text-align:center; border-bottom:1px solid #f0f0f0; vertical-align:middle; background:#fff; }
/* Values sit heavier and darker than the site default (#292929 / 400) — they are what
   you actually scan across columns, so they must out-weigh the labels around them. */
.cmp-table td{ color:#111; font-weight:600; }
.cmp-table th{ text-align:left; color:#e2001a; font-weight:700; white-space:nowrap; background:#fafafa; position:sticky; left:0; z-index:2; min-width:110px; box-shadow:1px 0 0 #f0f0f0; }
.cmp-table tr:last-child th, .cmp-table tr:last-child td{ border-bottom:0; }
@media (hover:hover){ .cmp-table tr:hover td{ background:#fcfcfc; } }
.cmp-table td:not(:first-child){ min-width:180px; }
.cmp-row-photo td, .cmp-row-photo th{ border-bottom:0; padding-bottom:.35rem; }
.cmp-col{ position:relative; display:inline-block; max-width:100%; }
.cmp-photo{ display:block; }
.cmp-photo img{ width:190px; height:126px; object-fit:cover; border-radius:10px; background:#f4f4f4; display:block; }
.cmp-remove{ position:absolute; top:6px; right:6px; width:26px; height:26px; border:0; border-radius:50%; background:rgba(0,0,0,.55); color:#fff; font-size:18px; line-height:24px; text-align:center; cursor:pointer; z-index:2; padding:0; transition:background .15s; -webkit-tap-highlight-color:transparent; }
.cmp-remove:hover{ background:#e2001a; }
.cmp-remove:active{ transform:scale(.92); }
.cmp-row-name td{ padding-top:.2rem; }
.cmp-name{ font-weight:700; color:#111; text-decoration:none; }
.cmp-name:hover{ color:#e2001a; }
.cmp-price{ color:#e2001a; font-weight:800; font-size:1.05rem; white-space:nowrap; }

/* Swipe hint — only while the table really overflows sideways (see JS below). */
.cmp-hint{ display:none; margin:.6rem 0 -.2rem; text-align:center; color:#888; font-family:"def_l"; font-size:.85rem; }
.cmp-hint.is-on{ display:block; }
.cmp-hint span{ display:inline-block; animation:cmpNudge 1.6s ease-in-out infinite; }
@keyframes cmpNudge{ 0%,100%{ transform:translateX(0); } 50%{ transform:translateX(6px); } }
@media (prefers-reduced-motion:reduce){ .cmp-hint span{ animation:none; } }

/* The per-spec band rows exist in the HTML for every viewport but only phones use them. */
.cmp-grp{ display:none; }

/* Tablet + small laptop: same table, tighter cells so three cars fit without scrolling. */
@media (min-width:768px) and (max-width:1199px){
	.cmp-table{ min-width:0; font-size:.9rem; }
	.cmp-table th, .cmp-table td{ padding:.6rem .7rem; }
	.cmp-table th{ min-width:6.5rem; white-space:normal; line-height:1.25; }
	.cmp-table td:not(:first-child){ min-width:170px; }
	.cmp-photo img{ width:100%; max-width:190px; height:auto; aspect-ratio:3/2; }
	.cmp-col{ display:block; }
}

/* Portrait tablet: capped height so both axes scroll inside the box — that is what lets
   the car-name bar stick to the top. With up to 10 cars you otherwise lose track of
   whose column you are reading after a swipe. The label column still fits here. */
@media (min-width:768px) and (max-width:999px) and (orientation:portrait){
	.cmp-wrap{ max-height:78vh; max-height:78dvh; }
	/* Same reason as on phones: a fixed photo height is what makes the name row's
	   offset predictable. .6 (top) + 7 (photo) + .35 (bottom) = 7.95rem. */
	.cmp-photo img{ height:7rem; aspect-ratio:auto; object-fit:cover; }
	.cmp-row-photo td, .cmp-row-photo th{ padding-bottom:.35rem; position:sticky; top:0; z-index:3; }
	.cmp-row-photo th{ left:0; z-index:4; }
	.cmp-row-name td, .cmp-row-name th{ padding-top:.1rem; padding-bottom:.35rem; position:sticky; top:7.95rem; z-index:3; box-shadow:0 3px 6px -5px rgba(0,0,0,.5); }
	.cmp-row-name th{ left:0; z-index:4; box-shadow:1px 0 0 #f0f0f0, 0 3px 6px -5px rgba(0,0,0,.5); }
}

/* Phones: drop the label column entirely — it would take a third of the screen and
   squeeze the values (and a narrow one breaks "Combustibil" one letter per line).
   Each spec becomes a full-width band above its values, with the label pinned to the
   left edge so it stays readable while you swipe sideways. */
@media (max-width:767px){
	.cmp-wrap{ max-height:76vh; max-height:76dvh; border-radius:12px; }
	.cmp-table{ min-width:0; font-size:.9rem; }
	.cmp-table th{ display:none; }
	.cmp-table td{ padding:.55rem .5rem; }
	/* Same specificity as the desktop rule above, or that 180px would still win. */
	.cmp-table td:not(:first-child){ min-width:9.5rem; }
	/* Fixed height, not an aspect ratio: the photo row has to be exactly as tall as the
	   offset the name row sticks at, and an aspect ratio makes that depend on how many
	   cars share the width. .55 (top) + 5.5 (photo) + .35 (bottom) = 6.4rem. */
	.cmp-photo img{ width:100%; height:5.5rem; aspect-ratio:auto; object-fit:cover; border-radius:8px; }
	.cmp-col{ display:block; }
	.cmp-remove{ top:4px; right:4px; width:34px; height:34px; font-size:22px; line-height:32px; background:rgba(0,0,0,.6); }
	/* Spec band. The inner span sticks to the left of the scroll box. */
	.cmp-grp{ display:table-row; }
	.cmp-grp td{ min-width:0; padding:.45rem 0; text-align:left; background:#fafafa; border-bottom:1px solid #f0f0f0; }
	/* The label carries its own padding + background: pinned at left:0 it would
	   otherwise sit flush against the edge once the box is scrolled sideways. */
	.cmp-grp span{ position:sticky; left:0; display:inline-block; padding:0 .6rem; background:#fafafa; color:#e2001a; font-weight:800; font-size:.82rem; letter-spacing:.03em; text-transform:uppercase; white-space:nowrap; }
	/* Photo + name pinned to the top of the box while the specs scroll under them. */
	.cmp-row-photo td{ padding-bottom:.35rem; position:sticky; top:0; z-index:3; }
	/* Tight around the name: it is pinned, so every pixel here is lost from the specs. */
	.cmp-row-name td{ padding:.1rem .5rem .3rem; position:sticky; top:6.4rem; z-index:3; box-shadow:0 3px 6px -5px rgba(0,0,0,.5); }
	.cmp-name{ display:block; font-size:.92rem; line-height:1.25; }
	.cmp-price{ font-size:1.05rem; }
	.cmp-table:has(.cmp-row-photo td:only-of-type) .cmp-photo img{
		width:15rem; max-width:100%; height:auto; aspect-ratio:3/2; margin:0 auto;
	}
	.cmp-table:has(.cmp-row-photo td:only-of-type) .cmp-row-name td{ top:10.9rem; }
}
@media (max-width:400px){
	.cmp-table td:not(:first-child){ min-width:8.6rem; }
}
</style>
<script>
(function(){
	var box = document.getElementById('compare_container');
	if (!box) return;
	var loading = document.getElementById('compare_loading');
	var empty   = document.getElementById('compare_empty');
	var hint    = document.getElementById('compare_hint');
	function getIds(){ try{ return (JSON.parse(localStorage.getItem('sauto_compare')) || []).map(Number).filter(Boolean); }catch(e){ return []; } }
	function saveIds(a){ try{ localStorage.setItem('sauto_compare', JSON.stringify(a)); }catch(e){} }

	// Show the swipe hint only when the table really is wider than its box, and drop
	// it the moment the user swipes — it has done its job by then.
	function syncHint(){
		if (!hint) return;
		var w = box.querySelector('.cmp-wrap');
		var over = !!w && (w.scrollWidth - w.clientWidth) > 8;
		hint.classList.toggle('is-on', over && window.matchMedia('(max-width:999px)').matches);
		if (w && !w.dataset.hintBound) {
			w.dataset.hintBound = '1';
			w.addEventListener('scroll', function(){ if (w.scrollLeft > 4) hint.classList.remove('is-on'); }, { passive:true });
		}
	}
	window.addEventListener('resize', syncHint);
	window.addEventListener('orientationchange', syncHint);

	function load(){
		var ids = getIds();
		box.innerHTML = '';
		if (empty) empty.style.display = 'none';
		if (hint) hint.classList.remove('is-on');
		if (!ids.length) { if (empty) empty.style.display = 'block'; if (window.SautoCompare) window.SautoCompare.sync(); return; }
		if (loading) loading.style.display = 'block';

		var form = new URLSearchParams();
		form.append('tp', 'ste');
		form.append('fn', 'compare_cars');
		form.append('ids', ids.join(','));

		fetch('/ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() })
			.then(function(r){ return r.json(); })
			.then(function(data){
				if (loading) loading.style.display = 'none';
				// Prune stale ids (deleted/inactive cars) so the counters match the table.
				if (data && Array.isArray(data.ids)) {
					saveIds(data.ids.map(Number).filter(Boolean));
					if (window.SautoCompare) window.SautoCompare.sync();
				}
				if (data && data.html && data.count > 0) { box.innerHTML = data.html; syncHint(); }
				else { if (empty) empty.style.display = 'block'; }
			})
			.catch(function(){ if (loading) loading.style.display = 'none'; if (empty) empty.style.display = 'block'; });
	}

	// Remove one car column from the comparison.
	box.addEventListener('click', function(e){
		var b = e.target.closest ? e.target.closest('.cmp-remove') : null;
		if (!b) return;
		e.preventDefault();
		var id = parseInt(b.dataset.removeId, 10);
		saveIds(getIds().filter(function(x){ return x !== id; }));
		if (window.SautoCompare) window.SautoCompare.sync();
		load();
	});

	load();
})();
</script>
