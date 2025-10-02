<?php

class HomeController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $this->render('home/index', [
            'title' => 'Panel principal',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? 'Usuario',
            ],
        ]);
    }
}
