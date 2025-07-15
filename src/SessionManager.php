<?php

interface SessionManager
{
    /**
     * Get the current user ID.
     *
     * @return string
     */
    public function getUserId(): ?string;

    /**
     * Get the current customer ID.
     *
     * @return string
     */
    public function getCustomerId(): ?string;

    /**
     * Get the current platform (e.g. 'web', 'mobile', 'desktop').
     *
     * @return int
     */
    public function getPlatform(): string;

    /**
     * Get the current environment (e.g., 'production', 'staging').
     *
     * @return string
     */
    public function getEnvironment(): string;

    /**
     * Get the current session ID.
     *
     * @return string | null
     */
    public function getSessionId(): ?string;

    /**
     * Get the current request UUID.
     *
     * @return array
     */
    public function getRequestId(): ?string;
}