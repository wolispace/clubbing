<?php
// load global app settings
$app = loadJson('_data.json');
$app["webRoot"] = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);

$j = $_REQUEST['j'] ?? null;
if ($j !== null || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $j ? json_decode($j, true) : $_REQUEST;
    outputJson(handleData($data));
    exit;
}
// regular page load, e.g. /?about-us
$urlKeys = array_keys($_GET);
$params = pageParams(["page" => $urlKeys[0] ?? '']);
print buildHtml($params);


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
    $section['things'] = buildSubHtml($section['things'], 'thing');
    $section['host'] = getSelected($data['members'], $section['host']);
    $section['location'] = getSelected($data['locations'], $section['location']);
    $section = stripKeys($section, 'dateformat,members');
    $html .= buildHtml($section);
  }
  $html .= '</div>';
  return $html;
}

function getSelected($list, $selected) {
  if ($selected === '') {
    return '';
  }
  return $list[$selected];
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
  $counter = 0;
  foreach($list as $thing) {
    $thing['template'] = $template;
    $thing['id'] = $counter++;
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
    if (is_array($value)) {
      logIt($key);
    }
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
  $html = '';
  switch ($params['type']) {
    case 'page': 
      $data['template'] = 'edit_page';
      $data['page'] = $params['page'];
      $data['buttons'] = buildSubHtml($params['buttons'], 'dialog_button');
      $data = stripKeys($data, 'dateformat,members,sections,locations');
      $html = buildHtml($data);
      break;
    case 'section': 
      $section = $data['sections'][$params['section']];
      if (empty($section)) {
        // default section details like next date
        $section = ['date' => '02 May 2026'];
      }       
      $section = formatSection($section, $data, $params);
      $html = buildHtml($section);
      break;
    case 'thing': 
      $data['template'] = 'edit_thing';
      $data = stripKeys($data, 'dateformat,members,sections,locations');
      $html = buildHtml($data);
      break;
    default: // nothing to get
      $html = 'Nothing to do';
  };

  return ['html'=> $html];
}

function formatSection($section, $data, $params) {
  $section = array_merge($section, $params);
  $section['thingCaption'] = $data['thingCaption'];
  $section['date'] = fromYmd($params['section']);
  $section['hosts'] = buildOptions($data['members'], $section['host']); 
  $section['locations'] = buildOptions($data['locations'], $section['location']); 
  $section['buttons'] = buildSubHtml($params['buttons'], 'dialog_button');
  $section['things'] = buildSubHtml($section['things'], 'edit_thing');
  $section['template'] = 'edit_section';
  $section = stripKeys($section, 'dateformat,members,sections');
  return $section;
}

function buildOptions($list, $current) {
  $html = '<option></option>';
  $counter = 0;
  foreach( $list as $item) {
    if($counter == $current) {
      logIt("{$item} {$counter} {$current}");
    }
    $selected = $counter == $current ? 'selected' : '';
    $html .= "<option value=\"{$counter}\" {$selected}>{$item}</option>";
    $counter++;
  }
  return $html;
}

function buildButtons($buttons) {
  $html = '';
  foreach($buttons as $button) {
    $button['template'] = 'dialog_button';
    $html .= buildHtml($button);
  }
  return $html;
}

function saveDataFromEditing($params) {
  global $app;
  $file = "{$app['clubFolder']}{$params['page']}.json";
  $data = loadJson($file);
  if (empty($data)) {
    return ['error' => 'Club not found'];
  }
  $oldSectionId = $params['section'];
  if (empty($oldSectionId)) {
    // saving the page not section of it
    $data = array_merge($data, $params);
  } else {
    $newSectionId = toYmd($params['date']);
    $section = $data['sections']['section'][$oldSectionId] ?? [];
    // remove old section if the date (which is the key) has changed
    if (!empty($section) && $oldSectionId != $newSectionId) {
      unset($data[$oldSectionId]);
    }
    $params['things'] = removeBlankThings($params['things']);
    $params = stripKeys($params,'test,action,page,section');
    $data['sections'][$newSectionId] = $params;
  }

  saveJson($data, $file);
  return ["status" => "ok"];
}

function removeBlankThings($things) {
  $newThings = [];
  foreach ($things as $thing) {
    if (!empty($thing['title'])) {
      $newThings[] = $thing;
    }
  }
return $newThings;
}

// remove keys we dont want to save
function stripKeys($data, $keys) {
  return array_diff_key($data, array_flip(explode(',', $keys)));
}

function deleteSomething($params) {
  global $app;
  $file = "{$app['clubFolder']}{$params['page']}.json";
  $data = loadJson($file);
  if (empty($data)) {
    return ['error' => 'Club not found'];
  }
  unset($data['sections'][$params['section']]);
  saveJson($data, $file);
  logIt("deleted {$params['page']} {$params['section']}");
  return ["status" => "ok"];
}

// utilities ----------------------------------

function toYmd($str) {
  foreach (['d M Y', 'd/m/Y'] as $format) {
    $date = DateTime::createFromFormat($format, $str);
    if ($date) return $date->format('Ymd');
  }
}

function fromYmd($ymd) {
  $date = DateTime::createFromFormat('Ymd', $ymd);
  return $date->format('d M Y');
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
  return json_decode(file_get_contents($file), true);
}

function saveJson($data, $file) {
  logIt('save ' . $file . ' ' . json_encode($data));
  file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
