<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\ServiceException;

class TargetPathNotWritableException extends ServiceException
{
    protected $code = 2007;        
}