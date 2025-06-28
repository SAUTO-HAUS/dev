<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Services\FileService;

//______________________________________________________________________________________________________START
$ihtml = 'tmp/index.html'; $zDir = _TYRES_IMG; $last_id = $_POST['last_id']; $zY = substr( md5( date('Y') ), 0, 4 ); $zM = substr( md5( date('m') ), 0, 4 );

foreach ($_FILES as $inp => $ar){//________________Цикл по типу файлов (берется из input name="*")
	//________________Создание (folders & index.html)
	$zp = ''; $fldrs = [ $zDir, $zY, $zM, $last_id, $file_av_ar[$inp]['fldr'] ]; //P.S. array needs to be at the end and just 1
	foreach($fldrs as $k => $v){
		if ( is_array($v) ){ foreach($v as $v => $ar2){ $zp_t = $zp.'/'.$v; if (!file_exists($zp_t)){ mkdir($zp_t, 0755, true); copy($ihtml, $zp_t.'/index.html'); } } }
		else{ $zp .= ($k==0) ? $v : '/'.$v; if (!file_exists($zp)) { mkdir($zp, 0755, true); copy($ihtml, $zp.'/index.html');} }	
	} unset($zp, $zp_t, $fldrs, $ar2);
	
	$i=0; $pdo_v = ''; $pdo_ar = [];
	//________________________________________________________________________________Цикл файлов
	foreach ($ar['name'] as $k => $nm){
		//________________Сбор информации о файле (F)
		$fi_path = $_FILES[$inp]['tmp_name'][$k]; //F path
		$fi_sz = filesize($fi_path); //F size
		$fi_mime = finfo_open(FILEINFO_MIME_TYPE); //F info
		$fi_tp = strtolower( finfo_file($fi_mime, $fi_path) ); //F type
		
		$p_path = 'tmp';
		$pdo = $db->prepare('SELECT `p_path` FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id '); $pdo->execute(['id'=>$last_id]);
		foreach ($pdo as $r){$p_path = $r['p_path'];}
		
		$tmp_f = 'tmp/'.$nm;
		$path = $zDir.'/'.$p_path.'/'.$last_id;
		$n_nm = substr( md5( microtime() ), 0, 10 );
		
		//______________Если input type="file" name = img[] или bg[]
		if ($inp=='img'){
			$main_file = ($_POST['main_img']==$_POST['img_k'])?1:0;
			$max_mb = 100*1024*1024;
			
			$size_cr = ($inp=='bg') ? [ 'high'=>['sz'=>2000, 'ql'=>80], 'med'=>['sz'=>600, 'ql'=>80] ] : $file_av_ar[$inp]['fldr'];
			
			//______________Проверки
			if ( $_FILES[$inp]['error'][$k] !== UPLOAD_ERR_OK ){ $rtrn .= ' | File #'.$i.': '.$nm.' - Upload failed with error code' . $_FILES[$inp]['error']; continue; }
			if ( getimagesize($_FILES[$inp]['tmp_name'][$k]) === FALSE ){ $rtrn .= ' | File #'.$i.': '.$nm.' - Unable to determine image type of uploaded file'; continue; }// проверяем файл
			if ( !isset($file_av_ar[$inp]['frmt'][$fi_tp]) ){ $rtrn .= ' | File #'.$i.': '.$nm.' - File format "'.$fi_tp.'" is not allowed'; continue; } else { $frmt_cr = $file_av_ar[$inp]['frmt'][$fi_tp]; };// проверяем формат файла
			
			if( strlen($nm) ){// проверяем что имя фото не пустое
				if ( $_FILES[$inp]['size'][$k] <= $max_mb ){// проверяем размер фото
					$upload_status = move_uploaded_file($_FILES[$inp]['tmp_name'][$k], $tmp_f); // загружаем фото во временную папку
					if($upload_status){ // если успешно загружено
						if((new FileService())->createImage( $tmp_f, $path, $n_nm, $size_cr, $frmt_cr ) ){ // создаем выходное фото
							//$pdo_v .= ($i>0?',':'').'(:id_'.$i.', :tp_'.$i.', :path_'.$i.', :name_'.$i.', :ff_'.$i.', :main_'.$i.')';
							//$pdo_ar += [ 'id_'.$i=>$last_id, 'tp_'.$i=>$inp, 'path_'.$i=>$zY.'/'.$zM, 'name_'.$i=>$n_nm, 'ff_'.$i=>$file_av_ar[$inp]['frmt'][$fi_tp][0], 'main_'.$i=>$main_file ];
							$pdo = $db->prepare('INSERT INTO '.$prefx.'_tyre_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`) VALUES (:it_id, :tp, :path, :name, :ff, :main)');
							$pdo->execute(['it_id'=>$last_id, 'tp'=>$inp, 'path'=>$p_path, 'name'=>$n_nm, 'ff'=>$file_av_ar[$inp]['frmt'][$fi_tp][0], 'main'=>$main_file]);
						}
					} else { $rtrn .= ' | File #'.$i.': '.$nm.' - Upload failed.'; continue; }
				} else { $rtrn .= ' | File #'.$i.': '.$nm.' - File upload size exceeded (Max '.$max_mb.'Mb)'; continue; }
			}
			
		//______________Если input type="file" name = doc[]
		}elseif($inp=='doc'){
			$fNewPath = $zDir.'/'.$zY.'/'.$zM.'/'.$last_id.'/'.$inp;
			if ( !file_exists($fNewPath) ){ mkdir($fNewPath, 0755, true); copy($ihtml, $fNewPath.'/index.html');}
			if ( !move_uploaded_file($fi_path, $fNewPath.'/'.$n_nm.'.pdf') ){ continue; $rtrn .= 'Can\'t move file '.$nm.'.'; }
		}
		$i++;
	}
}

//$pdo = $db->prepare('INSERT INTO '.$prefx.'_tyre_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`) VALUES '.$pdo_v); $pdo->execute($pdo_ar);

unset($ihtml);