<?php
$db_user = 'u82326';
$db_pass = '4843119';
$db_name = 'u82326';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("
        SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages
        FROM application a
        LEFT JOIN application_language al ON a.id = al.application_id
        LEFT JOIN language l ON al.language_id = l.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сохранённые анкеты</title>
    <link rel="icon" type="image/x-icon" href="maini.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .back-link { margin-top: 30px; text-align: center; }
        .back-link a { background: #8a2a8a; color: white; padding: 8px 24px; border-radius: 30px; text-decoration: none; display: inline-block; }
        table td:last-child, table th:last-child { white-space: normal; }
        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 20px;
            background: rgba(30, 20, 45, 0.5);
            backdrop-filter: blur(4px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(25, 15, 35, 0.7);
            border-radius: 20px;
            overflow: hidden;
            font-size: 0.9rem;
        }

        th {
            background: #4a1a6a;
            color: #ffccf0;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-bottom: 2px solid #ff80b0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #3a2a5a;
            color: #f0e6f6;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(120, 50, 150, 0.3);
            transition: background 0.2s;
        }

        
        td:nth-child(7) { 
            max-width: 250px;
            word-break: break-word;
        }
        td:nth-child(8) { 
            max-width: 300px;
            white-space: pre-wrap;
            word-break: break-word;
        }


        @media (max-width: 768px) {
            th, td {
                padding: 8px 6px;
                font-size: 0.8rem;
            }
            td:nth-child(7), td:nth-child(8) {
                max-width: 180px;
            }
        }
    </style>
</head>
<body>
<div class="gradient-bg"></div>
<div class="floating-shape shape1"></div>
<div class="floating-shape shape2"></div>

<div class="container">
    <div class="site-header">
        <div class="header-left">
            <img src="logon.png" alt="Profile" class="profile-photo" onerror="this.src='https://randomuser.me/api/portraits/women/1.jpg'">
            <h1>Сохранённые анкеты</h1>
        </div>
        <div class="nav-links">
            <a href="index.php">Форма</a>
            
        </div>
    </div>

    <p>Всего записей: <?= count($applications) ?></p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Дата рожд.</th><th>Пол</th><th>Языки</th><th>Биография</th><th>Дата создания</th></tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td><?= htmlspecialchars($app['id']) ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['phone']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= htmlspecialchars($app['birth_date']) ?></td>
                <td><?= $app['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td><?= nl2br(htmlspecialchars($app['biography'])) ?></td>
                <td><?= htmlspecialchars($app['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="back-link">
        <a href="index.php">← Вернуться к форме</a>
    </div>

    
</div>
</body>
</html>