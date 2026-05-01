<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// CSRF токен для форм редактирования
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === ПОДКЛЮЧЕНИЕ К БД ===
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
            error_log($e->getMessage());
            die("Внутренняя ошибка сервера. Попробуйте позже.");
        }
    }
    return $pdo;
}

$pdo = getDB();

// === HTTP-АВТОРИЗАЦИЯ ===
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Админ-панель Задание 6"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<div class="gradient-bg"></div>
          <div class="container" style="text-align:center; margin-top:20%;">
              <h1>Доступ запрещён</h1>
              <p>Введите логин и пароль администратора.</p>
          </div>';
    exit;
}

$auth_login = $_SERVER['PHP_AUTH_USER'];
$auth_pass  = $_SERVER['PHP_AUTH_PW'];

$stmt = $pdo->prepare("SELECT password_hash FROM admin WHERE login = ?");
$stmt->execute([$auth_login]);
$admin_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_row || !password_verify($auth_pass, $admin_row['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Админ-панель Задание 6"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<div class="gradient-bg"></div>
          <div class="container" style="text-align:center; margin-top:20%;">
              <h1>Неверный логин или пароль!</h1>
              <p>Попробуйте ещё раз (admin / admin123).</p>
          </div>';
    exit;
}

// === ОБРАБОТКА ДЕЙСТВИЙ ===
$messages = [];
$edit_errors = [];

// Удаление
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM application WHERE id = ?")->execute([$id]);
    $messages[] = '<div class="success-message">Анкета №' . $id . ' успешно удалена</div>';
}

// Редактирование – загрузка данных
$edit_id = 0;
$edit_values = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_values = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_values) {
        // Языки
        $lang_stmt = $pdo->prepare("
            SELECT l.name 
            FROM application_language al 
            JOIN language l ON al.language_id = l.id 
            WHERE al.application_id = ?
        ");
        $lang_stmt->execute([$edit_id]);
        $edit_values['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Сохранение редактирования (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF. Обновите страницу.');
    }

    $id = (int)$_POST['edit_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $allowed_languages = [
        'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
        'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go',
        'Ruby', 'Swift', 'Kotlin', 'TypeScript', 'Rust', 'Dart', 'Elixir',
        'F#', 'Lua', 'Perl', 'R', 'MATLAB', 'Julia', 'Groovy', 'Objective-C',
        'Assembly', 'VHDL', 'Erlang'
    ];
    $allowed_genders = ['male', 'female'];

    $has_error = false;

    // ФИО
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        $edit_errors['full_name'] = 'ФИО обязательно, только буквы и пробелы, макс. 150 символов.';
        $has_error = true;
    }
    // Телефон
    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        $edit_errors['phone'] = 'Телефон: 6–12 цифр, разрешены +, -, (, ), пробел.';
        $has_error = true;
    }
    // Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $edit_errors['email'] = 'Некорректный email.';
        $has_error = true;
    }
    // Дата рождения
    if (empty($birth_date)) {
        $edit_errors['birth_date'] = 'Дата рождения обязательна.';
        $has_error = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            $edit_errors['birth_date'] = 'Некорректная дата (ГГГГ-ММ-ДД, не позже сегодня).';
            $has_error = true;
        }
    }
    // Пол
    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        $edit_errors['gender'] = 'Выберите пол.';
        $has_error = true;
    }
    // Биография (необязательная, но с ограничением длины)
    if (strlen($biography) > 10000) {
        $edit_errors['biography'] = 'Биография не должна превышать 10000 символов.';
        $has_error = true;
    }
    // Чекбокс
    if (!$contract_accepted) {
        $edit_errors['contract_accepted'] = 'Необходимо подтвердить согласие.';
        $has_error = true;
    }
    // Языки
    if (empty($languages)) {
        $edit_errors['languages'] = 'Выберите хотя бы один язык программирования.';
        $has_error = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                $edit_errors['languages'] = 'Выбран недопустимый язык.';
                $has_error = true;
                break;
            }
        }
    }

    if ($has_error) {
        // сохраняем введённые значения для повторного отображения
        $edit_values = [
            'id' => $id,
            'full_name' => $full_name,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'biography' => $biography,
            'contract_accepted' => $contract_accepted,
            'languages' => $languages
        ];
        $edit_id = $id;
        $messages[] = '<div class="error-message">Исправьте ошибки в форме.</div>';
    } else {
        // Валидация пройдена – обновляем БД
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name = ?, phone = ?, email = ?, birth_date = ?, 
                    gender = ?, biography = ?, contract_accepted = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $id]);

            // Удаляем старые языки
            $pdo->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$id]);

            // Вставляем новые
            $lang_map = [];
            $stmt = $pdo->query("SELECT id, name FROM language");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lang_map[$row['name']] = $row['id'];
            }
            $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_name) {
                if (isset($lang_map[$lang_name])) $stmt->execute([$id, $lang_map[$lang_name]]);
            }

            $pdo->commit();
            $messages[] = '<div class="success-message">Анкета №' . $id . ' успешно обновлена</div>';
            $edit_id = 0; // выходим из режима редактирования
        } catch (Exception $e) {
            $pdo->rollBack();
            $messages[] = '<div class="error-message">Ошибка сохранения: ' . $e->getMessage() . '</div>';
        }
    }
}

// === ЗАГРУЗКА ДАННЫХ ДЛЯ ТАБЛИЦЫ ===
$applications = [];
$stmt = $pdo->query("
    SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages_list
    FROM application a
    LEFT JOIN application_language al ON a.id = al.application_id
    LEFT JOIN language l ON al.language_id = l.id
    GROUP BY a.id
    ORDER BY a.id DESC
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === СТАТИСТИКА ПО ЯЗЫКАМ ===
$stats = [];
$stmt = $pdo->query("
    SELECT l.name, COUNT(DISTINCT al.application_id) AS cnt
    FROM language l
    LEFT JOIN application_language al ON l.id = al.language_id
    GROUP BY l.id
    ORDER BY cnt DESC, l.name
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Список всех языков для выпадающего списка в форме редактирования
$all_languages = $pdo->query("SELECT name FROM language ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Задание 6</title>
    <link rel="icon" type="image/x-icon" href="maini.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-table, .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(25, 15, 35, 0.7);
            border-radius: 20px;
            overflow: hidden;
            margin: 20px 0;
        }
        .admin-table th, .stats-table th {
            background: #4a1a6a;
            color: #ffccf0;
            padding: 12px;
            text-align: left;
        }
        .admin-table td, .stats-table td {
            padding: 10px;
            border-bottom: 1px solid #3a2a5a;
        }
        .admin-table tr:hover td, .stats-table tr:hover td {
            background: rgba(120, 50, 150, 0.3);
        }
        .edit-link, .delete-link {
            display: inline-block;
            margin: 0 5px;
            padding: 4px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .edit-link { background: #6a1a8a; color: white; }
        .delete-link { background: #8a2a2a; color: white; }
        .action-buttons { white-space: nowrap; }
        .admin-edit-form {
            background: rgba(35, 25, 55, 0.8);
            border-radius: 30px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .field-error { color: #ffb0b0; font-size: 0.8rem; }
        .cancel-link { margin-top: 15px; text-align: center; }
        .cancel-link a { color: #ff80b0; }
        .btn-save { background: #d94a8a; }
    </style>
</head>
<body>
<div class="gradient-bg"></div>
<div class="floating-shape shape1"></div>
<div class="floating-shape shape2"></div>
<div class="floating-shape shape3"></div>
<div class="floating-shape shape4"></div>

<div class="container">
    <div class="site-header">
        <div class="header-left">
            <img src="logon.png" alt="Profile" class="profile-photo" onerror="this.src='https://randomuser.me/api/portraits/women/1.jpg'">
            <h1>🔧 Админ-панель</h1>
        </div>
        <div class="nav-links">
            <a href="index.php">Главная</a>
            <a href="v.php">Анкеты</a>
            <a href="admin.php" style="color:#ff80b0;">Админ</a>
        </div>
    </div>

    <p style="text-align:center; margin-bottom:20px;">Авторизован как <strong><?= htmlspecialchars($auth_login) ?></strong></p>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg) echo $msg; ?>
    <?php endif; ?>

    <!-- РЕДАКТИРОВАНИЕ -->
    <?php if ($edit_id > 0 && !empty($edit_values)): ?>
        <div class="admin-edit-form">
            <h2 style="margin-bottom: 15px;">Редактирование анкеты №<?= $edit_id ?></h2>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($edit_values['full_name'] ?? '') ?>">
                    <?php if (isset($edit_errors['full_name'])): ?>
                        <span class="field-error"><?= $edit_errors['full_name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($edit_values['phone'] ?? '') ?>">
                    <?php if (isset($edit_errors['phone'])): ?>
                        <span class="field-error"><?= $edit_errors['phone'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_values['email'] ?? '') ?>">
                    <?php if (isset($edit_errors['email'])): ?>
                        <span class="field-error"><?= $edit_errors['email'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($edit_values['birth_date'] ?? '') ?>">
                    <?php if (isset($edit_errors['birth_date'])): ?>
                        <span class="field-error"><?= $edit_errors['birth_date'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Пол</label>
                    <select name="gender">
                        <option value="male" <?= ($edit_values['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= ($edit_values['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Женский</option>
                    </select>
                    <?php if (isset($edit_errors['gender'])): ?>
                        <span class="field-error"><?= $edit_errors['gender'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Любимые языки (Ctrl+Click для множественного выбора)</label>
                    <select name="languages[]" multiple size="8">
                        <?php foreach ($all_languages as $lang): ?>
                            <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $edit_values['languages'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($edit_errors['languages'])): ?>
                        <span class="field-error"><?= $edit_errors['languages'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="biography" rows="5"><?= htmlspecialchars($edit_values['biography'] ?? '') ?></textarea>
                    <?php if (isset($edit_errors['biography'])): ?>
                        <span class="field-error"><?= $edit_errors['biography'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="contract_accepted" value="1" <?= !empty($edit_values['contract_accepted']) ? 'checked' : '' ?>>
                        Я ознакомлен(а) с контрактом
                    </label>
                    <?php if (isset($edit_errors['contract_accepted'])): ?>
                        <span class="field-error"><?= $edit_errors['contract_accepted'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-save">Сохранить изменения</button>
                <div class="cancel-link">
                    <a href="admin.php">Отмена</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ТАБЛИЦА ВСЕХ АНКЕТ -->
    <h2 style="margin-top: 20px;">Все анкеты пользователей</h2>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Дата рожд.</th><th>Пол</th><th>Языки</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['birth_date']) ?></td>
                    <td><?= $app['gender'] === 'male' ? 'М' : 'Ж' ?></td>
                    <td><?= htmlspecialchars($app['languages_list'] ?? '—') ?></td>
                    <td class="action-buttons">
                        <a href="admin.php?edit=<?= $app['id'] ?>" class="edit-link">✏️ Ред.</a>
                        <a href="admin.php?delete=<?= $app['id'] ?>" class="delete-link" onclick="return confirm('Удалить анкету №<?= $app['id'] ?>?')">🗑 Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($applications)): ?>
                    <tr><td colspan="8" style="text-align:center;">Пока нет ни одной анкеты</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- СТАТИСТИКА -->
    <h2>Статистика по языкам программирования</h2>
    <div class="table-responsive">
        <table class="stats-table">
            <thead><tr><th>Язык</th><th>Количество пользователей</th></tr></thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><strong><?= $s['cnt'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="back-link" style="margin-top: 30px;">
        <a href="index.php">← Вернуться к главной форме</a>
    </div>
</div>
</body>
</html>