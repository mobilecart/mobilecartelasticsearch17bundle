<?php

namespace MobileCart\ElasticSearch17Bundle\Command;

use MobileCart\CoreBundle\Constants\EntityConstants;
use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;


class IndexFindCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cart:es17:find')
            ->setDescription('Search products, categories, contents, item_vars in ElasticSearch')
            ->addArgument('id', InputArgument::REQUIRED, 'ID')
            ->addArgument('object_type', InputArgument::OPTIONAL, 'Object Type eg product, category, content, item_var', EntityConstants::PRODUCT)
            ->setHelp(<<<EOF
The <info>%command.name%</info> command searches products within Elastica Search:

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
        $entityService = $this->getContainer()->get('cart.es17.entity');

        $id = $input->getArgument('id');

        $document = $entityService->find($input->getArgument('object_type'), $id);
        $message = print_r($document, 1);
        $output->writeln($message);
    }
}
