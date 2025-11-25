<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Location;
use App\Repository\EnemyRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/', name: 'game_menu')]
    public function menu(SessionInterface $session): Response
    {
        return $this->render('game/menu.html.twig');
    }

    #[Route('/start', name: 'game_start')]
    public function start(SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Crée un joueur seulement si pas déjà créé
        if (!$session->has('player_id')) {
            $player = new Player();
            $player->setName("Héros")
                   ->setHp(100)        // ⚡ Initialisation obligatoire
                   ->setAttack(10)     // ⚡ Initialisation obligatoire
                   ->setDefense(5)     // ⚡ Initialisation obligatoire
                   ->setGold(0)        // ⚡ Initialisation obligatoire
                   ->setExperience(0); // ⚡ Initialisation obligatoire

            $em->persist($player);
            $em->flush();

            $session->set('player_id', $player->getId());
        }

        return $this->redirectToRoute('game_explore');
    }

    #[Route('/explore', name: 'game_explore')]
    public function explore(
        SessionInterface $session,
        LocationRepository $locationRepository
    ): Response {
        $locations = $locationRepository->findAll();
        $location = $locations[array_rand($locations)];

        return $this->render('game/explore.html.twig', [
            'location' => $location
        ]);
    }

    #[Route('/explore/encounter', name: 'game_encounter')]
    public function encounter(
        EnemyRepository $enemyRepo,
        SessionInterface $session
    ): Response {
        // Choix d’un ennemi aléatoire
        $enemies = $enemyRepo->findAll();
        $enemy = $enemies[array_rand($enemies)];

        $session->set('enemy_id', $enemy->getId());

        return $this->redirectToRoute('combat_start');
    }
}