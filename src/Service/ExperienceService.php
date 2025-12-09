<?php

namespace App\Service; // <-- Namespace correspondant à l'import dans CombatController

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

class ExperienceService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Calcule l'XP requise pour atteindre un niveau donné.
     * Courbe d'XP simple : XP requise = 100 * (Niveau * (Niveau + 1) / 2)
     * @param int $level Le niveau cible.
     * @return int L'XP totale requise pour ce niveau.
     */
    public function getRequiredExpForNextLevel(int $level): int
    {
        // Utilise une formule de progression standard (somme arithmétique)
        if ($level < 1) {
            return 100;
        }
        return 100 * $level * ($level + 1) / 2;
    }

    /**
     * Tente de faire monter le joueur de niveau si son XP le permet.
     *
     * @param Player $player Le joueur à vérifier.
     * @return array Un tableau contenant le statut de montée de niveau et le niveau atteint.
     */
    public function checkLevelUp(Player $player): array
    {
        $oldLevel = $player->getLevel();
        $leveledUp = false;

        // Boucle pour gérer les montées de niveau multiples si l'XP est très élevé
        // La condition vérifie si l'XP totale du joueur atteint ou dépasse l'XP TOTALE requise pour le niveau suivant
        while ($player->getExperience() >= $this->getRequiredExpForNextLevel($player->getLevel())) {
            $this->levelUp($player);
            $leveledUp = true;
        }

        return [
            'leveledUp' => $leveledUp,
            'oldLevel' => $oldLevel,
            'newLevel' => $player->getLevel()
        ];
    }
    
    /**
     * Applique la montée de niveau au joueur et ajuste ses statistiques.
     */
    private function levelUp(Player $player): void
    {
        $player->setLevel($player->getLevel() + 1);
        
        // --- LOGIQUE D'AUGMENTATION DES STATS ---
        
        // Augmentation des PV Max
        $newHpMax = $player->getHpMax() + 10;
        $player->setHpMax($newHpMax);
        
        // Rétablir les PV du joueur au nouveau maximum
        $player->setHp($newHpMax); 
        
        // Augmentation des stats de base ATK/DEF
        $player->setAttack($player->getAttack() + 1);
        $player->setDefense($player->getDefense() + 1);
        
        // Le flush sera géré par le contrôleur appelant (CombatController)
    }
}