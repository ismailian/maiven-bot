<?php

namespace TeleBot\App\Handlers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
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
     * @throws Exception|GuzzleException
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

        $caption = Utils::getCaption($selected);
        $coverPath = Utils::getCover($this->event['callback_query']['from']['id'], $selected['cover']);
        $back = (new InlineKeyboard(1))->addButton(
            '⬅ Back', ['season' => $sIndex], InlineKeyboard::CALLBACK_DATA
        )->toArray();

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$inlineKeyboard->toArray(), ...$back]]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

}