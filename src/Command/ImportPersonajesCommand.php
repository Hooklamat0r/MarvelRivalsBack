<?php

namespace App\Command;

use App\Entity\Personaje;
use App\Repository\PersonajeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import:personajes',
    description: 'Importa los personajes desde la API de Marvel Rivals',
)]
class ImportPersonajesCommand extends Command
{
    private const API_URL = 'https://marvelrivalsapi.com/api/v1/heroes';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PersonajeRepository $personajeRepository,
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importador de Personajes Marvel Rivals');

        try {
            $io->section('Obteniendo datos de la API...');
            $apiKey = $this->params->get('marvel_api_key');

            if (!$apiKey) {
                $io->error('MARVEL_API_KEY no está configurada en .env');
                return Command::FAILURE;
            }

            $response = $this->httpClient->request('GET', self::API_URL, [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $io->error(sprintf('Error de API: HTTP %d', $statusCode));
                return Command::FAILURE;
            }

            $personajes = $response->toArray();
            if (empty($personajes)) {
                $io->warning('La API no devolvió ningún personaje');
                return Command::SUCCESS;
            }

            $io->success(sprintf('->> Se obtuvieron %d personajes', count($personajes)));

            $io->section('Importando personajes...');
            $importados = 0;
            $actualizados = 0;

            foreach ($personajes as $datos) {
                $personaje = $this->personajeRepository->findById($datos['id']);

                if ($personaje === null) {
                    $personaje = new Personaje();
                    $personaje->setId($datos['id']);
                    $importados++;
                    $this->entityManager->persist($personaje);
                } else {
                    $personaje->setUpdatedAt();
                    $actualizados++;
                }

                $personaje->setName($datos['name'] ?? '');
                $personaje->setImageUrl($datos['imageUrl'] ?? null);
                $personaje->setRole($datos['role'] ?? null);
                $personaje->setDifficulty($datos['difficulty'] ?? null);
            }

            $this->entityManager->flush();

            $io->success(sprintf('->> Importados: %d | Actualizados: %d', $importados, $actualizados));
            $io->info(sprintf('Total en BD: %d personajes', $importados + $actualizados));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error durante la importación: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
