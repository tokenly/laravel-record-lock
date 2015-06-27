<?php

namespace Tokenly\RecordLock\Facade;

use Exception;
use Illuminate\Support\Facades\Facade;

/**
* RecordLock facade
*/
class RecordLock extends Facade {

    protected static function getFacadeAccessor() { return 'recordlock'; }

}


