<?php

namespace App\Controller;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProgressionController extends AbstractController
{
    // Points d'Augure à attribuer par dépense de point
    private const AUGUR_VALUE = [
        'attack' => 3, 
        'defense' => 3, 
        'hpmax' => 20 
    ];

    /**
     * Affiche l'écran de montée de niveau pour dépenser les points d'Augure.
     */
    #[Route('/progression/level-up', name: 'progression_level_up')]
    public function levelUp(
        SessionInterface $session, 
        EntityManagerInterface $em
    ): Response {
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            return $this->redirectToRoute('choose_hero');
        }
        
        // Si le joueur n'a pas de points à dépenser, le renvoyer à l'exploration
        if ($player->getAugurPoints() <= 0) {
            $this->addFlash('info', 'Vous n\'avez pas de Points d\'Augure à dépenser.');
            return $this->redirectToRoute('game_explore');
        }

        return $this->render('game/level_up.html.twig', [
            'player' => $player,
            'augurValues' => self::AUGUR_VALUE,
        ]);
    }

    /**
     * Traite le choix de l'utilisateur pour dépenser un Point d'Augure.
     */
    #[Route('/progression/apply-stat/{statName}', name: 'progression_apply_stat', methods: ['POST'])]
    public function applyStat(
        string $statName,
        Request $request,
        SessionInterface $session, 
        EntityManagerInterface $em
    ): Response {
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player || $player->getAugurPoints() <= 0) {
            $this->addFlash('error', 'Action invalide ou points d\'Augure insuffisants.');
            return $this->redirectToRoute('progression_level_up');
        }

        $statKey = strtolower($statName);

        if (!isset(self::AUGUR_VALUE[$statKey])) {
            $this->addFlash('error', 'Caractéristique invalide.');
            return $this->redirectToRoute('progression_level_up');
        }

        $value = self::AUGUR_VALUE[$statKey];

        // 1. Appliquer l'augmentation de la stat de base
        $player->increaseStat($statKey, $value);

        // 2. Déduire le point d'Augure
        $player->setAugurPoints($player->getAugurPoints() - 1);
        
        // 3. Persister les changements
        $em->flush();

        $this->addFlash('success', "Votre $statName a augmenté de $value ! Points restants : " . $player->getAugurPoints());

        // Si le joueur a encore des points à dépenser, le renvoyer à la même page
        if ($player->getAugurPoints() > 0) {
            return $this->redirectToRoute('progression_level_up');
        }

        // Sinon, le renvoyer à l'exploration
        return $this->redirectToRoute('game_explore');
    }
}