<?php

namespace App\Helpers;

use Request, Image;

class CropImage
{

    public static function make($input, $object)
    {
        if (!Request::hasFile($input) || !$object) return false;

        $image = Request::file($input);

        if (!is_array(array_values($object)[0])) $object = array($object);

        $name = str_slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'_'.date('YmdHis').'.'.$image->getClientOriginalExtension();

        if (!file_exists(public_path($config['path']))) {
            mkdir(public_path($config['path']), 0777, true);
        }

        foreach($object as $config) {
            $width       = $config['width'];
            $height      = $config['height'];
            $path        = $config['path'].$name;
            $upsize      = (array_key_exists('upsize', $config) ? $config['upsize'] : false);

            $imgobj = Image::make($image->getRealPath());

            if ($width == null && $height == null) {
                $imgobj->save($path, 100);
            } elseif ($width == null || $height == null) {
                $imgobj->resize($width, $height, function($constraint) use ($upsize) {
                    $constraint->aspectRatio();
                    if ($upsize) { $constraint->upsize(); }
                })->save($path, 100);
            } else {
                $imgobj->fit($width, $height, function ($constraint) use ($upsize) {
                    if ($upsize) { $constraint->upsize(); }
                })->save($path, 100);
            }

            $imgobj->destroy();
        }

        return $name;
    }

}
