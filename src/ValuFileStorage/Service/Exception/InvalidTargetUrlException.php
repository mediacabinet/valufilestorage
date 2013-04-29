<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\ServiceException;

class InvalidTargetUrlException extends ServiceException
{
    protected $code = 2006;        
}