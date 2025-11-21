<?php

namespace Source\Project\Requests;

use CurlHandle;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Logger;
use Source\Base\HttpRequests\WebHttpRequest;

class OneVision extends WebHttpRequest
{
    protected ?string $host = 'https://api.onevisionpay.com/payment/create';
    protected ?string $api_key = 'a70b023c-32e2-4458-a0dd-39a04e3f530a';
    protected ?string $api_secret = '28fff0cd9a81f6ca2bc9d20123132801a67221e2c6d762cd195557736c2c6637';
    protected ?string $MID = 'b835aa3f-9043-4c8a-a2d2-a2ed2dc385e5';
    protected ?string $SID = 'eaa43c43-99fe-440d-b589-dbe20f3c622b';

    protected ?string $login = 'support@bazarbay.kz';

    protected ?string $password = 'Ov_12345';
    /**
     * @var string|null
     */
    public ?string $link = null;
    /**
     * @var string|null
     */
    protected ?string $user_agent = null;
    /**
     * @var string|null
     */
    protected ?string $referer = null;


    public function __construct(string $login = null, string $password = null, array $cookies = [])
    {
        parent::__construct();

    }

    public function createPayment(string $user_id, string $email, int $amount, int $quantity, int $amount_one_pcs, string $unique, bool $is_curl = false)
    {
        $link_to_notify = 'https://api.bazarbay.kz/user/paymentnotification';

        $data = [
            "amount" => $amount,
            "currency" => "KZT",
            "order_id" => $unique,
            "description" => "Top up balance ID" . $user_id,
            "payment_type" => "pay",
            "payment_method" => "ecom",
            "user_id" => $user_id,
            "email" => $email,
            "success_url" => "https://bazarbay.kz/pay?success_pay=true",
            "failure_url" => "https://bazarbay.kz/pay?success_pay=false",
            "callback_url" => $link_to_notify,
            "payment_lifetime" => 3600,
            "lang" => "en",
            "items" => [
                [
                    "merchant_id" => $this->MID,
                    "service_id" => $this->SID,
                    "merchant_name" => "BazarBay",
                    "name" => $user_id .'/'. date('Y/m/d'),
                    "quantity" => $quantity,
                    "amount_one_pcs" => $amount_one_pcs,
                    "amount_sum" => $quantity * $amount_one_pcs
                ]
            ]
        ];
        //var_dump($data);die;

        $data = base64_encode(json_encode($data));

        $sign = hash_hmac(
            'sha512', $data,
            '28fff0cd9a81f6ca2bc9d20123132801a67221e2c6d762cd195557736c2c6637'
        );

        $token = base64_encode($this->api_key);

        $this->setHeader([
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ]);

        //var_dump($this->header);die;


        $array_data = [
            'data' => $data,
            'sign' => $sign
        ];

        $this->is_curl = $is_curl;

        $data = json_encode($array_data);
        $file_path = '/home/bazarbay/web/bazarbay.kz/app/resources/files/';

//        file_put_contents(
//            $file_path . 'onevision_notification.json',
//            $data
//        );die;

        $response = $this->post($this->host, $data);


        return $response;
    }
}

