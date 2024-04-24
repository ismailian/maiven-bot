<?php

namespace TeleBot\App\Handlers;

use Exception;
use TeleBot\System\BaseEvent;
use TeleBot\System\Events\Command;
use TeleBot\System\Events\Text;
use TeleBot\System\Filters\Awaits;
use TeleBot\System\SessionManager;

class Welcome extends BaseEvent
{

    /**
     * handle start command
     *
     * @return void
     */
    #[Command('start')]
    public function onStart(): void {}

}