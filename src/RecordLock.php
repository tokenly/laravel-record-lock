<?php

namespace Tokenly\RecordLock;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
* RecordLock facade
*/
class RecordLock {

    protected $memory_locks = [];
    protected $lock_stack = [];

    public function __construct() {
    }

    public function acquire($id, $timeout=60) {
        if ($this->isAlreadyLocked($id)) {
            $this->refresh($id, $timeout);
            $this->pushLock($id);
            return true;
        }

        return $this->acquireOnce($id, $timeout);
    }

    public function release($id) {
        $locks_remaining = $this->popLock($id);
        if ($locks_remaining > 0) {
            return true;
        }

        $driver_name = DB::getDriverName();
        if ($driver_name == 'mysql') {
            $released = $this->release_Mysql($id);
        } else {
            $released = $this->release_Memory($id);
        }

        return $released;
    }

    public function acquireAndExecute($id, Callable $func, $timeout=60) {
        $acquired = $this->acquire($id, $timeout);
        if (!$acquired) {
            throw new Exception("Unable to acquire lock", 1);
        }

        try {
            $response = $func();
            $this->release($id);
            return $response;
        } catch (Exception $e) {
            // attempt to release - but ignore any exceptions if we fail
            try {
                $this->release($id);
            } catch (Exception $ignored_exception) { }

            // throw the original exception
            throw $e;
        }
    }

    public function refresh($id, $timeout=60) {
        $driver_name = DB::getDriverName();
        if ($driver_name == 'mysql') {
            $refreshed = $this->refresh_Mysql($id, $timeout);
        } else {
            $refreshed = $this->refresh_Memory($id, $timeout);
        }

        return $refreshed;
    }

    public function acquireOnce($id, $timeout=60) {
        $driver_name = DB::getDriverName();
        if ($driver_name == 'mysql') {
            $acquired = $this->acquire_Mysql($id, $timeout);
        } else {
            $acquired = $this->acquire_Memory($id, $timeout);
        }

        if ($acquired) { $this->pushLock($id); }
        return $acquired;
    }

    // ------------------------------------------------------------------------
    
    protected function acquire_Mysql($id, $timeout) {
        $result = DB::selectOne(DB::raw('SELECT GET_LOCK(?,?) AS locked'), [$id, $timeout]);
        return !!$result->locked;
    }
    protected function refresh_Mysql($id, $timeout) {
        return $this->acquire_Mysql($id, $timeout);
    }

    protected function release_Mysql($id) {
        $result = DB::selectOne(DB::raw('SELECT RELEASE_LOCK(?) AS released'), [$id]);
        return !!$result->released;
    }

    protected function acquire_Memory($id, $timeout, $refresh=false) {
        if (!$refresh) {
            $start = time();
            while (isset($this->memory_locks[$id]) AND $this->memory_locks[$id] > time()) {
                if (time() > $start + $timeout) {
                    return false;
                }

                sleep(1);
            }
        }

        $this->memory_locks[$id] = time() + $timeout;
        return true;
    }
    protected function refresh_Memory($id, $timeout) {
        return $this->acquire_Memory($id, $timeout, true);
    }

    protected function release_Memory($id) {
        unset($this->memory_locks[$id]);
        return true;
    }



    protected function isAlreadyLocked($id) {
        if (!isset($this->lock_stack[$id])) { $this->lock_stack[$id] = 0; }
        return $this->lock_stack[$id] > 0;
    }

    protected function pushLock($id) {
        if (!isset($this->lock_stack[$id])) { $this->lock_stack[$id] = 0; }
        return ++$this->lock_stack[$id];
    }

    protected function popLock($id) {
        if ($this->lock_stack[$id] <= 0) { throw new Exception("Attempted to pop empty lock stack", 1); }
        return --$this->lock_stack[$id];
    }

}
