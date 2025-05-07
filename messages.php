<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $receiver_id    = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0; 
    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0; 
    $subject        = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message        = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    if ($receiver_id > 0 && !empty($subject) && !empty($message)) {
        $sender_id = $_SESSION['user_id'];
        $is_read = 0;  

       
        $sql = "INSERT INTO messages (sender_id, receiver_id, application_id, subject, message, is_read, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";  
                
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
           $error_message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("iiissi", $sender_id, $receiver_id, $application_id, $subject, $message, $is_read);

            if ($stmt->execute()) {
                $success_message = "Message envoyé avec succès!";
            } else {
                $error_message = "Erreur lors de l'envoi du message :" . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        $error_message = "Recipient, subject, and message are required.";
    }
}


$user_id = $_SESSION['user_id'];
$sql_users = "SELECT id, first_name FROM users WHERE id != ?";  

$stmt_users = $conn->prepare($sql_users);

if ($stmt_users === false) {
  $error_message = "Error preparing user query statement: " . $conn->error;
  $result_users = false; 
} else {
  $stmt_users->bind_param("i", $user_id); 
  $stmt_users->execute();
  $result_users = $stmt_users->get_result(); 

}


if($_SESSION['user_type'] == 'student'){
    $student_id = $_SESSION['user_id'];
    $sql_applications = "SELECT id FROM applications WHERE student_id = ?"; 
    $stmt_applications = $conn->prepare($sql_applications);

    if ($stmt_applications === false){
      $error_message = "Error preparing applications statement: " . $conn->error;
      $result_applications = false;
    } else {
        $stmt_applications->bind_param("i", $student_id);
        $stmt_applications->execute();
        $result_applications = $stmt_applications->get_result();
    }
} else {
    $result_applications = false; 
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoyer un message</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .message-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .message-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .message-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .message-form select,
        .message-form input[type="text"], 
        .message-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .message-form textarea {
            height: 150px;
        }

        .message-form button {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .message-form button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }

        .success-message {
            color: green;
            margin-bottom: 10px;
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
                            
                            
                         
                            <li><a href="show_message.php">Afficher les messages</a></li>
                            <li><a href="profile.php">Mon profil</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Tableau de bord</a></li>
                           
                          
                            <li><a href="show_message.php">Afficher les messages</a></li>
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
            <div class="message-container">
                <h2>Envoyer un message</h2>

                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <div class="message-form">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <label for="receiver">Destinataire:</label>
                        <select id="receiver" name="receiver_id" required>
                            <option value="">Sélectionner un destinataire</option>
                            <?php
                            if ($result_users && $result_users->num_rows > 0) {
                                while ($row = $result_users->fetch_assoc()) {
                                    echo '<option value="' . $row["id"] . '">' . htmlspecialchars($row["first_name"]) .  '</option>';
                                }
                            } else {
                                echo '<option value="">Aucun utilisateur trouvé</option>';
                            }

                            if (isset($stmt_users) && $stmt_users !== false) {
                                $stmt_users->close(); 
                            }
                            ?>
                        </select>

                        <?php if($_SESSION['user_type'] == 'student'): ?>
                          <label for="application_id">Candidature (Optionnel):</label>
                          <select id="application_id" name="application_id">
                              <option value="0">Sélectionner une candidature (Optionnel)</option>
                              <?php if($result_applications && $result_applications->num_rows > 0): ?>
                                  <?php while($row = $result_applications->fetch_assoc()): ?>
                                      <option value="<?php echo $row['id']; ?>">ID de l'application : <?php echo $row['id']; ?></option>
                                  <?php endwhile; ?>
                              <?php endif; ?>
                          </select>
                         <?php endif; ?>

                        <label for="subject">Sujet:</label>
                        <input type="text" id="subject" name="subject" required>

                        <label for="message">Message:</label>
                        <textarea id="message" name="message" required></textarea>

                        <button type="submit">Envoyer</button>
                    </form>
                </div>
            </div>
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
                        <li><a href="contact.php">Contact</a></li>
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

<?php

if (isset($stmt_applications) && $stmt_applications !== false) {
  $stmt_applications->close();
}

$conn->close();
?>