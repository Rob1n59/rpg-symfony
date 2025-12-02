<?php

namespace App\DataFixtures;

use App\Entity\Item;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ItemFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
       // 1. Melee Weapons (Warrior)
        $sword = new Item();
        $sword->setName('Épée en Fer');
        $sword->setDescription('Une simple épée en fer, efficace pour le combat.');
        $sword->setType('weapon');
        $sword->setWeaponType('sword');
        $sword->setImage('sword.png'); // Matches your file name
        $sword->setAttackBonus(10);
        $sword->setHpBonus(0);
        $manager->persist($sword);
        $this->addReference('item-sword', $sword); // Use reference for later use

        $axe = new Item();
        $axe->setName('Hache de Bois');
        $axe->setDescription('Une hache rudimentaire, lourde mais puissante.');
        $axe->setType('weapon');
        $axe->setWeaponType('axe');
        $axe->setImage('axe.png');
        $axe->setAttackBonus(12);
        $axe->setHpBonus(0);
        $manager->persist($axe);
        $this->addReference('item-axe', $axe);

        // 2. Ranged Weapons (Archer)
        $bow = new Item();
        $bow->setName('Arc Court');
        $bow->setDescription('Un arc léger, idéal pour les archers débutants.');
        $bow->setType('weapon');
        $bow->setWeaponType('bow');
        $bow->setImage('bow.png');
        $bow->setAttackBonus(8);
        $bow->setHpBonus(0);
        $manager->persist($bow);
        $this->addReference('item-bow', $bow);

        $crossbow = new Item();
        $crossbow->setName('Arbalète');
        $crossbow->setDescription('Une arme à distance puissante mais lente.');
        $crossbow->setType('weapon');
        $crossbow->setWeaponType('crossbow');
        $crossbow->setImage('crossbow.png');
        $crossbow->setAttackBonus(15);
        $crossbow->setHpBonus(0);
        $manager->persist($crossbow);
        $this->addReference('item-crossbow', $crossbow);

        // 3. Magic Weapons (Mage)
        $staff = new Item();
        $staff->setName('Bâton de Saule');
        $staff->setDescription('Un bâton simple qui amplifie les sorts de base.');
        $staff->setType('weapon');
        $staff->setWeaponType('staff');
        $staff->setImage('staff.png');
        $staff->setAttackBonus(18); // Plus d'attaque car moins de défense
        $staff->setHpBonus(0);
        $manager->persist($staff);
        $this->addReference('item-staff', $staff);

        // 4. Consumable (Example Potion)
        $potion = new Item();
        $potion->setName('Petite Potion de Vie');
        $potion->setDescription('Restaure un peu de santé.');
        $potion->setType('consumable');
        $potion->setWeaponType(null);
        $potion->setImage('potion.png'); // Assurez-vous d'avoir ce PNG
        $potion->setHealingAmount(30);
        $manager->persist($potion);
        $this->addReference('item-potion', $potion);


        $manager->flush();
    }
}
