<?php
/**
 * 文档发布
 *
 * @version        $Id: archives_add.php 1 8:26 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
//CheckPurview('a_New,a_AccNew');
require_once(DEDEINC.'/customfields.func.php');
require_once(DEDEADMIN.'/inc/inc_archives_functions.php');

function AcceptShowMsg($msg = "") {
    global $arcID;
    echo json_encode(array(
        'msg' => $msg,
        'aid' => $arcID,
    ));
}
$channelid = $channel;
$arcrank = -1;
$adminid = $mid;
$typeid = $relation_id;

require_once(DEDEINC.'/image.func.php');
require_once(DEDEINC.'/oxwindow.class.php');
$notpost = isset($notpost) && $notpost == 1 ? 1: 0;

if(empty($typeid2)) $typeid2 = '';
if(!isset($autokey)) $autokey = 0;
if(!isset($remote)) $remote = 0;
if(!isset($dellink)) $dellink = 0;
if(!isset($autolitpic)) $autolitpic = 0;
if(empty($click)) $click = ($cfg_arc_click=='-1' ? mt_rand(50, 200) : $cfg_arc_click);

if(empty($typeid))
{
    AcceptShowMsg("请指定文档的栏目！","-1");
    exit();
}
if(empty($channelid))
{
    AcceptShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！","-1");
    exit();
}
if(!CheckChannel($typeid,$channelid))
{
    AcceptShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！","-1");
    exit();
}
/*if(!TestPurview('a_New'))
{
    CheckCatalog($typeid,"对不起，你没有操作栏目 {$typeid} 的权限！");
}*/

//对保存的内容进行处理
//if(empty($writer))$writer=$cuserLogin->getUserName();
if(empty($source))$source='未知';
$isremote  = (empty($isremote)? 0  : $isremote);
$serviterm=empty($serviterm)? "" : $serviterm;

/*if(!TestPurview('a_Check,a_AccCheck,a_MyCheck'))
{
    $arcrank = -1;
}
$adminid = $cuserLogin->getUserID();*/

//处理上传的缩略图
if(empty($ddisremote))
{
    $ddisremote = 0;
}

//$litpic = GetDDImage('none', $picname, $ddisremote);

//生成文档ID
$arcID = GetIndexKey($arcrank,$typeid,$sortrank,$channelid,$senddate,$adminid);

if(empty($arcID))
{
    AcceptShowMsg("无法获得主键，因此无法进行后续操作！","-1");
    exit();
}
if(trim($title) == '')
{
    AcceptShowMsg('标题不能为空', '-1');
    exit();
}

/*//处理body字段自动摘要、自动提取缩略图等
$body = AnalyseHtmlBody($body,$description,$litpic,$keywords,'htmltext');

//自动分页
if($sptype=='auto')
{
    $body = SpLongBody($body,$spsize*1024,"#p#分页标题#e#");
}*/

//分析处理附加表数据
$inadd_f = $inadd_v = '';
if(!empty($dede_addonfields))
{
    $addonfields = explode(';',$dede_addonfields);
    if(is_array($addonfields))
    {
        foreach($addonfields as $v)
        {
            if($v=='') continue;
            $vs = explode(',',$v);
            if($vs[1]=='htmltext'||$vs[1]=='textdata')
            {
//                ${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
            }
            else
            {
                if(!isset(${$vs[0]})) ${$vs[0]} = '';
                ${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$arcID);
            }
            $inadd_f .= ','.$vs[0];
            $inadd_v .= " ,'".${$vs[0]}."' ";
        }
    }
}
//处理图片文档的自定义属性
if($litpic!='' && !preg_match("#p#", $flag))
{
    $flag = ($flag=='' ? 'p' : $flag.',p');
}
if($redirecturl!='' && !preg_match("#j#", $flag))
{
    $flag = ($flag=='' ? 'j' : $flag.',j');
}

//跳转网址的文档强制为动态
if(preg_match("#j#", $flag)) $ismake = -1;

//保存到主表
$query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,
    color,writer,source,litpic,pubdate,senddate,mid,voteid,notpost,description,keywords,filename,dutyadmin,weight)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','$ismake','$channelid','$arcrank','$click','$money',
    '$title','$shorttitle','$color','$writer','$source','$litpic','$pubdate','$senddate',
    '$adminid','$voteid','$notpost','$description','$keywords','$filename','$adminid','$weight');";

if(!$dsql->ExecuteNoneQuery($query))
{
    $gerr = $dsql->GetError();
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
    AcceptShowMsg("把数据保存到数据库主表 `#@__archives` 时出错，请把相关信息提交给DedeCms官方。".str_replace('"','',$gerr),"javascript:;");
    exit();
}
if ($arcrank > -1) {
    $sql = "UPDATE `#@__archives` SET auditdate='" . time() ."' WHERE id='$arcID' ";
    $rs = $dsql->ExecuteNoneQuery2($sql);
    writeLog(array(
        'sql' => $sql,
        'rs' => $rs,
    ));
}

//保存到附加表
$cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
$addtable = trim($cts['addtable']);
if(empty($addtable))
{
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
    AcceptShowMsg("没找到当前模型[{$channelid}]的主表信息，无法完成操作！。","javascript:;");
    exit();
}
//$useip = GetIP();
$templet = empty($templet) ? '' : $templet;
$query = "INSERT INTO `{$addtable}`(aid,typeid,redirecturl,templet,userip,body{$inadd_f}) Values('$arcID','$typeid','$redirecturl','$templet','$useip','$body'{$inadd_v})";

if(!$dsql->ExecuteNoneQuery($query))
{
    $gerr = $dsql->GetError();
    $dsql->ExecuteNoneQuery("Delete From `#@__archives` where id='$arcID'");
    $dsql->ExecuteNoneQuery("Delete From `#@__arctiny` where id='$arcID'");
    AcceptShowMsg("把数据保存到数据库附加表 `{$addtable}` 时出错，请把相关信息提交给DedeCms官方。".str_replace('"','',$gerr),"javascript:;");
    exit();
}
//生成HTML
InsertTags($tags,$arcID);
if($cfg_remote_site=='Y' && $isremote=="1")
{
    if($serviterm!=""){
        list($servurl,$servuser,$servpwd) = explode(',',$serviterm);
        $config=array( 'hostname' => $servurl, 'username' => $servuser, 'password' => $servpwd,'debug' => 'TRUE');
    }else{
        $config=array();
    }
    if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
}
$picTitle = false;
if(count($_SESSION['bigfile_info']) > 0)
{
    foreach ($_SESSION['bigfile_info'] as $k => $v)
    {
        if(!empty($v))
        {
            $pictitle = ${'picinfook'.$k};
            $titleSet = '';
            if(!empty($pictitle))
            {
                $picTitle = TRUE;
                $titleSet = ",title='{$pictitle}'";
            }
            $dsql->ExecuteNoneQuery("UPDATE `#@__uploads` SET arcid='{$arcID}'{$titleSet} WHERE url LIKE '{$v}'; ");
        }
    }
}
$artUrl = MakeArt($arcID,true,true,$isremote);
if($artUrl=='')
{
    $artUrl = $cfg_phpurl."/view.php?aid=$arcID";
}
ClearMyAddon($arcID, $title);
AcceptShowMsg("sucess","javascript:;");