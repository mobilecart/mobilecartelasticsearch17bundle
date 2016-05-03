<?php

namespace MobileCart\ElasticSearch17Bundle\Command;

use MobileCart\CoreBundle\Constants\EntityConstants;
use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use MobileCart\ElasticSearch17Bundle\Service\ElasticSearchClient;


class IndexFindItemVarsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cart:es17:finditemvars')
            ->setDescription('Search products, categories, contents, item_vars in ElasticSearch')
            ->addArgument('object_type', InputArgument::REQUIRED, 'Object Type eg product, category, content, item_var')
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

        $itemVars = $entityService->getObjectTypeItemVars($input->getArgument('object_type'));

        $message = print_r($itemVars, 1);
        $output->writeln($message);
    }
}
