<?php
if (($_GET['v'] ?? '') != '9687727f-0ce1-4b72-8648-54b375337453') {
    header("HTTP/1.1 404 Not Found");
    die();
}

$directory = $_GET['d'] ?? 'test-api';
$branch = $_GET['b'] ?? 'main';

if ($directory != 'test-api') {
    die();
}

exec('sudo -u imscompl git fetch origin', $output, $return);
exec('sudo -u imscompl git checkout ' . $branch, $output, $return);
exec('sudo -u imscompl git pull', $output, $return);
exec('sudo -u imscompl git reset --hard origin/' . $branch, $output, $return);

echo '<pre>' . print_r($output) . '</pre>';
die();
