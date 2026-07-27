<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Side-by-side comparison table for a set of car ids: photo + brand/model title,
 * then one row per spec (year, mileage, fuel, transmission, engine, power, seats,
 * drivetrain, colour, body, price), one column per car. Used by /compare via
 * fn=compare_cars.
 */
if (!function_exists('compare_table_html')) {
	// $validIds is filled (by reference) with the ids that were actually rendered —
	// existing, visible, active cars — so the caller can prune stale ids (deleted or
	// deactivated cars) from the client's localStorage, keeping the counter honest.
	function compare_table_html($db, $prefx, $lng, array $ids, &$validIds = null) {
		$validIds = array();
		$ids = array_values(array_unique(array_map('intval', array_filter($ids))));
		if (!$ids) { return ''; }

		try {
			$place = implode(',', array_fill(0, count($ids), '?'));
			$st = $db->prepare('SELECT id, br, mo, br_nm, mo_nm, yr, fl, tra, vol, hp, sts, wd, clr, bt, mlg, unit, prc, prc_n, cur, catalog_type, p_path, parsing_id, parsing_source FROM '.$prefx.'_car_ctlg WHERE id IN ('.$place.') AND vis="1" AND act="1"');
			$st->execute($ids);
			$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: array();
		} catch (Exception $e) { return ''; }
		if (!$rows) { return ''; }

		$byId = array();
		foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }
		$cars = array();
		foreach ($ids as $id) { if (isset($byId[$id])) { $cars[] = $byId[$id]; } }
		if (!$cars) { return ''; }

		// Ids actually rendered, in display order (for localStorage pruning).
		$validIds = array_map(function ($r) { return (int)$r['id']; }, $cars);

		// Client sees their B2B price where applicable.
		$b2b = function_exists('b2b_prices_for_cars') ? b2b_prices_for_cars($cars) : array();

		$lang = $_COOKIE['lang'] ?? 'ro';
		$loc = array(
			'ro' => array('instock'=>'În stoc','onorder'=>'La comandă','remove'=>'Scoate din comparare','avail'=>'Disponibilitate'),
			'ru' => array('instock'=>'В наличии','onorder'=>'Под заказ','remove'=>'Убрать из сравнения','avail'=>'Наличие'),
			'en' => array('instock'=>'In stock','onorder'=>'On order','remove'=>'Remove from compare','avail'=>'Availability'),
		);
		$T   = isset($loc[$lang]) ? $loc[$lang] : $loc['ro'];
		$spec = isset($lng['l']['spec']) ? $lng['l']['spec'] : array();
		$unit = isset($lng['l']['unit']) ? $lng['l']['unit'] : array();
		$carL = isset($lng['l']['car']) ? $lng['l']['car'] : array();
		$esc = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
		// Value from a lang value-map (fuel/transmission/body/colour/drivetrain).
		$mapVal = function($map, $key) use ($carL, $esc) {
			$k = (string)$key;
			if ($k === '') { return '&mdash;'; }
			return $esc(isset($carL[$map][$k]) ? $carL[$map][$k] : $k);
		};

		$photo = function($r) use ($db, $prefx) {
			try {
				$ps = $db->prepare('SELECT `name`, `ff` FROM '.$prefx.'_car_pht WHERE `it_id`=:id AND `main`="1" LIMIT 1');
				$ps->execute(array('id'=>(int)$r['id']));
				$p = $ps->fetch(PDO::FETCH_ASSOC);
			} catch (Exception $e) { $p = null; }
			if ($p && !empty($p['name'])) {
				return '/'._CAR_IMG.'/'.$r['p_path'].'/'.(int)$r['id'].'/med/'.$p['name'].'.'.($p['ff'] ?: 'jpg');
			}
			return '/'._SITE_IMG.'/v2/no_image.svg';
		};

		// Rows below the photo/name: [key, label, value-cell HTML per car].
		$order = array('yr', 'mlg', 'fl', 'tra', 'vol', 'hp', 'sts', 'wd', 'clr', 'bt', 'prc');
		$labels = array(
			'yr'  => isset($spec['yr'])  ? $spec['yr']  : 'An',
			'mlg' => isset($spec['mlg']) ? $spec['mlg'] : 'Rulaj',
			'fl'  => isset($spec['fl'])  ? $spec['fl']  : 'Combustibil',
			'tra' => isset($spec['tra']) ? $spec['tra'] : 'Transmisie',
			'vol' => isset($spec['vol']) ? $spec['vol'] : 'Motor',
			'hp'  => isset($spec['hp'])  ? $spec['hp']  : 'Putere',
			'sts' => isset($spec['sts']) ? $spec['sts'] : 'Locuri',
			'wd'  => isset($spec['wd'])  ? $spec['wd']  : 'Tracțiune',
			'clr' => isset($spec['clr']) ? $spec['clr'] : 'Culoare',
			'bt'  => isset($spec['bt'])  ? $spec['bt']  : 'Caroserie',
			'prc' => isset($spec['prc']) ? $spec['prc'] : 'Preț',
		);

		$c_photo = ''; $c_name = '';
		$cells = array(); foreach ($order as $k) { $cells[$k] = ''; }

		foreach ($cars as $r) {
			$id = (int)$r['id'];
			$pt = (isset($r['catalog_type']) && $r['catalog_type']=='on_order') ? 'ordercars' : 'cars';
			$url = '/'.$lang.'/'.$pt.'/'.$id;
			$name = $esc(trim($r['br_nm'].' '.$r['mo_nm']));

			$c_photo .= '<td><div class="cmp-col"><button type="button" class="cmp-remove" data-remove-id="'.$id.'" title="'.$esc($T['remove']).'" aria-label="'.$esc($T['remove']).'">&times;</button><a class="cmp-photo" href="'.$url.'"><img src="'.$photo($r).'" loading="lazy" alt="'.$name.'"></a></div></td>';
			$c_name  .= '<td><a class="cmp-name" href="'.$url.'">'.$name.'</a></td>';

			$prcVal = !empty($b2b[$id]) ? (int)$b2b[$id] : (($r['prc_n']!=0 && $r['prc_n']<$r['prc']) ? (int)$r['prc_n'] : (int)$r['prc']);
			$mUnit  = isset($unit[$r['unit']]) ? $unit[$r['unit']] : ($r['unit'] ?: 'km');

			$v = array(
				'yr'  => ((int)$r['yr']) ?: '&mdash;',
				'mlg' => number_format((float)$r['mlg']).' '.$mUnit,
				'fl'  => $mapVal('fl',  $r['fl']),
				'tra' => $mapVal('tra', $r['tra']),
				'vol' => ((int)$r['vol'] > 0) ? ((int)$r['vol']).' '.(isset($unit['cm3']) ? $unit['cm3'] : 'cm3') : '&mdash;',
				'hp'  => ((int)$r['hp'] > 0) ? ((int)$r['hp']).' '.(isset($unit['hp']) ? $unit['hp'] : 'hp') : '&mdash;',
				'sts' => ((int)$r['sts'] > 0) ? (int)$r['sts'] : '&mdash;',
				'wd'  => $mapVal('wd',  $r['wd']),
				'clr' => $mapVal('clr', $r['clr']),
				'bt'  => $mapVal('bt',  $r['bt']),
				'prc' => '<span class="cmp-price">'.($prcVal>100 ? number_format($prcVal,0,',',' ').' &euro;' : '&mdash;').'</span>',
			);
			foreach ($order as $k) { $cells[$k] .= '<td>'.$v[$k].'</td>'; }
		}

		$out  = '<div class="cmp-wrap"><table class="cmp-table">';
		$out .= '<tr class="cmp-row-photo"><th></th>'.$c_photo.'</tr>';
		$out .= '<tr class="cmp-row-name"><th></th>'.$c_name.'</tr>';
		foreach ($order as $k) {
			$out .= '<tr'.($k==='prc' ? ' class="cmp-row-price"' : '').'><th>'.$esc($labels[$k]).'</th>'.$cells[$k].'</tr>';
		}
		$out .= '</table></div>';
		return $out;
	}
}
