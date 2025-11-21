<?php

namespace Source\Base\Core;

use Source\Base\Core\Interfaces\BuilderInterface;

/**
 * Abstract Builder class.
 *
 * Provides base functionality for building class names.
 *
 */
abstract class Builder implements BuilderInterface
{
    /**
     * Full Build Class Name.
     *
     * @var string|null
     */
    protected ?string $fb_class_name = null;

    /**
     * Short Build Class Name.
     *
     * @var string|null
     */
    protected ?string $sb_class_name;

    /**
     * Sets the full and short build class names based on the provided class name.
     *
     * @param string $class The fully qualified class name.
     * @return void
     */
    public function setBuildClassName(string $class): void
    {
        $this->fb_class_name = $class;

        $exploded_class = explode('\\', $class);
        $this->sb_class_name = strtolower($exploded_class[count($exploded_class)-1]) . 's';
    }
}
