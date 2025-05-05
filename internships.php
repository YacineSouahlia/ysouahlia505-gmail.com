<?php

session_start();


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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


$search = isset($_GET['search']) ? $_GET['search'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$remote = isset($_GET['remote']) ? 1 : 0;


$query = "
    SELECT i.*, cp.company_name, GROUP_CONCAT(c.name) as categories
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    LEFT JOIN internship_categories ic ON i.id = ic.internship_id
    LEFT JOIN categories c ON ic.category_id = c.id
    WHERE i.is_active = 1
";


$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (i.title LIKE ? OR i.description LIKE ? OR cp.company_name LIKE ?)";
    $searchParam = "%" . $search . "%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($location)) {
    $query .= " AND i.location LIKE ?";
    $params[] = "%" . $location . "%";
    $types .= "s";
}

if (!empty($category)) {
    $query .= " AND c.id = ?";
    $params[] = $category;
    $types .= "i";
}

if ($remote) {
    $query .= " AND i.is_remote = 1";
}

$query .= " GROUP BY i.id ORDER BY i.created_at DESC";


$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$internships = [];
while ($row = $result->fetch_assoc()) {
    $internships[] = $row;
}
$stmt->close();


$categories = [];
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();


$savedInternships = [];
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student') {
    $stmt = $conn->prepare("SELECT internship_id FROM saved_internships WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $savedInternships[] = $row['internship_id'];
    }
    $stmt->close();
}


if (isset($_POST['toggle_save']) && isset($_POST['internship_id'])) {
    $internship_id = $_POST['internship_id'];
    
    if (in_array($internship_id, $savedInternships)) {
      
        $stmt = $conn->prepare("DELETE FROM saved_internships WHERE student_id = ? AND internship_id = ?");
        $stmt->bind_param("ii", $user_id, $internship_id);
        $stmt->execute();
        $stmt->close();
        
    
        $key = array_search($internship_id, $savedInternships);
        if ($key !== false) {
            unset($savedInternships[$key]);
        }
    } else {
     
        $stmt = $conn->prepare("INSERT INTO saved_internships (student_id, internship_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $internship_id);
        $stmt->execute();
        $stmt->close();
        
       
        $savedInternships[] = $internship_id;
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
    <title>Recherche de Stages - StageConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

    <main class="internships-page">
        <div class="page-title">
            <h2>Recherche de Stages</h2>
        </div>

        <div class="search-filters">
            <form action="internships.php" method="GET">
                <div class="search-bar">
                    <input type="text" name="search" placeholder="Rechercher par titre, description ou entreprise" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filters">
                    <div class="filter-group">
                        <label for="location">Lieu</label>
                        <input type="text" id="location" name="location" placeholder="Ville ou région" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Catégorie</label>
                        <select id="category" name="category">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if ($category == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-checkbox">
                        <input type="checkbox" id="remote" name="remote" <?php if ($remote) echo 'checked'; ?>>
                        <label for="remote">À distance</label>
                    </div>
                    
                    <button type="submit" class="btn-filter">Filtrer</button>
                    <a href="internships.php" class="btn-reset">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="internships-results">
            <div class="results-header">
                <h3>Résultats (<?php echo count($internships); ?>)</h3>
                <div class="sort-options">
                    <label for="sort">Trier par:</label>
                    <select id="sort" onchange="sortInternships(this.value)">
                        <option value="recent">Plus récents</option>
                        <option value="duration">Durée</option>
                        <option value="company">Entreprise</option>
                    </select>
                </div>
            </div>

            <?php if (count($internships) > 0): ?>
            <div class="internships-grid">
                <?php foreach ($internships as $internship): ?>
                <div class="internship-card">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($internship['title']); ?></h4>
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                        <form method="POST" class="save-form">
                            <input type="hidden" name="internship_id" value="<?php echo $internship['id']; ?>">
                            <input type="hidden" name="toggle_save" value="1">
                            <button type="submit" class="btn-save <?php echo in_array($internship['id'], $savedInternships) ? 'saved' : ''; ?>">
                                <i class="<?php echo in_array($internship['id'], $savedInternships) ? 'fas' : 'far'; ?> fa-bookmark"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

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
                        <p class="compensation"><i class="fas fa-euro-sign"></i> <?php echo htmlspecialchars($internship['compensation']) . ' DA';  ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($internship['categories'])): ?>
                    <div class="categories">
                        <?php foreach (explode(',', $internship['categories']) as $cat): ?>
                        <span class="category-tag"><?php echo htmlspecialchars($cat); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="internship-description">
                        <p><?php echo substr(htmlspecialchars($internship['description']), 0, 150) . '...'; ?></p>
                    </div>

                    <div class="card-actions">
                        <a href="internship-details.php?id=<?php echo $internship['id']; ?>" class="btn-details">Voir détails</a>
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                        <a href="apply.php?id=<?php echo $internship['id']; ?>" class="btn-apply">Postuler</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Aucun stage trouvé</h3>
                <p>Essayez de modifier vos critères de recherche ou consultez nos suggestions ci-dessous.</p>
                <a href="internships.php" class="btn-primary">Voir tous les stages</a>
            </div>
            <?php endif; ?>
        </div>
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
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="privacy.php">Confidentialité</a></li>
                    <li><a href="terms.php">Conditions d'utilisation</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> ysouahlia505@gmail.com</p>
                <p><i class="fas fa-phone"></i> +214 668742584</p>
                <div class="social-media">
                    <a href="https://www.facebook.com/yacine.souahlia.1/"><i class="fab fa-facebook"></i></a>
                  
                    <a href="https://www.instagram.com/yacine_souahlia/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 StageConnect. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        function sortInternships(sortBy) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', sortBy);
            window.location.href = currentUrl.toString();
        }
        
      
        document.querySelectorAll('.btn-save').forEach(btn => {
            btn.addEventListener('click', function(e) {
                this.classList.toggle('saved');
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.replace('far', 'fas');
                } else {
                    icon.classList.replace('fas', 'far');
                }
            });
        });
    </script>
</body>
</html>