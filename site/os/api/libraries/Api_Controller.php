<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

class Api_Controller extends REST_Controller {

    private $model;
    protected $act_lang = array();
    
    public function __construct() {
        parent::__construct();
    }
    protected function _takeVerify($data)
    {
        $beforeSign = $data['api_sign'];
        unset($data['api_sign'], $data['c'], $data['m']);

        $this->config->load('site');
        $api_secret = $this->config->item('api_secret');
        $data['api_secret'] = $api_secret;

        $afterSign = $this->_getSign($data);
        if ($beforeSign != $afterSign) {
            $this->output(FALSE, '500', "verify not right");die;
        }
    }

    /*
     * 新积分 Api 签名
	 * @access private
     */
    private function _getSign($data)
    {
        $sign = '';
        if (is_array($data)
            && !empty($data['api_key'])
            && !empty($data['api_secret'])) {
            $apiSecret = $data['api_secret'];
            unset($data['api_secret']);
            ksort($data);
            $sign .= implode('', $data);
            $sign .= $apiSecret;
            $sign = md5($sign);
        }
        return $sign;
    }

    /*
     * default load model handler
     */

    protected function model() {
        if (!$this->model) {
            $name = $this->router->class . '_model';
            $this->load->model($name);
            if (isset($this->$name)) {
                $this->model = $this->$name;
            } else {
                show_error("model load fail");
            }
        }
        return $this->model;
    }
    
    private function _load_lang() {
        if (!$this->act_lang) {
            $this->act_lang = $this->lang->load('form_validation', 'chinese', TRUE);
        }
        return $this->act_lang;
    }
    
    protected function output($data, $sign = '200', $message = '') {
        $act_lang = $this->_load_lang();
        $message = isset($act_lang[$message]) ? $act_lang[$message] : $message;
        $data = array(
            'result'    => $data,
            'code'    => $sign,
            'message' => $message,
        );
        $this->response($data, 200);
    }
    

    /*
     * 过滤函数
     *
     * @param Array $data 过滤变量及其过滤规则
     * @param Array $requesdata 从该数组抽出变量进行过滤
     * @return Array
     */
    public function verify($data, $requesdata = NULL) {
        /*
         * 执行验证流程
         *
         * @param All $key 要过滤的变量值
         * @param Array $value 要过滤的规则
         * @return Array
         */
        function do_verify($key, $value) {

            $k = $key;
            //默认是过滤标记，记录着过滤时候通过状态
            $sign = TRUE;
            //严格类型验证，默认是false
            if (!isset($value['forced'])) {
                $value['forced'] = false;
            }
            if ($sign && ($k !== NULL) && isset($value['type']) && $value['type']) {
                switch($value['type']) {
                    case 'integer': {
                        if (!$value['forced']) {
                            $k = (int)$k;
                        }
                        if (isset($value['range']) && $value['range']) {
                            list($begin, $end) = $value['range'];
                            if ($begin > $k || $end < $k) {
                                $sign = FALSE;
                            }
                        }
                    };break;
                    case 'url' : {
                        $rs = preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $k);
                        if (!$rs) {
                            $sign = FALSE;
                            break;
                        }
                    }
                    case 'string': {
                        if (!$value['forced']) {
                            $k = (string)$k;
                            $k = trim($k);
                        }
                        if (isset($value['range']) && $value['range']) {
                            list($begin, $end) = $value['range'];
                            $strlen = strlen($k);
                            if ($begin > $strlen || $end < $strlen) {
                                $sign = FALSE;
                            }
                        }
                    };break;
                    case 'range': {
                        if (isset($value['range']) && $value['range'] && is_array($value['range'])) {
                            $rs = array_search($k, $value['range'], $value['forced']);
                            if (FALSE === $rs) {
                                $sign = FALSE;
                            }
                        }
                    };break;
                }
            }
            $m = array();
            //如果状态失败
            if (!$sign) {
                $sign = FALSE;
            } else {
                if($value['required'] == TRUE && ($k === NULL) ){
                    $sign = FALSE;
                }else{
                    $m['value'] = $k;
                    $sign = TRUE;
                }
            }
           if (isset($value['default']) && $sign == FALSE || ($k === NULL)) { //如果设定了默认值并且不通过 就使用默认值
                $m['value'] = $value['default'];
                $sign = TRUE;
            }
            if($sign == FALSE){
                $m['message'] = $value['message'];
            }
            $m['sign'] = $sign;
            return $m;
        }
        
        
        if (!$data) return $data;
        
        $tmp = $temp = array();
        foreach ($data as $key => $value) {
            
            $k = $key;
            //接受数据源
            if (isset($requesdata)) {
                if (isset($requesdata[$key])) {
                    $k = $requesdata[$key] === FALSE ? '' : $requesdata[$key];
                } else {
                    $k = NULL;
                }
            }
            //如果是多参数
            if (isset($value['separator']) && $value['separator'] && !is_array($k) && FALSE !== strpos($k, $value['separator']))  {
                $k = explode($value['separator'], $k);
            }
            $isarray = true;
            if (!is_array($k)) {
                $isarray = false;
                $k = array($k);
                //$k = array_filter($k);
            }
            $tm = array();
            foreach($k as $v) {
                $tmp = do_verify($v, $value);
                if (isset($tmp['sign']) && $tmp['sign'] != TRUE) { //错误了就直接返回
                    return $this->output(array(), $tmp);
                }
                if (isset($tmp['value'])) { //有值表示了验证通过,收集起来返回
                    $tm[] = $tmp['value'];
                }
            }
            if ($tm) {
                if (!$isarray) {
                    $tm = implode('', $tm);
                }
                if (isset($requesdata)) { //如果从数据源接受就按照数据源的键值返回
                    $temp[$key] = $tm;
                } else {
                    $temp[] = $tm;
                }
            }
        }
        return $temp;
    }
}