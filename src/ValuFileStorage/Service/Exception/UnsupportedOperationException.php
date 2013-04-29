<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\SkippableException;

class UnsupportedOperationException extends SkippableException
{
    protected $code = 2004;
}