<?php
/**
 * 重定向
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
require_once(DEDEINC.'/arc.archives.class.php');
helper('site');
global $cfg_upload_pic, $cfg_upload_api_secre;
$url = $cfg_upload_pic;

$data = $_POST;

$srcFile = $_FILES['Filedata']['tmp_name'];
/*
$srcFile = 'E:/tmp/' . $_FILES['Filedata']['name'];
if (is_uploaded_file($_FILES['Filedata']['tmp_name'])) {
    $rs = move_uploaded_file($filename, $srcFile);
}
*/
$data['content'] = file_get_contents($srcFile);

//@unlink($srcFile);
$data['Filedata'] = json_encode($_FILES['Filedata']);

$data['api_key'] = 'pic';
$data['action'] = 'upload8jdj_effort';
$data['api_secret'] = $cfg_upload_api_secre;
$sign = getSign($data);
unset($data['api_secret']);
$data['api_sign'] = $sign;
$rs = curl($url, $data, 'post');
echo $rs;