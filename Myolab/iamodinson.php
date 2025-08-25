<?php
session_start();
require_once 'Database/confıg.php';

// Rate limiting - 5 dakikada maksimum 5 deneme
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt'] = time();
}

if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['first_attempt']) < 300) {
    $error_message = 'Çok fazla başarısız deneme. Lütfen 5 dakika bekleyin.';
    $show_form = false;
} else {
    if ((time() - $_SESSION['first_attempt']) >= 300) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt'] = time();
    }
    $show_form = true;
}

// Kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header('Location: Dasboard/dashboard.php');
    exit();
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(filter_var($_POST['username'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    
    $error_message = '';
    $success_message = '';
    
    // Logout mesajı
    if (isset($_GET['logout']) && $_GET['logout'] == '1') {
        $success_message = 'Başarıyla çıkış yapıldı.';
    }
    
    // Session expired mesajı
    if (isset($_GET['expired']) && $_GET['expired'] == '1') {
        $error_message = 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.';
    }
    
    // Temel validasyon - Giriş için sadece gerekli kontroller
    if (empty($username) || empty($password)) {
        $error_message = 'E-posta adresi ve şifre gereklidir.';
    } elseif (strlen($username) > 100) {
        $error_message = 'E-posta adresi çok uzun.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $username)) {
        $error_message = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 1) {
        $error_message = 'Şifre gereklidir.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Kullanıcıyı veritabanında ara
            $stmt = $db->getConnection()->prepare("SELECT id, full_name, mail, password, authority FROM users WHERE mail = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Giriş başarılı - Session oluştur
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['full_name'];
                $_SESSION['email'] = $user['mail'];
                $_SESSION['role'] = $user['authority'];
                $_SESSION['login_time'] = time();
                

                
                // Dashboard'a yönlendir
                header('Location: Dasboard/dashboard.php');
                exit();
                
            } else {
                $error_message = 'E-posta adresi veya şifre hatalı!';
                $_SESSION['login_attempts']++;
            }
            
        } catch (Exception $e) {
            $error_message = 'Sistem hatası oluştu. Lütfen tekrar deneyin.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}


?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyoLab - Giriş Yap</title>
    <link rel="stylesheet" href="Css/style.css">
    <link rel="icon" type="image/png" href="image/logo/myologo.png">
</head>
<body>
    <div class="container">
        <img src="image/logo/myologoblu.png" alt="MyoLab Logo" class="logo">
        <h1 class="welcome-text">MyoLab'a Hoş Geldiniz</h1>
        <p class="subtitle">Hesabınıza giriş yaparak devam edin</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        

        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

                <?php if ($show_form): ?>
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username">E-posta Adresi</label>
                    <input type="email" id="username" name="username" placeholder="E-posta adresinizi girin" autocomplete="email" required>
                </div>

                <div class="form-group password-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" placeholder="Şifrenizi girin" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" id="passwordToggle" title="Şifreyi göster/gizle">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>

          

                <button type="submit" id="loginBtn" class="login-btn">Giriş Yap</button>
            </form>
            
           
        <?php else: ?>
            <div class="rate-limit-message">
                <p>Çok fazla başarısız deneme yapıldı.</p>
                <p>Lütfen 5 dakika bekleyin.</p>
                <button onclick="location.reload()" class="login-btn">Tekrar Dene</button>
            </div>
        <?php endif; ?>

         
    </div>

    <script src="js/login.js"></script>
</body>
</html>