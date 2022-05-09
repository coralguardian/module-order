<?php

namespace D4rk0snet\CoralOrder;

use D4rk0snet\CoralOrder\Entity\StandardOrderEntity;

class Plugin
{
    public static function init()
    {
        add_filter(\Hyperion\Doctrine\Plugin::ADD_ENTITIES_FILTER, function (array $entities) {
            $entities[] = StandardOrderEntity::class;
            return $entities;
        });
    }
}