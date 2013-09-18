<?php

namespace bolt\cache;
use \b;

// localize
use \Memcached as MC;

class memcached extends base {

    const NAME = 'memcached';

    private $_handle;

    public function __construct($config) {


        // name of perssistante instance
        $name = b::config("name", false, $config);

        // handle
        $this->_handle = new MC($name);

        // hosts
        if (b::param('servers', false, $config)) {
            foreach ($config['servers'] as $host) {
                if (is_string($host)) { $host = explode(':', $host);}
                if (!isset($host[1])) {$host[1] = 11211;}
                $this->addServer($host[0], $host[1]);
            }
        }

        // namespace
        if (b::config('namespace', false, $config)) {
            $this->_handle->setOption(MC::OPT_PREFIX_KEY, $config['namespace']);
        }

    }

    public function addServer($host, $port=11211, $weight=null) {
        if (!$this->serverExists($host, $port)) {
            $this->_handle->addServer($host, $port, $weight);
        }
        return $this;
    }

    public function serverExists($host, $port=11211) {
        $servers = $this->_handle->getServerList();
        foreach ($servers as $server) {
            if ($server['host'] == $host AND $server['port'] == $port) {
                return true;
            }
        }
        return false;
    }

    public function __call($name, $args) {
        if (method_exists($this->_handle, $name)) {
            return call_user_func_array(array($this->_handle, $name), $args);
        }
        return false;
    }

}