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
}
