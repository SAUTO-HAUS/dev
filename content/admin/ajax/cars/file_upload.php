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

$fi_mime = finfo_open(FILEINFO_MIME_TYPE);

foreach ($_FILES as $_inp_raw => $ar){
    $inp = rtrim($_inp_raw, '[]');
    if (!isset($file_av_ar[$inp])) continue;

    // Create dirs once per field
    $base_path = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id;
    foreach ([$zDir, $zDir.'/'.$zY, $zDir.'/'.$zY.'/'.$zM, $base_path] as $d) {
        if (!file_exists($d)) { mkdir($d, 0755, true); @copy($ihtml, $d.'/index.html'); }
    }
    if (is_array($file_av_ar[$inp]['fldr'])) {
        foreach ($file_av_ar[$inp]['fldr'] as $sub => $unused) {
            $d = $base_path.'/'.$sub;
            if (!file_exists($d)) { mkdir($d, 0755, true); @copy($ihtml, $d.'/index.html'); }
        }
    }

    $changelog_photos_added = [];

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

    $pdoInsert = $db->prepare('INSERT INTO '.$prefx.'_car_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`, `pos`) VALUES (:it_id, :tp, :path, :name, :ff, :main, :pos)');

    $i = 0;
    foreach ($ar['name'] as $k => $nm) {
        if ($ar['error'][$k] !== UPLOAD_ERR_OK) {
            $rtrn .= ' | File #'.$i.': '.$nm.' - Error code '.$ar['error'][$k];
            continue;
        }

        $fi_path = $ar['tmp_name'][$k];
        $fi_tp   = strtolower(finfo_file($fi_mime, $fi_path));

        if ($inp == 'img') {
            $has_main_post = __post('main_img') !== null && __post('main_img') !== '';
            if ($has_main_post) {
                $main_file = ((int)__post('main_img') === $k) ? 1 : 0;
            } else {
                // No main selected — first photo in first batch becomes main if no main exists yet
                $chkMain = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1"');
                $chkMain->execute(['it_id' => $last_id]);
                $main_file = ($chkMain->fetchColumn() == 0 && $pos_counter == 1) ? 1 : 0;
            }
            $max_mb    = 100 * 1024 * 1024;
            $size_cr   = $file_av_ar[$inp]['fldr'];

            if (isset($size_cr['high']['sz']) && $size_cr['high']['sz'] > 1200) {
                $size_cr['high']['sz'] = 1200;
            }

            if (getimagesize($fi_path) === false) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Not a valid image';
                continue;
            }
            if (!isset($file_av_ar[$inp]['frmt'][$fi_tp])) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Format "'.$fi_tp.'" not allowed';
                continue;
            }
            $frmt_cr = $file_av_ar[$inp]['frmt'][$fi_tp];

            if (!strlen($nm) || $ar['size'][$k] > $max_mb) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Size exceeded';
                continue;
            }

            $tmp_f = 'tmp/'.uniqid('up_'.$last_id.'_', true).'.jpg';
            if (!move_uploaded_file($fi_path, $tmp_f)) {
                $rtrn .= ' | File #'.$i.': '.$nm.' - Move failed';
                continue;
            }

            $pos_counter++;
            $n_nm    = 'car_'.$last_id.'_'.$pos_counter;
            $imgRes  = (new FileService())->createImage($tmp_f, $base_path, $n_nm, $size_cr, $frmt_cr);

            if ($imgRes) {
                $pdoInsert->execute([
                    'it_id' => $last_id,
                    'tp'    => $inp,
                    'path'  => $zY.'/'.$zM,
                    'name'  => $n_nm,
                    'ff'    => $frmt_cr[0],
                    'main'  => $main_file,
                    'pos'   => $pos_counter,
                ]);
                $changelog_photos_added[] = $n_nm.'.'.$frmt_cr[0];
                // Mirror into R2 so the photo is servable once the local copies go.
                foreach (array_keys($size_cr) as $_sz) {
                    \App\Services\CarPhotoR2::push($base_path.'/'.$_sz.'/'.$n_nm.'.'.$frmt_cr[0]);
                }
            }

        } elseif ($inp == 'doc') {
            $pos_counter++;
            $n_nm     = 'car_'.$last_id.'_'.$pos_counter;
            $fNewPath = $base_path.'/doc';
            if (!file_exists($fNewPath)) { mkdir($fNewPath, 0755, true); @copy($ihtml, $fNewPath.'/index.html'); }
            if (!move_uploaded_file($fi_path, $fNewPath.'/'.$n_nm.'.pdf')) {
                $rtrn .= 'Can\'t move file '.$nm.'.';
                continue;
            }
            \App\Services\CarPhotoR2::push($fNewPath.'/'.$n_nm.'.pdf');
        }
        $i++;
    }

    if (!empty($changelog_photos_added)) {
        car_changelog_log($db, $prefx, [
            'car_id'       => $last_id,
            'action'       => 'photo_add',
            'field_name'   => 'photo',
            'old_value'    => null,
            'new_value'    => count($changelog_photos_added).' фото: '.implode(', ', $changelog_photos_added),
            'catalog_type' => 'cars'
        ]);
    }
}

finfo_close($fi_mime);
unset($ihtml);
