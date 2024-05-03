<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\Session;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Events\CallbackQuery;
use GuzzleHttp\Exception\GuzzleException;
use TeleBot\System\Types\IncomingCallbackQuery;

class Formats extends BaseEvent
{

    /**
     * handle movie format callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('movie:format')]
    public function onMovieFormat(IncomingCallbackQuery $query): void
    {
        $fIndex = (int)$query('movie:format');
        $selected = Session::get('selected');

        usort($selected['formats'], fn($a, $b) => ($a['format'] == $b['format']) ? 0 : ($a['format'] < $b['format'] ? 1 : -1));
        $format = $selected['formats'][$fIndex];

        $userId = $this->event->callbackQuery->from->id;
        $caption = Utils::getCaption($selected);
        $coverPath = Utils::getCover($userId, $selected['id'], $selected['cover']);

        $media = array_filter(Session::get('search'), fn($m) => $m['id'] == $selected['id']);
        $mIndex = array_keys($media)[0];

        $inlineKeyboard = (new InlineKeyboard(1))
            ->addButton('Download', $format['url'])
            ->addButton('⬅ Back', ['index' => $mIndex, 'media' => $selected['id']], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => $inlineKeyboard]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

    /**
     * handle format callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('series:format')]
    public function onSeriesFormat(IncomingCallbackQuery $query): void
    {
        $sIndex = (int)$query('s');
        $eIndex = (int)$query('e');
        $fIndex = (int)$query('series:format');

        $selected = Session::get('selected');
        $season = $selected['seasons'][$sIndex];
        $episode = $season['episodes'][$eIndex];

        usort($episode['formats'], fn($a, $b) => ($a['format'] == $b['format']) ? 0 : ($a['format'] < $b['format'] ? 1 : -1));
        $format = $episode['formats'][$fIndex];

        $sNumber = Utils::padLeft($season['number']);
        $eNumber = Utils::padLeft($episode['number']);

        $userId = $this->event->callbackQuery->from->id;
        $caption = Utils::getCaption($selected);
        $caption .= "\nS{$sNumber}E{$eNumber}";
        $coverPath = Utils::getCover($userId, $selected['id'], $selected['cover']);

        $inlineKeyboard = (new InlineKeyboard(1))
            ->addButton('Download', $format['url'])
            ->addButton('⬅ Back', ['s' => $sIndex, 'episode' => $eIndex], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => $inlineKeyboard]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

}
