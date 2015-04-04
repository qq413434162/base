<?php
if(!defined('DEDEINC'))
{
    exit("Request Error!");
}
/**
 * 广告标签
 *
 * @version        $Id: vote.lib.php 1 9:29 2010年7月6日Z tianya $
 * @package        DedeCMS.Taglib
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
 
/*>>dede>>
<name>投票标签</name>
<type>全局标记</type>
<for>V55,V56,V57</for>
<description>用于获取一组投票表单</description>
<demo>
{dede:vote id='' lineheight='22' tablewidth='100%' titlebgcolor='#EDEDE2' titlebackground='' tablebgcolor='#FFFFFF'/}
{/dede}
</demo>
<attributes>
    <iterm>id:数字，当前投票ID</iterm>
    <iterm>lineheight:表格高度</iterm>
    <iterm>tablewidth:表格宽度</iterm>
    <iterm>titlebgcolor:投票标题背景色</iterm>
    <iterm>titlebackground:标题背景图</iterm>
    <iterm>tablebg:投票表格背景色</iterm>
</attributes>
>>dede>>
 {dede:ad classify="1"/}*/

function lib_ad(&$ctag,&$refObj)
{
    global $dsql, $cfg_upload_api_secre, $cfg_upload_pic;
    $attlist="typeid|0,classify|0";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    if(empty($typeid)) $typeid=0;
    if(empty($classify)) $classify=0;
    if (is_object($GLOBALS['lv']) && $GLOBALS['lv']->TypeID) {
        $typeid = $GLOBALS['lv']->TypeID;
    } else if (is_object($GLOBALS['arc']) && $GLOBALS['arc']->Fields['typeid']) {
        $typeid = $GLOBALS['arc']->Fields['typeid'];
    } else if (isset($GLOBALS['_sys_globals']) && $GLOBALS['_sys_globals']['typeid']) {
        $typeid = $GLOBALS['_sys_globals']['typeid'];
    } else if (isset($GLOBALS['envs']) && $GLOBALS['envs']['typeid']) {
        $typeid = $GLOBALS['envs']['typeid'];
    }
    $typeid = abs($typeid);
    $classify = abs($classify);

    if (!$typeid || !$classify) return '';
    $row = lib_ad_getData($typeid, $classify);
    $adbody = '';
    if ($row) {
        if($row['timeset']==0)
        {
            $adbody = $row['normbody'];
        }
        else
        {
            $ntime = time();
            if($ntime > $row['endtime'] || $ntime < $row['starttime']) {
                $adbody = $row['expbody'];
            } else {
                $adbody = $row['normbody'];
            }
        }
        list($divname) = explode('-', $row['tagname']);
        $adbody = stripslashes($adbody);
        $adbody = str_replace('"', '\"',$adbody);
        $adbody = str_replace("\r", "\\r",$adbody);
        $adbody = str_replace("\n", "\\n",$adbody);

        $adbody = "document.write(\"" . ($divname ? '<div class=\"' . $divname . '\">' : '') . $adbody . ($divname ? '</div>' : '') . "\");";
    } else {
        $adbody = ' '; //防止缓存失效
    }
    $html = $adbody;

    $urls = parse_url($cfg_upload_pic);
    $url = str_replace($urls['path'], '', $cfg_upload_pic);
    $filename = "/js/data/ad_{$typeid}_{$classify}.js";
    $data['filename'] = json_encode($filename);
    $data['content'] = json_encode($html);
    $data['action'] = 'make_file';
    $data['api_key'] = 'pic';
    $data['api_secret'] = $cfg_upload_api_secre;
    $sign = getSign($data);
    unset($data['api_secret']);
    $data['api_sign'] = $sign;
    $rs = curl($cfg_upload_pic, $data, 'post');

    $filename = $url . $filename;

    $html = "<script type=\"text/javascript\" src=\"{$filename}\" charset=\"utf-8\"></script>";
    return $html;
}
//获取广告信息
function lib_ad_getData($typeid, $classify, $continue = TRUE) {
    global $dsql;
    $sql = "SELECT * FROM #@__myad WHERE `typeid`='{$typeid}' AND `clsid` = '{$classify}' LIMIT 0, 1";
    $row = $dsql->getOne($sql);
    if (!$row && $continue) {
        $sql = "SELECT `topid` FROM #@__arctype WHERE `id`='{$typeid}' LIMIT 0, 1";
        $row = $dsql->getOne($sql);
        if (!$row) return FALSE;
        return lib_ad_getData($row['topid'], $classify, FALSE);
    }
    return $row;
}