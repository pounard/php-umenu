<?php

namespace MakinaCorpus\Umenu\Event;

use MakinaCorpus\Umenu\Menu;
use Symfony\Component\EventDispatcher\GenericEvent;

class MenuEvent extends GenericEvent
{
    const EVENT_CREATE = 'menu:create';
    const EVENT_DELETE = 'menu:delete';
    const EVENT_TOGGLE_MAIN = 'menu:toggle-main';
    const EVENT_TOGGLE_ROLE = 'menu:toggle-role';
    const EVENT_UPDATE = 'menu:update';

    /**
     * Get menu
     *
     * @return Menu
     */
    public function getMenu()
    {
        return $this->subject;
    }
}
