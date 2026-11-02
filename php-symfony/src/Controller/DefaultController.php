<?php

declare(strict_types=1);

namespace App\Citizen\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function dashboard(): Response
    {
        return $this->render('citizen/dashboard.html.twig');
    }
}