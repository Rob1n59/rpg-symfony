<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Location; // Laissez-le pour l'instant si vous l'utilisez ailleurs, sinon il pourrait être commenté
use App\Repository\EnemyRepository;
use App\Repository\LocationRepository; // Laissez-le si vous l'utilisez pour Player->setCurrentLocation
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Utilisez Annotation\Route
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{
    // --- Vos méthodes existantes INTACTES (jusqu'à la fin de 'explore') ---

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
        SessionInterface $session,
        // Si vous utilisez de vraies entités Location, vous injecteriez LocationRepository ici
        // LocationRepository $locationRepository
    ): Response {
        $classId = (int)$request->request->get('class_id'); // Castez en int pour plus de sécurité
        $playerName = $request->request->get('player_name');

        if (!$classId || !$playerName) {
            $this->addFlash('error', 'Veuillez choisir une classe et entrer un nom.');
            return $this->redirectToRoute('choose_hero');
        }

        // Ajout des noms de classes dans le tableau pour pouvoir les stocker
        $classes = [
            1 => ['name' => 'Guerrier', 'hp' => 120, 'attack' => 8,  'defense' => 12],
            2 => ['name' => 'Mage',    'hp' => 80,  'attack' => 15, 'defense' => 5],
            3 => ['name' => 'Archer',  'hp' => 100, 'attack' => 10, 'defense' => 8]
        ];

        if (!isset($classes[$classId])) {
            $this->addFlash('error', 'Classe invalide.');
            return $this->redirectToRoute('choose_hero');
        }

        $stats = $classes[$classId];

        $player = new Player();
        $player->setName($playerName);
        $player->setHp($stats['hp']);
        $player->setHpMax($stats['hp']);       // <-- NOUVEAU : Définir hpMax
        $player->setAttack($stats['attack']);
        $player->setDefense($stats['defense']);
        $player->setGold(0);
        $player->setExperience(0);
        $player->setLevel(1);                  // <-- NOUVEAU : Définir un niveau initial
        $player->setPlayerClassId($classId);   // <-- NOUVEAU : Définir l'ID de la classe
        $player->setPlayerClassName($stats['name']); // <-- NOUVEAU : Définir le nom de la classe

        // Si vous avez une vraie entité Location que le joueur doit rejoindre au départ :
        /*
        $initialLocation = $em->getRepository(Location::class)->find(1); // Cherche la location avec l'ID 1
        if ($initialLocation) {
            $player->setCurrentLocation($initialLocation);
        }
        */

        $em->persist($player);
        $em->flush();

        $session->set('player_id', $player->getId());

        return $this->redirectToRoute('game_explore');
    }

    // Votre méthode explore existante (avec la liste de lieux en dur)
    #[Route('/explore', name: 'game_explore')]
    public function explore(
        SessionInterface $session
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

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
    // --- FIN de vos méthodes existantes et intactes ---


    // --- AJOUT : Nouvelle méthode pour la page de transition (CHARGEMENT) ---
    // Elle récupère le lieu comme vos autres méthodes le feraient
    #[Route('/game/location/{id}/travel', name: 'game_location_travel', methods: ['GET'])]
    public function travelToLocation(int $id, SessionInterface $session): Response
    {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        // Duplique la logique de récupération de lieu depuis votre méthode explore ou showLocation
        // Idéalement, ceci devrait être dans une fonction privée pour éviter la duplication.
        $allLocations = [
            [
                'id' => 1, 'name' => 'Forêt d’Alden', 'description' => '...', 'dangerLevel' => 'Faible', 'x' => 22, 'y' => 34
            ],
            [
                'id' => 2, 'name' => 'Ruines d’Eldamar', 'description' => '...', 'dangerLevel' => 'Élevé', 'x' => 55, 'y' => 48
            ],
            [
                'id' => 3, 'name' => 'Montagnes du Nord', 'description' => '...', 'dangerLevel' => 'Très Élevé', 'x' => 75, 'y' => 18
            ],
            [
                'id' => 4, 'name' => 'Plaine Verdoyante', 'description' => '...', 'dangerLevel' => 'Très faible', 'x' => 40, 'y' => 70
            ]
        ];
        // Note: J'ai abrégé les descriptions ici pour la lisibilité du code.
        // Vous devriez utiliser les descriptions complètes de votre tableau existant.


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

        return $this->render('game/travel_to_location.html.twig', [
            'location' => $location, // C'est un tableau PHP
        ]);
    }
    // --- FIN AJOUT ---


    // Votre méthode showLocation existante (avec la liste de lieux en dur)
    // Pas de modification ici, car votre code est déjà adapté à un ID et à la recherche dans le tableau
    #[Route('/game/location/{id}', name: 'game_location_show')]
    #[Route('/game/location/{id}', name: 'game_location_show')]
    public function showLocation(
        int $id,
        SessionInterface $session,
        EntityManagerInterface $em // Injectez l'EntityManager
        // LocationRepository $locationRepository, // Plus nécessaire ici, on utilise le tableau en dur pour les lieux
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        // --- NOUVEAU : Récupération de l'objet Player ---
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            $this->addFlash('error', 'Joueur non trouvé.');
            return $this->redirectToRoute('choose_hero');
        }
        // --- FIN NOUVEAU ---

        // Récupère la liste des lieux (celle que tu as définie manuellement)
        $allLocations = [
            [
                'id' => 1, 'name' => 'Forêt d’Alden', 'description' => 'Une forêt dense où rôdent des créatures sauvages.', 'dangerLevel' => 'Faible', 'x' => 22, 'y' => 34
            ],
            [
                'id' => 2, 'name' => 'Ruines d’Eldamar', 'description' => 'Ancienne cité magique, hantée par des esprits.', 'dangerLevel' => 'Élevé', 'x' => 55, 'y' => 48
            ],
            [
                'id' => 3, 'name' => 'Montagnes du Nord', 'description' => 'Région glacée abritant des monstres puissants.', 'dangerLevel' => 'Très Élevé', 'x' => 75, 'y' => 18
            ],
            [
                'id' => 4, 'name' => 'Plaine Verdoyante', 'description' => 'Zone paisible, idéale pour commencer une aventure.', 'dangerLevel' => 'Très faible', 'x' => 40, 'y' => 70
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

        $sceneOptions = [
            ['id' => 101, 'text' => 'Continuer le chemin'],
            ['id' => 102, 'text' => 'Chercher des ressources'],
            ['id' => 103, 'text' => 'Retour à la carte'],
        ];

        return $this->render('game/location_show.html.twig', [
            'location' => $location,
            'sceneOptions' => $sceneOptions,
            'player' => $player, // --- NOUVEAU : Passe l'objet Player au template ---
        ]);
    }

    // --- Vos méthodes handleOption et encounter existantes INTACTES ---

    #[Route('/game/handle-option/{locationId}/{optionId}', name: 'game_handle_option')]
    public function handleOption(
        int $locationId,
        int $optionId,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        if (!$player) {
            $this->addFlash('error', 'Joueur non trouvé.');
            return $this->redirectToRoute('choose_hero');
        }

        // Récupérer le lieu actuel pour pouvoir y retourner ou afficher des infos spécifiques
        // Ceci est important car vos options renvoient toujours à la location_show
        $allLocations = [
            [
                'id' => 1, 'name' => 'Forêt d’Alden', 'description' => 'Une forêt dense où rôdent des créatures sauvages.', 'dangerLevel' => 'Faible', 'x' => 22, 'y' => 34
            ],
            [
                'id' => 2, 'name' => 'Ruines d’Eldamar', 'description' => 'Ancienne cité magique, hantée par des esprits.', 'dangerLevel' => 'Élevé', 'x' => 55, 'y' => 48
            ],
            [
            'id' => 3, 'name' => 'Montagnes du Nord', 'description' => 'Région glacée abritant des monstres puissants.', 'dangerLevel' => 'Très Élevé', 'x' => 75, 'y' => 18
            ],
            [
                'id' => 4, 'name' => 'Plaine Verdoyante', 'description' => 'Zone paisible, idéale pour commencer une aventure.', 'dangerLevel' => 'Très faible', 'x' => 40, 'y' => 70
            ]
        ];

        $currentLocation = null;
        foreach ($allLocations as $loc) {
            if ($loc['id'] === $locationId) {
                $currentLocation = $loc;
                break;
            }
        }

        if (!$currentLocation) {
            $this->addFlash('error', 'Lieu introuvable.');
            return $this->redirectToRoute('game_explore'); // Redirection de secours
        }


      switch ($optionId) {
            case 101: // Continuer le chemin
                $this->addFlash('info', 'Vous continuez votre chemin et cherchez des adversaires...');
                return $this->redirectToRoute('game_loading_combat', ['locationId' => $locationId]);

            case 102: // Chercher des ressources
                $chanceToFind = rand(1, 100);

                if ($chanceToFind <= 70) { // 70% de chance de trouver quelque chose
                    $goldFound = rand(5, 15);
                    $player->setGold($player->getGold() + $goldFound);
                    $em->flush();
                    
                    // NOUVEAU: Insérer une balise <span> pour la surbrillance
                    $message = sprintf('Vous avez cherché des ressources et trouvé <span class="highlight-item">%d pièces d\'or</span> ! Votre total est maintenant de %d.', $goldFound, $player->getGold());
                    $this->addFlash('success', $message);
                } else { // 30% de chance de ne rien trouver
                    $this->addFlash('warning', 'Vous avez cherché des ressources, mais n\'avez rien trouvé cette fois-ci.');
                }
                
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

            case 103: // Retour à la carte
                $this->addFlash('info', 'Vous êtes de retour sur la carte.');
                return $this->redirectToRoute('game_explore');

            default:
                $this->addFlash('error', 'Option invalide.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
        }
    }


    #[Route('/explore/encounter', name: 'game_encounter')]
    public function encounter(
        EnemyRepository $enemyRepo,
        SessionInterface $session
    ): Response {
        $enemies = $enemyRepo->findAll();
        if (empty($enemies)) {
            $this->addFlash('error', 'Aucun ennemi trouvé pour le combat !');
            return $this->redirectToRoute('game_explore');
        }

        $enemy = $enemies[array_rand($enemies)];
        $session->set('enemy_id', $enemy->getId());

        return $this->redirectToRoute('combat_start');
    }
}