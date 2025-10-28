<?php

class HomeController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $userId = (int) $_SESSION['user_id'];

        $moduleModel = new UserModuleModel();
        $evaluationModel = new UserModuleEvaluationModel();

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        $this->render('home/index', [
            'title' => 'Panel principal',
            'user' => [
                'id' => $userId,
                'name' => $_SESSION['user_name'] ?? 'Usuario',
            ],
            'modules' => $moduleModel->getByUserId($userId),
            'evaluations' => $evaluationModel->getByUserId($userId),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
