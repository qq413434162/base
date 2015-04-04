<?php  if (!defined('DEDEINC')) exit('dedecms');
/**
 * 文档小助手
 *
 * @version        $Id: archive.helper.php 2 23:00 2010年7月5日Z tianya $
 * @package        DedeCMS.Helpers
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */

/**
 *  获取单篇文档信息
 *
 * @param     int $aid 文档id
 * @return    array
 */
if (!function_exists('GetArcTypeApiKey')) {
    function GetArcTypeApiKey($typeid)
    {
        global $cfg_cookie_encode, $dsql;
        $sql = "SELECT `siteurl`, `moresite`, `typedir` FROM #@__arctype art WHERE id='{$typeid}'";
        $row = $dsql->getOne($sql);

        if (empty($row)) return '';
        $apikey = isset($row['moresite']) && $row['moresite'] ? $row['siteurl'] : $row['typedir'];
        $apikey = md5(md5($apikey) . $cfg_cookie_encode);
        return $apikey;
    }
}

if (!function_exists('updateArcTypeApiKey')) {
    function updateArcTypeApiKey($typeid)
    {
        global $dsql;
        $apikey = GetArcTypeApiKey($typeid);
        if (empty($apikey)) return FALSE;
        $query = "UPDATE `#@__arctype` SET `apikey`='{$apikey}' WHERE id = '{$typeid}' ";
        $rs = $dsql->ExecuteNoneQuery2($query);
        return $rs;
    }
}

if (!function_exists('selectArcTypeApiKey')) {
    function selectArcTypeApiKey($typeid)
    {
        global $dsql;
        $sql = "SELECT `apikey` FROM #@__arctype art WHERE id='{$typeid}'";
        $row = $dsql->getOne($sql);
        return isset($row['apikey']) ? $row['apikey'] : '';
    }
}

if (!function_exists('getCurlData')) {
    function getCurlData($data)
    {
        $parse = parse_url($data['host']);
        $a = $data['act'];
        $m = $data['method'];
        unset($data['act']);
        unset($data['method']);
        $data['host'] = 'http://' . $parse['host'] . (isset($parse['port']) && $parse['port'] ? ':' . $parse['port'] : '') . '/os/api.php?c=' . $a . '&m=' . $m;
        // 参与签名的数据
        $signData = $data;

        $curlData = $data;
        unset($curlData['api_secret']);
        $curlData['api_sign'] = getSign($signData);
        return $curlData;
    }
}

if (!function_exists('call')) {
    function call($param, $sign = 'get')
    {
//        $param['content'] =substr($param['content'], 0, 30);
        $param = getCurlData($param);
        $rs = curl($param['host'], $param, $sign);
        $rs = json_decode($rs, TRUE);
        return $rs;
    }
}

if (!function_exists('makeDirByTypeId')) {
    function makeDirByTypeId($typeid)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey` FROM #@__arctype art WHERE id='{$typeid}'";
        $row = $dsql->getOne($sql);

        $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
        $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

        if($row['moresite']==1)
        {
            if($row['sitepath']!='')
            {
                $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
            }
        }

        $host = $row['siteurl'];
        $param = array(
            'act' => 'arctype',
            'method' => 'makeDir',
            'path' => $typedir,
            'chmod' => $cfg_dir_purview,
            'host' => $host,
            'api_key' => $host,
            'api_secret' => $row['apikey'],
        );
        helper('site');
        return call($param);
    }
}

if (!function_exists('delDirDefaultnameByTypeId')) {
    function delDirDefaultnameByTypeId($typeid)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `defaultname` FROM #@__arctype art WHERE id='{$typeid}'";
        $row = $dsql->getOne($sql);

        $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
        $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

        if($row['moresite']==1)
        {
            if($row['sitepath']!='')
            {
                $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
            }
        }
        $typedir = $typedir . '/' . $row['defaultname'];

        $host = $row['siteurl'];
        $param = array(
            'act' => 'archives',
            'method' => 'del',
            'path' => $typedir,
            'host' => $host,
            'api_key' => $host,
            'api_secret' => $row['apikey'],
        );
        helper('site');
        return call($param);
    }
}

if (!function_exists('delArchivesById')) {
    function delArchivesById($aid)
    {
        global $dsql;
        $arcQuery = "SELECT arc.*,tp.*,arc.id AS aid FROM `#@__archives` arc LEFT JOIN `#@__arctype` tp ON arc.typeid=tp.id WHERE arc.id='$aid' ";
        $arcRow = $dsql->GetOne($arcQuery);

        $arcurl = GetFileUrl($arcRow['aid'],$arcRow['typeid'],$arcRow['senddate'],$arcRow['title'],$arcRow['ismake'],
            $arcRow['arcrank'],$arcRow['namerule'],$arcRow['typedir'],$arcRow['money'],$arcRow['filename'],$arcRow['moresite'],$arcRow['siteurl'],$arcRow['sitepath']);

        $htmlfile = str_replace($arcRow['siteurl'], '', $arcurl);
        $param = array(
            'act' => 'archives',
            'method' => 'del',
            'path' => $htmlfile,
            'host' => $arcRow['siteurl'],
            'api_key' => $arcRow['siteurl'],
            'api_secret' => $arcRow['apikey'],
        );
        helper('site');
        $rs = call($param);
        return $rs;
    }
}

if (!function_exists('renameSource')) {
    function renameSource($oldname, $newname)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `id` FROM #@__arctype art WHERE `topid` = 0";
        $dsql->SetQuery($sql);
        $dsql->Execute('al');
        helper('site');
        $results = array();
        while($row = $dsql->GetArray("al")) {
            $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
            $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

            if($row['moresite']==1)
            {
                if($row['sitepath']!='')
                {
                    $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
                }
            }

            $host = $row['siteurl'];
            $param = array(
                'act' => 'source',
                'method' => 'rename',
                'oldname' => $oldname,
                'newname' => $newname,
                'host' => $host,
                'api_key' => $host,
                'api_secret' => $row['apikey'],
            );
            $results[$row['id']] = call($param);
        }
        return $results;
    }
}

if (!function_exists('delSource')) {
    function delSource($filename)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `id` FROM #@__arctype art WHERE `topid` = 0";
        $dsql->SetQuery($sql);
        $dsql->Execute('al');
        helper('site');
        $results = array();
        while($row = $dsql->GetArray("al")) {
            $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
            $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

            if($row['moresite']==1)
            {
                if($row['sitepath']!='')
                {
                    $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
                }
            }

            $host = $row['siteurl'];
            $param = array(
                'act' => 'source',
                'method' => 'del',
                'filename' => $filename,
                'host' => $host,
                'api_key' => $host,
                'api_secret' => $row['apikey'],
            );
            $results[$row['id']] = call($param);
        }
        return $results;
    }
}

if (!function_exists('moveSource')) {
    function moveSource($oldfileSite, $truepath, $mfile)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `id` FROM #@__arctype art WHERE `topid` = 0";
        $dsql->SetQuery($sql);
        $dsql->Execute('al');
        helper('site');
        $results = array();
        while($row = $dsql->GetArray("al")) {
            $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
            $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

            if($row['moresite']==1)
            {
                if($row['sitepath']!='')
                {
                    $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
                }
            }

            $host = $row['siteurl'];
            $param = array(
                'act' => 'source',
                'method' => 'move',

                'oldname' => $oldfileSite,
                'newname' => $truepath,
                'filename' => $mfile,

                'host' => $host,
                'api_key' => $host,
                'api_secret' => $row['apikey'],
            );
            $results[$row['id']] = call($param);
        }
        return $results;
    }
}

if (!function_exists('makeSource')) {
    function makeSource($filename, $content)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `id` FROM #@__arctype art WHERE `topid` = 0";
        $dsql->SetQuery($sql);
        $dsql->Execute('al');
        helper('site');
        $results = array();
        while($row = $dsql->GetArray("al")) {
            $true_typedir = str_replace("{cmspath}", $cfg_cmspath, $row['typedir']);
            $true_typedir = preg_replace("#\/{1,}#", "/", $true_typedir);

            if($row['moresite']==1)
            {
                if($row['sitepath']!='')
                {
                    $typedir = preg_replace("/".str_replace('/', '\/', $row['sitepath'])."/", '', $true_typedir, 1);
                }
            }

            $host = $row['siteurl'];
            $param = array(
                'act' => 'source',
                'method' => 'make',

                'path' => $filename,
                'content' => $content,

                'host' => $host,
                'api_key' => $host,
                'api_secret' => $row['apikey'],
            );
            $results[$row['id']] = call($param, 'post');
        }
        return $results;
    }
}
/**
 *  获取域名
 *
 * @param     int  $aid  文档id
 * @return    array
 */
if ( ! function_exists('GetDomain'))
{
    /*function GetDomain($typeid)
    {
        global $dsql;
        $addTypeRow = $dsql->GetOne("SELECT * FROM `#@__arctype` WHERE id='{$typeid}'");
        $domainset = '';
        if(isset($addTypeRow['moresite']) && $addTypeRow['moresite'])
        {
            $domainset = str_replace('{cmspath}/', '', ltrim($addTypeRow['typedir'], '/'));
        } else if ($addTypeRow['sitepath']) {
            list($domainset) = explode('/', str_replace('{cmspath}/', '', ltrim($addTypeRow['sitepath'], '/')));
        } else if ($addTypeRow['typedir']) {
            list($domainset) = explode('/', str_replace('{cmspath}/', '', ltrim($addTypeRow['typedir'], '/')));
        }
        return $domainset;
    }*/
    function GetDomain($typeid)
    {
        global $dsql;
        $addTypeRow = $dsql->GetOne("SELECT * FROM `#@__arctype` WHERE id='{$typeid}'");
        $domainset = '';
        if(isset($addTypeRow['moresite']) && $addTypeRow['moresite'])
        {
            preg_match('/\/?(\w+)\.\w+\./', $addTypeRow['siteurl'], $match);
            if (isset($match[1]) && $match[1]) {
                $domainset = $match[1];
            } else {
                ShowMsg("分析域名出错!请通知管理员","javascript:;");
                exit();
            }
        } else if ($addTypeRow['sitepath']) {
            list($domainset) = explode('/', str_replace('{cmspath}/', '', ltrim($addTypeRow['sitepath'], '/')));
        } else if ($addTypeRow['typedir']) {
            list($domainset) = explode('/', str_replace('{cmspath}/', '', ltrim($addTypeRow['typedir'], '/')));
        }
        return $domainset;
    }
}

/**
 * 生成单页文档
 *
 * @param     String  $filename  路径
 * @param     String  $content  内容
 * @return    array
 */
if ( ! function_exists('MakeSgPage'))
{
    function MakeSgPage($filename, $content, $topid = 0)
    {
        global $dsql, $cfg_cmspath, $cfg_dir_purview;
        $topid = abs($topid);
        if ($topid) {
            $where = "`id` = '{$topid}'";
        } else {
            $where = "`topid` = 0";
        }
        $sql = "SELECT `sitepath`, `moresite`, `siteurl`, `typedir`, `apikey`, `id` FROM #@__arctype art WHERE {$where}";
        $dsql->SetQuery($sql);
        $dsql->Execute('al');
        helper('site');

        $results = array();
        while($row = $dsql->GetArray("al")) {
            $param = array(
                'act' => 'arctype',
                'method' => 'make',
                'path' => $filename,
                'host' => $row['siteurl'],
                'content' => $content,
                'api_key' => $row['siteurl'],
                'api_secret' => $row['apikey'],
            );
            $rs = call($param, 'post');
            $results[$row['id']] = $rs;
        }
        return $results;
    }
}