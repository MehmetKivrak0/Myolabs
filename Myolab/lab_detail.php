<?php
// Laboratuvar ID'sini al
$lab_id = $_GET['id'] ?? null;

if (!$lab_id) {
    header('Location: iamodinson.php');
    exit();
}

try {
    require_once 'Database/config.php';
    $database = Database::getInstance();
    $mysqli = $database->getConnection();
    
    // Laboratuvar bilgilerini al
    $sql = "SELECT l.*, c.name as category_name FROM myo_laboratories l LEFT JOIN myo_categories c ON l.category_id = c.id WHERE l.id = '" . $mysqli->real_escape_string($lab_id) . "'";
    $result = $mysqli->query($sql);
    
    if ($result === false) {
        throw new Exception("Sorgu hatası: " . $mysqli->error);
    }
    
    $lab = $result->fetch_assoc();
    
    if (!$lab) {
        header('Location: iamodinson.php');
        exit();
    }
    
    // Laboratuvar cihazlarını resimleriyle birlikte al
    $sql = "SELECT d.*, 
                   (SELECT ei.url FROM myo_devices_images ei WHERE ei.devices_id = d.id ORDER BY ei.order_num ASC, ei.created_at ASC LIMIT 1) as device_image_url,
    (SELECT ei.alt_text FROM myo_devices_images ei WHERE ei.devices_id = d.id ORDER BY ei.order_num ASC, ei.created_at ASC LIMIT 1) as device_image_alt
               FROM myo_devices d 
               WHERE d.lab_id = '" . $mysqli->real_escape_string($lab_id) . "' 
               ORDER BY d.order_num ASC, d.created_at ASC";
    $result = $mysqli->query($sql);
    
    if ($result === false) {
        throw new Exception("Cihaz sorgu hatası: " . $mysqli->error);
    }
    
    $devices = [];
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    
    // Laboratuvar özel içeriklerini al (yeni tablo yapısı)
    $sql = "SELECT * FROM myo_lab_content_new WHERE lab_id = '" . $mysqli->real_escape_string($lab_id) . "'";
    $result = $mysqli->query($sql);
    
    if ($result === false) {
        throw new Exception("İçerik sorgu hatası: " . $mysqli->error);
    }
    
    $content = $result->fetch_assoc();
    
    // İçerikleri eski yapıya uyumlu hale getir
    $labContents = [];
    if ($content) {
        if ($content['lab_title']) {
            $labContents['lab_title'] = [
                'content_value' => $content['lab_title']
            ];
        }
        if ($content['main_image']) {
            $labContents['main_image'] = [
                'content_value' => $content['main_image'],
                'alt_text' => $content['alt_text']
            ];
        }
    }
    
    // Laboratuvar ana resmini al (önce özel içerik, sonra ilk cihazın resmi)
    $mainImage = null;
    if (isset($labContents['main_image'])) {
        $mainImage = [
            'url' => $labContents['main_image']['content_value'],
            'alt_text' => $labContents['main_image']['alt_text']
        ];
    } else {
        $sql = "SELECT ei.* FROM myo_devices_images ei 
               INNER JOIN myo_devices d ON ei.devices_id = d.id 
               WHERE d.lab_id = '" . $mysqli->real_escape_string($lab_id) . "' 
               ORDER BY ei.order_num ASC, ei.created_at ASC 
               LIMIT 1";
        $result = $mysqli->query($sql);
        
        if ($result === false) {
            throw new Exception("Ana resim sorgu hatası: " . $mysqli->error);
        }
        
        $mainImage = $result->fetch_assoc();
    }
    
} catch(Exception $e) {
    error_log('Lab detail error: ' . $e->getMessage());
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <p id="lab-about">
                        <?php 
                        // Yeni tablo yapısına göre öncelik sırası
                        if ($content && $content['detail_page_info']) {
                            echo nl2br(htmlspecialchars($content['detail_page_info']));
                        } elseif ($content && $content['catalog_info']) {
                            echo nl2br(htmlspecialchars($content['catalog_info']));
                        } elseif (isset($labContents['lab_title'])) {
                            echo nl2br(htmlspecialchars($labContents['lab_title']['content_value']));
                        } else {
                            echo "Bu laboratuvar " . htmlspecialchars($lab['category_name']) . " kategorisinde yer almaktadır. Laboratuvarımızda modern teknoloji ve güncel ekipmanlar kullanılarak eğitim ve araştırma faaliyetleri yürütülmektedir.";
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="devices-table-container">
                <h2>Laboratuvar Cihazları</h2>
                <?php if (!empty($devices)): ?>
                    <div class="devices-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>İşlemler</th>
                                    <th>ID</th>
                                    <th>Sıra</th>
                                    <th>Cihaz Adı</th>
                                    <th>Model</th>
                                    <th>Sayı</th>
                                    <th>Kullanım Amacı</th>
                                    <th>Oluşturulma Tarihi</th>
                                    <th>Güncellenme Tarihi</th>
                                    <th>Ekleyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devices as $device): ?>
                                    <tr>
                                        <td class="action-buttons">
                                            <button class="btn-edit" title="Düzenle">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </button>
                                            <button class="btn-copy" title="Kopyala">
                                                <i class="fas fa-copy"></i> Kopyala
                                            </button>
                                            <button class="btn-delete" title="Sil">
                                                <i class="fas fa-minus"></i> Sil
                                            </button>
                                        </td>
                                        <td><?php echo $device['id']; ?></td>
                                        <td><?php echo $device['order_num']; ?></td>
                                        <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                        <td><?php echo htmlspecialchars($device['device_model'] ?: 'NULL'); ?></td>
                                        <td><?php echo $device['device_count']; ?></td>
                                        <td><?php echo htmlspecialchars($device['purpose'] ?: 'NULL'); ?></td>
                                        <td><?php echo $device['created_at']; ?></td>
                                        <td><?php echo $device['updated_at'] ?: 'NULL'; ?></td>
                                        <td><?php echo htmlspecialchars($device['added_by'] ?: 'NULL'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-devices">
                        <p>Bu laboratuvarda henüz cihaz bulunmuyor.</p>
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
