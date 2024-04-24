<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\SessionManager;
use TeleBot\System\Events\CallbackQuery;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Types\IncomingCallbackQuery;

class Episodes extends BaseEvent
{

    /**
     * handle episode callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('episode')]
    public function onEpisode(IncomingCallbackQuery $query): void
    {
        $sIndex = (int)$query('s');
        $eIndex = (int)$query('episode');

        $selected = SessionManager::get('selected');
        $season = $selected['seasons'][$sIndex];
        $episode = $season['episodes'][$eIndex];

        $inlineKeyboard = new InlineKeyboard();
        foreach ($episode['formats'] as $i => $format) {
            $inlineKeyboard->setRowMax(2)->addButton(
                $format['format'] . "p",
                ['s' => $sIndex, 'e' => $eIndex, 'series:format' => $i],
                InlineKeyboard::CALLBACK_DATA
            );
        }

        $reply = "Title: {$selected['title']}\n";
        $reply .= "Rating: " . Utils::r2s($selected['rating']) . "\n";;
        $reply .= "Released In: {$selected['released']}\n";
        $reply .= "Type: {$selected['type']}\n";
        $reply .= "Description: " . Utils::shorten($selected['description']) . "\n\n";
        $reply .= "Please choose an format to download:";

        $this->telegram->withOptions(['reply_markup' => [
            'inline_keyboard' => $inlineKeyboard->toArray()
        ]])->editMessage($query->messageId, $reply);
    }

}