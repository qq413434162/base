<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/Api_Controller.php';

class Archives extends Api_Controller
{
    private $_validate = array(
        'path' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'path'),
        'host' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'host'),
        'content' => array('type' => 'string', 'range' => array(0, 100000), 'default' => '', 'message' => 'content'),
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
        $this->_mkdir(dirname($path));
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

    public function del_get()
    {
        $get = array();
        $get = $this->field_check('get');

        $path = $get['path'];
        $path = BASE . '/' . ltrim($path, '/');
        $path = str_replace("\\", '/', $path);
        $rs = @unlink($path);
        $this->output($rs);
    }
}