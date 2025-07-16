<?php

namespace TWEvents;

interface EventBus
{
    public function fire(Event $event): void;
}