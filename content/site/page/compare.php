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
$ttl = $titles[$lang] ?? $titles['ro'];
$emp = $empty[$lang]  ?? $empty['ro'];

$sa['meta']['h1']  = $ttl;
$sa['meta']['ttl'] = $ttl . ' | Sauto.md';

// The comparison list itself is client-side (localStorage); only the surrounding
// chrome differs by audience. A logged-in partner keeps the cabinet hero + nav so
// clicking the "Comparare" card doesn't drop them out of the cabinet; guests get
// the plain collection page like /favorites.
$compareBody =
      '<div id="compare_loading" class="fav-loading" style="display:none;">…</div>'
    . '<div id="compare_empty" class="fav-empty" style="display:none;">'.$emp.'</div>'
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
.cmp-wrap{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; border:1px solid #eee; border-radius:14px; background:#fff; }
.cmp-table{ border-collapse:collapse; width:100%; min-width:560px; font-family:"def_l"; font-size:.95rem; }
.cmp-table th, .cmp-table td{ padding:.7rem 1rem; text-align:center; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.cmp-table th{ text-align:left; color:#666; font-weight:700; white-space:nowrap; background:#fafafa; position:sticky; left:0; z-index:1; min-width:110px; }
.cmp-table tr:hover td{ background:#fcfcfc; }
.cmp-table td:not(:first-child){ min-width:180px; }
.cmp-row-photo td, .cmp-row-photo th{ border-bottom:0; padding-bottom:.35rem; }
.cmp-col{ position:relative; display:inline-block; }
.cmp-photo{ display:block; }
.cmp-photo img{ width:190px; height:126px; object-fit:cover; border-radius:10px; background:#f4f4f4; display:block; }
.cmp-remove{ position:absolute; top:6px; right:6px; width:26px; height:26px; border:0; border-radius:50%; background:rgba(0,0,0,.55); color:#fff; font-size:18px; line-height:24px; text-align:center; cursor:pointer; z-index:2; padding:0; transition:background .15s; }
.cmp-remove:hover{ background:#e2001a; }
.cmp-row-name td{ padding-top:.2rem; }
.cmp-name{ font-weight:700; color:#111; text-decoration:none; }
.cmp-name:hover{ color:#e2001a; }
.cmp-price{ color:#e2001a; font-weight:800; font-size:1.05rem; white-space:nowrap; }
@media (max-width:600px){
	.cmp-photo img{ width:132px; height:92px; }
	.cmp-table th{ min-width:88px; }
	.cmp-table td:not(:first-child){ min-width:132px; }
	.cmp-table th, .cmp-table td{ padding:.55rem .6rem; font-size:.86rem; }
}
</style>
<script>
(function(){
	var box = document.getElementById('compare_container');
	if (!box) return;
	var loading = document.getElementById('compare_loading');
	var empty   = document.getElementById('compare_empty');
	function getIds(){ try{ return (JSON.parse(localStorage.getItem('sauto_compare')) || []).map(Number).filter(Boolean); }catch(e){ return []; } }
	function saveIds(a){ try{ localStorage.setItem('sauto_compare', JSON.stringify(a)); }catch(e){} }

	function load(){
		var ids = getIds();
		box.innerHTML = '';
		if (empty) empty.style.display = 'none';
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
				if (data && data.html && data.count > 0) { box.innerHTML = data.html; }
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
