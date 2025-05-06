<?php
// Start the session to access user information
session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];


if ($user_type != 'student' && $user_type != 'company') {
    header("Location: dashboard.php");
    exit;
}


$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "internship_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . mysqli_error($conn));
}


if ($user_type == 'company' && isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['new_status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['pending', 'reviewed', 'interview', 'accepted', 'rejected']; 

    if (in_array($new_status, $allowed_statuses)) {
       
        $stmt = $conn->prepare("SELECT i.company_id FROM applications a
                               JOIN internships i ON a.internship_id = i.id
                               WHERE a.id = ? AND i.company_id = ?");
        $stmt->bind_param("ii", $app_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          
            $update_stmt = $conn->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("si", $new_status, $app_id);

            if ($update_stmt->execute()) {
                $_SESSION['message'] = "Le statut de la demande a été mis à jour avec succès.";
            } else {
                $_SESSION['error'] = "Erreur lors de la mise à jour du statut de l'application :" . $update_stmt->error; //Get the error message!
            }
            $update_stmt->close();
        } else {
            $_SESSION['error'] = "You don't have permission to update this application.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid status selected.";
    }

    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($user_type == 'student') {
    
    $stmt = $conn->prepare("SELECT a.*, i.title, i.description, i.location, cp.company_name
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC");
    $stmt->bind_param("i", $user_id);
} else if ($user_type == 'company') {
  
    $stmt = $conn->prepare("SELECT a.*, i.title, i.description, i.location, u.first_name as student_name, u.email as student_email
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN users u ON a.student_id = u.id
    WHERE i.company_id = ?
    ORDER BY a.applied_at DESC");
    $stmt->bind_param("i", $user_id);
}


if (isset($stmt)) {
    $stmt->execute();
    $applications = $stmt->get_result();
} else {
    
    $applications = false;
    $_SESSION['error'] = "Invalid user type detected.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user_type == 'student' ? 'My Applications' : 'Manage Applications'; ?></title>
   
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

        .alert {
            margin-bottom: 20px;
        }

        .application-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .application-table th,
        .application-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .application-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        .application-table tbody tr:hover {
            background-color: #f5f5f5;
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

        .status-interview {
            background-color: #007bff; 
        }

        .status-accepted {
            background-color: #28a745; 
        }

        .status-rejected {
            background-color: #dc3545; 
        }

        .status-select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            background-color: #fff;
        }

        .empty-state p {
            margin-bottom: 20px;
            color: #6c757d;
        }

        .btn {
            margin-right: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1><?php echo $user_type == 'student' ? 'Mes candidatures' : 'Gérer les applications'; ?></h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($applications) && $applications->num_rows > 0): ?>
            <table class="application-table">
                <thead>
                    <tr>
                        
                        <th>Stage</th>
                        <?php if ($user_type == 'company'): ?>
                            <th>Étudiante</th>
                        <?php else: ?>
                            <th>Entreprise</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Appliqué sur</th>
                        <?php if ($user_type == 'company'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $applications->fetch_assoc()): ?>
                        <tr>
                           
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['location']); ?></small>
                            </td>
                            <?php if ($user_type == 'company'): ?>
                                <td>
                                    <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>">
                                        <?php echo htmlspecialchars($row['student_name']); ?>
                                    </a><br>
                                    <small><?php echo htmlspecialchars($row['student_email']); ?>
                                </td>
                            <?php else: ?>
                                <td><?php echo $row['company_name']; ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($row['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['applied_at'])); ?></td>
                            <?php if ($user_type == 'company'): ?>
                                <td>
                                    <form method="post" action="" class="d-flex align-items-center">
                                        <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                                        <select name="new_status" class="status-select mr-2">
                                            <option value="pending" <?php if($row['status'] == 'pending') echo 'selected'; ?>>En attente</option>
                                            <option value="reviewed" <?php if($row['status'] == 'reviewed') echo 'selected'; ?>>Révisé</option>
                                            <option value="interview" <?php if($row['status'] == 'interview') echo 'selected'; ?>>Entretien</option>
                                            <option value="accepted" <?php if($row['status'] == 'accepted') echo 'selected'; ?>>Accepté</option>
                                            <option value="rejected" <?php if($row['status'] == 'rejected') echo 'selected'; ?>>Rejetée</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm mr-2">Mise à jour</button>
                                        <a href="view_application.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> Voir les détails</a>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <?php if ($user_type == 'student'): ?>
                    <p>Vous n'avez pas encore postulé à un stage.</p>
                    <a href="internships.php" class="btn btn-primary">Parcourir les stages</a>
                <?php else: ?>
                    <p>Aucune candidature reçue pour vos stages.</p>
                   
                <?php endif; ?>
            </div>
        <?php endif;

        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($update_stmt)) {
            $update_stmt->close();
        }

        $conn->close();
        ?>
     
        <a href="dashboard.php" class="btn btn-primary">Retour au tableau de bord</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>