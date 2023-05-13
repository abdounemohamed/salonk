<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\NotAvailableSlots;
use App\Repository\AppointmentRepository;
use App\Repository\ClientRepository;
use App\Repository\NotAvailableSlotsRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('', name: 'admin_index')]
    public function index
    (
        ClientRepository $clientRepository,
        AppointmentRepository $appointmentRepository,
        NotAvailableSlotsRepository $notAvailableSlotsRepository
    ): Response
    {
        $now = new \DateTime();

        $stats['clients'] = $clientRepository->count([]);
        $stats['rdvs'] = $appointmentRepository->count(['date' => $now]);
        $stats['rdvsTomorrow'] = $appointmentRepository->count(['date' => $now->modify("+1 day")]);
        $stats['off'] = $notAvailableSlotsRepository->findByDate($now->format("Y-m-d"));

        return $this->render('admin/index.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/agenda', name: 'admin_agenda')]
    public function agenda(): Response
    {
        return $this->render('admin/agenda.html.twig', [
            'data' => json_encode([]),
        ]);
    }

    #[Route('/clients', name: 'admin_clients')]
    public function clients(ClientRepository $clientRepository): Response
    {
        return $this->render('admin/clients.html.twig', [
            'clients' => $clientRepository->findAll()
        ]);
    }

    #[Route('/availablity', name: 'admin_not_available_day')]
    public function AddNotAvailableDay(): Response
    {

        return $this->render('admin/availablity.html.twig', [

        ]);
    }

    #[Route('/events', name: 'admin_events_all')]
    public function getEvents(AppointmentRepository $appointmentRepository, NotAvailableSlotsRepository $notAvailableSlotsRepository): Response
    {
        $appointments = $appointmentRepository->findAll();
        $notAvailableSlots = $notAvailableSlotsRepository->findAll();

        $results = [];
        //add rdvs
        foreach ($appointments as $appointment) {
            $start = $appointment->getDate()->format('Y-m-d') . ' ' . $appointment->getStart();
            $end = $appointment->getDate()->format('Y-m-d') . ' ' . $appointment->getEnd();

            $arrayTemp = [];
            $arrayTemp['id'] = $appointment->getId();
            $arrayTemp['title'] = $appointment->getClient()->getFirstName() . '.' . ucfirst(substr($appointment->getClient()->getLastname(), 0, 1));
            $arrayTemp['color'] = "#dc392d";
            $arrayTemp['start'] = $start;
            $arrayTemp['end'] = $end;
            $arrayTemp['allDays'] = false;
            $arrayTemp['description'] = $appointment->getClient()->getFirstName() . '.' . $appointment->getClient()->getLastname() . ' ' . $appointment->getClient()->getPhone();
            $results[] = $arrayTemp;
        }

        //add congés
        foreach ($notAvailableSlots as $notAvailableSlot) {
            $start = $notAvailableSlot->getStart()->format('Y-m-d H:i:s');
            $end = $notAvailableSlot->getEnd()->format('Y-m-d H:i:s');

            $arrayTemp = [];
            $arrayTemp['id'] = 'c_' . $notAvailableSlot->getId();
            $arrayTemp['title'] = 'Congé';
            $arrayTemp['color'] = "#212529";
            $arrayTemp['start'] = $start;
            $arrayTemp['end'] = $end;
            $arrayTemp['allDays'] = true;
            $arrayTemp['description'] = 'Congé';
            $results[] = $arrayTemp;
        }

        return $this->json($results);
    }


    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/events/add', name: 'admin_events_add')]
    public function addEvents(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent());

        if ($data->title !== null && $data->title === 'rdv') {
            //find client by number
            $client = $entityManager->getRepository(Client::class)->findOneBy(['phone' => $data->phone]);
            if (!$client instanceof Client) {
                $client = new Client();
                $client->setCreatedAt(new \DateTimeImmutable());
                $client->setFirstname($data->firstname);
                $client->setLastname($data->lastname);
                $numeroTelephone = preg_replace("/^\+33\s?/", "", $data->phone);

                if (!str_starts_with($numeroTelephone, '0')) {
                    $numeroTelephone = '0' . $numeroTelephone;
                }

                $client->setPhone(str_replace(" ", "", $numeroTelephone));
                $entityManager->persist($client);
            }
            //add appointment
            $appointment = new Appointment();
            $start = new \DateTime($data->start);

            $end = new \DateTime($data->end);
            $end->add(new DateInterval('PT15M'));

            $appointment->setDate($start);
            $appointment->setSlot($start->format('H:i') . '-' . $end->format('H:i'));
            $appointment->setClient($client);
            $appointment->setStart($start->format('H:i'));
            $appointment->setEnd($end->format('H:i'));
            $entityManager->persist($appointment);


            $entityManager->flush();

            return $this->json(['ok' => true]);
        } elseif ($data->title !== null && $data->title === 'off') {
            $off = new NotAvailableSlots();
            $off->setStart(new \DateTimeImmutable($data->start));
            $off->setEnd(new \DateTimeImmutable($data->end));
            $off->setCreatedBy($this->getUser());
            $entityManager->persist($off);
            $entityManager->flush();
            return $this->json(['ok' => true]);
        }

        return $this->json(['ok' => false]);
    }

    /**
     * @throws Exception
     */
    #[Route('/events/edit', name: 'admin_events_edit')]
    public function editEvent(Request $request, AppointmentRepository $appointmentRepository): JsonResponse
    {
        $data = json_decode($request->getContent());
        if ($data){
            //get event
            $event = $appointmentRepository->find($data->id);
            $start = new \DateTime($data->start);
            $end = new \DateTime($data->end);

            $event->setSlot($start->format('H:i') . '-' . $end->format('H:i'));
            $event->setStart($start->format('H:i'));
            $event->setEnd($end->format('H:i'));
            $appointmentRepository->save($event, true);
            return $this->json(['ok' => true]);
        }
        return $this->json(['ok' => false]);
    }

    #[Route('/events/delete', name: 'admin_events_delete')]
    public function dropEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        dd($data);
        return $this->json(['ok' => false]);
    }
}
