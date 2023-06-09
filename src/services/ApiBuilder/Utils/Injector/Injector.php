<?php

namespace Api\Services;

use Api\Services\Interfaces\RegisterServiceInterface;

class Injector implements RegisterServiceInterface
{
    /**
     * all services can be injected
     * @var array<RegisterServiceInterface> $services
     */
    private array $services = [];

    /**
     * Injector of the services
     * @param Logger $logger
     */
    public function __construct(
        /**
         * Logger of the application
         * @var Logger $logger
         */
        private Logger $logger
    ) {
    }

    /**
     * Add a service to the injector
     * 
     * @param string $name The name of the service
     * @param string $class The class of the service
     * @param array $params The params of the service
     * 
     * @return void
     */
    public function addService(RegisterServiceInterface $class): void
    {
        $this->logger->info('Add service: ' . $class::class);
        $this->services[$class::class] = $class;
    }

    /**
     * Get a service from the injector
     * 
     * @param string $name The name of the service
     * 
     * @return object The service
     */
    public function getService(string $name): ?RegisterServiceInterface
    {
        return $this->services[$name];
    }

    /**
     * Inject the params of a method
     * 
     * @param \ReflectionMethod $method The method to inject
     * @param array $params The params to inject
     * 
     * @return array The params with the injected params
     */
    public function injectParams(\ReflectionMethod $method, array $params = []): array
    {
        $method_params = $method->getParameters();
        $find_params = [];

        foreach ($method_params as $param) {
            $param_name = $param->getName();
            $param_type = $param->getType();

            if ($param_type === null) {
                throw new \Exception('Param ' . $param_name . ' in method ' . $method->getName() . ' in class ' . $method->getDeclaringClass()->getName() . ' has no type');
            }
            $not_null = str_replace("?", "", $param_type->__toString());
            if (in_array($not_null, array_keys($this->services))) {
                $find_params[] = $this->services[$not_null];
                continue;
            }
            if (in_array($not_null, array_keys($params))) {
                $find_params[] = $params[$not_null];
                continue;
            }
            if (isset($params[$param_name])) {
                $find_params[] = $params[$param_name];
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $find_params[] = $param->getDefaultValue();
                continue;
            }
            if ($param->allowsNull()) {
                $find_params[] = null;
                continue;
            }
            throw new \Exception('Param ' . $param_name . ' in method ' . $method->getName() . ' in class ' . $method->getDeclaringClass()->getName() . ' can\'t be injected');
        }
        return $find_params;
    }

    /**
     * Execute a function with the injected services
     * 
     * @param \ReflectionMethod $method The method to execute
     * @param object|null $class The class of the method
     * @param array $params The params who can be injected
     */
    public function execute(\ReflectionMethod $method, ?object $class, array $params = []): mixed
    {
        $params = $this->injectParams($method, $params);

        return $method->invokeArgs($class, $params);
    }

    /**
     * Create a class with the injected services
     * 
     * @param \ReflectionClass $class The class to create
     * @param array $params The params who can be injected
     * 
     * @return object The created class
     */
    public function create_class(\ReflectionClass $class, array $params = []): object
    {
        $constructor = $class->getConstructor();
        if ($constructor === null) {
            $this->logger->debug('Class ' . $class->getName() . ' has no constructor');
            return $class->newInstance();
        }
        if (!$constructor->isPublic()) {
            throw new \Exception('Constructor of class ' . $class->getName() . ' is not public');
        }
        $params = $this->injectParams($constructor, $params);

        return $class->newInstanceArgs($params);
    }
}
