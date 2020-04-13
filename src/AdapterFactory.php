<?php
namespace GasShaker\ZendModel;

use Zend\Db\Adapter\Adapter;

final class AdapterFactory
{

    private static $instance = [];

    private $adapter = null;

    private function __construct($driveConf)
    {
        $this->adapter = new Adapter($driveConf);
    }

    private function __clone()
    {
    }

    /**
     * Method  create
     * @desc  ......
     *
     * @author  huangql <hql@GasShaker.com>
     * @static
     * @param array $driveConf
     *
     * @return  AdapterFactory
     */
    public static function create(array $driveConf)
    {
        $name = $driveConf['dsn'];
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = null;
        }
        if (!(self::$instance[$name] instanceof self)) {
            self::$instance[$name] = new self($driveConf);
        }
        return self::$instance[$name];
    }

    public function getAdapter()
    {
        return $this->adapter;
    }
}
