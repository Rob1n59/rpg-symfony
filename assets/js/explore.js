// assets/js/explore.js

document.addEventListener("DOMContentLoaded", () => {
    
    // =========================================================================
    // SECTION 1: LOGIQUE DE LA CARTE (explore.html.twig)
    // S'exécute si les marqueurs et l'infobulle de la carte sont trouvés.
    // =========================================================================
    
    const markers = document.querySelectorAll(".map-marker");
    const infobox = document.getElementById("location-infobox");
    
    if (markers.length > 0 && infobox) {
        
        const infoboxName = document.getElementById("infobox-name");
        const infoboxDescription = document.getElementById("infobox-description");
        const infoboxDanger = document.getElementById("infobox-danger");
        const exploreLink = document.getElementById("explore-link");
        const closeButton = document.getElementById("infobox-close-button");
        
        let activeMarker = null;

        markers.forEach(marker => {
            marker.addEventListener("click", (event) => {
                event.stopPropagation();

                // Gestion de l'affichage/masquage
                if (activeMarker && activeMarker !== marker) {
                    infobox.classList.remove("active");
                }
                if (activeMarker === marker && infobox.classList.contains("active")) {
                    infobox.classList.remove("active");
                    activeMarker = null;
                    return;
                }

                activeMarker = marker;

                // Mise à jour du contenu
                infoboxName.textContent = marker.dataset.name;
                infoboxDescription.textContent = marker.dataset.description;
                infoboxDanger.textContent = "Danger : " + marker.dataset.danger;
                infoboxDanger.style.color = (marker.dataset.danger.includes("Très Élevé") || marker.dataset.danger.includes("Élevé")) ? 'red' : 'lightgreen';
                exploreLink.href = `/game/location/${marker.dataset.id}/travel`;

                // --- CALCUL DE POSITIONNEMENT ---
                const markerRect = marker.getBoundingClientRect();
                const margin = 10;
                
                // Mettre l'infobox active et cachée pour obtenir ses dimensions avant de la positionner
                infobox.style.visibility = 'hidden';
                infobox.classList.add("active");
                
                let desiredLeft = markerRect.left + markerRect.width / 2 + margin;
                let desiredTop = markerRect.top + markerRect.height / 2 - infobox.offsetHeight / 2;

                // Vérifications de débordement (droite, gauche, haut, bas)
                if (desiredLeft + infobox.offsetWidth > window.innerWidth - margin) {
                    desiredLeft = markerRect.left - infobox.offsetWidth - margin;
                    if (desiredLeft < margin) {
                        desiredLeft = margin;
                    }
                } else if (desiredLeft < margin) {
                    desiredLeft = margin;
                }
                if (desiredTop < margin) {
                    desiredTop = margin;
                }
                if (desiredTop + infobox.offsetHeight > window.innerHeight - margin) {
                    desiredTop = window.innerHeight - infobox.offsetHeight - margin;
                }

                infobox.style.left = `${desiredLeft}px`;
                infobox.style.top = `${desiredTop}px`;
                infobox.style.visibility = 'visible';
                // --- FIN CALCUL ---
            });
        });

        // Fermeture si clic en dehors
        document.addEventListener("click", (event) => {
            if (!infobox.contains(event.target) && !event.target.classList.contains("map-marker")) {
                infobox.classList.remove("active");
                activeMarker = null;
            }
        });

        // Gestion du clic sur le bouton de fermeture
        if (closeButton) {
            closeButton.addEventListener("click", (event) => {
                event.stopPropagation();
                infobox.classList.remove("active");
                activeMarker = null;
            });
        }
        
        // Empêche la propagation des clics à l'intérieur de l'infobulle
        infobox.addEventListener("click", (event) => {
            event.stopPropagation();
        });
    }

    // =========================================================================
    // SECTION 2: LOGIQUE DE LA SCÈNE (location_show.html.twig)
    // Utilise la délégation d'événements pour une meilleure fiabilité après rechargement.
    // =========================================================================

    const sceneGameContainer = document.querySelector('.game-container');
    const actionButtonsContainer = document.querySelector('.js-action-list');

    if (sceneGameContainer && actionButtonsContainer) {
        
        // Sélecteurs d'éléments de la scène
        const mainGameArea = document.querySelector('.main-game-area');
        const gamePageBackground = document.querySelector('.game-page-background');
        const logsContent = document.querySelector('.game-logs-panel .logs-content');
        const lootChestOption = document.querySelector('.js-loot-chest-option');
        const goBackOption = document.querySelector('.js-go-back-option');
        
        // Récupérer l'ID du lieu à partir du data-attribut du conteneur (solution fiable)
        const locationId = parseInt(sceneGameContainer.dataset.locationId);

        if (isNaN(locationId)) {
            console.error("ERREUR CRITIQUE: Location ID n'a pas pu être récupéré de .game-container.");
            return; 
        }

        // --- Fonctions utilitaires de la scène ---

        function updateSceneImages(mainImageUrl, blurredBgImageUrl) {
            mainGameArea.style.backgroundImage = `url('${mainImageUrl}')`;
            gamePageBackground.style.setProperty('--blurred-bg-image', `url('${blurredBgImageUrl}')`);
        }

        function updateLootChestButtonVisibility(show) {
            if (lootChestOption) {
                lootChestOption.style.display = show ? 'block' : 'none';
            }
        }

        function updateGoBackButtonVisibility(show) {
            if (goBackOption) {
                goBackOption.style.display = show ? 'block' : 'none';
            }
        }

        function handleAjaxOption(url) {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => { 
                        throw new Error(errorData.message || response.statusText); 
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    updateSceneImages(data.mainImageUrl, data.blurredBgImageUrl);
                    updateLootChestButtonVisibility(data.showLootChestOption);
                    updateGoBackButtonVisibility(data.canGoBack);
                } else {
                    if (logsContent) {
                        logsContent.innerHTML += `<p class="log-entry alert-danger">Erreur: ${data.message}</p>`;
                    }
                }
            })
            .catch(error => {
                console.error('Erreur Fetch:', error);
                if (logsContent) {
                    logsContent.innerHTML += `<p class="log-entry alert-danger">Erreur réseau ou du serveur: ${error.message}</p>`;
                }
            });
        }
        function handleStatsOption(url, optionId) {
    fetch(url, {
        method: 'GET', // Le contrôleur PHP handleOption est appelé ici
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => {
        // ... (gestion de la redirection vers le combat si nécessaire)
        if (!response.ok) {
            if (response.status === 302 || response.redirected) {
                 window.location.href = response.url; // Suit la redirection (pour le combat ou autre)
                 return;
            }
            return response.json().then(errorData => { throw new Error(errorData.message || response.statusText); });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'update') {
            // 1. Mettre à jour les stats du joueur dans la barre latérale
            document.querySelector('[data-stat-name="gold"]').innerHTML = `<strong>Or:</strong> ${data.playerStats.gold}`;
            
            // 2. Ajouter le message au log
            if (logsContent) {
                logsContent.innerHTML += `<p class="log-entry alert-${data.flashType}">${data.message}</p>`;
                logsContent.scrollTop = logsContent.scrollHeight; // Scroll vers le bas
            }
        }
    })
    .catch(error => {
        console.error('Erreur Fetch Stats:', error);
        if (logsContent) {
            logsContent.innerHTML += `<p class="log-entry alert-danger">Erreur: ${error.message}</p>`;
        }
    });
}
        
        // --- DÉLÉGATION D'ÉVÉNEMENTS : Gère les clics AJAX et non-AJAX ---
       // assets/js/explore.js - Remplacer le gestionnaire d'événements existant

// --- DÉLÉGATION D'ÉVÉNEMENTS : Gère les clics AJAX et non-AJAX ---
actionButtonsContainer.addEventListener('click', function(event) {
    const target = event.target.closest('a'); 
    
    // Vérifier si nous avons cliqué sur un lien d'action valide
    if (target && target.dataset.optionId) {
        event.preventDefault(); // On intercepte TOUS les clics valides ici
        
        const optionId = parseInt(target.dataset.optionId);
        let url = target.href; // L'URL de base est toujours nécessaire

        // 1. Logique AJAX pour le changement de scène (101, 105)
        if (optionId === 101 || optionId === 105) {
            
            // Construction de l'URL pour les routes AJAX spécifiques (POST)
            if (optionId === 101) { // Continuer le chemin
                url = `/game/next_scene_variant/${locationId}`;
            } else if (optionId === 105) { // Revenir en arrière
                url = `/game/previous_scene_variant/${locationId}`;
            }

            handleAjaxOption(url); // Gère le changement d'image et la visibilité des boutons
            return;
        }

        // 2. Logique AJAX pour la mise à jour des stats (102)
        else if (optionId === 102) {
            // L'URL est déjà target.href, nous passons à la fonction qui gère les stats.
            handleStatsOption(url, optionId);
            return;
        }

        // 3. Logique non-AJAX (103, 104) et actions non gérées par AJAX
        // Pour ces options, nous laissons le navigateur suivre le lien par défaut.
        // Puisque nous avons fait event.preventDefault() au début, nous le refaisons ici :
        window.location.href = url;
    }
});
    }

});