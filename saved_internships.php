<?php

session_start();

// --- Authentication & Authorization ---
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Database Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "internship_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Connection failed: Please try again later.");
}
$conn->set_charset("utf8mb4");

// --- Fetch User Information  ---
$user = null;
$stmt_user = $conn->prepare("SELECT first_name, last_name, user_type FROM users WHERE id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $stmt_user->close();
} else {
    error_log("Prepare failed (user fetch): " . $conn->error);
    die("Erreur lors de la récupération des informations utilisateur.");
}

// If user not found or not a student
if (!$user) {
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit;
}
if ($user['user_type'] !== 'student') {
    header("Location: dashboard.php?error=access_denied");
    exit;
}

// --- Fetch Saved Internships for the Student ---
$savedInternships = [];
$fetch_error = null; // Initialize fetch error
// JOIN condition remains the same
$stmt_saved = $conn->prepare("
    SELECT
        i.id AS internship_id,
        i.title,
        i.location,
        i.duration,
        i.is_active,
        cp.company_name
    FROM saved_internships si
    JOIN internships i ON si.internship_id = i.id
    JOIN company_profiles cp ON i.company_id = cp.user_id /* Make sure company_profiles.user_id is the FK to users.id for the company */
    WHERE si.student_id = ?
    ORDER BY si.saved_at DESC
");

if ($stmt_saved) {
    $stmt_saved->bind_param("i", $user_id);
    if ($stmt_saved->execute()) {
        $result_saved = $stmt_saved->get_result();
        while ($row = $result_saved->fetch_assoc()) {
            $savedInternships[] = $row;
        }
    } else {
        error_log("Execute failed (saved internships fetch): " . $stmt_saved->error);
        $fetch_error = "Une erreur s'est produite lors de la récupération de vos stages sauvegardés.";
    }
    $stmt_saved->close();
} else {
    error_log("Prepare failed (saved internships fetch): " . $conn->error);
    $fetch_error = "Une erreur technique s'est produite. Veuillez réessayer plus tard.";
}

$conn->close();

// --- Handle Status/Error Messages from Redirects ---
$status_message = '';
$error_message = $fetch_error ?? '';

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'removed') {
        $status_message = "Stage retiré de vos sauvegardes avec succès.";
    }
}
if (isset($_GET['error'])) {
     if ($_GET['error'] == 'not_found') {
        $error_message = "Le stage demandé n'a pas été trouvé dans vos sauvegardes ou a déjà été retiré.";
    } elseif ($_GET['error'] == 'delete_failed') {
        $error_message = "Impossible de retirer le stage. Veuillez réessayer.";
    } elseif ($_GET['error'] == 'db_error' || $_GET['error'] == 'db_prepare_error' || $_GET['error'] == 'db_connection') {
         $error_message = "Erreur technique. Veuillez contacter l'administrateur.";
    } elseif ($_GET['error'] == 'invalid_request' || $_GET['error'] == 'missing_id') {
         $error_message = "Requête invalide.";
    } elseif ($_GET['error'] == 'not_logged_in') {
         $error_message = "Veuillez vous connecter pour effectuer cette action.";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Stages Sauvegardés - StageConnect</title>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Basic styles */
        body { background-color: #f4f7f6; font-family: sans-serif; line-height: 1.6; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        main { flex: 1; }
        .page-container { max-width: 1200px;  margin: 2rem auto; padding: 0 1rem; }
        .page-container h2 { margin-bottom: 1.5rem; color: #333; font-weight: 600; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 0.75rem; }

        /* Message Styles */
        .alert { padding: 0.8rem 1.2rem; margin-bottom: 1.5rem; border: 1px solid transparent; border-radius: 5px; font-size: 0.95rem; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }

        /* Grid Layout for Internship List */
        .internships-list {
            display: grid;
            /* Create responsive columns: min 300px, max 1fr  */
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem; 
            padding: 0; 
            list-style: none; 
        }

        /* Individual Card Styles */
        .internship-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            display: flex; /* Use flexbox for internal layout */
            flex-direction: column; /* Stack content vertically */
            gap: 0.5rem; /* Space between elements inside card */
            /* Remove margin/border from previous single-column layout */
            margin-bottom: 0;
            border-bottom: none;
        }

        .internship-card h4 { margin: 0 0 0.4rem 0; font-size: 1.15rem;  font-weight: 600; }
        .internship-card h4 a { color: #333; text-decoration: none; }
        .internship-card h4 a:hover { color: #0056b3; }
        .internship-card p { margin: 0;  color: #666; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;}
        .internship-card p i { color: #888; width: 1.1em; text-align: center;}

        /* Card Actions */
        .card-actions {
            margin-top: auto; /* Push actions to the bottom */
            padding-top: 1rem; /* Add space above buttons */
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            border-top: 1px solid #eee; /* Separator line */
        }
        .btn-details, .btn-apply, .btn-remove { padding: 0.4rem 0.8rem; /* Smaller buttons */ font-size: 0.85rem; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; transition: background-color 0.2s ease, box-shadow 0.2s ease; display: inline-flex; align-items: center; gap: 0.4rem; }
        .btn-details { background-color: #6c757d; color: white;}
        .btn-details:hover { background-color: #5a6268; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}
        .btn-apply { background-color: #007bff; color: white;}
        .btn-apply:hover { background-color: #0056b3; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}
        .btn-remove { background-color: #dc3545; color: white; }
        .btn-remove:hover { background-color: #c82333; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}
        .btn-inactive { background-color: #adb5bd; color: #fff; cursor: not-allowed; opacity: 0.7; }

        /* Internship Status (Inactive) */
        .internship-status { font-size: 0.85rem; color: #aeaeae; /* Less harsh color */ font-weight: 500; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.3rem;}
        .internship-status i { color: #aeaeae; }

        /* Form Styling for Button */
        .card-actions form { display: inline; margin: 0; padding: 0; }

        /* No Data Message */
        .no-data {
             background-color: #fff;
             padding: 2rem;
             border-radius: 8px;
             box-shadow: 0 4px 8px rgba(0,0,0,0.05);
             color: #777;
             font-style: italic;
             text-align: center;
        }
        .no-data a { color: #007bff; text-decoration: none; font-weight: 500;}
        .no-data a:hover { text-decoration: underline; }

        /* Header & Footer Styles  */
        header { background-color: #ffffff; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;}
        .logo h1 a { color: #0056b3; text-decoration: none; font-weight: bold; font-size: 1.5rem;}
        nav ul { list-style: none; padding: 0; margin: 0; display: flex; gap: 1rem; flex-wrap: wrap; }
        nav a { text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 0.8rem; border-radius: 4px; transition: background-color 0.2s ease;}
        nav a.active, nav a:hover { color: #007bff; background-color: #e9ecef;}

        footer { background-color: #343a40; color: #f8f9fa; padding: 3rem 1rem 1rem 1rem; margin-top: auto; font-size: 0.9rem; }
        .footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto 2rem auto; }
        .footer-logo h2 { color: #ffffff; margin-bottom: 0.5rem; }
        .footer-logo p { color: #adb5bd; }
        .footer-links h3, .footer-contact h3 { color: #ffffff; margin-bottom: 1rem; font-size: 1.1rem; }
        .footer-links ul { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a { color: #adb5bd; text-decoration: none; transition: color 0.2s ease;}
        .footer-links a:hover { color: #ffffff; text-decoration: underline; }
        .footer-contact p { color: #adb5bd; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;}
        .footer-contact i { color: #00aaff; width: 1em; text-align: center;}
        .social-media { margin-top: 1rem; }
        .social-media a { color: #adb5bd; margin-right: 1rem; font-size: 1.2rem; transition: color 0.2s ease;}
        .social-media a:hover { color: #ffffff; }
        .footer-bottom { text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #495057; color: #adb5bd; }

    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1><a href="index.php">StageConnect</a></h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="page-container">
        <h2>Mes Stages Sauvegardés</h2>

        <!-- Display Status/Error Messages -->
        <?php if (!empty($status_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                 <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Check if there are internships to display -->
        <?php if (empty($fetch_error) && count($savedInternships) > 0): ?>
            <div class="internships-list">
                <?php foreach ($savedInternships as $internship): ?>
                <div class="internship-card">
                    <!-- Card Content -->
                    <div>
                        <h4><a href="internship-details.php?id=<?php echo $internship['internship_id']; ?>"><?php echo htmlspecialchars($internship['title']); ?></a></h4>
                        <p class="company"><i class="fas fa-building"></i> <?php echo $internship['company_name']; ?></p>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($internship['location']); ?></p>
                        <?php if (!empty($internship['duration'])): ?>
                        <p class="duration"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration']); ?></p>
                        <?php endif; ?>

                        <?php if ($internship['is_active'] == 0): ?>
                            <p class="internship-status"><i class="fas fa-info-circle"></i> Ce stage n'est plus actif.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Card Actions  -->
                    <div class="card-actions">
                        <a href="internship-details.php?id=<?php echo $internship['internship_id']; ?>" class="btn-details"><i class="fas fa-eye"></i> Détails</a>

                        <?php if ($internship['is_active'] == 1): ?>
                            <a href="apply.php?id=<?php echo $internship['internship_id']; ?>" class="btn-apply"><i class="fas fa-paper-plane"></i> Postuler</a>
                        <?php else: ?>
                             <button class="btn-apply btn-inactive" disabled><i class="fas fa-paper-plane"></i> Postuler</button>
                        <?php endif; ?>

                        <!-- Form for Removing Saved Internship -->
                        <form action="remove_saved_internship.php" method="POST">
                            <input type="hidden" name="internship_id" value="<?php echo $internship['internship_id']; ?>">
                            
                        </form>
                        <!-- End Form -->
                    </div>
                </div> <!-- End internship-card -->
                <?php endforeach; ?>
            </div> <!-- End internships-list -->
        <?php elseif (empty($fetch_error) && count($savedInternships) == 0): ?>
            <p class="no-data">Vous n'avez sauvegardé aucun stage pour le moment. <a href="internships.php">Parcourir les stages disponibles</a>.</p>
        <?php endif; ?>
        <?php // Fetch error is handled by the alert display at the top ?>

    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <h2><a href="index.php" style="color: #fff; text-decoration: none;">StageConnect</a></h2>
                <p>Connecter les étudiants aux meilleures opportunités de stage.</p>
            </div>
            <div class="footer-links">
                <h3>Liens utiles</h3>
                <ul>
                    <li><a href="about.php">À propos</a></li>
                    <li><a href="privacy.php">Confidentialité</a></li>
                    <li><a href="terms.php">Conditions d'utilisation</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars("ysouahlia505@gmail.com"); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars("+213 668 742 584"); ?></p>
                <div class="social-media">
                    <a href="https://www.facebook.com/yacine.souahlia.1/" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/yacine_souahlia/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/yacinesouahlia/" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date("Y"); ?> StageConnect. Tous droits réservés.</p>
        </div>
    </footer>

</body>
</html>
