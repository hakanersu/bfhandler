<?php
include '../vendor/autoload.php';
use Xuma\Bfxm\Builder;
use Xuma\Bfhandler\Handler;

$config = [
    'storage'=>'files',
    'path'=> '/home/xuma/Desktop/bfbuilder/storage'
];
$bfxm = new Builder;
$handler = new Handler($bfxm,$config);

$handler->step(1)->gather('ask','Ask customer number?');

$handler->step(2)->execute(function($response) {
    return ($response*2);
})
    ->playIfFails('asdfasd')
    ->persist('customerNumber')
    ->gather('ask','Ask password?');
$handler->step(3)->execute(function($response) use($handler){
    return $handler->get('customerNumber')*3;
})->persist('passwordResult');
