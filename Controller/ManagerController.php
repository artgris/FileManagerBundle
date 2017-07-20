<?php

namespace Artgris\Bundle\FileManagerBundle\Controller;

use Artgris\Bundle\FileManagerBundle\Helpers\File;
use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Artgris\Bundle\FileManagerBundle\Helpers\UploadHandler;
use Artgris\Bundle\FileManagerBundle\Twig\OrderExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class ManagerController extends Controller
{
    /**
     * @Route("/", name="file_manager")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $queryParameters = $request->query->all();
        $translator = $this->get('translator');
        $isJson = $request->get('json') ? true : false;
        if ($isJson) {
            unset($queryParameters['json']);
        }
        $fileManager = $this->newFileManager($queryParameters);

        // Folder search
        $directoriesArbo = $this->retrieveSubDirectories($fileManager, $fileManager->getDirName(), DIRECTORY_SEPARATOR, $fileManager->getBaseName());

        // File search
        $finderFiles = new Finder();
        $finderFiles->in($fileManager->getCurrentPath())->depth(0);
        $regex = $fileManager->getRegex();

        $orderBy = $fileManager->getQueryParameter('orderby');
        $orderDESC = $fileManager->getQueryParameter('order') === OrderExtension::DESC;
        if (!$orderBy) {
            $finderFiles->sortByType();
        }

        switch ($orderBy) {
            case 'name':
                $finderFiles->sort(function (SplFileInfo $a, SplFileInfo $b) {
                    return strcmp(strtolower($b->getFilename()), strtolower($a->getFilename()));
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
                if ($file->getType() === 'file') {
                    if (preg_match($regex, $file->getFilename())) {
                        return $file->isReadable();
                    }

                    return false;
                }

                return $file->isReadable();
            });
        }

        $formDelete = $this->createDeleteForm()->createView();
        $fileArray = [];
        foreach ($finderFiles as $file) {
            $fileArray[] = new File($file, $this->get('translator'), $this->get('file_type_service'), $fileManager);
        }

        if ($orderBy === 'dimension') {
            usort($fileArray, function (File $a, File $b) {
                $aDimension = $a->getDimension();
                $bDimension = $b->getDimension();
                if ($aDimension && !$bDimension) {
                    return 1;
                } elseif (!$aDimension && $bDimension) {
                    return -1;
                } elseif (!$aDimension && !$bDimension) {
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
            $fileList = $this->renderView('ArtgrisFileManagerBundle:views:_list.html.twig', $parameters);

            return new JsonResponse(['data' => $fileList, 'badge' => $finderFiles->count(), 'treeData' => $directoriesArbo]);
        }
        $parameters['treeData'] = json_encode($directoriesArbo);

        $form = $this->get('form.factory')->createNamedBuilder('rename', FormType::class)
                ->add('name', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => false,
                    'data' => $translator->trans('input.default'),
                ])
                ->add('send', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                    'label' => $translator->trans('button.rename'),
                ])
                ->getForm();

            /* @var Form $form */
            $form->handleRequest($request);
            /** @var Form $formRename */
            $formRename = $this->createRenameForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fs = new Filesystem();
            $directory = $directorytmp = $fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$data['name'];
            $i = 1;

            while ($fs->exists($directorytmp)) {
                $directorytmp = "{$directory} ({$i})";
                ++$i;
            }
            $directory = $directorytmp;

            try {
                $fs->mkdir($directory);
                $this->addFlash('success', $translator->trans('folder.add.success'));
            } catch (IOExceptionInterface $e) {
                $this->addFlash('danger', $translator->trans('folder.add.danger', ['%message%' => '$e->getPath()']));
            }

            return $this->redirectToRoute('file_manager', $fileManager->getQueryParameters());
        }
        $parameters['form'] = $form->createView();
        $parameters['formRename'] = $formRename->createView();

        return $this->render('ArtgrisFileManagerBundle::manager.html.twig', $parameters);
    }

    /**
     * @Route("/rename/{fileName}", name="file_manager_rename")
     *
     * @param Request $request
     * @param $fileName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function renameFileAction(Request $request, $fileName)
    {
        $translator = $this->get('translator');
        $queryParameters = $request->query->all();
        $formRename = $this->createRenameForm();
        /* @var Form $formRename */
        $formRename->handleRequest($request);
        if ($formRename->isSubmitted() && $formRename->isValid()) {
            $data = $formRename->getData();
            $extension = $data['extension'] ? '.'.$data['extension'] : '';
            $NewfileName = $data['name'].$extension;
            if (isset($data['name']) && $NewfileName !== $fileName) {
                $fileManager = $this->newFileManager($queryParameters);
                $NewfilePath = $fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$NewfileName;
                $OldfilePath = realpath($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$fileName);
                if (strpos($NewfilePath, $fileManager->getCurrentPath()) !== 0) {
                    $this->addFlash('danger', $translator->trans('file.renamed.unauthorized'));
                } else {
                    $fs = new Filesystem();
                    try {
                        $fs->rename($OldfilePath, $NewfilePath);
                        $this->addFlash('success', $translator->trans('file.renamed.success'));
                        //File has been renamed successfully
                    } catch (IOException $exception) {
                        $this->addFlash('danger', $translator->trans('file.renamed.danger'));
                    }
                }
            } else {
                $this->addFlash('warning', $translator->trans('file.renamed.nochanged'));
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    /**
     * @Route("/upload/", name="file_manager_upload")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function uploadFileAction(Request $request)
    {
        $fileManager = $this->newFileManager($request->query->all());

        $options = [
            'upload_dir' => $fileManager->getCurrentPath().DIRECTORY_SEPARATOR,
            'upload_url' => $fileManager->getImagePath(),
            'accept_file_types' => $fileManager->getRegex(),
        ];
        if (isset($fileManager->getConfiguration()['upload'])) {
            $options = $options + $fileManager->getConfiguration()['upload'];
        }
        new UploadHandler($options);

        return new Response();
    }

    /**
     * @Route("/file/{fileName}", name="file_manager_file")
     *
     * @param Request $request
     * @param $fileName
     *
     * @return BinaryFileResponse
     */
    public function binaryFileResponseAction(Request $request, $fileName)
    {
        $fileManager = $this->newFileManager($request->query->all());

        return new BinaryFileResponse($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.urldecode($fileName));
    }

    /**
     * @Route("/delete/", name="file_manager_delete")
     *
     * @param Request $request
     * @Method("DELETE")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $translator = $this->get('translator');
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        $queryParameters = $request->query->all();

        if ($form->isSubmitted() && $form->isValid()) {
            // remove file
            $fileManager = new FileManager($queryParameters, $this->getBasePath($queryParameters), $this->getKernelRoute(), $this->get('router'));
            $fs = new Filesystem();

            if (isset($queryParameters['delete'])) {
                $is_delete = false;
                foreach ($queryParameters['delete'] as $fileName) {
                    $filePath = realpath($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$fileName);
                    if (strpos($filePath, $fileManager->getCurrentPath()) !== 0) {
                        $this->addFlash('danger', $translator->trans('file.deleted.danger'));
                    } else {
                        try {
                            $fs->remove($filePath);
                            $is_delete = true;
                        } catch (IOException $exception) {
                            $this->addFlash('danger', $translator->trans('file.deleted.unauthorized'));
                        }
                    }
                }
                if ($is_delete) {
                    $this->addFlash('success', $translator->trans('file.deleted.success'));
                }
                unset($queryParameters['delete']);
            } else {
                try {
                    $fs->remove($fileManager->getCurrentPath());
                    $this->addFlash('success', $translator->trans('folder.deleted.success'));
                } catch (IOException $exception) {
                    $this->addFlash('danger', $translator->trans('folder.deleted.unauthorized'));
                }
                $queryParameters['route'] = dirname($fileManager->getCurrentRoute());

                return $this->redirectToRoute('file_manager', $queryParameters);
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    /**
     * @return Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm()
    {
        return $this->createFormBuilder()
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * @return mixed
     */
    private function createRenameForm()
    {
        $translator = $this->get('translator');

        return $this->createFormBuilder()
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
                'label' => $translator->trans('title.rename.file'),
            ])
            ->getForm();
    }

    /**
     * @param FileManager $fileManager
     * @param $path
     * @param string $parent
     * @param bool   $baseFolderName
     *
     * @return array|null
     */
    private function retrieveSubDirectories(FileManager $fileManager, $path, $parent = DIRECTORY_SEPARATOR, $baseFolderName = false)
    {
        $directories = new Finder();
        $directories->in($path)->ignoreUnreadableDirs()->directories()->depth(0)->sortByType()->filter(function (SplFileInfo $file) {
            return $file->isReadable();
        });

        if ($baseFolderName) {
            $directories->name($baseFolderName);
        }
        $directoriesList = null;

        foreach ($directories as $directory) {
            /** @var SplFileInfo $directory */
            $fileName = $baseFolderName ? '' : $parent.$directory->getFilename();

            $queryParameters = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute = $queryParameters;
            unset($queryParametersRoute['route']);

            $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            $fileSpan = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';

            $directoriesList[] = [
                'text' => $directory->getFilename().$fileSpan,
                'icon' => 'fa fa-folder-o',
                'children' => $this->retrieveSubDirectories($fileManager, $directory->getPathname(), $fileName.DIRECTORY_SEPARATOR),
                'a_attr' => [
                    'href' => $fileName ? $this->generateUrl('file_manager', $queryParameters) : $this->generateUrl('file_manager', $queryParametersRoute),
                ], 'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
//				    'expanded' => $fileName ? substr($fileManager->getCurrentRoute(), 0, strlen($fileName)) === $fileName : true,
                    'opened' => true,
                ],
//                'tags' => [$this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex())]
            ];
        }

        return $directoriesList;
    }

    /**
     * Tree Iterator.
     *
     * @param $path
     * @param $regex
     *
     * @return int
     */
    private function retrieveFilesNumber($path, $regex)
    {
        $files = new Finder();
        $files->in($path)->files()->depth(0)->name($regex);

        return iterator_count($files);
    }

    /*
     * Base Path
     */
    private function getBasePath($queryParameters)
    {
        $conf = $queryParameters['conf'];
        $managerConf = $this->getParameter('artgris_file_manager')['conf'];
        if (isset($managerConf[$conf]['dir'])) {
            return $managerConf[$conf];
        } elseif (isset($managerConf[$conf]['service'])) {
            $extra = isset($queryParameters['extra']) ? $queryParameters['extra'] : [];
            $conf = $this->get($managerConf[$conf]['service'])->getConf($extra);

            return $conf;
        }
    }

    /**
     * @return mixed
     */
    private function getKernelRoute()
    {
        return $this->getParameter('kernel.root_dir');
    }

    private function newFileManager($queryParameters)
    {
        if (!isset($queryParameters['conf'])) {
            throw new \Exception('Please define a conf parameter in your route');
        }

        return new FileManager($queryParameters, $this->getBasePath($queryParameters), $this->getKernelRoute(), $this->get('router'));
    }
}
