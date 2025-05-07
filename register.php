<?php
session_start();


if (isset($_SESSION['user_id'])) {
    
    if ($_SESSION['user_type'] == 'student') {
        header("Location: student/dashboard.php");
    } else {
        header("Location: company/dashboard.php");
    }
    exit;
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $conn->real_escape_string($_POST['user_type']);
    
    
    if ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas";
    } else {
        
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Cet email est déjà utilisé";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            
            $insert_sql = "INSERT INTO users (first_name, last_name, email, password, user_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $user_type);
            
            if ($insert_stmt->execute()) {
                $user_id = $conn->insert_id;
                
                
                if ($user_type == 'student') {
                    $profile_sql = "INSERT INTO student_profiles (user_id) VALUES (?)";
                } else {
                    $profile_sql = "INSERT INTO company_profiles (user_id) VALUES (?)";
                }
                
                $profile_stmt = $conn->prepare($profile_sql);
                $profile_stmt->bind_param("i", $user_id);
                $profile_stmt->execute();
                
                $success_message = "Compte créé avec succès! Vous pouvez maintenant vous connecter.";
            } else {
                $error_message = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - StageConnect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Inscription - StageConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="login.php">Connexion</a></li>
                    <li><a href="register.php" class="active">Inscription</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="form-container">
                <h2>Inscription</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    
                    <div class="form-group">
                        <label>Type de compte</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="user_type" value="student" checked>
                                Étudiant
                            </label>
                            <label>
                                <input type="radio" name="user_type" value="company">
                                Entreprise
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">S'inscrire</button>
                    </div>
                    
                    <p class="form-link">
                        Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici</a>
                    </p>
                </form>
            </section>
        </main>
        
        <footer>
            <p>© 2025 Plateforme de Stages - Master 1 TIC</p>
        </footer>
    </div>
</body>
</html>