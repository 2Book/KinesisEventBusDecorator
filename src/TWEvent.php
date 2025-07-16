<?php

namespace TWEvents;

abstract class TWEvent extends Event implements TogetherworkEvent
{
    use TogetherworkEventable;

    public function getAttributes(): array
    {
        return $this->data;
    }
}