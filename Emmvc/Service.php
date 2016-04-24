<?php
/**
 * service locator
 * 
 * 配置
 *  'db'    => [
 *       'class'     => '\stdClass' ,
 *       'prefix'    => 'tie' ,
 *       'tt'        => 'tieow'
 *   ]
 * 
 * 调用
 * service::db();
 * 
 * 设置 service::self()->std  = function()
 * {
 *      return new \stdClass;
 * }
 * 
 * 调用
 * service::std($args);
 * 
 * @author jk.qi
 */


namespace Emmvc;


class Service
{
    /**
     * 服务配置文件
     * @var array
     */
    private $config;
    /**
     * 保存 __set($name, $args)
     * @var array
     */
    private $__set;
    /**
     * 保存已经实例化的对象
     * @var array
     */
    private $__s;
    /**
     * 保存自己
     * @var self
     */
    private static $self;
    
    
    public function __construct($config)
    {
        $this->config    = $config;
    }
    
    public function createObject($name, $args)
    {
        if (isset($this->__set[$name]))
        {
            $class      = $this->__set[$name];
            unset($this->__set[$name]);
            return $class($args);
        }
        
        foreach ($this->config as $config)
            foreach ($config as $key => $obj_config)
            {
                if ($key == $name)
                {
                    if (empty($obj_config['class']))
                        throw new \Exception('service class not config');
                    $class  = new $obj_config['class']($args);
                    unset($obj_config['class']);

                    foreach ($obj_config as $key => $value)
                        $class->$key    = $value;
                    return $class;
                }
            }
        
        throw new \Exception('service not config');
    }
    
    public function __set($name, $args)
    {
        if (empty($this->__set[$name]))
            $this->__set[$name] = $args;
    }
    
    public function __call($name, $args)
    {
        if (empty($this->__s[$name]))
        {
            $this->__s[$name] = $this->createObject($name, $args);
        }
        return $this->__s[$name];
    }
    
    public function __get($name)
    {
    	return $this->__call($name, []);
    }
    
    public static function __callstatic($name, $args)
    {
        if (empty(self::$self))
        {
            self::$self     = new self(config::instance()->service);
        }
        
        if ($name == 'self')
            return self::$self;
        
        return self::$self->$name( $args );
    }
    
    
    
}


