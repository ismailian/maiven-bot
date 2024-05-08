<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\Session;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\Events\CallbackQuery;
use TeleBot\System\Types\InlineKeyboard;
use GuzzleHttp\Exception\GuzzleException;
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

        $selected = Session::get('selected');
        $season = $selected['seasons'][$sIndex];
        $episode = $season['episodes'][$eIndex];
        usort(
            $episode['formats'],
            fn($a, $b) => ($a['format'] == $b['format']) ? 0 : ($a['format'] < $b['format'] ? 1 : -1)
        );

        $inlineKeyboard = new InlineKeyboard();
        foreach ($episode['formats'] as $i => $format) {
            $inlineKeyboard->setRowMax(2)->addButton(
                $format['format'] . "p", $format['url'], InlineKeyboard::URL
            );
        }

        $userId = $this->event->callbackQuery->from->id;
        $coverPath = Utils::getCover($userId, $selected['id'], $selected['cover']);
        $caption = Utils::getCaption($selected);
        $caption .= PHP_EOL . 'S' . Utils::padLeft($sIndex + 1) . 'E' . Utils::padLeft($eIndex + 1);

        $back = Utils::getBackButton(['season' => $sIndex]);
        $pager = Utils::getEpisodePager($sIndex, $eIndex, $season['episodes']);

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$inlineKeyboard->toArray(), ...$pager, ...$back]]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

}