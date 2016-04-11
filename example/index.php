<?php
include '../vendor/autoload.php';
use Xuma\Bfxm\Builder;
use Xuma\Bfhandler\Handler;

$config = [
    'storage'=>'files',
    'path'=> '/home/xuma/Desktop/bfbuilder/storage',
    'soundFiles'=> [
        'welcome'           => 'http://bfxmdemo.bulutfon.com/demosesler/demo-hosgeldiniz.mp3',
        'error'             => 'http://bfxmdemo.bulutfon.com/demosesler/hatali-giris.mp3',
        'ask-customer-number'   => 'http://bfxmdemo.bulutfon.com/demosesler/numara-tuslayiniz.mp3',
        'ask-password'      => 'http://bfxmdemo.bulutfon.com/demosesler/numara-tuslayiniz.mp3'
    ]
];

// Bulutfon handler'i , bulutfon builder ile baslatalim.
$handler = new Handler(new Builder,$config);

// Birinci asamada kullanicidan musteri numarasi isteyelim.
// Gather methodu herzaman kullanicidan alinan degeri $response degiskeni olarak dondurur.
$handler->step(1)->gather('ask-customer-number');

// Gelen degeri execute ile istedigimiz sekilde isleyebiliriz.
// Sonrasinda ise persist methodu ile fonksitondan donen degeri sonraki istek icin
// cache dosyasinda saklayabiliriz.
$handler->step(2)->execute(function($response) {
    return ($response*2);
})
    ->playIfFails('error')
    ->persist('customerNumber')
    ->gather('ask-password');

// Yukarida kaydemis oldugumuz customer numarasini
// $handler use ile kullanarak fonksiyonumuzda kullanabiliriz.
$handler->step(3)->execute(function($response) use($handler){
    return $handler->get('customerNumber')*3;
})->persist('passwordResult');
