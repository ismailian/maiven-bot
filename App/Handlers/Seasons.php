<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\App\Services\Show365;
use TeleBot\System\SessionManager;
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
        $session = SessionManager::get();

        $selected = $session['selected'];
        $uuid = $selected['seasons'][$index]['id'];
        $number = $selected['seasons'][$index]['number'];

        $result = Show365::getEpisodes($uuid);
        if (empty($result)) return;

        $selected['seasons'][$index]['episodes'] = $result;
        $session['selected'] = $selected;
        SessionManager::set($session);

        $inlineKeyboard = new InlineKeyboard();
        foreach ($result as $i => $episode) {
            $inlineKeyboard->addButton(
                'S' . Utils::padLeft($number) . 'E' . Utils::padLeft($episode->number),
                ['s' => $index, 'episode' => $i],
                InlineKeyboard::CALLBACK_DATA
            );
        }

        $reply = "Title: {$session['selected']['title']}\n";
        $reply .= "Rating: " . Utils::r2s($session['selected']['rating']) . "\n";
        $reply .= "Released In: {$session['selected']['released']}\n";
        $reply .= "Type: {$session['selected']['type']}\n";
        $reply .= "Description: " . Utils::shorten($session['selected']['description']) . "\n\n";
        $reply .= "Please choose an episode to proceed:";

        $media = array_filter(SessionManager::get('search'), fn($m) => $m['id'] == $selected['id']);
        $mIndex = array_keys($media)[0];

        $back = (new InlineKeyboard(1))->addButton(
            '⬅ Back', ['index' => $mIndex, 'media' => $selected['id']], InlineKeyboard::CALLBACK_DATA
        )->toArray();
        $this->telegram
            ->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$inlineKeyboard->toArray(), ...$back]
        ]])
            ->editMessage($query->messageId, $reply);
    }

}