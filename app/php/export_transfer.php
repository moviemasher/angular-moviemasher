<?php /*
This script receives the rendered mash from the Transcoder API as a video file in $_FILES. 
If the file is okay, it's moved to the directory named $id in the user's directory. If an
error is encountered a 400 header is returned and it is logged, if possible.
*/
$err = '';
$config = array();
$include = dirname(__FILE__) . '/include/'; // load utilities
if ((! $err) && (! @include_once($include . 'archiveutils.php'))) $err = 'Problem loading archive utility script';
if ((! $err) && (! @include_once($include . 'authutils.php'))) $err = 'Problem loading authentication utility script';
if ((! $err) && (! @include_once($include . 'idutils.php'))) $err = 'Problem loading id utility script';
if (! $err) { // pull in configuration so we can log other errors
	$config = config_get();
	$err = config_error($config);
}
if (! $err) { // see if the user is authenticated (does not redirect or exit)
	if (! auth_ok_callback($config)) $err = 'Unauthenticated access';
}
if (! $err) { // pull in other configuration and check for required input
	$id = (empty($_REQUEST['id']) ? '' : $_REQUEST['id']);
	$job = (empty($_REQUEST['job']) ? '' : $_REQUEST['job']);
	$uid = (empty($_REQUEST['uid']) ? '' : $_REQUEST['uid']);
	if (! ($uid && $id && $job)) $err = "Parameter uid, id, job required";
	$path_media = $config['user_media_directory'];
	$path_media .=  $uid . '/';
	$media_dir = $config['web_root_directory'];
	$media_dir .=  $path_media . $id . '/';
	$path_without_extension = $media_dir . $job;
}
if ((! $err) && empty($_FILES)) { // make sure $_FILES is set and has item
	$err = 'No files supplied';
}
if (! $err) { // make sure first item in $_FILES is valid
	foreach($_FILES as $k => $v) {
		$file = $_FILES[$k];
		break;
	}
	if (! $file) $err = 'No file supplied';
}
if (! $err) { // make sure there wasn't a problem with the upload
	if (! empty($file['error'])) $err = 'Problem with your file: ' . $file['error'];
	elseif (! is_uploaded_file($file['tmp_name'])) $err = 'Not an uploaded file';
}
if (! $err) { // make sure file extension is valid
	$file_name = $file['name'];
	$file_ext = file_extension($file_name);
	if ($file_ext == 'tgz') {
		set_time_limit(0);
		$tmp_path = $config['temporary_directory'] . id_unique();
		$archive_dir = $tmp_path . '/';
		if (! archive_extract($file['tmp_name'], $archive_dir, $config)) $err = 'Could not extract to ' . $archive_dir;
		else { // move select files from the archive to media directory
		if (! file_move_extension('jpg', $archive_dir, $media_dir, $config)) $err = 'Could not move jpg files from ' . $archive_dir . ' to ' . $media_dir;
		else if (! file_move_extension($config['export_audio_extension'], $archive_dir, $media_dir, $config)) $err = 'Could not move ' . $config['export_audio_extension'] . ' files from ' . $archive_dir . ' to ' . $media_dir;
		else if (! file_move_extension($config['export_extension'], $archive_dir, $media_dir, $config)) $err = 'Could not move ' . $decoder_extension . ' files from ' . $archive_dir . ' to ' . $media_dir;
		// remove the temporary directory we created, and any remaining files (there shouldn't be any)
		file_dir_delete_recursive($tmp_path);
		$path .= 
	} else {
		if (($file_ext != $config['export_audio_extension']) && ($file_ext != $config['export_extension'])) {
			$err = 'invalid file extension ' . $file_ext;
		} else {
			$path = $path_without_extension . '.' . $file_ext;
			if (! file_move_upload($file['tmp_name'], $path)) $err = 'Problem moving uploaded file';
		}
	}
}
if (! $err) { // make sure we actually moved what we needed to, and change its permissions
	clearstatcache();
	$path = $path_without_extension . '.' . $config['export_extension'];
	if (! file_exists($path)) {
		$path = $media_dir . $job . '.' . $config['export_audio_extension'];
		if (! file_exists($path)) $err = 'Did not generate file ' . $path;
	}
}
if ($err) {
	header('HTTP/1.1: 400 Bad Request');
	header('Status: 400 Bad Request');
	print $err;
	log_file($err, $config);
}
?>