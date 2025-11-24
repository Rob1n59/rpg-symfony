<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\Enemy;
use App\Entity\Location;
use App\Entity\Item;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- Joueur de test ---
        $player = new Player();
        $player->setName('Hero')
               ->setHp(100)
               ->setAttack(10)
               ->setDefense(5)
               ->setGold(0)
               ->setExperience(0);

        $manager->persist($player);

        // --- Ennemis ---
        $goblin = new Enemy();
        $goblin->setName('Goblin')
               ->setHp(30)
               ->setAttack(5)
               ->setDefense(2)
               ->setGoldReward(10)
               ->setXpReward(5);
        $manager->persist($goblin);

        $dragon = new Enemy();
        $dragon->setName('Dragon')
               ->setHp(100)
               ->setAttack(20)
               ->setDefense(10)
               ->setGoldReward(100)
               ->setXpReward(50);
        $manager->persist($dragon);

        // --- Lieux ---
        $forest = new Location();
        $forest->setName('Forêt enchantée')
               ->setDescription('Un lieu mystérieux rempli de créatures.')
               ->setDangerLevel(1);
        $manager->persist($forest);

        $dungeon = new Location();
        $dungeon->setName('Donjon sombre')
                ->setDescription('Des couloirs sombres et des monstres redoutables.')
                ->setDangerLevel(2);
        $manager->persist($dungeon);

        // --- Items ---
        $sword = new Item();
        $sword->setName('Épée courte')
              ->setDescription('Une petite épée mais utile.')
              ->setAttackBonus(5)
              ->setHpBonus(0); // ⚡ Important : toutes les colonnes non nullables doivent avoir une valeur
        $manager->persist($sword);

        $potion = new Item();
        $potion->setName('Potion de soin')
               ->setDescription('Restaure 20 PV.')
               ->setAttackBonus(0) // ⚡ Initialisé pour éviter NOT NULL
               ->setHpBonus(20);
        $manager->persist($potion);

        // Envoi en base
        $manager->flush();
    }
}