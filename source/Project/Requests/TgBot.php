<?php

namespace Source\Project\Requests;

use Source\Base\Core\Logger;
use Source\Base\HttpRequests\ProxyHttpRequest;

/**
 * Class TgBotRequest
 * @package Source\Requests
 */
class TgBot extends ProxyHttpRequest
{
    /**
     * @var false|mixed
     */
    public mixed $action;
    /**
     * @var int|null
     */
    public ?int $send_message_id;
    /**
     * @var string|null
     */
    protected ?string $token;
    /**
     * @var int|null
     */
    public ?int $message_id;
    /**
     * @var array
     */
    public array $data;
    /**
     * @var int|null
     */
    public ?int $chat_id = null;
    /**
     * @var array|null
     */
    public ?array $field = [];
    /**
     * @var string|null
     */
    public ?string $name = NULL;
    /**
     * @var string|null
     */
    public ?string $text = NULL;
    /**
     * @var array|null
     */
    public ?array $data_bot;
    /**
     * @var string|null
     */
    public ?string $response;
    /**
     * @var bool
     */
    public bool $edit = false;
    /**
     * @var string|null
     */
    public ?string $parse_mode = 'MarkDown';

    /**
     * @var array
     */
    public array $text_data = [];

    /**
     * TgBotLM constructor.
     * @param string $token
     * @param null $chat_id
     */
    public function __construct(string $token, $chat_id = null)
    {
        $this->token = $token;
        $this->getInputData();

        if ($chat_id) {
            $this->chat_id = $chat_id;
        }

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function getInputData(): bool
    {
        $input = file_get_contents('php://input');

        $response = json_decode($input);

        if ($response) {
            if($response->callback_query ?? false) {
                $response = $response->callback_query;

                $this->answerCallbackQuery($response->id);
                $this->setDataBot($response->data);
            }

            if (($response->message->chat->id ?? false) > 0) {
                $this->chat_id = $response->message->chat->id;
                $this->name = ($response->message->chat->username ?? null) ?: 'hidden';

                $this->text = $response->message->text ?? null;


                if (str_contains($this->text ?? '', '/start')) {
                    $this->text_data = explode(' ', $response->message->text);
                    $this->text = ($this->text_data[0] ?? null);
                }

                $this->message_id = $response->message->message_id ?? null;

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $text
     * @param string $delimiter
     */
    public function setDataBot(string $text, string $delimiter = '-')
    {
        $this->data_bot = explode($delimiter, $text);
    }

    /**
     * @param string $text
     */
    public function setAction(string $text): void
    {
        $this->data_bot[0] = $text;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->data_bot[0] ?? null;
    }

    /**
     * @param string $mode
     */
    public function parseMode(string $mode)
    {
        $this->parse_mode = $mode;
    }

    /**
     * @param $method
     * @return array|mixed|null
     */
    public function request($method): mixed
    {
        $data = $this->data;
        $data['parse_mode'] = $this->parse_mode;

        if ($this->field != []) {
            $data['reply_markup'] = json_encode($this->field);
        }

        $this->addOptions([
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/' . $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT_MS => 600
        ]);

        if (is_array($this->chat_id ?? false)){
            foreach ($this->chat_id as $chat_id){
                $this->addOptions([
                    CURLOPT_POSTFIELDS => $data
                ]);

                $response[] = json_decode($this->exec());
            }
        } else {
            $data['chat_id'] = $this->chat_id;
            $this->addOptions([
                CURLOPT_POSTFIELDS => $data
            ]);

            $response = json_decode($this->exec());

            if($response->result->message_id ?? false) {
                $this->send_message_id = $response->result->message_id;
            }
        }

        $this->field = [];
        //Logger::log(print_r($data ?? "EMPTY", 1) ?? null, '$data');
        return $response ?? null;
    }

    /**
     * @param array $data
     */
    public function IButtons(array $data): void
    {
        $this->field = [];

        foreach($data as $key => $value){
            if (!is_array($value)) {
                strpos($value,'://') !== false
                    ? $this->field['inline_keyboard'][] = [
                        [
                            'text' => $key,
                            'url'=>$value
                        ]
                ]
                    : $this->field['inline_keyboard'][] = [
                        ['text' => $key,'callback_data'=>$value]
                ];
            } else {
                $field = [];

                foreach($value as $sec_key => $sec_value){
                    str_contains($sec_value, '://')
                        ? $field[] = [
                            'text' => $sec_key,
                            'url' => $sec_value
                    ]
                        : $field[] = [
                            'text' => $sec_key,
                            'callback_data' => $sec_value
                    ];
                }

                $this->field['inline_keyboard'][] = $field;
            }
        }
    }

    /**
     * @param array $data
     */
    public function RButtons(array $data): void
    {
        $this->field = [];
        $this->field['resize_keyboard'] = true;
        $this->field['one_time_keyboard'] = true;

        foreach($data as $key => $value) {
            if (!is_array($value)) {
                $this->field['keyboard'][] = [
                    [
                        'text' => $value
                    ]
                ];
            } else {
                $field = [];

                foreach($value as $sec_key => $sec_value) {
                    $field[] = ['text' => $sec_value];
                }

                $this->field['keyboard'][] = $field;}
        }
    }

    /**
     * @param $message
     * @param $disable_notification
     * @return array|string|null
     */
    public function sendMessage($message, $disable_notification = 'false'): mixed
    {
        $this->data = [
            'text' => $message,
            'disable_notification' => $disable_notification
        ];

        return $this->request('sendMessage');
    }

    /**
     * @param $text
     * @param $message_id
     * @param $notification_status
     * @return array|string|null
     */
    public function editMessageText($text, $message_id = null, $notification_status = 'false'): mixed
    {
        $this->data = [
            'text' => $text,
            'disable_notification' => $notification_status,
            'message_id' => $message_id ?? $this->message_id
        ];

        return $this->request('editMessageText');
    }

    /**
     * @param $photo_link
     * @param $caption
     * @param $notification_status
     * @return array|string|null
     */
    public function sendPhoto($photo_link, $caption = NULL, $notification_status = 'false'): mixed
    {
        $this->data = [
            'caption' => $caption,
            'photo' => $photo_link,
            'disable_notification' => $notification_status
        ];

        return $this->request('sendPhoto');
    }

    /**
     * @param $document_link
     * @param $caption
     * @param $notification_status
     * @return array|string|null
     */
    public function sendDocument($document_link, $caption = NULL, $notification_status = 'false'): mixed
    {
        $this->data = [
            'caption' => $caption,
            'document' => $document_link,
            'disable_notification' => $notification_status
        ];

        return $this->request('sendDocument');
    }

    /**
     * @param $document_link
     * @param $caption
     * @param $notification_status
     * @return array|string|null
     */
    public function sendFile($document_link, $caption = NULL, $notification_status = 'false'): mixed
    {
        $this->data = [
            'caption' => $caption,
            'file' => $document_link,
            'disable_notification' => $notification_status
        ];

        return  $this->request('sendFile');
    }

    /**
     * @param $file_link
     * @return array|string|null
     */
    public function getFile($file_link): mixed
    {
        $this->data['file_id'] = $file_link;

        return $this->request('getFile');
    }

    /**
     * @param $video_id
     * @param $caption
     * @param $notification_status
     * @return array|string|null
     */
    public function sendVideo($video_id, $caption = NULL, $notification_status = 'false'): mixed
    {
        $this->data = [
            'caption' => $caption,
            'video' => $video_id,
            'disable_notification' => $notification_status
        ];

        return $this->request('sendVideo');
    }

    /**
     * @param $callback_query_id
     * @return string|null
     */
    public function answerCallbackQuery($callback_query_id)
    {
        $this->data = [
            'callback_query_id' => $callback_query_id
        ];

        return $this->request('answerCallbackQuery');
    }

    /**
     * @param $link
     * @param $message_id
     * @param $caption
     * @param $type
     * @param $notification_status
     * @return array|string|null
     */
    public function editMessageMedia($link, $message_id = null, $caption = NULL,  $type = 'photo', $notification_status = 'false'): mixed
    {
        $this->data = [
            'message_id'=> $message_id ?? $this->message_id,
            'media' => json_encode([
            'type' => $type,
            'media' => $link,
            'parse_mode' => $this->parse_mode,
            'caption' => $caption
        ]),
            'disable_notification' => $notification_status
        ];

        return $this->request('editMessageMedia');
    }

    /**
     * @param $message_id
     * @return array|string|null
     */
    public function deleteMessage($message_id = null): mixed
    {
        $this->data = [
            'message_id' => $message_id ?? $this->message_id
        ];

        return $this->request('deleteMessage');
    }

}