<?php

include_once(dirname(__FILE__) . '/loadutils.php');
load_utils('file','date','json');


if (! function_exists('sig_s3_post')) {
	function sig_s3_post($secret_access_key, $options = array()){
		$s3data = array();
		if ( ! ( empty($options['mime']) || empty($options['path'])) )
		{
			if (empty($options['acl'])) $options['acl'] = 'public-read';
			$policy = array();
			$policy['expiration'] = gmdate(DATE_FORMAT_TIMESTAMP, strtotime('+ 1 hour'));
			$policy['conditions'] = array();
			$policy['conditions'][] = array('eq', '$bucket', $options['bucket']);
			$policy['conditions'][] = array('eq', '$key', $options['path']);
			$policy['conditions'][] = array('eq', '$acl', $options['acl']);
			$policy['conditions'][] = array('eq', '$Content-Type', $options['mime']);
			$policy['conditions'][] = array('content-length-range', $options['size'], $options['size']);
		
			$policy = base64_encode(stripslashes(json_encode($policy)));
			$s3data['key'] = $options['path'];
			$s3data['acl'] = $options['acl'];
			$s3data['policy'] = $policy;
			$s3data['Content-Type'] = $options['mime'];
			$s3data['signature'] = sig_access_key($secret_access_key, $policy);
		}
		return $s3data;
	}
}
if (! function_exists('sig_version_four')) {
	function sig_version_four($secret_access_key, $url, $data, $headers, $region, $method = 'get') {
		$params = array();
		uksort($data, 'strnatcmp');
		foreach($data as $k => $v) {
			$params[] = sig_encode2($k) . '=' . urlencode($v);
		}
		$payload = (implode('&', $params));
		$hexencode_hash_payload = sig_hexencode(sig_hash($payload));
		//print "~RequestPayload~\n$payload\n";
		//print "~HexEncode(Hash(RequestPayload))~\n$hexencode_hash_payload\n";

		$parsed_url = parse_url($url);
		$host = strtolower($parsed_url['host']);
		$canonical_lines = array();
		$canonical_lines[] = strtoupper($method);
		$canonical_lines[] = $parsed_url['path'];
		$canonical_lines[] = (empty($parsed_url['query']) ? '' : $parsed_url['query']);
	
		if (empty($headers['Host'])) $headers['Host'] = $host;
		if (empty($headers['X-Amz-Date'])) $headers['X-Amz-Date'] = gmdate(DATE_FORMAT_ISO8601_BASIC);
	
		$header_keys = array_keys($headers);
		usort($header_keys, 'strnatcmp');
		foreach($header_keys as $k){
			$v = $headers[$k];
			$canonical_lines[] = strtolower(sig_encode2($k)) . ':' . sig_encode2($v);
		}
		$canonical_lines[] = '';
		$canonical_lines[] = strtolower(implode(';', $header_keys)); // semicolon separated names of headers alphabetized
		$canonical_lines[] = $hexencode_hash_payload;
		$canonical_request = implode("\n", $canonical_lines);
		$hexencode_hash_canonical = sig_hexencode(sig_hash($canonical_request));
		//print "~CanonicalRequest~\n$canonical_request\n";
		//print "~HexEncode(Hash(CanonicalRequest))~\n$hexencode_hash_canonical\n";

		$date_str = substr($headers['X-Amz-Date'], 0, 8);
		if (! $region) $region = 'us-east-1';
		$service = 'sqs';
	
		$string_to_sign_lines = array();
		$string_to_sign_lines[] = 'AWS4-HMAC-SHA256';
		$string_to_sign_lines[] = $headers['X-Amz-Date'];
		$string_to_sign_lines[] = "$date_str/$region/$service/aws4_request";
		$string_to_sign_lines[] = $hexencode_hash_canonical;
	
		$method = 'sha256';
		$key = 'AWS4' . $secret_access_key;
		$key = hash_hmac($method, $date_str, $key, TRUE);
		$key = hash_hmac($method, $region, $key, TRUE);
		$key = hash_hmac($method, $service, $key, TRUE);
		$key = hash_hmac($method, 'aws4_request', $key, TRUE);
	
		$string_to_sign = implode("\n", $string_to_sign_lines);
		$hexencode_sig = sig_hexencode(sig_sign($key, $string_to_sign));
		//print "~StringToSign~\n$string_to_sign\n";
		//print "~HexEncode(Sign(StringToSign))~\n$hexencode_sig\n";
		return $hexencode_sig;
	}
}
if (! function_exists('sig_hexencode')) {
	function sig_hexencode($s){
		return bin2hex($s);
	}
}
if (! function_exists('sig_hash')) {
	function sig_hash($s, $method = 'sha256'){
		return hash($method, $s, TRUE);
	}
}
if (! function_exists('sig_sign')) {
	function sig_sign($secret_access_key, $s, $method = 'sha256'){
		return hash_hmac($method, $s, $secret_access_key, TRUE);
	}
}
if (! function_exists('sig_encode2')) {
	function sig_encode2($string) {
		$string = rawurlencode($string);
		return str_replace('%7E', '~', $string);
	}
}
if (! function_exists('sig_access_key')) {
	function sig_access_key($secret_access_key, $data) {
		return __sig_s3_post_base64(__sig_s3_post_hasher($secret_access_key, $data));
	}
}
function __sig_s3_post_base64($str) {
	$ret = "";
	for($i = 0; $i < strlen($str); $i += 2) $ret .= chr(hexdec(substr($str, $i, 2)));
	return base64_encode($ret);
}
function __sig_s3_post_hasher($key, $data) {
	// Algorithm adapted (stolen) from http://pear.php.net/package/Crypt_HMAC/)
	if(strlen($key) > 64) $key = pack("H40", sha1($key));
	if(strlen($key) < 64) $key = str_pad($key, 64, chr(0));
	$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
	$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
	return sha1($opad . pack("H40", sha1($ipad . $data)));
}

?>
