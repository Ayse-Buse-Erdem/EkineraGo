<?php

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register(): void
    {
        if (!is_post()) {
            redirect('register.php');
        }

        require_csrf();

        /*
         * Eski bir kullanıcı session'da açıksa temizliyoruz.
         * Böylece yeni kayıt işlemi eski hesaba bağlı kalmaz.
         * CSRF kontrolünden sonra yapılmalı; yoksa token bozulabilir.
         */
        if (isLoggedIn()) {
            logout_user();
        }

        $result = $this->authService->register($_POST);

        if (!$result['success']) {
            $old = $_POST;
            unset($old['password'], $old['password_confirmation'], $old['_csrf_token']);

            set_old($old);
            set_errors($result['errors']);
            flash_error('Kayıt işlemi tamamlanamadı.');

            redirect('register.php');
        }

        login_user($result['user']);

        flash_success('Kayıt başarılı. Hoş geldiniz!');

        redirect($this->authService->redirectPathForUser($result['user']));
    }

    public function login(): void
    {
        if (!is_post()) {
            redirect('login.php');
        }

        require_csrf();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($email, $password);

        if (!$result['success']) {
            set_old([
                'email' => $email,
            ]);

            set_errors($result['errors']);
            flash_error('Giriş işlemi başarısız.');

            redirect('login.php');
        }

        login_user($result['user']);

        flash_success('Giriş başarılı.');

        redirect($this->authService->redirectPathForUser($result['user']));
    }

    public function logout(): void
    {
        logout_user();

        flash_success('Çıkış yapıldı.');

        $next = $_GET['next'] ?? 'login.php';

        redirect($next);
    }
}