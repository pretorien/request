<?php

namespace Pretorien\RequestBundle\Command;

use Pretorien\RequestBundle\Entity\ProxyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Pretorien\RequestBundle\Service\RequestService;
use Symfony\Component\Console\Input\ArrayInput;

class ProxyFetcherCommand extends Command
{
    protected static $defaultName = 'pretorien:nordvpn:fetch';
    private $_requestService;
    private $_configuration;
    private $_proxyManager;
    private $_fetchers;

    public function __construct(
        RequestService $requestService,
        array $configuration,
        ProxyManager $proxyManager,
        string $name = null
    ) {
        $this->_requestService = $requestService;
        $this->_configuration = $configuration;
        $this->_proxyManager = $proxyManager;
        $this->_fetchers = $this->getFetchers();
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Fetch proxies');
        $this->addOption(
            'fetcher',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Which provider do you want to use?',
            array_keys($this->_fetchers)
        );
        $this->addOption(
            'force-check',
            null,
            InputOption::VALUE_NONE,
            'Force latency check after fetching'
        );
        $this->addOption(
            'renew',
            null,
            InputOption::VALUE_NONE,
            'Delete old proxies before fetching'
        );
        $this->addOption(
            'drop-failed',
            null,
            InputOption::VALUE_OPTIONAL,
            'Drop proxies with more than failures ?',
            10
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Proxy fetcher');

        $fetchers = [];

        foreach ($input->getOption('fetcher') as $inputFetcher) {
            if (!isset($this->_fetchers[$inputFetcher])) {
                throw new \Exception("Unknow fetcher : " . $inputFetcher, 1);
            }
            $class = $this->_fetchers[$inputFetcher];
            $fetchers[$inputFetcher] = new $class(
                $this->_requestService,
                $this->_proxyManager,
                $this->_configuration
            );
        }

        $output->writeln('Activated fetchers : ');
        $io->listing(array_keys($fetchers));

        if ($input->getOption('renew') == true) {
            $this->_proxyManager->dropProxies();
        }

        if ((int) $input->getOption('drop-failed') > 0) {
            $this->_proxyManager->dropProxies((int) $input->getOption('drop-failed'));
        }

        $newProxies = 0;
        $updatedproxies = 0;
        foreach ($fetchers as $name => $fetcher) {
            $proxies = $fetcher->fetch();
            foreach ($proxies as $fetcherProxy) {
                $proxy = $this->_proxyManager->findProxyByHost($fetcherProxy->getHost());
                if (is_null($proxy)) {
                    $io->write("Create proxy " . $fetcherProxy->getHost());
                    $proxy = $fetcherProxy;
                    $proxy->setFailure(0);
                    $newProxies++;
                } else {
                    $io->write("Update proxy " . $fetcherProxy->getHost());
                    $updatedproxies++;
                }
                $proxy->setHost($fetcherProxy->getHost());
                $proxy->setPort($fetcherProxy->getPort());
                $proxy->setUsername($fetcherProxy->getUsername());
                $proxy->setPassword($fetcherProxy->getPassword());
                if ($fetcherProxy->getEnable()) {
                    $io->writeln(" <info>[ONLINE]</info>");
                    $proxy->setEnable(true);
                } else {
                    $io->writeln(" <error>[MAINTENANCE]</error>");
                    $proxy->setEnable(false);
                }
                $this->_proxyManager->saveProxy($proxy);
            }
        }

        $io->success("$newProxies new proxies and $updatedproxies updated");

        if ($input->getOption('force-check') == true) {
            $command = $this->getApplication()->find(ProxyLatencyCommand::getDefaultName());
            $input = new ArrayInput([]);
            $returnCode = $command->run($input, $output);
        }

        return 0;
    }

    private function getFetchers(): array
    {
        $ns = substr(__NAMESPACE__, 0, -7) . "Fetcher\\";
        $result = [];
        foreach (glob(__DIR__ . "/../Fetcher/*Fetcher.php") as $fetchers) {
            preg_match('/Fetcher\/(.*)\.php/', $fetchers, $output);
            if ($output[1] && $output[1] !== "AbstractFetcher") {
                $class = $ns . $output[1];
                $name = constant($class . "::NAME");
                $result[$name] = $class;
            }
        }
        return $result;
    }
}
