<?php /*
This script forces authentication and then redirects to index.html
*/

$response = array();
$err = '';
$config = array();

if (! @include_once(dirname(__FILE__) . '/php/include/loadutils.php')) $err = 'Problem loading utility script';
if ((! $err) && (! load_utils('auth','data'))) $err = 'Problem loading utility scripts';

if (! $err) { // pull in configuration so we can log other errors
  $config = config_get();
  $err = config_error($config);
}
// autheticate the user (will exit if not possible)
if ((! $err) && (! auth_ok())) auth_challenge($config);

header("Location: ./index.html");
