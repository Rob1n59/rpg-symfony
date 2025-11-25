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
use Symfony\Component\HttpFoundation\Request;

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
    #[Route('/choose-hero', name: 'choose_hero')]
public function chooseHero(): Response
{
    // Liste des classes disponibles
    $classes = [
        [
            'id' => 1,
            'name' => 'Guerrier',
            'image' => '/images/classes/warrior.png',
            'hp' => 120,
            'attack' => 8,
            'defense' => 12
        ],
        [
            'id' => 2,
            'name' => 'Mage',
            'image' => '/images/classes/mage.png',
            'hp' => 80,
            'attack' => 15,
            'defense' => 5
        ],
        [
            'id' => 3,
            'name' => 'Archer',
            'image' => '/images/classes/archer.png',
            'hp' => 100,
            'attack' => 10,
            'defense' => 8
        ]
    ];

    return $this->render('game/choose_hero.html.twig', [
        'classes' => $classes
    ]);
}
    #[Route('/choose-hero/confirm', name: 'choose_hero_confirm', methods: ['POST'])]
public function chooseHeroConfirm(
    Request $request,
    EntityManagerInterface $em,
    SessionInterface $session
): Response {
    $classId = $request->request->get('class_id');
    $playerName = $request->request->get('player_name');

    if (!$classId || !$playerName) {
        $this->addFlash('error', 'Veuillez choisir une classe et un nom.');
        return $this->redirectToRoute('choose_hero');
    }

    // Données des classes (on pourrait mettre ça ailleurs)
    $classes = [
        1 => ['hp' => 120, 'attack' => 8, 'defense' => 12],
        2 => ['hp' => 80, 'attack' => 15, 'defense' => 5],
        3 => ['hp' => 100, 'attack' => 10, 'defense' => 8]
    ];

    $stats = $classes[$classId];

    // Création du joueur
    $player = new Player();
    $player->setName($playerName);
    $player->setHp($stats['hp']);
    $player->setAttack($stats['attack']);
    $player->setDefense($stats['defense']);
    $player->setGold(0);
    $player->setExperience(0);

    $em->persist($player);
    $em->flush();

    $session->set('player_id', $player->getId());

    return $this->redirectToRoute('game_explore');
}

}