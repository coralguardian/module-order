<?php

namespace D4rk0snet\CoralOrder;

use D4rk0snet\CoralOrder\API\CreateOrder;

class Plugin
{
    public static function launchActions()
    {
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new CreateOrder());
    }
}