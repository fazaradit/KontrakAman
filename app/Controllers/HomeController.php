<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class HomeController extends Controller {
    public function index(Request $req, Response $res) {
        return $this->json(['message' => 'Welcome to KontrakAman API']);
    }
}
