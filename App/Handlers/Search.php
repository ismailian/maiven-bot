<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\Session;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\Events\Text;
use TeleBot\App\Services\Show365;
use TeleBot\System\Filters\Awaits;
use TeleBot\System\Events\Command;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Events\CallbackQuery;
use GuzzleHttp\Exception\GuzzleException;
use TeleBot\System\Types\IncomingCallbackQuery;

class Search extends BaseEvent
{

    /**
     * handle search command
     *
     * @return void
     * @throws Exception|GuzzleException
     */
    #[Command('search')]
    public function onSearch(): void
    {
        Session::set('await', 'search');
        $this->telegram->sendMessage('Please type in the search keyword:');
    }

    /**
     * handle search query
     *
     * @return void
     * @throws Exception
     * @throws GuzzleException
     */
    #[Text(true)]
    #[Awaits('await', 'search')]
    public function onSearchQuery(): void
    {
        $keyword = $this->event->message->text;
        $this->telegram->sendMessage("Searching for: ($keyword)...");

        $results = Show365::search($keyword, 10);
        if (empty($results)) {
            // todo: return error message
            return;
        }

        $session = Session::get();
        $session['search'] = $results;
        $session['selected'] = null;

        unset($session['await']);
        Session::set('*', $session);

        $index = 0;
        $cursor = $index + 1;
        $select = (new InlineKeyboard)->setRowMax(1)->addButton(
            '✔️ Confirm',
            ['index' => $index, 'media' => $results[$index]->id],
            InlineKeyboard::CALLBACK_DATA
        )->toArray();
        $navigation = (new InlineKeyboard)
            ->setRowMax(3)
            ->addButton('⬅', ['index' => $index, 'nav' => 'prev'], InlineKeyboard::CALLBACK_DATA)
            ->addButton("$cursor/10", "none", InlineKeyboard::CALLBACK_DATA)
            ->addButton('➡', ['index' => $index, 'nav' => 'next'], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $userId = $this->event->message->from->id;
        $coverPath = Utils::getCover($userId, $results[$index]->id, $results[$index]->cover);

        $this->telegram->deleteLastMessage();
        $this->telegram->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])->sendPhoto($coverPath, Utils::getCaption((array)$results[$index]));
    }

    /**
     * handle back to search callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('back', 'search')]
    public function onBack(IncomingCallbackQuery $query): void
    {
        $index = 0;
        $cursor = $index + 1;
        $results = Session::get('search');
        $select = (new InlineKeyboard)->setRowMax(1)->addButton(
            '✔️ Confirm',
            ['index' => $index, 'media' => $results[$index]['id']],
            InlineKeyboard::CALLBACK_DATA
        )->toArray();
        $navigation = (new InlineKeyboard)
            ->setRowMax(3)
            ->addButton('⬅', ['index' => $index, 'nav' => 'prev'], InlineKeyboard::CALLBACK_DATA)
            ->addButton("$cursor/10", "none", InlineKeyboard::CALLBACK_DATA)
            ->addButton('➡', ['index' => $index, 'nav' => 'next'], InlineKeyboard::CALLBACK_DATA)
            ->toArray();

        $userId = $this->event->callbackQuery->from->id;
        $caption = Utils::getCaption($results[$index]);
        $coverPath = Utils::getCover($userId, $results[$index]['id'], $results[$index]['cover']);

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$select, ...$navigation]]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

    /**
     * handle media callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('media')]
    public function onSelect(IncomingCallbackQuery $query): void
    {
        $uuid = $query('media');
        $session = Session::get();
        $session['selected'] = $session['search'][$query('index')];
        $isMovie = $session['selected']['type'] == 'movie';
        $result = Show365::getShow($uuid, $isMovie);
        if (empty($result)) {
            // todo: return error message
            return;
        }

        $key = $isMovie ? 'formats' : 'seasons';
        $session['selected'][$key] = $result;
        Session::set('*', $session);

        $inlineKeyboard = new InlineKeyboard();
        if ($isMovie) {
            usort(
                $result,
                fn($a, $b) => ($a->format == $b->format) ? 0 : ($a->format < $b->format ? 1 : -1)
            );
            foreach ($result as $i => $format) {
                $inlineKeyboard->addButton("{$format->format}p", ['movie:format' => $i], InlineKeyboard::CALLBACK_DATA);
            }
        } else {
            foreach ($result as $i => $season) {
                $inlineKeyboard->addButton("S" . Utils::padLeft($season->number), ['season' => $i], InlineKeyboard::CALLBACK_DATA);
            }
        }

        $userId = $this->event->callbackQuery->from->id;
        $back = (new InlineKeyboard(1))->addButton(
            '⬅ Back', ['back' => 'search'], InlineKeyboard::CALLBACK_DATA
        )->toArray();
        $coverPath = Utils::getCover($userId, $session['selected']['id'], $session['selected']['cover']);
        $caption = Utils::getCaption($session['selected']);

        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$inlineKeyboard->toArray(), ...$back]]])
            ->editMedia($query->messageId, 'photo', $coverPath, $caption);
    }

}