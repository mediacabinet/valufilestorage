<?php
namespace ValuFileStorage\Service;

use Valu\Acl\Role\UniversalRole;
use Valu\Acl\Resource\UniversalResource;
use Valu\Acl\Service\AbstractAclService;
use Doctrine\ODM\MongoDB\DocumentManager;

class Acl extends AbstractAclService
{
    public static function version()
    {
        return '0.1';
    }
}