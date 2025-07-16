<?php

namespace TWEvents;

interface EventBus
{
    /**
     * Fire an event.
     *
     * @param Event $event
     * @return void
     */
    public function fire(Event $event): void;
}