<?php
session_start();
require_once 'Database/confıg.php';

// Kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim(htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $authority = 'user'; // Varsayılan yetki
    
    $error_message = '';
    $success_message = '';
    

    
    // Gelişmiş validasyon ve güvenlik
    if (empty($full_name) || empty($email) || empty($password)) {
        $error_message = 'Tüm alanlar gereklidir.';
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $error_message = 'Ad soyad 2-100 karakter arasında olmalıdır.';
    } elseif (strlen($email) > 100) {
        $error_message = 'E-posta adresi çok uzun.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $error_message = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 1) {
        $error_message = 'Şifre gereklidir.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Şifre en az 8 karakter olmalıdır.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
        $error_message = 'Şifre en az 8 karakter olmalı ve büyük harf, küçük harf, rakam ve özel karakter (!@#$%^&*(),.?":{}|<>) içermelidir.';
    } else {
        try {
            $db = Database::getInstance();
            
            // E-posta adresi zaten kullanılıyor mu kontrol et
            $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE mail = ? LIMIT 1");
            $stmt->execute([$email]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $error_message = 'Bu e-posta adresi zaten kullanılıyor.';
            } else {
                // Şifreyi hash'le
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Kullanıcıyı veritabanına ekle
                $stmt = $db->getConnection()->prepare("
                    INSERT INTO users (full_name, mail, password, authority) 
                    VALUES (?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$full_name, $email, $hashed_password, $authority])) {
                    $success_message = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
                    
                    // 3 saniye sonra giriş sayfasına yönlendir
                    header("refresh:3;url=iamodinson.php");
                } else {
                    $error_message = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                }
            }
            
        } catch (Exception $e) {
            $error_message = 'Sistem hatası oluştu. Lütfen tekrar deneyin.';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - MyoLab</title>
    <link rel="stylesheet" href="Css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <img src="image/logo/myologoblu.png" alt="MyoLab Logo" class="logo">
        
        <h1 class="welcome-text">Kayıt Ol</h1>
        <p class="subtitle">Hesabınızı oluşturun ve MyoLab'e katılın</p>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        

        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="full_name">
                    <i class="fas fa-user"></i> Ad Soyad
                </label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                       placeholder="Adınız ve soyadınız" required>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> E-posta Adresi
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="ornek@email.com" required>
            </div>
            
            <div class="form-group password-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Şifre
                </label>
                <input type="password" id="password" name="password" 
                       placeholder="En az 8 karakter, büyük harf, küçük harf, rakam ve özel karakter" required>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-user-plus"></i> Kayıt Ol
            </button>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Form validasyonu kaldırıldı - test için
    </script>
</body>
</html>
