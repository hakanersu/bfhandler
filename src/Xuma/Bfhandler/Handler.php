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

    protected $oldStep = false;

    protected $step = false;

    public function __construct(Builder $builder,$config)
    {
        CacheManager::setup(["storage"=>$config['storage'],"path"=>$config['path']]);

        CacheManager::CachingMethod("phpfastcache");

        if(array_diff(['uuid', 'caller', 'callee','step'],array_keys($_POST))) {
            throw new Exception("Please check values");
        }

        $this->cache = CacheManager::getInstance();

        $this->config = $config;

        $this->builder = $builder;

        $this->uuid = $_POST['uuid'];

        $this->step = $_POST['step'];

        if(isset($_POST['returnvar'])) {
            $previous = ($this->step - 1);
            $this->cache->set("{$this->uuid}-step-{$previous}",$_POST['returnvar']);
            $this->cache->set("{$this->uuid}-response",$_POST['returnvar'],60);
        }
    }

    /**
     * If input equal to given value.
     * @param $input
     * @return $this|Mock
     */
    public function ifInput($input)
    {
        if($input==$this->get("response")) {
            return $this;
        }
        return new Mock;
    }

    /**
     * If step value eqaul
     * @param $step
     * @return $this|Mock
     */
    public function ifStep($step)
    {
        $this->oldStep = $step;
        return $this;
    }

    public function equal($value)
    {
        if($value == $this->get("step-{$this->oldStep}")) {
            return $this;
        }
        return new Mock;
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
        $value = $func($this->get("response"));
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
            'min_digits'    => $min,
            'max_digits'    => $max,
            'max_attempts'  => $attempt,
            'ask'           => $this->getSoundFile($ask),
            'play_on_error' => $error ? $error : $this->config['soundFiles']['error'],
            'variable_name' => 'returnvar'
        ])->build(true);
        return $this;
    }

    /**
     * Gather if execute function fails.
     * @param $ask
     * @param bool $error
     * @param int $min
     * @param int $max
     * @param int $attempt
     * @return $this
     */
    public function gatherIfFails($ask,$error = false,$min=3,$max=10,$attempt=3)
    {
        if(!$this->response) {
            $this->gather($ask,$error,$min,$max,$attempt);
        }
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

    /**
     * Remove item from cache
     *
     * @param $name
     */
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
        if(!$this->response) {
            $this->play($name);
        }
        return $this;
    }

    /**
     * Play given sound file
     *
     * @param $name
     * @return $this
     */
    public function play($name)
    {
        $this->builder->play($this->getSoundFile($name))->build(true);
        return $this;
    }

    /**
     * Dial given number.
     * @param $number
     */
    public function dial($number)
    {
        $this->builder->dial($number)->build(true);
    }

    /**
     * Hangup call.
     */
    public function hangup()
    {
        $this->builder->hangup()->build(true);
    }

    /**
     * Set caller for current call.
     * @param $name
     */
    public function setCaller($name)
    {
        $this->builder->set_caller($name)->build(true);
    }

    protected function getSoundFile($name)
    {
        if(array_key_exists($name,$this->config['soundFiles'])) {
            return $this->config['soundFiles'][$name];
        }
        return $name;
    }
}