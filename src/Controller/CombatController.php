<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Enemy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CombatController extends AbstractController
{
    #[Route('/combat', name: 'combat_start')]
    public function combat(
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        $enemy = $em->getRepository(Enemy::class)->find($session->get('enemy_id'));

        return $this->render('game/combat.html.twig', [
            'player' => $player,
            'enemy' => $enemy
        ]);
    }

    #[Route('/combat/attack', name: 'combat_attack')]
    public function attack(
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        $enemy = $em->getRepository(Enemy::class)->find($session->get('enemy_id'));
        
        if (!$player || !$enemy || $player->getHp() <= 0 || $enemy->getHp() <= 0) {
            // Gérer le cas où le combat ne devrait pas avoir lieu
            return $this->redirectToRoute('game_explore'); // Rediriger vers la carte si les entités ne sont pas valides
        }

        // Utilisation des statistiques TOTALES du joueur (Base + Équipement)
        $playerTotalAttack = $player->calculateTotalAttack();
        $playerTotalDefense = $player->calculateTotalDefense();

        // 1. Calcul des dégâts infligés à l'ennemi (Attaque Totale du joueur vs Défense de l'ennemi)
        // Dégâts minimum garantis de 1
        $enemyDamage = max(1, $playerTotalAttack - $enemy->getDefense());

        // 2. Calcul des dégâts infligés au joueur (Attaque de l'ennemi vs Défense Totale du joueur)
        // Dégâts minimum garantis de 1
        $playerDamage = max(1, $enemy->getAttack() - $playerTotalDefense);

        // --- Déroulement du Tour ---

        // Le joueur attaque
        $enemy->setHp($enemy->getHp() - $enemyDamage);

        // 3. Ennemi vaincu ?
        if ($enemy->getHp() <= 0) {
            $player->setExperience($player->getExperience() + $enemy->getXpReward());
            $player->setGold($player->getGold() + $enemy->getGoldReward());
            
            // TODO: Ajouter la logique de montée de niveau si l'XP est suffisant ici
            
            $em->flush();
            $session->remove('enemy_id'); // L'ennemi n'existe plus pour la session
            
            return $this->render('game/result.html.twig', [
                'result' => 'victory',
                'enemy' => $enemy,
                'player' => $player,
                'damageDealt' => $enemyDamage // Optionnel : pour afficher dans la vue de victoire
            ]);
        }

        // L’ennemi attaque (seulement si l'ennemi n'est pas mort)
        $player->setHp($player->getHp() - $playerDamage);

        // 4. Joueur vaincu ?
        if ($player->getHp() <= 0) {
            // S'assurer que les HP ne sont pas négatifs dans la BDD
            $player->setHp(0); 
            $em->flush();
            $session->remove('enemy_id'); // Fin du combat
            
            return $this->render('game/result.html.twig', [
                'result' => 'defeat',
                'enemy' => $enemy,
                'player' => $player,
                'damageTaken' => $playerDamage // Optionnel : pour afficher dans la vue de défaite
            ]);
        }

        $em->flush();

        // Retourne à la vue de combat pour le tour suivant
        return $this->render('game/combat.html.twig', [
            'player' => $player,
            'enemy' => $enemy
        ]);
    }
}