<?php

namespace Source\Project\Requests;

use CurlHandle;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Logger;
use Source\Base\HttpRequests\WebHttpRequest;

class KaspiKz extends WebHttpRequest
{
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

    protected ?string $login;

    protected ?string $password;

    protected array $cookies = [];

    /**
     * @param string|null $login
     * @param string|null $password
     * @param array $cookies
     */
    public function __construct(string $login = null, string $password = null, array $cookies = [])
    {
        parent::__construct();

        if ($login) {
            $this->setLogin($login);
        }

        if ($password) {
            $this->setPassword($password);
        }

        $this->cookies = $cookies;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setCookie(string $key, string $value): void
    {
        $this->cookies[$key] = $value;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getCookie(string $key): ?string
    {
        return $this->cookies[$key] ?? null;
    }

    /**
     * @return array|null
     */
    public function getCookies(): ?array
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return void
     */
    public function setCookies(array $cookies): void
    {
        $this->cookies = $cookies;
    }

    /**
     * @return string
     */
    public function cookiesToString(): string
    {
        $string = '';

        foreach ($this->cookies as $name => $value) {
            $string .= $name . '=' . $value . '; ';
        }

        return $string;
    }

    /**
     * @param string $login
     * @return void
     */
    public function setLogin(string $login): void
    {
        $this->login = strtolower($login);
    }
    /**
     * @return string|null
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * @param string $password
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param int $product_id
     * @param int|null $city_id
     * @param int $limit
     * @param bool $is_curl
     * @return mixed
     */
    public function getOffers(int $product_id, ?int $city_id = 750000000, int $limit = 2, bool $is_curl = false): mixed
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://kaspi.kz/yml/offer-view/offers/' . $product_id;
        $array_data = [
            'cityId' => $city_id,
            'id' => $product_id,
            'merchantUID' => '',
            'limit' => $limit,
            'page' => 0,
            'sort' => 1,
            'baseProductCodes' => [],
            'groups' => null,
            'installationId' => -1
        ];
        $this->referer = 'https://kaspi.kz/shop/p/';
        $data = json_encode($array_data);

        $this->is_curl = $is_curl;

        return $this->post($link, $data, $this->referer); //json_decode($get, $associative);

    }

    public function getApiOrdersStatus(
        $api_key,
        $page = 0, $size = 20, $state = 'NEW',
        $creation_date = 0, $status = 'APPROVED_BY_BANK', $delivery_type = 'PICKUP',
        $signature_required = false, $include = 'user')
    {
//        https://kaspi.kz/shop/api/v2/orders?page[number]=0&page[size]=20&filter[orders][state]=NEW
//        &filter[orders][creationDate][$ge]=1478736000000&filter[orders][creationDate][$le]=1479945600000
//            &filter[orders][status]=APPROVED_BY_BANK&filter[orders][deliveryType]=PICKUP
//                    &filter[orders][signatureRequired]=false&include[orders]=user
        $this->setHeader([
            'Content-Type:application/vnd.api+json',
            "X-Auth-Token: $api_key"
        ]);

        $array_data = [
            'page[number]' => $page,
            'page[size]' => $size,
            'filter[orders][state]' => $state,
            'filter[orders][creationDate][$ge]' => $creation_date,
            'filter[orders][creationDate][$le]' => $creation_date,
            'filter[orders][status]' => $status,
            'filter[orders][deliveryType]' => $delivery_type,
            'filter[orders][signatureRequired]' => $signature_required,
            'include[orders]' => $include
        ];
        $link = 'https://kaspi.kz/shop/api/v2/orders';

        $data = json_encode($array_data);

        return $this->post($link, $data, $this->referer);
    }
    /**
     * @param bool $is_curl
     * @return \CurlHandle|string|null
     */
    public function getMainPage(bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://kaspi.kz/mc';

        $this->referer = 'https://kaspi.kz/mc';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    /**
     * @param bool $header
     * @param bool $is_curl
     * @return \CurlHandle|string|null
     */
    public function auth(bool $header = true, bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://kaspi.kz/mc/api/login';
        $array_data = [
            'username' => $this->login,
            'password' => $this->password
        ];
        $this->referer = 'https://kaspi.kz/mc/';
        $data = http_build_query($array_data);

        $this->is_curl = $is_curl;

        return $this->post($link, $data, $this->referer, $header);
    }

    /**
     * @param string $merchant_id
     * @param string $merchant_sku
     * @param bool $header
     * @param bool $is_curl
     * @return \CurlHandle|string|null
     */
    public function getCardDetails(string $merchant_id, string $merchant_sku, bool $header = false, bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/bff/offer-view/details?m=' . $merchant_id . '&s=' . $merchant_sku;

        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, $header);
    }

    /**
     * @param bool $header
     * @param bool $is_curl
     * @return \CurlHandle|string|null
     */
    public function getMerchant(bool $header = true, bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json',
            'Accept-Encoding: gzip, deflate, br',//
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://kaspi.kz/merchantcabinet/api/merchant/appData';

        $this->referer = 'https://kaspi.kz/mc/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, $header);
    }

    /**
     * @param string $merchant_id
     * @param string $merchant_sku
     * @param int $price
     * @param string $model
     * @param array $points
     * @param bool $header
     * @param bool $is_curl
     * @return \CurlHandle|string|null
     */
    public function setPrice(string $merchant_id, string $sku, int $price, string $model, array $points = [], bool $header = true,  bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/pricefeed/upload/merchant/process';
        $array_data = [
            'merchantUid' => $merchant_id,
            'sku' => $sku,
            'model' => $model,
            'price' => $price,
        ];
        $array_data = array_merge($array_data, $points);

        $this->referer = 'https://kaspi.kz/';
        $data = json_encode($array_data);

        if ($merchant_id == 16818127){
            Logger::log($data, 'ayan');
        }

        $this->is_curl = $is_curl;

        return $this->post($link, $data, $this->referer, $header); //json_decode($get, $associative);

    }

    public function getMerchantOffers(string $merchant_id, int $p = 0, int $l = 100, string $a = 'true', string $t = null, string $c = null, bool $header = true, bool $is_curl = false): CurlHandle|string|null
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/bff/offer-view/list?m=' . $merchant_id . '&p=' . $p . '&l=' . $l . '&a=' . $a . '&t=' . $t . '&c=' . $c;

        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, $header);
    }
//
//    public function getCategoryCommissions(): ?string
//    {
//
//    }

    /**
     * @param string $merchant_id
     * @param int $limit
     * @param int $page
     * @param string $filter_type
     * @param bool $header
     * @param bool $is_curl
     * @return string|null
     */
    public function getRefunds(
        string $merchant_id,
        int $limit = 10,
        int $page = 0,
        string $filter_type = "NEW",
        bool $header = true,
        bool $is_curl = false
    ) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/refund/api/v1/merchant-cabinet/load-refunds?merchantId=' . $merchant_id;

        $this->referer = 'https://kaspi.kz/';
        //{"paging":{"limit":10,"page":0},"filterType":"NEW"}

        $array_data = [
            'paging' => [
                'limit' => $limit,
                'page' => $page
            ],
            'filterType' => $filter_type
        ];

        $json_data = json_encode($array_data);

        $this->is_curl = $is_curl;

        return $this->post($link, $json_data, $this->referer, $header);
    }

    /**
     * @param string $merchant_id
     * @param array|string $selected_tabs
     * @param int $count
     * @param int $start_index
     * @param bool $header
     * @param bool $is_curl
     * @return string|null
     */
    public function getOrderTabsActive(
        string $merchant_id,
        array|string $selected_tabs,
        int $count = 10,
        int $start_index = 0,
        bool $header = false,
        bool $is_curl = false)
    : ?string
    {


        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString(),
        ]);

        $selected_tabs_str = '';
        if (is_array($selected_tabs)){
            foreach ($selected_tabs as $tab){
                $selected_tabs_str .= '&selectedTabs=' . $tab;
            }
        } else {
            $selected_tabs_str .= '&selectedTabs=' . $selected_tabs;
        }


        $link = 'https://mc.shop.kaspi.kz/mc/api/orderTabs/active?count=' . $count . $selected_tabs_str . '&startIndex=' . $start_index . '&_m=' . $merchant_id;

        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    public function getOrderTabsActiveTest(
        string $merchant_id,
        string|array $selected_tabs,
        int $count = 10,
        int $start_index = 0,
        bool $header = false,
        bool $is_curl = false)
    : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString(),
        ]);
        $selected_tabs_str = '';
        if (is_array($selected_tabs)){
            foreach ($selected_tabs as $tab){
                $selected_tabs_str .= '&selectedTabs=' . $tab;
            }
        } else {
            $selected_tabs_str .= '&selectedTabs=' . $selected_tabs;
        }

        $link = 'https://mc.shop.kaspi.kz/mc/api/orderTabs/active?count=' . $count . $selected_tabs_str . '&startIndex=' . $start_index . '&_m=' . $merchant_id;

        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    /**
     * @param string $merchant_id
     * @param int $from_date
     * @param int $to_date
     * @param string $statuses
     * @param int $start
     * @param int $count
     * @param bool $header
     * @param bool $is_curl
     * @return string|null
     */
    public function getOrderTabsArchive(
        string $merchant_id,
        int $from_date,
        int $to_date,
        string $statuses,
        int $start = 0,
        int $count = 10,
        bool $header = false,
        bool $is_curl = false)
    : ?string
    {

        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

//GET /mc/api/orderTabs/archive?start=0&count=10&fromDate=1719082800000&toDate=1719147875099&statuses=CANCELLED&statuses=COMPLETED&statuses=RETURNED&statuses=CREDIT_TERMINATION_PROCESS&_m=17965408 HTTP/1.1
        $link = "https://mc.shop.kaspi.kz/mc/api/orderTabs/archive?start=$start&count=$count&fromDate=$from_date&toDate=$to_date&statuses=$statuses&_m=$merchant_id";
        $this->referer = 'https://kaspi.kz/';
        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    public function getOrderTabsFilters(
        string $merchant_id,
        string $selected_tabs,
        bool $is_curl = false
    ) : ?string
    {

        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/mc/api/orderTabs/filters?selectedTabs=' . $selected_tabs . '&_m=' . $merchant_id;
        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    public function getAllCategories(string $merchant_id, bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/merchantcabinet/api/product/finalCategories/?_m=' . $merchant_id;
        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer);
    }

    /**
     * @param bool $header
     * @param bool $is_curl
     * @return string|null
     */
    public function getMerchantStatus(bool $header = true, bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = "https://kaspi.kz/merchantcabinet/api/su/info";

        $this->referer = 'https://kaspi.kz/mc/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, $header);
    }

    /**
     * @param $merchant_id
     * @param bool $header
     * @param bool $is_curl
     * @return string|null
     */
    public function getMerchantSettings($merchant_id, bool $header = true, bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Host: kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = "https://mc.shop.kaspi.kz/offers/api/v1/offer/settings?m=$merchant_id";

        $this->referer = 'https://kaspi.kz/mc/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, $header);
    }



    public function checkKaspiApiKey(string $api_key, bool $header = true, bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'X-Auth-Token: ' . $api_key
        ]);

        $link = 'https://mc.shop.kaspi.kz/s/m';
        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, 1);
    }

    /**
     * @param string $file_path
     * @param $merchant_id
     * @param bool $is_curl
     * @return string|null
     */
    public function uploadPriceList(string $file_path, $merchant_id, bool $is_curl = false) : ?string
    {
        $boundary = '----WebKitFormBoundary' . uniqid();
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = 'https://mc.shop.kaspi.kz/pricefeed/upload/merchant/upload';
        $this->referer = 'https://kaspi.kz/';

        $mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        // Формирование данных для отправки
        $post_fields = "--" . $boundary . "\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\""
            . basename($file_path) . "\"\r\n"
            . "Content-Type: " . $mime_type . "\r\n\r\n"
            . file_get_contents($file_path) . "\r\n"
            . "--" . $boundary . "\r\n"
            . "Content-Disposition: form-data; name=\"merchantUid\"\r\n\r\n"
            . $merchant_id . "\r\n"
            . "--" . $boundary . "--\r\n";

        $this->is_curl = $is_curl;

        // Выполнение POST запроса
        return $this->post($link, $post_fields, $this->referer, 1);
    }

    /**
     * @param string $merchant_id
     * @param string $file
     * @param bool $is_curl
     * @return string|null
     */
    public function checkUploadStatus(string $merchant_id, string $file,  bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Cookie: ' . $this->cookiesToString()
        ]);

        $link = "https://mc.shop.kaspi.kz/pricefeed/protocol/merchant/file/$file?m=" . $merchant_id;
//        https://mc.shop.kaspi.kz/pricefeed/protocol/merchant/file/669cafe7888c8d09000fe59b?m=17965408
        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->get($link, $this->referer, 1);
    }

    /**
     * @param string|array $data
     * @param bool $is_curl
     * @return string|null
     */
    public function onOrOffProduct(string|array $data, bool $is_curl = false) : ?string
    {
        $this->setHeader([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Host: mc.shop.kaspi.kz',
            'Accept-Language: en-US,en;q=0.8',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
        ]);

        $link = 'https://mc.shop.kaspi.kz/pricefeed/upload/merchant/process/process/batch';

        $this->referer = 'https://kaspi.kz/';

        $this->is_curl = $is_curl;

        return $this->post($link, $data, $this->referer, 1);
    }
}
