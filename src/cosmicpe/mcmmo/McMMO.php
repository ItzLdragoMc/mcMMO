<?php

declare(strict_types=1);

namespace cosmicpe\mcmmo;

use cosmicpe\mcmmo\command\McMMOCommandManager;
use cosmicpe\mcmmo\customitem\CustomItemFactory;
use cosmicpe\mcmmo\database\IDatabase;
use cosmicpe\mcmmo\database\sqlite\SQLiteDatabase;
use cosmicpe\mcmmo\integration\IntegrationManager;
use cosmicpe\mcmmo\player\PlayerManager;
use cosmicpe\mcmmo\skill\experience\ExponentialSkillExperience;
use cosmicpe\mcmmo\skill\experience\SkillExperienceManager;
use cosmicpe\mcmmo\skill\SkillManager;
use InvalidArgumentException;
use pocketmine\plugin\PluginBase;

final class McMMO extends PluginBase{

	private static ?McMMO $instance;

	public static function getInstance() : McMMO{
		return self::$instance;
	}

	private IDatabase $database;
	private PlayerManager $player_manager;
	private ?IntegrationManager $integration_manager = null;

	protected function onLoad() : void{
		self::$instance = $this;
		$this->player_manager = new PlayerManager();
		CustomItemFactory::load($this);
		SkillManager::load($this);
	}

	protected function onEnable() : void{
		$this->getIntegrationManager()->init();
		$this->parseExperienceFormula();

		$this->database = new SQLiteDatabase($this);
		$this->player_manager->init($this, $this->database);

		CustomItemFactory::init($this);
		SkillManager::init($this);
		McMMOCommandManager::init($this);
	}

	protected function onDisable() : void{
		$this->player_manager->destroy();
		$this->database->close();
		self::$instance = null;
	}

	private function parseExperienceFormula() : void{
		$config = $this->getConfig()->get("experience");
		$type = strtolower($config["type"]);
		switch($type){
			case "exponential":
				$args = $config[$type];
				SkillExperienceManager::set(new ExponentialSkillExperience($args["base"], $args["multiplier"], $args["exponent"]));
				break;
			default:
				throw new InvalidArgumentException("Invalid experience type \"{$config["type"]}\" in config.yml");
		}
	}

	public function getPlayerManager() : PlayerManager{
		return $this->player_manager;
	}

	public function getIntegrationManager() : IntegrationManager{
		return $this->integration_manager ??= new IntegrationManager($this);
	}
}
