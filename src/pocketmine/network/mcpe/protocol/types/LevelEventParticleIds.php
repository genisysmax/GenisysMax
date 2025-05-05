<?php

/*
 *
 *    _____            _               __  __            
 *   / ____|          (_)             |  \/  |           
 *  | |  __  ___ _ __  _ ___ _   _ ___| \  / | __ ___  __
 *  | | |_ |/ _ \ '_ \| / __| | | / __| |\/| |/ _` \ \/ /
 *  | |__| |  __/ | | | \__ \ |_| \__ \ |  | | (_| |>  < 
 *   \_____|\___|_| |_|_|___/\__, |___/_|  |_|\__,_/_/\_\
 *                            __/ |                      
 *                           |___/                       
 *
 * This program is licensed under the GPLv3 license.
 * You are free to modify and redistribute it under the same license.
 *
 * @author LINUXOV
 * @link vk.com/linuxof
 *
*/



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

interface LevelEventParticleIds{

	public const BUBBLE = 1;
	public const CRITICAL = 2;
	public const BLOCK_FORCE_FIELD = 3;
	public const SMOKE = 4;
	public const EXPLODE = 5;
	public const EVAPORATION = 6;
	public const FLAME = 7;
	public const LAVA = 8;
	public const LARGE_SMOKE = 9;
	public const REDSTONE = 10;
	public const RISING_RED_DUST = 11;
	public const ITEM_BREAK = 12;
	public const SNOWBALL_POOF = 13;
	public const HUGE_EXPLODE = 14;
	public const HUGE_EXPLODE_SEED = 15;
	public const MOB_FLAME = 16;
	public const HEART = 17;
	public const TERRAIN = 18;
	public const SUSPENDED_TOWN = 19, TOWN_AURA = 19;
	public const PORTAL = 20;
	public const SPLASH = 21, WATER_SPLASH = 21;
	public const WATER_WAKE = 22;
	public const DRIP_WATER = 23;
	public const DRIP_LAVA = 24;
	public const FALLING_DUST = 25, DUST = 25;
	public const MOB_SPELL = 26;
	public const MOB_SPELL_AMBIENT = 27;
	public const MOB_SPELL_INSTANTANEOUS = 28;
	public const INK = 29;
	public const SLIME = 30;
	public const RAIN_SPLASH = 31;
	public const VILLAGER_ANGRY = 32;
	public const VILLAGER_HAPPY = 33;
	public const ENCHANTMENT_TABLE = 34;
	public const TRACKING_EMITTER = 35;
	public const NOTE = 36;
	public const WITCH_SPELL = 37;
	public const CARROT = 38;
	//39 unknown
	public const END_ROD = 40;
	public const DRAGONS_BREATH = 41;

}

