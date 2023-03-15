<?php

namespace App\Controller;

use App\Service\AppointmentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentController extends AbstractController
{

    #[Route('/', name: 'app_index')]
    public function app(): Response
    {
        return $this->render('app/index.html.twig', []);
    }

    #[Route('/tarifs', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('app/pricing.html.twig', []);
    }

    #[Route('/appointment', name: 'app_appointment')]
    public function index(AppointmentManager $appointmentManager): Response
    {
        $avaibles = $appointmentManager->getAviableSlots();

        return $this->render('appointment/index.html.twig', [
            'avaibles' => $avaibles,
        ]);
    }

    #[Route('/appointment/list', name: 'app_appointment_list')]
    public function getAppointmentsList(Request $request, AppointmentManager $appointmentManager): JsonResponse
    {
       $avaibles = $appointmentManager->getAviableSlots();
       // get used appointment from

       return $this->json(["data" => $avaibles]);
    }

}
