<?php
class ImageWork {

    static function resize_crop($input, $path, $width, $height) {
        if( isset($_FILES[$input]) ) :
            if( $_FILES[$input]["name"] == TRUE ) :
                if( extension_loaded('imagick') || class_exists('Imagick', false) ) {
                    return self::imagick($input, $path, $width, $height);
                }
                else {
                	return self::GdImage($input, $path, $width, $height);
                }
            endif;
        endif;
    }


    static function imagick($input, $path, $width, $height) {

        $temp = $_FILES[$input]["tmp_name"];
        $name = $_FILES[$input]["name"];

	    $imagick = new Imagick();

	    $width = intval($width); $height = intval($height);

        $ext = pathinfo($name, PATHINFO_EXTENSION);

        list($w, $h) = getimagesize($temp);

        $imagick->readImage($temp);

        if(!is_dir($path)) 
        	$path = mkdir($path, 0777, true);

        if( file_exists(__DIR__.'/watermark.png') ) {
        	self::watermark($imagick);
        }

        if($ext == 'gif') {
            $image = $imagick->coalesceImages();

            foreach($image as $images) {
                $images->cropThumbnailImage($width, $height);
            }

            $image->deconstructImages();
            $image->writeImages( $path.$name, true );
        }
        else {
        	$imagick->cropThumbnailImage($width, $height);
        	$imagick->writeImage( $path.$name );
        }
        $imagick->destroy();

    }


    static function GdImage($input, $path, $width, $height) {

        $temp  = $_FILES[$input]["tmp_name"];
        $name = $_FILES[$input]["name"];

        $ext = pathinfo($name, PATHINFO_EXTENSION);

        switch($ext) {
            case 'gif':
                $origin = imagecreatefromgif($temp);
            break;
            case 'png':
                $origin = imagecreatefrompng($temp);
            break;
            case 'jpeg': case 'jpg':
                $origin = imagecreatefromjpeg($temp);
            break;
            case 'webp':
                $origin = imagecreatefromwebp($temp);
            break;
        }

        $orig_width  = imagesx($origin);
        $orig_height = imagesy($origin);

        $calc_width  = $orig_height * $width / $height;
        $calc_height = $orig_width * $height / $width;

        $imagecreate = imagecreatetruecolor($width, $height);

        if($calc_width > $orig_width) { # height
            $point_height = intval( (($orig_height - $calc_height) / 2) );
            imagecopyresampled(
                $imagecreate, 
                $origin, 
                0, 0, 0, $point_height, 
                $width, 
                $height, 
                $orig_width, 
                $calc_height
            );
        } 
        else { # width
            $point_width = intval( (($orig_width - $calc_width) / 2) );
            imagecopyresampled(
                $imagecreate, 
                $origin, 
                0, 0, $point_width, 0, 
                $width, 
                $height, 
                $calc_width, 
                $orig_height
            );
        }

        if(!is_dir($path)) 
            $path = mkdir($path, 0777, true);
        
        switch($ext) {
            case 'gif':
                $save = imagegif($imagecreate, $path.$name);
            break;
            case 'png':
                $save = imagepng($imagecreate, $path.$name);
            break;
            case 'jpeg': case 'jpg':
                $save = imagejpeg($imagecreate, $path.$name);
            break;
            case 'webp':
                $save = imagewebp($imagecreate, $path.$name);
            break;
        }

        imagedestroy($imagecreate);

        return $save;
    }


    static function watermark($imagick) {
    	$watermark = new Imagick(__DIR__.'/watermark.png');
		$iw = $imagick->getImageWidth();
		$ih = $imagick->getImageHeight();
		$ww = $watermark->getImageWidth();
		$wh = $watermark->getImageHeight();
		if($ih < $wh || $iw < $ww) {
		    $watermark->scaleImage($iw, $ih);
		    $ww = $watermark->getImageWidth();
		    $wh = $watermark->getImageHeight();
		}
		$x = ($iw - $ww) / 2;
		$y = ($ih - $wh) / 2;
		$imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);
	}

}
