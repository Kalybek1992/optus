<?php

namespace Source\Base\Core\Interfaces;

/**
 * Builder Interface.
 *
 * Defines the contract for builder classes.
 *
 */
interface BuilderInterface
{
    /**
     * Sets the full and short build class names based on the provided class name.
     *
     * @param string $class The fully qualified class name.
     * @return void
     */
    function setBuildClassName(string $class): void;
}
