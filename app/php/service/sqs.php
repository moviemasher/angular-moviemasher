<?php 

include_once(dirname(dirname(__FILE__)) . '/include/loadutils.php');
load_utils('auth','path','date','json','http','log');


function sqs_client_enqueue($data, $config){
	$result = array();
	$err = '';
	$variables = array();
	$variables['Action'] = 'SendMessage';
	$variables['MessageBody'] = json_encode($data);
	$variables['Version'] = '2012-11-05';
	$parsed_url = parse_url($config['sqs_queue_url']);
	$headers = array();
	$headers['Host'] = strtolower($parsed_url['host']);
	$headers['X-Amz-Date'] = gmdate(DATE_FORMAT_ISO8601_BASIC);
	$signature = __sqs_client_version_four($config['aws_secret_access_key'], $config['sqs_queue_url'], $variables, $headers, $config['s3_region'], 'post');
	$region =  (empty($config['sqs_region']) ? (empty($config['s3_region']) ? 'us-east-1' : $config['s3_region']) : $config['sqs_region']); // fallback to same region as s3 bucket
	$date_str = substr($headers['X-Amz-Date'], 0, 8);
	$headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$config['aws_access_key_id']}/$date_str/$region/sqs/aws4_request, SignedHeaders=host;x-amz-date, Signature=$signature";
	$post_result = http_send($config['sqs_queue_url'], $variables, $headers);
	$data = $post_result['result'];
	if ($data && $config['log_api_response']) log_file("sqs response:\n" . print_r($data, 1), $config);
	if ($post_result['error']) $err = 'Could not make sqs request ' . $config['sqs_queue_url'] . ' ' . $post_result['error'];
	else if (! $data) $err = 'Got no response from sqs request';
	else {
		$xml = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
		if (! is_object($xml)) $err = 'Could not parse sqs response';
		else if (sizeof($xml->Error)) {
			if (! $config['log_api_response']) log_file("sqs response:\n" . $data, $config);
			$err = 'Got error in sqs response';
		}
		else if (! (is_object($xml->SendMessageResult) && is_object($xml->SendMessageResult->MessageId))) $err = 'Got no MessageId in sqs response';
		else $result['id'] = (string) $xml->SendMessageResult->MessageId;
	}		
	if ($err) $result['error'] = $err;
	return $result;
}

function sqs_client_config_error($config = array()){
	$err = '';
	if ((! $err) && empty($config['sqs_queue_url'])) $err = 'Configuration option sqs_queue_url required';
	if ((! $err) && empty($config['aws_access_key_id'])) $err = 'Configuration option aws_access_key_id required';
	if ((! $err) && empty($config['aws_secret_access_key'])) $err = 'Configuration option aws_secret_access_key required';
	if ((! $err) && empty($config['temporary_directory'])) $err = 'Configuration option temporary_directory required';
	return $err;
}

function __sqs_client_version_four($secret_access_key, $url, $data, $headers, $region, $method = 'get') {
	$params = array();
	uksort($data, 'strnatcmp');
	foreach($data as $k => $v) {
		$params[] = __sqs_client_encode2($k) . '=' . urlencode($v);
	}
	$payload = (implode('&', $params));
	$hexencode_hash_payload = __sqs_client_hexencode(__sqs_client_hash($payload));
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
		$canonical_lines[] = strtolower(__sqs_client_encode2($k)) . ':' . __sqs_client_encode2($v);
	}
	$canonical_lines[] = '';
	$canonical_lines[] = strtolower(implode(';', $header_keys)); // semicolon separated names of headers alphabetized
	$canonical_lines[] = $hexencode_hash_payload;
	$canonical_request = implode("\n", $canonical_lines);
	$hexencode_hash_canonical = __sqs_client_hexencode(__sqs_client_hash($canonical_request));
	//print "~CanonicalRequest~\n$canonical_request\n";
	//print "~HexEncode(Hash(CanonicalRequest))~\n$hexencode_hash_canonical\n";

	$date_str = substr($headers['X-Amz-Date'], 0, 8);
	if (! $region) $region = 'us-east-1';

	$string_to_sign_lines = array();
	$string_to_sign_lines[] = 'AWS4-HMAC-SHA256';
	$string_to_sign_lines[] = $headers['X-Amz-Date'];
	$string_to_sign_lines[] = "$date_str/$region/sqs/aws4_request";
	$string_to_sign_lines[] = $hexencode_hash_canonical;

	$method = 'sha256';
	$key = 'AWS4' . $secret_access_key;
	$key = hash_hmac($method, $date_str, $key, TRUE);
	$key = hash_hmac($method, $region, $key, TRUE);
	$key = hash_hmac($method, 'sqs', $key, TRUE);
	$key = hash_hmac($method, 'aws4_request', $key, TRUE);

	$string_to_sign = implode("\n", $string_to_sign_lines);
	$hexencode_sig = __sqs_client_hexencode(__sqs_client_sign($key, $string_to_sign));
	//print "~StringToSign~\n$string_to_sign\n";
	//print "~HexEncode(Sign(StringToSign))~\n$hexencode_sig\n";
	return $hexencode_sig;
}
function __sqs_client_hexencode($s){
	return bin2hex($s);
}
function __sqs_client_encode2($string) {
	$string = rawurlencode($string);
	return str_replace('%7E', '~', $string);
}
function __sqs_client_hash($s, $method = 'sha256'){
	return hash($method, $s, TRUE);
}
function __sqs_client_sign($secret_access_key, $s, $method = 'sha256'){
	return hash_hmac($method, $s, $secret_access_key, TRUE);
}
