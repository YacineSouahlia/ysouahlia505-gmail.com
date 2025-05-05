<?php
session_start();


if (isset($_SESSION['user_id'])) {
    
    if ($_SESSION['user_type'] == 'student') {
        header("Location: dashboard.php");
    } else {
        header("Location: dashboard.php");
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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    

    $sql = "SELECT id, email, password, user_type FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
     
        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            
           
            if ($user['user_type'] == 'student') {
                header("Location: dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error_message = "Mot de passe incorrect";
        }
    } else {
        $error_message = "Aucun compte trouvé avec cet email";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Plateforme de Stages</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Plateforme de Recherche de Stages</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="login.php" class="active">Connexion</a></li>
                    <li><a href="register.php">Inscription</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="form-container">
                <h2>Connexion</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                    
                    <p class="form-link">
                        Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous ici</a>
                    </p>
                </form>
            </section>
        </main>
        
        <footer>
            <p>&copy; 2025 Plateforme de Stages - Master 1 TIC</p>
        </footer>
    </div>
</body>
</html>