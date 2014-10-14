<?php /*
This script is called in response to dropping a file for upload. A JSON formatted object
is posted as raw data, with a 'file' key containing a filename or URL and 'size' key
indicating the number of bytes in the file. There is also an optional 'label' key that
defaults to the filename (or basename, if it's a URL) and 'type' key that's used if the
mime type can't be determined from the file extension. The JSON response will include an
'error' key indicating any problem, or an 'upload' key containing a URL to POST the file
to. If the 'file' configuration option is 'local' then the URL will point to
import_upload.php, but if it's 's3' then the URL will point to an Amazon endpoint. In the
later case there will also be an 's3' key in the response containing authenticating
parameters to be passed along with the file, and a 'url' key pointing to import_api.php to
be requested if the upload is successful. 
*/

$response = array();
$log_responses = '';
$err = '';
$config = array();

$include = dirname(__FILE__) . '/include/'; // load utilities
if ((! $err) && (! @include_once($include . 'authutils.php'))) $err = 'Problem loading authentication utility script';
if ((! $err) && (! @include_once($include . 'idutils.php'))) $err = 'Problem loading id utility script';
if ((! $err) && (! @include_once($include . 'mimeutils.php'))) $err = 'Problem loading mime utility script';
if ((! $err) && (! @include_once($include . 'sigutils.php'))) $err = 'Problem loading signature utility script';

if (! $err) { // pull in configuration so we can log other errors
	$config = config_get();
	$err = config_error($config);
	$log_responses = $config['log_response'];
}
// autheticate the user (will exit if not possible)
if ((! $err) && (! auth_ok())) auth_challenge();

if (! $err) { // pull in other configuration and check for required input
	if (! $php_input = file_get_contents('php://input')) $err = 'JSON payload required';
	else if (! $request = @json_decode($php_input, TRUE)) $err = 'Could not parse JSON payload';
}
if (! $err) { 
	if ($config['log_request']) log_file(print_r($request, 1), $config);
	$type = (empty($request['type']) ? '' : $request['type']);
	$file_name = (empty($request['file']) ? '' : $request['file']);
	$file_label = (empty($request['label']) ? $file_name : $request['label']);
	$file_size = (empty($request['size']) ? 0 : $request['size']);
	if (! ($file_name && $file_size)) $err = 'Parameters file, size required ' . print_r($request, 1);
}
if (! $err) { // make sure we can determine mime type and extension from file name
	$extension = file_extension($file_name);
	$mime = mime_from_path($file_name);
	if (! $mime) $mime = $type; // user mime supplied by browser
	if (! ($mime && $extension)) $err = 'Could not determine mime type or extension';
}
if (! $err) { // make sure mime type is supported
	$type = mime_type($mime);
	switch($type) {
		case 'audio':
		case 'video':
		case 'image': break;
		default: $err = 'Only audio, image and video files supported';
	}
}
if (! $err) { // enforce size limit from configuration, if defined
	$max = (empty($config["max_meg_{$type}"]) ? '' : $config["max_meg_{$type}"]);
	if ($max) {
		$file_megs = round($file_size / (1024 * 1024));
		if ($file_megs > $max) $err = ($type . ' files must be less than ' . $max . ' meg');
	}
}
if (! $err) {
	$id = id_unique();
	$response['api'] = array();
	$response['data'] = array();
	if ('s3' == $config['file']) {
		$s3_options = array();
		$s3_options['bucket'] = $config['s3_bucket'];
		$path_media_url = $config['user_media_directory'];
		// remove bucket name from url if it's there
		if (substr($path_media_url, 0, strlen($config['s3_bucket'])) == $config['s3_bucket']) $path_media_url = substr($path_media_url, strlen($config['s3_bucket']) + 1);
		$path_media_url .=  auth_userid() . '/';
		$s3_options['path'] = $path_media_url . $id . '/' . $config['import_original_basename'] . '.' . $extension;
		$s3_options['mime'] = $mime;
		$s3_options['size'] = $file_size;
		$s3data = sig_s3_post($config['aws_secret_access_key'], $s3_options);
		$s3data['AWSAccessKeyId'] = $config['aws_access_key_id'];
		$response['data'] = $s3data;
		$response['endpoint'] = 'https://s3' . ($config['s3_region'] ? '-' . $config['s3_region'] : '') . '.amazonaws.com/'. $config['s3_bucket'];
		
	} else {
		// relative url from path site to path cgi - we assume the later is within the former!
		$response['endpoint'] = '/' . $config['callback_directory'] . 'import_upload.php';
		$response['data']['id'] = $id;
		$response['data']['type'] = $type;
		$response['data']['extension'] = $extension;
	}
	$response['api']['id'] = $id;
	$response['api']['type'] = $type;
	$response['api']['extension'] = $extension;
	$response['api']['label'] = $file_label;
}
if ($err) $response['error'] = $err;
else $response['ok'] = 1;
$json = json_encode($response);
print $json . "\n\n";
if ($log_responses) log_file($json, $config);
?>