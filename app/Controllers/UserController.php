<?php
namespace App\Controllers;

class UserController
{
    public function home(): void
    {
        require __DIR__ . '/../../app/Views/user/user_index.php';
    }

    public function profile(): void
    {
        require __DIR__ . '/../../app/Views/user/user_profile.php';
    }

    public function search(): void
    {
        require __DIR__ . '/../../app/Views/user/user_search.php';
    }
}


