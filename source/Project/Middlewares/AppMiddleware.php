<?php

namespace Source\Project\Middlewares;

use JetBrains\PhpStorm\NoReturn;
use Source\Base\Core\Exceptions\MiddlewareException;
use Source\Base\Core\Middleware;
use Source\Base\Core\Request;
use Source\Project\DataContainers\RequestDC;
use Source\Project\DataContainers\VariablesDC;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\MarketIDMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ProductIDMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ProductsIDMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\StatusMiddleware;

class AppMiddleware extends Middleware
{
    protected const array MAPPING_MIDDLEWARES = [
        'token' => ApiKeyMiddleware::class,
        'product_id' => ProductIDMiddleware::class,
        'merchant_id' => MarketIDMiddleware::class,
        'status' => StatusMiddleware::class,
        'sku' => ProductIDMiddleware::class,
        'file' => ProductsIDMiddleware::class
    ];
    /**
     * @var Request
     */
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request(
            $_SERVER['REQUEST_METHOD'] ?? null,
            $_SERVER['REQUEST_URI'] ?? null,
            getallheaders(),
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $_SESSION ?? []
        );
    }

    /**
     * @param callable $next
     * @return bool
     * @throws MiddlewareException
     */
    public function handle(callable $next): bool
    {

        $this->setRequestUrlBased();
        $this->setParams();

        $middlewares = [];
        $params = array_keys(RequestDC::$request_url_rules[RequestDC::$request_method]["/" . RequestDC::$request_function] ?? []);

        if ($params) {
            foreach ($params as $param => $value) {
                if (self::MAPPING_MIDDLEWARES[$value] ?? false) {
                    $middlewares[] = new (self::MAPPING_MIDDLEWARES[$value]);
                }
            }
        }

        Middleware::addMiddlewares([
            new UrlValidateMiddleware,
            ...$middlewares
        ]);

        return $next();
    }

    public function urlPathLogic(): ?string
    {
        preg_match('#(?<=/)[^?]+?(?=\?|\s|$)+#', $this->request->getUri(), $url_controller_function);

        return $url_controller_function[0] ?? null;
    }

    #[NoReturn] public function setParams(): void
    {
        RequestDC::$request_function = strtolower($this->urlPathLogic() ?? '');
        RequestDC::$request_method = $this->request->getMethod();

        if (RequestDC::$request_method == 'GET') {
            RequestDC::$request_params = $this->request->getQueryParams();
        } else {
            RequestDC::$request_params = $this->request->getParsedBodyParams();
        }

        if (RequestDC::$request_params['sku'] ?? false) {

        } else {

        }

        foreach (RequestDC::$request_params as $key => $value) {
            if (property_exists(RequestDC::class, $key)) {
                RequestDC::$$key = is_string($value) ? trim($value) : $value;
            }
        }
        if ($this->request->getMethod() == 'GET') {
            $params = $this->request->getQueryParams();
        } else {
            $params = $this->request->getParsedBodyParams();
            $file = $this->request->getFile('file');

            VariablesDC::set('file', $file);
        }

        foreach ($params as $key => $value) {
            VariablesDC::set($key, $value);
        }
    }

//    var_dump(VariablesDC::get)
    public function setRequestUrlBased(): void
    {
        RequestDC::$request_url_rules = [
            'GET' => [
                '/user/registration' => [
                    'email' => ['required' => true],
                    'password' => ['required' => true],
                    'captcha_response' => ['required' => false]
                ],
                '/user/auth' => [
                    'email' => ['required' => true],
                    'password' => ['required' => true],
                    'captcha_response' => ['required' => true]
                ],
                '/user/getinfo' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32]
                ],
                '/user/createpayment' => [
                    'token' => [
                        'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sum' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                    ],
                    'tariff' => [
                        'required' => true,
                        'custom_logic' => fn($a) =>  mb_strlen($a) > 2
                    ],
                    'phone' => [
                        'required' => true,
                        'custom_logic' => fn($a) =>  mb_strlen($a) > 10 && mb_strlen($a) < 16
                    ]
                ],
                '/user/getpaymentinfo' => [
                    'token' => [
                        'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'payment_id' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                    ]
                ],
                /**
                 * @TODO FUNCTIONS
                 * fn($a) => mb_strlen($a) == 32
                 * function($a) {return mb_strlen($a) == 32;}
                 *
                 * fn($a) => mb_strlen($a) == 32
                 * is_numeric($a) && $a > 0
                 */
                '/user/addemployee' => [
                    'token' => ['required' => true],
                    'email' => ['required' => true],
                    'password' => ['required' => true]
                ],
                '/user/rememployee' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                    'email' => ['required' => true],
                    'merchant_id' => ['required' => true]
                ],
                '/market/addrights' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                    'right_type' => ['required' => false],
                    'merchant_id' => ['required' => true]
                ],
                '/market/deleterights' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                    'right_type' => ['required' => true],
                    'merchant_id' => ['required' => true]
                ],
                '/market/authkaspi' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                    'login' => ['required' => true, 'regex' => '#[^@]*?@[^\.]*?\..*?\Z#'],
                    'password' => ['required' => true],
                    //'cookies' => ['required' => true]
                ],
                '/market/addmarket' => [
                    'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                    'kaspi_id' => ['required' => true],
                    'name' => ['required' => true],
                ],
                '/market/getproducts' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sort_by' => ['required' => false],
                    'sorting_param' => ['required' => false]
                ],
                '/market/setminprice' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => true,
                        fn($a) => $a > 0,
//                        'required_another' => 'product_id'
                    ],
                    'min_price' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 10
                    ],
                    'bot' => [
                        'required' => false,
                    ]
                ],
                '/market/setmaxprice' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => true,
                        fn($a) => $a > 0
                    ],
                    'max_price' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 10
                    ],
                    'bot' => [
                        'required' => false,
                    ]
                ],
                '/market/setpricestep' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                    'price_step' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                    ]
                ],
                '/market/setprice' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => true,
                        fn($a) => $a > 0
                    ],
                    'price' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a) && $a > 10
                    ]
                ],
                '/market/getlatestprices' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'merchant_id' => [
                        'required' => true
                    ],
                    'product_id' => [
                        'required' => false
                    ]
                ],
                '/market/blockmerchant' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'merchant_id_which_blocked' => [
                        'required' => true
                    ]
                ],
                '/market/getproductscategory' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                ],
                '/market/activateproduct' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/deactivateproduct' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/startdempingproduct' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/stopdempingproduct' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/getproductstats' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'sku' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/setkaspiapikey' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'api_key' => [
                        'required' => false,
                        fn($a) => $a > 0
                    ],
                ],
                '/market/activeorders' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'status' => [
                        'required' => true
                    ],
                    'count' => [
                        'required' => false,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ],
                    'offset' => [
                        'required' => false,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ]
                ],
                '/market/archiveorders' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'from_date' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ],
                    'to_date' => [
                        'required' => true,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ],
                    'status' => [
                        'required' => true
                    ],
                    'offset' => [
                        'required' => false,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ],
                    'count' => [
                        'required' => false,
                        'custom_logic' => fn($a) => is_numeric($a)
                    ],
                ],
//                '/market/setminprices' => [
//                    'token' => [
//                        'required' => false,//true,
//                        'custom_logic' => fn($a) => mb_strlen($a) == 32
//                    ],
//                ]
            ],
            'POST' => [
                '/market/setminprices' => [
                    'token' => [
                        'required' => true,
                        'custom_logic' => fn($a) => mb_strlen($a) == 32
                    ],
                    'file' => [
                        'required' => true
                    ]
                ]
            ]
        ];
    }
}