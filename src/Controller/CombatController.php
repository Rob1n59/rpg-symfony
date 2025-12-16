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
use App\Service\ExperienceService;

class CombatController extends AbstractController
{
    /**
     * Affiche l'écran de combat initial.
     */
    #[Route('/combat', name: 'combat_start')]
    public function combat(
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        $enemy = $em->getRepository(Enemy::class)->find($session->get('enemy_id'));
        
        if (!$player || !$enemy) {
            $this->addFlash('error', 'Erreur de combat : joueur ou ennemi non trouvé.');
            return $this->redirectToRoute('game_explore');
        }

        // Assurez-vous que l'ennemi a bien son maxHp pour la barre de vie
        if (!$enemy->getHpMax()) {
            $enemy->setHpMax($enemy->getHp()); 
        }
        
        // Réinitialisation des PV de l'ennemi à chaque nouveau combat
        if ($enemy->getHp() <= 0 || $enemy->getHp() != $enemy->getHpMax()) {
             $enemy->setHp($enemy->getHpMax());
             $em->flush();
        }
        
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
     * Route gérant un tour de combat (appelée par AJAX).
     */
    // Dans src/Controller/CombatController.php

/**
 * Route gérant un tour de combat (appelée par AJAX).
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

    if (!$player || !$enemy || $player->getHp() <= 0 || $enemy->getHp() <= 0) {
        return new JsonResponse(['status' => 'error', 'message' => 'Combat déjà terminé ou entités invalides.'], 400);
    }
    
    // --- CALCUL DES DÉGÂTS UTILISANT LES STATS TOTALES ---
    $playerTotalAttack = $player->calculateTotalAttack();
    $playerTotalDefense = $player->calculateTotalDefense();
    // 1. Dégâts infligés par le joueur à l'ennemi
    $playerBaseDamage = $player->calculateDamage();
    $enemyDamage = max(1, $playerBaseDamage - $enemy->getDefense());

    // 2. Dégâts infligés par l'ennemi au joueur (CORRECTION DES DÉGÂTS BLOQUÉS À 1)
    $enemyBaseDamage = $enemy->calculateDamage(); // Aléatoire entre 1 et 5
    $damageReduction = $playerTotalDefense;

    if ($enemy->getAttack() < 10) {
    // Si ATK < 10 (comme le Goblin), on ignore la défense pour garantir le RNG [random number generation].
    $playerDamage = max(1, $enemyBaseDamage); // Résultat : 1 à 5 dégâts
    } else {
    // Logique pour les ennemis plus forts
    $effectiveDefense = (int)($damageReduction * 0.25);
    $playerDamage = max(1, $enemyBaseDamage - $effectiveDefense);
    }
    // Calcul de la réduction effective (utilise seulement 25% de la DEF totale du joueur)
    $effectiveDefense = (int)($damageReduction * 0.25); // Ex: DEF 12 -> Réduction 3

    // Dégâts finaux reçus par le joueur (min 1 dégât)
    $playerDamage = max(1, $enemyBaseDamage - $effectiveDefense);
    
    // 2. Dégâts infligés par l'ennemi au joueur (CORRECTION CRITIQUE)
    
    // a. Dégâts de base de l'ennemi (aléatoire : 1 à ATK)
    $enemyBaseDamage = $enemy->calculateDamage(); // Utilise Enemy::calculateDamage()
    
    // b. Application de la défense du joueur
    $damageReduction = $playerTotalDefense;

    // c. Dégâts finaux reçus par le joueur (min 1 dégât)
    $playerDamage = max(1, $enemyBaseDamage - (int)($damageReduction / 2));
    
    // --- JOUEUR ATTAQUE ---
    $enemy->setHp($enemy->getHp() - $enemyDamage);
    
    // 3. VÉRIFICATION : Ennemi vaincu ?
    if ($enemy->getHp() <= 0) {
        $enemy->setHp(0); 
        
        // Gain de récompenses
        // CRITIQUE : XP aléatoire implémentée (entre 20 et 40)
        $xpGained = rand(20, 40); 
        
        // L'or reste le montant de base de l'ennemi
        $goldGained = $enemy->getGoldReward();

        $player->setExperience($player->getExperience() + $xpGained);
        $player->setGold($player->getGold() + $goldGained);
        
        // LOGIQUE DE MONTÉE DE NIVEAU
        $levelUpData = $experienceService->checkLevelUp($player);
        
        // CRITIQUE : Stocker les gains exacts en session pour que la vue Result puisse les afficher
        $session->set('goldGained', $goldGained);
        $session->set('xpGained', $xpGained);
        $session->set('last_enemy_name', $enemy->getName()); 
        
        // Stocker si le joueur a des points à dépenser
        $session->set('has_augur_points', $player->getAugurPoints() > 0);

        $em->flush();
        $session->remove('enemy_id'); // Fin du combat

        // Renvoie la réponse de victoire
        return new JsonResponse([
            'status' => 'victory',
            'enemyName' => $enemy->getName(),
            'damageDealt' => $enemyDamage,
            'leveledUp' => $levelUpData['leveledUp'],
            'newLevel' => $levelUpData['newLevel'], 
            // La redirection vers combat_result est maintenue pour afficher les récompenses
            'redirectUrl' => $this->generateUrl('combat_result', ['result' => 'victory'])
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
            'redirectUrl' => $this->generateUrl('combat_result', ['result' => 'defeat'])
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

        // Récupère les données de session nécessaires pour l'affichage
        $xpGained = $session->get('xpGained', 0);
        $goldGained = $session->get('goldGained', 0);
        $lastCombatLevel = $session->get('last_combat_level', 0);
        $lastEnemyName = $session->get('last_enemy_name', 'default');
        
        // CRITIQUE : Récupérer l'ID du lieu actuel stocké dans showLocation (Fix de l'erreur précédente)
        $currentLocationId = $session->get('current_location_id', 1);
        
        // Récupère l'information critique : a-t-on des points à dépenser ?
        $hasAugurPoints = $session->get('has_augur_points', false);
        
        // Nettoie les variables de session après lecture
        $session->remove('xpGained');
        $session->remove('goldGained');
        $session->remove('last_combat_level');
        $session->remove('last_enemy_name');
        $session->remove('has_augur_points'); // Nettoyer le flag après lecture

        
        // Si défaite et que le joueur est mort, on le remet en vie pour l'exploration
        if ($result === 'defeat' && $player && $player->getHp() <= 0) {
             $player->setHp($player->getHpMax());
             $em->flush();
        }
        
        // Si le joueur a des points ET qu'il a gagné, on le redirige directement vers l'écran de progression APRÈS l'affichage rapide des récompenses
        if ($result === 'victory' && $hasAugurPoints) {
            $this->addFlash('info', 'Vous avez gagné des Points d\'Augure ! Vous devez les dépenser avant de continuer.');
            // Redirige vers la page de progression
            return $this->redirectToRoute('progression_level_up');
        }

        // Sinon, affiche simplement la page de résultat classique
        return $this->render('game/result.html.twig', [
            'result' => $result,
            'player' => $player,
            'xpGained' => $xpGained,
            'goldGained' => $goldGained,
            'leveledUp' => $player && $player->getLevel() > $lastCombatLevel,
            'lastEnemyName' => $lastEnemyName,
            'locationId' => $currentLocationId, // CRITIQUE : Transmission de la variable
        ]);
    }
}