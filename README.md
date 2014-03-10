stathat.com
===========

Class for working with API of stathat based on stathat.php from:
```
PHP Library: https://www.stathat.com/downloads/stathat.php
PHP usage: https://www.stathat.com/code/new/php
```

Prepared to be Symfony2 Bundle

Example
-------
```
$statHat = new StatHat(new ASyncStatHat(new StatHatRequest(), array(
    'key' => '***************************',
    'userKey' => '********************',
    'email' => '****************'
)));

for ($i = 0; $i < 20; $i++) {
    $statHat->count('test', $i);
    $statHat->ezCount('test', $i);
}
```
