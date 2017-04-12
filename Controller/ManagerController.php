<?php

namespace Artgris\Bundle\FileManagerBundle\Controller;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Artgris\Bundle\FileManagerBundle\Helpers\UploadHandler;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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


    private function newFileManager($queryParameters)
    {
        if (!isset($queryParameters['conf'])) {
            throw new \Exception('Please defined a conf parameter in your route');
        }
        return new FileManager($queryParameters, $this->getBasePath($queryParameters), $this->getKernelRoute(), $this->get('router'));
    }

    /**
     * @Route("/", name="file_manager")
     * @param Request $request
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
        $finderFiles->in($fileManager->getCurrentPath())->depth(0)->files()->sortByType()->name($fileManager->getRegex())->filter(function (SplFileInfo $file) {
            return $file->isReadable();
        });

        $formDelete = $this->createDeleteForm()->createView();
        $imageSize = [];
        foreach ($finderFiles as $file) {
            if (preg_match('/(gif|png|jpe?g|svg)$/i', $file->getExtension())) {
                $imageSize[$file->getFilename()] = getimagesize($file->getPathname());
            }
        }

        $parameters = [
            'fileManager' => $fileManager,
            'finderFiles' => $finderFiles,
            'formDelete' => $formDelete,
            'imageSize' => $imageSize
        ];

        if ($isJson) {
            $fileList = $this->renderView('ArtgrisFileManagerBundle::_list.html.twig', $parameters);
            return new JsonResponse(['data' => $fileList, 'badge' => $finderFiles->count(), 'treeData' => $directoriesArbo]);
        } else {
            $parameters['treeData'] = json_encode($directoriesArbo);

            $form = $this->get('form.factory')->createNamedBuilder('rename', FormType::class)
                ->add('name', TextType::class, [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => false,
                    'data' => $translator->trans('input.default')
                ])
                ->add('send', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ],
                    'label' => $translator->trans('button.rename')
                ])
                ->getForm();

            /** @var Form $form */
            $form->handleRequest($request);
            /** @var Form $formRename */
            $formRename = $this->createRenameForm();

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $fs = new Filesystem();
                $directory = $directorytmp = $fileManager->getCurrentPath() . DIRECTORY_SEPARATOR . $data['name'];
                $i = 1;

                while ($fs->exists($directorytmp)) {
                    $directorytmp = "{$directory} ({$i})";
                    $i++;
                }
                $directory = $directorytmp;

                try {
                    $fs->mkdir($directory);
                    $this->addFlash("success", $translator->trans('folder.add.success'));
                } catch (IOExceptionInterface $e) {
                    $this->addFlash("danger", $translator->trans('folder.add.danger', ['%message%' => '$e->getPath()']));
                }
                return $this->redirectToRoute("file_manager", $fileManager->getQueryParameters());
            }
            $parameters['form'] = $form->createView();
            $parameters['formRename'] = $formRename->createView();
            return $this->render('ArtgrisFileManagerBundle::manager.html.twig', $parameters);
        }
    }


    /**
     * @Route("/rename/{fileName}", name="file_manager_rename")
     * @param Request $request
     * @param $fileName
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function renameFile(Request $request, $fileName)
    {
        $translator = $this->get('translator');
        $queryParameters = $request->query->all();
        $formRename = $this->createRenameForm($queryParameters);
        /** @var Form $formRename */
        $formRename->handleRequest($request);

        if ($formRename->isSubmitted() && $formRename->isValid()) {
            $data = $formRename->getData();
            $NewfileName = $data['name'] . '.' . $data['extension'];
            if (isset($data['name']) && $NewfileName !== $fileName) {
                $fileManager = $this->newFileManager($queryParameters);
                $NewfilePath = $fileManager->getCurrentPath() . DIRECTORY_SEPARATOR . $NewfileName;
                $OldfilePath = realpath($fileManager->getCurrentPath() . DIRECTORY_SEPARATOR . $fileName);
                if (strpos($NewfilePath, $fileManager->getCurrentPath()) !== 0) {
                    $this->addFlash("danger", $translator->trans('file.renamed.unauthorized'));
                } else {
                    $fs = new Filesystem();
                    try {
                        $fs->rename($OldfilePath, $NewfilePath);
                        $this->addFlash("success", $translator->trans('file.renamed.success'));
                        //File has been renamed successfully
                    } catch (IOException $exception) {
                        $this->addFlash("danger", $translator->trans('file.renamed.danger'));
                    }
                }
            } else {
                $this->addFlash("warning", $translator->trans('file.renamed.nochanged'));
            }
        }
        return $this->redirectToRoute("file_manager", $queryParameters);
    }

    /**
     * @Route("/upload/", name="file_manager_upload")
     * @param Request $request
     * @return Response
     */
    public function uploadFile(Request $request)
    {
        $fileManager = $this->newFileManager($request->query->all());


        $options = [
            'upload_dir' => $fileManager->getCurrentPath() . DIRECTORY_SEPARATOR,
            'upload_url' => $fileManager->getImagePath(),
            "accept_file_types" => $fileManager->getRegex()
        ];
        if (isset($fileManager->getConfiguration()['upload'])) {
            $options = $options + $fileManager->getConfiguration()['upload'];
        }
        new UploadHandler($options);

        return new Response();
    }

    /**
     * @Route("/file/{fileName}", name="file_manager_file")
     * @param Request $request
     * @param $fileName
     * @return BinaryFileResponse
     */
    public function binaryFileResponseAction(Request $request, $fileName)
    {
        $fileManager = $this->newFileManager($request->query->all());
        return new BinaryFileResponse($fileManager->getCurrentPath() . DIRECTORY_SEPARATOR . urldecode($fileName));
    }

    /**
     * @Route("/delete/", name="file_manager_delete")
     * @param Request $request
     * @Method("DELETE")
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
                    $filePath = realpath($fileManager->getCurrentPath() . DIRECTORY_SEPARATOR . $fileName);
                    if (strpos($filePath, $fileManager->getCurrentPath()) !== 0) {
                        $this->addFlash("danger", $translator->trans('file.deleted.danger'));
                    } else {
                        try {
                            $fs->remove($filePath);
                            $is_delete = true;
                        } catch (IOException $exception) {
                            $this->addFlash("danger", $translator->trans('file.deleted.unauthorized'));
                        }
                    }
                }
                if ($is_delete) {
                    $this->addFlash("success", $translator->trans('file.deleted.success'));
                }
                unset ($queryParameters['delete']);
            } else {
                try {
                    $fs->remove($fileManager->getCurrentPath());
                    $this->addFlash("success", $translator->trans('folder.deleted.success'));
                } catch (IOException $exception) {
                    $this->addFlash("danger", $translator->trans('folder.deleted.unauthorized'));
                }
                $queryParameters['route'] = dirname($fileManager->getCurrentRoute());
                return $this->redirectToRoute("file_manager", $queryParameters);
            }
        }
        return $this->redirectToRoute("file_manager", $queryParameters);

    }


    /**
     * @Route("/imagetojson/", name="file_manager_imagetojson")
     * @author https://github.com/betamax/getImageData
     */
    public function imageToJsonAction()
    {

        try {

            // Check if the URL is set
            if (isset($_GET["url"])) {

                // Get the URL and decode to remove any %20, etc
                $url = urldecode($_GET["url"]);

                // Get the contents of the URL
                $file = file_get_contents($url);

                // Check if it is an image
                if (@imagecreatefromstring($file)) {

                    // Get the image information
                    $size = getimagesize($url);
                    // Image type
                    $type = $size["mime"];
                    // Dimensions
                    $width = $size[0];
                    $height = $size[1];

                    // Setup the data URL
                    $type_prefix = "data:" . $type . ";base64,";
                    // Encode the image into base64
                    $base64file = base64_encode($file);
                    // Combine the prefix and the image
                    $data_url = $type_prefix . $base64file;

                    // Setup the return data
                    $return_arr = [
                        'width' => $width,
                        'height' => $height,
                        'data' => $data_url
                    ];

                    // Encode it into JSON
                    $return_val = json_encode($return_arr);

                    // If a callback has been specified
                    if (isset($_GET["callback"])) {

                        // Wrap the callback around the JSON
                        $return_val = $_GET["callback"] . '(' . $return_val . ');';

                        // Set the headers to JSON and so they wont cache or expire
                        header('Cache-Control: no-cache, must-revalidate');
                        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                        header('Content-type: application/json');

                        // Print the JSON
                        print $return_val;

                        // No callback was set
                    } else {
                        header('HTTP/1.0 400 Bad Request');
                        print "No callback specified";
                    }

                    // The requested file is not an image
                } else {
                    header('HTTP/1.0 400 Bad Request');
                    print "Invalid image specified";
                }

                // No URL set so error
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo "No URL was specified";
            }
        } catch (Exception $e) {
            header('HTTP/1.0 500 Internal Server Error');
            echo "Internal Server Error";
        }

        exit(0);


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
                    new NotBlank()
                ],
                'label' => false,
            ])->add('extension', HiddenType::class, [
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary'
                ],
                'label' => $translator->trans('title.rename.file')
            ])
            ->getForm();

    }

    /**
     * @param FileManager $fileManager
     * @param $path
     * @param string $parent
     * @param bool $baseFolderName
     * @return array|null
     */
    private function retrieveSubDirectories(FileManager $fileManager, $path, $parent = DIRECTORY_SEPARATOR, $baseFolderName = false)
    {
        $directories = new Finder();
        $directories->ignoreUnreadableDirs()->in($path)->directories()->depth(0)->sortByType()->filter(function (SplFileInfo $file) {
            return $file->isReadable();
        });

        if ($baseFolderName) {
            $directories->name($baseFolderName);
        }
        $directoriesList = null;

        foreach ($directories as $directory) {

            /** @var SplFileInfo $directory */
            $fileName = $baseFolderName ? "" : $parent . $directory->getFilename();

            $queryParameters = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute = $queryParameters;
            unset($queryParametersRoute['route']);

            $directoriesList[] = [
                'text' => $directory->getFilename(),
                'icon' => "fa fa-folder",
                'nodes' => $this->retrieveSubDirectories($fileManager, $directory->getPathname(), $fileName . DIRECTORY_SEPARATOR),
                'href' => $fileName ? $this->generateUrl('file_manager', $queryParameters) : $this->generateUrl('file_manager', $queryParametersRoute),
                'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
//				    'expanded' => $fileName ? substr($fileManager->getCurrentRoute(), 0, strlen($fileName)) === $fileName : true,
                    'expanded' => true
                ],
                'tags' => [$this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex())]
            ];
        }
        return $directoriesList;
    }


    /**
     * Tree Iterator
     *
     * @param $path
     * @param $regex
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
        $managerConf = $this->getParameter("artgris_file_manager")['conf'];
        if (isset($managerConf[$conf]['dir'])) {

            return $managerConf[$conf];

        } else if (isset($managerConf[$conf]['service'])) {

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

}
