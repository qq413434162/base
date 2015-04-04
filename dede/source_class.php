<?php   if(!defined('DEDEINC')) exit('dedecms');
/**
 * 源码管理逻辑类
 *
 * @version        $Id: file_class.php 1 19:09 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
class SourceManagement
{
    var $baseDir="";
    var $activeDir="";

    //是否允许文件管理器删除目录；
    //默认为不允许 0 ,如果希望可能管理整个目录,请把值设为 1 ；
    var $allowDeleteDir=0;

    //初始化系统
    function Init()
    {
        global $cfg_basedir, $activepath;
        $this->baseDir = $cfg_basedir;
        $this->activeDir = $activepath;
    }

    //发送文件
    function SendFile($filename)
    {
        global $cfg_source_dir, $dsql;
        $activeDir = preg_replace("/" . str_replace("/", "\/", $cfg_source_dir) . "/", '', $this->activeDir, 1);
        $activeDir = str_replace("//", '/', $activeDir);

        $filenameSite = $activeDir."/$filename";

        $filenameLocal = $this->baseDir.$this->activeDir."/$filename";

        $content = file_get_contents($filenameLocal);
        helper('site');
        $rs = makeSource($filenameSite, $content);

        $fail = array();
        if ($rs) {
            foreach($rs as $key => $val) {
                if (!$val['result']) {
                    $fail[$key] = array();
                }
            }
            if ($fail) {
                $sql = "SELECT `typename`, `id` FROM #@__arctype art WHERE `id` IN (" . implode(',', array_keys($fail)) . ")";
                $dsql->SetQuery($sql);
                $dsql->Execute('al');
                while($row = $dsql->GetArray("al")) {
                    $fail[$row['id']] = $row['typename'];
                }
            }
        }

        if (empty($fail)) {
            ShowMsg("成功发送文件！","source_manage_main.php?activepath=".$this->activeDir);
        } else {
            ShowMsg("部分发送失败：" . implode(',', $fail),"source_manage_main.php?activepath=".$this->activeDir);
        }
        return 0;
    }

    //更改文件名
    function RenameFile($oldname,$newname)
    {
        global $cfg_source_dir, $dsql;
        $activeDir = preg_replace("/" . str_replace("/", "\/", $cfg_source_dir) . "/", '', $this->activeDir, 1);
        $activeDir = str_replace("//", '/', $activeDir);

        $oldnameSite = $activeDir."/".$oldname;
        $newnameSite = $activeDir."/".$newname;

        helper('site');
        $rs = renameSource($oldnameSite, $newnameSite);

        $oldname = $this->baseDir.$this->activeDir."/".$oldname;
        $newname = $this->baseDir.$this->activeDir."/".$newname;
        if(($newname!=$oldname) && is_writable($oldname))
        {
            rename($oldname,$newname);
        }
        $fail = array();
        if ($rs) {
            foreach($rs as $key => $val) {
                if (!$val['result']) {
                    $fail[$key] = array();
                }
            }
            if ($fail) {
                $sql = "SELECT `typename`, `id` FROM #@__arctype art WHERE `id` IN (" . implode(',', array_keys($fail)) . ")";
                $dsql->SetQuery($sql);
                $dsql->Execute('al');
                while($row = $dsql->GetArray("al")) {
                    $fail[$row['id']] = $row['typename'];
                }
            }
        }
        if (empty($fail)) {
            ShowMsg("成功更改一个文件名！","source_manage_main.php?activepath=".$this->activeDir);
        } else {
            ShowMsg("部分站点修改失败:" . implode(', ', $fail),"source_manage_main.php?activepath=".$this->activeDir);
        }
        return 0;
    }

    /**
     *  移动文件
     *
     * @access    public
     * @param     string  $mfile  文件
     * @param     string  $mpath  路径
     * @return    string
     */
    function MoveFile($mfile, $mpath)
    {
        global $cfg_source_dir, $dsql;
        if($mpath!="" && !preg_match("#\.\.#", $mpath))
        {
            $activeDir = preg_replace("/" . str_replace("/", "\/", $cfg_source_dir) . "/", '', $this->activeDir, 1);
            $activeDir = str_replace("//", '/', $activeDir);

            $mpathSite = preg_replace("/" . str_replace("/", "\/", $cfg_source_dir) . "/", '', $mpath, 1);
            $mpathSite = str_replace("//", '/', $mpathSite);

            $oldfileSite = $activeDir."/".$mfile;
            $mpathSite = str_replace("\\","/",$mpathSite);
            $mpathSite = preg_replace("#\/{1,}#", "/", $mpathSite);
            if(!preg_match("#^/#", $mpathSite))
            {
                $mpathSite = $activeDir."/".$mpathSite;
            }
            $truepath = $mpathSite;

            helper('site');
            $rs = moveSource($oldfileSite, $truepath, $mfile);

            $fail = array();
            if ($rs) {
                foreach($rs as $key => $val) {
                    if (!$val['result']) {
                        $fail[$key] = array();
                    }
                }
                if ($fail) {
                    $sql = "SELECT `typename`, `id` FROM #@__arctype art WHERE `id` IN (" . implode(',', array_keys($fail)) . ")";
                    $dsql->SetQuery($sql);
                    $dsql->Execute('al');
                    while($row = $dsql->GetArray("al")) {
                        $fail[$row['id']] = $row['typename'];
                    }
                }
            }


            $oldfile = $this->baseDir.$this->activeDir."/$mfile";
            $mpath = str_replace("\\","/",$mpath);
            $mpath = preg_replace("#\/{1,}#", "/", $mpath);
            if(!preg_match("#^/#", $mpath))
            {
                $mpath = $this->activeDir."/".$mpath;
            }
            $truepath = $this->baseDir.$mpath;
            if(is_readable($oldfile)/* && is_readable($truepath) && is_writable($truepath)*/)
            {
                if(is_dir($truepath))
                {
                    copy($oldfile, $truepath."/$mfile");
                }
                else
                {
                    MkdirAll($truepath, $GLOBALS['cfg_dir_purview']);
                    CloseFtp();
                    copy($oldfile,$truepath."/$mfile");
                }
                unlink($oldfile);
                if (empty($fail)) {
                    ShowMsg("成功移动文件！","source_manage_main.php?activepath=$mpath",0,1000);
                } else {
                    ShowMsg("部分移动失败！" . implode(',', $fail),"source_manage_main.php?activepath=$mpath",0,1000);
                }
                return 1;
            }
            else
            {
                if (empty($fail)) {
                    ShowMsg("移动文件 $oldfile -&gt; $truepath/$mfile 失败，可能是某个位置权限不足！","source_manage_main.php?activepath=$mpath",0,1000);
                } else {
                    ShowMsg("移动文件 $oldfile -&gt; $truepath/$mfile 失败，可能是某个位置权限不足！并部分文件移动失败:" . implode(',', $fail),"source_manage_main.php?activepath=$mpath",0,1000);
                }
                return 0;
            }
        }
        else
        {
            ShowMsg("对不起，你移动的路径不合法！","-1",0,5000);
            return 0;
        }
    }

    /**
     * 删除目录
     *
     * @param unknown_type $indir
     */
    function RmDirFiles($indir)
    {
        if(!is_dir($indir))
        {
            return ;
        }
        $dh = dir($indir);
        while($filename = $dh->read())
        {
            if($filename == "." || $filename == "..")
            {
                continue;
            }
            else if(is_file("$indir/$filename"))
            {
                @unlink("$indir/$filename");
            }
            else
            {
                $this->RmDirFiles("$indir/$filename");
            }
        }
        $dh->close();
        @rmdir($indir);
    }

    /**
     * 删除文件
     *
     * @param unknown_type $filename
     * @return unknown
     */
    function DeleteFile($filename)
    {
        global $cfg_source_dir, $dsql;
        $activeDir = preg_replace("/" . str_replace("/", "\/", $cfg_source_dir) . "/", '', $this->activeDir, 1);
        $activeDir = str_replace("//", '/', $activeDir);

        $filenameSite = $activeDir."/".$filename;
        helper('site');
        $rs = delSource($filenameSite);
        $fail = array();
        if ($rs) {
            foreach($rs as $key => $val) {
                if (!$val['result']) {
                    $fail[$key] = array();
                }
            }
            if ($fail) {
                $sql = "SELECT `typename`, `id` FROM #@__arctype art WHERE `id` IN (" . implode(',', array_keys($fail)) . ")";
                $dsql->SetQuery($sql);
                $dsql->Execute('al');
                while($row = $dsql->GetArray("al")) {
                    $fail[$row['id']] = $row['typename'];
                }
            }
        }
        $filename = $this->baseDir.$this->activeDir."/$filename";
        if(is_file($filename))
        {
            @unlink($filename); $t="文件";
        }
        else
        {
            $t = "目录";
            if($this->allowDeleteDir==1)
            {
                $this->RmDirFiles($filename);
            } else
            {
                // 完善用户体验，by:sumic
                ShowMsg("系统禁止删除".$t."！","source_manage_main.php?activepath=".$this->activeDir);
                exit;
            }
            
        }
        if (empty($fail)) {
            ShowMsg("成功删除一个".$t."！","source_manage_main.php?activepath=".$this->activeDir);
        } else {
            ShowMsg("部分删除失败".$t."！" . implode(',', $fail),"source_manage_main.php?activepath=".$this->activeDir);
        }
        return 0;
    }
/*
    //创建新目录
    function NewDir($dirname)
    {
        $newdir = $dirname;
        $dirname = $this->baseDir.$this->activeDir."/".$dirname;
        if(is_writable($this->baseDir.$this->activeDir))
        {
            MkdirAll($dirname,$GLOBALS['cfg_dir_purview']);
            CloseFtp();
            ShowMsg("成功创建一个新目录！","source_manage_main.php?activepath=".$this->activeDir."/".$newdir);
            return 1;
        }
        else
        {
            ShowMsg("创建新目录失败，因为这个位置不允许写入！","source_manage_main.php?activepath=".$this->activeDir);
            return 0;
        }
    }
*/
    /**
     * 获得某目录合符规则的文件
     *
     * @param unknown_type $indir
     * @param unknown_type $fileexp
     * @param unknown_type $filearr
     */
    /*
    function GetMatchFiles($indir, $fileexp, &$filearr)
    {
        $dh = dir($indir);
        while($filename = $dh->read())
        {
            $truefile = $indir.'/'.$filename;
            if($filename == "." || $filename == "..")
            {
                continue;
            }
            else if(is_dir($truefile))
            {
                $this->GetMatchFiles($truefile, $fileexp, $filearr);
            }
            else if(preg_match("/\.(".$fileexp.")/i",$filename))
            {
                $filearr[] = $truefile;
            }
        }
        $dh->close();
    }
    */
}

//目录文件大小检测类
class SpaceUse
{
    var $totalsize=0;

    function checksize($indir)
    {
        $dh=dir($indir);
        while($filename=$dh->read())
        {
            if(!preg_match("#^\.#", $filename))
            {
                if(is_dir("$indir/$filename"))
                {
                    $this->checksize("$indir/$filename");
                }
                else
                {
                    $this->totalsize=$this->totalsize + filesize("$indir/$filename");
                }
            }
        }
    }

    function setkb($size)
    {
        $size=$size/1024;

        if($size>0)
        {
            list($t1,$t2)=explode(".",$size);
            $size=$t1.".".substr($t2,0,1);
        }
        return $size;
    }

    function setmb($size)
    {
        $size=$size/1024/1024;
        if($size>0)
        {
            list($t1,$t2)=explode(".",$size);
            $size=$t1.".".substr($t2,0,2);
        }
        return $size;
    }
}