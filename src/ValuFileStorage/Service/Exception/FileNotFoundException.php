<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\ServiceException;

class FileNotFoundException extends ServiceException
{
    protected $code = 2001;        
}