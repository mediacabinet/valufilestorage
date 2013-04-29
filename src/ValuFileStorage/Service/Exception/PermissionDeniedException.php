<?php
namespace ValuFileStorage\Service\Exception;

class PermissionDeniedException 
    extends \ValuSo\Exception\PermissionDeniedException
{
    protected $code = 2002;
}