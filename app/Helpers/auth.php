<?php

declare(strict_types=1);

if (!function_exists('currentUser')) {
    function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId(): ?int
    {
        $user = currentUser();

        return isset($user['id']) ? (int) $user['id'] : null;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return currentUser() !== null;
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'status' => $user['status'] ?? 'active',
        ];
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        unset($_SESSION['user']);

        session_regenerate_id(true);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (!isLoggedIn()) {
            flash_error('Bu sayfaya erişmek için giriş yapmalısınız.');
            redirect('login.php');
        }
    }
}

if (!function_exists('requireGuest')) {
    function requireGuest(): void
    {
        if (isLoggedIn()) {
            $user = currentUser();

            if (($user['role'] ?? '') === ROLE_PRODUCER) {
                redirect('producer/dashboard.php');
            }

            if (($user['role'] ?? '') === ROLE_ADMIN) {
                redirect('admin/dashboard.php');
            }

            redirect('consumer/dashboard.php');
        }
    }
}

if (!function_exists('hasRole')) {
    function hasRole(string $role): bool
    {
        $user = currentUser();

        return $user !== null && ($user['role'] ?? null) === $role;
    }
}

if (!function_exists('requireRole')) {
    function requireRole(string $role): void
    {
        requireLogin();

        if (!hasRole($role)) {
            flash_error('Bu sayfaya erişim yetkiniz yok.');
            redirect('index.php');
        }
    }
}

if (!function_exists('isConsumer')) {
    function isConsumer(): bool
    {
        return hasRole(ROLE_CONSUMER);
    }
}

if (!function_exists('isProducer')) {
    function isProducer(): bool
    {
        return hasRole(ROLE_PRODUCER);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return hasRole(ROLE_ADMIN);
    }
}

if (!function_exists('requireConsumer')) {
    function requireConsumer(): void
    {
        requireRole(ROLE_CONSUMER);
    }
}

if (!function_exists('requireProducer')) {
    function requireProducer(): void
    {
        requireRole(ROLE_PRODUCER);
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        requireRole(ROLE_ADMIN);
    }
}