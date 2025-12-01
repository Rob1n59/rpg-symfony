<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\EnemyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class GameController extends AbstractController
{
    // ... (Vos méthodes existantes : menuPrincipal, start, chooseHero, chooseHeroConfirm, explore, travelToLocation) ...
    private EntityManagerInterface $entityManager;
    // Note: Vous pouvez ajouter ici d'autres Repository si nécessaire

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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
        $classId = (int)$request->request->get('class_id');
        $playerName = $request->request->get('player_name');

        if (!$classId || !$playerName) {
            $this->addFlash('error', 'Veuillez choisir une classe et entrer un nom.');
            return $this->redirectToRoute('choose_hero');
        }

        $classes = [
            1 => ['name' => 'Guerrier', 'hp' => 120, 'attack' => 8, 'defense' => 12],
            2 => ['name' => 'Mage', 'hp' => 80, 'attack' => 15, 'defense' => 5],
            3 => ['name' => 'Archer', 'hp' => 100, 'attack' => 10, 'defense' => 8]
        ];

        if (!isset($classes[$classId])) {
            $this->addFlash('error', 'Classe invalide.');
            return $this->redirectToRoute('choose_hero');
        }

        $stats = $classes[$classId];

        $player = new Player();
        $player->setName($playerName);
        $player->setHp($stats['hp']);
        $player->setHpMax($stats['hp']);
        $player->setAttack($stats['attack']);
        $player->setDefense($stats['defense']);
        $player->setGold(0);
        $player->setExperience(0);
        $player->setLevel(1);
        $player->setPlayerClassId($classId);
        $player->setPlayerClassName($stats['name']);

        $em->persist($player);
        $em->flush();

        $session->set('player_id', $player->getId());

        // NOUVEAU: Initialiser les données de session pour les variantes et l'état des coffres
        $session->set('player_location_variants', []);
        $session->set('player_location_variant_history', []); // Historique des variantes pour chaque lieu
        $session->set('chests_looted', []); // [locationId => [variantKey => true/false]]

        return $this->redirectToRoute('game_explore');
    }

    #[Route('/explore', name: 'game_explore')]
    public function explore(
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            // Le joueur n'est pas trouvé, invalider la session et rediriger
            $session->remove('player_id');
            $this->addFlash('error', 'Session joueur expirée ou invalide.');
            return $this->redirectToRoute('choose_hero');
        }

        $locations = $this->getAllDefinedLocations();

        return $this->render('game/explore.html.twig', [
            'locations' => $locations
        ]);
    }
    // src/Controller/GameController.php

private function getSceneFileName(int $locationId, string $variant): string
{
    if ($variant === 'base') {
        return $locationId . '.png'; // Retourne 1.png, 2.png, etc.
    }
    return $locationId . '_' . $variant . '.png'; // Retourne 1_v1.png, 1_v2.png, etc.
}

  #[Route('/game/location/{id}/travel', name: 'game_location_travel', methods: ['GET'])]
    public function travelToLocation(
        int $id,
        SessionInterface $session,
        EntityManagerInterface $em 
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            $session->remove('player_id');
            $this->addFlash('error', 'Session joueur expirée ou invalide.');
            return $this->redirectToRoute('choose_hero');
        }
        
        $allLocations = $this->getAllDefinedLocations();
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
            'location' => $location,
            'player' => $player
        ]);
    }

    // Votre méthode showLocation existante, MODIFIÉE pour initialiser l'historique et l'état du coffre
    #[Route('/game/location/{id}', name: 'game_location_show')]
    public function showLocation(
        int $id,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            $this->addFlash('error', 'Joueur non trouvé.');
            return $this->redirectToRoute('choose_hero');
        }

      $allLocations = $this->getAllDefinedLocations();

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

    $playerLocationVariants = $session->get('player_location_variants', []);
    $playerLocationVariantHistory = $session->get('player_location_variant_history', []);
    
    // Définir la variante initiale comme 'base' (correspond à 1.png)
    $initialVariant = 'base'; 
    
    // Vérifier si c'est la première fois qu'on visite ce lieu (ou si la session est vide pour cet ID)
    $isFirstVisit = !isset($playerLocationVariants[$id]);
    
    if ($isFirstVisit) {
        // --- Si première visite, forcer 'base' et initialiser la session ---
        $currentSceneVariant = $initialVariant;
        
        // Initialiser la session avec la variante 'base'
        $playerLocationVariants[$id] = $currentSceneVariant;
        $session->set('player_location_variants', $playerLocationVariants);
        
        // Initialiser l'historique de la scène avec 'base'
        $playerLocationVariantHistory[$id] = [$currentSceneVariant];
        $session->set('player_location_variant_history', $playerLocationVariantHistory);
        
    } else {
        // Sinon, on utilise la variante actuellement enregistrée
        $currentSceneVariant = $playerLocationVariants[$id];
    }


    $sceneOptions = [
        // ... (vos options de scène) ...
    ];

    $chestsLooted = $session->get('chests_looted', []);
    $isChestLooted = $chestsLooted[$id]['v1'] ?? false; 

    // Note: Le code pour "S'assurer que la variante actuelle est dans l'historique" 
    // peut être retiré car il est implicitement géré par le bloc `if ($isFirstVisit)`
    // et par les routes AJAX (next/previousSceneVariant) lors des changements.
    // L'ancienne logique `if (!in_array(...))` est redondante ou peut créer des problèmes ici.
    
    return $this->render('game/location_show.html.twig', [
        'location' => $location,
        'sceneOptions' => $sceneOptions,
        'player' => $player,
        'currentSceneVariant' => $currentSceneVariant, // 'base' au premier chargement
        'isChestLooted' => $isChestLooted,
        'canGoBack' => count($playerLocationVariantHistory[$id] ?? []) > 1,
    ]);
}

    // Votre méthode handleOption EXISTANTE, MODIFIÉE pour la logique du coffre et le retour arrière
    #[Route('/game/handle-option/{locationId}/{optionId}', name: 'game_handle_option', methods: ['GET', 'POST'])]
    public function handleOption(
        Request $request,
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

        $currentLocation = null;
        foreach ($this->getAllDefinedLocations() as $loc) {
            if ($loc['id'] === $locationId) {
                $currentLocation = $loc;
                break;
            }
        }

        if (!$currentLocation) {
            $this->addFlash('error', 'Lieu introuvable.');
            return $this->redirectToRoute('game_explore');
        }

        // Récupérer la variante de scène actuelle du joueur
        $playerLocationVariants = $session->get('player_location_variants', []);
        $currentSceneVariant = $playerLocationVariants[$locationId] ?? 'v1';

        switch ($optionId) {
            case 101: // Continuer le chemin (géré par AJAX)
                $this->addFlash('error', 'Le bouton "Continuer le chemin" doit être géré via JavaScript.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

           case 102: // Chercher des ressources
            $chanceToFind = rand(1, 100);
            $message = '';
            $flashType = 'warning'; // Valeur par défaut si rien n'est trouvé

            if ($chanceToFind <= 70) {
            $goldFound = rand(5, 15);
            $player->setGold($player->getGold() + $goldFound);
            $this->entityManager->flush(); // Sauvegarde en DB
        
            $message = sprintf('Vous avez trouvé <span class="highlight-item">%d pièces d\'or</span> !', $goldFound);
            $flashType = 'success';
            } else {
            $message = 'Vous n\'avez rien trouvé d\'intéressant';
            $flashType = 'warning';
}
    
    // Retourne une réponse JSON pour l'AJAX
    return new JsonResponse([
        'status' => 'update',
        'message' => $message,
        'flashType' => $flashType,
        'playerStats' => [
            'gold' => $player->getGold(),
            'hp' => $player->getHp(),
            'hpMax' => $player->getHpMax(),
            'attack' => $player->getAttack(),
            'defense' => $player->getDefense(),
            'experience' => $player->getExperience(),
            'level' => $player->getLevel(),
        ]
    ]);
            case 103: // Retour à la carte
                $this->addFlash('info', 'Vous êtes de retour sur la carte.');
                return $this->redirectToRoute('game_explore');

            case 104: // NOUVEAU: Fouiller le coffre (ID 104) - LOGIQUE CORRIGÉE
                $chestsLooted = $session->get('chests_looted', []);

                // Le coffre est sur la scène v1 de la Forêt d'Alden (locationId 1)
                if ($locationId === 1 && $currentSceneVariant === 'v1') {
                    // Vérifier si le coffre a déjà été fouillé pour cette scène
                    if (!($chestsLooted[$locationId]['v1'] ?? false)) {
                        $goldFound = rand(20, 50);
                        $player->setGold($player->getGold() + $goldFound);
                        $em->flush();
                        $message = sprintf('Vous avez fouillé le coffre et trouvé <span class="highlight-item">%d pièces d\'or</span> !', $goldFound);
                        $this->addFlash('success', $message);

                        // Marquer le coffre comme fouillé dans la session
                        $chestsLooted[$locationId]['v1'] = true;
                        $session->set('chests_looted', $chestsLooted);

                        // Après avoir fouillé, vous pourriez vouloir changer de scène automatiquement
                        // ou simplement recharger pour que le bouton disparaisse.
                        // Pour l'instant, on recharge pour que le bouton disparaisse.
                        return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
                    } else {
                        $this->addFlash('info', 'Ce coffre a déjà été fouillé.');
                        return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
                    }
                } else {
                    $this->addFlash('error', 'Il n\'y a pas de coffre à fouiller ici.');
                    return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
                }

            case 105: // NOUVEAU: Revenir en arrière (ID 105) - GÉRÉ PAR AJAX DANS LE FRONT
                $this->addFlash('error', 'Le bouton "Revenir en arrière" doit être géré via JavaScript.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

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
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $enemies = $enemyRepo->findAll();
        if (empty($enemies)) {
            $this->addFlash('error', 'Aucun ennemi trouvé pour le combat !');
            return $this->redirectToRoute('game_explore');
        }

        $enemy = $enemies[array_rand($enemies)];
        $session->set('enemy_id', $enemy->getId());

        return $this->redirectToRoute('combat_start');
    }

    // --- NOUVELLE MÉTHODE AJAX pour changer de scène ---
    #[Route('/game/next_scene_variant/{locationId}', name: 'game_next_scene_variant', methods: ['POST'])]
    public function nextSceneVariant(Request $request, int $locationId): JsonResponse
    {
        $session = $request->getSession();
        $playerLocationVariants = $session->get('player_location_variants', []);
        $playerLocationVariantHistory = $session->get('player_location_variant_history', []);

        $currentVariantKey = $playerLocationVariants[$locationId] ?? 'v1';

        // Déterminer la prochaine variante
        $nextVariantKey = $this->determineNextVariant($locationId, $currentVariantKey);
        
        // Mettre à jour la session avec la nouvelle variante
        $playerLocationVariants[$locationId] = $nextVariantKey;
        $session->set('player_location_variants', $playerLocationVariants);

        // NOUVEAU: Ajouter la nouvelle variante à l'historique
        if (!isset($playerLocationVariantHistory[$locationId])) {
            $playerLocationVariantHistory[$locationId] = [];
        }
        // Éviter les doublons si on est déjà sur cette variante (ex: premier chargement)
        if (empty($playerLocationVariantHistory[$locationId]) || end($playerLocationVariantHistory[$locationId]) !== $nextVariantKey) {
            $playerLocationVariantHistory[$locationId][] = $nextVariantKey;
        }
        $session->set('player_location_variant_history', $playerLocationVariantHistory);

        $mainImageUrl = $this->asset('/images/location_scenes/' . $locationId . '_' . $nextVariantKey . '.png');
        $blurredBgImageUrl = $this->asset('/images/location_scenes/' . $locationId . '_' . $nextVariantKey . '.png');

        // NOUVEAU: Vérifier si le bouton "Revenir en arrière" doit être visible
        $canGoBack = count($playerLocationVariantHistory[$locationId] ?? []) > 1;

        // NOUVEAU: Vérifier si le bouton "Fouiller le coffre" doit être visible
        $chestsLooted = $session->get('chests_looted', []);
        $isChestLooted = $chestsLooted[$locationId]['v1'] ?? false;
        $showLootChestOption = ($locationId === 1 && $nextVariantKey === 'v1' && !$isChestLooted);

        return new JsonResponse([
            'status' => 'success',
            'mainImageUrl' => $mainImageUrl,
            'blurredBgImageUrl' => $blurredBgImageUrl,
            'canGoBack' => $canGoBack,
            'showLootChestOption' => $showLootChestOption, // Pour la logique front-end
        ]);
    }

    // NOUVEAU: MÉTHODE AJAX pour revenir à la scène précédente
    #[Route('/game/previous_scene_variant/{locationId}', name: 'game_previous_scene_variant', methods: ['POST'])]
    public function previousSceneVariant(Request $request, int $locationId): JsonResponse
    {
        $session = $request->getSession();
        $playerLocationVariants = $session->get('player_location_variants', []);
        $playerLocationVariantHistory = $session->get('player_location_variant_history', []);

        if (!isset($playerLocationVariantHistory[$locationId]) || count($playerLocationVariantHistory[$locationId]) <= 1) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Pas d\'historique de scène pour revenir en arrière.',
                'canGoBack' => false,
            ], 400); // Bad Request
        }

        // Retirer la variante actuelle (la dernière de l'historique)
        array_pop($playerLocationVariantHistory[$locationId]);
        // La nouvelle variante actuelle est l'avant-dernière (maintenant la dernière)
        $previousVariantKey = end($playerLocationVariantHistory[$locationId]);

        // Mettre à jour la session avec la nouvelle variante et l'historique
        $playerLocationVariants[$locationId] = $previousVariantKey;
        $session->set('player_location_variants', $playerLocationVariants);
        $session->set('player_location_variant_history', $playerLocationVariantHistory);

        $mainImageUrl = $this->asset('/images/location_scenes/' . $locationId . '_' . $previousVariantKey . '.png');
        $blurredBgImageUrl = $this->asset('/images/location_scenes/' . $locationId . '_' . $previousVariantKey . '.png');

        $canGoBack = count($playerLocationVariantHistory[$locationId]) > 1;

        // NOUVEAU: Vérifier si le bouton "Fouiller le coffre" doit être visible
        $chestsLooted = $session->get('chests_looted', []);
        $isChestLooted = $chestsLutionId['v1'] ?? false;
        $showLootChestOption = ($locationId === 1 && $previousVariantKey === 'v1' && !$isChestLooted);


        return new JsonResponse([
            'status' => 'success',
            'mainImageUrl' => $mainImageUrl,
            'blurredBgImageUrl' => $blurredBgImageUrl,
            'canGoBack' => $canGoBack,
            'showLootChestOption' => $showLootChestOption, // Pour la logique front-end
        ]);
    }

    /**
     * Logique pour déterminer la prochaine variante de scène.
     * C'est ici que vous définissez la séquence des images pour chaque lieu.
     *
     * IMPORTANT:
     * - Pour la Forêt d'Alden (ID 1):
     * - 'v1': Forêt avec le coffre entre-ouvert
     * - 'v2': Forêt avec l'entrée de la grotte
     * - 'v3': Intérieur de la grotte
     */
    private function determineNextVariant(int $locationId, string $currentVariantKey): string
    {
        $nextVariant = $currentVariantKey;

        switch ($locationId) {
            case 1: // Forêt d'Alden (ID 1)
                if ($currentVariantKey === 'v1') {
                    $nextVariant = 'v2'; // Coffre -> Entrée grotte
                } elseif ($currentVariantKey === 'v2') {
                    $nextVariant = 'v3'; // Entrée grotte -> Intérieur grotte
                } elseif ($currentVariantKey === 'v3') {
                    // Optionnel: revenir à l'entrée de la grotte après l'exploration intérieure
                    $nextVariant = 'v2';
                } else {
                    $nextVariant = 'v1'; // Fallback
                }
                break;
            case 2: // Ruines d'Eldamar (ID 2)
                if ($currentVariantKey === 'v1') {
                    $nextVariant = 'v2';
                } else {
                    $nextVariant = 'v1';
                }
                break;
            case 3: // Montagnes du Nord (ID 3)
                if ($currentVariantKey === 'v1') {
                    $nextVariant = 'v2';
                } else {
                    $nextVariant = 'v1';
                }
                break;
            case 4: // Plaine Verdoyante (ID 4)
                if ($currentVariantKey === 'v1') {
                    $nextVariant = 'v2';
                } else {
                    $nextVariant = 'v1';
                }
                break;
            default:
                $nextVariant = 'v1';
                break;
        }

        $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/location_scenes/' . $locationId . '_' . $nextVariant . '.png';
        if (!file_exists($imagePath)) {
            $fallbackVariant = 'v1';
            $fallbackImagePath = $this->getParameter('kernel.project_dir') . '/public/images/location_scenes/' . $locationId . '_' . $fallbackVariant . '.png';
            if (file_exists($fallbackImagePath)) {
                 return $fallbackVariant;
            }
        }

        return $nextVariant;
    }

    private function asset(string $path): string
    {
        return $this->container->get('router')->getContext()->getBaseUrl() . $path;
    }

    private function getAllDefinedLocations(): array
    {
        return [
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
    }
}