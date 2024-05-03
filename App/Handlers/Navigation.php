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

class Navigation extends BaseEvent
{

    /**
     * handle next navigation callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('nav', 'next')]
    public function next(IncomingCallbackQuery $query): void
    {
        $index = $query('index');
        $results = Session::get('search');
        if ($index >= (count($results) - 1)) return;

        $index++;
        $cursor = $index + 1;
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
        $coverPath = Utils::getCover($userId, $results[$index]['id'], $results[$index]['cover']);
        $this->telegram
            ->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])
            ->editMedia($query->messageId, 'photo', $coverPath, Utils::getCaption($results[$index]));
    }

    /**
     * handle previous navigation callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception|GuzzleException
     */
    #[CallbackQuery('nav', 'prev')]
    public function previous(IncomingCallbackQuery $query): void
    {
        $index = $query('index');
        if ($index <= 0) return;

        $index--;
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
        $coverPath = Utils::getCover($userId, $results[$index]['id'], $results[$index]['cover']);
        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$select, ...$navigation]]])
            ->editMedia($query->messageId, 'photo', $coverPath, Utils::getCaption($results[$index]));
    }

}