<?php
namespace Xuma\Bfhandler;

use Xuma\Bfxm\Builder;
use phpFastCache\CacheManager;

class Handler{
    protected $uuid;

    protected $cache;

    protected $builder;

    protected $response;

    protected $step = false;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

        $config = array(
            "storage"   => "files",
            "path"      =>  "/home/xuma/Desktop/bfbuilder/storage",
        );

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

    public function step($value)
    {
        if($value != $this->step) {
            return new Mock();
        }
        $this->step = $value;
        return $this;
    }

    public function execute($func)
    {
        $value = $func($this->get('response'));
        $this->response = $value;
        return $this;
    }

    public function gather($type,$voice,$answer=8)
    {
        $this->builder->gather([
            'min_digits'=>3,
            'max_digits'=>7,
            'max_attempts'=>2,
            'ask'=>'http://stage.ni.net.tr/bf/enter_customer_number.mp3',
            'play_on_error'=>'http://bfxmdemo.bulutfon.com/demosesler/hatali-giris.mp3',
            'variable_name'=>'returnvar'
        ])->build(true);
        return $this;
    }

    public function persist($name)
    {
        var_dump($this->response);
        $this->cache->set("{$this->uuid}-{$name}",$this->response,60);
        return $this;
    }

    public function get($name)
    {
        return $this->cache->get("{$this->uuid}-{$name}");
    }

    public function remove($name)
    {
        $this->cache->delete($name);
    }

    public function clean()
    {
        $this->cache->clean();
    }

    public function playIfFails($name)
    {
        if($this->response) {
            $this->play($name);
            return $this;
        }
        return new Mock();
    }

    public function play($name)
    {
        // play
        return $this;
    }
}