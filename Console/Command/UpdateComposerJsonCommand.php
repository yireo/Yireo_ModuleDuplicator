<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Console\Command;

use JsonException;
use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateComposerJsonCommand extends Command
{
    private const KEYWORD_PATTERN = '/^[a-z0-9-]+$/D';

    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly State $appState,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:module-duplicator:update-composer-json');
        $this->setDescription('Update composer.json with specific details');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
        $this->addOption('keywords', null, InputOption::VALUE_OPTIONAL, 'Composer keywords (comma-separated)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->appState->getMode() === State::MODE_PRODUCTION) {
            $output->writeln('<error>This command is not available in production mode</error>');
            return Command::FAILURE;
        }

        $moduleName = (string)$input->getArgument('module');
        $modulePath = (string)$this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        if ($modulePath === '') {
            $output->writeln('<error>Module "' . $moduleName . '" not found</error>');
            return Command::FAILURE;
        }

        $keywords = $this->parseKeywords((string)$input->getOption('keywords'));
        $invalidKeywords = preg_grep(self::KEYWORD_PATTERN, $keywords, PREG_GREP_INVERT);
        if (!empty($invalidKeywords)) {
            $output->writeln('<error>Invalid keywords: use lowercase letters, digits and dashes only</error>');
            return Command::FAILURE;
        }

        $composerFile = $modulePath . '/composer.json';
        if (!is_file($composerFile)) {
            $output->writeln('<error>composer.json not found</error>');
            return Command::FAILURE;
        }

        try {
            $composerData = json_decode((string)file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);
            if (empty($composerData) || !is_array($composerData)) {
                $output->writeln('<error>composer.json empty</error>');
                return Command::FAILURE;
            }

            if (!empty($keywords)) {
                $composerData['keywords'] = $keywords;
            }

            $contents = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $output->writeln('<error>Invalid JSON: ' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if (file_put_contents($composerFile, $contents) === false) {
            $output->writeln('<error>Failed to write composer.json</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function parseKeywords(string $value): array
    {
        $keywords = array_map(fn (string $keyword) => trim($keyword, " \f\n\r\t\v\x00"), explode(',', $value));

        return array_values(array_filter($keywords, fn (string $keyword) => $keyword !== ''));
    }
}
