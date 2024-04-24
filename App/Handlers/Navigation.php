<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\SessionManager;
use TeleBot\System\Events\CallbackQuery;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Types\IncomingCallbackQuery;

class Navigation extends BaseEvent
{

    /**
     * handle next navigation callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('nav', 'next')]
    public function next(IncomingCallbackQuery $query): void
    {
        $index = $query('index');
        $results = SessionManager::get('search');
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

        $reply = "Title: {$results[$index]['title']}\n";
        $reply .= "Rating: " . Utils::r2s($results[$index]['rating']) . "\n";
        $reply .= "Released In: {$results[$index]['released']}\n";
        $reply .= "Type: {$results[$index]['type']}\n";
        $reply .= "Description: " . Utils::shorten($results[$index]['description']) . "\n";

        $this->telegram->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])->editMessage($query->messageId, $reply);
    }

    /**
     * handle previous navigation callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('nav', 'prev')]
    public function previous(IncomingCallbackQuery $query): void
    {
        $index = $query('index');
        if ($index <= 0) return;

        $index--;
        $cursor = $index + 1;
        $results = SessionManager::get('search');
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

        $reply = "Title: {$results[$index]['title']}\n";
        $reply .= "Rating: " . Utils::r2s($results[$index]['rating']) . "\n";
        $reply .= "Released In: {$results[$index]['released']}\n";
        $reply .= "Type: {$results[$index]['type']}\n";
        $reply .= "Description: " . Utils::shorten($results[$index]['description']) . "\n";

        $this->telegram->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])->editMessage($query->messageId, $reply);
    }

}