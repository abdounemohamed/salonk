<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'data' => json_encode([]),
        ]);
    }

    #[Route('/availablity', name: 'admin_not_available_day')]
    public function AddNotAvailableDay(): Response
    {

        return $this->render('admin/availablity.html.twig', [

        ]);
    }

}
