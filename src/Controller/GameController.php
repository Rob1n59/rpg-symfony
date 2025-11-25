<?php

namespace App\Controller;

use App\Entity\Player;
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
    #[Route('/', name: 'app_menu_principal')]
    public function menuPrincipal(): Response
    {
        // Modifie cette ligne !
        return $this->render('game/menu.html.twig'); // <--- C'est ici !
    }

    #[Route('/start', name: 'game_start')]
    public function start(): Response
    {
        return $this->redirectToRoute('choose_hero');
    }

    #[Route('/choose_hero', name: 'choose_hero')]
    public function chooseHero(): Response
    {
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
            $this->addFlash('error', 'Veuillez choisir une classe et entrer un nom.');
            return $this->redirectToRoute('choose_hero');
        }

        $classes = [
            1 => ['hp' => 120, 'attack' => 8,  'defense' => 12],
            2 => ['hp' => 80,  'attack' => 15, 'defense' => 5],
            3 => ['hp' => 100, 'attack' => 10, 'defense' => 8]
        ];

        if (!isset($classes[$classId])) {
            $this->addFlash('error', 'Classe invalide.');
            return $this->redirectToRoute('choose_hero');
        }

        $stats = $classes[$classId];

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


    #[Route('/explore', name: 'game_explore')]
public function explore(
    SessionInterface $session
): Response {

    // Vérifie qu'un joueur existe en session
    if (!$session->has('player_id')) {
        return $this->redirectToRoute('choose_hero');
    }

    // Lieux définis manuellement pour la carte interactive
    $locations = [
        [
            'id' => 1,
            'name' => 'Forêt d’Alden',
            'description' => 'Une forêt dense où rôdent des créatures sauvages.',
            'danger' => 'Faible',
            'x' => 22,
            'y' => 34
        ],
        [
            'id' => 2,
            'name' => 'Ruines d’Eldamar',
            'description' => 'Ancienne cité magique, hantée par des esprits.',
            'danger' => 'Élevé',
            'x' => 55,
            'y' => 48
        ],
        [
            'id' => 3,
            'name' => 'Montagnes du Nord',
            'description' => 'Région glacée abritant des monstres puissants.',
            'danger' => 'Très Élevé',
            'x' => 75,
            'y' => 18
        ],
        [
            'id' => 4,
            'name' => 'Plaine Verdoyante',
            'description' => 'Zone paisible, idéale pour commencer une aventure.',
            'danger' => 'Très faible',
            'x' => 40,
            'y' => 70
        ]
    ];

    return $this->render('game/explore.html.twig', [
        'locations' => $locations
    ]);
}


    #[Route('/explore/encounter', name: 'game_encounter')]
    public function encounter(
        EnemyRepository $enemyRepo,
        SessionInterface $session
    ): Response {
        $enemies = $enemyRepo->findAll();
        $enemy = $enemies[array_rand($enemies)];

        $session->set('enemy_id', $enemy->getId());

        return $this->redirectToRoute('combat_start');
    }
}