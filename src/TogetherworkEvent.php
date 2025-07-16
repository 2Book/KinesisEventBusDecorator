<?php

namespace TWEvents;

use JsonSerializable;

interface TogetherworkEvent extends JsonSerializable
{
    public function getName(): string;

    public function getAttributes(): array;

    public function jsonSerialize(): array;
}