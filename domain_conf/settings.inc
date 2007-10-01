<?php

/**
 * Domain Conf module routine -- add this to settings.php
 */
// process where we are
$subdomain = explode('.', implode('.', array_reverse(explode(':',rtrim($_SERVER['HTTP_HOST'],'.')))));

$url = parse_url($db_url); 
if (isset($db_url['default'])) {
  $url = parse_url($db_url['default']);
}

// Decode url-encoded information in the db connection string
$url['user'] = urldecode($url['user']);
// Test if database url has a password.
if(isset($url['pass'])) {
  $url['pass'] = urldecode($url['pass']);
}
else {
  $url['pass'] = '';
}
$url['host'] = urldecode($url['host']);
$url['path'] = urldecode($url['path']);

// Allow for non-standard MySQL port.
if (isset($url['port'])) {
  $url['host'] = $url['host'] .':'. $url['port'];
}
// chop the leading slash
$db_name = substr($url['path'], 1);

// dummy data
$data = array();

switch ($url['scheme']) {
  case 'mysql':
    $connect = mysql_connect($url['host'], $url['user'], $url['pass']);  
    mysql_select_db($db_name);
    $query = sprintf("SELECT domain_id, subdomain, sitename FROM domain WHERE subdomain = '%s'", mysql_real_escape_string($subdomain[0])); 
    $result = mysql_query($query);
    $data = mysql_fetch_array($result);
    mysql_free_result($result);
    if (!empty($data)) {
      global $_domain;
      $_domain = $data;
      $query = sprintf("SELECT settings FROM domain_conf WHERE domain_id = %d", $_domain['domain_id']);
      $result = mysql_query($query);
      $settings = mysql_fetch_array($result);
      mysql_free_result($result);
      if (!empty($settings)) {
        $conf = unserialize($settings['settings']);
      }
    }
    mysql_close($connect);
    break;

  case 'mysqli':
    $connect = mysqli_connect($url['host'], $url['user'], $url['pass'], $db_name);  
    $query = sprintf("SELECT domain_id, subdomain, sitename FROM domain WHERE subdomain = '%s'", mysql_real_escape_string($subdomain[0])); 
    $result = mysqli_query($connect, $query);
    $data = mysqli_fetch_array($result);
    mysqli_free_result($result);
    if (!empty($data)) {
      global $_domain;
      $_domain = $result;
      $query = sprintf("SELECT settings FROM domain_conf WHERE domain_id = %d", $_domain['domain_id']);
      $result = mysqli_query($connect, $query);
      $settings = mysqli_fetch_array($result);
      mysqli_free_result($result);
      if (!empty($settings)) {
        $conf = unserialize($settings['settings']);
      }
    }
    mysqli_close($connect);
    break;

  case 'pgsql':
    $connect = pg_connect('host='. $url['host'] .' dbname='. $db_name .' user='. $url['user'] .' password='. $url['pass']);
    $query = sprintf("SELECT domain_id, subdomain, sitename FROM domain WHERE subdomain = '%s'", mysql_real_escape_string($subdomain[0])); 
    $result = pg_query($query);
    $data = pg_fetch_array($result);
    pg_free_result($result);
    if (!empty($data)) {
      global $_domain;
      $_domain = $result;
      $query = sprintf("SELECT settings FROM domain_conf WHERE domain_id = %d", $_domain['domain_id']);
      $result = pg_query($query);
      $settings = pg_fetch_array($result);
      pg_free_result($result);
      if (!empty($settings)) {
        $conf = unserialize($settings['settings']);
      }
    }    
    pg_close($connect);
    break;
}