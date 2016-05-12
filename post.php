//to post json data to a url
<?php
function curl_post($url,$post_parameters,$user_cred)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_USERPWD, $user_cred);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post_parameters);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec ($ch);
	curl_close ($ch);
	return $output;
}
$temp=fopen('temp','a+');
$URL = 'http://sanitationimpact.org'; // url for api
$USER_PASS = 'root:root123'; // username and password

$json=json_decode(curl_post($URL.'/reporting/api/download_audio/','ticket_id=3&audio_file_url=http://recordings.kookoo.in/raj1992/9833.mp3' ,$USER_PASS),true);
file_put_contents('temp',print_r($json,true));
?>
