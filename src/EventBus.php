<?php

interface EventBus
{
    public function fire(Event $event): void;
}