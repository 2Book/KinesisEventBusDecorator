<?php

namespace TWEvents;

abstract class Event
{
    /**
     * The data associated with the event.
     *
     * @var array
     */
    protected array $data;

    /**
     * Metadata associated with the event.
     *
     * @var array
     */
    protected array $metadata;

    /**
     * Constructor to initialize event data and metadata.
     *
     * @param array $data
     * @param array $metadata
     */
    public function __construct(array $data = [], array $metadata = [])
    {
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * Get the data associated with the event.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the metadata associated with the event.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
