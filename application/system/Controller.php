<?php


namespace App\system;


use App\lib\Config;
use App\Lib\Registry;
use App\model\Language;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @property Application Application
 * @property Language Language
 * @property Config Config
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
            'Token'   => isset($_SESSION['token']) ? $_SESSION['token'] : '',
            'Shop_Title' => $this->Config->get("site_title"),
            'T'         => $this->Language->all(),
            'ADMIN_URL' => ADMIN_URL
        );
        if(isset($this->data['Customer'])) {
            $_['Customer'] = $this->data['Customer'];
        }
        $data = array_merge($_, $data);
        return $this->twig->render($path . '.twig', $data);
    }

    public function __get($name)
    {
        return $this->registry->{$name};
    }

    public function load($name, ... $params) {
        $parts = explode('\\', $name);
        $model_id = strtolower(implode('_', $parts));
        if(!$this->registry->has($model_id)) {
            $className = array_pop($parts);
            $file = MODEL_PATH;
            if(count($parts) > 0) {
                $file .= DS . strtolower(implode(DS, $parts));
            }
            $file .= DS . ucfirst($className) . '.php';
            if(file_exists($file)) {
                require_once $file;
                $modelName = '\\' . MODEL_NAMESPACE . '\\' . ucfirst($className);
                $modelObject = new $modelName(... $params);
                $this->registry->{$model_id} = $modelObject;
            }else {
                throw new \Exception("Call an unknown Model name = {$name}");
            }
        }
        return $this->registry->{$model_id};
    }


}