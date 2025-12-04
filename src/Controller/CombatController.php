<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Enemy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse; // <-- Ajout CRITIQUE pour l'AJAX
use Symfony\Component\Routing\Annotation\Route;

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
            // Ceci suppose que votre entité Enemy a une méthode setHpMax
            $enemy->setHpMax($enemy->getHp()); 
            $em->flush();
        }

        return $this->render('game/combat.html.twig', [
            'player' => $player,
            'enemy' => $enemy
        ]);
    }

    /**
     * Route gérant un tour de combat (appelée par AJAX).
     */
    #[Route('/combat/attack', name: 'combat_attack', methods: ['POST'])]
    public function attack(
        SessionInterface $session,
        EntityManagerInterface $em
    ): JsonResponse { // <-- Changement du type de retour en JsonResponse
        $playerId = $session->get('player_id');
        $enemyId = $session->get('enemy_id');
        
        $player = $em->getRepository(Player::class)->find($playerId);
        $enemy = $em->getRepository(Enemy::class)->find($enemyId);

        // Vérifications essentielles (combat déjà terminé ou entités invalides)
        if (!$player || !$enemy || $player->getHp() <= 0 || $enemy->getHp() <= 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Combat déjà terminé ou entités invalides.'], 400);
        }
        
        // --- CALCUL DES DÉGÂTS UTILISANT LES STATS TOTALES ---
        // Utilisation des méthodes de Player pour inclure les bonus d'équipement
        $playerTotalAttack = $player->calculateTotalAttack();
        $playerTotalDefense = $player->calculateTotalDefense();

        // 1. Dégâts infligés par le joueur à l'ennemi
        // Dégâts minimum garantis de 1
        $enemyDamage = max(1, $playerTotalAttack - $enemy->getDefense());
        
        // 2. Dégâts infligés par l'ennemi au joueur
        // Dégâts minimum garantis de 1
        $playerDamage = max(1, $enemy->getAttack() - $playerTotalDefense);
        
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
            
            $em->flush();
            $session->remove('enemy_id'); // Fin du combat

            // Renvoie la réponse de victoire avec l'URL de redirection
            return new JsonResponse([
                'status' => 'victory',
                'enemyName' => $enemy->getName(),
                'xpGained' => $xpGained,
                'goldGained' => $goldGained,
                'damageDealt' => $enemyDamage, // Utile pour afficher le coup fatal
                'redirectUrl' => $this->generateUrl('combat_result', ['result' => 'victory'])
            ]);
        }

        // --- ENNEMI ATTAQUE (Seulement si l'ennemi est encore en vie) ---
        $player->setHp($player->getHp() - $playerDamage);

        // 4. VÉRIFICATION : Joueur vaincu ?
        if ($player->getHp() <= 0) {
            $player->setHp(0); 
            $em->flush();
            $session->remove('enemy_id'); // Fin du combat
            
            // Renvoie la réponse de défaite avec l'URL de redirection
            return new JsonResponse([
                'status' => 'defeat',
                'enemyName' => $enemy->getName(),
                'damageTaken' => $playerDamage, // Utile pour afficher le coup fatal
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
        // Logique de fuite simple
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
        $player = $em->getRepository(Player::class)->find($session->get('player_id'));
        
        return $this->render('game/result.html.twig', [
            'result' => $result, // 'victory' ou 'defeat'
            'player' => $player,
        ]);
    }
}