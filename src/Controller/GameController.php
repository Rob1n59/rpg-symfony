<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Enemy;
use App\Entity\PlayerItem; // Ajout
use App\Service\InventoryService; // Ajout : Assurez-vous d'avoir ce service
use App\Service\ExperienceService; // Ajout
use App\Repository\EnemyRepository;
use App\Repository\PlayerItemRepository; // Ajout
use App\Repository\ItemRepository; // Ajout
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class GameController extends AbstractController
{
    // Méthodes utilitaires pour la liste des lieux (FACTORISATION)
    private function getAllDefinedLocations(): array
    {
        return [
            ['id' => 1, 'name' => 'Forêt d’Alden', 'description' => 'Une forêt dense où rôdent des créatures sauvages.', 'dangerLevel' => 'Faible', 'x' => 22, 'y' => 34],
            ['id' => 2, 'name' => 'Ruines d’Eldamar', 'description' => 'Ancienne cité magique, hantée par des esprits.', 'dangerLevel' => 'Élevé', 'x' => 55, 'y' => 48],
            ['id' => 3, 'name' => 'Montagnes du Nord', 'description' => 'Région glacée abritant des monstres puissants.', 'dangerLevel' => 'Très Élevé', 'x' => 75, 'y' => 18],
            ['id' => 4, 'name' => 'Plaine Verdoyante', 'description' => 'Zone paisible, idéale pour commencer une aventure.', 'dangerLevel' => 'Très faible', 'x' => 40, 'y' => 70]
        ];
    }

    private function getLocationDataById(int $id): ?array
    {
        foreach ($this->getAllDefinedLocations() as $loc) {
            if ($loc['id'] === $id) {
                return $loc;
            }
        }
        return null;
    }

    // Méthodes de base (Menu, Start, ChooseHero) -------------------------------------------

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
        $player->setAugurPoints(0); 

        $em->persist($player);
        $em->flush();

        $session->set('player_id', $player->getId());

        $session->remove('player_location_variants');
        $session->remove('player_location_variant_history');
        $session->remove('chests_looted');

        return $this->redirectToRoute('game_explore');
    }

    // Page de la carte d'exploration
    #[Route('/explore', name: 'game_explore')]
    public function explore(
        SessionInterface $session
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $locations = $this->getAllDefinedLocations();

        return $this->render('game/explore.html.twig', [
            'locations' => $locations
        ]);
    }

    // Page de transition (chargement)
    #[Route('/game/location/{id}/travel', name: 'game_location_travel', methods: ['GET'])]
    public function travelToLocation(int $id, SessionInterface $session): Response
    {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $location = $this->getLocationDataById($id);

        if (!$location) {
            throw $this->createNotFoundException('Le lieu demandé n\'existe pas.');
        }

        return $this->render('game/travel_to_location.html.twig', [
            'location' => $location,
        ]);
    }

    // Page du lieu d'exploration (après chargement)
    #[Route('/game/location/{id}', name: 'game_location_show')]
    public function showLocation(
        int $id,
        SessionInterface $session,
        EntityManagerInterface $em,
        ExperienceService $xpService
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

        $location = $this->getLocationDataById($id);

        if (!$location) {
            throw $this->createNotFoundException('Le lieu demandé n\'existe pas.');
        }

        // --- ENREGISTREMENT ET GESTION DE L'ÉTAT DU LIEU ---

        $session->set('current_location_id', $id); 
        $session->set('return_to_map_url', $this->generateUrl('game_location_show', ['id' => $id]));
        
        // CRITIQUE : CHARGEMENT CONDITIONNEL DE L'ENNEMI
        $enemy = null;
        $enemyId = $session->get('enemy_id');
        
        if ($enemyId) { 
            $enemy = $em->getRepository(Enemy::class)->find($enemyId);
            
            // Sécurité : si l'ennemi n'est pas trouvé (combat terminé ou ID invalide), on nettoie.
            if (!$enemy) {
                $session->remove('enemy_id');
            }
        }
        // ----------------------------------------------------------------------

        // --- LOGIQUE XP pour la barre de progression ---
        $currentLevel = $player->getLevel();
        $xpRequiredCurrent = $xpService->getRequiredExpForNextLevel($currentLevel - 1); 
        $xpRequiredNext = $xpService->getRequiredExpForNextLevel($currentLevel);
        // -------------------------------------------

        // SIMPLIFICATION
        $currentSceneVariant = ''; 
        $isChestLooted = false; 
        $canGoBack = false;

        $sceneOptions = [
            ['id' => 101, 'text' => 'Continuer le chemin'],
            ['id' => 102, 'text' => 'Chercher des ressources'],
            ['id' => 103, 'text' => 'Retour à la carte'],
        ];
        
        return $this->render('game/location_show.html.twig', [
            'location' => $location,
            'sceneOptions' => $sceneOptions,
            'player' => $player,
            'enemy' => $enemy, 
            'currentSceneVariant' => $currentSceneVariant,
            'isChestLooted' => $isChestLooted,
            'canGoBack' => $canGoBack,
            'xpRequiredCurrent' => $xpRequiredCurrent, 
            'xpRequiredNext' => $xpRequiredNext, 
        ]);
    }
    #[Route('/session/remove_force_reload_flag', name: 'remove_force_reload_flag', methods: ['POST'])]
    public function removeForceReloadFlag(SessionInterface $session): JsonResponse
    {
    // Supprime le flag de session
    $session->remove('force_page_reload');
    return new JsonResponse(['status' => 'removed']);
    }
    // --- ROUTE : INVENTAIRE ---
    #[Route('/game/inventory', name: 'game_inventory', methods: ['GET'])]
    public function showInventory(
        SessionInterface $session, 
        EntityManagerInterface $em,
        PlayerItemRepository $playerItemRepository 
    ): Response {
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            return $this->redirectToRoute('choose_hero');
        }

        $inventory = $playerItemRepository->findBy(['player' => $player]);

        return $this->render('game/inventory_modal.html.twig', [
            'player' => $player,
            'inventory' => $inventory,
        ]);
    }
    
#[Route('/game/equip_item/{playerItemId}', name: 'game_equip_item', methods: ['POST'])]
public function equipItem(
    int $playerItemId,
    SessionInterface $session,
    EntityManagerInterface $em,
    \App\Service\InventoryService $inventoryService
): JsonResponse {
    $playerId = $session->get('player_id');
    $player = $em->getRepository(Player::class)->find($playerId);
    $playerItem = $em->getRepository(\App\Entity\PlayerItem::class)->find($playerItemId);

    if (!$player || !$playerItem || $playerItem->getPlayer()->getId() !== $playerId) {
        return new JsonResponse(['status' => 'error', 'message' => 'Objet ou joueur invalide.'], 400);
    }
    
    $isEquippedBefore = $playerItem->isIsEquipped(); 
    $itemName = $playerItem->getItem()->getName();
    $logMessage = ''; // Initialisation pour éviter l'erreur de variable non définie
    
    try {
        $inventoryService->toggleEquipItem($player, $playerItem);
        if ($isEquippedBefore) {
            
            $logMessage = "L'objet <span class=\"highlight-item\">{$itemName}</span> a été déséquipé.";
        } else {
            
            $logMessage = "L'objet <span class=\"highlight-item\">{$itemName}</span> a été équipé.";
        }

    } catch (\LogicException $e) {
        return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
    }

    return new JsonResponse([
        'status' => 'success',
        'isEquipped' => $playerItem->isIsEquipped(),
        'newAttack' => $player->calculateTotalAttack(), 
        'newDefense' => $player->calculateTotalDefense(), 
        'message' => $logMessage 
    ]);
}
   #[Route('/game/consume_item/{playerItemId}', name: 'game_consume_item', methods: ['POST'])]
public function consumeItem(
    int $playerItemId,
    SessionInterface $session,
    EntityManagerInterface $em,
    PlayerItemRepository $playerItemRepository
): JsonResponse {
    $playerId = $session->get('player_id');
    $player = $em->getRepository(Player::class)->find($playerId);
    $playerItem = $playerItemRepository->find($playerItemId);

    // Vérifications de base
    if (!$player || !$playerItem || $playerItem->getPlayer()->getId() !== $playerId || $playerItem->getQuantity() <= 0) {
        return new JsonResponse(['status' => 'error', 'message' => 'Objet invalide ou indisponible.'], 400);
    }
    
    $item = $playerItem->getItem(); 

    // 1. Vérification que c'est bien un consommable
    if ($item->getType() !== 'consumable' || $item->getHealingAmount() <= 0) { // <--- CORRECTION ICI
        return new JsonResponse(['status' => 'error', 'message' => 'Cet objet ne peut pas être consommé ou n\'a aucun effet de soin.'], 400);
    }
    
    // 2. Appliquer l'effet de soin
    $healAmount = $item->getHealingAmount(); // <--- CORRECTION ICI
    $player->heal($healAmount);
    
    // 3. Déduire la quantité
    $newQuantity = $playerItem->getQuantity() - 1;
    
    if ($newQuantity <= 0) {
        $em->remove($playerItem);
        $message = "Vous avez consommé une {$item->getName()} et récupéré {$healAmount} PV. Elle a disparu de votre inventaire.";
    } else {
        $playerItem->setQuantity($newQuantity);
        $message = "Vous avez consommé une {$item->getName()} et récupéré {$healAmount} PV. Il vous en reste {$newQuantity}.";
    }
    
    $em->flush();

    // 4. Retourner les nouvelles stats pour la mise à jour du front-end
    return new JsonResponse([
        'status' => 'success',
        'message' => $message,
        'newHp' => $player->getHp(),
        'newHpMax' => $player->getHpMax(),
        'playerItemId' => $playerItemId 
    ]);
}
    
    // --- ROUTE : GESTION DES OPTIONS (LOOT, CONTINUER, RETOUR) ---
    #[Route('/game/handle-option/{locationId}/{optionId}', name: 'game_handle_option', methods: ['GET', 'POST'])]
    public function handleOption(
        Request $request,
        int $locationId,
        int $optionId,
        SessionInterface $session,
        EntityManagerInterface $em,
        ItemRepository $itemRepository,
        PlayerItemRepository $playerItemRepository,
        EnemyRepository $enemyRepository
    ): Response {
        if (!$session->has('player_id')) {
            return $this->redirectToRoute('choose_hero');
        }

        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        if (!$player) {
            $this->addFlash('error', 'Joueur non trouvé.');
            return $this->redirectToRoute('choose_hero');
        }

        $currentLocation = $this->getLocationDataById($locationId);
        if (!$currentLocation) {
            $this->addFlash('error', 'Lieu introuvable.');
            return $this->redirectToRoute('game_explore');
        }

        // --- Variables de retour AJAX / FLASH ---
        $goldChange = 0;
        $message = 'Rien trouvé.';
        $flashType = 'warning';
        // ------------------------------------------

        switch ($optionId) {
            case 101: // Continuer le chemin (Risque de Rencontre)
                
                $encounterChance = 50; 
                if (rand(1, 100) <= $encounterChance) {
                    
                    $locationDangerLevel = $currentLocation['dangerLevel'];
                    $enemies = $enemyRepository->findEnemiesByDangerLevel($locationDangerLevel);

                    if (!empty($enemies)) {
                        $enemy = $enemies[array_rand($enemies)]; 

                        if ($enemy->getHp() != $enemy->getHpMax()) {
                            $enemy->setHp($enemy->getHpMax());
                            $em->flush(); // CRITIQUE : Enregistrer l'état pour les vues suivantes
                        }

                        $session->set('last_location_id', $locationId); 
                        $session->set('enemy_id', $enemy->getId());
                        $this->addFlash('danger', 'Attention ! Un ' . $enemy->getName() . ' apparaît !');
                        
                        return $this->redirectToRoute('game_encounter_choice', ['locationId' => $locationId]); 
                    }
                }
                
                $this->addFlash('info', 'Vous continuez votre chemin sans encombre.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

            case 102: 
                
                $chanceToFind = rand(1, 100);

                if ($chanceToFind <= 70) {
                    if (rand(1, 100) <= 30) { 
                        
                        $availableItemNames = ['Épée en Fer', 'Hache de Bois', 'Arc Court', 'Bâton de Saule', 'Potion de soin'];
                        $randomItemName = $availableItemNames[array_rand($availableItemNames)];
                        $foundItem = $itemRepository->findOneBy(['name' => $randomItemName]);
                        
                        if ($foundItem) {
                            $existingPlayerItem = $playerItemRepository->findOneBy(['player' => $player, 'item' => $foundItem]);
                            $isConsumable = $foundItem->getType() === 'consumable';
                            
                            if ($existingPlayerItem && $isConsumable) {
                                $existingPlayerItem->setQuantity($existingPlayerItem->getQuantity() + 1);
                                $em->flush();
                                $message = sprintf('Vous avez trouvé une <span class="highlight-item">%s</span> et en avez maintenant %d !', $foundItem->getName(), $existingPlayerItem->getQuantity());
                                $flashType = 'success';
                            } elseif (!$existingPlayerItem) {
                                $playerItem = new PlayerItem();
                                $playerItem->setPlayer($player);
                                $playerItem->setItem($foundItem);
                                $playerItem->setQuantity(1);
                                $playerItem->setIsEquipped(false);
                                $em->persist($playerItem);
                                $em->flush();

                                $message = sprintf('Vous avez trouvé une <span class="highlight-item">%s</span> !', $foundItem->getName());
                                $flashType = 'success';
                            } else {
                                $message = 'Vous avez trouvé une ' . $foundItem->getName() . ', mais vous ne pouvez pas la porter !';
                                $flashType = 'warning';
                            }
                        } else {
                            // Fallback: Si l'entité Item est manquante, donne de l'or
                            $goldChange = rand(5, 10);
                            $player->setGold($player->getGold() + $goldChange);
                            $em->flush();
                            $message = sprintf('Vous avez trouvé <span class="highlight-item">%d pièces d\'or</span> !', $goldChange);
                            $flashType = 'success';
                        }
                    } else {
                        // Chance de trouver de l'or seulement
                        $goldChange = rand(5, 15);
                        $player->setGold($player->getGold() + $goldChange);
                        $em->flush();
                        $message = sprintf('Vous avez trouvé <span class="highlight-item">%d pièces d\'or</span> !', $goldChange);
                        $flashType = 'success';
                    }
                } else {
                    $message = 'Vous n\'avez rien trouvé d\'intéressant';
                    $flashType = 'warning';
                }
                
                // Après avoir traité, nous renvoyons la réponse JSON pour AJAX
                return new JsonResponse([
                    'status' => 'success',
                    'message' => $message,
                    'flashType' => $flashType,
                    'playerStats' => [
                        'gold' => $player->getGold(),
                        'hp' => $player->getHp(),
                        'attack' => $player->calculateTotalAttack(),
                        'defense' => $player->calculateTotalDefense(),
                    ]
                ]);

            case 103: // Retour à la carte
                $this->addFlash('info', 'Vous êtes de retour sur la carte.');
                return $this->redirectToRoute('game_explore');
                
            default:
                $this->addFlash('error', 'Option invalide.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
        }
    }
    
    // --- ROUTE : CHOIX AVANT LE DÉBUT DU COMBAT ---
    #[Route('/game/encounter/choice/{locationId}', name: 'game_encounter_choice')]
    public function encounterChoice(
        int $locationId,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $enemyId = $session->get('enemy_id');
        $enemy = $em->getRepository(Enemy::class)->find($enemyId);

        if (!$enemy) {
            $this->addFlash('error', 'Ennemi non trouvé. Le combat a été annulé.');
            return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
        }

        $location = $this->getLocationDataById($locationId);
        
        return $this->render('game/encounter_choice.html.twig', [
            'enemy' => $enemy,
            'locationId' => $locationId,
            'location' => $location 
        ]);
    }
    
    // --- ROUTE : FUITE ---
    #[Route('/game/flee/{locationId}', name: 'game_flee')]
    public function flee(
        int $locationId,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        if (!$player) {
            return $this->redirectToRoute('choose_hero');
        }
        
        // --- Logique de Fuite ---
        $successChance = 50;
        $fleeSuccessful = rand(1, 100) <= $successChance;

        // CRITIQUE : Le combat est terminé, on nettoie la session.
        $session->remove('enemy_id'); 

        if ($fleeSuccessful) {
            $this->addFlash('success', 'Vous avez réussi à fuir l\'ennemi sans encombre !');
        } else {
            $damageTaken = 10;
            $player->setHp(max(0, $player->getHp() - $damageTaken));
            $em->flush();
            
            $message = sprintf("Vous n'avez pas réussi à fuir ! Vous avez subi %d dégâts en vous échappant.", $damageTaken);
            $this->addFlash('danger', $message);
        }
        
        return $this->redirectToRoute('game_location_show', ['id' => $locationId]); 
    }
}