<?php


namespace App\system;


use App\Lib\Registry;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @property Application Application
 */
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
        $_ = array(
            'URL'   => URL,
            'CURRENT_URL'   => $this->Application->getUrl(),
            'Site_Title' => 'فروشگاه من', // TODO : set Site title with Config Class
        );
        $data = array_merge($_, $data);
        return $this->twig->render($path . '.twig', $data);
    }

    public function __get($name)
    {
        return $this->registry->{$name};
    }


}