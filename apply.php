<?php

session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: internships.php");
    exit;
}

$internship_id = $_GET['id'];

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "internship_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$stmt = $conn->prepare("SELECT id FROM applications WHERE student_id = ? AND internship_id = ?");
$stmt->bind_param("ii", $user_id, $internship_id);
$stmt->execute();
$result = $stmt->get_result();
$already_applied = $result->num_rows > 0;
$stmt->close();


$stmt = $conn->prepare("
    SELECT i.*, cp.company_name
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE i.id = ? AND i.is_active = 1
");
$stmt->bind_param("i", $internship_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
   
    header("Location: internships.php");
    exit;
}

$internship = $result->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("
    SELECT s.*, u.email
    FROM student_profiles s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();


$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
  
    $cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
    $availability = isset($_POST['availability']) ? trim($_POST['availability']) : '';

   
    if (strlen($cover_letter) > 3000) {
        $error_message = "La lettre de motivation ne doit pas dépasser 3000 caractères.";
    }

   
    $cv_path = '';
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['cv']['type'];
        $file_size = $_FILES['cv']['size'];

       
        if ($file_size > 5 * 1024 * 1024) {
            $error_message = "Le fichier CV ne doit pas dépasser 5 Mo.";
        }

        if (empty($error_message) && in_array($file_type, $allowed_types)) {
            $upload_dir = 'uploads/cvs/';

           
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . '_' . basename($_FILES['cv']['name']);
            $cv_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
               
            } else {
                $error_message = "Échec du téléchargement du CV.";
            }
        } elseif (empty($error_message)) {
            $error_message = "Format de fichier non autorisé. Veuillez télécharger un fichier PDF ou Word.";
        }
    } elseif ($_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {
       
        $error_message = "Erreur lors du téléchargement du fichier. Code: " . $_FILES['cv']['error'];
    } else {
        $error_message = "Veuillez télécharger un CV.";
    }

    if (empty($error_message)) {
        
        $stmt = $conn->prepare("
            INSERT INTO applications (student_id, internship_id, cover_letter, cv_path, availability, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $status = 'pending';
        $stmt->bind_param("iissss", $user_id, $internship_id, $cover_letter, $cv_path, $availability, $status);

        if ($stmt->execute()) {
            $success_message = "Votre candidature a été soumise avec succès!";
            $already_applied = true;
        } else {
            $error_message = "Erreur lors de la soumission de la candidature: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postuler - <?php echo htmlspecialchars($internship['title']); ?> - StageConnect</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
       
        body {
            background-color: #f8f9fa;
        }

        header {
            background-color: #343a40;
            color: #fff;
        }

        main {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .apply-container {
            background-color: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .internship-summary {
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .internship-summary h2 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .company-info .company-name {
            font-weight: bold;
            color: #007bff;
        }

        .internship-details {
            margin-top: 1rem;
        }

        .internship-details p {
            margin-bottom: 0.5rem;
        }

        .internship-details .location {
            color: #6c757d;
        }

        .internship-details .remote-badge {
            background-color: #28a745;
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }

        .application-form {
            padding: 1.5rem;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group textarea {
            resize: vertical;
        }

        .form-actions {
            margin-top: 1.5rem;
        }

        .alert {
            margin-bottom: 1.5rem;
        }

        .alert p {
            margin-bottom: 0;
        }

        .alert .button-group {
            margin-top: 1rem;
        }

        footer {
            background-color: #343a40;
            color: #fff;
            padding: 2rem 0;
        }

        footer a {
            color: #fff;
        }

        .breadcrumbs {
          padding: 1rem 1.5rem;
          background-color: #e9ecef;
          border-radius: 0.25rem;
          margin-bottom: 1.5rem;
        }

        .breadcrumbs a {
          color: #007bff;
          text-decoration: none;
        }

        .breadcrumbs span {
          color: #6c757d;
        }

        .required {
            color: red;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">StageConnect</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                    </li>
                  
                   
                    
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="breadcrumbs">
            <a href="internships.php">Stages</a> >
            <a href="internship-details.php?id=<?php echo $internship_id; ?>"><?php echo htmlspecialchars($internship['title']); ?></a> >
            <span>Postuler</span>
        </div>

        <div class="apply-container">
            <div class="internship-summary">
                <h2>Postuler pour: <?php echo htmlspecialchars($internship['title']); ?></h2>
                <div class="company-info">
                    <span class="company-name"><?php echo $internship['company_name']; ?></span>
                </div>
                <div class="internship-details">
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
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <p><?php echo $success_message; ?></p>
                <div class="button-group">
                    <a href="applications.php" class="btn btn-primary">Voir mes candidatures</a>
                    <a href="internships.php" class="btn btn-secondary">Parcourir d'autres stages</a>
                </div>
            </div>
            <?php elseif ($already_applied): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <p>Vous avez déjà postulé à ce stage.</p>
                <div class="button-group">
                    <a href="applications.php" class="btn btn-primary">Voir mes candidatures</a>
                    <a href="internships.php" class="btn btn-secondary">Parcourir d'autres stages</a>
                </div>
            </div>
            <?php else: ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <p><?php echo $error_message; ?></p>
            </div>
            <?php endif; ?>

            <div class="application-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3>CV et Lettre de motivation</h3>
                        <div class="form-group">
                            <label for="cv">CV (PDF ou Word) <span class="required">*</span></label>
                            <input type="file" class="form-control-file" id="cv" name="cv" required accept=".pdf,.doc,.docx">
                        </div>
                        <div class="form-group">
                            <label for="cover_letter">Lettre de motivation <span class="required">*</span></label>
                            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" required placeholder="Présentez-vous, expliquez pourquoi ce stage vous intéresse et ce que vous pourriez apporter à l'entreprise..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Disponibilités</h3>
                        <div class="form-group">
                            <label for="availability">Précisez vos disponibilités <span class="required">*</span></label>
                            <textarea class="form-control" id="availability" name="availability" rows="4" required placeholder="Exemple: Disponible à partir du 15 juin 2025, à temps plein, pour une durée de 6 mois..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="internship-details.php?id=<?php echo $internship_id; ?>" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer ma candidature
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h3>StageConnect</h3>
                    <p>Connecter les étudiants aux meilleures opportunités de stage</p>
                </div>
                <div class="col-md-4">
                    <h3>Liens utiles</h3>
                    <ul>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="privacy.php">Confidentialité</a></li>
                        <li><a href="terms.php">Conditions d'utilisation</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3>Contact</h3>
                    <p><i class="fas fa-envelope"></i> ysouahlia505@gmail.com</p>
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
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        
        const fileInput = document.getElementById('cv');
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                console.log('File selected:', fileInput.files[0].name);
            }
        });
    </script>
</body>
</html>