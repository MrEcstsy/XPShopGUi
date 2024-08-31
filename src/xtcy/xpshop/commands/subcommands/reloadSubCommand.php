<?php

namespace xtcy\xpshop\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use wockkinmycup\utilitycore\utils\Utils;
use xtcy\xpshop\Loader;

class reloadSubCommand extends BaseSubCommand {

    public function prepare(): void
    {
        $this->setPermission("xpshop.command.admin");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $config = Utils::getConfiguration(Loader::getInstance(), "config.yml");
        $config->reload();
        $sender->sendMessage(C::colorize("&r&l&a(!) &r&aReloaded all configurations."));
    }
}