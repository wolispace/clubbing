<?php
const CLUB_FOLDER = __DIR__ . '/club/';
const TEMPLATE_FOLDER = __DIR__ . '/template/';

if (isset($_GET['j'])) {
  $data = json_decode($_REQUEST['j'], true);
  outputJson(handleData($data));
  exit;
} else {
  // regular page load, e.g. /?about-us
  $urlKeys = array_keys($_GET);
  $params = [
    "template" => $urlKeys[0] ?? "page",
    "version" => rand(10, 99999),
    "sitename" => "Clubbing",
    "footer" => '',
    "content" => formatClubList(getClubs())
  ];

  outputPage($params);
}

// --------------------------------------------------- 

function outputPage($params) {
  logIt("content " . json_encode($params));
  $file = buildTemplatePath($params['template']);
  logIt("loading template {$file}");
  $html = file_get_contents($file);
  $html = renderTemplate($html, $params);
  print $html;
}

function buildTemplatePath($name) {
  return TEMPLATE_FOLDER . "{$name}.html";
}

function renderTemplate($html, $params) {
  foreach ($params as $key => $value) {
    $html = str_replace("{{{$key}}}", $value, $html);
  }
  return $html;
}

// $data is a json object with action=something and other params
// this always returns a json object
function handleData($data) {
  logIt("handling json data: " . json_encode($data));
  $json = array();
  switch ($data['action'] ?? '') {
    case 'list':
      $json = getClubs();
      break;
    case 'delete':
      $json = deleteSomething($data);
      break;     
  };
  return $json;
}

function getClubs() {
  $clubs = [];
  $files = glob(CLUB_FOLDER . '*.json');
  foreach($files as $file) {
    $clubData = json_decode(file_get_contents($file), true);
    logIt("clubData {$clubData['name']} " . json_encode($clubData));
    $clubId = str_replace([CLUB_FOLDER , '.json'], '', $file);
    $clubs[$clubId] = ["name" => $clubData['name'], "tagline" => $clubData['tagline']];
  }
  return $clubs;
}

function formatClubList($clubs) {
  $clubList = '';
  foreach($clubs as $clubId => $clubData) {
    $clubList .= "<a href='?$clubId'>{$clubData['name']}</a>";
  }
  logIt("cliblist {$clubList} " . json_encode($clubs));
  return $clubList;
}

function deleteSomething() {
  //
}

// utilities ----------------------------------

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
