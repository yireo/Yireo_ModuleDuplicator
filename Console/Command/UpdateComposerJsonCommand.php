<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ModuleDuplicator\Service\ModuleCreator;

class UpdateComposerJsonCommand extends Command
{
    public function __construct(
        private ComponentRegistrar $componentRegistrar,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:module-duplicator:update-composer-json');
        $this->setDescription('Update composer.json with specific details');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
        $this->addOption('keywords', null, InputOption::VALUE_OPTIONAL, 'Composer keywords');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = (string)$input->getArgument('module');
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $composerFile = $modulePath.'/composer.json';
        if (!file_exists($composerFile)) {
            $output->writeln('<error>composer.json not found</error>');
            return Command::FAILURE;
        }

        $composerData = json_decode(file_get_contents($composerFile), true);

        if (empty($composerData)) {
            $output->writeln('<error>composer.json empty</error>');
            return Command::FAILURE;
        }

        $keywords = explode(',', trim((string)$input->getOption('keywords')), -1);
        if (!empty($keywords)) {
            $composerData['keywords'] =  $keywords;
        }

        file_put_contents($composerFile, json_encode($composerData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
