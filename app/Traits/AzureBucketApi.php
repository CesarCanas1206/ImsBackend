<?php
namespace App\Traits;

class AzureBucketApi
{
    public $message = '';
    public $contenttype = '';
    public $result = '';
    public $status = 0;

    private $accesskey = "jujI9Aj2JlpqA6BWH4agZnOrY4PUOflPzX/g96MD7pSOfY+wp2uSPo3ypPEjvhr+qMJJ3NxBh53m+AStTASEBA==";
    private $storageAccount = 'imsappstoragetest';
    private $containerName = '';

    public function __construct()
    {
        $this->setContainer(defined('IMS_WEBSITE') ? IMS_WEBSITE : '');
    }

    public function setContainer($container)
    {
        $this->containerName = $container;
    }

    public function getContainer()
    {
        return $this->containerName;
    }

    public function azureBucket($blobName, $arraysign = array(), $method = 'GET', $handle = false, $isFile = false)
    {
        $currentDate = gmdate("D, d M Y H:i:s T", time());
        $this->result = '';
        $version = '2019-12-12';
        if ($blobName == false) {
            $headerResource = "x-ms-date:$currentDate\nx-ms-version:" . $version;
        } else {
            $headerResource = "x-ms-blob-cache-control:max-age=3600\nx-ms-blob-type:BlockBlob\nx-ms-date:$currentDate\nx-ms-version:$version";
        }

        if ($blobName != false) {
            $blobName = ltrim(ltrim($blobName, './'), '/');
        }

        $resource = $this->containerName . ($blobName != false ? '/' . $blobName : '?restype=container');

        $urlResource = "/$this->storageAccount/" . $this->containerName . ($blobName != false ? '/' . $blobName : "\n" . 'restype:container');
        $destinationURL = "https://$this->storageAccount.blob.core.windows.net/" . $this->containerName . ($blobName != false ? '/' . $blobName : '?restype=container');

        //we still need the new line character even if the header option is null
        $initialsign = array();
        $initialsign['method'] = $method;
        $initialsign['encoding'] = '';
        $initialsign['language'] = '';
        $initialsign['length'] = '';
        $initialsign['md5'] = '';
        $initialsign['type'] = '';
        $initialsign['date'] = '';
        $initialsign['modified-since'] = '';
        $initialsign['match'] = '';
        $initialsign['none-match'] = '';
        $initialsign['unmodified-since'] = '';
        $initialsign['range'] = '';
        $initialsign['header'] = $headerResource;
        $initialsign['resource'] = $urlResource;
        $arraysign = array_merge($initialsign, $arraysign);
        $str2sign = implode("\n", $arraysign);

        //Hash-based Message Authentication Code (HMAC) constructed from the request and computed by using the
        //SHA256 algorithm, and then encoded by using Base64 encoding
        $sig = base64_encode(hash_hmac('sha256', urldecode(utf8_encode($str2sign)), base64_decode($this->accesskey), true));
        $authHeader = "SharedKey $this->storageAccount:$sig";

        $headers = [
            'Authorization: ' . $authHeader,
            'x-ms-blob-cache-control: max-age=3600',
            'x-ms-blob-type: BlockBlob',
            'x-ms-date: ' . $currentDate,
            'x-ms-version: ' . $version,
            'Content-Type: ' . $arraysign['type'],
            'Content-Length: ' . $arraysign['length'],
        ];

        if ($blobName === false) {
            $headers = [
                'Authorization: ' . $authHeader,
                'x-ms-date: ' . $currentDate,
                'x-ms-version: ' . $version,
                'Content-Length: 0',
            ];
        }

        $ch = curl_init($destinationURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($blobName === false) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($handle !== false) {
            if ($isFile) {
                curl_setopt($ch, CURLOPT_INFILE, $handle);
                curl_setopt($ch, CURLOPT_INFILESIZE, $arraysign['length']);
                curl_setopt($ch, CURLOPT_UPLOAD, true);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $handle);
            }
        }
        $result = curl_exec($ch);

        if ($result === false) {
            $this->message = curl_error($ch);
        }
        $this->contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return $result;

    }

    public function createContainer($containerName)
    {
        $this->setContainer($containerName);
        $this->getContainer();
        $result = $this->azureBucket(false, array(), 'PUT');
        return $result;
    }

    public function uploadFile($filetoUpload, $blobName, $sizes = [], $createContainer = true)
    {
        if ($createContainer) {
            $this->createContainer($this->getContainer());
        }
        $isBinary = preg_match('~[^\x20-\x7E\t\r\n]~', $filetoUpload) > 0;

        if ($isBinary) {
            $tmpfname = tempnam(sys_get_temp_dir(), 'tempfile');
            $temp = fopen($tmpfname, "w");
            fwrite($temp, $filetoUpload);
            $filetoUpload = stream_get_meta_data($temp)['uri'];

            $handle = fopen($filetoUpload, "r");
            $fileLen = filesize($filetoUpload);
            $isFile = true;
        } else if (!$isBinary && is_file($filetoUpload)) {
            $handle = fopen($filetoUpload, "r");
            $fileLen = filesize($filetoUpload);
            $isFile = true;
        } else {
            $handle = file_get_contents($filetoUpload);
            $fileLen = strlen($handle);
            $isFile = false;
        }

        foreach ($sizes as $size) {
            $this->resizeAndUpload($filetoUpload, $blobName, $size);
        }

        $contenttype = $contenttype ?? mime_content_type($filetoUpload);

        $arraysign = [];
        $arraysign['length'] = $fileLen;
        $arraysign['type'] = $contenttype;

        $result = true;
        $result = $this->azureBucket($blobName, $arraysign, 'PUT', $handle, $isFile);

        if ($result === false) {
            return $result;
        } else {
            $this->message = 'Operation completed without any errors';
            return true;
        }
    }

    public function resizeAndUpload($file, $blobName = '', $new_width = 150, $options = [])
    {
        $contentType = mime_content_type($file);
        if (is_file($file)) {
            if ($contentType == 'image/png') {
                $im = imagecreatefrompng($file);
            } else {
                $im = imagecreatefromjpeg($file);
            }
        } else {
            $file = str_replace(
                array(
                    'data:image/jpeg;',
                    'data:image/png;',
                    'base64,',
                ), '', $file);
            $file = base64_decode($file);
            $im = imagecreatefromstring($file);
        }
        $source_width = imagesx($im);
        $source_height = imagesy($im);
        $greaterw = $source_width > $source_height;
        $ratio = $source_height / $source_width;
        $ratiow = $source_width / $source_height;

        if ($greaterw) {
            if ($ratiow > 2.22) {
                $new_height = $ratio * $new_width;
            } else {
                if (isset($options['height'])) {
                    $new_height = $options['height'];
                    $new_width = $ratiow * $new_height;
                } else {
                    $new_height = $ratio * $new_width;
                }
            }
        } else
        if (isset($options['height']) && ($ratiow > 2.22 || !$greaterw)) {
            $new_height = $options['height'];
            $new_width = $ratiow * $new_height;
        } else {
            $new_height = $ratio * $new_width;
        }
        $resized = imagecreatetruecolor($new_width, $new_height);

        if (!isset($options['keepname'])) {
            $blobNameExploded = explode('.', $blobName);
            $blobNameExploded[count($blobNameExploded) - 2] .= 'x' . $new_width;
            $blobName = implode('.', $blobNameExploded);
        }

        $whitebackground = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $whitebackground);

        imagecopyresampled($resized, $im, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);
        ob_start();
        imagejpeg($resized);
        $contents = ob_get_clean();
        imagedestroy($im);
        $file = 'data:image/png;base64,' . base64_encode($contents);
        return $this->uploadFile($file, $blobName);
    }

    public function downloadFile($blobName)
    {
        $result = $this->azureBucket($blobName, array(), 'GET');

        if ($result === false) {
            return $result;
        } else {
            $this->message = 'Operation completed without any errors';
            $this->result = $result;
            $this->status = (!stristr($result, '<error>'));
            return true;
        }
    }

    public function copyFile($blobName, $fromContainer = '')
    {
        $arraysign = [];
        $method = 'PUT';
        $handle = false;
        $isFile = false;

        // $currentDate = gmdate("D, d M Y H:i:s T", time());
        $currentDate = gmdate('D, d M Y H:i:s \G\M\T');
        $this->result = '';
        // $canonicalizedHeaders = "x-ms-copy-source:https://".$account_name.".blob.core.windows.net/".$sourcecontainer."/".$blobname."\nx-ms-date:$date\nx-ms-version:2015-04-05";
        $version = '2019-12-12';
        $version = '2015-04-05';
        $headerResource = "x-ms-blob-cache-control:max-age=3600\nx-ms-blob-type:BlockBlob\nx-ms-date:$currentDate\nx-ms-version:$version";

        // if ($blobName != false) {
        $blobName = ltrim(ltrim($blobName, './'), '/');
        // }

        $headerResource = "x-ms-copy-source:https://{$this->storageAccount}.blob.core.windows.net/${fromContainer}/${blobName}\nx-ms-date:${currentDate}\nx-ms-version:${version}";
        echo $headerResource;
        $resource = $fromContainer . '/' . $blobName;
        // $headerResource = $resource;

        $urlResource = "/$this->storageAccount/" . $this->containerName . '/' . $blobName;

        $destinationURL = "https://$this->storageAccount.blob.core.windows.net/" . $this->containerName . '/' . $blobName;

        $copyURL = "https://$this->storageAccount.blob.core.windows.net/" . $fromContainer . '/' . $blobName;

        //we still need the new line character even if the header option is null
        $initialsign = array();
        $initialsign['method'] = $method;
        $initialsign['encoding'] = '';
        $initialsign['language'] = '';
        $initialsign['length'] = '';
        $initialsign['md5'] = '';
        $initialsign['type'] = '';
        $initialsign['date'] = '';
        $initialsign['modified-since'] = '';
        $initialsign['match'] = '';
        $initialsign['none-match'] = '';
        $initialsign['unmodified-since'] = '';
        $initialsign['range'] = '';
        $initialsign['header'] = $headerResource;
        $initialsign['resource'] = $urlResource;
        $arraysign = array_merge($initialsign, $arraysign);
        $str2sign = implode("\n", $arraysign);

        //Hash-based Message Authentication Code (HMAC) constructed from the request and computed by using the
        //SHA256 algorithm, and then encoded by using Base64 encoding
        $sig = base64_encode(hash_hmac('sha256', urldecode(utf8_encode($str2sign)), base64_decode($this->accesskey), true));
        $authHeader = "SharedKey $this->storageAccount:$sig";

        $arraysign['length'] = 0;

        $headers = [
            'x-ms-copy-source: ' . $copyURL,
            'x-ms-requires-sync: true',
            'Authorization: ' . $authHeader,
            // 'x-ms-blob-cache-control: max-age=3600',
            'Accept:application/json;odata=nometadata',
            'Accept-Charset:UTF-8',
            'x-ms-blob-type: BlockBlob',
            'x-ms-date: ' . $currentDate,
            'x-ms-version: ' . $version,
            // 'Content-Type: ' . $arraysign['type'],
            'Content-Length: ' . $arraysign['length'],
        ];

        $ch = curl_init($destinationURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $result = curl_exec($ch);

        if ($result === false) {
            $this->message = curl_error($ch);
        }
        $this->contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return $result;
    }

    public function deleteFile($blobName, $sizes = array())
    {

        foreach ($sizes as $size) {
            $blobNameExploded = explode('.', $blobName);
            $blobNameExploded[count($blobNameExploded) - 2] .= 'x' . $size;
            $sizeBlobName = implode('.', $blobNameExploded);
            $result = $this->azureBucket($sizeBlobName, array(), 'DELETE');
        }

        $result = $this->azureBucket($blobName, array(), 'DELETE');

        if ($result === false) {
            return $result;
        } else {
            $this->message = 'File was deleted';
            return true;
        }
    }

}
