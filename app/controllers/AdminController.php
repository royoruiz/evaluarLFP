<?php

class AdminController extends Controller
{
    public function index(): void
    {
        $this->ensureAdmin();

        $cycleModel = new CycleModel();
        $moduleModel = new CycleModuleModel();
        $learningOutcomeModel = new LearningOutcomeModel();
        $criteriaModel = new EvaluationCriteriaModel();
        $userModel = new UserModel();

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        $success = $_SESSION['success'] ?? null;

        unset($_SESSION['errors'], $_SESSION['old']);
        if ($success) {
            $_SESSION['success'] = $success;
        }

        $this->render('admin/index', [
            'title' => 'Panel de administración',
            'cycles' => $cycleModel->getAll(),
            'modules' => $moduleModel->getAll(),
            'learningOutcomes' => $learningOutcomeModel->getAll(),
            'evaluationCriteria' => $criteriaModel->getAll(),
            'users' => $userModel->getAll(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function updateUserRole(): void
    {
        $this->ensureAdmin();

        $userId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        if ($userId <= 0 || !in_array($role, ['user', 'admin'], true)) {
            $_SESSION['errors']['general'] = 'No se pudo actualizar el rol del usuario.';
            $this->redirect('/admin');
        }

        $userModel = new UserModel();
        if (!$userModel->updateRole($userId, $role)) {
            $_SESSION['errors']['general'] = 'No se pudo actualizar el rol del usuario.';
            $this->redirect('/admin');
        }

        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
            $_SESSION['user_role'] = $role;
        }

        $_SESSION['success'] = 'Rol de usuario actualizado correctamente.';
        $this->redirect('/admin');
    }

    public function saveCycle(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');
        $familia = trim($_POST['familia'] ?? '');

        if ($codigo === '' || $nombre === '' || $familia === '') {
            $_SESSION['errors']['cycle'] = 'Todos los campos del ciclo formativo son obligatorios.';
            $_SESSION['old']['cycle'] = compact('codigo', 'nombre', 'familia');
            $this->redirect('/admin');
        }

        $cycleModel = new CycleModel();
        $cycleModel->save($codigo, $nombre, $familia);

        $_SESSION['success'] = 'Ciclo formativo guardado correctamente.';
        $this->redirect('/admin');
    }

    public function deleteCycle(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        if ($codigo === '') {
            $_SESSION['errors']['cycle'] = 'No se pudo eliminar el ciclo formativo.';
            $this->redirect('/admin');
        }

        $cycleModel = new CycleModel();
        $cycleModel->delete($codigo);

        $_SESSION['success'] = 'Ciclo formativo eliminado correctamente.';
        $this->redirect('/admin');
    }

    public function saveModule(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');
        $codigoCiclo = strtoupper(trim($_POST['codigo_ciclo'] ?? ''));
        $curso = (int)($_POST['curso'] ?? 0);

        if ($codigo === '' || strlen($codigo) !== 5 || $nombre === '' || $codigoCiclo === '' || $curso <= 0) {
            $_SESSION['errors']['module'] = 'Todos los campos del módulo son obligatorios y el código debe tener 5 caracteres.';
            $_SESSION['old']['module'] = compact('codigo', 'nombre', 'codigoCiclo', 'curso');
            $this->redirect('/admin');
        }

        $moduleModel = new CycleModuleModel();
        try {
            $moduleModel->save($codigo, $nombre, $codigoCiclo, $curso);
            $_SESSION['success'] = 'Módulo guardado correctamente.';
        } catch (Throwable $exception) {
            $_SESSION['errors']['module'] = 'No se pudo guardar el módulo. Verifica que el ciclo formativo exista.';
            $_SESSION['old']['module'] = compact('codigo', 'nombre', 'codigoCiclo', 'curso');
        }

        $this->redirect('/admin');
    }

    public function deleteModule(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        if ($codigo === '') {
            $_SESSION['errors']['module'] = 'No se pudo eliminar el módulo.';
            $this->redirect('/admin');
        }

        $moduleModel = new CycleModuleModel();
        $moduleModel->delete($codigo);

        $_SESSION['success'] = 'Módulo eliminado correctamente.';
        $this->redirect('/admin');
    }

    public function saveLearningOutcome(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $numero = trim($_POST['numero'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $codigoModulo = strtoupper(trim($_POST['codigo_modulo'] ?? ''));
        $codigoCiclo = strtoupper(trim($_POST['codigo_ciclo'] ?? ''));

        if ($codigo === '' || $numero === '' || $descripcion === '' || $codigoModulo === '' || $codigoCiclo === '') {
            $_SESSION['errors']['learning_outcome'] = 'Todos los campos del resultado de aprendizaje son obligatorios.';
            $_SESSION['old']['learning_outcome'] = compact('codigo', 'numero', 'descripcion', 'codigoModulo', 'codigoCiclo');
            $this->redirect('/admin');
        }

        $learningOutcomeModel = new LearningOutcomeModel();
        try {
            $learningOutcomeModel->save($codigo, $numero, $descripcion, $codigoModulo, $codigoCiclo);
            $_SESSION['success'] = 'Resultado de aprendizaje guardado correctamente.';
        } catch (Throwable $exception) {
            $_SESSION['errors']['learning_outcome'] = 'No se pudo guardar el resultado de aprendizaje. Verifica los códigos proporcionados.';
            $_SESSION['old']['learning_outcome'] = compact('codigo', 'numero', 'descripcion', 'codigoModulo', 'codigoCiclo');
        }

        $this->redirect('/admin');
    }

    public function deleteLearningOutcome(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        if ($codigo === '') {
            $_SESSION['errors']['learning_outcome'] = 'No se pudo eliminar el resultado de aprendizaje.';
            $this->redirect('/admin');
        }

        $learningOutcomeModel = new LearningOutcomeModel();
        $learningOutcomeModel->delete($codigo);

        $_SESSION['success'] = 'Resultado de aprendizaje eliminado correctamente.';
        $this->redirect('/admin');
    }

    public function saveEvaluationCriterion(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $letra = strtoupper(trim($_POST['letra'] ?? ''));
        $descripcion = trim($_POST['descripcion'] ?? '');
        $codigoResultado = strtoupper(trim($_POST['codigo_resultado'] ?? ''));

        if ($codigo === '' || $letra === '' || strlen($letra) !== 1 || $descripcion === '' || $codigoResultado === '') {
            $_SESSION['errors']['evaluation_criterion'] = 'Todos los campos del criterio de evaluación son obligatorios y la letra debe tener un carácter.';
            $_SESSION['old']['evaluation_criterion'] = compact('codigo', 'letra', 'descripcion', 'codigoResultado');
            $this->redirect('/admin');
        }

        $criteriaModel = new EvaluationCriteriaModel();
        try {
            $criteriaModel->save($codigo, $letra, $descripcion, $codigoResultado);
            $_SESSION['success'] = 'Criterio de evaluación guardado correctamente.';
        } catch (Throwable $exception) {
            $_SESSION['errors']['evaluation_criterion'] = 'No se pudo guardar el criterio de evaluación. Verifica el resultado de aprendizaje seleccionado.';
            $_SESSION['old']['evaluation_criterion'] = compact('codigo', 'letra', 'descripcion', 'codigoResultado');
        }

        $this->redirect('/admin');
    }

    public function deleteEvaluationCriterion(): void
    {
        $this->ensureAdmin();

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        if ($codigo === '') {
            $_SESSION['errors']['evaluation_criterion'] = 'No se pudo eliminar el criterio de evaluación.';
            $this->redirect('/admin');
        }

        $criteriaModel = new EvaluationCriteriaModel();
        $criteriaModel->delete($codigo);

        $_SESSION['success'] = 'Criterio de evaluación eliminado correctamente.';
        $this->redirect('/admin');
    }

    private function ensureAdmin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
            $_SESSION['errors']['general'] = 'No tienes permisos para acceder a esta sección.';
            $this->redirect('/');
        }
    }
}
