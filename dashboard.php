<?php

session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "internship_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error); // Log error
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4"); // Good practice


// --- Fetch User Information ---
$stmt_user = $conn->prepare("SELECT first_name, last_name, email, user_type FROM users WHERE id = ?");
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

if (!$user) {
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit;
}


// --- Initialize variables ---
$profile = null;
$applications = []; // Student sent applications
$savedInternships = []; // Student saved internships
$totalActiveInternships = 0; // Total internships on platform
$receivedApplicationsCount = 0; // Company received applications
$postedInternshipsCount = 0; // Company posted internships
$unreadMessages = [];
$notifications = []; // Keep fetching notifications as in original code

// --- Fetch Data Based on User Type ---
if ($user['user_type'] == 'student') {
    // Fetch Student Profile
    $stmt_profile = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    if ($stmt_profile) {
        $stmt_profile->bind_param("i", $user_id);
        $stmt_profile->execute();
        $result_profile = $stmt_profile->get_result();
        $profile = $result_profile->fetch_assoc();
        $stmt_profile->close();
    } else { error_log("Prepare failed (student profile fetch): " . $conn->error); }

    // Fetch Student Sent Applications
    $stmt_app = $conn->prepare("
        SELECT a.id, a.internship_id, a.applied_at, a.status, /* Added id, internship_id */
               i.title, i.location, i.duration, cp.company_name
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN company_profiles cp ON i.company_id = cp.user_id
        WHERE a.student_id = ?
        ORDER BY a.applied_at DESC
    ");
    if ($stmt_app) {
        $stmt_app->bind_param("i", $user_id);
        $stmt_app->execute();
        $result_app = $stmt_app->get_result();
        while ($row = $result_app->fetch_assoc()) { $applications[] = $row; }
        $stmt_app->close();
    } else { error_log("Prepare failed (student applications fetch): " . $conn->error); }

    // Fetch Student Saved Internships
    $stmt_saved = $conn->prepare("
        SELECT i.id, i.title, i.location, i.duration, cp.company_name
        FROM saved_internships si
        JOIN internships i ON si.internship_id = i.id
        JOIN company_profiles cp ON i.company_id = cp.user_id
        WHERE si.student_id = ?
        ORDER BY si.saved_at DESC
    ");
     if ($stmt_saved) {
        $stmt_saved->bind_param("i", $user_id);
        $stmt_saved->execute();
        $result_saved = $stmt_saved->get_result();
        while ($row = $result_saved->fetch_assoc()) { $savedInternships[] = $row; }
        $stmt_saved->close();
    } else { error_log("Prepare failed (saved internships fetch): " . $conn->error); }

    // Fetch Total Active Internships (for student's 4th card)
    $stmt_total_count = $conn->prepare("SELECT COUNT(*) as count FROM internships WHERE is_active = 1");
    if ($stmt_total_count) {
        if ($stmt_total_count->execute()) {
            $result_total_count = $stmt_total_count->get_result();
            $count_data = $result_total_count->fetch_assoc();
            $totalActiveInternships = $count_data['count'] ?? 0;
        } else { error_log("Execute failed (total internships count): " . $stmt_total_count->error); }
        $stmt_total_count->close();
    } else { error_log("Prepare failed (total internships count): " . $conn->error); }


} elseif ($user['user_type'] == 'company') {
    // Fetch Company Received Applications Count
    $stmt_received_count = $conn->prepare("
        SELECT COUNT(a.id) as count
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        WHERE i.company_id = ?
    ");
     if ($stmt_received_count) {
        $stmt_received_count->bind_param("i", $user_id);
        if($stmt_received_count->execute()){
            $result_received_count = $stmt_received_count->get_result();
            $countData = $result_received_count->fetch_assoc();
            $receivedApplicationsCount = $countData['count'] ?? 0;
        } else { error_log("Execute failed (company received applications count): " . $stmt_received_count->error); }
        $stmt_received_count->close();
    } else { error_log("Prepare failed for company received applications count: " . $conn->error); }

    // Fetch Company Posted Internships Count
     $stmt_posted_count = $conn->prepare("SELECT COUNT(*) as count FROM internships WHERE company_id = ? AND is_active = 1");
     if ($stmt_posted_count) {
        $stmt_posted_count->bind_param("i", $user_id);
         if($stmt_posted_count->execute()){
            $result_posted_count = $stmt_posted_count->get_result();
            $countData = $result_posted_count->fetch_assoc();
            $postedInternshipsCount = $countData['count'] ?? 0;
        } else { error_log("Execute failed (company internship count): " . $stmt_posted_count->error); }
        $stmt_posted_count->close();
    } else { error_log("Prepare failed for company internship count: " . $conn->error); }
}

// --- Fetch Common Data (Unread Messages, Notifications) ---
$stmt_msg = $conn->prepare("
    SELECT m.id, u.first_name, u.last_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? AND m.is_read = 0
    ORDER BY m.sent_at DESC
");
 if ($stmt_msg) {
    $stmt_msg->bind_param("i", $user_id);
    $stmt_msg->execute();
    $result_msg = $stmt_msg->get_result();
    while ($row = $result_msg->fetch_assoc()) { $unreadMessages[] = $row; }
    $stmt_msg->close();
} else { error_log("Prepare failed (unread messages fetch): " . $conn->error); }

// Keep original notifications fetch logic




// Close connection
$conn->close();

// Define status labels array
$statusLabels = [
    'pending' => 'En attente',
    'reviewed' => 'Examinée',
    'interview' => 'Entretien',
    'accepted' => 'Accepté',
    'rejected' => 'Refusé'
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Plateforme de Stages</title>
    <!-- Link to your existing CSS file -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Using FA6 -->
     <!-- NO ADDITIONAL CSS ADDED HERE -->
     <style>
        /* Simple style for stat card links (if not already handled by style.css) */
        .stat-card .card-link {
            display: block; /* Make link take full width below number */
            margin-top: 0.5rem; /* Add some space */
            font-size: 0.85rem;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .stat-card .card-link:hover {
            text-decoration: underline;
        }
        .section-header a { /* Style for "Voir toutes" links */
             font-size: 0.9rem; color: #007bff; text-decoration: none;
        }
         .section-header a:hover { text-decoration: underline;}

        /* Basic styles copied from previous response for context */
        body { background-color: #f4f7f6; font-family: sans-serif; line-height: 1.6; }
        .dashboard { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .dashboard h2 { margin-bottom: 1.5rem; color: #333; font-weight: 600; }
        .dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); text-align: center; transition: transform 0.2s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card i { font-size: 2.5rem; color: #007bff; margin-bottom: 0.75rem; display: block;}
        .stat-card h3 { margin-bottom: 0.5rem; font-size: 1rem; color: #555; font-weight: 500; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #333; margin-bottom: 0.5rem; }

        .dashboard-content { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 992px) { /* Use 2 columns on larger screens */
             .dashboard-content { grid-template-columns: repeat(2, 1fr); }
        }
    
        .dashboard-section { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.75rem;}
        .section-header h3 { margin: 0; color: #0056b3; font-size: 1.2rem; font-weight: 600; }


        /* Card Styles */
        .application-card, .internship-card { border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; }
        .applications-list > div:last-child, .internships-list > div:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; } /* Remove border from last card */

        .application-card h4, .internship-card h4 { margin: 0 0 0.4rem 0; font-size: 1.1rem; font-weight: 600; }
        .application-card h4 a, .internship-card h4 a { color: #333; text-decoration: none; }
        .application-card h4 a:hover, .internship-card h4 a:hover { color: #0056b3; }
        .application-card p, .internship-card p { margin: 0.2rem 0; color: #666; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;}
        .application-card p i, .internship-card p i { color: #888; width: 1em; text-align: center;}
        .application-card .date { font-size: 0.85rem; color: #888; }

        /* Status Styles */
        .status { padding: 0.25em 0.7em; border-radius: 12px; font-size: 0.8rem; font-weight: 500; display: inline-block; margin-top: 0.5rem; border: 1px solid transparent;}
        .status-pending { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
        .status-reviewed { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .status-interview { background-color: #ffe8cc; color: #7a4d00; border-color: #ffdabc; }
        .status-accepted { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .status-rejected { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        .no-data { color: #777; font-style: italic; padding: 1rem 0; text-align: center; }
        .card-actions { margin-top: 0.8rem; display: flex; gap: 0.5rem;}
        .btn-details, .btn-apply { padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none; border-radius: 4px; }
        .btn-details { background-color: #6c757d; color: white;}
        .btn-details:hover { background-color: #5a6268;}
        .btn-apply { background-color: #007bff; color: white;}
        .btn-apply:hover { background-color: #0056b3;}

        .badge { background-color: #dc3545; color: white; font-size: 0.75em; padding: 3px 6px; border-radius: 50%; position: relative; top: -10px; left: -3px; font-weight: bold;}

        /* Header & Footer Styles */
        header { background-color: #ffffff; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;}
        .logo h1 a { color: #0056b3; text-decoration: none; font-weight: bold; font-size: 1.5rem;}
        nav ul { list-style: none; padding: 0; margin: 0; display: flex; gap: 1rem; flex-wrap: wrap; }
        nav a { text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 0.8rem; border-radius: 4px; transition: background-color 0.2s ease;}
        nav a.active, nav a:hover { color: #007bff; background-color: #e9ecef;}
        nav .badge { top: -5px; left: -1px;}

        footer { background-color: #343a40; color: #f8f9fa; padding: 3rem 1rem 1rem 1rem; margin-top: 3rem; font-size: 0.9rem; }
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
            <h1><a href="index.php" style="color: inherit; text-decoration: none;">StageConnect</a></h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="dashboard.php" class="active">Tableau de bord</a></li>
                <?php // Conditional navigation based on user type ?>
                <?php if ($user['user_type'] == 'student'): ?>
                    <li><a href="profile.php">Profil</a></li>
                    
                    
                <?php elseif ($user['user_type'] == 'company'): ?>
                 
                    
                    <li><a href="add_internship.php">Publier Stage</a></li>
                <?php endif; ?>
        
                
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard">
        <h2>Bienvenue, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>

        <div class="dashboard-stats">
            <?php // Display 4 stat cards, dynamically changing content based on user type ?>
            <?php if ($user['user_type'] == 'student'): ?>
                <div class="stat-card">
                     <i class="fas fa-building"></i>
                     <h3>Bienvenue</h3>
                     <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>Candidatures</h3>
                    <p class="stat-number"><?php echo count($applications); ?></p>
                    <a href="applications.php" class="card-link">Voir détails</a>
                </div>
              
                <div class="stat-card">
                    <i class="fas fa-bookmark"></i>
                    <h3>Stages sauvegardés</h3>
                    <p class="stat-number"><?php echo count($savedInternships); ?></p>
                    <a href="saved_internships.php" class="card-link">Voir liste</a>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-envelope"></i>
                    <h3>Messages non lus</h3>
                    <p class="stat-number"><?php echo count($unreadMessages); ?></p>
                    <a href="messages.php" class="card-link">Voir messages</a>
                </div>
               
                <div class="stat-card">
                    <i class="fas fa-briefcase"></i>
                    <h3>Stages Disponibles</h3>
                    <p class="stat-number"><?php echo $totalActiveInternships; ?></p>
                    <a href="internships.php" class="card-link">Voir les stages</a>
                </div>
            <?php elseif ($user['user_type'] == 'company'): ?>
                <div class="stat-card">
                     <i class="fas fa-building"></i>
                     <h3>Bienvenue</h3>
                     <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                </div>
                <div class="stat-card">
                    <i class="fas fa-list-check"></i>
                    <h3>Stages Publiés</h3>
                    <p class="stat-number"><?php echo $postedInternshipsCount; ?></p>
                    <a href="internships.php" class="card-link">Gérer</a>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Candidatures Reçues</h3>
                    <p class="stat-number"><?php echo $receivedApplicationsCount; ?></p>
                     <a href="applications.php" class="card-link">Voir détails</a>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-envelope"></i>
                    <h3>Messages non lus</h3>
                    <p class="stat-number"><?php echo count($unreadMessages); ?></p>
                    <a href="messages.php" class="card-link">Voir messages</a>
                </div>
           
                
               
            <?php endif; ?>
        </div>

        <div class="dashboard-content">
            <?php // Content sections are only shown for students as per original structure ?>
            <?php if ($user['user_type'] == 'student'): ?>
                <section class="dashboard-section recent-applications">
                    <div class="section-header">
                        <h3>Candidatures récentes</h3>
                        <a href="applications.php">Voir toutes</a> <?php // Link already here ?>
                    </div>
                    <?php if (count($applications) > 0): ?>
                    <div class="applications-list">
                        <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                        <div class="application-card">
                            <h4><a href="internship-details.php?id=<?php echo $app['internship_id']; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($app['title']); ?></a></h4>
                            <p class="company"><i class="fas fa-building"></i> <?php echo $app['company_name']; ?></p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location']); ?></p>
                            <p class="date"><i class="fas fa-calendar-alt"></i> Postulé le: <?php echo date('d/m/Y', strtotime($app['applied_at'])); ?></p>
                            <div class="status status-<?php echo strtolower(htmlspecialchars($app['status'])); ?>">
                                <?php echo isset($statusLabels[$app['status']]) ? $statusLabels[$app['status']] : htmlspecialchars(ucfirst($app['status'])); ?>
                            </div>
                            <!-- Optional: Link to view this specific application details -->
                            <!-- <div class="card-actions"><a href="application_details.php?id=<?php echo $app['id']; ?>" class="btn-details">Voir</a></div> -->
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data">Vous n'avez pas encore postulé à des stages. <a href="internships.php">Découvrez des stages disponibles</a>.</p>
                    <?php endif; ?>
                </section>

                <section class="dashboard-section saved-internships">
                    <div class="section-header">
                        <h3>Stages sauvegardés</h3>
                        <a href="saved_internships.php" class="card-link">Voir liste</a>
                    </div>
                    <?php if (count($savedInternships) > 0): ?>
                    <div class="internships-list">
                        <?php foreach (array_slice($savedInternships, 0, 3) as $internship): ?>
                        <div class="internship-card">
                            <h4><a href="internship_details.php?id=<?php echo $internship['id']; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($internship['title']); ?></a></h4>
                            <p class="company"><i class="fas fa-building"></i> <?php echo $internship['company_name']; ?></p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($internship['location']); ?></p>
                            <?php if (!empty($internship['duration'])): ?>
                            <p class="duration"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration']); ?></p>
                            <?php endif; ?>
                            <div class="card-actions">
                                <a href="internship-details.php?id=<?php echo $internship['id']; ?>" class="btn-details">Voir détails</a>
                                <a href="apply.php?id=<?php echo $internship['id']; ?>" class="btn-apply">Postuler</a>
                                <!-- Optional: Form/button to unsave directly -->
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data">Vous n'avez pas encore sauvegardé de stages. <a href="internships.php">Parcourir les stages</a>.</p>
                    <?php endif; ?>
                </section>
            <?php endif; // End student-only content ?>
             <?php // No company-specific content sections added here as per request ?>
        </div>
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

                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars("ysouahlia505@gmail.com"); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars("+213 668 742 584"); ?></p>
                <div class="social-media">
                    <a href="https://www.facebook.com/yacine.souahlia.1/" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/yacine_souahlia/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date("Y"); ?> StageConnect. Tous droits réservés.</p>
        </div>
    </footer>


</body>
</html>