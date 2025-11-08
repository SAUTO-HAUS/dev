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
        $ratio = $h / $w; // Get original photo dimensions

        foreach ($frmt_cr as $frmt) { // Format loop
            foreach ($size_cr as $k => $v) { // Size loop
                $full_path = $path.'/'.$k.'/'.$n_nm.'.'.$frmt; // Create output photo path
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
                imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $img_w, $img_h, $w, $h); // Apply source to output photo

                // Save output photo
                if ( $frmt == 'jpg' || $frmt == 'jpeg' ) {
                    imagejpeg($img_new, $full_path, $v['ql']);
                } elseif ($frmt == 'webp') {
                    imagewebp($img_new, $full_path, $v['ql']);
                }

                imagedestroy($img_new);
                imagedestroy($img_old); // Clear memory
            }
        }
        unlink($tmp_f); // Delete source photo
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
                    $mkdir_result = mkdir($dir_path, 0755, true);
                    error_log("Creating directory: {$dir_path} - " . ($mkdir_result ? 'SUCCESS' : 'FAILED'));
                    if ($mkdir_result) {
                        copy('tmp/index.html', $dir_path.'/index.html');
                    }
                } else {
                    error_log("Directory already exists: {$dir_path}");
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
                error_log("Saving JPEG to: {$full_path} with quality: {$v['ql']}");
                $result = imagejpeg($img_new, $full_path, $v['ql']);
                error_log("JPEG save result: " . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result && file_exists($full_path)) {
                    error_log("File created successfully: {$full_path} (size: " . filesize($full_path) . " bytes)");
                } else {
                    error_log("File creation FAILED: {$full_path}");
                }
                
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
