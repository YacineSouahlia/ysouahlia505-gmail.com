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

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: internships.php"); 
    exit;
}

$internship_id = $_GET['id'];


$query = "
    SELECT i.*, cp.company_name, cp.description AS company_description, GROUP_CONCAT(c.name) as categories
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    LEFT JOIN internship_categories ic ON i.id = ic.internship_id
    LEFT JOIN categories c ON ic.category_id = c.id
    WHERE i.id = ?
    GROUP BY i.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $internship_id);
$stmt->execute();
$result = $stmt->get_result();
$internship = $result->fetch_assoc();
$stmt->close();


if (!$internship) {
    header("Location: internships.php");
    exit;
}


$isOwner = false;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'company' && $_SESSION['user_id'] == $internship['company_id']) {
    $isOwner = true;
}


$isSaved = false;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student') {
    $stmt = $conn->prepare("SELECT 1 FROM saved_internships WHERE student_id = ? AND internship_id = ?");
    $stmt->bind_param("ii", $user_id, $internship_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $isSaved = true;
    }
    $stmt->close();
}


if (isset($_POST['toggle_save']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student') {
    if ($isSaved) {
        $stmt = $conn->prepare("DELETE FROM saved_internships WHERE student_id = ? AND internship_id = ?");
    } else {
        $stmt = $conn->prepare("INSERT INTO saved_internships (student_id, internship_id) VALUES (?, ?)");
    }
    $stmt->bind_param("ii", $user_id, $internship_id);
    $stmt->execute();
    $stmt->close();

   
    header("Location: internship_detail.php?id=" . $internship_id);
    exit;
}




$deleteError = null; 
if (isset($_POST['delete_internship']) && $isOwner) {
    
    $conn->begin_transaction();

    try {
       
        $stmt = $conn->prepare("DELETE FROM saved_internships WHERE internship_id = ?");
        $stmt->bind_param("i", $internship_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM internship_categories WHERE internship_id = ?");
        $stmt->bind_param("i", $internship_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM applications WHERE internship_id = ?");
        $stmt->bind_param("i", $internship_id);
        $stmt->execute();
        $stmt->close();

        
        $stmt = $conn->prepare("DELETE FROM internships WHERE id = ? AND company_id = ?");
        $stmt->bind_param("ii", $internship_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

       
        $conn->commit();

        
        header("Location: internships.php?deleted=success");
        exit;
    } catch (Exception $e) {
        
        $conn->rollback();
        
        $deleteError = "Erreur lors de la suppression du stage: " . $e->getMessage();
    }
}



$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Stage - StageConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .btn-delete {
            background-color: #ff3b30;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
            margin-top: 15px; 
            text-decoration: none;
        }

        .btn-delete:hover {
            background-color: #d93228;
        }

        .btn-delete i {
            margin-right: 8px;
        }

        

        .danger-alert {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

       
        .btn-save {
            background: none;
            border: none;
            font-size: 1.5em; 
            cursor: pointer;
            color: #6c757d; 
            padding: 0 5px;
            vertical-align: middle;
        }
        .btn-save.saved {
            color: #007bff; 
        }
        .save-form {
            display: inline-block; 
            vertical-align: middle;
            margin-left: 10px;
        }
         .details-header {
            display: flex;
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 15px; 
        }
        .details-header h3 {
            margin: 0; 
            flex-grow: 1;
        }
      

    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>StageConnect</h1>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
              
               
  
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="internship-details-page">
        <div class="page-title">
            <h2>Détails du Stage</h2>
        </div>

        <?php if (isset($deleteError)): ?>
            <div class="danger-alert"><?php echo htmlspecialchars($deleteError); ?></div>
        <?php endif; ?>

        <?php if ($internship):  ?>
        <div class="internship-details">
            <div class="details-header">
                <h3><?php echo htmlspecialchars($internship['title']); ?></h3>

                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student'): ?>
                <form method="POST" class="save-form">
                    <input type="hidden" name="toggle_save" value="1">
                    
                </form>
                <?php endif; ?>
            </div>

            <div class="company-info">
                <h4><?php echo $internship['company_name']; ?></h4>
                <p><?php echo $internship['company_description']; ?></p>
            </div>

            <div class="internship-overview">
                <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($internship['location']); ?>
                    <?php if ($internship['is_remote']): ?>
                    <span class="remote-badge">À distance possible</span>
                    <?php endif; ?>
                </p>
                <p class="duration"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration']); ?></p>
                <?php if (!empty($internship['start_date'])): ?>
                <p class="date"><i class="fas fa-calendar-alt"></i> Début: <?php echo date('d/m/Y', strtotime($internship['start_date'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($internship['compensation'])): ?>
                <p class="compensation"><i class="fas fa-euro-sign"></i> <?php echo htmlspecialchars($internship['compensation']); ?></p>
                <?php endif; ?>

                <?php if (!empty($internship['categories'])): ?>
                <div class="categories">
                    <?php foreach (explode(',', $internship['categories']) as $cat): ?>
                    <span class="category-tag"><?php echo htmlspecialchars(trim($cat)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="internship-description">
                <h3>Description du stage</h3>
                <p><?php echo nl2br(htmlspecialchars($internship['description'])); ?></p>
            </div>

             <div class="apply-section">
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student'): ?>
                <a href="apply.php?id=<?php echo $internship['id']; ?>" class="btn-apply">Postuler</a>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                
                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce stage ? Cette action est irréversible.');">
                    <input type="hidden" name="delete_internship" value="1">
                    <button type="submit" class="btn-delete">
                       Supprimer
                    </button>
                </form>
                <?php endif; ?>

                <a href="internships.php" class="btn-link">Retour à la liste des stages</a>
            </div>
        </div>
        <?php else: ?>
            <p>Le stage demandé n'a pas été trouvé.</p>
            <a href="internships.php" class="btn-secondary">Retour à la liste des stages</a>
        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <h2>StageConnect</h2>
                <p>Connecter les étudiants aux meilleures opportunités de stage</p>
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
                <p><i class="fas fa-envelope"></i> contact@stageconnect.fr</p>
                <p><i class="fas fa-phone"></i> +214 0668742584</p>
                <div class="social-media">
                <a href="https://www.facebook.com/yacine.souahlia.1/"><i class="fab fa-facebook"></i></a>
                 
                <a href="https://www.instagram.com/yacine_souahlia/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 StageConnect. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>