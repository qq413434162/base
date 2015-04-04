<?php
/*
 * 方法缓存类
 * @description:
 * 调用范例
 * public function __call($method, $arguments) {
        if ($this->config->item('is_cached')) {
            return $this->cached->call($this, $method, $arguments);
        } else {
        	Cached::_call_do($this, $method, $arguments);
        }
    }
 * 要添加缓存方法的话：通过在要缓存的方法前面添加前缀call_,例如base_model:getlist()添加call_前缀得到base_model:call_getlist()
 * 原有代码调用方法时,会被拦截器方法__call拦截处理,通过在数据表cached填写要缓存对应的信息，
 * 有应用(application)、类名(class)、方法(method),主要缓存(adapter)、次要缓存(backup)、缓存时间(timeout)来缓存结果
 * 要添加删除缓存方法：通过在要删除缓存的方法前面添加前缀call_,例如base_model:edit()添加call_前缀得到base_model:call_edit()
 * 原有代码调用方法时,会被拦截器方法__call拦截处理,通过在数据表cached_destroy填写要缓存对应的信息，
 * 有应用(application)、类名(class)、方法(method)来和数据库cached_relation关联得到要删除的缓存。
 * @author wubaorong
 * @date 20131113 
 */
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Cached {
	protected $_ci;    // CodeIgniter instance
	protected $_that;  // Current $this
	protected $_db;    //db
	protected $_sign = ':';
	static public $_callsign = 'call_';
	protected $_cachename = 'mai:cached';
	protected $_reholder;
	function __construct($config = '') { //顶层和上层缓存
		$this->_ci = & get_instance ();
		$this->_reholder = $this->_ci->cache->file;
		//log_message ( 'debug', 'Cached Class Initialized' );
		$this->_db = $this->_ci->load->database ( "cached", TRUE );
	}

	public function call($that, $method, $arguments) {
		$this->_that = $that;
		/*
		 * 准备参数
		 */
		$get = array ();
		$get ['application'] = trim ( APPPATH, '/' );
		$get ['class'] = get_class ( $this->_that );
		$get ['method'] = $method;
		$get ['arguments'] = is_array ( $arguments ) ? http_build_query ( $arguments ) : $arguments;
		$get = array_map ( 'strtolower', $get );
		/*
		 * 载入配置
		 */
		$this->_db->select('application, class, method, adapter, backup, timeout');
		$this->_db->where ( 'application', $get ['application'] );
		$this->_db->where ( 'class', $get ['class'] );
		$this->_db->where ( 'method', $get ['method'] );
		$this->_db->where ( 'timeout >', 0 );
		$query = $this->_db->get ( "cached" );
		$config = $query->first_row ( "array" );
		$this->_db->close ();
		if (! $config) {
			//echo 'no exists config';
			$rs = $this->_destroy ( $get ['application'], $get ['class'], $get ['method'] );
			return self::_call_do ($this->_that, $method, $arguments );
		}

		/*
		 * 配置存在，进行缓存操作
		 */
		$this->_ci->load->driver ( 'cache', array ('adapter' => $config ['adapter'], 'backup' => $config ['backup'] ) );
		//获取当前的缓存键名
		$keyname = $this->_keyname ( $get );
		//检查是否存在
		if (! $data = $this->_call_get ( $keyname )) { //不存在,先缓存
			//echo 'no exists cache';
			$data = self::_call_do ($this->_that, $method, $arguments );
			if ($data) {
				//echo 'saving cache';
				$rs = $this->_call_savecache ( $keyname, $data, $config ['timeout'] );
			}
		}
		return $data;
	}

	/**
	 * 构造键名
	 */
	protected function _call_keyname($get) {
		$keyname = implode ( $this->_sign, $get );
		return $keyname;
	}

	/**
	 * 第二层缓存键名
	 */
	protected function _call_parentname($get) {
		$keyparent = array ();
		$keyparent [] = $get ['application'];
		$keyparent [] = $get ['class'];
		$keyparent [] = $get ['method'];
		$keyparent = implode ( $this->_sign, $keyparent );
		return $keyparent;
	}
	/**
	 * 第三层键名加密
	 */
	protected function _serkey($keyname) {
		$rs = md5($keyname);
		return $rs;
	}

	/**
	 * 返回内容
	 */
	protected function _call_get($keyname) {
		$rs = $this->_ci->cache->get ( $this->_serkey($keyname) );
		return $rs;
	}

	/**
	 * 存储内容
	 */
	protected function _call_savecache($keyname, $data, $ttl = 30) {
		return $this->_ci->cache->save ( $this->_serkey($keyname), $data, $ttl );
	}

	/**
	 * 删除内容
	 */
	protected function _call_delete($keyname) {
		return $this->_ci->cache->delete ( $this->_serkey($keyname) );
	}

	/**
	 * 响应调用方法
	 */
	static function _call_do($that, $method, $arguments) {
		return call_user_func_array ( array ($that, self::$_callsign . $method ), $arguments );
	}

	/**
	 * 返回内容
	 */
	protected function _reholder_get($get) {
		return $this->_reholder->get ( $this->_serkey($get)  );
	}

	/**
	 * 存储内容
	 */
	protected function _reholder_save($get, $data, $ttl) {
		return $this->_reholder->save ( $this->_serkey($get), $data, $ttl );
	}

	/**
	 * 删除内容
	 */
	protected function _reholder_delete($get) {
		return @$this->_reholder->delete ( $this->_serkey($get) );
	}

	/**
	 * 将缓存键值存入第二层缓存空间
	 */
	protected function _call_parent($keyname, $get, $ttl = 30) {
		$keyname = $this->_serkey($keyname);
		$keyparent = $this->_call_parentname ( $get );

		//顶层缓存
		$data = $this->_reholder_get ( $this->_cachename );
		if (is_array ( $data )) {
			if(!isset($data [$keyparent])){
				$data [$keyparent] = array ();
			}
		} else {
			$data = array ($keyparent => array () );
		}
		$rs = $this->_reholder_save ( $this->_cachename, $data, $ttl );

		//第二层缓存
		$data = $this->_reholder_get ( $keyparent );
		if (is_array ( $data )) {
			if(!isset($data [$keyname])){
				$data [$keyname] = array ();
			}
		} else {
			$data = array ($keyname => array () );
		}
		$rs = $this->_reholder_save ( $keyparent, $data, $ttl );
		return $rs;
	}

	/**
	 * 构建缓存空间并且返回键名
	 */
	protected function _keyname($get) {
		$keyname = $this->_call_keyname ( $get );
		//调用第二层缓存逻辑
		$rs = $this->_call_parent ( $keyname, $get );
		return $keyname;
	}

	/**
	 * 清理空间
	 */
	protected function _destroy($application, $class, $method) {
		$rs = FALSE;
		$param = array ();
		$where = array (1);
		$where [] = "destroy.application = '{$application}'";
		$where [] = "destroy.class = '{$class}'";
		$where [] = "destroy.method = '{$method}'";
		$param [] = 'cached.application, cached.class, cached.method, cached.adapter, cached.backup';
		$sql = "SELECT " . implode ( ',', $param ) . "
				FROM {$this->_db->dbprefix}cached AS cached
				LEFT JOIN {$this->_db->dbprefix}cached_relation AS relation ON cached.id = relation.cached_id
				LEFT JOIN {$this->_db->dbprefix}cached_destroy AS destroy ON destroy.id = relation.destroy_id
				WHERE " . implode ( ' AND ', $where ) . "
				GROUP BY cached.id
				ORDER BY cached.id DESC";
		$query = $this->_db->query ( $sql );
		$config = $query->first_row ( "array" );
		$this->_db->close ();
		if ($config) {
			//echo 'startint destroy parent';
			$this->_ci->load->driver ( 'cache', array ('adapter' => $config ['adapter'], 'backup' => $config ['backup'] ) );
			$rs = $this->_destroy_parent ( $config );
		}
		return $rs;
	}

	/**
	 * 清理第二层缓存
	 */
	protected function _destroy_parent($get) {
		$keyparent = $this->_call_parentname ( $get );
		$data = $this->_reholder_get ( $keyparent );
		//清理第二空间存储的键
		if ($data) {
			foreach ( $data as $key => $val ) {
				$this->_ci->cache->delete ( $key );
			}
		}
		//清理空间
		return $this->_reholder_delete ( $keyparent );
	}

	/**
	 * 清理顶层缓存
	 */
	protected function _destroy_top($thatkey) {
		$data = $this->_reholder_get ( $this->_cachename );
		if ($data) {
			if (empty($thatkey)) {
				foreach ( $data as $key => $val ) {
					$this->_reholder->delete ( $key );
				}
				//清理空间
				return $this->_reholder->delete ( $this->_cachename );
			} else if (isset($data[$thatkey])){
				$rs = $this->_reholder->delete ( $thatkey );
				if ($rs) {
					unset($data[$thatkey]);
				}
				//清理第二层缓存应用
				return $this->_reholder_save ( $this->_cachename, $data);
			}
		}
		return FALSE;
	}
}

/* End of file Cached.php */
/* Location: ./application/libraries/Cached.php */