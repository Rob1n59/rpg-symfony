<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Enemy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request; // Importation de Request pour la méthode combat
use App\Service\ExperienceService; // Service de gestion de l'XP

class CombatController extends AbstractController
{
    /**
     * Affiche l'écran de combat initial.
     */
    #[Route('/combat', name: 'combat_start')]
    public function combat(
        SessionInterface $session,
        EntityManagerInterface $em,
        Request $request // Injection de Request
    ): Response {
        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        $enemy = $em->getRepository(Enemy::class)->find($session->get('enemy_id'));
        
        if (!$player || !$enemy) {
            $this->addFlash('error', 'Erreur de combat : joueur ou ennemi non trouvé.');
            return $this->redirectToRoute('game_explore');
        }

        // --- GESTION DES PV DE L'ENNEMI (Hypothèse: l'Entité de base est modifiée) ---
        if (!$enemy->getHpMax()) {
            $enemy->setHpMax($enemy->getHp()); 
        }
        
        if ($enemy->getHp() <= 0 || $enemy->getHp() != $enemy->getHpMax()) {
             $enemy->setHp($enemy->getHpMax());
             $em->flush(); 
        }
        // ------------------------------------------------------------------
        
        // --- NOUVEAU : ENREGISTREMENT DE L'URL DE RETOUR ---
        // On suppose que la page précédente (Referer) ou une route par défaut est la location.
        // NOTE: Si votre page de location est dynamique (ex: /explore/{id}), il est préférable 
        // de stocker l'URL EXACTE dans la session AVANT la redirection vers '/combat'.
        $returnUrl = $request->headers->get('referer') ?: $this->generateUrl('game_explore');
        $session->set('return_to_map_url', $returnUrl);
        // ---------------------------------------------------

        // Stocke le niveau actuel du joueur (pour la comparaison de montée de niveau)
        $session->set('last_combat_level', $player->getLevel());
        // Nettoie les sessions de récompense
        $session->remove('xpGained');
        $session->remove('goldGained');

        return $this->render('game/combat.html.twig', [
            'player' => $player,
            'enemy' => $enemy
        ]);
    }

    /**
     * Route gérant un tour de combat (appelée par AJAX) avec variance et coups critiques.
     */
    #[Route('/combat/attack', name: 'combat_attack', methods: ['POST'])]
    public function attack(
        SessionInterface $session,
        EntityManagerInterface $em,
        ExperienceService $experienceService // Injection du service XP
    ): JsonResponse {
        $playerId = $session->get('player_id');
        $enemyId = $session->get('enemy_id');
        
        $player = $em->getRepository(Player::class)->find($playerId);
        $enemy = $em->getRepository(Enemy::class)->find($enemyId);

        // Si l'ennemi est déjà mort (0 PV), on renvoie une erreur pour éviter de boucler
        if (!$player || !$enemy || $player->getHp() <= 0 || $enemy->getHp() <= 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Combat déjà terminé ou entités invalides.'], 400);
        }
        
        // Récupération des statistiques totales
        $playerTotalAttack = $player->calculateTotalAttack();
        $playerTotalDefense = $player->calculateTotalDefense();
        
        // Initialisation des variables critiques
        $isPlayerCrit = false;
        $isEnemyCrit = false; 

        // --- CALCUL DES DÉGÂTS DU JOUEUR (avec Variance et Critique) ---
        
        // 1. Variance des dégâts (entre 0.90 et 1.10)
        $variance = mt_rand(90, 110) / 100; 

        // 2. Dégâts de base du joueur
        $baseEnemyDamage = $playerTotalAttack - $enemy->getDefense();
        $enemyDamage = (int)round($baseEnemyDamage * $variance);
        
        // 3. Vérification du Coup Critique du Joueur (Ex: 5% par défaut si méthode getCriticalChance n'existe pas)
        $playerCritChance = method_exists($player, 'getCriticalChance') ? $player->getCriticalChance() : 5;
        
        if (mt_rand(1, 100) <= $playerCritChance) {
            $enemyDamage *= 2; // Facteur critique (double les dégâts)
            $isPlayerCrit = true;
        }

        // 4. S'assurer que les dégâts sont toujours >= 1
        $enemyDamage = max(1, $enemyDamage);


        // --- CALCUL DES DÉGÂTS DE L'ENNEMI (avec Variance seulement) ---
        
        // 1. Dégâts de base de l'ennemi
        $basePlayerDamage = $enemy->getAttack() - $playerTotalDefense;
        // Utiliser la même variance pour la symétrie
        $playerDamage = (int)round($basePlayerDamage * $variance); 
        
        // 2. S'assurer que les dégâts sont toujours >= 1
        $playerDamage = max(1, $playerDamage);


        // --- JOUEUR ATTAQUE ---
        $enemy->setHp($enemy->getHp() - $enemyDamage);
        
        // 3. VÉRIFICATION : Ennemi vaincu ?
        if ($enemy->getHp() <= 0) {
            $enemy->setHp(0); 
            
            // Gain de récompenses
            $xpGained = $enemy->getXpReward();
            $goldGained = $enemy->getGoldReward();

            $player->setExperience($player->getExperience() + $xpGained);
            $player->setGold($player->getGold() + $goldGained);
            
            // LOGIQUE DE MONTÉE DE NIVEAU
            $levelUpData = $experienceService->checkLevelUp($player);
            
            // Stocker les gains exacts en session pour que la vue Result puisse les afficher
            $session->set('goldGained', $goldGained);
            $session->set('xpGained', $xpGained);
            $session->set('last_enemy_name', $enemy->getName()); 

            $em->flush();
            $session->remove('enemy_id'); // Fin du combat

            // Renvoie la réponse de victoire
            return new JsonResponse([
                'status' => 'victory',
                'enemyName' => $enemy->getName(),
                'damageDealt' => $enemyDamage,
                'leveledUp' => $levelUpData['leveledUp'],
                'newLevel' => $levelUpData['newLevel'], 
                'redirectUrl' => $this->generateUrl('combat_result', ['result' => 'victory']),
                'isPlayerCrit' => $isPlayerCrit, 
                'goldGained' => $goldGained,
                'xpGained' => $xpGained,
            ]);
        }

        // --- ENNEMI ATTAQUE (Seulement si l'ennemi est encore en vie) ---
        $player->setHp($player->getHp() - $playerDamage);

        // 4. VÉRIFICATION : Joueur vaincu ?
        if ($player->getHp() <= 0) {
            $player->setHp(0); 
            
            // Stocker le nom de l'ennemi vainqueur
            $session->set('last_enemy_name', $enemy->getName()); 
            
            $em->flush();
            $session->remove('enemy_id'); // Fin du combat

            // Renvoie la réponse de défaite
            return new JsonResponse([
                'status' => 'defeat',
                'enemyName' => $enemy->getName(),
                'damageTaken' => $playerDamage,
                'redirectUrl' => $this->generateUrl('combat_result', ['result' => 'defeat']),
                'isEnemyCrit' => $isEnemyCrit,
            ]);
        }

        $em->flush();

        // 5. COMBAT EN COURS : Renvoie l'état mis à jour
        return new JsonResponse([
            'status' => 'ongoing',
            'enemyName' => $enemy->getName(),
            'damageDealt' => $enemyDamage,
            'damageTaken' => $playerDamage,
            'enemyHp' => $enemy->getHp(),
            'playerHp' => $player->getHp(),
            'isPlayerCrit' => $isPlayerCrit,
            'isEnemyCrit' => $isEnemyCrit,
        ]);
    }
    
    /**
     * Gère la tentative de fuite.
     */
    #[Route('/combat/flee', name: 'combat_flee')]
    public function flee(SessionInterface $session): Response
    {
        $session->remove('enemy_id'); 
        $this->addFlash('info', 'Vous avez réussi à prendre la fuite.');
        
        return $this->redirectToRoute('game_explore'); 
    }
    
    /**
     * Affiche l'écran de résultat (Victoire ou Défaite).
     */
    #[Route('/combat/result/{result}', name: 'combat_result')]
    public function combatResult(string $result, EntityManagerInterface $em, SessionInterface $session): Response
    {
        $playerId = $session->get('player_id');
        $player = $em->getRepository(Player::class)->find($playerId);

        // Récupère les gains stockés en session APRES le combat pour l'affichage
        $xpGained = $session->get('xpGained', 0);
        $goldGained = $session->get('goldGained', 0);
        $lastCombatLevel = $session->get('last_combat_level', 0);
        $lastEnemyName = $session->get('last_enemy_name', 'default');
        
        // --- NOUVEAU : Récupération et nettoyage de l'URL de retour ---
        $returnToMapUrl = $session->get('return_to_map_url', $this->generateUrl('game_explore')); 
        $session->remove('return_to_map_url');
        // -------------------------------------------------------------
        
        // Nettoie les autres variables de session après lecture
        $session->remove('xpGained');
        $session->remove('goldGained');
        $session->remove('last_combat_level');
        $session->remove('last_enemy_name');
        
        
        // Si défaite et que le joueur est mort, on le remet en vie pour l'exploration
        if ($result === 'defeat' && $player && $player->getHp() <= 0) {
             $player->setHp($player->getHpMax());
             $em->flush();
        }
        
        return $this->render('game/result.html.twig', [
            'result' => $result, // 'victory' ou 'defeat'
            'player' => $player,
            'xpGained' => $xpGained,
            'goldGained' => $goldGained,
            'leveledUp' => $player && $player->getLevel() > $lastCombatLevel,
            'lastEnemyName' => $lastEnemyName,
            'returnToMapUrl' => $returnToMapUrl, // NOUVEAU : Passe l'URL à la vue
        ]);
    }
}