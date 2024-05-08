<?php

namespace TeleBot\App\Handlers;

use TeleBot\System\Process;
use TeleBot\System\Session;
use TeleBot\System\BaseEvent;
use TeleBot\App\Helpers\Utils;
use TeleBot\System\Events\CallbackQuery;
use GuzzleHttp\Exception\GuzzleException;
use TeleBot\System\Types\IncomingCallbackQuery;

class Downloads extends BaseEvent
{

    /**
     * prepare a whole season for background download
     *
     * @param IncomingCallbackQuery $query
     * @return void
     * @throws GuzzleException
     */
    #[CallbackQuery('season:prepare')]
    public function prepare(IncomingCallbackQuery $query): void
    {
        $sIndex = (int)$query('season:prepare');
        $this->telegram->sendMessage('preparing your download...');
        $feedbackId = $this->telegram->getLastMessageId();
        $inputFile = 'tmp/' . md5(microtime(true)) . '.txt';

        $season = Session::get('selected.seasons')[$sIndex];
        $urls = [];

        /** call the Aria2c api */
        foreach ($season['episodes'] as $episode) {
            $sNumber = Utils::padLeft($season['number']);
            $eNumber = Utils::padLeft($episode['number']);
            $format = Utils::getBestFormat($episode['formats'], 720);
            $filename = "S{$sNumber}E{$eNumber}.mp4";

            $url = $format['url'] . PHP_EOL;
            $url .= '   dir=/home/user/download/tmp' . PHP_EOL;
            $url .= '   out=' . $filename;

            $urls[] = $url;
        }

        if (empty($url)) {
            $this->telegram->editMessage($feedbackId, 'There are no episodes to download!!');
            return;
        }

        $this->telegram->editMessage($feedbackId, 'Writing urls to input file...');
        file_put_contents($inputFile, join(PHP_EOL, $urls));

        $this->telegram->editMessage($feedbackId, 'Starting download process..');
        Process::runAsync('aria2c', '-s 10', '-x 10', '-i', $inputFile);
    }

}