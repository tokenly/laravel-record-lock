# Record Lock

A Laravel library for creating a shared lock across an application.  Requires a MySQL database connection.


# Installation

### Add the Laravel package via composer

```
composer require tokenly/laravel-record-lock
```

### Add the Service Provider

Add the following to the `providers` array in your application config:

```
Tokenly\RecordLock\Provider\RecordLockServiceProvider::class
```


### Use it

```php

use Tokenly\RecordLock\Facade\RecordLock;

$lock_id = 'plant-garden-once';
$planted = RecordLock::acquireAndExecute($lock_id, function() {
    // plant the garden
    //   only one process should do this at a time
    //   other processes will block until this is complete
    sleep(1);

    return true;
});

```
