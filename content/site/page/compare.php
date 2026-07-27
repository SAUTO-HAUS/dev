<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/compare — the "compare cars" collection, mirroring /favorites: the list
 * lives in localStorage ('sauto_compare'); the JS below fetches the cards for those
 * ids (fn=compare_cars) and drops them into the same card grid.
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

echo '<div class="gr fav-page">';
echo '<h1>'.$ttl.'</h1>';
echo '<div id="compare_loading" class="fav-loading" style="display:none;">…</div>';
echo '<div id="compare_empty" class="fav-empty" style="display:none;">'.$emp.'</div>';
echo '<div class="cnt list" id="compare_container" data-empty-text="'.htmlspecialchars($emp, ENT_QUOTES).'"></div>';
echo '</div>';
?>
<script>
(function(){
	var box = document.getElementById('compare_container');
	if (!box) return;
	var loading = document.getElementById('compare_loading');
	var empty = document.getElementById('compare_empty');
	var ids = [];
	try { ids = (JSON.parse(localStorage.getItem('sauto_compare')) || []).map(Number).filter(Boolean); } catch(e) {}
	if (!ids.length) { if (empty) empty.style.display = 'block'; return; }
	if (loading) loading.style.display = 'block';

	var form = new URLSearchParams();
	form.append('tp', 'ste');
	form.append('fn', 'compare_cars');
	form.append('ids', ids.join(','));

	fetch('/ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() })
		.then(function(r){ return r.json(); })
		.then(function(data){
			if (loading) loading.style.display = 'none';
			if (data && data.html && (data.count > 0)) {
				box.innerHTML = data.html;
				// Prune localStorage of ids that no longer exist / are inactive.
				if (Array.isArray(data.ids)) {
					try {
						var valid = data.ids.map(Number);
						var cur = (JSON.parse(localStorage.getItem('sauto_compare')) || []).map(Number).filter(Boolean);
						localStorage.setItem('sauto_compare', JSON.stringify(cur.filter(function(id){ return valid.indexOf(id) !== -1; })));
					} catch(e) {}
				}
				// Cards were injected after load — init their sliders + lazy-load.
				try { if (typeof initSliderLazyLoading === 'function') initSliderLazyLoading(); } catch(e){}
				try { if (typeof initProductCardSliders === 'function') initProductCardSliders(); } catch(e){}
				try { if (typeof initMobileCardSliders === 'function' && (window.innerWidth <= 768 || /Mobile|Android|iPhone|iPad/.test(navigator.userAgent))) initMobileCardSliders(); } catch(e){}
				if (window.SautoCompare) window.SautoCompare.sync();
			} else {
				if (empty) empty.style.display = 'block';
			}
		})
		.catch(function(){ if (loading) loading.style.display = 'none'; if (empty) empty.style.display = 'block'; });
})();
</script>
