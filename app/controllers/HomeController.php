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
        $cycleModuleModel = new CycleModuleModel();

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];

        $showModuleWizard = $_SESSION['module_wizard_show'] ?? false;
        $activeTab = $_GET['tab'] ?? ($_SESSION['active_tab'] ?? 'modules');

        unset(
            $_SESSION['errors'],
            $_SESSION['old'],
            $_SESSION['module_wizard_show'],
            $_SESSION['active_tab']
        );

        $this->render('home/index', [
            'title' => 'Panel principal',
            'user' => [
                'id' => $userId,
                'name' => $_SESSION['user_name'] ?? 'Usuario',
            ],
            'modules' => $moduleModel->getByUserId($userId),
            'evaluations' => $evaluationModel->getByUserId($userId),
            'catalogModules' => $cycleModuleModel->getAll(),
            'errors' => $errors,
            'old' => $old,
            'activeTab' => in_array($activeTab, ['modules', 'evaluations'], true) ? $activeTab : 'modules',
            'showModuleWizard' => $showModuleWizard || !empty($errors['module_wizard'] ?? []),
        ]);
    }
}
