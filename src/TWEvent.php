<?php

namespace TWEvents;

abstract class TWEvent extends Event implements TogetherworkEvent
{
    use TogetherworkEventable;

    /**
     * Array of attributes for the event as defined by Togetherwork.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->data;
    }
}