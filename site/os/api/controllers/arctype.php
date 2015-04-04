<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/Api_Controller.php';

class Arctype extends Api_Controller
{
    private $_validate = array(
        'path' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'path'),
        'host' => array('type' => 'string', 'range' => array(1, 255), 'message' => 'host'),
        'content' => array('type' => 'string', 'range' => array(0, 10000), 'message' => 'content'),
        'chmod' => array('type' => 'integer', 'range' => array(0, 1000), 'default' => 777, 'message' => 'chmod'),
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

        $rs = file_put_contents(BASE . '/' . ltrim($get['path'], '/'), $get['content']);
        $this->output($rs);
    }

    public function makeDir_get()
    {
        $get = array();
        $get = $this->field_check('get');
        $rs = mkdir(BASE . '/' . ltrim($get['path'], '/'), $get['chmod']);
        $this->output($rs);
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