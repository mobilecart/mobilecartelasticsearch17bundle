<?php

namespace MobileCart\ElasticSearch17Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;


class IndexCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cart:es17:index')
            ->setDescription('Import products, categories, customers into ElasticSearch')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command manages importing products from MobileCart into Elastica Search:

<info>php %command.full_name%</info>

EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', -1);
        $esEntityService = $this->getContainer()->get('cart.es17.entity');

        $objectTypes = $esEntityService->indexAllObjectTypes();
        if ($objectTypes) {
            foreach($objectTypes as $objectType) {
                $message = "Populated Index: {$objectType}";
                $output->writeln($message);
            }
        }
    }
}
