<?php

namespace MobileCart\ElasticSearch17Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MobileCart\CoreBundle\Constants\EntityConstants;
use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;


class IndexSearchCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cart:es17:search')
            ->setDescription('Search object type in ElasticSearch')
            ->addArgument('object_type', InputArgument::REQUIRED, 'Object Type eg product, content')
            ->addArgument('search', InputArgument::OPTIONAL, 'Search terms; use quotes', 'match_all')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command searches objects within ElasticSearch:

<info>php %command.full_name%</info>

The optional argument specifies which action to execute:

<info>php %command.full_name%</info> search "modern fashion"
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resultSet = $this->getContainer()->get('cart.es17.search')
            ->setObjectType($input->getArgument('object_type'))
            ->setQuery($input->getArgument('search'))
            ->search();

        $output->writeln(print_r($resultSet, 1));
    }
}
