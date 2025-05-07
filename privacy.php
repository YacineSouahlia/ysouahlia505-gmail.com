<?php
session_start();

// Although the privacy page content itself might not be dynamic based on login,
// the header often is (e.g., showing login/logout, messages).
// So, we include the logic to fetch necessary data for the header.

$user_id = $_SESSION['user_id'] ?? null; // Use null coalescing operator for safety
$unreadMessages = [];
$user = null; // Initialize user variable

if ($user_id) {
    $servername = "localhost";
    $username = "root"; 
    $password = ""; 
    $dbname = "internship_platform";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log error instead of dying on a public page if possible
        error_log("Database Connection failed: " . $conn->connect_error);
        // For simplicity here, we'll die, but consider a more graceful handling
        die("Connection failed: " . $conn->connect_error); 
    }

    // Fetch basic user info (might be needed for header/other elements)
    $stmt = $conn->prepare("SELECT first_name, last_name, email, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // Fetch user data
    $stmt->close();

    // Fetch unread messages count for the header badge
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount = $result->fetch_assoc()['count'];
    // Store the count directly, we don't need the full messages here
    // If you needed the messages themselves, you'd use the query from dashboard.php
    // For just the count, this is more efficient.
    $stmt->close();

    // Close connection
    $conn->close();
} else {
    // User is not logged in, set unread count to 0
    $unreadCount = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - StageConnect</title>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Add some basic styles for the privacy content if needed */
        .privacy-policy {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            line-height: 1.6;
        }
        .privacy-policy h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .privacy-policy h2 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #0056b3; /* Or your theme color */
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .privacy-policy p, .privacy-policy ul {
            margin-bottom: 15px;
            color: #555;
        }
        .privacy-policy ul {
            list-style: disc;
            margin-left: 20px;
        }
        .privacy-policy strong {
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1><a href="index.php" style="text-decoration: none; color: inherit;">StageConnect</a></h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <?php if ($user_id): ?>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                 
                    <?php if ($user && $user['user_type'] == 'student'): ?>
                      
                    <?php endif; ?>
                   
                     <li><a href="logout.php">Déconnexion</a></li>
                <?php else: ?>
                   
                    <li><a href="login.php">Connexion</a></li>
                    <li><a href="register.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="privacy-policy">
        <h1>Politique de Confidentialité de StageConnect</h1>
        <p><strong>Dernière mise à jour :</strong> 25 juillet 2024</p>

        <p>Bienvenue sur StageConnect . Nous nous engageons à protéger la vie privée de nos utilisateurs . Cette Politique de Confidentialité explique comment nous collectons, utilisons, divulguons et protégeons vos informations lorsque vous utilisez notre plateforme .</p>
        <p>En utilisant le Service, vous acceptez la collecte et l'utilisation des informations conformément à cette politique.</p>

        <h2>1. Informations que nous collectons</h2>
        <p>Nous pouvons collecter différents types d'informations vous concernant, notamment :</p>
        <ul>
            <li><strong>Informations d'identification personnelle :</strong> Nom, prénom, adresse e-mail, numéro de téléphone, date de naissance, etc., que vous fournissez lors de l'inscription </li>
            <li><strong>Informations de profil (Étudiant) :</strong> Cursus scolaire, compétences, expériences professionnelles, CV, lettre de motivation.</li>
            <li><strong>Informations de profil (Entreprise) :</strong> Nom de l'entreprise, description, adresse, informations de contact.</li>
            <li><strong>Informations sur les stages :</strong> Détails des offres de stage publiées par les entreprises.</li>
            <li><strong>Informations sur les candidatures :</strong> Informations relatives aux candidatures que vous soumettez ou recevez via la plateforme.</li>
            <li><strong>Données de communication :</strong> Messages échangés entre utilisateurs (étudiants et entreprises) via notre système de messagerie.</li>
           
        </ul>

        <h2>2. Comment nous utilisons vos informations</h2>
        <p>Nous utilisons les informations collectées aux fins suivantes :</p>
        <ul>
            <li>Fournir, exploiter et maintenir notre Service.</li>
            <li>Gérer votre compte et votre profil.</li>
            <li>Faciliter la mise en relation entre étudiants et entreprises pour des opportunités de stage.</li>
            <li>Permettre aux étudiants de postuler à des offres de stage.</li>
            <li>Permettre aux entreprises de gérer les candidatures reçues.</li>
            <li>Faciliter la communication entre les utilisateurs via notre messagerie.</li>
           
            <li>Améliorer et personnaliser votre expérience sur la plateforme.</li>
            <li>Analyser l'utilisation du Service pour l'améliorer.</li>
            <li>Prévenir la fraude et assurer la sécurité de la plateforme.</li>
            <li>Respecter nos obligations légales.</li>
        </ul>

        <h2>3. Partage de vos informations</h2>
        <p>Nous ne partageons vos informations personnelles qu'avec des tiers dans les circonstances suivantes :</p>
        <ul>
            <li><strong>Avec les entreprises :</strong> Si vous êtes un étudiant et que vous postulez à une offre de stage, nous partagerons votre profil et les informations de votre candidature avec l'entreprise concernée.</li>
            <li><strong>Avec les étudiants :</strong> Si vous êtes une entreprise, les informations publiques de votre profil et les détails des offres de stage que vous publiez seront visibles par les étudiants.</li>
            <li><strong>Prestataires de services :</strong> Nous pouvons partager vos informations avec des tiers qui nous aident à exploiter notre Service (par exemple, hébergement web, analyse de données, services de messagerie). Ces prestataires n'ont accès qu'aux informations nécessaires pour effectuer leurs tâches et sont contractuellement tenus de protéger vos données.</li>
            <li><strong>Obligations légales :</strong> Nous pouvons divulguer vos informations si la loi l'exige ou en réponse à des demandes valides des autorités publiques (par exemple, un tribunal ou une agence gouvernementale).</li>
            <li><strong>Transferts d'entreprise :</strong> En cas de fusion, acquisition ou vente d'actifs, vos informations personnelles pourraient être transférées.</li>
        </ul>

        <h2>4. Sécurité de vos informations</h2>
        <p>La sécurité de vos données est importante pour nous. Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles appropriées pour protéger vos informations personnelles contre l'accès non autorisé, la modification, la divulgation ou la destruction. Cependant, aucune méthode de transmission sur Internet ou de stockage électronique n'est sûre à 100 %, et nous ne pouvons garantir une sécurité absolue.</p>

        <h2>5. Conservation des données</h2>
        <p>Nous conserverons vos informations personnelles aussi longtemps que nécessaire aux fins énoncées dans cette Politique de Confidentialité, sauf si une période de conservation plus longue est requise ou autorisée par la loi. Nous conserverons et utiliserons vos informations dans la mesure nécessaire pour nous conformer à nos obligations légales, résoudre les litiges et faire respecter nos accords.</p>

        <h2>6. Vos droits en matière de protection des données</h2>
        <p>Selon votre juridiction, vous pouvez disposer des droits suivants concernant vos informations personnelles :</p>
        <ul>
            <li>Le droit d'accéder, de mettre à jour ou de supprimer les informations que nous détenons sur vous.</li>
            <li>Le droit de rectification si ces informations sont inexactes ou incomplètes.</li>
            <li>Le droit de vous opposer au traitement de vos informations personnelles.</li>
            <li>Le droit de demander la limitation du traitement de vos informations personnelles.</li>
            <li>Le droit à la portabilité des données.</li>
            <li>Le droit de retirer votre consentement à tout moment lorsque nous nous sommes appuyés sur votre consentement pour traiter vos informations.</li>
        </ul>
        <p>Vous pouvez généralement exercer ces droits via les paramètres de votre compte sur la plateforme. Pour toute autre demande, veuillez nous contacter.</p>

        

        <h2>7. Vie privée des enfants</h2>
        <p>Notre Service ne s'adresse pas aux personnes de moins de 16 ans (ou l'âge minimum requis dans votre juridiction). Nous ne collectons pas sciemment d'informations personnellement identifiable auprès d'enfants. Si vous découvrez qu'un enfant nous a fourni des informations personnelles, veuillez nous contacter.</p>

        <h2>8. Modifications de cette Politique de Confidentialité</h2>
        <p>Nous pouvons mettre à jour notre Politique de Confidentialité de temps à autre. Nous vous informerons de tout changement en publiant la nouvelle Politique de Confidentialité sur cette page et en mettant à jour la date de "Dernière mise à jour" en haut. Nous vous encourageons à consulter périodiquement cette Politique de Confidentialité pour prendre connaissance de toute modification.</p>

        <h2>9. Nous contacter</h2>
        <p>Si vous avez des questions concernant cette Politique de Confidentialité, veuillez nous contacter :</p>
        <ul>
            <li>Par e-mail : ysouahlia505@gmail.com</li>
        </ul>
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
                    <li><a href="privacy.php" class="active">Confidentialité</a></li>
                    <li><a href="terms.php">Conditions d'utilisation</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> ysouahlia505@gmail.com</p>
                <p><i class="fas fa-phone"></i> +214 06668742584</p> 
                <div class="social-media">
                    <a href="https://www.facebook.com/yacine.souahlia.1/"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/yacine_souahlia/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date("Y"); ?> StageConnect. Tous droits réservés.</p> 
        </div>
    </footer>

    <script src="js/script.js"></script> 
</body>
</html>