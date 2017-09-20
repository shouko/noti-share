<?php

function err($code, $msg = '') {
  http_response_code($code);
  die($msg);
}

function http_get($url) {
  $opts = [
    'http' => [
      'method' => 'GET',
      'header' => [
        'User-Agent: PHP'
      ]
    ]
  ];
  $context = stream_context_create($opts);
  return file_get_contents($url, false, $context);
}

function filter_html($data) {
  return htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
}

function get_gist_text($gist_id) {
  if (!preg_match('/^[a-f0-9]{32}$/', $gist_id)) return err(400, 'Invalid Gist ID.');
  $data = json_decode(http_get('https://api.github.com/gists/'.$gist_id), 1);
  if (!isset($data['files']) || count($data['files']) < 1) return err(404, 'Gist not found.');
  return filter_html(reset($data['files'])['content']);
}

function get_youtube_data($vid) {
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $vid)) return err(400, 'Invalid YouTube ID.');
  $data = http_get('https://www.youtube.com/watch?v='.$vid);
  $data = explode('</title>', $data)[0];
  $data = explode('<title>', $data);
  if (count($data) < 2) return err(404, 'YouTube video not found.');
  return [
    'title' => filter_html($data[1]),
    'image' => 'https://i.ytimg.com/vi/'.$vid.'/maxresdefault.jpg'
  ];
}

function get_canonical_url($args, $keys) {
  $hostname = getenv('HOSTNAME');
  if (strlen($hostname) == 0) {
    $hostname = $_SERVER['SERVER_NAME'];
  }
  $suf = '';
  foreach ($keys as $key) {
    $suf .= '/'.$key.'/'.$args[$key];
  }
  return [
    'pre' => 'https://'.$hostname,
    'suf' => $suff
  ];
}

function parse_args() {
  $data = explode('/', $_SERVER['REQUEST_URI']);
  $length = count($data);
  if ($length < 5) return err(400);
  $args = [];
  $i = 1;
  while ($i < $length - 1) {
    $args[$data[$i]] = $data[$i + 1];
    $i += 2;
  }
  return $args;
}

function render($data) {
  return '<!DOCTYPE HTML><html>\
  <head>
  <title>'.$data['title'].'</title>
  <meta name="description" content="'.$data['description'].'" />
  <meta name="twitter:card" value="summary">
  <meta property="og:title" content="'.$data['title'].'" />
  <meta property="og:type" content="video" />
  <meta property="og:url" content="'.$data['url']['pre'].$data['url']['suf'].'" />
  <meta property="og:image" content="'.$data['image'].'" />
  <meta property="og:description" content="'.$data['description'].'" />
  </head>
  <body>
  <script type="text/javascript">document.location.href = "https://hyww.github.io/Noti/#'.$data['url']['suf'].'/view";</script>
  </body>
  </html>';
}

$args = parse_args();
if (!isset($args['y']) || !isset($args['g'])) return err(400, 'Invalid arguments.');

$yt = get_youtube_data($args['y']);
$gist = get_gist_text($args['g']);
$url = get_canonical_url($args, ['y', 'g']);

echo render([
  'title' => $yt['title'],
  'image' => $yt['image'],
  'description' => $gist,
  'url' => $url
]);