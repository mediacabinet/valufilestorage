<?php
namespace ValuFileStorage\Service\Exception;

class PermissionDeniedException 
    extends \Valu\Service\Exception\PermissionDeniedException
{
    protected $code = 2002;
}