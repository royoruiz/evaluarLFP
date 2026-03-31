<?php

class GroupController extends Controller
{
    public function edit(): void
    {
        $this->ensureAuthenticated();

        $groupId = (int) ($_GET['id'] ?? 0);
        if ($groupId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo acceder al grupo solicitado.';
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $groupModel = new UserGroupModel();
        $group = $groupModel->findForUser($groupId, $userId);

        if ($group === null || ($group['status'] ?? 'Activa') !== 'Activa') {
            $_SESSION['errors']['general'] = 'El grupo solicitado no existe o no está disponible.';
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/');
        }

        $studentModel = new GroupStudentModel();

        $sessionErrors = $_SESSION['errors'] ?? [];
        $sessionOld = $_SESSION['old'] ?? [];

        $formErrors = $sessionErrors['group_student_form'] ?? [];
        $oldForm = $sessionOld['group_student_form'] ?? [];

        unset(
            $_SESSION['errors']['group_student_form'],
            $_SESSION['old']['group_student_form']
        );

        $this->render('groups/edit', [
            'title' => 'Grupo: ' . ($group['group_name'] ?? ''),
            'group' => $group,
            'students' => $studentModel->getActiveByGroup($groupId),
            'errors' => [
                'group_student_form' => $formErrors,
            ],
            'old' => [
                'group_student_form' => $oldForm,
            ],
        ]);
    }

    public function store(): void
    {
        $this->ensureAuthenticated();

        $groupName = trim($_POST['group_name'] ?? '');

        if ($groupName === '') {
            $_SESSION['errors']['group_form']['group_name'] = 'Debes indicar un nombre para el grupo.';
            $_SESSION['old']['group_form']['group_name'] = $groupName;
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/?tab=groups');
        }

        $groupModel = new UserGroupModel();
        $groupModel->create((int) $_SESSION['user_id'], $groupName);

        $_SESSION['success'] = 'Grupo creado correctamente.';
        $_SESSION['active_tab'] = 'groups';
        $this->redirect('/?tab=groups');
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();

        $groupId = (int) ($_POST['group_id'] ?? 0);
        $userId = (int) $_SESSION['user_id'];

        $groupModel = new UserGroupModel();
        $group = $groupModel->findForUser($groupId, $userId);

        if ($group === null || ($group['status'] ?? 'Activa') !== 'Activa') {
            $_SESSION['errors']['general'] = 'No se pudo eliminar el grupo indicado.';
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/?tab=groups');
        }

        $groupModel->markAsDeleted($groupId);

        $_SESSION['success'] = 'Grupo eliminado correctamente.';
        $_SESSION['active_tab'] = 'groups';
        $this->redirect('/?tab=groups');
    }

    public function storeStudent(): void
    {
        $this->ensureAuthenticated();

        $groupId = (int) ($_POST['group_id'] ?? 0);
        $nia = trim($_POST['nia'] ?? '');
        $studentName = trim($_POST['student_name'] ?? '');

        $groupModel = new UserGroupModel();
        $group = $groupModel->findForUser($groupId, (int) $_SESSION['user_id']);
        if ($group === null || ($group['status'] ?? 'Activa') !== 'Activa') {
            $_SESSION['errors']['general'] = 'No se pudo acceder al grupo solicitado.';
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/?tab=groups');
        }

        $errors = [];
        if ($nia === '') {
            $errors['nia'] = 'El NIA es obligatorio.';
        }

        if ($studentName === '') {
            $errors['student_name'] = 'El nombre del alumno es obligatorio.';
        }

        if (!empty($errors)) {
            $_SESSION['errors']['group_student_form'] = $errors;
            $_SESSION['old']['group_student_form'] = [
                'nia' => $nia,
                'student_name' => $studentName,
            ];

            $this->redirect('/grupos/editar?id=' . $groupId);
        }

        $studentModel = new GroupStudentModel();
        $studentModel->createOrRestore($groupId, $nia, $studentName);

        $_SESSION['success'] = 'Alumno guardado correctamente.';
        $this->redirect('/grupos/editar?id=' . $groupId);
    }

    public function deleteStudent(): void
    {
        $this->ensureAuthenticated();

        $studentId = (int) ($_POST['student_id'] ?? 0);
        $groupId = (int) ($_POST['group_id'] ?? 0);

        $groupModel = new UserGroupModel();
        $group = $groupModel->findForUser($groupId, (int) $_SESSION['user_id']);
        if ($group === null || ($group['status'] ?? 'Activa') !== 'Activa') {
            $_SESSION['errors']['general'] = 'No se pudo acceder al grupo solicitado.';
            $_SESSION['active_tab'] = 'groups';
            $this->redirect('/?tab=groups');
        }

        $studentModel = new GroupStudentModel();
        $student = $studentModel->findById($studentId);

        if ($student === null || (int) ($student['user_group_id'] ?? 0) !== $groupId || ($student['status'] ?? 'Activa') !== 'Activa') {
            $_SESSION['errors']['general'] = 'No se pudo eliminar el alumno indicado.';
            $this->redirect('/grupos/editar?id=' . $groupId);
        }

        $studentModel->markAsDeleted($studentId);

        $_SESSION['success'] = 'Alumno eliminado correctamente.';
        $this->redirect('/grupos/editar?id=' . $groupId);
    }

    private function ensureAuthenticated(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
}
