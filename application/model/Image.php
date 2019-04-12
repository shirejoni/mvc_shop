<?php


namespace App\model;


use App\Lib\Resize;
use App\system\Model;
use function GuzzleHttp\Psr7\copy_to_stream;

class Image extends Model
{
    public function resize($file_path, $width, $height) {
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $image_old = $file_path;
        $image_new = 'cache/' . substr($file_path, 0, strpos( $image_old, '.')) . '-' . $width . 'x' . $height . '.'. $extension;
        if(!is_file(ASSETS_PATH . DS . $image_new) || filemtime(ASSETS_PATH . DS . $image_old) > filemtime(ASSETS_PATH . DS . $image_new)) {
            $directories = explode('/', dirname($image_new));
            $path = ASSETS_PATH;
            foreach ($directories as $directory) {
                $path .= DS . $directory;
                if(!is_dir($path)) {
                    @mkdir($path);
                }
            }
            $imageDetail = getimagesize(ASSETS_PATH . DS . $image_old);
            if($width != $imageDetail[0] || $height != $imageDetail[1]) {
                $image = new Resize(ASSETS_PATH . DS . $image_old);
                $image->resizeToBestFit($width, $height);
                $image->save(ASSETS_PATH . DS . $image_new);
            }else {
                copy(ASSETS_PATH . DS . $image_old , ASSETS_PATH . DS . $image_new);
            }
        }
        return $image_new;
    }

}