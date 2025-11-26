<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Location; // Ajouté : Pour le type-hinting futur et la clarté
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
    // Méthodes existantes ---------------------------------------------------------------------------------

    #[Route('/', name: 'app_menu_principal')]
    public function menuPrincipal(): Response
    {
        return $this->render('game/menu.html.twig');
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

        // Au lieu d'utiliser LocationRepository->find(1), tu peux passer null initialement.
        // Ou bien, si tu veux une location de départ, assure-toi qu'elle existe dans la BDD
        // et qu'elle est récupérée via LocationRepository. Pour l'instant, null est plus sûr
        // si tu n'as pas encore de fixtures.
        // $initialLocation = $locationRepository->find(1); // Si 1 est ton ID de départ
        // $player->setCurrentLocation($initialLocation);

        $em->persist($player);
        $em->flush();

        $session->set('player_id', $player->getId());

        return $this->redirectToRoute('game_explore');
    }

    // Méthode explore (modifiée pour dangerLevel) ----------------------------------------------------

    #[Route('/explore', name: 'game_explore')]
    public function explore(
        SessionInterface $session
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        // Lieux définis manuellement pour la carte interactive
        // CORRECTION : 'danger' devient 'dangerLevel' pour correspondre au Twig et au JS
        $locations = [
            [
                'id' => 1,
                'name' => 'Forêt d’Alden',
                'description' => 'Une forêt dense où rôdent des créatures sauvages.',
                'dangerLevel' => 'Faible',
                'x' => 22,
                'y' => 34
            ],
            [
                'id' => 2,
                'name' => 'Ruines d’Eldamar',
                'description' => 'Ancienne cité magique, hantée par des esprits.',
                'dangerLevel' => 'Élevé',
                'x' => 55,
                'y' => 48
            ],
            [
                'id' => 3,
                'name' => 'Montagnes du Nord',
                'description' => 'Région glacée abritant des monstres puissants.',
                'dangerLevel' => 'Très Élevé',
                'x' => 75,
                'y' => 18
            ],
            [
                'id' => 4,
                'name' => 'Plaine Verdoyante',
                'description' => 'Zone paisible, idéale pour commencer une aventure.',
                'dangerLevel' => 'Très faible',
                'x' => 40,
                'y' => 70
            ]
        ];

        return $this->render('game/explore.html.twig', [
            'locations' => $locations
        ]);
    }

    // Nouvelle méthode showLocation -------------------------------------------------------------------

    #[Route('/game/location/{id}', name: 'game_location_show')]
    public function showLocation(
        int $id,
        SessionInterface $session
        // LocationRepository $locationRepository, // Décommenter si tu passes aux entités BDD pour récupérer les SceneOption
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        // Récupère la liste des lieux (celle que tu as définie manuellement)
        // NOTE: Idéalement, cette logique devrait être factorisée ou les lieux stockés en BDD
        $allLocations = [
            [
                'id' => 1,
                'name' => 'Forêt d’Alden',
                'description' => 'Une forêt dense où rôdent des créatures sauvages.',
                'dangerLevel' => 'Faible',
                'x' => 22,
                'y' => 34
            ],
            [
                'id' => 2,
                'name' => 'Ruines d’Eldamar',
                'description' => 'Ancienne cité magique, hantée par des esprits.',
                'dangerLevel' => 'Élevé',
                'x' => 55,
                'y' => 48
            ],
            [
                'id' => 3,
                'name' => 'Montagnes du Nord',
                'description' => 'Région glacée abritant des monstres puissants.',
                'dangerLevel' => 'Très Élevé',
                'x' => 75,
                'y' => 18
            ],
            [
                'id' => 4,
                'name' => 'Plaine Verdoyante',
                'description' => 'Zone paisible, idéale pour commencer une aventure.',
                'dangerLevel' => 'Très faible',
                'x' => 40,
                'y' => 70
            ]
        ];

        $location = null;
        foreach ($allLocations as $loc) {
            if ($loc['id'] === $id) {
                $location = $loc;
                break;
            }
        }

        if (!$location) {
            throw $this->createNotFoundException('Le lieu demandé n\'existe pas.');
        }

        // Options de scène par défaut pour l'instant
        // Idéalement, ces options viendraient de la BDD liées à l'entité Location
        $sceneOptions = [
            ['id' => 101, 'text' => 'Continuer le chemin'],
            ['id' => 102, 'text' => 'Chercher des ressources'],
            ['id' => 103, 'text' => 'Retour à la carte'], // Option pour revenir à l'exploration
        ];

        return $this->render('game/location_show.html.twig', [
            'location' => $location,
            'sceneOptions' => $sceneOptions,
        ]);
    }

    // Nouvelle méthode handleOption (suggérée) -------------------------------------------------------

    #[Route('/game/handle-option/{locationId}/{optionId}', name: 'game_handle_option')]
    public function handleOption(
        int $locationId,
        int $optionId,
        SessionInterface $session,
        EntityManagerInterface $em, // Ajouté si tu as besoin de modifier l'état du joueur
        LocationRepository $locationRepository // Ajouté si tu passes le joueur d'un lieu à l'autre via la BDD
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        if (!$player) {
            $this->addFlash('error', 'Joueur non trouvé.');
            return $this->redirectToRoute('choose_hero');
        }

        // Ici, tu mettras la logique pour chaque option
        switch ($optionId) {
            case 101: // Continuer le chemin
                $this->addFlash('info', 'Vous continuez votre chemin...');
                // Pourrait potentiellement mener à un combat, ou juste avancer
                return $this->redirectToRoute('game_encounter'); // Exemple: mène à un combat
                break;
            case 102: // Chercher des ressources
                $this->addFlash('info', 'Vous cherchez des ressources et trouvez 5 pièces d\'or !');
                $player->setGold($player->getGold() + 5);
                $em->flush();
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]); // Reste sur la scène
                break;
            case 103: // Retour à la carte
                $this->addFlash('info', 'Vous êtes de retour sur la carte.');
                return $this->redirectToRoute('game_explore');
                break;
            default:
                $this->addFlash('error', 'Option invalide.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
        }
    }

    // Méthode encounter existante --------------------------------------------------------------------

    #[Route('/explore/encounter', name: 'game_encounter')]
    public function encounter(
        EnemyRepository $enemyRepo,
        SessionInterface $session
    ): Response {
        $enemies = $enemyRepo->findAll();
        // Gérer le cas où il n'y a pas d'ennemis (si ta BDD est vide)
        if (empty($enemies)) {
            $this->addFlash('error', 'Aucun ennemi trouvé pour le combat !');
            return $this->redirectToRoute('game_explore'); // Redirige à l'exploration
        }

        $enemy = $enemies[array_rand($enemies)];

        $session->set('enemy_id', $enemy->getId());

        return $this->redirectToRoute('combat_start');
    }
}