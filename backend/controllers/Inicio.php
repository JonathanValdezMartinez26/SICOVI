<?php

namespace Controllers;

use Core\Controller;

class Inicio extends Controller
{
    public function index()
    {
        self::set("titulo", CONFIGURACION['EMPRESA'] . ' | SICOVI');
        self::render("inicio");
    }
}
