<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

$car_card = function ($v1='', $lmt='4', $zreq=null, $stts='av') use (&$prefx, &$db, &$img_frmt, &$lng){
	/** @var PDO $db */
	$ar = [ 'ids'=>[], 'txt'=>'', 'qu'=>0 ];
	$debug_enabled = false; // Disable debugging
	
	// Debug disabled
	
	$specs_arr = ['yr'=>0, 'vol'=>0, 'fl'=>1, 'tra'=>1, 'mlg'=>0];
	$f_arr = ['bt'=>0, 'gr'=>0, 'br'=>0, 'mo'=>0, 'yr'=>1, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'clr'=>0, 'mlg'=>1, 'vol'=>1, 'sts'=>1, 'prc'=>1];
	
	$query_args = ['lmt'=>$lmt];
	$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE (catalog_type = "in_stock" OR catalog_type IS NULL)';
	
	// For filter searches, show all cars regardless of catalog_type
	if ($v1=='fltr') {
		$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1';
	}
	
	// For similar cars, show both in_stock and on_order cars
	if ($v1=='smlr') {
		$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE (catalog_type = "in_stock" OR catalog_type = "on_order" OR catalog_type IS NULL)';
	}
	
	if ($v1=='new'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
	elseif ($v1=='archive'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
	elseif ($v1=='top'){ $sql .= ' AND `vis`="1" AND `act`="1" AND `top`="1" '; }
	elseif ($v1=='smlr'){ 
		if ($zreq!==null){
			$sql .= ' AND `act`="1" AND `n_a`="0" AND (`prc` BETWEEN :prc_min AND :prc_max ) AND `id`<>:prc_id ';
			$query_args['prc_min'] = (($zreq['prc']*1)-1000);
			$query_args['prc_max'] = (($zreq['prc']*1)+1000);
			$query_args['prc_id'] = $zreq['id'];
		}
		$sql .= ' ORDER BY RAND() DESC LIMIT :lmt';
	}
	elseif ($v1=='fltr'){
		if ($zreq!==null){
			// Debug disabled
			// file_put_contents('debug_sql.log', "\n--------------------\n");
			// file_put_contents('debug_sql.log', "zreq: " . print_r($zreq, true) . "\n", FILE_APPEND);

			// Clean up parameters - remove query string from values
			foreach ($zreq as $k => $v) {
				if (is_string($v)) {
					$zreq[$k] = explode('?', $v)[0];
				}
			}
			// file_put_contents('debug_sql.log', "Cleaned parameters: " . print_r($zreq, true) . "\n", FILE_APPEND);

			// Handle brand and model separately
			if (!empty($zreq['br'])) {
				// Check what's in the database for this brand
				$check_sql = "SELECT DISTINCT br, br_nm, mo, mo_nm, bt FROM {$prefx}_car_ctlg WHERE br = :br AND vis='1' AND act='1'";
				$check_stmt = $db->prepare($check_sql);
				$check_stmt->execute(['br' => $zreq['br']]);
				$brand_data = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
				// file_put_contents('debug_sql.log', "\nAvailable cars for brand {$zreq['br']}:\n" . print_r($brand_data, true) . "\n", FILE_APPEND);

				$sql .= ' AND `br` = :br';
				$query_args['br'] = $zreq['br'];
				// file_put_contents('debug_sql.log', "Added brand filter: br = {$zreq['br']}\n", FILE_APPEND);
			}
			
			// Only add model filter if it's not empty and not a query string
			if (!empty($zreq['mo']) && strpos($zreq['mo'], '?') === false) {
				$sql .= ' AND `mo` = :mo';
				$query_args['mo'] = $zreq['mo'];
				// file_put_contents('debug_sql.log', "Added model filter: mo = {$zreq['mo']}\n", FILE_APPEND);
			}
			
			// Add body type filter if present
			if (!empty($zreq['bt'])) {
				$sql .= ' AND `bt` = :bt';
				$query_args['bt'] = $zreq['bt'];
				// file_put_contents('debug_sql.log', "Added body type filter: bt = {$zreq['bt']}\n", FILE_APPEND);
			}
			
			// Add color filter if present
			if (!empty($zreq['clr'])) {
				$sql .= ' AND `clr` = :clr';
				$query_args['clr'] = $zreq['clr'];
				// file_put_contents('debug_sql.log', "Added color filter: clr = {$zreq['clr']}\n", FILE_APPEND);
			}
			
			// Add transmission filter if present
			if (!empty($zreq['tra'])) {
				$sql .= ' AND `tra` = :tra';
				$query_args['tra'] = $zreq['tra'];
				// file_put_contents('debug_sql.log', "Added transmission filter: tra = {$zreq['tra']}\n", FILE_APPEND);
			}
			
			// Add fuel type filter if present
			if (!empty($zreq['fl'])) {
				$sql .= ' AND `fl` = :fl';
				$query_args['fl'] = $zreq['fl'];
				// file_put_contents('debug_sql.log', "Added fuel type filter: fl = {$zreq['fl']}\n", FILE_APPEND);
			}
			
			// Add drivetrain filter if present
			if (!empty($zreq['wd'])) {
				$sql .= ' AND `wd` = :wd';
				$query_args['wd'] = $zreq['wd'];
				// file_put_contents('debug_sql.log', "Added drivetrain filter: wd = {$zreq['wd']}\n", FILE_APPEND);
			}
			
			// Handle year range filter
			if (!empty($zreq['yr'])) {
				$range = explode('-', $zreq['yr']);
				if (count($range) == 2) {
					if ($range[0] == 'x') {
						// Less than max
						$sql .= " AND `yr` <= :yr_max";
						$query_args['yr_max'] = (int)$range[1];
						// file_put_contents('debug_sql.log', "Added year max filter: yr <= {$range[1]}\n", FILE_APPEND);
					} elseif ($range[1] == 'x') {
						// Greater than min
						$sql .= " AND `yr` >= :yr_min";
						$query_args['yr_min'] = (int)$range[0];
						// file_put_contents('debug_sql.log', "Added year min filter: yr >= {$range[0]}\n", FILE_APPEND);
					} else {
						// Between min and max
						$sql .= " AND `yr` BETWEEN :yr_min AND :yr_max";
						$query_args['yr_min'] = (int)min($range);
						$query_args['yr_max'] = (int)max($range);
						// file_put_contents('debug_sql.log', "Added year range filter: yr BETWEEN {$query_args['yr_min']} AND {$query_args['yr_max']}\n", FILE_APPEND);
					}
				} else {
					// Exact year
					$sql .= " AND `yr` = :yr";
					$query_args['yr'] = (int)$zreq['yr'];
					// file_put_contents('debug_sql.log', "Added exact year filter: yr = {$zreq['yr']}\n", FILE_APPEND);
				}
			}
			
			// Handle mileage range filter
			if (!empty($zreq['mlg'])) {
				$range = explode('-', $zreq['mlg']);
				if (count($range) == 2) {
					if ($range[0] == 'x') {
						// Less than max
						$sql .= " AND `mlg` <= :mlg_max";
						$query_args['mlg_max'] = (int)$range[1];
						// file_put_contents('debug_sql.log', "Added mileage max filter: mlg <= {$range[1]}\n", FILE_APPEND);
					} elseif ($range[1] == 'x') {
						// Greater than min
						$sql .= " AND `mlg` >= :mlg_min";
						$query_args['mlg_min'] = (int)$range[0];
						// file_put_contents('debug_sql.log', "Added mileage min filter: mlg >= {$range[0]}\n", FILE_APPEND);
					} else {
						// Between min and max
						$sql .= " AND `mlg` BETWEEN :mlg_min AND :mlg_max";
						$query_args['mlg_min'] = (int)min($range);
						$query_args['mlg_max'] = (int)max($range);
						// file_put_contents('debug_sql.log', "Added mileage range filter: mlg BETWEEN {$query_args['mlg_min']} AND {$query_args['mlg_max']}\n", FILE_APPEND);
					}
				} else {
					// Exact mileage
					$sql .= " AND `mlg` = :mlg";
					$query_args['mlg'] = (int)$zreq['mlg'];
					// file_put_contents('debug_sql.log', "Added exact mileage filter: mlg = {$zreq['mlg']}\n", FILE_APPEND);
				}
			}
			
			// Handle price range filter
			if (!empty($zreq['prc'])) {
				$range = explode('-', $zreq['prc']);
				if (count($range) == 2) {
					if ($range[0] == 'x') {
						// Less than max
						$sql .= " AND `prc` <= :prc_max";
						$query_args['prc_max'] = (int)$range[1];
						// file_put_contents('debug_sql.log', "Added price max filter: prc <= {$range[1]}\n", FILE_APPEND);
					} elseif ($range[1] == 'x') {
						// Greater than min
						$sql .= " AND `prc` >= :prc_min";
						$query_args['prc_min'] = (int)$range[0];
						// file_put_contents('debug_sql.log', "Added price min filter: prc >= {$range[0]}\n", FILE_APPEND);
					} else {
						// Between min and max
						$sql .= " AND `prc` BETWEEN :prc_min AND :prc_max";
						$query_args['prc_min'] = (int)min($range);
						$query_args['prc_max'] = (int)max($range);
						// file_put_contents('debug_sql.log', "Added price range filter: prc BETWEEN {$query_args['prc_min']} AND {$query_args['prc_max']}\n", FILE_APPEND);
					}
				} else {
					// Exact price
					$sql .= " AND `prc` = :prc";
					$query_args['prc'] = (int)$zreq['prc'];
					// file_put_contents('debug_sql.log', "Added exact price filter: prc = {$zreq['prc']}\n", FILE_APPEND);
				}
			}
			
			// Handle engine volume/capacity range filter
			if (!empty($zreq['vol'])) {
				$range = explode('-', $zreq['vol']);
				if (count($range) == 2) {
					if ($range[0] == 'x') {
						// Less than max
						$sql .= " AND `vol` <= :vol_max";
						$query_args['vol_max'] = (int)$range[1];
						// file_put_contents('debug_sql.log', "Added volume max filter: vol <= {$range[1]}\n", FILE_APPEND);
					} elseif ($range[1] == 'x') {
						// Greater than min
						$sql .= " AND `vol` >= :vol_min";
						$query_args['vol_min'] = (int)$range[0];
						// file_put_contents('debug_sql.log', "Added volume min filter: vol >= {$range[0]}\n", FILE_APPEND);
					} else {
						// Between min and max
						$sql .= " AND `vol` BETWEEN :vol_min AND :vol_max";
						$query_args['vol_min'] = (int)min($range);
						$query_args['vol_max'] = (int)max($range);
						// file_put_contents('debug_sql.log', "Added volume range filter: vol BETWEEN {$query_args['vol_min']} AND {$query_args['vol_max']}\n", FILE_APPEND);
					}
				} else {
					// Exact volume
					$sql .= " AND `vol` = :vol";
					$query_args['vol'] = (int)$zreq['vol'];
					// file_put_contents('debug_sql.log', "Added exact volume filter: vol = {$zreq['vol']}\n", FILE_APPEND);
				}
			}


			// Handle other filter parameters (legacy approach for select fields)
			$processed_filters = ['tg', 'br', 'mo', 'bt', 'clr', 'tra', 'fl', 'wd', 'yr', 'mlg', 'vol', 'prc', 'loc', 'sts'];
			foreach($zreq as $k => $v){
				// Skip parameters already processed
				if (in_array($k, $processed_filters)){continue;}
				
				if ( isset( $f_arr[$k] ) ){
					if ($f_arr[$k]=='1'){//inp
						if (is_array($v)) {
							// Handle array input (for range values)
							if (count($v) >= 2 && $v[0] !== '' && $v[1] !== '') {
								$sql .= ' AND (`'.$k.'` BETWEEN :'.$k.'_min AND :'.$k.'_max )';
								$query_args[$k.'_min'] = min($v[0], $v[1]);
								$query_args[$k.'_max'] = max($v[0], $v[1]);
							} elseif (count($v) >= 1 && $v[0] !== '') {
								$sql .= ' AND `'.$k.'` >= :'.$k.'_min';
								$query_args[$k.'_min'] = $v[0];
							}
						} else {
							// Handle string input (for hyphen-separated values)
							$btwn = explode("-", $v);
							if (count($btwn) >= 2) {
								if ($btwn[0] == 'x'){ 
									$sql .= ' AND `'.$k.'`<:'.$k.''; 
									$query_args[$k] = $btwn[1]; 
								} elseif($btwn[1] == 'x'){ 
									$sql .= ' AND `'.$k.'`>:'.$k.''; 
									$query_args[$k] = $btwn[0]; 
								} else { 
									$sql .= ' AND (`'.$k.'` BETWEEN :'.$k.'_min AND :'.$k.'_max )'; 
									$query_args[$k.'_min'] = min($btwn); 
									$query_args[$k.'_max'] = max($btwn); 
								}
							} elseif ($v !== '') {
								$sql .= ' AND `'.$k.'` = :'.$k;
								$query_args[$k] = $v;
							}
						}
					} elseif ($f_arr[$k]==0){//sel
						if ($v !== '') {
							$v_arr = explode("-", $v);
							if (count($v_arr) > 0) {
								$v_gr = ''; 
								$i=0; 
								foreach($v_arr as $it){ 
									if ($it !== '') {
										$v_gr .= ($v_gr ? "," : "").':'.$k.$i; 
										$query_args[$k.$i] = $it; 
										$i++; 
									}
								}
								if ($v_gr !== '') {
									$sql .= ' AND (`'.$k.'` IN ('.$v_gr.') )';
								}
							}
						}
					}
				}
			}
			
			// Handle location filter
			if (!empty($zreq['loc'])) {
				$sql .= " AND `loc` = :loc";
				$query_args['loc'] = $zreq['loc'];
			}
			
			// Handle status filter
			if (!empty($zreq['sts'])) {
				$sql .= " AND `sts` = :sts";
				$query_args['sts'] = $zreq['sts'];
			}
		}
		if ($stts=='av'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
		else if ($stts=='na'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
	}
	elseif ($v1=='smpl'){
		if ( isset($zreq['v']) ){
			if ($zreq['v'] == 'gD2ksAsmc5L' ){
				$sql .= ' AND `mo` IN ("qashqai", "kadjar", "kuga") AND `vis`="1" AND `act`="1" AND `n_a`="0" ';
			}else{$sql .= ' AND 2=1 '; $query_args = ['lmt'=>0];}
		}
	}
	
	if ($v1=='fltr'){ 
		$sql .= ' ORDER BY CASE WHEN catalog_type = "in_stock" AND n_a = 0 THEN 1 WHEN catalog_type = "on_order" THEN 2 ELSE 3 END, `id` DESC LIMIT :lmt '; 
		// file_put_contents('debug_sql.log', "SQL: " . $sql . "\n", FILE_APPEND);
		// file_put_contents('debug_sql.log', "Params: " . print_r($query_args, true) . "\n", FILE_APPEND);
	} elseif ($v1!='smlr'){ 
		$sql .= ' ORDER BY `n_a` ASC, `id` DESC LIMIT :lmt '; 
	}
	
	// Debug info disabled

	try {
		$pdo = $db->prepare($sql);
		
		// All debugging information has been disabled

		$pdo->execute($query_args);
		$results = $pdo->fetchAll(PDO::FETCH_ASSOC);
		
		if ($debug_enabled) {
			$ar['txt'] .= "\nSQL returned " . count($results) . " rows.\n";
			if (count($results) > 0) {
				$ar['txt'] .= "First result:\n" . print_r($results[0], true) . "\n";
			} else {
				// Get some sample data to see what's in the database
				$sample_sql = "SELECT DISTINCT br, mo FROM {$prefx}_car_ctlg WHERE vis='1' AND act='1' LIMIT 5";
				$sample_stmt = $db->query($sample_sql);
				$sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
				$ar['txt'] .= "\nNo results found. Here are some sample entries from database:\n" . print_r($sample_data, true) . "\n";
			}
			if (count($results) == 0) {
				// Get the last SQL error if any
				$error = $pdo->errorInfo();
				$ar['txt'] .= "SQL Error Info: " . print_r($error, true) . "\n";
				
				// Check what values exist in the database
				$check_sql = "SELECT DISTINCT br, br_nm, mo, mo_nm FROM {$prefx}_car_ctlg WHERE vis='1' AND act='1' LIMIT 10";
				$check_stmt = $db->query($check_sql);
				$sample_data = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
				$ar['txt'] .= "\nSample data from database (first 10 rows):\n" . print_r($sample_data, true) . "\n";
				
				// Show what we're searching for
				$ar['txt'] .= "\nSearching for:\n";
				$ar['txt'] .= "Brand (br): " . (isset($query_args['br']) ? $query_args['br'] : 'not set') . "\n";
				$ar['txt'] .= "Brand Name (br_nm): " . (isset($query_args['br_name']) ? $query_args['br_name'] : 'not set') . "\n";
				$ar['txt'] .= "Model (mo): " . (isset($query_args['mo']) ? $query_args['mo'] : 'not set') . "\n";
				$ar['txt'] .= "Model Name (mo_nm): " . (isset($query_args['mo_name']) ? $query_args['mo_name'] : 'not set') . "\n";
			}
		}
		if ($debug_enabled) {
			$ar['txt'] .= "Number of results: " . count($results) . "\n";
		}
		
		$i=0;
		foreach ($results as $r){
			if ($debug_enabled) {
				$ar['txt'] .= "Processing row: " . print_r($r, true) . "\n";
			}
			$ar['ids'][] = 'c'.$r['id']; $ar['br'] = $r['br_nm']; $ar['mo'] = $r['mo_nm']; $ar['qu']++;
		}
	} catch (PDOException $e) {
		if ($debug_enabled) {
			$ar['txt'] .= "SQL Error: " . $e->getMessage() . "\n";
		}
	}

	// Close debug section
	if ($debug_enabled) {
		$ar['txt'] .= "</pre>\n";
	}
	
	$card_counter = 0;
	
	foreach ($results as $r) {
		// Check if mobile - simple detection
		$is_mobile = (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']));
		
		if ($is_mobile) {
			// Mobile: Get images for slider (limited to 5 for better mobile performance)
			$pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `main` DESC, `pos` ASC LIMIT 5'); 
			$pdo2->execute([ 'it_id'=>$r['id'] ]); 
			$all_images = $pdo2->fetchAll(PDO::FETCH_ASSOC);
			
			// Ensure maximum 5 images for mobile performance
			if (count($all_images) > 5) {
				$all_images = array_slice($all_images, 0, 5);
			}
			
			if (count($all_images) > 1) {
				// Multiple images - create slider HTML with lazy loading
				$image_html = '<div class="mobile-card-slider" data-lazy-load="pending"><div class="mobile-card-slider__container"><div class="mobile-card-slider__track">';
				foreach ($all_images as $idx => $img) {
					// For order cars (catalog_type = 'on_order'), use .jpg extension instead of $img_frmt
					$image_extension = (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
					$img_src = '/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/'.$img['name'].$image_extension;
					// First image: load immediately with lazy loading
					// Other images: use data-src for deferred loading
					if ($idx === 0) {
						$image_html .= '<div class="mobile-card-slider__slide"><img src="'.$img_src.'" loading="lazy" width="300" height="200" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' photo '.($idx+1).'" /></div>';
					} else {
						$image_html .= '<div class="mobile-card-slider__slide"><img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f0f0f0\'/%3E%3C/svg%3E" data-src="'.$img_src.'" loading="lazy" width="300" height="200" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' photo '.($idx+1).'" /></div>';
					}
				}
				$image_html .= '</div>';
				$image_html .= '<div class="mobile-card-slider__line-indicator"></div>';
				$image_html .= '</div></div>';
			} else {
				// Single image - normal display
				$p = $all_images[0] ?? null;
				$p_src = isset($p['name']) ? '/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/' : '/'._SITE_IMG.'/v2/';
				// For order cars (catalog_type = 'on_order'), use .jpg extension instead of $img_frmt
				$image_extension = (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
				$p_name = isset($p['name']) ? $p['name'].$image_extension : 'no_image.svg';
				$image_html = '<img src="'.$p_src.$p_name.'" loading="lazy" width="300" height="200" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' main photo" />';
			}
		} else {
			// Desktop: Single image as before
			$pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1'); 
			$pdo2->execute([ 'it_id'=>$r['id'] ]); 
			$p = $pdo2->fetch();
			
			$p_src = isset($p['name']) ? '/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/' : '/'._SITE_IMG.'/v2/';
			// For order cars (catalog_type = 'on_order'), use .jpg extension instead of $img_frmt
			$image_extension = (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
			$p_name = isset($p['name']) ? $p['name'].$image_extension : 'no_image.svg';
			$image_html = '<img src="'.$p_src.$p_name.'" loading="lazy" width="300" height="200" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' main photo" />';
		}
		
		$z_stat = '';
		if ( $r['n_a']==0 && $r['act']==1 ){
			$z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '';
			$z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
			$z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
			$z_stat .= ($r['tva']==1) ? '<div class="stat top1">'.$lng['l']['stat']['vat'].'</div>' : '';
			$z_stat .= ($r['gift']==1) ? '<div class="stat gift">+ '.$lng['l']['stat']['gift'].'</div>' : '';
		}else{
			$z_stat .= '<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>';
		}

			// Generate correct URL based on catalog_type
			$page_type = (isset($r['catalog_type']) && $r['catalog_type'] == 'on_order') ? 'ordercars' : 'cars';
			
			// Prepare compact info display
			$year = $r['yr'];
			$fuel = isset($lng['l']['car']['fl'][$r['fl']]) ? $lng['l']['car']['fl'][$r['fl']] : $r['fl'];
			$transmission = isset($lng['l']['car']['tra'][$r['tra']]) ? $lng['l']['car']['tra'][$r['tra']] : $r['tra'];
			$volume = $r['vol'].' '.$lng['l']['unit']['cm3'];
			$mileage = number_format($r['mlg']).' '.( isset($lng['l']['unit'][ $r['unit'] ]) ? $lng['l']['unit'][ $r['unit'] ] : $r['unit'] );
		
			// Calculate price before displaying
			if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
				$prc = number_format($r['prc_n'], 0, ',', ' ');
				$o_prc = number_format($r['prc'], 0, ',', ' ');
				$o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.$o_prc.'</span> &#8364;</span>';
			}else{
				$prc = number_format($r['prc'], 0, ',', ' ');
				$o_prc = 0;
				$o_prc_bl = '';
			}
		
			$ar['txt'] .= '
			<a class="it car" href="/'.$_COOKIE['lang'].'/'.$page_type.'/'.$r['id'].'">
				<div class="name">'.$r['br_nm'].' '.$r['mo_nm'].'</div>
				<div class="compact-info">
					<div class="line1">'.$year.' | '.$fuel.' | '.$volume.'</div>
					<div class="line2">'.$transmission.' | '.$mileage.'</div>
				</div>
				'.$image_html.'
				<div class="prc">
					<strong class="val">'.($r['prc'] > 100 ? $prc.' &#8364;' : $lng['w']['negociabil']).'</strong>'.$o_prc_bl.'
					<span class="stock-status'.($r['catalog_type'] == 'on_order' ? ' on-order' : '').'">'.($r['n_a'] == '1' ? $lng['w']['not_available'] : ($r['catalog_type'] == 'on_order' ? $lng['w']['on_order'] : $lng['w']['in_stock'])).'</span>
				</div>
				<div class="txt">';
				
				//$ar['txt'] .= '<div class="status">'.$z_stat.'</div>';
				//$ar['txt'] .= '<div class="id">ID-'.$r['id'].'</div>';
				$ar['txt'] .= '
				<div class="specs">';
					
					// Add monthly payment after the specs
					if($r['prc'] > 100) {
						$monthly_payment = floor($r['prc'] * (9.2/1200) / (1 - pow(1 + (9.2/1200), -60)));
						$ar['txt'] .= '
						<p class="ar">
							<span class="name">'.(isset($lng['w']['monthly_payment']) ? $lng['w']['monthly_payment'] : 'Plată lunară').'</span>
							<span class="space"></span>
							<span class="val">'.(isset($lng['w']['from']) ? $lng['w']['from'] : 'de la').' <span style="color: #ff0000; font-weight: bold;">'.$monthly_payment.'</span> €</span>
						</p>';
					}
					
					// Calculate price before displaying
					if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
						$prc = number_format($r['prc_n'], 0, ',', ' ');
						$o_prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.$o_prc.'</span> &#8364;</span>';
					}else{
						$prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc = 0;
						$o_prc_bl = '';
					}
					
					// Add import country to specs if available
					if (!empty($r['import_country_id'])) {
						$country_name = '';
						$country_code = '';
						
						// Determine language column based on current language
						$langColumn = 'name_ro'; // Default to Romanian
						if (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'ru') {
							$langColumn = 'name_ru';
							$country_label = 'Страна импорта';
						} elseif (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'en') {
							$langColumn = 'name_en';
							$country_label = 'Import country';
						} else {
							$country_label = 'Țara de import';
						}
						
						try {
							$stmt = $db->prepare("SELECT {$langColumn}, code FROM countries WHERE id = :id LIMIT 1");
							$stmt->execute(['id' => $r['import_country_id']]);
							$result = $stmt->fetch(PDO::FETCH_ASSOC);
							if ($result) {
								$country_name = isset($result[$langColumn]) ? $result[$langColumn] : '';
								$country_code = isset($result['code']) ? strtolower($result['code']) : '';
							}
						} catch (Exception $e) {
							// Silent error handling
						}
						
						if (!empty($country_name)) {
							$ar['txt'] .= '
							<p class="ar">
								<span class="name">'.$country_label.'</span>
								<span class="space"></span>
								<span class="val">'.$country_name.'</span>
							</p>';
							
							// Flag container - will include button for mobile
							if (!empty($country_code)) {
								if ($is_mobile && $v1 !== 'smlr') {
									// Mobile: Add button text for language
									$details_text = 'Vezi detalii';
									if (isset($_COOKIE['lang'])) {
										if ($_COOKIE['lang'] == 'ru') {
											$details_text = 'Подробности';
										} elseif ($_COOKIE['lang'] == 'en') {
											$details_text = 'View details';
										}
									}
									$ar['txt'] .= '
							<div class="mobile-details-with-flag" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding: 0; border: none;">
								<div class="mobile-details-button">'.$details_text.'</div>';
								} else {
									$ar['txt'] .= '
							<div style="text-align: right; margin-right:-3px; margin-top: -8px; padding: 0; border: none;">';
								}
								$ar['txt'] .= '<img src="/media/images/flags/'.$country_code.'.svg" alt="'.$country_name.' flag" style="width: 36px; height: 30px; border: none; padding: 0;">';
								$ar['txt'] .= '</div>';
							}
							
							// Set smaller margin when import country exists
							$price_margin_style = 'margin-top: -30px;';
						}
					} else {
						// Set larger margin when no import country
						$price_margin_style = 'margin-top: 40px;';
					}
					
				$ar['txt'] .= '
				</div>';
				
				// Add mobile "Vezi detalii" button for cars without flag (but not for similar cars grid)
				if ($is_mobile && empty($r['import_country_id']) && $v1 !== 'smlr') {
					$details_text = 'Vezi detalii';
					if (isset($_COOKIE['lang'])) {
						if ($_COOKIE['lang'] == 'ru') {
							$details_text = 'Подробности';
						} elseif ($_COOKIE['lang'] == 'en') {
							$details_text = 'View details';
						}
					}
					
					// Without flag - button centered at bottom
					$ar['txt'] .= '<div class="mobile-details-no-flag">';
					$ar['txt'] .= '<div class="mobile-details-button">'.$details_text.'</div>';
					$ar['txt'] .= '</div>';
				}
				
				$ar['txt'] .= '
			</div>
		</a>';
		
		$i++;
		$card_counter++;
		
		if ($card_counter % 16 == 0 && $card_counter > 0) {
			// On /cars page show hint about ordercars, on /ordercars show hint about cars
			$is_order_page = (strpos($_SERVER['REQUEST_URI'], '/ordercars') !== false);
			$hint_text = $is_order_page ? $lng['w']['in_stock_hint'] : $lng['w']['on_order_hint'];
			$hint_link = $is_order_page ? '/'.$_COOKIE['lang'].'/cars' : '/'.$_COOKIE['lang'].'/ordercars';
			$hint_link_text = $is_order_page ? $lng['w']['in_stock'] : $lng['w']['on_order'];
			
			$ar['txt'] .= '<div class="catalog-hint-block" style="width: 100%; padding: 20px; margin: 15px 0; background-color: #f8f9fa; border-left: 4px solid #ff0000; border-radius: 4px; box-sizing: border-box;">';
			$ar['txt'] .= '<p style="margin: 0 0 10px 0; color: #333; font-size: 15px;">'.$hint_text.'</p>';
			$ar['txt'] .= '<a href="'.$hint_link.'" style="display: inline-block; background-color: #ff0000; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">'.$hint_link_text.'</a>';
			$ar['txt'] .= '</div>';
		}
	}
	if ($i==0){$ar['txt'] .= '<div class="empty">'.$lng['t']['x']['no_offers'].'</div>';}
	
	return $ar;
};

function tyre_card($prefx, $db, $img_frmt, $lng, $v1='', $lmt='4', $zreq=null){
	$ar = [ 'ids'=>[], 'txt'=>'', 'qu'=>0 ];
	$specs_arr = ['w'=>0, 'h'=>0, 'd'=>0, 'ss'=>1];
	$f_arr = ['w'=>0, 'h'=>0, 'd'=>0, 'c'=>0, 'ss'=>0, 'br'=>0, 'mo'=>0, 'prc'=>1];
	
	$query_args = ['lmt'=>$lmt];
	$sql = 'SELECT * FROM '.$prefx.'_tyre_ctlg WHERE 1=1 ';
	
	if ($v1=='new'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
	elseif ($v1=='archive'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
	elseif ($v1=='smlr'){ 
		if ($zreq!==null){
			//$sql .= ' AND (`prc` BETWEEN :prc_min AND :prc_max ) AND `id`<>:prc_id '; $query_args['prc_min'] = (($zreq['prc']*1)-1000); $query_args['prc_max'] = (($zreq['prc']*1)+1000); $query_args['prc_id'] = $zreq['id'];
			
			$sql .= ' AND `w`=:w AND `id`<>:id ';
			$query_args['w'] = $zreq['w']; $query_args['id'] = $zreq['id'];
		}
		$sql .= ' ORDER BY RAND() DESC LIMIT :lmt';
	}
	elseif ($v1=='fltr'){
		if ($zreq!==null){
			foreach($zreq as $k => $v){
				if ($k=='tg'){continue;}
				
				if ( isset( $f_arr[$k] ) ){
					if ($f_arr[$k]=='1'){//inp
						$btwn = explode("-", $v );
						if ($btwn[0]=='x'){ $sql .= ' AND `'.$k.'`<:'.$k.''; $query_args[$k] = $btwn[1]; }//lower
						elseif($btwn[1]=='x'){ $sql .= ' AND `'.$k.'`>:'.$k.''; $query_args[$k] = $btwn[0]; }//higher
						else{ $sql .= ' AND (`'.$k.'` BETWEEN :'.$k.'_min AND :'.$k.'_max )'; $query_args[$k.'_min'] = min($btwn); $query_args[$k.'_max'] = max($btwn); }//between
					} elseif ($f_arr[$k]==0){//sel
						$v_gr = ''; $i=0; $v = explode("-", $v );
						foreach($v as $it){ $v_gr .= ($v_gr ? "," : "").':'.$k.$i; $query_args[$k.$i] = $it; $i++; }
						$sql .= ' AND (`'.$k.'` IN ('.$v_gr.') )';
					}
				}
			}
		}
		$sql .= ' AND `vis`="1" AND `act`="1" ';
	}
	
	if ($v1!='smlr'){ $sql .= ' ORDER BY `n_a` ASC, `new` DESC, `d` DESC LIMIT :lmt '; /*`id` DESC, `n_a` DESC*/ }
	
	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);
	$i=0;
	foreach ($pdo as $r){
		$ar['ids'][] = $r['id']; $ar['br'] = $r['br']; $ar['mo'] = $r['mo']; $ar['qu']++;
		$pdo = $db->prepare('SELECT `name`, `ff` FROM '.$prefx.'_tyre_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1'); 
		$pdo->execute([ 'it_id'=>$r['id'] ]); 
		$p = $pdo->fetch();
		
		$p_src = isset($p['name']) ? '/'._TYRES_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/' : '/'._SITE_IMG.'/v2/';
		$p_name = isset($p['name']) ? $p['name'].($p['ff']!=''?'.'.$p['ff']:$img_frmt) : 'no_image.svg' ;
		
		$z_stat = '';
		if ( $r['n_a']==0 && $r['act']==1 ){
			$z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '<div class="stat n_a0">'.$lng['l']['stat']['n_a0'].'</div>';
			//$z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
			//$z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
		}else{
			$z_stat .= '<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>';
		}
		
		// Convert brand to URL-friendly format with hyphens
		$brand_url = str_replace('_', '-', $r['br']);

		$ar['txt'] .= '
		<a class="it tyre" href="/'.$_COOKIE['lang'].'/tyres/'.$r['id'].'">
			<div class="crdt">'.$lng['w']['credit'].' 0%</div>
			<img src="'.$p_src.$p_name.'" alt="tyre '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' main photo" />
			<div class="txt">
				<div class="status">'.$z_stat.'</div>
				<div class="name">'.$r['br_nm'].' '.$r['mo_nm'].'</div>';
				
				$ar['txt'] .= '
				<div class="specs">';
					
					foreach ($specs_arr as $k => $v){
						//$unit =  isset( $f_it_xtd_arr['tyre'][$k]['unit'] ) ? $f_it_xtd_arr['tyre'][$k]['unit'] : ''; на данный момент не подключена переменная
						if ($k=='w'){$unit = ' mm';}elseif($k=='h'){$unit = '%';}elseif($k=='d'){$unit = '”';}else{$unit = '';}
						
						$v1 = ( $v===1&&isset($lng['l']['tyre']['ss'][$r[$k]]) ) ? $lng['l']['tyre']['ss'][$r[$k]] : $r[$k];
						
						$ar['txt'] .= '
						<p class="ar">
							<span class="name">'.$lng['l']['tyre']['spec'][$k].'</span>
							<span class="space"></span>
							<span class="val">'.$v1.$unit.'</span>
						</p>';
					}
					
					if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
						$prc = number_format($r['prc_n'], 0, ',', ' ');
						$o_prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.$o_prc.'</span> '.$r['cur'].'</span>';
					}else{
						$prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc = 0;
						$o_prc_bl = '';
					}
					
				$ar['txt'] .= '
				</div>
				
				<div class="prc"> <strong class="val">'.$prc.' '.$r['cur'].'</strong> '.$o_prc_bl.'</div>
			</div>
		</a>';
		$i++;
	}
	if ($i==0){$ar['txt'] .= '<div class="empty">'.$lng['t']['x']['no_offers'].'</div>';}
	
	return $ar;
}

/**
 * Generate image slider HTML for product cards
 * 
 * @param array $images - Array of image data
 * @param array $car_data - Car data
 * @param string $img_frmt - Image format extension
 * @return string - HTML for image slider
 */
function generateProductCardSliderHTML($images, $car_data, $img_frmt) {
    if (empty($images)) {
        // No images - show placeholder
        return '<img src="/'._SITE_IMG.'/v2/no_image.svg" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' no photo" />';
    }
    
    if (count($images) == 1) {
        // Single image - no slider needed
        $image = $images[0];
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        return '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' main photo" />';
    }
    
    // Multiple images - generate slider
    $html = '<div class="product-card-slider">';
    $html .= '<div class="product-card-slider__container">';
    $html .= '<div class="product-card-slider__track">';
    
    foreach ($images as $index => $image) {
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        $html .= '<div class="product-card-slider__slide">';
        $html .= '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' photo '.($index+1).'" />';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // close track
    
    // Navigation arrows
    $html .= '<button class="product-card-slider__nav product-card-slider__nav--prev">‹</button>';
    $html .= '<button class="product-card-slider__nav product-card-slider__nav--next">›</button>';
    
    // Dots indicator (only for 5 or fewer images)
    if (count($images) <= 5) {
        $html .= '<div class="product-card-slider__dots">';
        for ($i = 0; $i < count($images); $i++) {
            $active_class = $i === 0 ? ' product-card-slider__dot--active' : '';
            $html .= '<div class="product-card-slider__dot'.$active_class.'"></div>';
        }
        $html .= '</div>';
    }
    
    // Image counter
    $html .= '<div class="product-card-slider__counter">1/'.count($images).'</div>';
    
    $html .= '</div>'; // close container
    $html .= '</div>'; // close slider
    
    return $html;
}

/**
 * Generate mobile slider HTML for product cards
 * 
 * @param array $images - Array of image data
 * @param array $car_data - Car data
 * @param string $img_frmt - Image format extension
 * @return string - HTML for mobile slider
 */
function generateMobileSliderHTML($images, $car_data, $img_frmt) {
    if (empty($images)) {
        // No images - show placeholder
        return '<img src="/'._SITE_IMG.'/v2/no_image.svg" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' no photo" />';
    }
    
    if (count($images) == 1) {
        // Single image - no slider needed
        $image = $images[0];
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        return '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' main photo" />';
    }
    
    // Multiple images - generate mobile slider
    $html = '<div class="mobile-card-slider">';
    $html .= '<div class="mobile-card-slider__container">';
    $html .= '<div class="mobile-card-slider__track">';
    
    foreach ($images as $index => $image) {
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        $html .= '<div class="mobile-card-slider__slide">';
        $html .= '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' photo '.($index+1).'" />';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // close track
    
    // Line indicator
    $html .= '<div class="mobile-card-slider__line-indicator"></div>';
    
    $html .= '</div>'; // close container
    $html .= '</div>'; // close slider
    
    return $html;
}


/*
$fn_card = function ($gr='x', $v1='', $lmt='4', $zreq=null, $stts='av') use (&$prefx, &$db, &$img_frmt, &$lng){
	$ar = [ 'ids'=>[], 'txt'=>'', 'qu'=>0 ];
	
	if ($gr=='car'){
		$specs_arr = ['yr'=>0, 'vol'=>0, 'fl'=>1, 'tra'=>1, 'hp'=>0];
		$f_arr = ['bt'=>0, 'gr'=>0, 'br'=>0, 'mo'=>0, 'yr'=>1, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'clr'=>0, 'mlg'=>1, 'vol'=>1, 'sts'=>1, 'prc'=>1];
	} elseif ($gr=='tyre'){
		$specs_arr = ['br'=>0, 'w'=>0, 'h'=>0, 'd'=>0, 'ss'=>1];
		$f_arr = ['w'=>0, 'h'=>0, 'd'=>0, 'c'=>0, 'ss'=>0, 'br'=>0, 'prc'=>1];
	} else {return;}
	
	$query_args = ['lmt'=>$lmt];
	$sql = 'SELECT * FROM '.$prefx.'_'.$gr.'_ctlg WHERE 1=1 ';
	
	if ($v1=='new'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
	elseif ($v1=='archive'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
	elseif ($v1=='top'){ $sql .= ' AND `vis`="1" AND `act`="1" AND `top`="1" '; }
	elseif ($v1=='smlr'){ 
		if ($zreq!==null){
			if ($gr=='car'){
				$sql .= ' AND `act`="1" AND `n_a`="0" AND (`prc` BETWEEN :prc_min AND :prc_max ) AND `id`<>:prc_id ';
				$query_args['prc_min'] = (($zreq['prc']*1)-1000); $query_args['prc_max'] = (($zreq['prc']*1)+1000); $query_args['prc_id'] = $zreq['id'];
			} elseif ($gr=='tyre'){
				$sql .= ' AND `w`=:w AND `id`<>:id ';
				$query_args['w'] = $zreq['w']; $query_args['id'] = $zreq['id'];
			}
		}
		$sql .= ' ORDER BY RAND() DESC LIMIT :lmt';
	}
	elseif ($v1=='fltr'){
		if ($zreq!==null){
			foreach($zreq as $k => $v){
				if ($k=='tg'){continue;}
				
				if ( isset( $f_arr[$k] ) ){
					if ($f_arr[$k]=='1'){//inp
						$btwn = explode("-", $v );
						if ($btwn[0]=='x'){ $sql .= ' AND `'.$k.'`<:'.$k.''; $query_args[$k] = $btwn[1]; }//lower
						elseif($btwn[1]=='x'){ $sql .= ' AND `'.$k.'`>:'.$k.''; $query_args[$k] = $btwn[0]; }//higher
						else{ $sql .= ' AND (`'.$k.'` BETWEEN :'.$k.'_min AND :'.$k.'_max )'; $query_args[$k.'_min'] = min($btwn); $query_args[$k.'_max'] = max($btwn); }//between
					} elseif ($f_arr[$k]==0){//sel
						$v_gr = ''; $i=0; $v = explode("-", $v );
						foreach($v as $it){ $v_gr .= ($v_gr ? "," : "").':'.$k.$i; $query_args[$k.$i] = $it; $i++; }
						$sql .= ' AND (`'.$k.'` IN ('.$v_gr.') )';
					}
				}
			}
		}
		if ($stts=='av'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
		else if ($stts=='na'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
	}
	elseif ($v1=='smpl'){
		if ( isset($zreq['v']) ){
			if ($gr=='car'){
				if ( $zreq['v'] == 'gD2ksAsmc5L' ){
					$sql .= ' AND `mo` IN ("qashqai", "kadjar", "kuga") AND `vis`="1" AND `act`="1" AND `n_a`="0" ';
				}else{$sql .= ' AND 2=1 '; $query_args = ['lmt'=>0];}
			} elseif ($gr=='tyre'){
				
			}
		}
	}
	
	if ($v1!='smlr'){ $sql .= ' ORDER BY `n_a` ASC, `id` DESC LIMIT :lmt '; } //`id` DESC, `n_a` ASC
	
	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);
	$i=0;
	foreach ($pdo as $r){
		if ($gr=='car'){ $ar['ids'][] = 'c'.$r['id']; $ar['br'] = $r['br_nm']; $ar['mo'] = $r['mo_nm']; $ar['qu']++; }
		elseif ($gr=='tyre'){ $ar['ids'][] = 't'.$r['id']; $ar['br'] = $r['br']; $ar['qu']++; }
		
		$pdo = $db->prepare('SELECT `name` FROM '.$prefx.'_'.$gr.'_pht WHERE `it_id`=:id AND `main`="1" LIMIT 1'); //NEED TO CHANGE!!!!!!! index to id, id to it_id
		$pdo->execute(['id'=>$r['id']]); 
		$p = $pdo->fetch();
		
		$p_src = isset($p['name']) ? '/'.($gr=='car'?_CAR_IMG:_TYRES_IMG).'/'.$r['p_path'].'/'.$r['id'].'/med/' : '/'._SITE_IMG.'/v2/';
		$p_name = isset($p['name']) ? $p['name'].$img_frmt : 'no_image.svg' ;
		
		$z_stat = '';
		if ( $r['n_a']==0 && $r['act']==1 ){
			if ($gr=='car'){
				$z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '';
				$z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
				$z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
				$z_stat .= ($r['tva']==1) ? '<div class="stat top1">'.$lng['l']['stat']['vat'].'</div>' : '';
			} elseif ($gr=='tyre'){
				$z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '<div class="stat n_a0">'.$lng['l']['stat']['n_a0'].'</div>';
			}
		}else{
			$z_stat .= '<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>';
		}
		
		$ar['txt'] .= '
		<a class="it '.$gr.'" href="/'.$_COOKIE['lang'].'/'.$gr.'s/'.$r['id'].'">
			'.($gr=='tyre'?'<div class="crdt">'.$lng['w']['credit'].' 0%</div>':'').'
			<img src="'.$p_src.$p_name.'" alt="'.$gr.' '.($gr=='car' ? $r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'] : $r['w'].'/'.$r['h'].' R'.$r['d'].' id'.$r['id']).' main photo" />
			<div class="txt">
				<div class="status">'.$z_stat.'</div>
				<div class="name">'.($gr=='car' ? $r['br_nm'].' '.$r['mo_nm'] : '<span>'.$r['br'].',</span> <span>'.$r['w'].'/'.$r['h'].' R'.$r['d'].($r['c']==1?'C':'').'</span>').'</div>
				<div class="specs">';
					
					foreach ($specs_arr as $k => $v){
						if ($gr=='car'){
							$r[$k] = $k=='hp' ? $r[$k].' '.$lng['l']['unit']['hp'].' ('.round($r[$k]*0.735,0).' '.$lng['l']['unit']['kw'].')' : $r[$k];
							$r[$k] = $k=='vol' ? $r[$k].' '.$lng['l']['unit']['cm3'] : $r[$k];
							$unit = '';
						} elseif ($gr=='tyre'){
							$unit = $k=='w'?' mm':($k=='h'?'%':($k=='d'?'”':''));
						}
						$v1 = $gr=='car'
							? ($v===1&&isset($lng['l'][$gr][ ($gr=='car'?$k:'ss') ][$r[$k]]) ? $lng['l'][$gr][ ($gr=='car'?$k:'ss') ][$r[$k]] : $r[$k])
							: ($v===1&&isset($lng['l']['tyre']['ss'][$r[$k]]) ? $lng['l']['tyre']['ss'][$r[$k]] : $r[$k]);
						$ar['txt'] .= '
						<p class="ar">
							<span class="name">'.$lng['l'][$gr]['spec'][$k].'</span>
							<span class="space"></span>
							<span class="val">'.$v1.$unit.'</span>
						</p>';
					}
					
					if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
						$prc = number_format($r['prc_n'], 0, ',', ' ');
						$o_prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.$o_prc.'</span> '.$lng['l']['cur'][ $r['cur'] ].'</span>'; // &#8364;
					}else{
						$prc = number_format($r['prc'], 0, ',', ' ');
						$o_prc = 0;
						$o_prc_bl = '';
					}
					
				$ar['txt'] .= '
				</div>
				<div class="prc"> <strong class="val">'.$prc.' '.$lng['l']['cur'][ $r['cur'] ].'</strong> '.$o_prc_bl.'</div>
			</div>
		</a>';
		$i++;
	}
	if ($i==0){$ar['txt'] .= '<div class="empty">'.$lng['t']['x']['no_offers'].'</div>';}
	
	return $ar;
};
*/

?>
