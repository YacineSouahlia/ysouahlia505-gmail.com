<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'internship_platform';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);

   
    $stmt_verify = $conn->prepare("SELECT receiver_id FROM messages WHERE id = ? AND receiver_id = ?");
    $stmt_verify->bind_param("ii", $message_id, $user_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();

    if ($result_verify->num_rows > 0) {
        $stmt_update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt_update->bind_param("i", $message_id);
        if ($stmt_update->execute()) {
            
        } else {
            echo "Error updating message: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
    $stmt_verify->close();
}


$sql = "SELECT m.*,
            CASE
                WHEN m.sender_id = ? THEN 'You'  
                ELSE CONCAT(u_sender.first_name, ' ', u_sender.last_name) 
            END as sender_name,
            CASE
                WHEN m.receiver_id = ? THEN 'You'  
                ELSE CONCAT(u_receiver.first_name, ' ', u_receiver.last_name) 
            END AS receiver_name
        FROM messages m
        LEFT JOIN users u_sender ON m.sender_id = u_sender.id
        LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.id
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.sent_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id); 

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .message-container {
            max-width: 800px;
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

        .message-list {
            list-style: none;
            padding: 0;
        }

        .message-item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
            background-color: #fff;
        }

        .message-item h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #333;
        }

        .message-item p {
            margin-bottom: 8px;
            color: #666;
        }

        .message-item .message-metadata {
            font-size: 0.8em;
            color: #888;
        }

        .message-item .read-status {
            font-weight: bold;
            color: green;
        }

        .message-item .unread-status {
            font-weight: bold;
            color: red;
        }

        .message-item a {
            color: #007bff;
            text-decoration: none;
        }

        .message-item a:hover {
            text-decoration: underline;
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
            <div class="message-container">
                <h2>Messages</h2>

                <ul class="message-list">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="message-item">
                                <h3><?php echo htmlspecialchars($row['subject']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p> 
                                <p class="message-metadata">
                                    <strong>From:</strong> <?php echo $row['sender_name']; ?>
                                    | <strong>To:</strong> <?php echo $row['receiver_name']; ?>
                                    | <strong>Sent:</strong> <?php echo date('M d, Y h:i A', strtotime($row['sent_at'])); ?>
                                    | <strong>Status:</strong>
                                    <?php if ($row['is_read']): ?>
                                        <span class="read-status">Read</span>
                                    <?php else: ?>
                                        <span class="unread-status">Unread</span>
                                        <a href="messages.php?mark_read=<?php echo $row['id']; ?>">Mark as Read</a>
                                    <?php endif; ?>
                                </p>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No messages found.</p>
                    <?php endif; ?>
                </ul>
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
$stmt->close();
$conn->close();
?>