<?php
require_once 'backend/auth.php';

gmgCerrarSesion(); // vacía la sesión y borra la cookie (F3-1)

header('Location: login.php');
exit;
