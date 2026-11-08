<?php
####################### HEADER ##############################
/**
* @ This file is created by :
*
* @ Author			:	BoxBR Apks Rebrand
* @ Contact-me 		: 	https://boxbrapk.t.me
* @ Official site	:	http://boxbrapk.k6.com.br
*
*/
header('Content-Type: application/json;charset=utf-8');
date_default_timezone_set("America/Sao_Paulo");
#############################################################
// Check page erros 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
#############################################################
//#\\\\\\\\\ SECURITY URI ////////#\\
if (empty(file_get_contents('php://input'))){
	// redirecioar as bixas curiosas para o lugar certo
header('Location: https://ongrupos.com/grupo/ICRIDqAL2q90dDlf1GOy1g');
   die;
}
// ######## decoder data ###################
$allowedCharacters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
function getEncryptKeyPosition($str)
{
	global $allowedCharacters;
	return strpos($allowedCharacters,$str);
}
function getDecodedString($str, $type=1)
{
	$endKey =  substr($str,strlen($str) - 2,strlen($str) - 2);
	if($type == 1)
	{
		 $encryptKeyPosition = getEncryptKeyPosition($endKey[0]) ;
		$encryptKeyPosition2 = getEncryptKeyPosition($endKey[1]) ;
	}
	else
	{
		$encryptKeyPosition = getEncryptKeyPosition($endKey[1]) ;
		$encryptKeyPosition2 = getEncryptKeyPosition($endKey[0]) ;
	}
	$substring = substr($str,0,strlen($str) - 2);
	return base64_decode(trim(substr($substring,0,$encryptKeyPosition) . substr($substring,($encryptKeyPosition + $encryptKeyPosition2),strlen($substring))));
}
#############################################################
$jsondata = file_get_contents('php://input'); 
$data0 = json_decode($jsondata, true);
$data1 = getDecodedString($data0['data']);
$json = json_decode($data1, true);
$app_device_mac = strtoupper($json['mac_address']);
$playlist_id = $json['playlist_id'];
$playlist_name = $json['playlist_name'];
$playlist_url = $json['playlist_url'];
$playlist_url_parts = parse_url($playlist_url);
$playlist_url_output = [];
parse_str($playlist_url_parts['query'], $playlist_url_output);
$playlist_url_user = $playlist_url_output['username'];
$playlist_url_pass = $playlist_url_output['password'];
// Encontra o número no início da URL
preg_match('/^\d+/', $playlist_url, $matches);

if (!empty($matches)) {
    $playlist_url_host = $matches[0]; // Define o número encontrado na variável $playlist_url_host
} else {
    $playlist_url_host = ''; // Se não encontrar nenhum número, atribui vazio à variável
}
$playlist_type= $json['playlist_type'];
$app_type= $json['app_type'];
#############################################################
function call_api($api_link){
	$returnData = "0";
	//global $httpcode;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_link);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_2; en-US) AppleWebKit/532.8 (KHTML, like Gecko) Chrome/4.0.302.2 Safari/532.8');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
  ));
	$html = curl_exec($ch);
	$result = json_decode($html);
	if (!empty($result)) {
		$returndata = $result;
		return ["result" => "success", "data" => $returndata];
	}
}
#############################################################
$db = new SQLite3('./db/studiolivecode_clientes.db'); $db->busyTimeout(5000);
$db->exec("CREATE TABLE IF NOT EXISTS ibo(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mac_address VARCHAR(100), username VARCHAR(100), password VARCHAR(100), expire_date VARCHAR(100), dns VARCHAR(100), epg_url VARCHAR(100), title VARCHAR(100), url VARCHAR(100), type VARCHAR(100), id_user TEXT, active TEXT)");


$resg = $db->query('SELECT * FROM ibo ' . "\r\n\t\t\t\t" . '  WHERE mac_address=\''. $app_device_mac . '\'');

$rowg = $resg->fetchArray();
	$gid = $rowg['id'];
	$gmac = $rowg['mac_address'];
	$gdate = $rowg['expire_date'];
	$gtitle = $rowg['title'];
	$id_user = $rowg['id_user'];
	$active = $rowg['active'];
	$store_type = $rowg['store_type'];
	$playlistpassword = $rowg['playlistpassword'];
	$we = strtotime($gdate);
	$ne = date('Y-m-d', $we);
	$date = date('Y-m-d');
	$wed = strtotime($date);
	if($ne > $wed){
	    $timer = '1';
	}else{
	    $timer = '0';
	}
	if($app_device_mac){
$dbdns = new SQLite3('./db/studiolivecode_dns.db');	
$dbdns->exec('CREATE TABLE IF NOT EXISTS dns(id INTEGER PRIMARY KEY NOT NULL,title VARCHAR(100),url VARCHAR(100),epg VARCHAR(100))');
$resh = $dbdns->query('SELECT * FROM dns');
$check_dns = false;
$active_title = ''; // Variável para salvar o título do URL ativo
$dns_load = ''; // Variável para salvar o DNS ativo
$check_dns = false; // Variável para controlar a busca do DNS ativo

while ($row = $resh->fetchArray(SQLITE3_ASSOC)) {
    $dns = $row['url'];

    $api_link = $dns . "/player_api.php?username={$playlist_url_user}&password={$playlist_url_pass}";
    $result = call_api($api_link);

    if ($result["result"] == "success" &&
        isset($result["data"]->user_info->auth) &&
        $result["data"]->user_info->auth != 0 &&
        $result["data"]->user_info->status == "Active") {
        
        $active_title = $row['title']; // Salva o título correspondente ao URL ativo
        $dns_load = $dns; // Salva o DNS ativo
        $check_dns = true; // Define a busca do DNS ativo como concluída
    }
}

if ($check_dns) {
    $type = '1';
    $dns_url = $dns_load;
    $epg_url = $dns_load . '/xmltv.php?username=' . $playlist_url_user . '&password=' . $playlist_url_pass;
    $m3u_url = $dns_load . '/get.php?username=' . $playlist_url_user . '&password=' . $playlist_url_pass . '&type=m3u_plus&output=ts';
    $res_url = $dns_load . '/get.php?username\u003d' . $playlist_url_user . '\u0026password\u003d' . $playlist_url_pass . '\u0026output\u003dts\u0026type\u003dm3u_plus';
    $playlist_namea = $active_title;
    $playlist_nameb = $active_title;

    if (isset($app_device_mac) && empty($gid)) {
        $expire_date = date('Y-m-d', strtotime("+1 YEAR"));
        $db->exec('INSERT INTO ibo(mac_address, username, password, expire_date, dns, epg_url, title, url, type, id_user, active)
        VALUES(\'' . $app_device_mac . '\', \'' . $playlist_url_user . '\', \'' . $playlist_url_pass . '\', \'' . $expire_date . '\', \'' . $dns_url . '\', \'' . $epg_url . '\', \'' . $playlist_nameb . '\', \'' . $m3u_url . '\',  \'1\',  \''. $playlist_url_host . '\',  \'1\')');
        $new_id = $db->lastInsertRowid();
    }
        $db->exec('UPDATE ibo SET username=\'' . $playlist_url_user . '\', password=\'' . $playlist_url_pass . '\', dns=\'' . $dns_url . '\',epg_url=\'' . $epg_url . '\', title=\'' . $playlist_namea . '\', url=\'' . $m3u_url . '\', id_user=\'' . $playlist_url_host . '\' WHERE mac_address=\''. $gmac . '\'');

    if (empty($new_id)) {
        $new_id = $gid . "10";
    }
    
    // Add playlist;
    // "is_protected":"1" blocks the list after editing, "0" leaves it open
    $datares = '{
        "name": "' . $active_title . '",
        "id": "' . $new_id . '",
        "url": "' . $res_url . '",
        "type": "general",
        "is_protected":"1", 
        "created_at": "2023-03-23 01:02:03",
        "updated_at": "' . date('Y-m-d H:i:s') . '"
    }';
} else {
    $datares = '{"status":false}';
}
$dbdns->close();
$db->close();
} else {
    $datares = '{"status":false}';
}
###################### END ###############################
echo trim($datares);
?>