<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Service\AppointmentManager;
use Doctrine\ORM\EntityManagerInterface;
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
        return $this->render('app/index.html.twig', ['success' => false]);
    }

    #[Route('/tarifs', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('app/pricing.html.twig', []);
    }

    /**
     * @throws \Exception
     */
    #[Route('/appointment', name: 'app_appointment')]
    public function index(AppointmentManager $appointmentManager, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->get('date') != null){
            //TODO:: check if this client exists and have rdv
            //create new client
            $client = new Client();
            $client->setCreatedAt(new \DateTimeImmutable());
            $client->setFirstname($request->get('firstname'));
            $client->setLastname($request->get('lastname'));
            $client->setPhone($request->get('phone'));
            $entityManager->persist($client);

            $appointment = new Appointment();
            $appointment->setDate(new \DateTime($request->get('date')));
            $appointment->setSlot($request->get('slot'));
            $appointment->setClient($client);
            $slots = explode('-',$request->get('slot') );
            $appointment->setStart($slots[0]);
            $appointment->setEnd($slots[1]);
            $entityManager->persist($appointment);
            $entityManager->flush();

            //$this->addFlash('success', 'Votre rendez-vous a bien été pris en compte, 24h avant votre rendez-vous vous recevrez un sms de rappel');
            return $this->redirectToRoute('app_index', ['success' => true]);
        }
        $now = new \DateTime();
        $avaibles = $appointmentManager->getAvailableSlots2($now->format("Y-m-d"));

        return $this->render('appointment/index.html.twig', [
            'avaibles' => $avaibles,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/appointment/list', name: 'app_appointment_list')]
    public function getAppointmentsList(Request $request, AppointmentManager $appointmentManager): JsonResponse
    {
       $avaibles = $appointmentManager->getAvailableSlots2($request->get('date'));
       // get used appointment from
        //$appointmentManager->getUsedSlots($request->get('date'));
       return $this->json(["data" => $avaibles]);
    }

}
