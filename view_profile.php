<?php
// Start the session to access user information
session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];


if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
} else {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: dashboard.php"); 
    exit;
}

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


$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? AND user_type = 'student'"); // Ensure only students are retrieved
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['error'] = "Student profile not found.";
    header("Location: dashboard.php"); 
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 20px;
        }

        h1 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .profile-card {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px 5px 0 0;
            margin-bottom: 20px;
        }

        .profile-details {
            margin-bottom: 20px;
        }

        .detail-label {
            font-weight: bold;
            margin-right: 5px;
            color: #343a40;
        }

        .detail-value {
            color: #495057;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
        }

        .back-link:hover {
            text-decoration: none;
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profil de l'étudiant</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
            Informations sur le profil
            </div>
            <div class="profile-details">
                
                <p><span class="detail-label">Prénom:</span> <span class="detail-value"><?php echo htmlspecialchars($student['first_name']); ?></span></p>
                <p><span class="detail-label">Nom de famille:</span> <span class="detail-value"><?php echo htmlspecialchars($student['last_name']); ?></span></p>
                <p><span class="detail-label">Email:</span> <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span></p>
            </div>
        </div>

        <a href="applications.php" class="btn btn-primary">Retour au tableau de bord</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>