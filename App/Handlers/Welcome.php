<?php

namespace TeleBot\App\Handlers;

use TeleBot\System\BaseEvent;
use TeleBot\System\Events\Command;
use GuzzleHttp\Exception\GuzzleException;

class Welcome extends BaseEvent
{

    /**
     * handle start command
     *
     * @return void
     * @throws GuzzleException
     */
    #[Command('start')]
    public function onStart(): void
    {
        $this->telegram->sendMessage(
            "Hello and welcome to MaivenBot!\nsearch and download your favourite movies and series."
        );
    }

}