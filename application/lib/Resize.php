<?php
namespace  App\Lib;

use App\Model\Language;
use Exception;

class Resize {
    private $width;
    private $height;
    private $resource_type;
    private $mime;
    private $image;
    private $dest_width;
    private $dest_height;
    /**
     * @var int
     */
    private $source_h;
    /**
     * @var int
     */
    private $source_w;
    /**
     * @var int
     */
    private $source_x;
    /**
     * @var int
     */
    private $source_y;
    /**
     * @var int
     */
    private $dest_x;
    /**
     * @var int
     */
    private $dest_y;
    private $quality_jpeg = 90;
    private $quality_png = 7;


    /**
     * Resize constructor.
     * @param $file
     */
    public function __construct($file)
    {
        if(empty($file) || $file === null || !is_file($file)) {
            throw new Exception("Error: Couldn't load Image");
        }
        list($this->width, $this->height, $this->resource_type, $this->mime) = getimagesize($file);
        $this->image = $this->createResourceFromFile($this->resource_type, $file);
    }

    public function resize($width, $height, $allow_enlarge = false) {
        if(!$allow_enlarge) {
            if($width > $this->width || $height > $this->height) {
                $width = $this->width;
                $height = $this->height;
            }
        }
        $this->dest_width = $width;
        $this->dest_height = $height;
        $this->source_x = 0;
        $this->source_y = 0;
        $this->source_w = $this->width;
        $this->source_h = $this->height;

    }

    public function save($filename, $image_type = null, $quality = null) {
        $image_type = $image_type ?: $this->resource_type;
        $quality = is_numeric($quality) ?(int) abs($quality) : null;
        switch ($image_type) {
            case IMAGETYPE_JPEG :
                $dest_image = imagecreatetruecolor($this->dest_width, $this->dest_height);
                $background = imagecolorallocate($dest_image, 255,255,255);
                imagefilledrectangle($dest_image, 0,0, $this->dest_width, $this->dest_height, $background);
                break;
            case IMAGETYPE_PNG :
                $dest_image = imagecreatetruecolor($this->dest_width, $this->dest_height);
                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);
                $background = imagecolorallocatealpha($dest_image, 255,255,255,127);
                imagefill($dest_image, 0,0, $background);
                break;
        }
        if(isset($dest_image)) {
            imagecopyresampled($dest_image, $this->image, $this->dest_x, $this->dest_y, $this->source_x, $this->source_y, $this->dest_width, $this->dest_height, $this->source_w, $this->source_h);
            switch ($image_type) {
                case IMAGETYPE_JPEG :
                    if($quality == null || $quality > 100) {
                        $quality = $this->quality_jpeg;
                    }
                    imagejpeg($dest_image, $filename, $quality);
                    break;
                case IMAGETYPE_PNG :
                    if($quality == null || $quality > 9) {
                        $quality = $this->quality_png;
                    }
                    imagepng($dest_image, $filename, $quality);
                    break;
            }
            imagedestroy($dest_image);
        }
        imagedestroy($this->image);
    }

    public function resizeToLongSide($max_long, $allow_enlarge = false) {
        if($this->height > $this->width) {
            $ratio = $max_long / $this->height;
            $short = $this->width * $ratio;
            $this->resize($short, $max_long, $allow_enlarge);
        }else {
            $ratio = $max_long * $this->width;
            $short = $this->height * $ratio;
            $this->resize($max_long, $short, $allow_enlarge);
        }
    }

    public function resizeToHeight($height, $allow_enlarge = false) {
        $ratio = $height / $this->height;
        $width = $this->width * $ratio;
        $this->resize($width, $height, $allow_enlarge);
    }

    public function resizeToWidth($width, $allow_enlarge = false) {
        $ratio = $width / $this->width;
        $height = $this->height * $ratio;
        $this->resize($width, $height, $allow_enlarge);
    }

    public function resizeToBestFit($max_width, $max_height, $allow_enlarge = false)
    {
        if ($this->width <= $max_width && $this->height <= $max_height && $allow_enlarge === false) {
            return $this;
        }
        $ratio  = $this->height / $this->width;
        $width = $max_width;
        $height = $width * $ratio;
        if ($height > $max_height) {
            $height = $max_height;
            $width = $height / $ratio;
        }
        return $this->resize($width, $height, $allow_enlarge);
    }


    public function scale($scale)
    {
        $width  = $this->width * $scale / 100;
        $height = $this->height * $scale / 100;
        $this->resize($width, $height, true);
        return $this;
    }



    private function createResourceFromFile($resource_type, $file)
    {
        switch ($resource_type) {
            case IMAGETYPE_JPEG :
                return imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG :
                return imagecreatefrompng($file);
                break;
        }
        return false;
    }
}