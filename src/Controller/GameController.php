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
use Symfony\Component\HttpFoundation\JsonResponse;

class GameController extends AbstractController
{
    // Méthodes utilitaires pour la liste des lieux (FACTORISATION)
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

        $em->persist($player);
        $em->flush();

        $session->set('player_id', $player->getId());

        // NETTOYAGE: Suppression des données de session relatives aux variantes de scène
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

        $location = $this->getLocationDataById($id);

        if (!$location) {
            throw $this->createNotFoundException('Le lieu demandé n\'existe pas.');
        }

        // SIMPLIFICATION pour l'interface : pas de variante, pas de coffre, pas de retour historique
        $currentSceneVariant = ''; // Vide pour utiliser seulement l'ID dans Twig (ex: 1.png)
        $isChestLooted = false; // Désactivé
        $canGoBack = false; // Désactivé

        $sceneOptions = [
            ['id' => 101, 'text' => 'Continuer le chemin'],
            ['id' => 102, 'text' => 'Chercher des ressources'],
            ['id' => 103, 'text' => 'Retour à la carte'],
        ];
        // 104 'Fouiller le coffre' et 105 'Revenir en arrière' sont retirés

        return $this->render('game/location_show.html.twig', [
            'location' => $location,
            'sceneOptions' => $sceneOptions,
            'player' => $player,
            'currentSceneVariant' => $currentSceneVariant,
            'isChestLooted' => $isChestLooted,
            'canGoBack' => $canGoBack,
        ]);
    }

    // Gestion des options (Ressources, Retour)
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

        $currentLocation = $this->getLocationDataById($locationId);
        if (!$currentLocation) {
            $this->addFlash('error', 'Lieu introuvable.');
            return $this->redirectToRoute('game_explore');
        }

        switch ($optionId) {
            case 101: // Continuer le chemin (recharge simplement la page pour le moment)
                $this->addFlash('info', 'Vous avancez prudemment dans la zone.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

            case 102: // Chercher des ressources
                $chanceToFind = rand(1, 100);

                if ($chanceToFind <= 70) {
                    $goldFound = rand(5, 15);
                    $player->setGold($player->getGold() + $goldFound);
                    $em->flush();

                    $message = sprintf('Vous avez trouvé <span class="highlight-item">%d pièces d\'or</span> !', $goldFound);
                    $this->addFlash('success', $message);
                } else {
                    $this->addFlash('warning', 'Vous n\'avez rien trouvé d\'intéressant');
                }
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);

            case 103: // Retour à la carte
                $this->addFlash('info', 'Vous êtes de retour sur la carte.');
                return $this->redirectToRoute('game_explore');
                
            case 104: // Fouiller le coffre (Supprimé de la liste, mais si l'ID arrive...)
            case 105: // Revenir en arrière (Supprimé de la liste, mais si l'ID arrive...)
            default:
                $this->addFlash('error', 'Option invalide ou désactivée temporairement.');
                return $this->redirectToRoute('game_location_show', ['id' => $locationId]);
        }
    }

    // Les méthodes nextSceneVariant, previousSceneVariant, determineNextVariant et asset sont SUPPRIMÉES.

    // Méthode encounter existante
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
}