<?php
session_start();

// Vérifier si l'utilisateur est connecté et est une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: login.php");
    exit();
}


$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = '';
$error_message = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location = trim($_POST['location']);
    $duration = trim($_POST['duration']);
    $start_date = $_POST['start_date'];
    $is_remote = isset($_POST['is_remote']) ? 1 : 0;
    
 
    if (empty($title) || empty($description) || empty($location) || empty($duration) || empty($start_date)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } else {
       
        $sql = "INSERT INTO internships (company_id, title, description, requirements, location, duration, start_date, is_remote, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?,  1, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssi", 
            $_SESSION['user_id'],
            $title,
            $description,
            $requirements,
            $location,
            $duration,
            $start_date,

            $is_remote
        );
        
        if ($stmt->execute()) {
            $success_message = "L'offre de stage a été publiée avec succès.";
            
            header("refresh:3;url=internships.php");
        } else {
            $error_message = "Erreur lors de la publication de l'offre: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier une offre de stage - Plateforme de Recherche de Stages</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/add_internship.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>StageConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
          
                    
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="add-internship">
                <h2>Publier une nouvelle offre de stage</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="add_internship.php" method="post" class="internship-form">
                    <div class="form-group">
                        <label for="title">Titre du stage *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description du stage *</label>
                        <textarea id="description" name="description" rows="6" required></textarea>
                        <p class="form-help">Décrivez les missions et objectifs du stage.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Prérequis</label>
                        <textarea id="requirements" name="requirements" rows="4"></textarea>
                        <p class="form-help">Compétences et qualifications requises.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Lieu du stage *</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="duration">Durée du stage *</label>
                            <input type="text" id="duration" name="duration" placeholder="Ex: 3 mois" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="start_date">Date de début *</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    
                   
                    
                    <div class="form-group checkbox">
                        <input type="checkbox" id="is_remote" name="is_remote">
                        <label for="is_remote">Stage en télétravail possible</label>
                    </div>
                    
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Publier l'offre</button>
                        <a href="internships.php" class="btn btn-secondary">Annuler</a>
                    </div>

                </form>
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
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Plateforme de Stages - Master 1 TIC. Tous droits réservés.</p>
            </div>
        </footer>
    </div>
</body>
</html>