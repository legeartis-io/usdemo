<?php

namespace UnifiedSearch\controllers;

use Legeartis\UnifiedSearch\exceptions\AccessDeniedException;

/**
 * @property array files
 * @property float totalPages
 * @property array tasks
 * @property array errors
 * @property array errorsFiles
 * @property string checkFileProgressUrl
 * @property string deleteFileUrl
 * @property string downloadErrorsFileUrl
 * @property string uploadFileUrl
 * @property string downloadLocal
 */
class filesController extends Controller
{
    public function show()
    {
        $format = $this->input->getString('format', false);

        if (!$this->user->isLoggedIn()) {
            $this->renderError('401', '401 Unavailable', $this->input->getString('format', null));
        }

        $us = $this->getUS();

        $size = $this->input->getInt('size', 20);
        $page = $this->input->getInt('page', 0);
        $skip = $page * $size;

        try {
            $tasksResponse = $us->getListTasks($skip, $size);
            $tasks = $tasksResponse->getData();

            unset($us);

            if ($tasks) {
                $this->pathway->addItem('Unified Search', $this->getBaseUrl());
                $this->pathway->addItem($this->getLanguage()->t('SEARCH_DEMO'), $this->createUrl('search', 'show'));
                $this->pathway->addItem($this->getLanguage()->t('LOAD_OFFERS'), $this->createUrl('files', 'show'));
            }

            $this->tasks = $tasks;
            $this->errors = [];

            $this->uploadFileUrl = $this->createUrl('files', 'load');
            $this->checkFileProgressUrl = $this->createUrl('files', 'checkFileProgress');
            $this->deleteFileUrl = $this->createUrl('files', 'remove');
            $this->downloadErrorsFileUrl = $this->createUrl('files', 'download');
            $this->downloadLocal = $this->createUrl('files', 'downloadLocal');

            $this->render('files', 'view.twig', true, $format);
        } catch (AccessDeniedException $ex) {
            $this->render('files', 'noaccess.twig', true, $format);
        }
    }

    public function load()
    {
        $file = $this->input->getFiles('file');

        if (!$file) {
            die('Upload error! File is long.');
        }

        $us = $this->getUS();
        $res = $us->uploadFile($file['tmp_name'], $file['name']);

        $this->responseJson($res);
    }

    public function checkFileProgress()
    {
        $us = $this->getUS();

        $lastTasksResponce = $us->getListTasks(0, 1);
        $response = (object)[];

        if ($lastTasksResponce->getData()) {
            $tasks = $lastTasksResponce->getData();
            $response = current($tasks);
        }
        $this->responseJson($response);
    }

    public function remove()
    {
        $id = $this->input->getString('id');

        $us = $this->getUS();
        $us->cancelTask($id);

        $this->responseJson([]);
    }

    public function downloadErrors()
    {

        $taskId = $this->input->getInt('taskId');
        $us = $this->getUS();
        $task = $us->getTaskById($taskId);
        $res = $us->downloadErrors($taskId);

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=errors." . $task->getSourceFile());
        echo $res;
        die();
    }

    public function downloadSource()
    {
        $taskId = $this->input->getInt('taskId');
        $us = $this->getUS();
        $task = $us->getTaskById($taskId);
        $res = $us->downloadSource($taskId);

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=" . $task->getSourceFile());
        echo $res;
        die();
    }
}