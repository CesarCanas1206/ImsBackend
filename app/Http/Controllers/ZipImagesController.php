<?php
namespace App\Http\Controllers;

use App\Traits\AzureBucketApi;
use Illuminate\Http\Request;

class ZipImagesController extends APIController
{
    public function getTempPath($url, $tmpFile)
    {
        $parsedUri = parse_url($url);
        $uri = $parsedUri['path'] ?? '';

        [$dir, $storage, $container, $file] = explode('/', trim($uri, '/'), 4);
        $blobName = preg_replace('#(\?.*)?$#', '', $file);

        $azure = new AzureBucketApi();
        $azure->setContainer($container);
        $azure->downloadFile($blobName);

        if (empty($azure->status)) {
            return '';
        }

        // $tmpFile = \tmpfile();
        fwrite($tmpFile, $azure->result);
        $path = stream_get_meta_data($tmpFile)['uri'];
        return $path;
    }

    public function zipImages(Request $request)
    {
        $files = $request->photos;

        $zip = new \ZipArchive();
        $tmpDir = sys_get_temp_dir();
        $zipFile = tempnam($tmpDir, "ZIP");
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $tmpFiles = [];

        foreach ($files as $key => $url) {
            $tmpFiles[$key] = \tmpfile();
            $parts = explode('/', $url);
            $blobName = end($parts);
            $path = $this->getTempPath($url, $tmpFiles[$key]);
            $zip->addFile($path, $blobName);
        }
        $zip->close();

        $fileName = 'photos.zip';
        return response()->download($zipFile, $fileName);
    }
}
