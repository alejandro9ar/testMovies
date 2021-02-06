<?php

namespace App\Command;

use App\Entity\Character;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

class StarwarsCommand extends Command
{

    private $em;

    public function __construct(string $name = null, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct($name);
    }

    protected static $defaultName = 'starwars:import';

    protected function configure()
    {
        $this
            ->setDescription('Coommand to download 30 characters of starswars')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $httpClient = HttpClient::create();
        $response = $httpClient->request('GET', 'https://swapi.dev/api/films/');
        $films = $response->toArray();

        foreach ($films['results'] as $film) {
            $movieC = $this->em->getRepository(Movie::class)->getByName($film['title']);
            if ($movieC == null) {
                $movie = new Movie();
                $movie->setName($film['title']);
                $this->em->persist($movie);
                dump('Imported character: '. $film['title']);
            }
        }
        $this->em->flush();

        $token = 0;
        for ($i = 1; $token <= 31; $i++) {
            $response = $httpClient->request('GET', 'https://swapi.dev/api/people/'.$i.'/');
            if (200 !== $response->getStatusCode()) {
                dump('error');
            } else {
                $characterResponse = $response->toArray();
            }

            $character = $this->em->getRepository(Character::class)->getByName($characterResponse['name']);
            if ($character == null) {
                $character = new Character();
                $character->setName($characterResponse['name']);
                $character->setGender($characterResponse['gender']);
                $character->setHeight($characterResponse['height']);
                $character->setMass($characterResponse['mass']);
                $this->em->persist($character);
                $this->em->flush();
                dump('Imported character: '. $characterResponse['name']);
                $token++;
            }

            foreach($characterResponse['films'] as $characterFilm){
                $response = $httpClient->request('GET', $characterFilm);
                $characterFilmDecoded = $response->toArray();
                $movie = $this->em->getRepository(Movie::class)->getByName($characterFilmDecoded['title']);
                $character->addMovie($movie);
            }
        }

        $io->success('You have donload was succes!');

        return Command::SUCCESS;
    }
}
