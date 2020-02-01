<?php

namespace WTeam\RequestBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WTeam\RequestBundle\Entity\Proxy;
use WTeam\RequestBundle\Service\RequestService;

class ProxyNordvpnCommand extends Command
{
    protected static $defaultName = 'proxy:nordvpn:fetch';
    private $requestService;
    private $configuration;
    private $em;

    public function __construct(RequestService $requestService, $configuration, EntityManagerInterface $em, string $name = null)
    {
        if (!isset($configuration['nordvpn'])) {
            throw new \Exception("Merci de configurer les informations nécessaires à NordVpn (api, username et password)", 1);
        }

        $this->requestService = $requestService;
        $this->configuration = $configuration;
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Récupère les proxy NordVpn');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note("Source : " . $this->configuration['nordvpn']['api']);

        $proxies = $this->requestService->request(['uri' => $this->configuration['nordvpn']['api'], 'behindProxy' => false, 'format' => RequestService::FORMAT_JSON]);
        $io->success(count($proxies) . " proxy récupérés");

        foreach ($proxies as $nordvpnProxy) {

            $proxy = $this->em->getRepository(Proxy::class)->findOneBy(['host' => $nordvpnProxy['hostname']]);
            if (is_null($proxy)) {
                $io->writeln("Ajout du proxy : " . $nordvpnProxy['hostname']);
                $proxy = new Proxy();
            } else {
                $io->writeln("Mise à jour du proxy : " . $nordvpnProxy['hostname']);
            }

            $proxy->setHost($nordvpnProxy['hostname']);
            $proxy->setPort(80);
            $proxy->setUsername($this->configuration['nordvpn']['username']);
            $proxy->setPassword($this->configuration['nordvpn']['password']);
            $proxy->setEnable(true);
            $this->em->persist($proxy);
        }

        $this->em->flush();

        $io->success("Traitement terminé");

        return 0;
    }
}
