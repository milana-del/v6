<?php
session_start();
if (isset($_SESSION['application_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Заполните оба поля.';
    } else {
        function getDB() {
            static $pdo = null;
            if ($pdo === null) {
                $db_host = 'localhost';
                $db_user = 'u82326';
                $db_pass = '4843119';
                $db_name = 'u82326';
                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Ошибка подключения к БД: " . $e->getMessage());
                }
            }
            return $pdo;
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['application_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            // Очищаем куки ошибок
            $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
            foreach ($fields as $f) {
                setcookie($f . '_error', '', 1);
                setcookie($f . '_value', '', 1);
            }
            setcookie('languages_value', '', 1);
            setcookie('contract_accepted_value', '', 1);
            setcookie('save', '', 1);
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – Лабораторная работа №6</title>
    <link rel="icon" type="image/x-icon" href="maini.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 500px;
            margin: 80px auto;
            background: rgba(20, 15, 30, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 30px;
            text-align: center;
        }
        .login-container h1 {
            margin-bottom: 20px;
        }
        .form-group {
            text-align: left;
        }
        button {
            width: 100%;
        }
        .back-link {
            margin-top: 20px;
        }
        .back-link { margin-top: 30px; text-align: center; }
        .back-link a { background: #8a2a8a; color: white; padding: 8px 24px; border-radius: 30px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
<div class="gradient-bg"></div>
<div class="floating-shape shape1"></div>
<div class="floating-shape shape2"></div>

<div class="login-container">
    <div class="site-header" style="justify-content: center; border-bottom: none; margin-bottom: 10px;">
        <h1>Вход в систему</h1>
    </div>
    <p>Введите логин и пароль, полученные при отправке анкеты</p>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($login_input) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div class="back-link">
        <a href="index.php">← Вернуться к анкете</a>
    </div>
</div>
</body>
</html>