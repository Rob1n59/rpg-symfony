<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\PlayerItem;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PlayerItemRepository;
use Doctrine\Common\Collections\Collection;

class InventoryService
{
    private EntityManagerInterface $em;
    private PlayerItemRepository $playerItemRepository;

    public function __construct(EntityManagerInterface $em, PlayerItemRepository $playerItemRepository)
    {
        $this->em = $em;
        $this->playerItemRepository = $playerItemRepository;
    }

    /**
     * Gère l'équipement ou le déséquipement d'un PlayerItem.
     * Cette méthode garantit qu'un seul objet du même type (e.g., 'weapon') est équipé à la fois.
     */
    public function toggleEquipItem(Player $player, PlayerItem $playerItemToToggle): void
    {
        $item = $playerItemToToggle->getItem();
        $itemType = $item->getType(); // Ex: 'weapon', 'armor', etc.

        if ($itemType === 'consumable') {
            throw new \LogicException('Les consommables ne peuvent pas être équipés.');
        }

        $isCurrentlyEquipped = $playerItemToToggle->isIsEquipped();

        if ($isCurrentlyEquipped) {
            // Action : Déséquiper
            $playerItemToToggle->setIsEquipped(false);
            $this->em->flush();
            $this->recalculatePlayerStats($player);
            return;
        }

        // Action : Équiper
        
        // 1. Déséquiper TOUS les autres PlayerItems du même type d'équipement
        $equippedItems = $player->getPlayerItems()->filter(function(PlayerItem $pi) use ($itemType) {
            return $pi->isIsEquipped() && $pi->getItem()->getType() === $itemType;
        });

        /** @var PlayerItem $oldEquippedItem */
        foreach ($equippedItems as $oldEquippedItem) {
            $oldEquippedItem->setIsEquipped(false);
        }

        // 2. Équiper le nouvel objet
        $playerItemToToggle->setIsEquipped(true);

        $this->em->flush();

        // 3. Recalculer les stats globales après le changement
        $this->recalculatePlayerStats($player);
    }
    
    /**
     * Recalcule les bonus ATK/DEF totaux du joueur à partir de TOUS ses PlayerItems équipés
     * et met à jour les champs de l'entité Player.
     */
    private function recalculatePlayerStats(Player $player): void
    {
        $totalAtkBonus = 0;
        $totalDefBonus = 0;

        /** @var PlayerItem $playerItem */
        foreach ($player->getPlayerItems() as $playerItem) {
            if ($playerItem->isIsEquipped()) {
                $item = $playerItem->getItem();
                
                // On additionne les bonus de l'objet Item
                $totalAtkBonus += $item->getAttackBonus() ?? 0;
                
                // NOTE IMPORTANTE : Votre entité Item.php n'a pas de DefenseBonus.
                // Si vous voulez que la défense soit affectée, vous devez ajouter le champ DefenseBonus 
                // à Item.php et son getter/setter.
                // Pour l'instant, j'utilise une valeur par défaut de 0.
                // $totalDefBonus += $item->getDefenseBonus() ?? 0; 
            }
        }

        // Met à jour et persiste les bonus totaux sur l'entité Player
        $player->setEquippedAttackBonus($totalAtkBonus);
        $player->setEquippedDefenseBonus($totalDefBonus);
        
        $this->em->flush();
    }
}