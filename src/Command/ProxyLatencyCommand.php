<?php

namespace Pretorien\RequestBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Pretorien\RequestBundle\Entity\ProxyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\CurlHttpClient;
use Pretorien\RequestBundle\Model\Proxy as Proxy;
use Pretorien\RequestBundle\Http\Proxy as HttpProxy;
use Pretorien\RequestBundle\Http\Request\PrivateRequest;
use Pretorien\RequestBundle\Http\Request\PublicRequest;
use Pretorien\RequestBundle\Http\Response\PoolResponse;
use Pretorien\RequestBundle\Http\Response\Response;
use Pretorien\RequestBundle\Service\RequestService;

class ProxyLatencyCommand extends Command
{
    protected static $defaultName = 'pretorien:proxy:check';
    private $proxyManager;
    private $requestService;

    public const POOL_SIZE = 5;
    public const TEST_URL = "https://api.nordvpn.com/vpn/check/full";

    protected function configure()
    {
        $this
            ->setDescription('Check proxy latency')
            ->setHelp('This command check the proxy latency');
    }

    public function __construct(RequestService $requestService, ProxyManager $proxyManager, string $name = null)
    {
        $this->proxyManager = $proxyManager;
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

        $chunks = array_chunk(
            $this->proxyManager->getRepository()->findAll(),
            self::POOL_SIZE
        );

        $publicIP = $this->getPublicIP();
        
        $progressBar = new ProgressBar($output, count($chunks));
        foreach ($chunks as $proxies) {
            $progressBar->advance();
            $pool = $this->requestService->createPoolRequest();

            foreach ($proxies as $proxy) {
                $request = (new PrivateRequest(self::TEST_URL))->setProxy($proxy);
                $pool->addRequest($request);
            }
    
            $responses = $this
                        ->requestService
                        ->sendPoolRequest($pool)
                        ->getContents(['throw' => false]);
    
            foreach ($responses[PoolResponse::RESPONSES_SUCCESSFUL] as $response) {
                $proxy = $this->proxyManager->findProxyByHost(
                    $response['request']->getProxy()['host']
                );
                try {
                    if (self::checkProxyStatus($response, $publicIP) == true) {
                        $time = (float) $response['response']->getInfo('total_time');
                        $proxy->setLastLatency((int) ($time * 1000));
                        $proxy->setEnable(true);
                    } else {
                        $this->proxyManager->incFailure($proxy);
                        $proxy->setEnable(false);
                    }
                } catch (\Throwable $th) {
                    $this->proxyManager->incFailure($proxy);
                    $proxy->setEnable(false);
                }
                $this->proxyManager->saveProxy($proxy);
            }
    
            foreach ($responses[PoolResponse::RESPONSES_FAILED] as $response) {
                $proxy = $this->proxyManager->findProxyByHost(
                    $response['request']->getProxy()['host']
                );
                $this->proxyManager->incFailure($proxy);
                $proxy->setEnable(false);
                $this->proxyManager->saveProxy($proxy);
            }
        }
        $progressBar->finish();

        $output->writeln("");

        $io->success("Traitement terminé");
        $this->printResult($output);

        return 0;
    }

    private static function checkProxyStatus(array $response, string $publicIP)
    {
        if ($response['response']->getStatusCode() == 200) {
            return true;
        }
        $json = \json_decode($response['content'], true);
        if ($json['ip'] == $publicIP) {
            return false;
        }
        return false;
    }

    private function getPublicIP()
    {
        $content = $this
                    ->requestService
                    ->publicRequest(self::TEST_URL)
                    ->getContent();
        $json = \json_decode($content, true);
        return $json['ip'];
    }

    private function printResult($output)
    {
        $command = $this->getApplication()->find(ProxyListCommand::getDefaultName());
        $input = new ArrayInput([]);
        $returnCode = $command->run($input, $output);
    }
}
