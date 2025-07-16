<?php

namespace TWEvents;

use JsonSerializable;

interface TogetherworkEvent extends JsonSerializable
{
    /**
     * The name of the event.  Uses the class short name by default,
     * but can be overridden by setting the TW_EVENT_NAME constant.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Array of attributes for the event as defined by Togetherwork.
     *
     * @return array
     */
    public function getAttributes(): array;
}