<?php
// Start the session to access user information
session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

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


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $application_id = $_GET['id'];

 
    if ($user_type == 'student') {
        
        $stmt = $conn->prepare("SELECT a.*, i.title, i.description, i.location, i.requirements, i.start_date, i.end_date, cp.company_name
                               FROM applications a
                               JOIN internships i ON a.internship_id = i.id
                               JOIN company_profiles cp ON i.company_id = cp.user_id
                               WHERE a.id = ? AND a.student_id = ?"); 
        $stmt->bind_param("ii", $application_id, $user_id);


    } elseif ($user_type == 'company') {
        
        $stmt = $conn->prepare("SELECT a.*, i.title, i.description, i.location, i.requirements, i.start_date, 
                                      u.first_name as student_first_name, u.last_name as student_last_name, u.email as student_email
                               FROM applications a
                               JOIN internships i ON a.internship_id = i.id
                               JOIN users u ON a.student_id = u.id
                               WHERE a.id = ? AND i.company_id = ?"); 
        $stmt->bind_param("ii", $application_id, $user_id);


    } else {
        $_SESSION['error'] = "Invalid user type.";
        header("Location: dashboard.php");
        exit;
    }

    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$application) {
        $_SESSION['error'] = "Application not found or you do not have permission to view it.";
        header("Location: dashboard.php");
        exit;
    }

} else {
    $_SESSION['error'] = "Invalid application ID.";
    header("Location: dashboard.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details</title>
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

        .card {
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }

        .card-body {
            padding: 20px;
        }

        .detail-label {
            font-weight: bold;
            margin-right: 5px;
            color: #343a40;
        }

        .detail-value {
            color: #495057;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .status-pending {
            background-color: #ffc107; 
        }

        .status-reviewed {
            background-color: #17a2b8; 
        }

        .status-shortlisted {
            background-color: #28a745; 
        }

        .status-interviewed {
            background-color: #007bff; 
        }

        .status-selected {
            background-color: #28a745; 
        }

        .status-rejected {
            background-color: #dc3545; 
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
        <h1>Détails de la demande</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
            Informations sur les stages
            </div>
            <div class="card-body">
                <p><span class="detail-label">Titre:</span> <span class="detail-value"><?php echo htmlspecialchars($application['title']); ?></span></p>
                <p><span class="detail-label">Entreprise:</span> <span class="detail-value"><?php echo ($user_type == 'student') ? htmlspecialchars($application['company_name']) : 'N/A'; ?></span></p>
                <p><span class="detail-label">Emplacement:</span> <span class="detail-value"><?php echo htmlspecialchars($application['location']); ?></span></p>
                <p><span class="detail-label">Description:</span> <span class="detail-value"><?php echo htmlspecialchars($application['description']); ?></span></p>
                <p><span class="detail-label">Exigences:</span> <span class="detail-value"><?php echo htmlspecialchars($application['requirements']); ?></span></p>
                <p><span class="detail-label">Date de début:</span> <span class="detail-value"><?php echo date('M d, Y', strtotime($application['start_date'])); ?></span></p>
              
        </div>

        <div class="card">
            <div class="card-header">
            Informations sur la candidature
            </div>
            <div class="card-body">
                <p><span class="detail-label">Date de candidature :</span> <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($application['applied_at'])); ?></span></p>
                <?php if($user_type == 'company'): ?>
                    <p><span class="detail-label">Nom de l'étudiant :</span> <span class="detail-value"><?php echo htmlspecialchars($application['student_first_name'] . ' ' . $application['student_last_name']); ?></span></p>
                    <p><span class="detail-label">Courriel de l'étudiant :</span> <span class="detail-value"><?php echo htmlspecialchars($application['student_email']); ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <a href="applications.php" class="btn btn-primary">Retour aux applications</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>