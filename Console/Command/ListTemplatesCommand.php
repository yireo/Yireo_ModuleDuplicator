<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ModuleDuplicator\Service\TemplateModuleListing;

class ListTemplatesCommand extends Command
{
    public function __construct(
        private TemplateModuleListing $templateModuleListing,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:module-duplicator:list-templates');
        $this->setDescription('List all available templates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $templateModules = $this->templateModuleListing->getAll();
        foreach ($templateModules as $templateCode => $templateModuleName) {
            $output->writeln($templateCode. ' = '. $templateModuleName);
        }

        return Command::SUCCESS;
    }
}
