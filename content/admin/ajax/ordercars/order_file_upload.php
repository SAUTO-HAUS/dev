<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Services\FileService;

$ihtml = 'tmp/index.html';
$zDir = _CAR_IMG;
$last_id = __post('last_id');
$zY = substr( md5( date('Y') ), 0, 4 );
$zM = substr( md5( date('m') ), 0, 4 );

// A car keeps ONE folder for its whole life. The gallery builds every photo URL
// from car_ctlg.p_path, so a photo added later must land in that same folder —
// writing it under the current month made it unreachable on any car created in
// an earlier month.
$pPath = $zY.'/'.$zM;
if ($last_id) {
    $pchk = $db->prepare('SELECT p_path FROM '.$prefx.'_car_ctlg WHERE id = :id');
    $pchk->execute(['id' => $last_id]);
    $existingPPath = trim((string)$pchk->fetchColumn(), '/');
    if ($existingPPath !== '' && strpos($existingPPath, '/') !== false) $pPath = $existingPPath;
}
[$zY, $zM] = explode('/', $pPath, 2);

foreach ($_FILES as $inp => $ar){
    $inp = rtrim($inp, '[]');

	$zp = '';
    $fldrs = [ $zDir, $zY, $zM, $last_id, $file_av_ar[$inp]['fldr'] ];

	foreach($fldrs as $k => $v) {
		if (is_array($v)) {
            foreach ($v as $v1 => $ar2) {
                $zp_t = $zp.'/'.$v1;
                if (!file_exists($zp_t)) {
                    mkdir($zp_t, 0755, true);
                    copy($ihtml, $zp_t . '/index.html');
                }
            }
        } else {
            $zp .= ($k==0) ? $v : '/'.$v;
            if (!file_exists($zp)) {
                mkdir($zp, 0755, true);
                copy($ihtml, $zp . '/index.html');
            }
        }
	}
    unset($zp, $zp_t, $fldrs, $ar2);

	$i=0;
    $changelog_photos_added = [];

    // Normalize to array (single file upload sends flat strings)
    if (!is_array($ar['name'])) {
        foreach (['name','type','tmp_name','error','size'] as $_fk) {
            $ar[$_fk] = [$ar[$_fk]];
        }
    }

    // pos_start is the file's index WITHIN this upload batch (the JS sends it so
    // parallel uploads don't race on MAX(pos)). It has to be added on top of what
    // the car already has — taken as an absolute position it restarted at 1 and
    // the new photo overwrote car_<id>_1, which is why an added photo appeared as
    // a duplicate of the first one.
    $pdoMax = $db->prepare('SELECT COALESCE(MAX(pos), 0) FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id');
    $pdoMax->execute(['it_id' => $last_id]);
    $existingMax = (int)$pdoMax->fetchColumn();

    $pos_start   = (int)__post('pos_start', 0);
    $pos_counter = $existingMax + ($pos_start > 0 ? $pos_start - 1 : 0);

	$fi_mime = finfo_open(FILEINFO_MIME_TYPE);
	foreach ($ar['name'] as $k => $nm) {
		$fi_path = $ar['tmp_name'][$k];
		$fi_tp = strtolower( finfo_file($fi_mime, $fi_path) );

		$tmp_f = 'tmp/'.uniqid('up_'.$last_id.'_', true).'.jpg';
		$path = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id;

		if ($inp=='img') {
            if (__post('main_img') !== null && __post('main_img') !== '') {
                $main_file = ((int)__post('main_img') === (int)$k) ? 1 : 0;
            } else {
                $main_file = 0;
            }

			$max_mb = 100*1024*1024;
			$size_cr = $file_av_ar[$inp]['fldr'];

			if (isset($size_cr['high']['sz']) && $size_cr['high']['sz'] > 1200) {
				$size_cr['high']['sz'] = 1200;
			}

			if ( $ar['error'][$k] !== UPLOAD_ERR_OK ) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Upload failed with error code ' . $ar['error'][$k];
                continue;
            }
			if ( getimagesize($ar['tmp_name'][$k]) === FALSE ) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Unable to determine image type of uploaded file';
                continue;
            }

			$allowed_jpeg_types = ['image/jpeg', 'image/jpg', 'image/pjpeg'];
			if (!in_array($fi_tp, $allowed_jpeg_types)) {
				$file_extension = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
				if (!in_array($file_extension, ['jpg', 'jpeg'])) {
					$rtrn .= ' | File #'.$i.': '.$nm.' - Doar fișiere JPEG acceptate.';
					continue;
				}
			}

			if( strlen($nm) ){
				if ( $ar['size'][$k] <= $max_mb ){
					$upload_status = move_uploaded_file($ar['tmp_name'][$k], $tmp_f);
					if($upload_status){
                        $pos_counter++;
                        $pos = $pos_counter;
                        $n_nm = 'car_'. $last_id .'_'. $pos;
						$imageResult = (new FileService())->createImagePreserveJpeg($tmp_f, $path, $n_nm, $size_cr);
						if($imageResult) {
							$pdo = $db->prepare('INSERT INTO '.$prefx.'_car_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`, `pos`)
							    VALUES (:it_id, :tp, :path, :name, :ff, :main, :pos)');
							$pdo->execute([
                                'it_id'=>$last_id,
                                'tp'=>$inp,
                                'path'=>$zY.'/'.$zM,
                                'name'=>$n_nm,
                                'ff'=>'jpg',
                                'main'=>$main_file,
                                'pos'=>$pos
                            ]);
                            $changelog_photos_added[] = $n_nm.'.jpg';
                            // Mirror into R2 so the photo survives the local cleanup.
                            foreach (array_keys($size_cr) as $_sz) {
                                \App\Services\CarPhotoR2::push($path.'/'.$_sz.'/'.$n_nm.'.jpg');
                            }
						} else {
							$rtrn .= ' | File #'.$i.': '.$nm.' - Image processing failed.';
						}
					} else {
                        $rtrn .= ' | File #'.$i.': '.$nm.' - Upload failed.';
                        continue;
                    }
				} else {
                    $rtrn .= ' | File #'.$i.': '.$nm.' - File upload size exceeded (Max '.$max_mb.'Mb)';
                    continue;
                }
			}

		} elseif ($inp=='doc') {
            $pos_counter++;
            $pos = $pos_counter;
            $n_nm = 'car_'. $last_id .'_'. $pos;
			$fNewPath = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id.'/'.$inp;
			if ( !file_exists($fNewPath) ) {
                mkdir($fNewPath, 0755, true);
                copy($ihtml, $fNewPath.'/index.html');
            }
			if ( !move_uploaded_file($fi_path, $fNewPath.'/'.$n_nm.'.pdf') ) {
                $rtrn .= 'Can\'t move file '.$nm.'.';
                continue;
            }
		}
		$i++;
	}
	finfo_close($fi_mime);
}

if (!empty($changelog_photos_added)) {
    car_changelog_log($db, $prefx, [
        'car_id' => $last_id,
        'action' => 'photo_add',
        'field_name' => 'photo',
        'old_value' => null,
        'new_value' => count($changelog_photos_added).' фото: '.implode(', ', $changelog_photos_added),
        'catalog_type' => 'ordercars'
    ]);
}

unset($ihtml);
