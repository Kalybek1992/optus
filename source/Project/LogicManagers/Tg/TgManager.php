<?php

namespace Source\Project\LogicManagers\Tg;

use Source\Project\Requests\TgBot;

class TgManager
{

    /**
     * @param \Source\Project\Requests\TgBot $tg_bot
     * @param array|null $answer_buttons
     * @param bool $edit
     * @return void
     */
    public static function sendMessages(TgBot $tg_bot, ?array $answer_buttons, bool $edit = false): array
    {
        $results = [];

        if ($edit) {
            if ($answer_buttons['r_text'] ?? false) {
                $tg_bot->RButtons($answer_buttons['r_buttons'] ?? []);
                $results[] = $tg_bot->editMessageText($answer_buttons['r_text']);
            }

            if ($answer_buttons['i_text'] ?? false) {
                $tg_bot->IButtons($answer_buttons['i_buttons'] ?? []);
                $results[] = $tg_bot->editMessageText($answer_buttons['i_text']);
            }
        } else {
            if ($answer_buttons['r_text'] ?? false) {
                $tg_bot->RButtons($answer_buttons['r_buttons'] ?? []);
                $results[] = $tg_bot->sendMessage($answer_buttons['r_text']);
            }

            if ($answer_buttons['i_text'] ?? false) {
                $tg_bot->IButtons($answer_buttons['i_buttons'] ?? []);
                $results[] = $tg_bot->sendMessage($answer_buttons['i_text']);
            }
        }

        return $results;
    }

}