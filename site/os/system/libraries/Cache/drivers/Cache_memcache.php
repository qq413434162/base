<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package                CodeIgniter
 * @author                ExpressionEngine Dev Team
 * @copyright        Copyright (c) 2006 - 2011 EllisLab, Inc.
 * @license                http://codeigniter.com/user_guide/license.html
 * @link                http://codeigniter.com
 * @since                Version 2.0
 * @filesource        
 */
 
// ------------------------------------------------------------------------
 
/**
 * CodeIgniter Memcache Caching Class 
 *
 * @package                CodeIgniter
 * @subpackage        Libraries
 * @category        Core
 * @author                lijie
 * @link                
 */
 
class CI_Cache_memcache extends CI_Driver {
 
        private $_memcache;        // Holds the memcache object
 
        protected $_memcache_conf         = array(
                                        'default' => array(
                                                'default_host'                => '192.168.10.175',
                                                'default_port'                => 11211,
                                                'default_weight'        => 1
                                        )
                                );
 
        // ------------------------------------------------------------------------        
 
        /**
         * Fetch from cache
         *
         * @param         mixed                unique key id
         * @return         mixed                data on success/false on failure
         */        
        public function get($id)
        {        
               return $this->_memcache->get($id);
        }
        
 
        // ------------------------------------------------------------------------
 
        /**
         * Save
         *
         * @param         string                unique identifier
         * @param         mixed                data being cached
         * @param         int                        time to live
         * @return         boolean         true on success, false on failure
         */
        public function save($id, $data, $ttl = 60)
        {
                return $this->_memcache->set($id, $data, FALSE, $ttl);
        }
 
        // ------------------------------------------------------------------------
        
        /**
         * Delete from Cache
         *
         * @param         mixed                key to be deleted.
         * @return         boolean         true on success, false on failure
         */
        public function delete($id)
        {
                return $this->_memcache->delete($id);
        }
 
        // ------------------------------------------------------------------------
        
        /**
         * Clean the Cache
         *
         * @return         boolean                false on failure/true on success
         */
        public function clean()
        {
                return $this->_memcache->flush();
        }
 
        // ------------------------------------------------------------------------
 
        /**
         * Cache Info
         *
         * @param         null                type not supported in memcached
         * @return         mixed                 array on success, false on failure
         */
        public function cache_info($type = NULL)
        {
                return $this->_memcache->getStats();
        }
 
 
        // ------------------------------------------------------------------------
 
        /**
         * Setup memcached.
         */
        private function _setup_memcache()
        {
                // Try to load memcached server info from the config file.
                $CI =& get_instance();
                if ($CI->config->load('memcache', TRUE, TRUE))
                {
                        if (is_array($CI->config->config['memcache']))
                        {
                                $this->_memcache_conf = NULL;
 
                                foreach ($CI->config->config['memcache'] as $name => $conf)
                                {
                                        $this->_memcache_conf[$name] = $conf;
                                }                                
                        }                        
                }
                
                $this->_memcache = new Memcache();
                foreach ($this->_memcache_conf as $name => $cache_server)
                {                                               
                        if ( ! isset($cache_server['hostname']))
                        {
                                $cache_server['hostname'] = $this->_memcache_conf['default']['default_host'];
                        }
        
                        if ( ! isset($cache_server['port']))
                        {
                                $cache_server['port'] = $this->_memcache_conf['default']['default_port'];
                        }
        
                        if ( ! isset($cache_server['weight']))
                        {
                                $cache_server['weight'] = $this->_memcache_conf['default']['default_weight'];
                        }
        
                        $this->_memcache->addServer(
                                        $cache_server['hostname'], $cache_server['port'], true, $cache_server['weight']                        
                        );
                        
                }
        }
 
        // ------------------------------------------------------------------------
 
 
        /**
         * Is supported
         *
         * Returns FALSE if memcache is not supported on the system.
         * If it is, we setup the memcache object & return TRUE
         */
        public function is_supported()
        {
                if ( ! extension_loaded('memcache'))
                {
                        log_message('error', 'The Memcache Extension must be loaded to use Memcached Cache.');
                        
                        return FALSE;
                }
                
                $this->_setup_memcache();
                return TRUE;
        }
 
        // ------------------------------------------------------------------------
 
}
// End Class
 
/* End of file Cache_memcache.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcache.php */