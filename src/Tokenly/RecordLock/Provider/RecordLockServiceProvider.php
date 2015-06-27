<?php

namespace Tokenly\RecordLock\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;

class RecordLockServiceProvider extends ServiceProvider {

    public function register() {

        $this->app->bind('recordlock', function($app) {
            return app('Tokenly\RecordLock\RecordLock');
        });

    }



    

}
