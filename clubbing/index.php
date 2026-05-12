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
  print buildHtml($params);
}

// --------------------------------------------------- 

function pageParams($params) {
  global $app;
  // page is the first param ?about etc.. Default page of blank is the 'home' page 
  $params['page'] = $params['page'] ?? '';
  switch ($params['page']) {
    case '': // no page so get homepage content
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
  if (empty($data)) {
    $params['name'] = 'Club not found';
    $params['content'] = '';   
  } else {
    $params['name'] = $data['name'];
    $params['content'] = formatSections($data);
  }
  return $params;
}

function formatSections($data) {
  global $app;
  $html = '<div class="sections">';
  // loop through the events and build them from html templates
  foreach($data['sections'] as $sectionId => $section) {
    $section['sectionId'] = $sectionId;
    $section['template'] = 'section';
    $section = buildDateBits($sectionId, $section);
    $section['thingHtml'] = buildSubHtml($section['things'], 'thing');
    $section['showlocation'] = buildLocation($data['locations'], $section);
    $html .= buildHtml($section);
  }
  $html .= '</div>';
  return $html;
}

function buildLocation($locations, $section) {
  return $locations[$section['location']];
}

function buildDateBits($ymd, $section) {
  $date = DateTime::createFromFormat('Ymd', $ymd);
  $dateBits = explode(' ', $date->format('D j M'));
  $section['weekday'] = $dateBits[0];
  $section['monthday'] = $dateBits[1];
  $section['month'] = $dateBits[2];  
  return $section;
}

function buildSubHtml($list, $template) {
  global $app;
  $html = '';
  foreach($list as $thing) {
    $thing['template'] = $template;
    $html .= buildHtml($thing);
  }
  return $html;
}
    
function buildHtml($params) {
  $file = buildTemplatePath($params['template']);
  $html = file_get_contents($file);
  $html = renderTemplate($html, $params);
  // remove all left-over {{vars}}
  $html = preg_replace('/{{\w+}}/', '', $html);
  return $html;
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
    case 'load':
      $json = loadDataForEditing($data);
      break;
    case 'save': // builder editing will be form post to handle images and lots of content
      $json = saveDataFromEditing($data);
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

function loadDataForEditing($params) {
 global $app;
  $file = "{$app['clubFolder']}{$params['page']}.json";
  $data = loadJson($file);
  if (empty($data)) {
    return ['error' => 'Club not found'];
  }
  $section = $data['sections'][$section['sectionId']];
  if (empty($section)) {
    // default section details like next date
    $section = ['date' => '01 May 2026'];
  } 
  $section['sectionId'] = $params['section'];

  $section['template'] = 'edit_section';
  $section['buttons'] = buildSubHtml($params['buttons'], 'dialog_button');
  $section['things'] = buildSubHtml($params['things'], 'edit_things');
  return ['html'=> buildHtml($section)];
}

function buildButtons($buttons) {
  $html = '';
  foreach($buttons as $button) {
    $button['template'] = 'dialog_button';
    $html .= buildHtml($button);
  }
  return $html;
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
