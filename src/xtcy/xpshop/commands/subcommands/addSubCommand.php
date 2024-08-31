<?php

namespace xtcy\xpshop\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;
use wockkinmycup\utilitycore\utils\Utils;
use xtcy\xpshop\Loader;
use xtcy\xpshop\utils\XpShopGUI;

class AddSubCommand extends BaseSubCommand {

    protected XpShopGUI $xpShopUI;

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("xpshop.command.admin");
        $this->registerArgument(0, new IntegerArgument("price", false));
        $this->registerArgument(1, new RawStringArgument("command", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&cYou must run this command in-game."));
            return;
        }

        $inv = $this->xpShopUI->getMenu($sender)->getInventory();
        $config = Utils::getConfiguration(Loader::getInstance(), "config.yml");
    }
}