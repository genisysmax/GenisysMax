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

namespace pocketmine\tile;

use pocketmine\BedrockPlayer;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\bedrock\utils\BedrockUtils;
use pocketmine\Player;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;

class Sign extends Spawnable
{

    public const TAG_TEXT_BLOB = "Text";
    public const TAG_TEXT_LINE = "Text%d"; //sprintf()able
    public const TAG_TEXT_COLOR = "SignTextColor";
    public const TAG_GLOWING_TEXT = "IgnoreLighting";
    public const TAG_PERSIST_FORMATTING = "PersistFormatting"; //TAG_Byte
    /**
     * This tag is set to indicate that MCPE-117835 has been addressed in whatever version this sign was created.
     * @see https://bugs.mojang.com/browse/MCPE-117835
     */
    public const TAG_LEGACY_BUG_RESOLVE = "TextIgnoreLegacyBugResolved";

    public const TAG_FRONT_TEXT = "FrontText"; //TAG_Compound
    public const TAG_BACK_TEXT = "BackText"; //TAG_Compound
    public const TAG_WAXED = "IsWaxed"; //TAG_Byte
    public const TAG_LOCKED_FOR_EDITING_BY = "LockedForEditingBy"; //TAG_Long

    public string $text1 = "";
    public string $text2 = "";
    public string $text3 = "";
    public string $text4 = "";

    /** @var Color  */
    protected Color $textColor;

    protected function readSaveData(CompoundTag $nbt) : void{
        $baseColor = new Color(0, 0, 0);
        if(($baseColorTag = $nbt->getTag(self::TAG_TEXT_COLOR)) instanceof IntTag){
            $baseColor = Color::fromARGB(Binary::unsignInt($baseColorTag->getValue()));
        }
        $this->textColor = $baseColor;

        $this->text1 = $nbt->getString("Text1", "");
        $this->text2 = $nbt->getString("Text2", "");
        $this->text3 = $nbt->getString("Text3", "");
        $this->text4 = $nbt->getString("Text4", "");
    }

    protected function writeSaveData(CompoundTag $nbt) : void
    {
        $nbt->setInt(self::TAG_TEXT_COLOR, Binary::signInt($this->textColor->toARGB()));
        $nbt->setString("Text1", $this->text1);
        $nbt->setString("Text2", $this->text2);
        $nbt->setString("Text3", $this->text3);
        $nbt->setString("Text4", $this->text4);
    }

    public function setText($line1 = "", $line2 = "", $line3 = "", $line4 = ""):void{
        $this->text1 = $line1;
        $this->text2 = $line2;
        $this->text3 = $line3;
        $this->text4 = $line4;
        $this->onChanged();
    }

    public function getText() :array{
        $text1 = $this->text1;
        $text2 = $this->text2;
        $text3 = $this->text3;
        $text4 = $this->text4;

        if ($text4 !== "") {
            return [$text1, $text2, $text3, $text4];
        } elseif ($text3 !== "") {
            return [$text1, $text2, $text3];
        } elseif ($text2 !== "") {
            return [$text1, $text2];
        } elseif ($text1 !== "") {
            return [$text1];
        }

        return [];
    }

    public function getTextColor() :Color{
        return $this->textColor;
    }

    public function setTextColor(Color $color) :void{
        $this->textColor = $color;
        $this->onChanged();
    }

    public function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        if($isBedrock){
            $nbt->setTag(self::TAG_FRONT_TEXT, CompoundTag::create()
                ->setString(self::TAG_TEXT_BLOB, BedrockUtils::convertSignLinesToText($this->getText()))
                ->setInt(self::TAG_TEXT_COLOR, Binary::signInt($this->textColor->toARGB()))
                ->setByte(self::TAG_GLOWING_TEXT, 0)
                ->setByte(self::TAG_PERSIST_FORMATTING, 1) //TODO: not sure what this is used for
            );
            //TODO: this is not yet used by the server, but needed to rollback any client-side changes to the back text
            $nbt->setTag(self::TAG_BACK_TEXT, CompoundTag::create()
                ->setString(self::TAG_TEXT_BLOB, "")
                ->setInt(self::TAG_TEXT_COLOR, Binary::signInt(0xff_00_00_00))
                ->setByte(self::TAG_GLOWING_TEXT, 0)
                ->setByte(self::TAG_PERSIST_FORMATTING, 1)
            );
            $nbt->setByte(self::TAG_WAXED, 0);
            $nbt->setLong(self::TAG_LOCKED_FOR_EDITING_BY, $this->editorEntityRuntimeId ?? -1);
        }else{
            $nbt->setString("Text1", $this->text1);
            $nbt->setString("Text2", $this->text2);
            $nbt->setString("Text3", $this->text3);
            $nbt->setString("Text4", $this->text4);
        }
    }

    public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
        if($nbt->getString("id") !== Tile::SIGN){
            return false;
        }

        $removeFormat = $player->getRemoveFormat();
        if($player instanceof BedrockPlayer){
            if($nbt->hasTag(self::TAG_TEXT_BLOB, StringTag::class)) {
                $lines = BedrockUtils::convertSignTextToLines(TextFormat::clean($nbt->getString("Text"), $removeFormat));
            }else{
                $frontTextTag = $nbt->getTag(Sign::TAG_FRONT_TEXT);
                $textBlobTag = $frontTextTag->getTag(self::TAG_TEXT_BLOB);
                $lines = BedrockUtils::convertSignTextToLines($textBlobTag->getValue());
            }
        }else{
            $lines = [
                TextFormat::clean($nbt->getString("Text1"), $removeFormat),
                TextFormat::clean($nbt->getString("Text2"), $removeFormat),
                TextFormat::clean($nbt->getString("Text3"), $removeFormat),
                TextFormat::clean($nbt->getString("Text4"), $removeFormat)
            ];
        }
        $ev = new SignChangeEvent($this->getBlock(), $player, $lines);
        $ev->call();

        if(!$ev->isCancelled()){
            $this->setText(...$ev->getLines());
            return true;
        }else{
            return false;
        }
    }
}

