<?php

namespace Api\Services\Interfaces;

/**
 * Interface for services who need to set is own build
 * 
 * @package Api\Services\Interfaces
 */
interface BuilderServiceInterface
{
    /**
     * Build the service
     * 
     * @return string The code to add to the build
     */
    public function build(string $classCode): ?string;
}
