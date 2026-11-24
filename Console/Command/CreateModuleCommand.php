<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Console\Command;

use InvalidArgumentException;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ModuleDuplicator\Service\ModuleCreator;

class CreateModuleCommand extends Command
{
    public function __construct(
        private readonly ModuleCreator $moduleCreator,
        private readonly State $appState,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:module-duplicator:create');
        $this->setDescription('Create a new module from template');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
        $this->addArgument('template', InputArgument::REQUIRED, 'Template module name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->appState->getMode() === State::MODE_PRODUCTION) {
            $output->writeln('<error>This command is not available in production mode</error>');
            return Command::FAILURE;
        }

        $moduleName = (string)$input->getArgument('module');
        $templateModuleName = (string)$input->getArgument('template');

        try {
            $this->moduleCreator->create($moduleName, $templateModuleName);
        } catch (InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
