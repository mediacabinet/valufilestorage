<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\ServiceException;

class InvalidTargetUrlException extends ServiceException
{
    protected $code = 2006;        
}