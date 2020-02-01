<?php

namespace WTeam\RequestBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WTeam\RequestBundle\Entity\Proxy;

class ProxyListCommand extends Command
{
    protected static $defaultName = 'proxy:list';
    private $em;

    public const FAILURE_WARNING = 1;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Liste l\'ensemble des proxy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proxies = $this->em->getRepository(Proxy::class)->findBy([], ['enable' => 'DESC', 'lastLatency' => 'ASC']);

        $table = new Table($output);
        $table->setHeaders(['Hôte', 'Port', 'Latence', 'Activé', 'Nombre d\'erreurs', 'Dernière erreur', 'Dernière MAJ', 'Date de création']);
        if (count($proxies) > 0) {
            foreach ($proxies as $proxy) {
                $table->addRow([
                    $proxy->getHost(),
                    $proxy->getPort(),
                    $proxy->getLastLatency() ?  $proxy->getLastLatency() . ' ms' : '-',
                    $proxy->getEnable() ? "Oui" : "Non",
                    $proxy->getFailure() > self::FAILURE_WARNING ? "<error>" . $proxy->getFailure() . "</error>" : $proxy->getFailure(),
                    $proxy->getLastFailure() ? $proxy->getLastFailure()->format("d/m/Y H:i:s") : '-',
                    $proxy->getUpdatedAt() ? $proxy->getUpdatedAt()->format("d/m/Y H:i:s") : '-',
                    $proxy->getCreatedAt() ? $proxy->getCreatedAt()->format("d/m/Y H:i:s") : '-',
                ]);
            }
        } else {
            $table->addRow([new TableCell('Aucun proxy présent en base', ['colspan' => 8])]);
        }

        $table->render();
        return 0;
    }
}
