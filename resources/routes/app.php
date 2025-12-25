<?php


use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyAddUserCategoriesMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyAdminMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeySupplierMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\AuthMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyGetMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ClientIdMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\GetFileMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\NewUserRoleCheckMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\NewUserTokenMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\RepeatEmailMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\RepeatManagerNameMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\UserIdMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyShopreceiptsDateMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\ApiKeyCourierMiddleware;
use Source\Project\Middlewares\VariablesMiddlewares\MutualSettlementMiddleware;

return [
    'GET' => [
        '/' => [
            'page' => [
                'required' => false,
                'custom_logic' => fn($a) => is_numeric($a) || $a == null
            ],
            'date_from' => [
                'required' => false,
                'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
            ],
            'date_to' => [
                'required' => false,
                'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/user/adduser' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getclients' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getshop' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getclientservices' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getsuppliers' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getcouriers' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getadministrators' => [
            'validation' => [
                'captcha_response' => ['required' => false],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/getuserslinking' => [
            'validation' => [
                'page' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/bindaccount' => [
            'validation' => [
                'email' => [
                    'required' => true,
                    'custom_logic' => fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)
                ],
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/entities/unknownaccounts' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/unknownbankorder' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/linkexpenses' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/linkexpensescourier' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/legalentities' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0,
                ],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/categories/pagecategories' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAddUserCategoriesMiddleware
            ]
        ],
        '/categories/projectpage' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAddUserCategoriesMiddleware
            ]
        ],
        '/transaction/getexpenses' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'category' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/gettransferyourself' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/getexpensesstockbalances' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/linkexpensesorder' => [
            'validation' => [
                'order_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/linkremovalorder' => [
            'validation' => [
                'order_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/createtransaction' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/returntransaction' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/getcourierfinances' => [
            'validation' => [
                'courier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || null == $a
                ],
                'category' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/debts/debtsclientservices' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/testdelldb' => [
            'validation' => [
                'page' => [
                    'required' => false,
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/debts/getmutualsettlements' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'supplier_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/debts/getmutualsdata' => [
            'validation' => [
                'date' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'supplier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/clientreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/shopreceiptsdate' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'shop_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/clientservicesreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/supplierssendingsdate' => [
            'validation' => [
                'supplier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/getourentities' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/courier/home' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/courier/pending' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/incomeotherform' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/expenseform' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/debtpayoutform' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/issueaanothercourier' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/transaction/distributioncommoditymoney' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/transaction/supplierreports' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'supplier_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/supplierscan' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'supplier_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/supplier/adduserpage' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/clientservicesmanager' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/deliveredgoods' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'legal_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null,
                ],
                'manager_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null,
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/returngoods' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/categories/suppliercategories' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/stockbalancecommodity' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/getexpenses' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'category' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/supplierclientreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/debtexpenses' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/gettransactiondate' => [
            'validation' => [
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/unloading/downloadfile' => [
            'validation' => [
                'file' => [
                    'required' => true,
                ]
            ],
            'middlewares' => [
                new ApiKeyShopreceiptsDateMiddleware
            ]
        ],
        '/supplier/managerdailyreports' => [
            'validation' => [
                'manager_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0,
                ],
                'date' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/getmovedcash' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'manager_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0,
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/scheduler/addscheduler' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/scheduler/allschedule' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'legal_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null,
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/supplier/getusersbyrole' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'role' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'client' || $a == 'manager'
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/entities/archiveofextracts' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
                'date' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/downloadextract' => [
            'validation' => [
                'file' => [
                    'required' => true,
                ]
            ],
            'middlewares' => [
                new ApiKeyShopreceiptsDateMiddleware
            ]
        ],
    ],
    'POST' => [
        '/user/auth' => [
            'validation' => [
                'email' => [
                    'required' => true,
                    'custom_logic' => fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)
                ],
                'password' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new authMiddleware
            ]
        ],
        '/user/adduserrole' => [
            'validation' => [
                'email' => [
                    'required' => false,
                ],
                'password' => [
                    'required' => false
                ],
                'name' => [
                    'required' => true
                ],
                'role' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'admin' || $a == 'client' || $a == 'supplier' || $a == 'courier' || $a == 'client_services' || $a == 'shop'
                ],
                'percent' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a >= 0 && $a < 100 || $a == null
                ],
                'supplier_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a >= 0 || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new RepeatEmailMiddleware,
                new NewUserTokenMiddleware,
                new NewUserRoleCheckMiddleware,
            ]
        ],
        '/supplier/adduser' => [
            'validation' => [
                'name' => [
                    'required' => true
                ],
                'role' => [
                    'required' => true,
                    'custom_logic' => fn($a) =>  $a == 'client' || $a == 'manager',
                ],
                'percent' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a >= 0 && $a < 100 || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
                new RepeatManagerNameMiddleware,
            ]
        ],
        '/file/upload' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new GetFileMiddleware
            ]
        ],
        '/user/bindaccount' => [
            'validation' => [
                'email' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a === 'on_account' || $a === 'cancellation' || filter_var($a, FILTER_VALIDATE_EMAIL) !== false,
                ],
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/changeemail' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'email' => [
                    'required' => true,
                    'custom_logic' => fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new UserIdMiddleware,
                new RepeatEmailMiddleware,
            ]
        ],
        '/user/changepassword' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'password' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && strlen($a) >= 6
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new UserIdMiddleware,
            ]
        ],
        '/user/userdelete' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new UserIdMiddleware,
            ]
        ],
        '/user/changepercentage' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'percentage' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 && $a <= 100
                ],
            ],
            'middlewares' => [
                new UserIdMiddleware,
            ]
        ],
        '/entities/unlinkaccount' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/addexpenses' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
                'comment' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/categories/addcategory' => [
            'validation' => [
                'parent_category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
                'new_category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
                'project' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 0 || $a == 1,
                ],
            ],
            'middlewares' => [
                new ApiKeyAddUserCategoriesMiddleware
            ]
        ],
        '/categories/delcategory' => [
            'validation' => [
                'category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/user/bindaccountsupplier' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'percent' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 && $a <= 100
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/user/bindaccountclientservices' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'percent' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 && $a <= 100
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/user/bindaccountclient' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/changepercentage' => [
            'validation' => [
                'transaction_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'percent' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 && $a <= 100
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/supplier/changepercentage' => [
            'validation' => [
                'transaction_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'percent' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 && $a <= 100
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/entities/addexpensesorder' => [
            'validation' => [
                'order_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
                'comment' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/entities/withdrawingorder' => [
            'validation' => [
                'order_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'card_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'courier_id' => [
                    'required' => false,
                ],
                'purpose' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'courier' || $a == 'stock_balances'
                ],
                'comment' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/sendingcourier' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'courier_id' => [
                    'required' => false,
                ],
                'card_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'comment' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/debts/mutualsettlement' => [
            'validation' => [
                'amount_repaid' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'supplier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/cards/addcard' => [
            'validation' => [
                'card_number' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && preg_match('/^\d{16}$/', $a)
                ],
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/cards/getourcompany' => [
            'validation' => [
                'page' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/transaction/setstockbalances' => [
            'validation' => [
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/setyourself' => [
            'validation' => [
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/setshop' => [
            'validation' => [
                'entity_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/storetransaction' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/storereturntransaction' => [
            'validation' => [
                'captcha_response' => ['required' => false]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/courier/confirm' => [
            'validation' => [
                'company_finances_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/storeincomeother' => [
            'validation' => [
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false
                ],
                'category' => [
                    'required' => true
                ],
                'comments' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/storeexpense' => [
            'validation' => [
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'category_path' => [
                    'required' => true
                ],
                'comments' => [
                    'required' => false
                ],
            ],
            'middlewares' => [
                new ApiKeyCourierMiddleware
            ]
        ],
        '/courier/storedebtpayout' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'comments' => ['required' => false],
            ],
            'middlewares' => [
                new ApiKeyMiddleware
            ]
        ],
        '/entities/definitioncommoditymoney' => [
            'validation' => [
                'delivery_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'expense' || $a == 'client' || $a == 'courier'
                ],
                'selected_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
                'category_path' => [
                    'required' => false
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'comments' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeyAddUserCategoriesMiddleware
            ]
        ],
        '/courier/expenseadmin' => [
            'validation' => [
                'finances_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'status' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'confirm' || $a == 'cancel'
                ],
                'action_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'consumption' || $a == 'debt' || $a == 'courier_income_other'
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/supplier/linkuserlegal' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'role_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'role' => [
                    'required' => true,
                    'custom_logic' => fn($a) =>  $a == 'client' || $a == 'manager',
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/supplier/distributeamount' => [
            'validation' => [
                'balance_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false
                ],
                'manager_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'comment' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/supplier/createreturnmanager' => [
            'validation' => [
                'balance_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'manager_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'comment' => [
                    'required' => true
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false
                ],
                'return_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'cash' || $a == 'wheel'
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/categories/addsuppliercategory' => [
            'validation' => [
                'parent_category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
                'new_category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/categories/delsuppliercategory' => [
            'validation' => [
                'category' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a)
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/supplier/definitioncommoditymoney' => [
            'validation' => [
                'delivery_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'expense' || $a == 'debt' || $a == 'client' || $a == 'courier'
                ],
                'selected_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
                'category_path' => [
                    'required' => false
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'comments' => [
                    'required' => true
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/supplier/expenseadmin' => [
            'validation' => [
                'finances_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'status' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'confirm' || $a == 'cancel'
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/supplier/saveproductbalance' => [
            'validation' => [
                'finances_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/entities/returnaccount' => [
            'validation' => [
                'order_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'stock_balances' => [
                    'required' => false,
                    'custom_logic' => fn($a) => $a === 'сompany' || $a === 'courier' || $a == null,
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/supplier/movedcash' => [
            'validation' => [
                'manager_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'comment' => [
                    'required' => true
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/scheduler/addnewscheduler' => [
            'validation' => [
                'comment' => [
                    'required' => true,
                ],
                'plan_name' => [
                    'required' => true,
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'weekday' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && ($a >= 1 && $a <= 7),
                ],
                'legal_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) =>
                        is_string($a)
                        && preg_match('/^\d{2}\.\d{2}$/', $a)
                        && DateTime::createFromFormat('d.m', $a) !== false
                ],
                'repeat_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'none' || $a == 'weekly' || $a == 'monthly'
                ],

            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/scheduler/deletetask' => [
            'validation' => [
                'task_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/scheduler/paytask' => [
            'validation' => [
                'task_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/scheduler/edittask' => [
            'validation' => [
                'comment' => [
                    'required' => true,
                ],
                'plan_name' => [
                    'required' => true,
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'weekday' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && ($a >= 1 && $a <= 7),
                ],
                'legal_id' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0 || $a == null
                ],
                'date' => [
                    'required' => true,
                    'custom_logic' => fn($a) =>
                        is_string($a)
                        && preg_match('/^\d{2}\.\d{2}$/', $a)
                        && DateTime::createFromFormat('d.m', $a) !== false
                ],
                'repeat_type' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'none' || $a == 'weekly' || $a == 'monthly'
                ],
                'task_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/supplier/unlinkaccount' => [
            'validation' => [
                'legal_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware,
            ]
        ],
        '/unloading/shopreceiptsdate' => [
            'validation' => [
                'shop_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyShopreceiptsDateMiddleware
            ]
        ],
        '/unloading/clientreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/clientservicesreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/supplierssendingsdate' => [
            'validation' => [
                'supplier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/getcourierfinances' => [
            'validation' => [
                'courier_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'category' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/getexpenses' => [
            'validation' => [
                'category' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) || $a == null
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/gettransactiondate' => [
            'validation' => [
                'date_from' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/unloading/gettransferyourself' => [
            'validation' => [
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/getexpensesstockbalances' => [
            'validation' => [
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/archiveofextracts' => [
            'validation' => [
                'date' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
        '/unloading/supplierclientreceiptsdate' => [
            'validation' => [
                'client_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'date_from' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ],
                'date_to' => [
                    'required' => false,
                    'custom_logic' => fn($a) => is_string($a) && DateTime::createFromFormat('d.m.Y', $a) !== false || $a == null
                ]
            ],
            'middlewares' => [
                new ApiKeySupplierMiddleware
            ]
        ],
        '/user/changerestrictedaccess' => [
            'validation' => [
                'user_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'restricted_access' => [
                    'required' => true,
                    'custom_logic' => fn($a) => $a == 'limitation' || $a == 'unlimited'
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
            ]
        ],
        '/transaction/mutualsettlement' => [
            'validation' => [
                'role_id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'amount' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ],
                'role' => [
                    'required' => true,
                    'custom_logic' => fn($a) =>  $a == 'supplier' || $a == 'client' || $a == 'client_services',
                ],
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware,
                new MutualSettlementMiddleware,
            ]
        ],
        '/rollback/rollbackerrorupload' => [
            'validation' => [
                'id' => [
                    'required' => true,
                    'custom_logic' => fn($a) => is_numeric($a) && $a > 0
                ]
            ],
            'middlewares' => [
                new ApiKeyAdminMiddleware
            ]
        ],
    ]
];