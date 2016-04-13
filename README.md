### BulutfonXM Handler

BulutfonXM handler oldukca basit sekilde bulutfon xm adimlari olusturmanizi saglar.



### Ornek
Ornegimizde kullanici id'si ve telefon sifresi kontrolu yapalim.

```php
include '../vendor/autoload.php';
use Xuma\Bfxm\Builder;
use Xuma\Bfhandler\Handler;

// Lutfen storage yolunu web uzerinden erisilemeyecek bir dizinde tutunuz.
// hernekadar .htaccess ile korunuyor olsada etkinlestirilmemis sistemlerde
// veya nginx'de sikinti yasamaniza yol acabilir.
$config = [
    'storage'=>'files',
    'path'=> '/home/xuma/Desktop/bfbuilder/storage',
    'soundFiles'=> [
        'welcome'               => 'http://bfxmdemo.bulutfon.com/demosesler/demo-hosgeldiniz.mp3',
        'error'                 => 'http://bfxmdemo.bulutfon.com/demosesler/hatali-giris.mp3',
        'kullanici-id'          => 'http://benimadresim.com/sesler/kullanici-id-sor.mp3',
        'kullanici-sifre'       => 'http://benimadresim.com/sesler/kullanici-sifre-sor.mp3',
        'tesekkurler'           => 'http://bfxmdemo.bulutfon.com/demosesler/tesekkurler.mp3'
    ]
];

// Yeni bir baglanti olusturalim
$db = new PDO('mysql:host=localhost;dbname=veritabanim;charset=utf8', 'kullanicim', 'sifrem');


// Bulutfon handler'i , bulutfon builder ile baslatalim.
$handler = new Handler(new Builder,$config);

// Kullanicidan musteri numarasini girmesini isteyelim. Bu istek sonrasinda bu deger
// ikinci istegimize gonderilecek.
$handler->step(1)->gather('kullanici-id');

// Ikinci asamada dilersek gelen kullanici id'sini execute fonksiyonu ile
// kontrol edebiliriz.
$handler->step(2)->execute(function($response) use($db) {
    $user = $db->prepare('SELECT * FROM users WHERE id=:id');
    $user->execute([
        'id'=> $response
    ]);
    $result = $user->fetch(PDO::FETCH_OBJ);

    // Kullanici bulunamadigi taktirde false dondurebilir
    // ve playIfFails gibi fonksiyonlari calistirabiliriz.
    if(!$result->id) {
        return false;
    }
    // Burada dilerseniz true dondurebilirsiniz veya bu degerin bir sonraki
    // asamaya gecmesini istiyorsaniz direkt degeri dondurup persist() 
    // fonksiyonunu kullanabilirsiniz.
    return $result->id;
})
    // Eger execute fonksiyonu false donerse calisacaktir ve
    // bu asamada bu adim icin geri kalan islemler calismayacaktir.
    ->playIfFails('error')
    // Kullanici id'sini customerNumber olarak bir sonraki adima gonderebiliriz.
    ->persist('customerNumber')
    // Eger kullanici bulunduysa bu adimda kullanici telefon sifresini sorabiliriz.
    ->gather('kullanici-sifre');

// Bir onceki asamadan gerekli degerleri kullanabilmek icin handler objesini
// use ile kullanmamiz gerekmekte.
$handler->step(3)->execute(function($response) use($handler,$db){
    $user = $db->prepare('SELECT * FROM phonepasswords WHERE user_id=:id and password=:password');
    $user->execute([
        'id'        => $handler->get('customerNumber'),
        'password'  => $response
    ]);
    $result = $user->fetch(PDO::FETCH_OBJ);

    if(!$result->value) {
        return false;
    }
    // Bu asamadan sonra simdilik tesekkur edecegimiz icin
    // true dondurebiliriz.
    return true;
})->playIfFails('error')->play('tesekkurler');
```

Sql sorgularinin oldugu bir yapi hosunuza gitmiyorsa biraz daha soyutlayabilirsiniz.

```php
<?php
class User
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getUser($customerId)
    {
        $user = $this->db->prepare('SELECT * FROM users WHERE id=:id');

        $user->execute([
            'id'=> $customerId
        ]);

        $result = $user->fetch(PDO::FETCH_OBJ);

        if(!$result->id) {
            return false;
        }

        return $result->id;
    }

    public function checkUserPassword($id,$response)
    {
        $user = $this->db->prepare('SELECT * FROM phonepasswords WHERE user_id=:id and password=:password');

        $user->execute([
            'id'        => $id,
            'password'  => $response
        ]);

        $result = $user->fetch(PDO::FETCH_OBJ);

        if(!$result->value) {
            return false;
        }

        return true;
    }

}
```

```php
$handler = new Handler(new Builder,$config);

$db = new PDO('mysql:host=localhost;dbname=veritabanim;charset=utf8', 'kullanicim', 'sifrem');

$customer = new User($db);

$handler->step(1)->gather('kullanici-id');

$handler->step(2)->execute(function($response) use($customer) {
    return $customer->getUser() ?: false;
})->playIfFails('error')->persist('customerNumber')->gather('kullanici-sifre');

$handler->step(3)->execute(function($response) use($handler,$customer){
    return $customer->checkUserPassword($handler->get('customerNumber'),$response);
})->playIfFails('error')->play('tesekkurler');
```

### Karmasik Menu Ornegi
```php
$handler->step(1)->gather('karsilama');

// Step 2
$handler->step(2)->input(1)->gather('musteri-numarasi');
$handler->step(2)->input(2)->gather('ziyaretci');
$handler->step(2)->input(3)->execute(function($response){ return $response; })->persist('hukiki')->play('kaydedilecek');
$handler->step(2)->input(9)->execute(function($response){ return $response; })->persist('ingilizce')->play('kaydedilecek');


// Step 3
$handler->step(3)->ifStep(1)->equal(1)->execute(function($response) use($client){
    return $client->client($response) ?: false;
})->playIfFails('hatali-giris')
  ->persist('customerNumber')
  ->gather('musteri-pin');

$handler->step(3)->ifStep(1)->equal(2)->execute(function($response) {
    if($response == 1) return 12;
    if($response == 2) return 13;
    if($response == 3) return 14;
    return false;
})->playIfFails('hatali-giris')
  ->persist('musteri-menu')
  ->play('kaydedilecek');
$handler->step(3)->ifStep(1)->equal(3)->dial($handler->get('hukiki'));
$handler->step(3)->ifStep(1)->equal(9)->dial($handler->get('ingilizce'));

// Step 4.
$handler->step(4)->ifStep(1)->equal(1)->execute(function($response) use($handler,$client){
    return $client->password($handler->get('customerNumber'),$response);
})->playIfFails('hatali-giris')
  ->gather('giris-yapmis');

$handler->step(4)->ifStep(1)->equal(2)->dial($handler->get('musteri-menu'));


// Step 5
$handler->step(5)->execute(function($response) {
    if($response == 1) return 13;
    if($response == 2) return 14;
    if($response == 2) return 15;
    return false;
})->playIfFails('hatali-giris')
  ->persist('hedef')
  ->play('kaydedilecek');

// Step 6
$handler->step(6)->dial($handler->get('hedef'));
```

## TODO

* Bfxm fonksiyonlarinin geri kalanlari eklenmeli.
  * set_caller
  * say
  * dial