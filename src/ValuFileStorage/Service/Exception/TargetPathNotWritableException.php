<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\ServiceException;

class TargetPathNotWritableException extends ServiceException
{
    protected $code = 2007;        
}