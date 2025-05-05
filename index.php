<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$featured_sql = "SELECT i.*, c.company_name, cp.logo_path 
                FROM internships i 
                JOIN users u ON i.company_id = u.id 
                JOIN company_profiles cp ON u.id = cp.user_id
                JOIN companies c ON u.id = c.user_id
                WHERE i.is_active = 1 
                ORDER BY i.created_at DESC 
                LIMIT 6";
                
$featured_result = $conn->query($featured_sql);


$unreadMessages = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $messages_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $conn->prepare($messages_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $unreadMessages = array_fill(0, $row['count'], 1); 
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plateforme de Recherche de Stages</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StageConnect</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Accueil</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                            <li><a href="dashboard.php">Tableau de bord</a></li>
                            
                   
                           
                            <li><a href="profile.php">Mon profil</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Tableau de bord</a></li>
                          
                            
                        <?php endif; ?>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                        <li><a href="register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <main>
            <section class="hero">
                <div class="hero-content">
                    <h2>Trouvez le stage idéal pour votre carrière</h2>
                    <p>Connectez-vous avec les meilleures entreprises et découvrez des opportunités de stage passionnantes.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="hero-buttons">
                            <a href="register.php?type=student" class="btn btn-primary">Je suis étudiant</a>
                            <a href="register.php?type=company" class="btn btn-secondary">Je suis une entreprise</a>
                        </div>
                    <?php elseif ($_SESSION['user_type'] == 'student'): ?>
                        <div class="hero-buttons">
                            <a href="internships.php" class="btn btn-primary">Voir les offres</a>
                        </div>
                    <?php else: ?>
                        <div class="hero-buttons">
                            <a href="internships.php?action=new" class="btn btn-primary">Publier une offre</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="features">
                <h2>Comment ça marche</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🎓</div>
                        <h3>Pour les étudiants</h3>
                        <ul>
                            <li>Créez un profil professionnel</li>
                            <li>Parcourez les offres de stage</li>
                            <li>Postulez en quelques clics</li>
                            <li>Suivez l'état de vos candidatures</li>
                            <li>Recevez des alertes personnalisées</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🏢</div>
                        <h3>Pour les entreprises</h3>
                        <ul>
                            <li>Présentez votre entreprise</li>
                            <li>Publiez des offres de stage</li>
                            <li>Gérez les candidatures reçues</li>
                            <li>Communiquez avec les candidats</li>
                            <li>Trouvez les talents de demain</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="featured-internships">
                <h2>Offres de stage récentes</h2>
                <div class="internships-grid">
                    <?php if ($featured_result && $featured_result->num_rows > 0): ?>
                        <?php while ($internship = $featured_result->fetch_assoc()): ?>
                            <div class="internship-card">
                                <div class="internship-logo">
                                    <?php if (!empty($internship['logo_path'])): ?>
                                        <img src="<?php echo $internship['logo_path']; ?>" alt="<?php echo $internship['company_name']; ?>">
                                    <?php else: ?>
                                        <div class="placeholder-logo"><?php echo substr($internship['company_name'], 0, 1); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="internship-content">
                                    <h3><?php echo htmlspecialchars($internship['title']); ?></h3>
                                    <h4><?php echo htmlspecialchars($internship['company_name']); ?></h4>
                                    <p class="internship-location">📍 <?php echo htmlspecialchars($internship['location']); ?></p>
                                    <p class="internship-duration">⏱️ <?php echo htmlspecialchars($internship['duration']); ?></p>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                                        <a href="student/internship.php?id=<?php echo $internship['id']; ?>" class="btn btn-secondary">Voir détails</a>
                                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                                        <a href="login.php" class="btn btn-secondary">Connectez-vous pour postuler</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-internships">Aucune offre de stage disponible pour le moment.</p>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                    <div class="view-all">
                        <a href="internships.php" class="btn btn-primary">Voir toutes les offres</a>
                    </div>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div class="view-all">
                        <a href="register.php" class="btn btn-primary">S'inscrire pour voir plus</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="testimonials">
                <h2>Ce que disent nos utilisateurs</h2>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"Grâce à cette plateforme, j'ai trouvé un stage dans une entreprise qui correspondait parfaitement à mes attentes. L'interface est intuitive et le processus de candidature très simple."</p>
                        </div>
                        <div class="testimonial-author">
                            <p><strong>Khedara Issam Eddine</strong> - Étudiante en informatique</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"En tant qu'entreprise, nous avons pu trouver des stagiaires talentueux et motivés. La plateforme nous permet de gérer efficacement les candidatures et de communiquer facilement avec les étudiants."</p>
                        </div>
                        <div class="testimonial-author">
                            <p><strong>Souahlia Yacine</strong> - Responsable RH, TechSolutions</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta">
                <h2>Prêt à commencer ?</h2>
                <p>Rejoignez notre plateforme et découvrez toutes les opportunités qui s'offrent à vous.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary">Créer un compte</a>
                        <a href="login.php" class="btn btn-secondary">Se connecter</a>
                    </div>
                <?php endif; ?>
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
                        <li><a href="terms.php" >Conditions d'utilisation</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Plateforme de Stages - Master 1 TIC. Tous droits réservés.</p>
            </div>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>