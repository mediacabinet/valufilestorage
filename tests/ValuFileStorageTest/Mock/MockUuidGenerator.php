<?php
namespace ValuFileStorageTest\Mock;

class MockUuidGenerator
{
    public $uuids = [];

    public function __construct(array $uuids)
    {
        $this->uuids = $uuids;
    }

    public function generateV5($seed)
    {
        return $seed;
    }

    public function generateV4()
    {
        return array_shift($this->uuids);
    }
}
