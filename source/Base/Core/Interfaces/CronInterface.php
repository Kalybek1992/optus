<?php

namespace Source\Base\Core\Interfaces;

/**
 * Interface CronInterface
 *
 * Provides the necessary contract for Cron classes.
 *
 * @package Source\Base\Interfaces
 */
interface CronInterface
{
    /**
     * Cron constructor logic.
     */
    public function __construct();

    /**
     * Executes a function while a trigger condition is met.
     *
     * @param string $function
     * @param mixed $connector
     * @param RulesInterface $rules
     * @param mixed ...$values
     */
    public function whileTrigger(string $function, mixed $connector, RulesInterface $rules, ...$values): void;

    /**
     * Retrieves the trigger name based on the function name.
     *
     * @param string $function
     * @return string
     */
    public function getTriggerName(string $function): string;
}