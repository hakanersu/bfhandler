<?php
namespace Xuma\Bfhandler;

use Xuma\Bfxm\Builder;
use phpFastCache\CacheManager;

class Handler{
    protected $uuid;

    protected $cache;

    protected $config;

    protected $builder;

    protected $response;

    protected $step = false;

    public function __construct(Builder $builder,$config)
    {
        $this->builder = $builder;

        $this->config = $config;

        $config = ["storage"=>$config['storage'],"path"=>$config['path']];

        CacheManager::setup($config);

        CacheManager::CachingMethod("phpfastcache");

        $this->cache = CacheManager::getInstance();

        if(array_diff(['uuid', 'caller', 'callee','step'],array_keys($_POST))) {
            throw new Exception("Please check values");
        }
        $this->step = $_POST['step'];

        $this->uuid = $_POST['uuid'];

        if(isset($_POST['returnvar'])) {
           $this->cache->set("{$this->uuid}-response",$_POST['returnvar'],60);
        }
    }

    /**
     * If step is equal to current step return object
     * else return new Mock object.
     *
     * @param $value
     * @return $this|Mock
     */
    public function step($value)
    {
        if($value != $this->step) {
            return new Mock();
        }
        $this->step = $value;
        return $this;
    }

    /**
     * Execute given function.
     *
     * @param $func
     * @return $this
     */
    public function execute($func)
    {
        $value = $func($this->get('response'));
        $this->response = $value;
        return $this;
    }

    /**
     * Gather user input.
     * 
     * @param $ask
     * @param bool $error
     * @param int $min
     * @param int $max
     * @param int $attempt
     * @return $this
     */
    public function gather($ask,$error = false,$min=3,$max=10,$attempt=3)
    {
        $this->builder->gather([
            'min_digits'=>$min,
            'max_digits'=>$max,
            'max_attempts'=>$attempt,
            'ask'=>$ask,
            'play_on_error'=> $error ? $error : $this->config['soundFiles']['error'],
            'variable_name'=>'returnvar'
        ])->build(true);
        return $this;
    }

    /**
     * Persist given value with cache file.
     *
     * @param $name
     * @return $this
     */
    public function persist($name)
    {
        $this->cache->set("{$this->uuid}-{$name}",$this->response,60);
        return $this;
    }

    /**
     * Get value from cache file.
     *
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->cache->get("{$this->uuid}-{$name}");
    }

    public function remove($name)
    {
        $this->cache->delete($name);
    }

    /**
     * Clear all cache.
     */
    public function clean()
    {
        $this->cache->clean();
    }

    /**
     * Play if executed function fails.
     *
     * @param $name
     * @return $this|Mock
     */
    public function playIfFails($name)
    {
        if($this->response) {
            $this->play($name);
            return $this;
        }
        return new Mock();
    }

    /**
     * Play given sound file
     *
     * @param $name
     * @return $this
     */
    public function play($name)
    {
        if(in_array($name,$this->config['soundFiles'])) {
            $name = $this->config['soundFiles'][$name];
        }
        $this->builder->play($name)->build(true);
        return $this;
    }
}