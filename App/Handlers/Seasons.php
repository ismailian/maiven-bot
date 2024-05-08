<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\Session;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\App\Services\Show365;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Events\CallbackQuery;
use GuzzleHttp\Exception\GuzzleException;
use TeleBot\System\Types\IncomingCallbackQuery;

class Seasons extends BaseEvent
{

    /**
     * handle season callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     * @throws GuzzleException
     */
    #[CallbackQuery('season')]
    public function onSeason(IncomingCallbackQuery $query): void
    {
        $index = (int)$query('season');
        $session = Session::get();

        $selected = $session['selected'];
        $uuid = $selected['seasons'][$index]['id'];
        $number = $selected['seasons'][$index]['number'];

        $result = Show365::getEpisodes($uuid);
        if (empty($result)) return;

        $selected['seasons'][$index]['episodes'] = $result;
        $session['selected'] = $selected;
        Session::set('*', $session);

        $inlineKeyboard = new InlineKeyboard();
        foreach ($result as $i => $episode) {
            $inlineKeyboard->addButton(
                'S' . Utils::padLeft($number) . 'E' . Utils::padLeft($episode->number),
                ['s' => $index, 'episode' => $i],
                InlineKeyboard::CALLBACK_DATA
            );
        }

        $media = array_filter(Session::get('search'), fn($m) => $m['id'] == $selected['id']);
        $mIndex = array_keys($media)[0];
        $navigation = Utils::getBackButton(['index' => $mIndex, 'media' => $selected['id']]);
        $prepare = Utils::getPrepareButton($index);
        if (!in_array($this->event->callbackQuery->from->id, ['5655471560', '990663891'])) {
            $prepare = [];
        }

        $userId = $this->event->callbackQuery->from->id;
        $caption = Utils::getCaption($selected);
        $coverPath = Utils::getCover($userId, $selected['id'], $selected['cover']);

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$inlineKeyboard->toArray(), ...$prepare, ...$navigation]]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

    /**
     * handle full season callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws GuzzleException
     */
    #[CallbackQuery('season:all')]
    public function onAllSeason(IncomingCallbackQuery $query): void
    {
        $index = (int)$query('season:all');
        $session = Session::get();

        $userId = $this->event->callbackQuery->from->id;
        $selected = $session['selected'];
        $uuid = $selected['seasons'][$index]['id'];
        $number = Utils::padLeft($selected['seasons'][$index]['number']);

        $episodes = $selected['seasons'][$index]['episodes'] ?? null;
        if (empty($episodes) || !is_array($episodes))
            $episodes = Show365::getEpisodes($uuid);

        $files = Utils::getFiles($userId, $selected['title'], $number, $episodes);
        $caption = Utils::getCaption($selected, $number);
        $navigation = Utils::getBackButton(['season' => $index]);

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => $navigation]])
            ->editMedia($query->messageId, 'document', $files[0], $caption);

        /** delete generated files */
        array_map('unlink', $files);
    }

}