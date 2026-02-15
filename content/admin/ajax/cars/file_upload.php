<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Services\FileService;

//______________________________________________________________________________________________________START
$ihtml = 'tmp/index.html';
$zDir = _CAR_IMG;
$last_id = __post('last_id');
$zY = substr( md5( date('Y') ), 0, 4 );
$zM = substr( md5( date('m') ), 0, 4 );

foreach ($_FILES as $inp => $ar){//________________Цикл по типу файлов (берется из input name="*")
	//________________Создание (folders & index.html)
	$zp = '';
    $fldrs = [ $zDir, $zY, $zM, $last_id, $file_av_ar[$inp]['fldr'] ]; //P.S. array needs to be at the end and just 1

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
    $pdo_v = '';
    $pdo_ar = [];
	//________________________Цикл файлов
	foreach ($ar['name'] as $k => $nm) {
		//________________Сбор информации о файле (F)
		$fi_path = $_FILES[$inp]['tmp_name'][$k][0]; //F path
		$fi_sz = filesize($fi_path); //F size
		$fi_mime = finfo_open(FILEINFO_MIME_TYPE); //F info
		$fi_tp = strtolower( finfo_file($fi_mime, $fi_path) ); //F type
		
		$tmp_f = 'tmp/'.$nm[0];
		$path = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id;
		// $n_nm = substr( md5( microtime() ), 0, 10 );

        /*
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1); */

        $pdo2 = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id order by pos desc limit 1 ');
        $pdo2->execute(['it_id'=> $last_id ]);
        $photo = $pdo2->fetch();

        $pos = $photo['pos'] + 1;
        $n_nm = 'car_'. $last_id .'_'. $pos;


		
		//______________Если input type="file" name = img[] или bg[]
		if ($inp=='img') {
            if (!empty(__post('main_img')) || __post('main_img') == 0) {
                $main_file = (__post('main_img') == $k) ? 1 : 0;
            } else {
                $main_file = 0;
            }

			$max_mb = 100*1024*1024;
			
			$size_cr = ($inp=='bg') ? ['high'=>['sz'=>2000, 'ql'=>80], 'med'=>['sz'=>600, 'ql'=>80]] : $file_av_ar[$inp]['fldr'];
			
			//______________Проверки
			if ( $_FILES[$inp]['error'][$k][0] !== UPLOAD_ERR_OK ) {
                $rtrn .= ' | File #'.$i.': '.$nm[0].' - Upload failed with error code' . $_FILES[$inp]['error'];
                continue;
            }

			if ( getimagesize($_FILES[$inp]['tmp_name'][$k][0]) === FALSE ) {
                $rtrn .= ' | File #'.$i.': '.$nm[0].' - Unable to determine image type of uploaded file';
                continue;
            }// проверяем файл

			if ( !isset($file_av_ar[$inp]['frmt'][$fi_tp]) ) {
                $rtrn .= ' | File #'.$i.': '.$nm[0].' - File format "'.$fi_tp.'" is not allowed';
                continue;
            } else {
                $frmt_cr = $file_av_ar[$inp]['frmt'][$fi_tp];
            }// проверяем формат файла
			
			if( strlen($nm[0]) ){// проверяем что имя фото не пустое
				if ( $_FILES[$inp]['size'][$k][0] <= $max_mb ){// проверяем размер фото
					$upload_status = move_uploaded_file($_FILES[$inp]['tmp_name'][$k][0], $tmp_f); // загружаем фото во временную папку
					if($upload_status){ // если успешно загружено
						if((new FileService())->createImage($tmp_f, $path, $n_nm, $size_cr, $frmt_cr)) { // создаем выходное фото
                            $photoPdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE it_id = :it_id order by pos desc limit 1');
                            $photoPdo->execute(['it_id'=>$last_id]);
                            $photo = $photoPdo->fetch();

                            //if ($photo['pos'] == 0) {
                                $pos = $photo['pos'] + 1;
                            //} else {
                              //  $pos = 0;
                            //}

							$pdo = $db->prepare('INSERT INTO '.$prefx.'_car_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`, `pos`) 
							    VALUES (:it_id, :tp, :path, :name, :ff, :main, :pos)');
							$pdo->execute([
                                'it_id'=>$last_id,
                                'tp'=>$inp,
                                'path'=>$zY.'/'.$zM,
                                'name'=>$n_nm,
                                'ff'=>$file_av_ar[$inp]['frmt'][$fi_tp][0],
                                'main'=>$main_file,
                                'pos'=>$pos
                            ]);

                            // --- CHANGELOG: log photo add ---
                            car_changelog_log($db, $prefx, [
                                'car_id' => $last_id,
                                'action' => 'photo_add',
                                'field_name' => 'photo',
                                'old_value' => null,
                                'new_value' => $n_nm.'.'.$file_av_ar[$inp]['frmt'][$fi_tp][0],
                                'catalog_type' => 'cars'
                            ]);
						}
					} else {
                        $rtrn .= ' | File #'.$i.': '.$nm[0].' - Upload failed.';
                        continue;
                    }
				} else {
                    $rtrn .= ' | File #'.$i.': '.$nm[0].' - File upload size exceeded (Max '.$max_mb.'Mb)';
                    continue;
                }
			}
			
		//______________Если input type="file" name = doc[]
		} elseif ($inp=='doc') {
			$fNewPath = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id.'/'.$inp;
			if ( !file_exists($fNewPath) ) {
                mkdir($fNewPath, 0755, true);
                copy($ihtml, $fNewPath.'/index.html');
            }
			if ( !move_uploaded_file($fi_path, $fNewPath.'/'.$n_nm.'.pdf') ) {
                $rtrn .= 'Can\'t move file '.$nm[0].'.';
                continue;
            }
		}
		$i++;
	}
}

unset($ihtml);
