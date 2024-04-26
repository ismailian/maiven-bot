<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\Events\Text;
use TeleBot\App\Services\Show365;
use TeleBot\System\SessionManager;
use TeleBot\System\Filters\Awaits;
use TeleBot\System\Events\Command;
use TeleBot\System\Types\InlineKeyboard;
use TeleBot\System\Events\CallbackQuery;
use GuzzleHttp\Exception\GuzzleException;
use TeleBot\System\Types\IncomingCallbackQuery;

class Search extends BaseEvent
{

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
        $keyword = $this->event['message']['text'];
        $this->telegram->sendMessage("Searching for: ($keyword)...");

        $results = Show365::search($keyword, 10);
        if (empty($results)) {
            // todo: return error message
            return;
        }

        $session = SessionManager::get();
        $session['search'] = $results;
        $session['selected'] = null;

        unset($session['await']);
        SessionManager::set($session);

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

        $reply = "Title: {$results[$index]->title}\n";
        $reply .= "Rating: " . Utils::r2s($results[$index]->rating) . "\n";
        $reply .= "Released In: {$results[$index]->released}\n";
        $reply .= "Type: {$results[$index]->type}\n";
        $reply .= "Description: " . Utils::shorten($results[$index]->description) . "\n";

        $this->telegram->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])->editMessage($this->telegram->getLastMessageId(), $reply);
    }

    /**
     * handle search command
     *
     * @return void
     * @throws Exception
     */
    #[Command('search')]
    public function onSearch(): void
    {
        SessionManager::start()->set(['await' => 'search']);
        $this->telegram->sendMessage('Please type in the search keyword:');
    }

    /**
     * handle back to search callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     */
    #[CallbackQuery('back', 'search')]
    public function onBack(IncomingCallbackQuery $query): void
    {
        $index = 0;
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

        $this->telegram
            ->withOptions(['reply_markup' => [
            'inline_keyboard' => [...$select, ...$navigation]
        ]])
            ->editMessage($query->messageId, $reply);
    }

    /**
     * handle media callback query
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws Exception
     * @throws GuzzleException
     */
    #[CallbackQuery('media')]
    public function onSelect(IncomingCallbackQuery $query): void
    {
        $uuid = $query('media');
        $session = SessionManager::get();
        $session['selected'] = $session['search'][$query('index')];
        $isMovie = $session['selected']['type'] == 'movie';
        $result = Show365::getShow($uuid, $isMovie);
        if (empty($result)) {
            // todo: return error message
            return;
        }

        $key = $isMovie ? 'formats' : 'seasons';
        $session['selected'][$key] = $result;
        SessionManager::set($session);

        $inlineKeyboard = new InlineKeyboard();
        if ($isMovie) {
            foreach ($result as $i => $format) {
                $inlineKeyboard->addButton("{$format->format}p", ['movie:format' => $i], InlineKeyboard::CALLBACK_DATA);
            }
        } else {
            foreach ($result as $i => $season) {
                $inlineKeyboard->addButton("S" . Utils::padLeft($season->number), ['season' => $i], InlineKeyboard::CALLBACK_DATA);
            }
        }

        $reply = "Title: {$session['selected']['title']}\n";
        $reply .= "Rating: " . Utils::r2s($session['selected']['rating']) . "\n";
        $reply .= "Released In: {$session['selected']['released']}\n";
        $reply .= "Type: {$session['selected']['type']}\n";
        $reply .= "Description: " . Utils::shorten($session['selected']['description']) . "\n\n";
        $reply .= "Please choose a " . ($isMovie ? 'format to download:' : 'season to proceed:');

        $back = (new InlineKeyboard(1))->addButton(
            '⬅ Back', ['back' => 'search'], InlineKeyboard::CALLBACK_DATA
        )->toArray();
        $this->telegram
            ->withOptions(['reply_markup' => ['inline_keyboard' => [...$inlineKeyboard->toArray(), ...$back]]])
            ->editMessage($query->messageId, $reply);
    }

}