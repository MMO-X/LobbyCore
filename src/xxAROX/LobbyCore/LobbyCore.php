<?php
declare(strict_types=1);
namespace xxAROX\LobbyCore;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\block\Block;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;


/**
 * Class LobbyCore
 * @package xxAROX\LobbyCore
 * @author Jan Sohn / xxAROX - <jansohn@hurensohn.me>
 * @date 18. Februar, 2021 - 19:12
 * @ide PhpStorm
 * @project LobbyCore
 */
class LobbyCore extends PluginBase implements Listener{
	public bool $onlyForTesters = true;
	protected Item $item;
	public function onEnable(){
		PlayerDatabase::init();
		$this->onlyForTesters = (new Config($this->getDataFolder() . "config.yml", Config::YAML, ["onlyForTesters" => false]))->get("onlyForTesters", false);
		$this->item = new class extends Item{
			/**
			 * Anonymous constructor.
			 */
			public function __construct(){
				parent::__construct(Item::BEETROOT_SEEDS, 0, "Teleporter");
				$this->setCustomName("§r§9Slot selector");
			}
			/**
			 * Function getCooldownTicks
			 * @return int
			 */
			public function getCooldownTicks(): int{
				return 20;
			}
			/**
			 * Function onClickAir
			 * @param Player $player
			 * @param Vector3 $directionVector
			 * @return bool
			 */
			public function onClickAir(Player $player, Vector3 $directionVector): bool{
				if (!$player->hasItemCooldown($this)) {
					$player->resetItemCooldown($this);
					$this->use($player);
					$player->resetItemCooldown($this);
				}
				return parent::onClickAir($player, $directionVector);
			}
			/**
			 * Function onActivate
			 * @param Player $player
			 * @param Block $blockReplace
			 * @param Block $blockClicked
			 * @param int $face
			 * @param Vector3 $clickVector
			 * @return bool
			 */
			public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool{
				if (!$player->hasItemCooldown($this)) {
					$player->resetItemCooldown($this);
					$this->use($player);
				}
				return parent::onActivate($player, $blockReplace, $blockClicked, $face, $clickVector);
			}
			/**
			 * Function use
			 * @param Player $player
			 * @return void
			 */
			public function use(Player $player): void{
				if (!$player->hasPermission("xxarox.testServer")) {
					$player->sendForm(new MenuForm("§3In development", "§cError, only§5 Testers§c can play right now!"));
					return;
				}
				if (!is_file("/home/.SLOTS")) {
					$player->sendForm(new MenuForm("Select slot", "§cError, no slots found. §lPlease contact an Moderator"));
				} else {
					$arr = $buttons = ["§cClose"];
					$servers = new Config("/home/.SLOTS", Config::JSON);
					if (count($servers->getAll()) == 0) {
						$player->sendForm(new MenuForm("Select slot", "§cError, no slots found. §lPlease contact an Moderator"));
						return;
					}
					foreach ($servers->getAll() as $name => $data) {
						$data = explode(":", $data);
						$arr[$buttons[] = "§b" . $name] = function (Player $player, string $id) use ($data): void{
							$player->transfer($data[0], (int) $data[1]);
							Server::getInstance()->broadcastMessage("§l§d» §e{$player->getName()} §7is playing on §9Slot-{$id}");
						};
					}
					$player->sendForm(new MenuForm(
						"Select slot",
						"Select to load slot.",
						$buttons,
						function (Player $player, Button $button) use ($arr): void{
							if ($button->getValue() !== 0) {
								call_user_func($arr[$button->getText()], $player, "{$button->getValue()}");
							}
						}
					));
				}
			}
		};
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		foreach ($this->getServer()->getCommandMap()->getCommands() as $command) {
			if ($command instanceof VanillaCommand) {
				$this->getServer()->getCommandMap()->unregister($command);
			}
		}
	}
	public function f1(BlockUpdateEvent $event): void{
		$event->setCancelled(true);
	}
	public function f2(BlockGrowEvent $event): void{
		$event->setCancelled(true);
	}
	public function f3(BlockFormEvent $event): void{
		$event->setCancelled(true);
	}
	public function f4(BlockSpreadEvent $event): void{
		$event->setCancelled(true);
	}
	public function f5(PlayerJoinEvent $event): void{
		$event->getPlayer()->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
		$event->getPlayer()->getInventory()->clearAll();
		$event->getPlayer()->getInventory()->setItemInHand($this->item);
	}
	public function f6(InventoryPickupItemEvent $event): void{
		$event->setCancelled(true);
	}
	public function f7(PlayerDropItemEvent $event): void{
		$event->setCancelled(true);
	}
	public function f8(EntityDamageEvent $event): void{
		$event->setCancelled(true);
	}
	public function f9(PlayerItemHeldEvent $event): void{
		$event->getPlayer()->getInventory()->clearAll();
		$event->getPlayer()->getInventory()->setItem($event->getSlot(), $this->item, true);
	}
	public function f10(InventoryTransactionEvent $event): void{
		$event->setCancelled(true);
	}
	public function f11(PlayerExhaustEvent $event): void{
		$event->getPlayer()->setFood($event->getPlayer()->getMaxFood());
	}
	public function f12(PlayerMoveEvent $event): void{
		$radius = 12;
		if ($event->getPlayer()->distanceSquared($this->getServer()->getDefaultLevel()->getSafeSpawn()) > $radius) {
			$event->getPlayer()->teleport($event->getFrom());
			$event->getPlayer()->sendMessage("§c§l» §r§7You cannot leave the spawn area.");
			if ($event->getPlayer()->distanceSquared($this->getServer()->getDefaultLevel()->getSafeSpawn()) > $radius *2) {
				$event->getPlayer()->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
			}
		}
	}

	public function join(PlayerJoinEvent $event): void{
		PlayerDatabase::loadPlayer($event->getPlayer());
	}

	public function quit(PlayerQuitEvent $event): void{
		PlayerDatabase::savePlayer($event->getPlayer());
	}
}
