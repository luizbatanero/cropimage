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

        $isTransparent = false;
        foreach ($object as $config) {
            if (array_key_exists('transparent', $config)) {
                $isTransparent = true;
            }
        }

        if ($isTransparent) {
            $name = str_slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'_'.date('YmdHis').'.png';
        } else {
            $name = str_slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'_'.date('YmdHis').'.'.$image->getClientOriginalExtension();
        }

        foreach($object as $config) {
            $width       = $config['width'];
            $height      = $config['height'];
            $path        = $config['path'].$name;
            $upsize      = (array_key_exists('upsize', $config) ? $config['upsize'] : false);
            $bgcolor     = (array_key_exists('bgcolor', $config) ? $config['bgcolor'] : false);
            $transparent = array_key_exists('transparent', $config);

            if (!file_exists(public_path($config['path']))) {
                mkdir(public_path($config['path']), 0777, true);
            }

            $imgobj = Image::make($image->getRealPath());

            if ($width == null && $height == null) {
                $imgobj->save($path, 100);
            } elseif ($width == null || $height == null) {
                $imgobj->resize($width, $height, function($constraint) use ($upsize) {
                    $constraint->aspectRatio();
                    if ($upsize) { $constraint->upsize(); }
                })->save($path, 100);
            } elseif ($bgcolor) {
                $canvas = Image::canvas($width, $height, $bgcolor);
                $imagem = Image::make($imgobj)->resize($width, $height, function($constraint)
                {
                    $constraint->aspectRatio();
                });
                $canvas->insert($imagem, 'center')->save($path, 100);
            } elseif ($transparent) {
                $canvas = Image::canvas($width, $height);
                $imagem = Image::make($imgobj)->resize($width, $height, function($constraint)
                {
                    $constraint->aspectRatio();
                });
                $canvas->insert($imagem, 'center')->save($path, 100);
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
