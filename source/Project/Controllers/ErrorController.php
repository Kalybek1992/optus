<?php

namespace Source\Project\Controllers;

use Source\Project\Controllers\Base\BaseController;


class ErrorController extends BaseController
{
    public function errorPage(): string
    {
        return $this->twig->render('Error/ErrorPage.twig');
    }

    public function timeBlocking($time_from, $time_to): string
    {
        return $this->twig->render('Error/TimeBlocking.twig', [
            'time_from' => $time_from,
            'time_to' => $time_to,
        ]);
    }
}