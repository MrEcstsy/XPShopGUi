<?php

namespace xtcy\xpshop\commands;

use CortexPE\Commando\BaseCommand;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use wockkinmycup\utilitycore\utils\Utils;
use xtcy\xpshop\commands\subcommands\addSubCommand;
use xtcy\xpshop\commands\subcommands\reloadSubCommand;
use xtcy\xpshop\Loader;
use xtcy\xpshop\utils\XpShopGUI;

class XpShopCommand extends BaseCommand {

    public XpShopGUI $xpShopUI;

    public function prepare(): void
    {
        $this->setPermission("xpshop.command");
        $this->xpShopUI = new XpShopGUI();
        $this->registerSubCommand(new AddSubCommand(Loader::getInstance(), "add", "add an item to the shop.", ["set"]));
        $this->registerSubCommand(new reloadSubCommand(Loader::getInstance(), "reload", "reload all the configurations"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if(!$sender instanceof Player) {
            $sender->sendMessage("You must run this command in-game!");
            return;
        }

        $this->xpShopUI->getMenu($sender)->send($sender);
    }
}