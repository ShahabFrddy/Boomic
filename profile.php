<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);

$success = '';
$error = '';

// ایجاد پوشه uploads اگر وجود ندارد
if (!file_exists('uploads')) {
    if (!mkdir('uploads', 0755, true)) {
        $error = 'خطا در ایجاد پوشه آپلود';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bio = trim($_POST['bio']);
    
    // آپلود عکس
    $avatar = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        
        // بررسی نوع فایل
        if (in_array($file_type, $allowed_types)) {
            // بررسی سایز فایل (حداکثر 5MB)
            if ($file_size <= 5 * 1024 * 1024) {
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = 'uploads/' . $new_filename;
                
                // اطمینان از وجود پوشه uploads
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // حذف عکس قبلی اگر عکس پیش‌فرض نباشد
                    if ($avatar != 'default.png' && file_exists('uploads/' . $avatar)) {
                        unlink('uploads/' . $avatar);
                    }
                    $avatar = $new_filename;
                } else {
                    $error = 'خطا در آپلود عکس. لطفاً از وجود پوشه uploads اطمینان حاصل کنید';
                }
            } else {
                $error = 'حجم فایل باید کمتر از 5 مگابایت باشد';
            }
        } else {
            $error = 'فرمت فایل مجاز نیست. فقط JPG, PNG و GIF مجاز هستند';
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != 4) {
        // خطا 4 یعنی هیچ فایلی انتخاب نشده
        $upload_errors = [
            1 => 'حجم فایل بیش از حد مجاز است',
            2 => 'حجم فایل بیش از حد مجاز است',
            3 => 'فایل به صورت ناقص آپلود شده است',
            6 => 'پوشه موقت وجود ندارد',
            7 => 'نوشتن فایل روی دیسک失敗 شد',
            8 => 'یک افزایش PHP باعث توقف آپلود فایل شده است'
        ];
        $error = $upload_errors[$_FILES['avatar']['error']] ?? 'خطای ناشناخته در آپلود فایل';
    }
    
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$avatar, $bio, $user_id])) {
            $success = 'پروفایل با موفقیت به‌روزرسانی شد';
            $user = getUser($user_id); // دریافت اطلاعات به‌روز شده
        } else {
            $error = 'خطا در به‌روزرسانی پروفایل';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پروفایل - Discord Clone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            color: #dcddde;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: #2f3136;
            padding: 20px;
            border-radius: 8px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-left: 20px;
            object-fit: cover;
        }

        .profile-info h2 {
            color: white;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: #b9bbbe;
        }

        .profile-bio {
            background-color: #2f3136;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .profile-bio h3 {
            margin-bottom: 10px;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b9bbbe;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background-color: #40444b;
            border: none;
            border-radius: 3px;
            color: #dcddde;
            font-size: 16px;
        }

        .btn {
            background-color: #5865f2;
            border: none;
            border-radius: 3px;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 10px 20px;
        }

        .btn:hover {
            background-color: #4752c4;
        }

        .servers-sidebar {
            width: 72px;
            background-color: #202225;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .server-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #36393f;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-radius 0.2s, background-color 0.2s;
            color: white;
            font-size: 18px;
        }

        .server-icon:hover {
            border-radius: 16px;
            background-color: #5865f2;
        }
    </style>
</head>
<body>
    <div class="servers-sidebar">
        <div class="server-icon" onclick="location.href='index.php'" title="بازگشت">
            ←
        </div>
    </div>
    
    <div class="profile-container">
        <h1>پروفایل کاربر</h1>
        
        <?php if($success): ?>
            <div style="color: #3ba55c; margin-bottom: 15px; padding: 10px; background-color: rgba(59, 165, 92, 0.1); border-radius: 4px;">
                <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div style="color: #ed4245; margin-bottom: 15px; padding: 10px; background-color: rgba(237, 66, 69, 0.1); border-radius: 4px;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="profile-header">
                <div>
                    <img class="profile-avatar" src="uploads/<?= $user['avatar'] ?>" alt="<?= $user['username'] ?>" 
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iNDAiIGN5PSI0MCIgcj0iNDAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iNDAiIGN5PSIzMCIgcj0iMTUiIGZpbGw9IiNkY2RkZGUiLz4KPHBhdGggZD0iTTQwIDUwQzUwIDUwIDU4IDU4IDU4IDY4SDIyQzIyIDU4IDMwIDUwIDQwIDUwWiIgZmlsbD0iI2RjZGRkZSIvPgo8L3N2Zz4K'">
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($user['username']) ?></h2>
                    <p>عضو شده در: <?= date('Y/m/d', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="avatar">عکس پروفایل</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif">
                <small style="color: #72767d; font-size: 12px;">فرمت‌های مجاز: JPG, PNG, GIF - حداکثر حجم: 5MB</small>
            </div>
            
            <div class="form-group">
                <label for="bio">بیوگرافی</label>
                <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="درباره خودتان بنویسید..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn">ذخیره تغییرات</button>
        </form>
        
        <div class="profile-bio">
            <h3>درباره من</h3>
            <p><?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : 'هنوز بیوگرافی اضافه نشده است.' ?></p>
        </div>
    </div>

    <script>
        // نمایش پیش‌نمایش عکس قبل از آپلود
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-avatar').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>