<?php

/**
 * Class RequestRules
 *
 * This class represents a set of rules for validating requests based on URL and request type.
 * It extends the base class Rules.
 */

namespace Source\Base\Rules;


use /**
 * This file contains the Core Rules class.
 *
 * The Core Rules class is responsible for handling the business logic related to rules.
 * It provides methods for validating and applying rules.
 *
 * @category  Source\Base\Core
 * @package   Source\Base\Core\Rules
 */
    Source\Base\Core\Rules;

/**
 * @var array $url_based_rules The URL based rules.
 */
class RequestRules extends Rules
{
    /**
     * @var mixed|null $last_param The value of the last param.
     *
     * The $last_param variable stores the value of the last param passed to the function or method.
     * It can hold any data type or be set to null if no parameter was passed.
     */
    public static mixed $last_param = null;
    /**
     * The $url_based_rules variable holds an array of rules used for URL-based rules routing.
     *
     *  The array is structured in the following format:
     *  [
     *     'test-get-action' => [
     *         'GET' => [
     *             'id' => ['required' => true, 'custom_logic' => fn($a) => $a > 0 && is_numeric($a)],
     *             'action' => ['required' => true]
     *          ]
     *      ],
     *     'products' => [
     *          'GET' => [
     *              'category' => ['regex' => '/^[a-zA-Z]+$/', 'required' => false]
     *           ]
     *      ]
     *  ]
     *
     *
     *
     * It is important to note that the order of the rules matter,
     * as the first matching rule will be executed.
     *
     * @var array
     */
    private array $url_based_rules;

    /**
     * Constructor for the class.
     *
     * @param array $url_based_rules The URL based rules to be assigned to the class property.
     *
     * @return void
     */
    public function __construct(array $url_based_rules)
    {
        $this->url_based_rules = $url_based_rules;
    }

    /**
     * @return string|null
     */
    public function getLastParam(): ?string
    {
        return self::$last_param;
    }

    /**
     * Validate a request based on rules defined for a given URL and request type.
     *
     * @param string $url The URL for which the rules are defined.
     * @param string $request_type The type of the request (e.g., GET, POST, PUT, etc.).
     * @param array $parameters An array of parameters passed in the request.
     *
     * @return bool Returns true if all validations passed, false otherwise.
     *   If there are missing required parameters, it returns false with the description "Missing required parameter".
     *   If a parameter fails regex validation, it returns false with the description "Regex validation failed".
     *   If a parameter fails custom logic validation, it returns false with the description "Custom logic validation failed".
     *   If no rules are defined for the given URL and request type, it returns false with the description "No rules defined for this URL and request type".
     */
    public function validateRequest(string $request_type, string $url,  array $parameters): bool
    {

        if ($this->url_based_rules[$request_type][$url] ?? false) {
            $rules = $this->url_based_rules[$request_type][$url];
            $xor_params = [];

            foreach ($rules as $param_name => $rule) {
                self::$last_param = $param_name;

                $is_required = $rule['required'] ?? false;
                $has_param = isset($parameters[$param_name]);

                if ($rule['xor_param'] ?? false) {
                    $key_array = [$rule['xor_param'], $param_name];
                    asort($key_array);

                    $key_md5 = md5(json_encode($key_array));
                    //$xor_params[$key_md5] = [$key_md5];

                    if ($has_param) {
                        unset($rules[$rule['xor_param']]);
                    } else {
                        if (count($xor_params[$key_md5] ?? []) == 1) {
                            $is_required = true;
                        } else {
                            $is_required = false;
                        }
                    }

                    $xor_params[$key_md5][] = $param_name;
                }

                if ($is_required && !$has_param) {
                    /**
                     * @DESC Missing required parameter
                     */
                    return false;
                }

                if ($has_param && isset($rule['regex']) && !preg_match($rule['regex'], $parameters[$param_name])) {
                    /**
                     * @DESC Regex validation failed
                     */
                    return false;
                }

                if ($has_param && isset($rule['custom_logic']) && !$rule['custom_logic']($parameters[$param_name])) {
                    /**
                     * @DESC Custom logic validation failed
                     */
                    return false;
                }
            }

            /**
             * @DESC All validations passed
             */
            return true;
        }

        /**
         * @DESC No rules defined for this URL and request type
         */
        return false;
    }
}