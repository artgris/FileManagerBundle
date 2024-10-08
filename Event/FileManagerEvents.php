<?php

namespace Artgris\Bundle\FileManagerBundle\Event;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
final class FileManagerEvents {
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const PRE_UPDATE = 'file_manager.pre_update';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_UPDATE = 'file_manager.post_update';
    
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const RENAME_FILE = 'file_manager.post_rename_file';

    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const PRE_DELETE_FILE = 'file_manager.pre_delete_file';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_DELETE_FILE = 'file_manager.post_delete_file';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const PRE_DELETE_FOLDER = ' file_manager.pre_delete_folder';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_DELETE_FOLDER = 'file_manager.post_delete_folder';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const RENAME_FOLDER = 'file_manager.post_rename_folder';

    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_DIRECTORY_FILTER_CONFIGURATION = 'file_manager.post_directory_filter_configuration';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_FILE_FILTER_CONFIGURATION = 'file_manager.post_file_filter_configuration';

    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const POST_CHECK_SECURITY = 'file_manager.post_check_security';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const FILE_ACCESS = 'file_manager.file_access';
}
