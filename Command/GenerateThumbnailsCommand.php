<?php

namespace Artgris\Bundle\FileManagerBundle\Command;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManagerUploadHandler;
use Artgris\Bundle\FileManagerBundle\Service\FilemanagerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'artgris:filemanager:thumbnails', description: 'Creates the missing image versions (upload.image_versions) of existing images')]
class GenerateThumbnailsCommand extends Command {

    public function __construct(private FilemanagerService $filemanagerService) {
        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->addArgument('conf', InputArgument::REQUIRED, 'Name of the configuration (artgris_file_manager.conf.<name>)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Recreate the versions that already exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $conf = $this->filemanagerService->getBasePath(['conf' => $input->getArgument('conf')]);
        $upload = $conf['upload'] ?? [];
        // The empty key holds the options of the original, it is not a version
        $versions = array_values(array_diff(array_keys($upload['image_versions'] ?? []), ['']));
        if (!$versions) {
            $output->writeln('<error>No image version is defined in upload.image_versions.</error>');

            return Command::FAILURE;
        }

        // Same extensions as UploadHandler::has_image_file_extension()
        $images = (new Finder())->files()->in($conf['dir'])->name('/\.(gif|jpe?g|png)$/i')
            ->filter(function (SplFileInfo $file) use ($versions) {
                return !\in_array(basename($file->getRelativePath()), $versions, true);
            });

        $created = 0;
        $failed = 0;
        foreach ($images as $image) {
            $directory = $image->getPath().\DIRECTORY_SEPARATOR;
            // One handler per image: the GD backend keeps every opened image in memory as long as the handler lives
            $handler = new FileManagerUploadHandler(['upload_dir' => $directory] + $upload, false);
            foreach ($versions as $version) {
                if (!$input->getOption('force') && is_file($directory.$version.\DIRECTORY_SEPARATOR.$image->getFilename())) {
                    continue;
                }
                if ($handler->createVersion($image->getFilename(), $version)) {
                    ++$created;
                } else {
                    ++$failed;
                    $output->writeln(sprintf('<error>Cannot create the "%s" version of %s</error>', $version, $image->getPathname()));
                }
            }
        }

        $output->writeln(sprintf('%d image version(s) created, %d failed.', $created, $failed));

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
