<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/", requirements={"_locale": "([a-z]{2})?"}, name="home_")
 */
class HomeController extends AbstractController
{
    /**
     * Home.
     * @Route("{_locale}", name="index")
     * @Route("", name="index-locale")
     * @param  Request             $request    Request instance
     * @param  LoggerInterface     $logger     Logger service
     * @param  TranslatorInterface $translator Translator service
     * @return Response                        Response instance
     */
    public function index(Request $request, LoggerInterface $logger, TranslatorInterface $translator): Response
    {
        return $this->render("home/index.html.twig");
    }
}
