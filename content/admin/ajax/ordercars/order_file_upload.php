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
		$i++;
		error_log("Processing file #{$i}: {$nm[0]}");
		
		//________________Сбор информации о файле (F)
		$fi_path = $_FILES[$inp]['tmp_name'][$k][0]; //F path
		$fi_sz = filesize($fi_path); //F size
		$fi_mime = finfo_open(FILEINFO_MIME_TYPE); //F info
		$fi_tp = strtolower( finfo_file($fi_mime, $fi_path) ); //F type
		
		error_log("File info - Path: {$fi_path}, Size: {$fi_sz}, MIME: {$fi_tp}");
		
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

			// For order cars only accept JPEG format (use same structure as regular cars)
			$allowed_jpeg_types = ['image/jpeg', 'image/jpg', 'image/pjpeg'];
			
			if (!in_array($fi_tp, $allowed_jpeg_types)) {
				// Also check file extension as backup
				$file_extension = strtolower(pathinfo($nm[0], PATHINFO_EXTENSION));
				$is_jpeg_by_extension = in_array($file_extension, ['jpg', 'jpeg']);
				
				if (!$is_jpeg_by_extension) {
					$rtrn .= ' | File #'.$i.': '.$nm[0].' - Pentru automobile la comandă sunt acceptate doar fișiere JPEG / Для автомобилей под заказ только JPEG / For custom order cars only JPEG files allowed. Format "'.$fi_tp.'" not supported.';
					continue;
				}
			}
			
			// Set format for order cars (JPEG only, no WebP)
			$frmt_cr = ['jpg'];
			
			// Count existing JPEG images for this car (for edit mode)
			$existingJpegCount = 0;
			if (!empty($last_id)) {
				$jpegCountPdo = $db->prepare('SELECT COUNT(*) as jpeg_count FROM '.$prefx.'_car_pht WHERE it_id = :it_id AND ff = "jpg"');
				$jpegCountPdo->execute(['it_id' => $last_id]);
				$jpegCountResult = $jpegCountPdo->fetch();
				$existingJpegCount = $jpegCountResult['jpeg_count'] ?? 0;
			}
			
			error_log("JPEG file accepted: {$nm[0]}, MIME: {$fi_tp}. Existing JPEG count: {$existingJpegCount}");
			
			if( strlen($nm[0]) ){// проверяем что имя фото не пустое
				if ( $_FILES[$inp]['size'][$k][0] <= $max_mb ){// проверяем размер фото
					$upload_status = move_uploaded_file($_FILES[$inp]['tmp_name'][$k][0], $tmp_f); // загружаем фото во временную папку
					error_log("Upload status: " . ($upload_status ? 'SUCCESS' : 'FAILED') . " for file: {$tmp_f}");
					if($upload_status){ // если успешно загружено
						// Use JPEG-only method for order cars
						error_log("Attempting to create JPEG image: {$tmp_f} -> {$path}/{$n_nm}");
						error_log("Size config: " . json_encode($size_cr));
						$imageResult = (new FileService())->createImagePreserveJpeg($tmp_f, $path, $n_nm, $size_cr);
						error_log("Image creation result: " . ($imageResult ? 'SUCCESS' : 'FAILED'));
						if($imageResult) { // создаем выходное фото в JPEG
							error_log("JPEG image creation successful");
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
                                'ff'=>'jpg', // Always JPEG for order cars
                                'main'=>$main_file,
                                'pos'=>$pos
                            ]);
                            
                            // Debug success
                            error_log("Image saved to DB: {$n_nm}.jpg for car {$last_id}");
						} else {
							error_log("JPEG image creation FAILED for: {$tmp_f}");
							$rtrn .= ' | File #'.$i.': '.$nm[0].' - Image processing failed.';
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
