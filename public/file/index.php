<?php
require __DIR__ . '/../../vendor/autoload.php';

use App\Traits\AzureBucketApi;

$parsedUri = parse_url($_SERVER['REQUEST_URI']);
$uri = $parsedUri['path'] ?? '';
parse_str($parsedUri['query'] ?? '', $query);

[$dir, $storage, $container, $file] = explode('/', trim($uri, '/'), 4);
$blobName = preg_replace('#(\?.*)?$#', '', $file);

$localFile = __DIR__ . '../../../storage/app/public/' . $container . '/' . $file;
if (is_file($localFile)) {
    $contentType = mime_content_type($localFile);
    $file = file_get_contents($localFile);
} else {
    $azure = new AzureBucketApi();
    $azure->setContainer($container);
    $azure->downloadFile($blobName);

    if (empty($azure->status)) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    $contentType = $azure->contenttype;
    $file = $azure->result;
}

if (!empty($query['w']) || !empty($query['h'])) {
    $isPng = strstr($contentType, 'png');
    function resizeImage($file, $w, $h, $crop = false)
    {
        global $isPng, $query;
        $file = 'data://application/octet-stream;base64,' . base64_encode($file);
        list($width, $height) = getimagesize($file);
        $ratio = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = abs(ceil($width - ($width * abs($ratio - $w / $h))));
            } else {
                $height = abs(ceil($height - ($height * abs($ratio - $w / $h))));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w / $h > $ratio) {
                $newwidth = $h * $ratio;
                $newheight = $h;
            } else {
                $newheight = $w / $ratio;
                $newwidth = $w;
            }
        }

        if ($isPng) {
            $src = imagecreatefrompng($file);
        } else {
            $src = imagecreatefromjpeg($file);
        }
        $dst = imagecreatetruecolor($newwidth, $newheight);
        if ($isPng) {
            $black = imagecolorallocate($dst, 255, 255, 255);
            // Make the background transparent
            imagecolortransparent($dst, $black);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        $exif = exif_read_data($file);
        if (!empty($exif['Orientation'])) {
            $ort = $exif['Orientation'];
            switch ($ort) {
                case 3:
                    $query['r'] = 90;
                    break;
                case 6:
                    $query['r'] = 270;
                    break;
            }

        }

        if (isset($query['r'])) {
            $degrees = $query['r'] ?? 270;
            $dst = imagerotate($dst, $degrees, 0);
        }

        return $dst;
    }
    ob_start();
    $image = resizeImage($file, $query['w'] ?? 600, $query['h'] ?? $query['w'] ?? 400, !empty($query['c']));
    if ($isPng) {
        imagepng($image);
    } else {
        imagejpeg($image, null, 100);
    }
    $file = ob_get_clean();
}

header('Content-Type: ' . $contentType);
header("Access-Control-Allow-Origin: *");

echo $file;
