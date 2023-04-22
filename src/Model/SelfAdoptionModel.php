<?php

namespace D4rk0snet\CoralOrder\Model;

class SelfAdoptionModel implements \JsonSerializable
{
    private ?array $names = null;

    public function getNames(): ?array
    {
        return $this->names;
    }

    public function setNames(?array $names): void
    {
        $this->names = $names;
    }

    public function jsonSerialize()
    {
        return [
            'names' => $this->getNames()
        ];
    }
}