<?php

namespace Api\Services\Interfaces;

/**
 * Interface for services who need to set functions at the start and end of the application
 * 
 * @package Api\Services\Interfaces
 */
interface StarterServiceInterface
{
    /**
     * Function to run at the start of the application
     */
    public function atStart(): void;

    /**
     * Function to run at the end of the application
     */
    public function atEnd(): void;
}
