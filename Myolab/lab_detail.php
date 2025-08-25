<?php
// Laboratuvar ID'sini al
$lab_id = $_GET['id'] ?? null;

if (!$lab_id) {
    header('Location: iamodinson.php');
    exit();
}

try {
    require_once 'Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Laboratuvar bilgilerini al
    $stmt = $pdo->prepare("SELECT l.*, c.name as category_name FROM laboratories l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = ?");
    $stmt->execute([$lab_id]);
    $lab = $stmt->fetch();
    
    if (!$lab) {
        header('Location: iamodinson.php');
        exit();
    }
    
    // Laboratuvar cihazlarını resimleriyle birlikte al
    $stmt = $pdo->prepare("SELECT d.*, 
                           (SELECT ei.url FROM equipment_images ei WHERE ei.equipment_id = d.id ORDER BY ei.order_num ASC, ei.created_at ASC LIMIT 1) as device_image_url,
                           (SELECT ei.alt_text FROM equipment_images ei WHERE ei.equipment_id = d.id ORDER BY ei.order_num ASC, ei.created_at ASC LIMIT 1) as device_image_alt
                           FROM devices d 
                           WHERE d.lab_id = ? 
                           ORDER BY d.order_num ASC, d.created_at ASC");
    $stmt->execute([$lab_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Laboratuvar özel içeriklerini al
    $stmt = $pdo->prepare("SELECT * FROM lab_contents WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İçerikleri tip bazında organize et
    $labContents = [];
    foreach ($contents as $content) {
        $labContents[$content['content_type']] = $content;
    }
    
    // Laboratuvar ana resmini al (önce özel içerik, sonra ilk cihazın resmi)
    $mainImage = null;
    if (isset($labContents['main_image'])) {
        $mainImage = [
            'url' => $labContents['main_image']['content_value'],
            'alt_text' => $labContents['main_image']['alt_text']
        ];
    } else {
        $stmt = $pdo->prepare("SELECT ei.* FROM equipment_images ei 
                               INNER JOIN devices d ON ei.equipment_id = d.id 
                               WHERE d.lab_id = ? 
                               ORDER BY ei.order_num ASC, ei.created_at ASC 
                               LIMIT 1");
        $stmt->execute([$lab_id]);
        $mainImage = $stmt->fetch();
    }
    
} catch(Exception $e) {
    header('Location: iamodinson.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($lab['name']); ?> Detayları - MCBÜ">
    <title><?php echo htmlspecialchars($lab['name']); ?> - MCBÜ</title>

    <link rel="stylesheet" href="myolab/styles/variables.css">
    <link rel="stylesheet" href="myolab/styles/base.css">
    <link rel="stylesheet" href="myolab/styles/components/navbar.html">
    <link rel="stylesheet" href="myolab/styles/components/cards.css">
    <link rel="stylesheet" href="myolab/styles/components/buttons.css">
    <link rel="stylesheet" href="myolab/styles/components/footer.css">
    <link rel="stylesheet" href="myolab/styles/components/lab-details.css">
    <link rel="stylesheet" href="myolab/styles/utilities.css">
    <link rel="stylesheet" href="myolab/styles/responsive.css">
    <link rel="stylesheet" href="myolab/styles/components/hero.css">
</head>
<body>
    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <img src="myolab/images/myo.png" alt="MCBÜ Logo" class="myo-logo">   
        </div>
    </header>
    
    <main class="container">
        <div class="lab-details">
            <!-- Ana laboratuvar resmi -->
            <?php if (!empty($mainImage)): ?>
                <img src="<?php echo htmlspecialchars($mainImage['url']); ?>" class="lab-main-image" alt="<?php echo htmlspecialchars($mainImage['alt_text'] ?: $lab['name']); ?>">
            <?php else: ?>
                <img src="myolab/images/XRLAB/lab9_2.png" class="lab-main-image" alt="<?php echo htmlspecialchars($lab['name']); ?>">
            <?php endif; ?>
            
            <div class="lab-info">
                <h1><?php 
                    if (isset($labContents['lab_title'])) {
                        echo htmlspecialchars($labContents['lab_title']['content_value']);
                    } else {
                        echo htmlspecialchars($lab['name']);
                    }
                ?></h1>
                
                <div class="lab-features">
                    <h2>Laboratuvar Hakkında</h2>
                    <p>
                        <?php 
                        if (isset($labContents['about_text'])) {
                            echo nl2br(htmlspecialchars($labContents['about_text']['content_value']));
                        } else {
                            echo "Bu laboratuvar " . htmlspecialchars($lab['category_name']) . " kategorisinde yer almaktadır. Laboratuvarımızda modern teknoloji ve güncel ekipmanlar kullanılarak eğitim ve araştırma faaliyetleri yürütülmektedir.";
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="devices-grid">
                <?php if (!empty($devices)): ?>
                    <?php foreach ($devices as $device): ?>
                        <div class="device-container">
                            <div class="device-photo">
                                <?php if (!empty($device['device_image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($device['device_image_url']); ?>" alt="<?php echo htmlspecialchars($device['device_image_alt'] ?: $device['device_name']); ?>">
                                <?php else: ?>
                                    <img src="myolab/images/XRLAB/xrlab_1.png" alt="<?php echo htmlspecialchars($device['device_name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="device-info">
                                <div class="device-name">
                                    <h2>Cihaz Adı/Modeli</h2>
                                    <h4><?php echo htmlspecialchars($device['device_name']); ?></h4>
                                    <?php if (!empty($device['device_model'])): ?>
                                        <p><small><?php echo htmlspecialchars($device['device_model']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="device-count">
                                    <h2>Sayısı</h2>
                                    <h4><?php echo $device['device_count']; ?></h4>
                                </div>
                            </div>
                            <div class="device-purpose">
                                <h2>Kullanım Amacı</h2>
                                <h4><?php echo htmlspecialchars($device['purpose'] ?: 'Belirtilmemiş'); ?></h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- myolab.html yapısına uygun varsayılan cihazlar -->
                    <div class="device-container">
                        <div class="device-photo">
                            <img src="myolab/images/XRLAB/xrlab_1.png" alt="Ana Bilgisayar">
                        </div>
                        <div class="device-info">
                            <div class="device-name">
                                <h2>Cihaz Adı/Modeli</h2>
                                <h4>Ana Bilgisayar</h4>
                            </div>
                            <div class="device-count">
                                <h2>Sayısı</h2>
                                <h4>1</h4>
                            </div>
                        </div>
                        <div class="device-purpose">
                            <h2>Kullanım Amacı</h2>
                            <h4>Intel Core i7-1270KF 3.6 GHz 32 GB RAM 1 TB SSD</h4>
                        </div>
                    </div>
                    
                    <div class="device-container">
                        <div class="device-photo">
                            <img src="myolab/images/XRLAB/xrlab_2.png" alt="Taşınabilir Bilgisayar">
                        </div>
                        <div class="device-info">
                            <div class="device-name">
                                <h2>Cihaz Adı/Modeli</h2>
                                <h4>Taşınabilir Bilgisayar</h4>
                            </div>
                            <div class="device-count">
                                <h2>Sayısı</h2>
                                <h4>1</h4>
                            </div>
                        </div>
                        <div class="device-purpose">
                            <h2>Kullanım Amacı</h2>
                            <h4>Intel Core i7-1270H 3.5 GHz 32 GB RAM 500 GB SSD</h4>
                        </div>
                    </div>
                    
                    <div class="device-container">
                        <div class="device-photo">
                            <img src="myolab/images/XRLAB/xrlab_3.png" alt="Grafik Tablet">
                        </div>
                        <div class="device-info">
                            <div class="device-name">
                                <h2>Cihaz Adı/Modeli</h2>
                                <h4>Grafik Tablet</h4>
                            </div>
                            <div class="device-count">
                                <h2>Sayısı</h2>
                                <h4>1</h4>
                            </div>
                        </div>
                        <div class="device-purpose">
                            <h2>Kullanım Amacı</h2>
                            <h4>Wacom Cintiq</h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-contact">
            <div class="footer-info">
                <a href="https://xrlab.mcbu.edu.tr/" 
                   target="_blank" 
                   aria-label="XRlab sitesini ziyaret et">
                    <img src="myolab/images/xr.png" alt="XRlab" width="100" height="100" />
                </a>
                <p class="footer-title">Copyright © 2025. MCBU Extended Reality Lab. All Rights Reserved.</p>
                <p class="footer-title">Tasarım ve Kodlama</p>
            </div>
            <div class="footer-name-container">  
                <p class="footer-name">Görkem Taha Çanakcı</p>
                <div class="footer-social">
                    <a href="mailto:gorkemtaha1000@gmail.com" 
                       target="_blank" 
                       aria-label="Gmail adresimi ziyaret et">
                        <img src="myolab/images/gm.png" alt="Gmail" width="28" height="28" />
                    </a>
                    <a href="https://www.linkedin.com/in/görkem-taha-ç-31521028a/" 
                       target="_blank" 
                       aria-label="LinkedIn profilimi ziyaret et">
                        <img src="myolab/images/li.png" alt="LinkedIn" width="28" height="28" />
                    </a>
                    <a href="https://github.com/Gorkem-Taha" 
                       target="_blank" 
                       aria-label="GitHub profilimi ziyaret et">
                        <img src="myolab/images/github.png" alt="GitHub" width="28" height="28" />
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
