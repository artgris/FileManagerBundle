<?php

namespace Artgris\Bundle\FileManagerBundle\Event;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
final class FileManagerEvents
{
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_UPDATE = 'file_manager.pre_update';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_UPDATE = 'file_manager.post_update';

    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_DELETE_FILE = 'file_manager.pre_delete_file';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_DELETE_FILE = 'file_manager.post_delete_file';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_DELETE_FOLDER = ' file_manager.pre_delete_folder';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_DELETE_FOLDER = 'file_manager.post_delete_folder';
}
