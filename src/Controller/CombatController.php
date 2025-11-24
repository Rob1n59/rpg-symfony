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

        // dégâts simples (à améliorer plus tard)
        $enemyDamage = max(1, $player->getAttack() - $enemy->getDefense());
        $playerDamage = max(1, $enemy->getAttack() - $player->getDefense());

        // joueur attaque
        $enemy->setHp($enemy->getHp() - $enemyDamage);

        // ennemi mort ?
        if ($enemy->getHp() <= 0) {
            $player->setExperience($player->getExperience() + $enemy->getXpReward());
            $player->setGold($player->getGold() + $enemy->getGoldReward());

            $em->flush();
            return $this->render('game/result.html.twig', [
                'result' => 'victory',
                'enemy' => $enemy,
                'player' => $player
            ]);
        }

        // l’ennemi attaque
        $player->setHp($player->getHp() - $playerDamage);

        // joueur mort ?
        if ($player->getHp() <= 0) {
            $em->flush();
            return $this->render('game/result.html.twig', [
                'result' => 'defeat',
                'enemy' => $enemy,
                'player' => $player
            ]);
        }

        $em->flush();

        return $this->render('game/combat.html.twig', [
            'player' => $player,
            'enemy' => $enemy
        ]);
    }
}
