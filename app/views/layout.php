<?php
/** @var callable $content */
$title = $title ?? 'Aplicación MVC';
$errors = $errors ?? [];
$old = $old ?? [];
$success = $_SESSION['success'] ?? null;
if ($success) {
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/">MVC DuckDB</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item me-2 text-white align-self-center">
                        Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="/logout">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(session_id()) ?>">
                            <button class="btn btn-outline-light" type="submit">Cerrar sesión</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Iniciar sesión</a></li>
                    <li class="nav-item"><a class="nav-link" href="/registro">Registro</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php $content(); ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
