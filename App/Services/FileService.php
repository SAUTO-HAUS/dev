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
        $info = @getimagesize($tmp_f);
        if (!$info) { @unlink($tmp_f); return false; }
        list($w, $h) = $info;

        // Sort sizes descending so largest is first — we use it as the resize source
        uasort($size_cr, fn($a, $b) => $b['sz'] <=> $a['sz']);

        $img_src = null;  // loaded lazily, reused for all sizes
        $src_w = $w;
        $src_h = $h;

        foreach ($size_cr as $k => $v) {
            $dir_path = $path.'/'.$k;
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0755, true);
                if (file_exists('tmp/index.html')) copy('tmp/index.html', $dir_path.'/index.html');
            }

            $max_dim = max($src_w, $src_h);

            // Source fits in this slot — save directly without resize
            if ($max_dim <= $v['sz']) {
                if ($img_src === null) {
                    $img_src = @imagecreatefromjpeg($tmp_f);
                    if (!$img_src) { @unlink($tmp_f); return false; }
                }
                foreach ($frmt_cr as $frmt) {
                    $full_path = $dir_path.'/'.$n_nm.'.'.$frmt;
                    if ($frmt === 'jpg' || $frmt === 'jpeg') imagejpeg($img_src, $full_path, $v['ql']);
                    elseif ($frmt === 'webp') imagewebp($img_src, $full_path, $v['ql']);
                }
                continue;
            }

            // Need resize
            if ($img_src === null) {
                $img_src = @imagecreatefromjpeg($tmp_f);
                if (!$img_src) { @unlink($tmp_f); return false; }
            }

            if ($src_w >= $src_h) {
                $img_new = imagescale($img_src, $v['sz'], -1, IMG_BILINEAR_FIXED);
            } else {
                $img_new = imagescale($img_src, -1, $v['sz'], IMG_BILINEAR_FIXED);
            }

            foreach ($frmt_cr as $frmt) {
                $full_path = $dir_path.'/'.$n_nm.'.'.$frmt;
                if ($frmt === 'jpg' || $frmt === 'jpeg') imagejpeg($img_new, $full_path, $v['ql']);
                elseif ($frmt === 'webp') imagewebp($img_new, $full_path, $v['ql']);
            }

            imagedestroy($img_new);
        }

        if ($img_src) imagedestroy($img_src);
        unlink($tmp_f);
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
        $ratio = $h / $w;

        // Load source ONCE
        $img_old = @imagecreatefromjpeg($tmp_f);
        if (!$img_old) {
            @unlink($tmp_f);
            return false;
        }

        foreach ($size_cr as $k => $v) {
            $dir_path = $path.'/'.$k;
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0755, true);
                if (file_exists('tmp/index.html')) {
                    copy('tmp/index.html', $dir_path.'/index.html');
                }
            }

            $full_path = $dir_path.'/'.$n_nm.'.jpg';

            if ($w > $h) {
                $img_w = $v['sz'];
                $img_h = (int)($img_w * $ratio);
            } elseif ($w < $h) {
                $img_h = $v['sz'];
                $img_w = (int)($img_h / $ratio);
            } else {
                $img_w = $v['sz'];
                $img_h = $v['sz'];
            }

            $img_new = imagecreatetruecolor($img_w, $img_h);
            imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $img_w, $img_h, $w, $h);
            imagejpeg($img_new, $full_path, $v['ql']);
            imagedestroy($img_new);
        }

        imagedestroy($img_old);
        unlink($tmp_f);
        return true;
    }
}
