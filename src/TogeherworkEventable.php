<?php

trait TogetherworkEventable
{
    const TW_EVENT_NAME = null;
    
    /**
     * The name of the event.  Uses the class short name by default,
     * but can be overridden by setting the TW_EVENT_NAME constant.
     *
     * @return string
     */
    public function getName(): string
    {
        return is_null(static::TW_EVENT_NAME) ? (new \ReflectionClass($this))->getShortName() : static::TW_EVENT_NAME;
    }
    
    /**
     * Array of attributes for the event as defined by Togetherwork.
     *
     * @return array
     */
    abstract public function getAttributes(): array;
}