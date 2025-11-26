// assets/bootstrap.js

// Importation de votre feuille de style principale
// Assurez-vous que le chemin est correct (app.scss ou app.css)
import './styles/app.css'; 

// Importation et démarrage de l'application Stimulus
// C'est la ligne qui doit changer et la façon de démarrer l'app
import { startStimulusApp } from '@symfony/stimulus-bridge';

const app = startStimulusApp(
    // Le require.context permet de découvrir automatiquement les contrôleurs
    require.context('./controllers', true, /\.js$/) 
);

// Vous pouvez enregistrer des contrôleurs Stimulus personnalisés ou tiers ici
// app.register('some_controller_name', SomeImportedController);