<?php

namespace Pretorien\RequestBundle\Command;

use Pretorien\RequestBundle\Entity\ProxyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProxyListCommand extends Command
{
    protected static $defaultName = 'pretorien:proxy:list';
    private $proxyManager;

    public const FAILURE_WARNING = 1;

    public function __construct(ProxyManager $proxyManager, string $name = null)
    {
        $this->proxyManager = $proxyManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('List proxies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proxies = $this->proxyManager->getRepository()->findBy([], ['enable' => 'DESC', 'lastLatency' => 'ASC']);

        $table = new Table($output);
        $table->setHeaders(['Host', 'Port', 'Latency', 'Activated', 'Number of errors', 'Last error', 'Updated at', 'Created at']);
        if (count($proxies) > 0) {
            foreach ($proxies as $proxy) {
                $table->addRow([
                    $proxy->getHost(),
                    $proxy->getPort(),
                    $proxy->getLastLatency() ?  $proxy->getLastLatency() . ' ms' : '-',
                    $proxy->getEnable() ? "Yes" : "No",
                    $proxy->getFailure() > self::FAILURE_WARNING ? "<error>" . $proxy->getFailure() . "</error>" : $proxy->getFailure(),
                    $proxy->getLastFailure() ? $proxy->getLastFailure()->format("d/m/Y H:i:s") : '-',
                    '',
                    ''
                    // $proxy->getUpdatedAt() ? $proxy->getUpdatedAt()->format("d/m/Y H:i:s") : '-',
                    // $proxy->getCreatedAt() ? $proxy->getCreatedAt()->format("d/m/Y H:i:s") : '-',
                ]);
            }
        } else {
            $table->addRow([new TableCell('No proxy in database', ['colspan' => 8])]);
        }

        $table->render();
        return 0;
    }
}
