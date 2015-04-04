<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/Api_Controller.php';

class Source extends Api_Controller
{
    private $_validate = array(
        'path' => array('type' => 'string', 'range' => array(0, 255), 'default' => '', 'message' => 'path'),
        'host' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'host'),
        'content' => array('type' => 'string', 'range' => array(0, 100000), 'default' => '', 'message' => 'content'),

        'oldname' => array('type' => 'string', 'range' => array(0, 255), 'default' => '', 'message' => 'oldname'),
        'newname' => array('type' => 'string', 'range' => array(0, 255), 'default' => '', 'message' => 'newname'),
        'filename' => array('type' => 'string', 'range' => array(0, 255), 'default' => '', 'message' => 'filename'),

        'api_key' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'api_key'),
        'api_secret' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'api_secret'),
    );

    public function __construct()
    {
        parent::__construct();
    }

    private function field_check($act = 'get')
    {
        $this->_takeVerify($this->$act(NULL, TRUE));
        $get = $this->verify($this->_validate, $this->$act(NULL, TRUE));
        return $get;
    }

    public function make_post()
    {
        $get = array();
        $get = $this->field_check('post');

        $path = BASE . '/' . ltrim($get['path'], '/');
        $rs = $this->_mkdir(dirname($path));

        $rs = file_put_contents($path, $get['content']);
        $this->output($rs);
    }

    private function _mkdir($path, $mod = 755) {
        $path = str_replace("\\", '/', $path);
        $paths = explode('/', $path);

        $current = '';
        foreach($paths as $val) {
            $current .= ((empty($current)) ? '' : '/') . $val;
            if (strpos($val, ':')) continue;
            if (!file_exists($current)) {
                $rs = mkdir($current, $mod);
                if (!$rs) return $rs;
            }
        }
        return TRUE;
    }

    public function rename_get()
    {
        $get = array();
        $get = $this->field_check('get');

        $oldname = $get['oldname'];
        $newname = $get['newname'];

        $oldname = BASE . '/' . ltrim($oldname, '/');
        $newname = BASE . '/' . ltrim($newname, '/');

        $rs = FALSE;
        if(($newname!=$oldname) && is_writable($oldname))
        {
            $rs = rename($oldname,$newname);
        }
        $this->output($rs);
    }

    public function del_get()
    {
        $get = array();
        $get = $this->field_check('get');

        $path = $get['filename'];
        $path = BASE . '/' . ltrim($path, '/');
        $path = str_replace("\\", '/', $path);
        $rs = @unlink($path);
        $this->output($rs);
    }

    public function move_get()
    {
        $get = array();
        $get = $this->field_check('get');

        $oldfileSite = BASE . '/' . ltrim($get['oldname'], '/');
        $truepath = BASE . '/' . ltrim($get['newname'], '/');
        $mfile = $get['filename'];
        $rs = FALSE;

        if(is_readable($oldfileSite)/* && is_readable($truepath) && is_writable($truepath)*/)
        {
            if(is_dir($truepath))
            {
                $rs = copy($oldfileSite, $truepath."/$mfile");
            }
            else
            {
                mkdir($truepath);
                $rs = copy($oldfileSite, $truepath."/$mfile");
            }
            if ($rs) {
                @unlink($oldfileSite);
            }
        }
        $this->output($rs);
    }
}