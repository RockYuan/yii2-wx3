<?php

namespace rockyuan\wx3\mp\message;

use rockyuan\wx3\core\Driver;

class Voice extends Driver {

    public $type = 'voice';
    public $props = [];// MediaId
}