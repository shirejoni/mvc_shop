<?php


namespace App\system;


use App\Lib\Registry;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller
{
    protected $registry;
    protected $data;
    private $twig;

    public function __construct(Registry $registry, array  $data = [])
    {
        $this->registry = $registry;
        $this->data = $data;

        $loader = new FilesystemLoader(VIEW_PATH);
        // TODO: Enable Cache Option in Twig for Production
        $this->twig = new Environment($loader);
    }

    public function render($path, $data = []) {
        return $this->twig->render($path . '.twig', $data);
    }

}