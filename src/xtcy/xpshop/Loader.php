<?php

namespace xtcy\xpshop;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use xtcy\xpshop\commands\XpShopCommand;

class Loader extends PluginBase
{
    public static Loader $instance;

    public function onLoad(): void
    {
        self::$instance = $this;
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register("xpshopgui", new XpShopCommand($this, "xpshop", "Open the EXP shop gui", ["xps"]));
    }

    public static function getInstance() : Loader {
        return self::$instance;
    }
}