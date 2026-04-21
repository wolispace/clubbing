<?php

$clubFolder = __DIR__ . '/clubs/';

$clubId = $_REQUEST['c'] ?? '';
$data = $_REQUEST['d'] ?? '';
$back = $_REQUEST['b'] ?? date('Ymd');
$clubId = cleanString($clubId);

logIt("clubId: {$clubId}, clubFolder: {$clubFolder}");
outputPage(time());

function getClubs() {
    $clubs = [];
    $files = glob('{$clubFolder}_*.json');
    logIt("files: " . json_encode($files));
    foreach($files as $file) {
        $clubData = json_decode(file_get_contents($file), true);
        $clubId = str_replace(["{$clubFolder}_", '.json'], '', $file);
        $clubs[$clubId] = $clubData['name'];
    }
    return $clubs;
}

function outputPage($v) {

    $clubs = getClubs();
    $clubList = $clubs == [] ? '' : 'Pick a club' ;

    $files = glob('_*.json');
    foreach($clubs as $clubId => $clubData) {
      $clubList .= "<a href='?$clubId'>{$clubData['name']}</a>";
    }

    $htmlSource = file_get_contents('home.html');
    $htmlSource = str_replace('{$clubList}', $clubList, $htmlSource);
    $htmlSource = str_replace('{$v}', $v, $htmlSource);
    print $htmlSource;

}


// utilities

function toYmd($str) {
    foreach (['d M Y', 'd/m/Y'] as $format) {
        $date = DateTime::createFromFormat($format, $str);
        if ($date) return $date->format('Ymd');
    }
}

function logIt($str) {
  $dateTime = date('Ymd H:i:s');
  file_put_contents('_log.txt', "{$dateTime},{$_SERVER['REMOTE_ADDR']},{$str}\n", FILE_APPEND | LOCK_EX);
}

function cleanString($str) {
  $str = preg_replace('/[^a-z0-9]/i', '', $str);
  return substr($str, 0, 15);
}

function outputJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
}
