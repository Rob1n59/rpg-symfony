// assets/js/explore.js

document.addEventListener("DOMContentLoaded", () => {
    // const mapContainer = document.querySelector(".map-container"); // Cette ligne n'est plus nécessaire car map-container a été supprimé
    const markers = document.querySelectorAll(".map-marker");
    const infobox = document.getElementById("location-infobox");
    const infoboxName = document.getElementById("infobox-name");
    const infoboxDescription = document.getElementById("infobox-description");
    const infoboxDanger = document.getElementById("infobox-danger");
    const exploreLink = document.getElementById("explore-link");
    const closeButton = document.getElementById("infobox-close-button"); // Récupère le nouveau bouton de fermeture

    let activeMarker = null; // Pour garder une trace du marqueur actuellement sélectionné

    markers.forEach(marker => {
        marker.addEventListener("click", (event) => {
            console.log("Marqueur cliqué !", marker.dataset.name);
            event.stopPropagation(); // Empêche le clic de se propager au document

            // Si un marqueur est déjà actif et que ce n'est pas le marqueur cliqué, cache l'ancien
            if (activeMarker && activeMarker !== marker) {
                infobox.classList.remove("active"); // Utilise 'active' pour afficher/cacher
            }

            // Si le même marqueur est cliqué, bascule la visibilité
            if (activeMarker === marker && infobox.classList.contains("active")) {
                infobox.classList.remove("active");
                activeMarker = null;
                return;
            }

            activeMarker = marker;

            // Met à jour le contenu de l'infobulle
            infoboxName.textContent = marker.dataset.name;
            infoboxDescription.textContent = marker.dataset.description;
            infoboxDanger.textContent = "Danger : " + marker.dataset.danger;
            exploreLink.href = `/game/location/${marker.dataset.id}`; // URL d'exploration (adapter si besoin)

            // --- NOUVEAU CALCUL DE POSITIONNEMENT ---
            // Le marqueur est positionné en % par rapport à la carte en background.
            // On a besoin de sa position réelle en pixels sur l'écran pour positionner l'infobulle.
            const markerRect = marker.getBoundingClientRect(); // Position du marqueur par rapport à la fenêtre
            
            // On veut positionner l'infobulle à côté du marqueur.
            // Par exemple, 20px à droite du centre du marqueur.
            let infoboxLeft = markerRect.left + markerRect.width / 2 + 20;
            let infoboxTop = markerRect.top + markerRect.height / 2 - infobox.offsetHeight / 2;

            // Simple ajustement pour que l'infobulle ne sorte pas de l'écran (bords droit/bas)
            // if (infoboxLeft + infobox.offsetWidth > window.innerWidth) {
            //     infoboxLeft = markerRect.left - infobox.offsetWidth - 20; // À gauche du marqueur
            // }
            // if (infoboxTop + infobox.offsetHeight > window.innerHeight) {
            //     infoboxTop = window.innerHeight - infobox.offsetHeight - 10;
            // }
            // if (infoboxTop < 0) {
            //     infoboxTop = 10;
            // }

            // Un peu plus robuste :
            // Garder une marge de 10px autour de l'écran
            const margin = 10;

            // Positionnement initial (par exemple, à droite du marqueur)
            let desiredLeft = markerRect.left + markerRect.width / 2 + margin;
            let desiredTop = markerRect.top + markerRect.height / 2 - infobox.offsetHeight / 2;

            // Vérifier le débordement à droite
            if (desiredLeft + infobox.offsetWidth > window.innerWidth - margin) {
                // Si déborde à droite, essayer de la mettre à gauche du marqueur
                desiredLeft = markerRect.left - infobox.offsetWidth - margin;
                // Si ça déborde encore à gauche (infobox trop grande ou marqueur trop à gauche)
                if (desiredLeft < margin) {
                    desiredLeft = margin; // Le coller au bord gauche
                }
            } else if (desiredLeft < margin) { // Si l'infobox est déjà trop à gauche
                 desiredLeft = margin;
            }

            // Vérifier le débordement en haut
            if (desiredTop < margin) {
                desiredTop = margin;
            }
            // Vérifier le débordement en bas
            if (desiredTop + infobox.offsetHeight > window.innerHeight - margin) {
                desiredTop = window.innerHeight - infobox.offsetHeight - margin;
            }

            infobox.style.left = `${desiredLeft}px`;
            infobox.style.top = `${desiredTop}px`;
            // --- FIN NOUVEAU CALCUL ---
            
            infobox.classList.add("active"); // Utilise la classe 'active' pour afficher l'infobulle
        });
    });

    // Cache l'infobulle si l'on clique n'importe où ailleurs sur la page
    document.addEventListener("click", (event) => {
        // Si le clic n'est pas sur l'infobulle ET n'est pas sur un marqueur
        if (!infobox.contains(event.target) && !event.target.classList.contains("map-marker")) {
            infobox.classList.remove("active");
            activeMarker = null;
        }
    });

    // Gestion du clic sur le nouveau bouton de fermeture
    if (closeButton) { // Vérifie si le bouton existe
        closeButton.addEventListener("click", (event) => {
            event.stopPropagation(); // Empêche le clic de se propager et de rouvrir l'infobulle immédiatement
            infobox.classList.remove("active");
            activeMarker = null;
        });
    }
    
    // Empêche la propagation des clics à l'intérieur de l'infobulle pour ne pas la fermer involontairement
    infobox.addEventListener("click", (event) => {
        event.stopPropagation();
    });
});