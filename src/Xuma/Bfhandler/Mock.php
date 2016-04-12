<?php
namespace Xuma\Bfhandler;

class Mock {
    public function step()              { return $this; }
    public function execute($func)      { return $this; }
    public function gather($ask,$error = false,$min=3,$max=10,$attempt=3){ return $this; }
    public function persist($name)      { return $this; }
    public function get($name)          { return $this; }
    public function remove($name)       { return $this; }
    public function playIfFails($name)  { return $this; }
    public function gatherIfFails($name){ return $this; }
    public function play($name)         { return $this; }
}