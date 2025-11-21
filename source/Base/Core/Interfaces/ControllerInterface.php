<?php

namespace Source\Base\Core\Interfaces;

use Source\Base\Core\DataContainer;

/**
 * InterfaceControllerInterface
 * Defines basic methods and properties for controllers.
 *
 * @package Source\Base\Interfaces
 */
interface ControllerInterface
{
    /**
     * Method for checking the existence of a controller file.
     *
     * @param string|null $name Controller name.
     * @return bool
     */
    public static function isController(string $name = null): bool;

    /**
     * Magic method to handle calling non-existent methods in the controller.
     *
     * @param string|null $name The name of the method to call.
     * @param array|null $arguments Arguments for the method being called.
     * @return mixed
     */
    public function __call(string $name = null, array $arguments = null);

    /**
     * Abstract method for handling not found methods.
     * Must be implemented in every child controller.
     *
     * @return array
     */
    public function default(): array;
}
