<?php

abstract class TogetherworkEvent implements JsonSerializable
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
    
    abstract public function getAttributes(): array;

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'attributes' => $this->getAttributes()
        ];
    }
    /**
     * Convert the event to a JSON serializable format.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}