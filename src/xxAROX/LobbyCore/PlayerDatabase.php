<?php
/*
 * Copyright (c) 2021 Jan Sohn.
 * All rights reserved.
 * I don't want anyone to use my source code without permission.
 */

declare(strict_types=1);
namespace xxAROX\LobbyCore;
use pocketmine\Player;
use xxAROX\Utils\SQLite3Database;


/**
 * Class PlayerDatabase
 * @package xxAROX\Core\utils\database
 * @author Jan Sohn / xxAROX - <jansohn@hurensohn.me>
 * @date 04. Mai, 2021 - 23:43
 * @ide PhpStorm
 * @project Core
 */
class PlayerDatabase{
	protected static SQLite3Database $database;

	static function init(): void{
		self::$database = new SQLite3Database("/home/.data/playerData.db");

		if (!self::$database->isTable("registeredPlayers")) {
			self::$database->createTable("registeredPlayers", "`uuid` VARCHAR(32),`name` VARCHAR(32),`online` VARCHAR");
		}
	}

	static function loadPlayer(Player $player): void{
		if (self::$database->getMedoo()->get("registeredPlayers", "uuid", ["uuid" => $player->getUniqueId()->toString()]) == null) {
			self::$database->getMedoo()->insert("registeredPlayers", ["uuid" => $player->getUniqueId()->toString(),"name" => $player->getName(), "online" => "Lobby"]);
		} else {
			self::$database->getMedoo()->update("registeredPlayers", ["uuid" => $player->getUniqueId()->toString(),"name" => $player->getName(), "online" => "Lobby"], ["uuid" => $player->getUniqueId()->toString()]);
		}
	}

	static function savePlayer(Player $player): void{
		self::$database->getMedoo()->update("registeredPlayers", ["online" => ""], ["uuid" => $player->getUniqueId()->toString()]);
	}
}
