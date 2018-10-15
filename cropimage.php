<?php

namespace App\Helpers;

use Image;

class CropImage
{
    public static function make($input, $options)
    {
        self::validate($input, $options);

        $options = self::normalizeOptions($options);

        $imageFile = request()->file($input);
        $imageName = self::getName($imageFile, $options);

        foreach($options as $config) {
            self::checkPathDir($config['path']);

            $imageInstance = Image::make($imageFile->getRealPath());

            $width  = $config['width'];
            $height = $config['height'];
            $path   = $config['path'].$imageName;
            $upsize = self::upsizeConstraint($config);

            if ($width == null && $height == null) {
                self::saveOriginal($path, $imageInstance);
            } elseif ($width == null || $height == null) {
                self::saveResize(
                    $path, $imageInstance, $width, $height, $upsize
                );
            } elseif ($color = self::getColor($config)) {
                self::saveColor(
                    $path, $imageInstance, $width, $height, $color, $upsize
                );
            } elseif (self::isTransparent($config)) {
                self::saveTransparent(
                    $path, $imageInstance, $width, $height, $upsize
                );
            } else {
                self::saveFit(
                    $path, $imageInstance, $width, $height, $upsize
                );
            }

            $imageInstance->destroy();
        }

        return $imageName;
    }

    private static function validate($input, $options)
    {
        if (!request()->hasFile($input)) {
            throw new \Exception('CropImage: File not found.');
        }

        if (!$options) {
            throw new \Exception('CropImage: Options missing.');
        }
    }

    public static function normalizeOptions($options) {
        return !is_array(array_values($options)[0]) ? array($options) : $options;
    }

    public static function hasTransparent($options) {
        foreach ($options as $config) {
            if (array_key_exists('transparent', $config)) {
                return true;
            }
        }

        return false;
    }

    public static function getName($image, $options)
    {
        $extension = self::hasTransparent($options)
            ? 'png'
            : $image->getClientOriginalExtension();

        $name  = str_slug(
            pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
        );
        $name .= '_'.date('YmdHis');
        $name .= str_random(10);
        $name .= '.'.$extension;

        return $name;
    }

    public static function checkPathDir($path)
    {
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0777, true);
        }
    }

    public static function upsizeConstraint($config)
    {
        return array_key_exists('upsize', $config) && $config['upsize'];
    }

    public static function isTransparent($config)
    {
        return array_key_exists('transparent', $config) && $config['transparent'];
    }

    public static function getColor($config)
    {
        return array_key_exists('color', $config) ? $config['color'] : false;
    }

    public static function saveOriginal($path, $image)
    {
        return $image->save($path, 100);
    }

    public static function saveResize($path, $image, $width, $height, $upsize)
    {
        return $image->resize($width, $height, function($constraint) use ($upsize) {
            $constraint->aspectRatio();
            if ($upsize) { $constraint->upsize(); }
        })->save($path, 100);
    }

    public static function saveColor($path, $image, $width, $height, $color, $upsize)
    {
        $canvas = Image::canvas($width, $height, $color);
        $image  = Image::make($image)->resize($width, $height, function($constraint) use ($upsize)
        {
            $constraint->aspectRatio();
            if ($upsize) { $constraint->upsize(); }
        });

        return $canvas->insert($image, 'center')->save($path, 100);
    }

    public static function saveTransparent($path, $image, $width, $height, $upsize)
    {
        $canvas = Image::canvas($width, $height);
        $image  = Image::make($image)->resize($width, $height, function($constraint) use ($upsize)
        {
            $constraint->aspectRatio();
            if ($upsize) { $constraint->upsize(); }
        });

        return $canvas->insert($image, 'center')->save($path, 100);
    }

    public static function saveFit($path, $image, $width, $height, $upsize)
    {
        return $image->fit($width, $height, function ($constraint) use ($upsize)
        {
            if ($upsize) { $constraint->upsize(); }
        })->save($path, 100);
    }
}
