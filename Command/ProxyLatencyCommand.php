<?php

namespace WTeam\RequestBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WTeam\RequestBundle\Entity\Proxy;
use WTeam\RequestBundle\Http\Proxy as HttpProxy;
use WTeam\RequestBundle\Service\RequestService;

class ProxyLatencyCommand extends Command
{
    protected static $defaultName = 'app:proxy:refresh';
    private $em;
    private $requestService;

    protected function configure()
    {
        $this
            ->setDescription('Check proxy latency')
            ->setHelp('This command check the proxy latency');
    }

    public function __construct(RequestService $requestService, EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        $this->requestService = $requestService;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln([
            'Proxy latency checker',
            '======================',
            '',
        ]);

        $client = RequestService::createClient(['timeout' => 2]);
        $response = $this->requestService->getMyIp($client);
        $publicIp = $response['ip'];

        $proxies = $this->em->getRepository(Proxy::class)->findAll();

        if(count($proxies) > 0){
            $progressBar = new ProgressBar($output, count($proxies));

            foreach ($proxies as $proxy) {

                $progressBar->advance();

                try {
                    $response = $this->requestService->getMyIp($client, HttpProxy::toPrototype($proxy));
                } catch (\Throwable $th) {
                    $proxy->setEnable(false);
                    $proxy->setFailure($proxy->getFailure() + 1);
                    $proxy->setLastFailure(new \DateTime());
                    continue;
                }
                $proxyIp = $response["ip"];
    
                if ($proxyIp != $publicIp) {
                    $proxy->setEnable(true);
                } else {
                    $proxy->setFailure($proxy->getFailure() + 1);
                    $proxy->setLastFailure(new \DateTime());
                    $proxy->setEnable(false);
                }
    
                $proxy->setLastLatency($response['total_time']);
                $this->em->persist($proxy);
            }
            $progressBar->finish();
    
            $this->em->flush();

            $io->writeln("");
            $io->success("Traitement terminé");
            $this->printResult($output);

            return 0;
        } else {
            $io->warning("Aucun proxy présent en base");
            return 1;
        }

    }

    private function printResult($output){
        $command = $this->getApplication()->find('proxy:list');
        $input = new ArrayInput([]);
        $returnCode = $command->run($input, $output);
    }
}
