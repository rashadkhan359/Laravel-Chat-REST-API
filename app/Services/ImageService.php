<?php

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * @param File | string
     * @return array
     */
    public function storeImage($file, $path)
    {
        $ext = $file->getClientOriginalExtension();
        $fileName = 'media_' . uniqid() . '.' . $ext;

        $thumbnail = Image::make($file->getRealPath())->fit(150, 150);

        $image = Image::make($file->getRealPath())->fit(1000, 1000);

        Storage::disk('public')->put($path . '/' . $fileName, $image->stream());

        Storage::disk('public')->put($path . '/thumbnails/' . $fileName, $thumbnail->stream());

        // Return the file path for further use
        return [
            'image' => $path . '/' . $fileName,
            'thumbnail' =>  $path . "/thumbnails/" . $fileName,
        ];
    }


    /**
     * Remove the image
     * @param string
     * @return null
     */
    public function destroyImage($filePath)
    {

        $fullPath = public_path($filePath);

        if (File::exists($fullPath)) {
            // Delete the file from the server
            File::delete($fullPath);

            File::delete($fullPath . '/thumbnails');
        }
    }
}
