<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\SessionManager;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Events\CallbackQuery;
use TeleBot\System\Types\IncomingCallbackQuery;

class Formats extends BaseEvent
{

    /**
     * handle movie format callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('movie:format')]
    public function onMovieFormat(IncomingCallbackQuery $query): void
    {
        $fIndex = (int)$query('movie:format');
        $selected = SessionManager::get('selected');
        $format = $selected['formats'][$fIndex];

        $reply = "Title: {$selected['title']}\n";
        $reply .= "Rating: " . Utils::r2s($selected['rating']) . "\n";
        $reply .= "Released In: {$selected['released']}\n";
        $reply .= "Type: {$selected['type']}\n";
        $reply .= "Description: " . Utils::shorten($selected['description']) . "\n\n";
        $reply .= "({$format['format']}p)";

        $media = array_filter(SessionManager::get('search'), fn($m) => $m['id'] == $selected['id']);
        $mIndex = array_keys($media)[0];

        $inlineKeyboard = (new InlineKeyboard(1))
            ->addButton('Download', $format['url'])
            ->addButton('⬅ Back', ['index' => $mIndex, 'media' => $selected['id']], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => $inlineKeyboard]])
            ->editMessage($query->messageId, $reply);
    }

    /**
     * handle format callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('series:format')]
    public function onSeriesFormat(IncomingCallbackQuery $query): void
    {
        $sIndex = (int)$query('s');
        $eIndex = (int)$query('e');
        $fIndex = (int)$query('series:format');

        $selected = SessionManager::get('selected');
        $season = $selected['seasons'][$sIndex];
        $episode = $season['episodes'][$eIndex];
        $format = $episode['formats'][$fIndex];

        $sNumber = str_pad($season['number'], 2, 0, STR_PAD_LEFT);
        $eNumber = str_pad($episode['number'], 2, 0, STR_PAD_LEFT);

        $reply = "Title: {$selected['title']}\n";
        $reply .= "Rating: " . Utils::r2s($selected['rating']) . "\n";
        $reply .= "Released In: {$selected['released']}\n";
        $reply .= "Type: {$selected['type']}\n";
        $reply .= "Description: " . Utils::shorten($selected['description']) . "\n\n";
        $reply .= "S{$sNumber}E{$eNumber} ({$format['format']}p)";

        $inlineKeyboard = (new InlineKeyboard(1))
            ->addButton('Download', $format['url'])
            ->addButton('⬅ Back', ['s' => $sIndex, 'episode' => $eIndex], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => $inlineKeyboard]])
            ->editMessage($query->messageId, $reply);
    }

}