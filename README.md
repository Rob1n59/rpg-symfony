⚔️ Mythfall - RPG Symfony
Mythfall est un jeu de rôle (RPG) textuel développé avec le framework Symfony 6.4. Ce projet a été réalisé dans le cadre du BTS SIO pour illustrer la gestion d'une logique métier complexe, la persistance de données et une interface utilisateur dynamique.

(Note : Pense à vérifier que le chemin de ton image est correct sur GitHub)


Objectif : Créer une application web complète gérant des entités liées, un système de progression et une interface interactive.
🎮 Fonctionnalités du Jeu
Exploration narrative : Un système de scènes avec des choix multiples menant à différentes issues.

Système de Combat : Affrontements contre des ennemis avec calcul de dégâts (Attaque - Défense).

Évolution du Héros : Gain d'expérience (XP), montée de niveau et statistiques améliorables.

Inventaire : Collecte et utilisation d'objets (équipements).

Persistance : -Sauvegarde automatique de l'état de la partie en base de données.
              -Pas de sauvegarde du personnage

🛠️ Stack Technique
Backend : PHP 8.1+ & Symfony 6.4 (LTS)

Base de données : Doctrine ORM (Compatible MySQL / SQLite)

Templating : Twig & Symfony UX (Turbo/Stimulus) pour la réactivité

Outils : Composer, Symfony CLI

🚀 Installation Express (pour testeurs)
Pour lancer le projet sur votre machine, suivez ces étapes :

1. Prérequis
PHP 8.1 ou supérieur

Composer installé

Symfony CLI (recommandé)

2. Installation
Bash

# 1. Cloner le projet
git clone https://github.com/Rob1n59/rpg-symfony.git
cd rpg-symfony

# 2. Installer les dépendances
composer install

# 3. Configurer la base de données
# Par défaut, le projet est prêt pour SQLite (simple fichier).
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Charger les données du jeu (Scènes, Ennemis, Objets)
php bin/console doctrine:fixtures:load --no-interaction
3. Lancement
Bash

symfony server:start
Accédez ensuite au jeu sur : http://localhost:8000

📂 Structure des Entités (Modèle de données)
Le projet repose sur une architecture de données solide :

Player : Centralise les statistiques et la progression.

Scene & SceneOption : Gèrent l'arbre narratif.

Enemy : Définit le bestiaire.

Item & PlayerItem : Gèrent la boutique et l'inventaire.

Développé par Robin – Projet de formation BTS SIO
