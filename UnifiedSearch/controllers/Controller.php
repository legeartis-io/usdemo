<?php

namespace UnifiedSearch\controllers;

use Legeartis\UnifiedSearch\Config;
use Legeartis\UnifiedSearch\UnifiedSearchService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use UnifiedSearch\modules\Input;
use UnifiedSearch\modules\Language;
use UnifiedSearch\modules\Menu;
use UnifiedSearch\modules\pathway\Pathway;
use UnifiedSearch\modules\ServiceProxy;
use UnifiedSearch\modules\User;


/**
 * @property string errorMessage
 * @property false|mixed $action
 * @property bool|mixed|string $baseUrl
 * @property mixed $template
 * @property false|mixed $controller
 */
class Controller
{
    /**
     * @var  Pathway
     */
    public $pathway;

    /**
     * @var Menu
     */
    public $menu;
    /**
     * @var string[]
     */
    public $requestText = [];
    /**
     * @var string[]
     */
    public $responseText = [];
    /**
     * @var string
     */
    public $theme = 'default';
    /**
     * @var array
     * @default null
     */
    protected $config = null;
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Input
     */
    protected $input;
    /**
     * @var Language
     */
    private $language;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->input = new Input();
        $this->menu = new Menu();
        $this->action = $this->input->getString('action');
        $this->controller = $this->input->getString('controller');
        $this->user = User::getUser();
        $this->config = $this->getConfig();
        $this->baseUrl = $this->getBaseUrl();
        $this->pathway = new Pathway();
        $this->template = $this->config['property']['template']['name'];

        $this->language = new Language($this->config['property']['template']['default_lang']);
    }

    protected function getUS(): UnifiedSearchService
    {
        $config = new Config($this->config['UnifiedSearchService']);
        return new ServiceProxy($config, $this);
    }

    protected function getConfig()
    {
        $config = json_decode(file_get_contents(ROOTPATH . '/config.json'), true);

        if ($this->user->isLoggedIn()) {
            $config['UnifiedSearchService']['login'] = $this->user->getLogin();
            $config['UnifiedSearchService']['password'] = $this->user->getPassword();

            $config['OEMService']['login'] = $this->user->getLogin();
            $config['OEMService']['key'] = $this->user->getPassword();
        }

        return $config;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->language;
    }

    /**
     * @return bool|mixed
     */
    public function getBaseUrl()
    {
        return !empty($_SERVER['DOCUMENT_URI']) ? $_SERVER['DOCUMENT_URI'] : '/';
    }

    public function renderError($code, $message, $format = null)
    {
        $this->errorMessage = $message;

        if ($format === 'json') {
            $this->responseJson(['error' => true, 'code' => $code, 'message' => $this->errorMessage]);
            die();
        }

        $this->render('tmpl', $code . '.twig');
        die();
    }

    public function responseJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

    public function render($tpl = '', $view = 'view.twig', $fullHtml = true, $renderJson = false)
    {
        if ($renderJson) {
            $vars = get_object_vars($this);
            unset($vars['config']);
            unset($vars['user']);

            $this->responseJson($vars);
        }

        $user = $this->user;
        $vars = (array)$this;
        $menu = $this->menu->getMenu('main', null, $this);
        $vars = array_merge($vars, [
            'user' => $user,
            'vars' => $vars,
            'menuItems' => $menu['items'],
        ]);
        $this->loadTwig($tpl, $view, $vars, $fullHtml);
    }

    public function getLocalization()
    {
        return $this->getLanguage()->getLocalization();
    }

    public function createUrl($controller = null, $action = null, $format = null, array $params = [])
    {
        if (!$controller && !$action) {
            return 'index.php';
        }

        $paths = [];

        if ($controller) {
            if (is_array($controller)) {
                $paths = array_merge($paths, $controller);
            } else {
                $paths['controller'] = lcfirst($controller) . 'Controller';
            }
        }

        if ($action) {
            if (is_array($action)) {
                $paths = array_merge($paths, $action);
            } else {
                $paths['action'] = $action;
            }
        }

        if ($format) {
            if (is_array($format)) {
                $paths = array_merge($paths, $format);
            } else {
                $paths['format'] = $format;
            }
        }

        foreach ($params as $key => $param) {
            $params[$key] = trim($param);
        }

        if ($params) {
            $paths = array_merge($paths, $params);
        }

        $baseUrl = $_SERVER['HTTP_HOST'] . '/';

        if ($paths) {
            $url = ('index.php?' . http_build_query($paths));
            if (strpos($url, $baseUrl) === false) {
                $url = 'index.php?' . http_build_query($paths);
            }
        } else {
            $url = $baseUrl;
        }

        return urldecode($url);
    }

    public function noSpaces($name)
    {
        $name = (string)$name;
        return preg_replace('/\s+/', ' ', $name);
    }

    public function loadTwig($tpl = '', $view = '', $vars = [], $fullHtml = true)
    {
        if ($tpl === '') {
            $tpl = 'tmpl';
        }

        $rootDir = ROOTPATH;

        $loader = new FilesystemLoader([
            $rootDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->template,
            $rootDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->template . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $tpl . '/',
        ]);

        $twig = new Environment($loader, [
            'cache' => false,
            'auto_reload' => true,
        ]);

        $twig->addFilter(new TwigFilter('t', [$this->getLanguage(), 't']));
        $twig->addFunction(new TwigFunction('createUrl', [$this, 'createUrl']));
        $twig->addFunction(new TwigFunction('getHeadScripts', [$this, 'getHeadScripts']));
        $twig->addFunction(new TwigFunction('pagination', [$this, 'pagination']));
        $twig->addFilter(new TwigFilter('dump', 'var_dump'));
        $twig->addFilter(new TwigFilter('printr', 'print_r'));
        $twig->addFilter(new TwigFilter('noSpaces', [$this, 'noSpaces']));

        $twig->addFilter(new TwigFilter('cast_to_array', function ($stdClassObject) {
            $response = [];
            if ($stdClassObject) {
                foreach ($stdClassObject as $key => $value) {
                    $response[$key] = $value;
                }

                return $response;
            }

            return [];
        }));

        if ($fullHtml) {
            $vars['templateName'] = $view;
            $vars['current'] = getenv('REQUEST_URI');
            echo $twig->render('layouts\index.twig', $vars);
        } else {
            echo $twig->render($view, $vars);
        }

        return $twig;
    }

    public function pagination(
        $block = '',
        $additionalParam1 = [],
        $additionalParam2 = [],
        $additionalParam3 = [],
        $totalPages = false
    )
    {
        $this->totalPages = $totalPages;
        $sizes = [
            20,
            50,
            100,
            500,
            1000
        ];

        $this->block = $block;
        $this->cPage = $this->input->getString('page', 0);
        $this->controller = str_replace('Controller', '', (new \ReflectionClass($this))->getShortName());
        $this->action = $this->input->getString('action');
        $this->format = $this->input->getString('format');
        $this->pageSizes = $sizes;

        $this->param1 = $additionalParam1;
        $this->param2 = $additionalParam2;
        $this->param3 = $additionalParam3;


        $this->render('tmpl', 'pagination.twig', false);
    }

    public function filter(
        $type = 'text',
        $filterName = '',
        $filterType = '',
        $defaultValue = '',
        $filterKeyValues = [],
        $multi = '',
        $itemsBlock = '',
        $tooltip = '',
        $checked = ''
    )
    {
        $this->cPage = $this->input->getString('page', 0);
        $this->controller = str_replace('Controller', '', (new \ReflectionClass($this))->getShortName());
        $this->action = $this->input->getString('action');
        $this->format = $this->input->getString('format');
        $this->type = $type;
        $this->filterName = $filterName;
        $this->filterType = $filterType;
        $this->defaultValue = $defaultValue;
        $this->filterKeyValues = $filterKeyValues;
        $this->multi = $multi;
        $this->itemsBlock = $itemsBlock;
        $this->tooltip = $tooltip;
        $this->checked = $checked;

        $this->render('tmpl', 'filterInput.twig', false);
    }

    public function redirect($controller, $action, $params, $message = null, $messageType = 'alert')
    {
        $params['message'] = $message;
        $params['messageType'] = $messageType;

        $location = $this->createUrl($controller, $action, $params);

        header('Location:' . $location);
    }

    public function redirectToUrl($url)
    {
        header('Location:' . $url);
    }

    public function showIndex()
    {
        $this->render('tmpl', 'index.twig');
    }

    public function downloadLocal($name = null)
    {
        if (!$name) {
            $name = $this->input->getString('name');
        }

        if (!$name) {
            die("Error: Wrong input");
        }

        $name = basename($name);

        $path = ROOTPATH . '/files/' . $name;
        if (file_exists($path)) {

            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public"); // needed for internet explorer
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:" . filesize(ROOTPATH . '/files/' . $name));
            header("Content-Disposition: attachment; filename=$name");
            readfile(ROOTPATH . '/files/' . $name);
            die();
        } else {
            die("Error: File not found.");
        }
    }

    public function getHeadScripts()
    {
        $scripts = scandir(ROOTPATH . '/assets/js');
        $scriptsStr = '';


        foreach ($scripts as $script) {
            if ($script !== '.' && $script !== '..')
                $scriptsStr .= '<script src="UnifiedSearch/assets/js/' . $script . '"></script>';
        }

        return $scriptsStr;
    }
}
