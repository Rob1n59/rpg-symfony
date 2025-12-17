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
               ->setHpMax(100)           // ✅ AJOUT: Initialisation de HP Max
               ->setAttack(10)
               ->setDefense(5)
               ->setGold(0)
               ->setExperience(0)
               ->setLevel(1)             // ✅ CORRECTION: Initialisation de Level
               ->setPlayerClassId(1)     // ✅ AJOUT: ID de classe par défaut (ex: 1 pour Guerrier)
               ->setPlayerClassName('Guerrier'); // ✅ AJOUT: Nom de classe

        $manager->persist($player);

        $snake = new Enemy();
        $snake->setName('Serpent des Herbes')
              ->setHp(25)
              ->setHpMax(25) 
              ->setAttack(4)
              ->setDefense(0)
              ->setGoldReward(5)
              ->setXpReward(10)
              ->setDangerLevel('Très faible') 
              ->setImage('serpent_des_herbes.png');
        $manager->persist($snake);

        $goblin = new Enemy();
        $goblin->setName('Goblin')
               ->setHp(35) 
               ->setHpMax(35) 
               ->setAttack(5)
               ->setDefense(2)
               ->setGoldReward(10)
               ->setXpReward(25) 
               ->setDangerLevel('Faible') 
               ->setImage('goblin.png');
        $manager->persist($goblin);

        $spectre = new Enemy();
        $spectre->setName('Spectre Ancien')
               ->setHp(90)
               ->setHpMax(90)
               ->setAttack(14)
               ->setDefense(6)
               ->setGoldReward(40)
               ->setXpReward(100)
               ->setDangerLevel('Élevé') 
               ->setImage('spectre_ancien.png');
        $manager->persist($spectre);

        $golem = new Enemy();
        $golem->setName('Golem de Glace')
               ->setHp(180)
               ->setHpMax(180)
               ->setAttack(22)
               ->setDefense(12)
               ->setGoldReward(80)
               ->setXpReward(200)
               ->setDangerLevel('Très Élevé') 
               ->setImage('golem_de_glace.png');
        $manager->persist($golem);

        $forest = new Location();
        $forest->setName('Forêt d’Alden') // Renommé
               ->setDescription('Un lieu mystérieux rempli de créatures.')
               ->setDangerLevel('2'); // <-- Mis à jour
        $manager->persist($forest);

        $plains = new Location();
        $plains->setName('Plaine Verdoyante')
               ->setDescription('Des champs à perte de vue, idéal pour l\'exploration initiale.')
               ->setDangerLevel('1'); // <-- Nouveau lieu
        $manager->persist($plains);
        
        $ruins = new Location();
        $ruins->setName('Ruines d’Eldamar')
               ->setDescription('D\'anciennes ruines où errent de puissants esprits.')
               ->setDangerLevel('3'); // <-- Nouveau lieu
        $manager->persist($ruins);

        $mountains = new Location();
        $mountains->setName('Montagnes du Nord')
               ->setDescription('Un col enneigé, très dangereux.')
               ->setDangerLevel('4'); // <-- Nouveau lieu
        $manager->persist($mountains);

        $dungeon = new Location();
        $dungeon->setName('Donjon sombre')
                ->setDescription('Des couloirs sombres et des monstres redoutables.')
                ->setDangerLevel(2);
        $manager->persist($dungeon);

        $sword = new Item();
        $sword->setName('Épée courte')
              ->setDescription('Une petite épée mais utile.')
              ->setAttackBonus(5)
              ->setHpBonus(0)
              ->setType('weapon')      
              ->setWeaponType('sword') 
              ->setHealingAmount(0)    
              ->setImage('sword_short.png'); 
        $manager->persist($sword);
        
        $potion = new Item();
        $potion->setName('Potion de soin')
               ->setDescription('Restaure 20 PV.')
               ->setAttackBonus(0)
               ->setHpBonus(0)
               ->setType('consumable')  
               ->setHealingAmount(20)   
               ->setImage('potion.png'); 
        $manager->persist($potion);

        $manager->flush();
    }
}