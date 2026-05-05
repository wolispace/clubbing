<?php
// load global app settings
$app = loadJson('_data.json');
$app["webRoot"] = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);

if (isset($_GET['j'])) {
  $data = json_decode($_REQUEST['j'], true);
  outputJson(handleData($data));
  exit;
} else {
  // regular page load, e.g. /?about-us
  $urlKeys = array_keys($_GET);
  $params = pageParams(["page" => $urlKeys[0] ?? '']);
  outputPage($params);
}

// --------------------------------------------------- 

function pageParams($params) {
  global $app;
  // page is the first param ?about etc.. Default page of blank is the 'home' page 
  $params['page'] = $params['page'] ?? '';
  switch ($params['page']) {
    case '': // no page so get homeplage content
      $params['content'] = formatClubList(getClubs());
      $params['template'] = 'home';
      $params['name'] = $app['name'];
      $params['footer'] = ''; 
      break;
    // new case for each special page   
    default: // page defined so get its content
      $params['template'] = $params['template'] ?? 'page';
      $params = getContent($params); 
      $params['footer'] = "<a href='{$app['webRoot']}'>Home</a>";
  };

  $params['version'] = rand(10, 99999);
  return $params;
}

function getContent($params) {
  global $app;
  $file = "{$app['clubFolder']}{$params['page']}.json";
  $data = loadJson($file);
  $params['name'] = $data['name'];
  return $params;
}

function outputPage($params) {
  $file = buildTemplatePath($params['template']);
  $html = file_get_contents($file);
  $html = renderTemplate($html, $params);
  print $html;
}

function buildTemplatePath($name) {
  global $app;
  return "{$app['templateFolder']}{$name}.html";
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
  // logIt("handling json data: " . json_encode($data));
  $json = array();
  switch ($data['action'] ?? '') {
    case 'clubList':
      $json = getClubs();
      break;
    case 'delete':
      $json = deleteSomething($data);
      break;     
  };
  return $json;
}

function getClubs() {
  global $app;
  $clubs = [];
  $files = glob("{$app['clubFolder']}*.json");
  foreach($files as $file) {
    $clubData = loadJson($file);
    $clubId = str_replace([$app['clubFolder'] , '.json'], '', $file);
    $clubs[$clubId] = ["name" => $clubData['name'], "tagline" => $clubData['tagline']];
  }
  return $clubs;
}

function formatClubList($clubs) {
  $clubList = '';
  foreach($clubs as $clubId => $clubData) {
    $clubList .= "<a href='?$clubId'>{$clubData['name']}</a>";
  }
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
  $str = preg_replace('/[^a-z0-9-_]/i', '', $str);
  return substr($str, 0, 20);
}

function outputJson($data) {
  header('Content-Type: application/json');
  echo json_encode($data);
}

function loadJson($file) {
  logIt("loading {$file}");
    return json_decode(file_get_contents($file), true);
}

function saveJson($data, $file) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
