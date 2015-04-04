<?php
/**
 *
 * 广告JS调用方式
 *
 * @version        $Id: ad_js.php 1 20:30 2010年7月8日Z tianya $
 * @package        DedeCMS.Site
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/include/common.inc.php");

$results = array();
global $dsql;

$sql = "SELECT art.id, art.sortrank, arc.pubdate FROM  `#@__arctiny` art
        left join `#@__archives` arc on arc.id = art.id
        where art.sortrank = 50
        order by art.id asc";
$dsql->SetQuery($sql);
$dsql->Execute();
while($row = $dsql->GetArray()) {
    $sortrank = $row['pubdate'];
    $id = $row['id'];
    $sql = "update `#@__arctiny` set `sortrank` = '{$sortrank}' where id = '{$id}'";
    $rs = $dsql->ExecuteNoneQuery2($sql);
    $results[$id] = $rs;
}

var_dump($results);die;