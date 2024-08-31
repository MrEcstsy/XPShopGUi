<?php

namespace xtcy\xpshop\utils;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use wockkinmycup\utilitycore\utils\Utils;
use pocketmine\utils\TextFormat as C;
use xtcy\xpshop\Loader;

class XpShopGUI
{

    public function getMenu(Player $player): InvMenu
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(C::colorize("&r&8Xp Shop"));
        $config = Utils::getConfiguration(Loader::getInstance(), "config.yml");
        $playerExp = $player->getXpManager()->getCurrentTotalXp();
        $playerXpItem = VanillaItems::EXPERIENCE_BOTTLE()->setCustomName(C::colorize($config->getNested("shop.player-exp.name")));
        $configData = $config->getAll();

        if (isset($configData['shop']['player-exp']['lore']) && is_array($configData['shop']['player-exp']['lore'])) {
            $lore = [];
            foreach ($configData['shop']['player-exp']['lore'] as $line) {
                $lineWithExp = str_replace('{exp}', number_format($playerExp), $line);
                $color = C::colorize($lineWithExp);
                $lore[] = $color;
            }
            $playerXpItem->setLore($lore);
        }

        $menu->getInventory()->setItem(49, $playerXpItem);
        $menu->getInventory()->setItem(47, VanillaBlocks::CARPET()->setColor(DyeColor::RED)->asItem()->setCustomName(C::colorize("&r&l&cGo Back"))->setLore([C::colorize("&r&7Go back a page")]));
        $menu->getInventory()->setItem(51, VanillaBlocks::CARPET()->setColor(DyeColor::GREEN)->asItem()->setCustomName(C::colorize("&r&l&aNext Page"))->setLore([C::colorize("&r&7Go forward a page")]));

        // Slots to be updated on the menu
        $slotRanges = [
            [10, 11, 12, 13, 14, 15, 16,
                19, 20, 21, 22, 23, 24, 25,
                28, 29, 30, 31, 32, 33, 34,
                37, 38, 39, 40, 41, 42, 43],
        ];

        $items = $configData['shop']['items'];

        $itemsPerPage = 28;
        $pages = array_chunk($items, $itemsPerPage);

        $currentPage = 0;

        $this->populateMenuPage($menu, $pages, $slotRanges, $currentPage, $itemsPerPage);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($menu, $pages, &$currentPage, &$slotRanges, $itemsPerPage, $config) : void {
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $configData = $config->getAll();
            if ($itemClicked === null) {
                return;
            }

            $matchedItemData = null;
            foreach ($configData["shop"]["items"] as $itemData) {
                // Convert the vanilla item name to lowercase and replace spaces with underscores
                $formattedVanillaName = str_replace(' ', '_', strtolower($itemClicked->getVanillaName()));

                // Compare the formatted vanilla item name
                if ($itemData["item"] === $formattedVanillaName) {
                    $matchedItemData = $itemData;
                    break;
                }
            }

            if ($matchedItemData === null) {
                return;
            }

            // Create a form for the player to confirm the purchase
            $form = new CustomForm(function (Player $player, ?array $data) use ($matchedItemData, $itemClicked, $currentPage, $menu, $pages, $slotRanges, $itemsPerPage) : void {
                if ($data === null || $data[0] === false) {
                    // Player canceled the purchase
                    $this->populateMenuPage($menu, $pages, $slotRanges, $currentPage, $itemsPerPage);
                    return;
                }

                $quantity = (int) $data[1];
                $totalPrice = $matchedItemData["price"] * $quantity;

                // Check if the player has enough XP
                $playerExp = $player->getXpManager()->getCurrentTotalXp();
                if ($playerExp < $totalPrice) {
                    $player->sendMessage(C::RED . "You don't have enough XP to make this purchase.");
                    return;
                }

                // Process the purchase
                // Remove the amount from the player's XP
                $player->getXpManager()->subtractXp($totalPrice);

                // Give the actual clicked item to the player
                $player->getInventory()->addItem($itemClicked->setCount($quantity));

                $player->sendMessage(C::GREEN . "Purchase successful! You've been charged " . number_format($totalPrice) . " XP.");

                // Repopulate the menu after the purchase
                $this->populateMenuPage($menu, $pages, $slotRanges, $currentPage, $itemsPerPage);
            });

            $form->setTitle(C::colorize("&r&8Confirm Purchase"));
            $form->addLabel(C::colorize("&r&7Are you sure you want to purchase:"));
            $form->addLabel($matchedItemData["name"]);
            $form->addSlider(C::colorize("&r&fQuantity"), 1, 64, 1);
            $player->sendForm($form);
        }));

        $excludedSlots = [47, 49, 51];
        Utils::fillBorders($menu->getInventory(), "light_gray_stained_glass_pane", $excludedSlots);
        return $menu;
    }

    private function populateMenuPage(InvMenu $menu, array $pages, array $slotRanges, int $currentPage, int $itemsPerPage): void
    {
        $startIndex = $currentPage * $itemsPerPage;
        $endIndex = $startIndex + $itemsPerPage - 1;

        // Clear items in the slots for the current page
        foreach ($slotRanges[0] as $slot) {
            $menu->getInventory()->clear($slot);
        }

        foreach ($pages[$currentPage] as $index => $itemData) {
            $item = StringToItemParser::getInstance()->parse($itemData['item']);
            $item->setCustomName(C::colorize($itemData['name']));
            $itemLore = [];

            foreach ($itemData['lore'] as $line) {
                $lineWithPrice = str_replace('{price}', number_format($itemData['price']), $line);
                $color = C::colorize($lineWithPrice);
                $itemLore[] = $color;
            }

            $item->setLore($itemLore);

            if (isset($itemData['nbt']['tag']) && isset($itemData['nbt']['value'])) {
                $tagValue = $itemData['nbt']['value'];

                if ($item->getNamedTag() instanceof CompoundTag) {
                    $item->getNamedTag()->setString($itemData['nbt']['tag'], $tagValue);
                } else {
                    $nbt = new CompoundTag();
                    $nbt->setString($itemData['nbt']['tag'], $tagValue);
                    $item->setNamedTag($nbt);
                }
            }

            $slotRangesIndex = $index % $itemsPerPage;
            $slot = $slotRanges[0][$slotRangesIndex];

            // Set the item in the corresponding slot
            $menu->getInventory()->setItem($slot, $item);
        }
    }

}
