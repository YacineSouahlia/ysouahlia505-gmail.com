<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - Plateforme de Recherche de Stages</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .about-content {
            padding: 20px;
            text-align: justify; 
        }

        .about-section {
            margin-bottom: 30px;
        }

        .about-section h2 {
            margin-bottom: 15px;
            color: #333;
        }

        .team-members {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .team-member {
            width: 250px;
            margin: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .team-member img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .team-member h3 {
            margin-bottom: 5px;
            color: #333;
        }

        .team-member p {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>StageConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                            <li><a href="dashboard.php">Tableau de bord</a></li>
                           
                         
                            <li><a href="profile.php">Mon profil</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Tableau de bord</a></li>
                            
                          
                            
                        <?php endif; ?>
                        <li><a href="about.php" class="active">À propos</a></li> 
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                        <li><a href="register.php">Inscription</a></li>
                    <?php endif; ?>
                    
                    
                </ul>
            </nav>
        </header>

        <main>
            <section class="about-content">
                <div class="about-section">
                    <h2>Notre mission</h2>
                    <p>
                        Notre mission est de simplifier la recherche de stages pour les étudiants et de faciliter le recrutement de stagiaires pour les entreprises. Nous croyons que les stages sont une étape cruciale dans le développement professionnel des étudiants et une excellente opportunité pour les entreprises de découvrir de nouveaux talents.
                    </p>
                </div>

                <div class="about-section">
                    <h2>Notre vision</h2>
                    <p>
                        Nous aspirons à devenir la plateforme de référence en matière de stages, reconnue pour sa simplicité d'utilisation, la qualité de ses offres et la pertinence de ses services. Nous voulons créer une communauté dynamique où les étudiants et les entreprises peuvent se rencontrer et collaborer pour construire l'avenir.
                    </p>
                </div>

                <div class="about-section">
                    <h2>Notre équipe</h2>
                    <div class="team-members">
                        <div class="team-member">
                            <img src="images/team/john_doe.jpg" alt="Souahlia Yacine"> 
                            <h3>Souahlia Yacine</h3>
                            <p>Fondateur et PDG</p>
                        </div>
                        <div class="team-member">
                            <img src="images/team/jane_smith.jpg" alt="Khedara Issam Eddine">
                            <h3>Khedara Issam Eddine</h3>
                            <p>Directrice Technique</p>
                        </div>
                        <div class="team-member">
                            <img src="images/team/peter_jones.jpg" alt="Souahlia Yacine">
                            <h3>Souahlia Yacine</h3>
                            <p>Responsable Marketing</p>
                        </div>
                        <div class="team-member">
                            <img src="images/team/sarah_williams.jpg" alt="Khedara Issam Eddine">
                            <h3>Khedara Issam Eddine</h3>
                            <p>Chargée de Recrutement</p>
                        </div>
                    </div>
                </div>

                <div class="about-section">
                    <h2>Nos valeurs</h2>
                    <ul>
                        <li><strong>Innovation :</strong> Nous cherchons constamment de nouvelles façons d'améliorer notre plateforme et de répondre aux besoins de nos utilisateurs.</li>
                        <li><strong>Qualité :</strong> Nous nous engageons à offrir des services de haute qualité et des offres de stage pertinentes et vérifiées.</li>
                        <li><strong>Transparence :</strong> Nous communiquons ouvertement avec nos utilisateurs et nous nous efforçons d'être clairs et honnêtes dans toutes nos interactions.</li>
                        <li><strong>Collaboration :</strong> Nous croyons en la force du travail d'équipe et nous encourageons la collaboration entre les étudiants et les entreprises.</li>
                    </ul>
                </div>
            </section>
        </main>

        <footer>
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Plateforme de Stages</h3>
                    <p>Connecter les étudiants et les entreprises pour créer des opportunités professionnelles enrichissantes.</p>
                </div>
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li><a href="login.php">Connexion</a></li>
                            <li><a href="register.php">Inscription</a></li>
                        <?php endif; ?>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="privacy.php">Confidentialité</a></li>
                        <li><a href="terms.php">Conditions d'utilisation</a></li>
                        
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Plateforme de Stages - Master 1 TIC. Tous droits réservés.</p>
            </div>
        </footer>
    </div>
</body>
</html>