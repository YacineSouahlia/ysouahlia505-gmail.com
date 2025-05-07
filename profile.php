<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login.php");
    exit;
}


$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";


$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();


$profile_sql = "SELECT * FROM student_profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    
 
    $university = $conn->real_escape_string($_POST['university']);
    $program = $conn->real_escape_string($_POST['program']);
    $graduation_year = $conn->real_escape_string($_POST['graduation_year']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $bio = $conn->real_escape_string($_POST['bio']);
    
  
    $update_user_sql = "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?";
    $update_user_stmt = $conn->prepare($update_user_sql);
    $update_user_stmt->bind_param("ssi", $first_name, $last_name, $user_id);
    
    
    $update_profile_sql = "UPDATE student_profiles SET university = ?, program = ?, graduation_year = ?, skills = ?, bio = ? WHERE user_id = ?";
    $update_profile_stmt = $conn->prepare($update_profile_sql);
    $update_profile_stmt->bind_param("ssissi", $university, $program, $graduation_year, $skills, $bio, $user_id);
    
   
    if ($update_user_stmt->execute() && $update_profile_stmt->execute()) {
        $success_message = "Profil mis à jour avec succès!";
        
       
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_extensions)) {
                $upload_dir = '../uploads/resumes/';
                $file_name = $user_id . '_' . time() . '.' . $file_extension;
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $file_name)) {
                    $resume_path = 'uploads/resumes/' . $file_name;
                    
                    $update_resume_sql = "UPDATE student_profiles SET resume_path = ? WHERE user_id = ?";
                    $update_resume_stmt = $conn->prepare($update_resume_sql);
                    $update_resume_stmt->bind_param("si", $resume_path, $user_id);
                    $update_resume_stmt->execute();
                } else {
                    $error_message = "Erreur lors du téléchargement du CV.";
                }
            } else {
                $error_message = "Format de fichier non autorisé. Veuillez utiliser PDF, DOC ou DOCX.";
            }
        }
        
        
        $profile_stmt->execute();
        $profile_result = $profile_stmt->get_result();
        $profile = $profile_result->fetch_assoc();
    } else {
        $error_message = "Erreur lors de la mise à jour du profil.";
    }
}

$user_stmt->close();
$profile_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Plateforme de Stages</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>StageConnect</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
    
       
                    <li><a href="profile.php" class="active">Mon profil</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="profile-container">
                <h2>Mon Profil</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <form action="profile.php" method="post" enctype="multipart/form-data">
                    <div class="profile-section">
                        <h3>Informations personnelles</h3>
                        
                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small>L'email ne peut pas être modifié</small>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h3>Formation et compétences</h3>
                        
                        <div class="form-group">
                            <label for="university">Université/École</label>
                            <input type="text" id="university" name="university" value="<?php echo htmlspecialchars($profile['university'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="program">Programme d'études</label>
                            <input type="text" id="program" name="program" value="<?php echo htmlspecialchars($profile['program'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="graduation_year">Année de diplomation</label>
                            <input type="number" id="graduation_year" name="graduation_year" min="2020" max="2030" value="<?php echo htmlspecialchars($profile['graduation_year'] ?? date('Y')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="skills">Compétences (séparées par des virgules)</label>
                            <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h3>À propos de moi</h3>
                        
                        <div class="form-group">
                            <label for="bio">Biographie</label>
                            <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h3>CV</h3>
                        
                        <div class="form-group">
                            <label for="resume">Télécharger votre CV (PDF, DOC, DOCX)</label>
                            <input type="file" id="resume" name="resume">
                        </div>
                        
                        <?php if (!empty($profile['resume_path'])): ?>
                            <div class="current-resume">
                                <p>CV actuel: <a href="../<?php echo $profile['resume_path']; ?>" target="_blank">Voir le CV</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </section>
        </main>
        
        <footer>
            <p>&copy; 2025 Plateforme de Stages - Master 1 TIC</p>
        </footer>
    </div>
</body>
</html>