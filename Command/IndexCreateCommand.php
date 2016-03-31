<?php

namespace MobileCart\ElasticSearch17Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;


class IndexCreateCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cart:es17:create')
            ->setDescription('Create indexes for products, categories, customers within ElasticSearch')
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
        $search = $this->getContainer()->get('cart.es17.search');
        $host = $search->getClient()->getConfig('host');
        $port = $search->getClient()->getConfig('port');
        $message = "Connecting to host: {$host}:{$port}";
        $output->writeln($message);

        $message = $search->getClient()->createRootIndex();
        $message = print_r($message, 1);
        $output->writeln($message);

        $esEntity = $search->getEntityService();
        $objectTypes = $esEntity->mapAll();
        if ($objectTypes) {
            foreach($objectTypes as $objectType) {
                $message = "Created Mapping: {$objectType}";
                $output->writeln($message);
            }
        }
    }
}
