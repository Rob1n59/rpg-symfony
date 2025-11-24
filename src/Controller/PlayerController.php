<?php

namespace App\Controller;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends AbstractController
{
    #[Route('/player/{id}', name: 'player_show')]
    public function show(Player $player): Response
    {
        return $this->render('game/player.html.twig', [
            'player' => $player
        ]);
    }

    #[Route('/player/reset/{id}', name: 'player_reset')]
    public function reset(Player $player, EntityManagerInterface $em): Response
    {
        $player->setHp(100);
        $player->setGold(0);
        $player->setExperience(0);
        $em->flush();

        return $this->redirectToRoute('game_menu');
    }
}
