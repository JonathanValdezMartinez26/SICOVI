<?php

namespace Controllers;

use Core\Controller;

class Inicio extends Controller
{
    public function index()
    {
        self::render("inicio");
    }
}
