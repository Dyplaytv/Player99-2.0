<?php
ini_set('display_errors', 1);
include(__DIR__ . '/../includes/functions.php');
$infos = explode("{}", base64_decode($_GET['info']));
$dnsId = $infos[0];
$username = $infos[1];
$password = $infos[2];
$macAddress = base64_decode($infos[3]);
$macAddress = strtoupper(implode(":", str_split(substr(str_replace("afea", "", $macAddress), 0, 12), 2)));
$result = $db->select('playlist', '*', 'dns_id = :dns_id AND mac_address = :mac_address', '', [':dns_id' => $dnsId,':mac_address' => $macAddress]);

if (!empty($result)) {
	$data = ['username' => $username,'password' => $password,'pin' => '0000',];
	$db->update('playlist', $data, 'dns_id = :dns_id AND mac_address = :mac_address', [':dns_id' => $dnsId,':mac_address' => $macAddress]);
} else {
	$data = ['dns_id' => $dnsId,'mac_address' => $macAddress,'username' => $username,'password' => $password,'pin' => '0000',];
	$db->insert('playlist', $data);
}

$response = ['success' => 1, 'id' => $dnsId, "name"=>null,"url"=>null];
echo json_encode($response);