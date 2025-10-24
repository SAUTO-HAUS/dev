<?php

namespace App\Services;

class FileService
{
    /**
     * Create image
     *
     * @param $tmp_f
     * @param $path
     * @param $n_nm
     * @param $size_cr
     * @param $frmt_cr
     * @return true
     */
    public function createImage($tmp_f, $path, $n_nm, $size_cr, $frmt_cr): bool
    {
        list($w, $h) = getimagesize($tmp_f);
        $ratio = $h / $w; // Получаем размеры исходного фото

        foreach ($frmt_cr as $frmt) {//Цикл форматов
            foreach ($size_cr as $k => $v) {// Цикл размеров
                $full_path = $path.'/'.$k.'/'.$n_nm.'.'.$frmt; // создаем путь для выходного фото
                // задаем размеры для выходного фото
                if ( $w > $h ) {
                    $img_w = $v['sz'];
                    $img_h = (int)($img_w * $ratio);
                } elseif ( $w < $h ) {
                    $img_h = $v['sz'];
                    $img_w = (int)($img_h / $ratio);
                } else {
                    $img_w = $v['sz'];
                    $img_h = $v['sz'];
                }

                $img_new = imagecreatetruecolor($img_w, $img_h); // создаем выходное фото с указанными выше размерами
                $img_old = imagecreatefromjpeg($tmp_f); // исходное фото
                imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $img_w, $img_h, $w, $h); // наложение исходного на выходное фото

                // сохраняем выходное фото
                if ( $frmt == 'jpg' || $frmt == 'jpeg' ) {
                    imagejpeg($img_new, $full_path, $v['ql']);
                } elseif ($frmt == 'webp') {
                    imagewebp($img_new, $full_path, $v['ql']);
                }

                imagedestroy($img_new);
                imagedestroy($img_old); // очищаем память
            }
        }
        unlink($tmp_f); // уничтожаем исходное фото
        return true;
    }

    /**
     * Create image preserving original JPEG format for order cars
     * No WebP conversion - keep original JPEG only
     *
     * @param $tmp_f
     * @param $path
     * @param $n_nm
     * @param $size_cr
     * @return bool
     */
    public function createImagePreserveJpeg($tmp_f, $path, $n_nm, $size_cr): bool
    {
        $imageInfo = getimagesize($tmp_f);
        if ($imageInfo === false) {
            return false;
        }
        
        list($w, $h) = $imageInfo;
        $ratio = $h / $w; // Get original photo dimensions

        // Only JPEG format for order cars
        $frmt_cr = ['jpg'];

        foreach ($frmt_cr as $frmt) { // Format loop
            foreach ($size_cr as $k => $v) { // Size loop
                $dir_path = $path.'/'.$k;
                // Create directory if not exists
                if (!file_exists($dir_path)) {
                    mkdir($dir_path, 0755, true);
                    copy('tmp/index.html', $dir_path.'/index.html');
                }
                
                $full_path = $dir_path.'/'.$n_nm.'.'.$frmt; // Create output photo path
                
                // Set dimensions for output photo
                if ( $w > $h ) {
                    $img_w = $v['sz'];
                    $img_h = (int)($img_w * $ratio);
                } elseif ( $w < $h ) {
                    $img_h = $v['sz'];
                    $img_w = (int)($img_h / $ratio);
                } else {
                    $img_w = $v['sz'];
                    $img_h = $v['sz'];
                }

                $img_new = imagecreatetruecolor($img_w, $img_h); // Create output photo with dimensions above
                $img_old = imagecreatefromjpeg($tmp_f); // Source photo
                
                if ($img_old === false) {
                    return false;
                }
                
                imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $img_w, $img_h, $w, $h); // Apply source to output photo

                // Save output photo in JPEG only
                $result = imagejpeg($img_new, $full_path, $v['ql']);
                
                imagedestroy($img_new);
                imagedestroy($img_old); // Clear memory
                
                if (!$result) {
                    return false;
                }
            }
        }
        unlink($tmp_f); // Delete source photo
        return true;
    }
}
