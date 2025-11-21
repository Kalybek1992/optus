<?php

use Source\Project\Middlewares\BlockMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ProductIDMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\StatusMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\EmailMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\MarketIDMiddleware;
//use Source\Project\Middlewares\StockMiddleware;

return [
    'GET' => [
        '/user/registration' => [
            'validation' => [
                'email' => ['required' => true],
                'password' => ['required' => true],
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [

            ]
        ],
        '/user/auth' => [
            'validation' => [
                'email' => ['required' => true],
                'password' => ['required' => true],
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
            ]
        ],
        '/user/getinfo' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/testpremium' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/gettariffs' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/createpayment' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sum' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'tariff' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) > 2
                ],
                'phone' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) >= 10 && mb_strlen($a) < 16
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/historypayments' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/activatecoupon' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'coupon' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) >= 5
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/addsocials' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'socials' => [
                    'required' => true,
                    //'custom_logic' => fn($a) => mb_strlen($a) >= 5
                ],
                'data' => [
                    'required' => false
                    //'custom_logic' => fn($a) => mb_strlen($a) >= 5
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/getpaymentinfo' => [
            'validation' => [
                'token' => [
                    'required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'payment_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/requestforresetpassword' => [
            'validation' => [
                'email' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new EmailMiddleware
            ]
        ],
        '/user/resetpassword' => [
            'validation' => [
                'hash' => [
                    'required' => true
                ]
            ],
            'middlewares' => [
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
//        '/user/addemployee' => [
//            'token' => ['required' => true],
//            'email' => ['required' => true],
//            'password' => ['required' => true]
//        ],
//        '/user/rememployee' => [
//            'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
//            'email' => ['required' => true],
//            'merchant_id' => ['required' => true]
//        ],
//        '/market/addrights' => [
//            'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
//            'right_type' => ['required' => false],
//            'merchant_id' => ['required' => true]
//        ],
//        '/market/deleterights' => [
//            'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
//            'right_type' => ['required' => true],
//            'merchant_id' => ['required' => true]
//        ],
        '/market/authkaspi' => [
            'validation' => [
                'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
                'login' => ['required' => true, 'regex' => '#[^@]*?@[^\.]*?\..*?\Z#'],
                'password' => ['required' => true],
                //'cookies' => ['required' => true]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/market/addmarket' => [
            'token' => ['required' => true, 'custom_logic' => fn($a) => mb_strlen($a) == 32],
            'kaspi_id' => ['required' => true],
            'name' => ['required' => true],
        ],
        '/market/getproducts' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sort_by' => ['required' => false],
                'sorting_param' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/market/setminprice' => [
            'validation' => [
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
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/setmaxprice' => [
            'validation' => [
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
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/setpricestep' => [
            'validation' => [
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
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/setprice' => [
            'validation' => [
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
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/getlatestprices' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'product_id' => [
                    'required' => false
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
//        '/market/blockmerchant' => [
//            'validation' => [
//                'token' => [
//                    'required' => true,
//                    'custom_logic' => fn($a) => mb_strlen($a) == 32
//                ],
//                'merchant_id_which_blocked' => [
//                    'required' => true
//                ]
//            ],
//            'middlewares' => [
//                new ApiKeyMiddleware
//            ]
//        ],
        '/market/blockmerchant' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'merchant_id' => [
                    'required' => false
                ],
                'product_id' => [
                    'required' => false
                ],
                'sku' => [
                    'required' => false
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware,
                new BlockMiddleware
            ]
        ],
        '/market/unblockmerchant' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'merchant_id' => [
                    'required' => false
                ],
                'product_id' => [
                    'required' => false
                ],
                'sku' => [
                    'required' => false
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware,
                new BlockMiddleware
            ]
        ],
        '/market/getproductscategory' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/market/activateproduct' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/deactivateproduct' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/startdempingproduct' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/stopdempingproduct' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/getproductstats' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware
            ]
        ],
        '/market/setkaspiapikey' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'api_key' => [
                    'required' => false,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/market/activeorders' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'status' => [
                    'required' => true
                ],
                'count' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ],
                'offset' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ]
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new StatusMiddleware
            ]
        ],
        '/market/archiveorders' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'status' => [
                    'required' => true
                ],
                'from_date' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ],
                'to_date' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ],
                'offset' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ],
                'count' => [
                    'required' => false,
                    //'custom_logic' => fn($a) => is_numeric($a)
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new StatusMiddleware
            ]
        ],
        '/market/kaspiremove' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'merchant_id' => [
                    'required' => true,
                    fn($a) => $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new MarketIDMiddleware
            ]
        ],
        '/market/setstock' => [
            'validation' => [
                'token' => [
                    'required' => true,
                    'custom_logic' => fn($a) => mb_strlen($a) == 32
                ],
                'sku' => [
                    'required' => true,
                    fn($a) => $a > 0
                ],
                'complete' => [
                    'required' => true,
                    fn($a) => $a > 0
                ],
                'empty' => [
                    'required' => false,
                    //fn($a) => $a > 0
                ],
                'percent' => [
                    'required' => false,
                ],
            ],
            'middlewares' => [
                new ApiKeyMiddleware,
                new ProductIDMiddleware,
//                new StockMiddleware
            ]
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
