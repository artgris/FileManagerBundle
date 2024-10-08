<?php

namespace Artgris\Bundle\FileManagerBundle\Controller;

use Artgris\Bundle\FileManagerBundle\Event\FileManagerEvents;
use Artgris\Bundle\FileManagerBundle\Helpers\File;
use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Artgris\Bundle\FileManagerBundle\Helpers\FileManagerUploadHandler;
use Artgris\Bundle\FileManagerBundle\Service\FilemanagerService;
use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;
use Artgris\Bundle\FileManagerBundle\Twig\OrderExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Exception;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class ManagerController extends AbstractController
{

    private FileManager $fileManager;

    /**
     * ManagerController constructor.
     */
    public function __construct(private FilemanagerService $filemanagerService, private EventDispatcherInterface $dispatcher, private TranslatorInterface $translator, private RouterInterface $router, private FormFactoryInterface $formFactory)
    {
    }

    #[Route('/', name: 'file_manager')]
    public function indexAction(Request $request, FileTypeService $fileTypeService, SessionInterface $session): JsonResponse|Response
    {
        $queryParameters = $request->query->all();
        $isJson = $request->get('json');
        if ($isJson) {
            unset($queryParameters['json']);
        }

        // Remember Last Path - 31.08.2024
        if (isset($this->getParameter('artgris_file_manager')['conf'][$queryParameters['conf']]['remember_last_path'])
            && $this->getParameter('artgris_file_manager')['conf'][$queryParameters['conf']]['remember_last_path']
        ) {
            $queryParameters = $this->rememberLastPath($queryParameters, $session);
        }

        $fileManager = $this->newFileManager($queryParameters);


        // Folder search
        $directoriesArbo = $this->retrieveSubDirectories($fileManager, $fileManager->getDirName(), \DIRECTORY_SEPARATOR, $fileManager->getBaseName());

        // File search
        $finderFiles = new Finder();
        $finderFiles->in($fileManager->getCurrentPath())->depth(0);
        $regex = $fileManager->getRegex();

        $orderBy = $fileManager->getQueryParameter('orderby');
        $orderDESC = OrderExtension::DESC === $fileManager->getQueryParameter('order');
        if (!$orderBy) {
            $finderFiles->sortByType();
        }

        switch ($orderBy) {
            case 'name':
                $finderFiles->sort(function (SplFileInfo $a, SplFileInfo $b) {
                    return strcmp(mb_strtolower($b->getFilename()), mb_strtolower($a->getFilename()));
                });
                break;
            case 'date':
                $finderFiles->sortByModifiedTime();
                break;
            case 'size':
                $finderFiles->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                    return $a->getSize() - $b->getSize();
                });
                break;
        }

        if ($fileManager->getTree()) {
            $finderFiles->files()->name($regex)->filter(function (SplFileInfo $file) {
                return $file->isReadable();
            });
        } else {
            $finderFiles->filter(function (SplFileInfo $file) use ($regex) {
                if ('file' === $file->getType()) {
                    if (preg_match($regex, $file->getFilename())) {
                        return $file->isReadable();
                    }

                    return false;
                }

                return $file->isReadable();
            });
        }

        $this->dispatch(FileManagerEvents::POST_FILE_FILTER_CONFIGURATION, ['finder' => $finderFiles]);

        $formDelete = $this->createDeleteForm()->createView();
        $fileArray = [];
        foreach ($finderFiles as $file) {
            $fileArray[] = new File($file, $this->translator, $fileTypeService, $fileManager);
        }

        if ('dimension' === $orderBy) {
            usort($fileArray, function (File $a, File $b) {
                $aDimension = $a->getDimension();
                $bDimension = $b->getDimension();
                if ($aDimension && !$bDimension) {
                    return 1;
                }

                if (!$aDimension && $bDimension) {
                    return -1;
                }

                if (!$aDimension && !$bDimension) {
                    return 0;
                }

                return ($aDimension[0] * $aDimension[1]) - ($bDimension[0] * $bDimension[1]);
            });
        }

        if ($orderDESC) {
            $fileArray = array_reverse($fileArray);
        }

        $parameters = [
            'fileManager' => $fileManager,
            'fileArray' => $fileArray,
            'formDelete' => $formDelete,
        ];
        if ($isJson) {
            $fileList = $this->renderView('@ArtgrisFileManager/views/_manager_view.html.twig', $parameters);

            return new JsonResponse(['data' => $fileList, 'badge' => $finderFiles->count(), 'treeData' => $directoriesArbo]);
        }
        $parameters['treeData'] = json_encode($directoriesArbo);

        $form = $this->formFactory->createNamedBuilder('rename', FormType::class)
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => false,
                'data' => $this->translator->trans('input.default'),
            ])
            ->add('defined_parent_path', HiddenType::class, [])
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
                'label' => $this->translator->trans('button.save'),
            ])
            ->getForm();

        /* @var Form $form */
        $form->handleRequest($request);
        /** @var Form $formRename */
        $formRename = $this->createRenameForm();
        $formRenameFolder = $this->createRenameFolderForm();


        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $fs = new Filesystem();

            if ($data['defined_parent_path']) {
                $directory = $directorytmp = $fileManager->getConfiguration()['dir'] . $data['defined_parent_path'] . \DIRECTORY_SEPARATOR . $data['name'];
            } else {
                $directory = $directorytmp = $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $data['name'];
            }

            $i = 1;

            while ($fs->exists($directorytmp)) {
                $directorytmp = "{$directory} ({$i})";
                ++$i;
            }
            $directory = $directorytmp;

            try {
                $fs->mkdir($directory);
                $this->addFlash('success', $this->translator->trans('folder.add.success'));
            } catch (IOExceptionInterface $e) {
                $this->addFlash('danger', $this->translator->trans('folder.add.danger', ['%message%' => $data['name']]));
            }

            return $this->redirectToRoute('file_manager', $fileManager->getQueryParameters());
        }
        $parameters['form'] = $form->createView();
        $parameters['formRename'] = $formRename->createView();
        $parameters['formRenameFolder'] = $formRenameFolder->createView();

        return $this->render('@ArtgrisFileManager/manager.html.twig', $parameters);
    }

    #[Route("/rename/{fileName}", name: 'file_manager_rename')]
    public function renameFileAction(Request $request, string $fileName): RedirectResponse
    {
        $queryParameters = $request->query->all();
        $formRename = $this->createRenameForm();
        /* @var Form $formRename */
        $formRename->handleRequest($request);
        if ($formRename->isSubmitted() && $formRename->isValid()) {
            $data = $formRename->getData();
            $extension = $data['extension'] ? '.' . $data['extension'] : '';
            $newfileName = $data['name'] . $extension;
            if ($newfileName !== $fileName && isset($data['name'])) {
                $fileManager = $this->newFileManager($queryParameters);
                $newfilePath = $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $newfileName;
                $oldfilePath = realpath($fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $fileName);
                if (0 !== mb_strpos($newfilePath, $fileManager->getCurrentPath())) {
                    $this->addFlash('danger', $this->translator->trans('file.renamed.unauthorized'));
                } else {
                    $fs = new Filesystem();
                    try {
                        $this->dispatch(FileManagerEvents::RENAME_FILE, ['oldFile' => $oldfilePath, 'newFile' => $newfilePath]);
                        $fs->rename($oldfilePath, $newfilePath);
                        $this->addFlash('success', $this->translator->trans('file.renamed.success'));
                        //File has been renamed successfully
                    } catch (IOException $exception) {
                        $this->addFlash('danger', $this->translator->trans('file.renamed.danger'));
                    } catch (Exception $exception) {
                        $this->addFlash('danger', $exception->getMessage());
                    }
                }
            } else {
                $this->addFlash('warning', $this->translator->trans('file.renamed.nochanged'));
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    #[Route("/renamefolder/", name: 'file_manager_rename_folder')]
    public function renameFolderAction(Request $request): RedirectResponse
    {
        $queryParameters = $request->query->all();
        $formRenameFolder = $this->createRenameFolderForm();
        /* @var Form $formRenameFolder */
        $formRenameFolder->handleRequest($request);
        if ($formRenameFolder->isSubmitted() && $formRenameFolder->isValid()) {
            $data = $formRenameFolder->getData();
            $fs = new Filesystem();
            $fileManager = $this->newFileManager($queryParameters);
            $dirname = $fileManager->getConfiguration()['dir'];

            if (!$data['oldpath'] || '\\' === $data['oldpath'] || !$fs->exists($dirname . $data['oldpath'])) {
                $this->addFlash('danger', 'folder.rename.unauthorized');
            } else {
                try {
                    $this->dispatch(FileManagerEvents::RENAME_FOLDER, ['oldFolderName' => $data['oldname'], 'newFolderName' => $data['name']]);
                    $newFolderPath = $dirname . substr($data['oldpath'], 0, strrpos($data['oldpath'], '\\')) . \DIRECTORY_SEPARATOR . $data['name'];
                    $fs->rename($dirname . $data['oldpath'], $newFolderPath);
                    $this->addFlash('success', $this->translator->trans('folder.rename.success'));
                } catch (IOException $exception) {
                    $this->addFlash('danger', $this->translator->trans('folder.rename.danger', ['%message%' => $data['name']]));
                } catch (Exception $exception) {
                    $this->addFlash('danger', $exception->getMessage());
                }
            }
        }

        if(!$fs->exists($fileManager->getConfiguration()['dir']. $fileManager->getRoute())){
            unset($queryParameters['route']);
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    #[Route("/upload/", name: 'file_manager_upload')]
    public function uploadFileAction(Request $request): JsonResponse|Response
    {
        $fileManager = $this->newFileManager($request->query->all());

        $options = [
            'upload_dir' => $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR,
            'upload_url' => implode('/', array_map('rawurlencode', explode('/', $fileManager->getImagePath()))),
            'accept_file_types' => $fileManager->getRegex(),
            'print_response' => false,
            'override' => false,
            'image_versions' => array(
                '' => array(
                    'auto_orient' => true
                ),
            ),
        ];
        if (isset($fileManager->getConfiguration()['upload'])) {
            $options = $fileManager->getConfiguration()['upload'] + $options;
        }

        $this->dispatch(FileManagerEvents::PRE_UPDATE, ['options' => &$options]);
        $uploadHandler = new FileManagerUploadHandler($options);
        $response = $uploadHandler->get_response();

        foreach ($response['files'] as $file) {
            if (isset($file->error)) {
                $file->error = $this->translator->trans($file->error);
            } else {
                if (!$fileManager->getImagePath()) {
                    $file->url = $this->generateUrl('file_manager_file', array_merge($fileManager->getQueryParameters(), ['fileName' => $file->url]));
                }
            }
        }
        $this->dispatch(FileManagerEvents::POST_UPDATE, ['response' => &$response]);

        return new JsonResponse($response);
    }

    #[Route("/file/{fileName}", name: 'file_manager_file')]
    public function binaryFileResponseAction(Request $request, string $fileName): BinaryFileResponse
    {
        $fileManager = $this->newFileManager($request->query->all());
        $configuredDirectory = $fileManager->getConfiguration()['dir'];

        $file = $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . urldecode($fileName);
        $realFilePath = realpath($file);
        if (false === $realFilePath) {
            throw new FileNotFoundException($file);
        }
        if (!str_starts_with($realFilePath, realpath($configuredDirectory))) {
            throw new BadRequestHttpException('Accessing outside configured directory is not allowed.');
        }
        $this->dispatch(FileManagerEvents::FILE_ACCESS, ['path' => $file]);

        return new BinaryFileResponse($file);
    }

    #[Route("/delete/", name: 'file_manager_delete')]
    public function deleteAction(Request $request): RedirectResponse
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        $queryParameters = $request->query->all();
        if ($form->isSubmitted() && $form->isValid()) {
            // remove file
            $fileManager = $this->newFileManager($queryParameters);
            $fs = new Filesystem();
            if (isset($queryParameters['delete'])) {
                $is_delete = false;
                foreach ($queryParameters['delete'] as $fileName) {
                    $filePath = realpath($fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $fileName);
                    if (0 !== mb_strpos($filePath, $fileManager->getCurrentPath())) {
                        $this->addFlash('danger', 'file.deleted.danger');
                    } else {
                        try {
                            $this->dispatch(FileManagerEvents::PRE_DELETE_FILE);
                            $fs->remove($filePath);
                            $is_delete = true;
                            $this->dispatch(FileManagerEvents::POST_DELETE_FILE);
                        } catch (IOException $exception) {
                            $this->addFlash('danger', 'file.deleted.unauthorized');
                        } catch (Exception $exception) {
                            $this->addFlash('danger', $exception->getMessage());
                        }
                    }
                }
                if ($is_delete) {
                    $this->addFlash('success', 'file.deleted.success');
                }
                unset($queryParameters['delete']);
            } else {
                try {
                    $this->dispatch(FileManagerEvents::PRE_DELETE_FOLDER);
                    if ( $form->getData()['defined_parent_path'] == '\\' ) {
                        throw new \Exception('folder.deleted.unauthorized');
                    } else {
                        if ($form->getData()['defined_parent_path']) { // Tree Contextmenu Folder delete action
                            if ($fs->exists($fileManager->getConfiguration()['dir'] . $form->getData()['defined_parent_path'])) {
                                $fs->remove($fileManager->getConfiguration()['dir'] . $form->getData()['defined_parent_path']);
                            } else {
                                throw new \Exception('folder.deleted.unauthorized');
                            }
                        }
                        $this->addFlash('success', 'folder.deleted.success');
                    }
                } catch (IOException $exception) {
                    $this->addFlash('danger', 'folder.deleted.unauthorized');
                } catch (Exception $exception) {
                    $this->addFlash('danger', $exception->getMessage());
                }

                $this->dispatch(FileManagerEvents::POST_DELETE_FOLDER);
                $queryParameters['route'] = \dirname($fileManager->getRoute());
                if ($queryParameters['route'] == '/' || !$fs->exists($fileManager->getConfiguration()['dir'] . $queryParameters['route'])) {
                    unset($queryParameters['route']);
                }

                return $this->redirectToRoute('file_manager', $queryParameters);
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    private function createDeleteForm(): FormInterface|Form
    {
        return $this->formFactory->createNamedBuilder('delete_f')
            ->add('defined_parent_path', HiddenType::class, [])
            ->add('DELETE', SubmitType::class, [
                'translation_domain' => 'messages',
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
                'label' => 'button.delete.action',
            ])
            ->getForm();
    }

    private function createRenameForm(): FormInterface|Form
    {
        return $this->formFactory->createNamedBuilder('rename_f')
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => false,
            ])->add('extension', HiddenType::class)
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
                'label' => 'button.rename.action',
            ])
            ->getForm();
    }

    private function createRenameFolderForm(): FormInterface|Form
    {
        return $this->formFactory->createNamedBuilder('rename_folder')
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => false,
            ])
            ->add('oldpath', HiddenType::class)
            ->add('oldname', HiddenType::class)
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
                'label' => 'button.rename.action',
            ])
            ->getForm();
    }

    private function retrieveSubDirectories(FileManager $fileManager, string $path, ?string $parent = \DIRECTORY_SEPARATOR, ?string $baseFolderName = null): ?array
    {
        $directories = new Finder();
        $directories->in($path)->ignoreUnreadableDirs()->directories()->depth(0)->sortByType()->filter(function (SplFileInfo $file) {
            return $file->isReadable();
        });

        $this->dispatch(FileManagerEvents::POST_DIRECTORY_FILTER_CONFIGURATION, ['finder' => $directories]);

        if ($baseFolderName) {
            $directories->name($baseFolderName);
        }
        $directoriesList = null;

        foreach ($directories as $directory) {
            /** @var SplFileInfo $directory */
            $directoryFileName = $directory->getFilename();
            $fileName = $baseFolderName ? '' : $parent . $directoryFileName;

            $queryParameters = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute = $queryParameters;
            unset($queryParametersRoute['route']);

            $fileSpan = '';
            if (true === $fileManager->getConfiguration()['show_file_count']) {
                $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
                $fileSpan = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';
            }

            if ($fileName === '' && isset($fileManager->getConfiguration()['root_name'])) {
                $directoryFileName = $fileManager->getConfiguration()['root_name'];

            }

                $queryParametersRoute['route'] ?? $queryParametersRoute['route'] = '\\';
//            $queryParametersRoute['route'] = '\\';

            $selected_directory = false;
            if ($fileName === '' && $fileManager->getRoute() === null) {
                $selected_directory = true;
            } else {
                if ($fileManager->getRoute() === $fileName) {
                    $selected_directory = true;
                } else {
                    $selected_directory = false;
                }
            }

            $directoriesList[] = [
                'text' => $directoryFileName . $fileSpan,
//                'icon' => 'far fa-folder-open',
                'children' => $this->retrieveSubDirectories($fileManager, $directory->getPathname(), $fileName . \DIRECTORY_SEPARATOR),
                'a_attr' => [
                    'href' => $fileName ? $this->generateUrl('file_manager', $queryParameters) : $this->generateUrl('file_manager', $queryParametersRoute),
                ],
                'state' => [
//                    'selected' => $fileManager->getRoute() === $fileName,
                    'selected' => $selected_directory,
                    'opened' => true,
                    'test' => $fileManager->getRoute(),
                ],
            ];
        }

        return $directoriesList;
    }

    /**
     * Tree Iterator.
     */
    private function retrieveFilesNumber(string $path, string $regex): int
    {
        $files = new Finder();
        $files->in($path)->files()->depth(0)->name($regex);
        $this->dispatch(FileManagerEvents::POST_FILE_FILTER_CONFIGURATION, ['finder' => $files]);

        return iterator_count($files);
    }

    private function newFileManager(array $queryParameters): FileManager
    {
        if (!isset($queryParameters['conf'])) {
            throw new \RuntimeException('Please define a conf parameter in your route');
        }
        $webDir = $this->getParameter('artgris_file_manager')['web_dir'];
        $this->fileManager = new FileManager($queryParameters, $this->filemanagerService->getBasePath($queryParameters), $this->router, $this->dispatcher, $webDir);

        return $this->fileManager;
    }

    protected function dispatch(string $eventName, array $arguments = [])
    {
        $arguments = array_replace([
            'filemanager' => $this->fileManager,
        ], $arguments);

        $subject = $arguments['filemanager'];
        $event = new GenericEvent($subject, $arguments);
        $this->dispatcher->dispatch($event, $eventName);
    }

    private function rememberLastPath(array $queryParameters, $session): array
    {
        // GET Last Path -> iEntegre
        if ($session->has('file_manager_last_path') && empty($queryParameters['route'])) {

            $fileSystem = new Filesystem();
            $last_path = $this->getParameter('artgris_file_manager')['conf'][$queryParameters['conf']]['dir'] . $session->get('file_manager_last_path');
            $exist = $fileSystem->exists($last_path);

            if (false === $exist) {
                unset($queryParameters['route']); // if directory not exit return home and clear session
                if ($session->has('file_manager_last_path')) {
                    $session->remove('file_manager_last_path');
                }
            } else {
                $queryParameters['route'] = $session->get('file_manager_last_path');
            }
        }

        // SET Last Path -> iEntegre
        if (isset($queryParameters['route'])) {
            if ($session->has('file_manager_last_path')) {
                $session->remove('file_manager_last_path');
            }
            $session->set('file_manager_last_path', $queryParameters['route']);
        }

        return $queryParameters;
    }
}
