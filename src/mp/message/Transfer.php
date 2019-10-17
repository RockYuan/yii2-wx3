<?php

namespace rockyuan\wx3\mp\message;

use rockyuan\wx3\core\Driver;

class Transfer extends Driver {

    public $type = 'transfer_customer_service';
    public $props = [];
}