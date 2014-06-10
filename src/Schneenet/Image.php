<?php
namespace Schneenet;

class Image
{
	public static function createThumbnail($path, $target_path, $type, $max_dimension = 200)
	{
		switch ($type)
		{
			case 'jpeg':
			case 'jpg':
				$image = imagecreatefromjpeg($path);
				break;
			case 'gif':
				$image = imagecreatefromgif($path);
				break;
			case 'png':
				$image = imagecreatefrompng($path);
				break;
		}
		
		$orig_w = imagesx($image);
		$orig_h = imagesy($image);
		$aspect = $orig_w / $orig_h;
		
		if ($aspect > 1)
		{
			$new_w = $max_dimension;
			$new_h = $new_w / $aspect;
		}
		else
		{
			$new_h = $max_dimension;
			$new_w = $aspect * $new_h;
		}
		
		$thumb = imagecreatetruecolor($new_w, $new_h);
		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
		
		switch ($type)
		{
			case 'jpeg':
			case 'jpg':
				imagejpeg($thumb, $target_path, 95);
				break;
			case 'gif':
				imagegif($thumb, $target_path);
				break;
			case 'png':
				imagepng($thumb, $target_path, 2);
				break;
		}
	}
}