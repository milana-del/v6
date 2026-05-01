<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета | Лабораторная работа №5</title>
    <link rel="icon" type="image/x-icon" href="maini.ico">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
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
            <h1>Анкета</h1>
        </div>
        <div class="nav-links">
            <a href="index.php">Главная</a>
            <a href="v.php">Просмотр анкет</a>
            <?php if ($is_logged_in): ?>
                <a href="index.php?logout=1" style="color:#ff80b0;">Выйти</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_logged_in): ?>
        <div class="success-message" style="background: #6a1a8a; margin-bottom: 15px;">
            ✅ Вы авторизованы (ID: <?= htmlspecialchars($user_id) ?>). Можете редактировать свои данные.
        </div>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?= $msg ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Прогресс-бар -->
    <div class="progress-section">
        <div class="steps-indicator" id="stepsIndicator">
            <div class="step-icon" data-step="0">1. Личные данные</div>
            <div class="step-icon" data-step="1">2. Детали</div>
            <div class="step-icon" data-step="2">3. Языки & биография</div>
            <div class="step-icon" data-step="3">4. Согласие</div>
        </div>
        <div class="progress-bar-container">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>

    <form method="post" action="index.php" id="multiStepForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <!-- Шаг 1 -->
        <div class="form-step" data-step="0">
            <div class="card-field" data-card="0">
                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($values['full_name'] ?? '') ?>">
                    <?php if (!empty($errors['full_name'])): ?><span class="field-error">Некорректное ФИО</span><?php endif; ?>
                </div>
            </div>
            <div class="card-field" data-card="1">
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($values['phone'] ?? '') ?>">
                    <?php if (!empty($errors['phone'])): ?><span class="field-error">Некорректный телефон</span><?php endif; ?>
                </div>
            </div>
            <div class="card-field" data-card="2">
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                    <?php if (!empty($errors['email'])): ?><span class="field-error">Некорректный email</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Шаг 2 -->
        <div class="form-step" data-step="1">
            <div class="card-field" data-card="3">
                <div class="form-group">
                    <label>Дата рождения *</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>">
                    <?php if (!empty($errors['birth_date'])): ?><span class="field-error">Некорректная дата</span><?php endif; ?>
                </div>
            </div>
            <div class="card-field" data-card="4">
                <div class="form-group">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
                    </div>
                    <?php if (!empty($errors['gender'])): ?><span class="field-error">Выберите пол</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Шаг 3 -->
        <div class="form-step" data-step="2">
            <div class="card-field" data-card="5">
                <div class="form-group">
                    <label>Любимые языки программирования *</label>
                    <select name="languages[]" multiple size="8">
                        <?php foreach ($languages_from_db as $lang): ?>
                            <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $values['languages'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['languages'])): ?><span class="field-error">Выберите хотя бы один язык</span><?php endif; ?>
                </div>
            </div>
            <div class="card-field" data-card="6">
                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="biography" rows="5"><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
                    <?php if (!empty($errors['biography'])): ?><span class="field-error">Биография слишком длинная</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Шаг 4 -->
        <div class="form-step" data-step="3">
            <div class="card-field" data-card="7">
                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="contract_accepted" value="1" <?= !empty($values['contract_accepted']) ? 'checked' : '' ?>>
                        Я ознакомлен(а) с контрактом *
                    </label>
                    <?php if (!empty($errors['contract_accepted'])): ?><span class="field-error">Необходимо подтвердить согласие</span><?php endif; ?>
                </div>
            </div>
            <div class="nav-buttons">
                <button type="button" class="btn-prev" style="visibility: hidden;">Назад</button>
                <button type="submit" class="btn-submit"><?= $is_logged_in ? 'Сохранить изменения' : 'Отправить анкету' ?></button>
            </div>
        </div>

        <div class="nav-buttons step-nav" style="justify-content: space-between; margin-top: 10px;">
            <button type="button" class="btn-prev" id="prevBtn">← Назад</button>
            <button type="button" class="btn-next" id="nextBtn">Далее →</button>
        </div>
    </form>
</div>

<script>
      
    const steps = document.querySelectorAll('.form-step');
    const stepIcons = document.querySelectorAll('.step-icon');
    const progressFill = document.getElementById('progressFill');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let currentStep = 0;

    function updateSteps() {
        steps.forEach((step, idx) => {
            step.classList.toggle('active-step', idx === currentStep);
        });
        stepIcons.forEach((icon, idx) => {
            icon.classList.toggle('active', idx === currentStep);
        });
        const percent = ((currentStep + 1) / steps.length) * 100;
        progressFill.style.width = percent + '%';
        
        prevBtn.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
        
        if (currentStep === steps.length - 1) {
            nextBtn.style.display = 'none';
        } else {
            nextBtn.style.display = 'inline-block';
        }
    }

    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateSteps();
            
            const activeStep = document.querySelector('.form-step.active-step');
            activeStep.style.animation = 'none';
            activeStep.offsetHeight; // reflow
            activeStep.style.animation = 'fadeFlip 0.6s ease-out';
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateSteps();
            const activeStep = document.querySelector('.form-step.active-step');
            activeStep.style.animation = 'none';
            activeStep.offsetHeight;
            activeStep.style.animation = 'fadeFlip 0.6s ease-out';
        }
    });

    updateSteps();

    
    const cards = document.querySelectorAll('.card-field');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                if (!entry.target.dataset.flipped) {
                    entry.target.classList.add('flip');
                    entry.target.dataset.flipped = 'true';
                    setTimeout(() => {
                        entry.target.classList.remove('flip');
                    }, 800);
                }
            }
        });
    }, { threshold: 0.3 });
    cards.forEach(card => observer.observe(card));

    
    if (document.querySelector('.success-message')) {
        canvasConfetti({
            particleCount: 200,
            spread: 70,
            origin: { y: 0.6 },
            startVelocity: 20,
            colors: ['#ff80b0', '#c97eff', '#ffffff']
        });
        setTimeout(() => canvasConfetti({ particleCount: 100, spread: 100, origin: { y: 0.5, x: 0.3 } }), 150);
        setTimeout(() => canvasConfetti({ particleCount: 100, spread: 100, origin: { y: 0.5, x: 0.7 } }), 300);
    }
</script>
</body>
</html>