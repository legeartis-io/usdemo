<?php

namespace UnifiedSearch;

use Exception;
use UnifiedSearch\controllers\Controller;

class router
{
    public static function start()
    {
        try {
            $route = self::parse($_SERVER['REQUEST_URI']);
            if (isset($route['controller']) && $route['controller'] !== '') {
                $controllerNamespace = 'UnifiedSearch\controllers\\' . $route['controller'];
                $action = isset($route['action']) ? $route['action'] : 'index';

                if (class_exists($controllerNamespace)) {
                    $controller = new $controllerNamespace();

                    if (method_exists($controller, $action)) {
                        $controller->$action();
                    } else {
                        http_response_code('404');
                        $abstractController = new Controller();
                        $abstractController->render('tmpl', '404.twig');
                    }
                } else {
                    http_response_code('404');
                    $abstractController = new Controller();
                    $abstractController->render('tmpl', '404.twig');
                }
            } else {
                $abstractController = new Controller();
                $abstractController->showIndex();
            }
        } catch (Exception $ex) {
            $controller = new Controller();
            $controller->renderError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * @param $segments
     *
     * @return array
     */
    public static function parse(&$segments)
    {

        $url = parse_url($segments);
        if (isset($url['query'])) {
            $query = $url['query'];
        }

        if (!empty($query)) {
            $values = explode('&', $query);
            foreach ($values as $key => $value) {
                if ($value === '') {
                    unset($values[$key]);
                }
                $parameter = explode('=', $value);

                if (!empty($parameter)) {
                    $parameters[$parameter[0]] = isset($parameter[1]) ? $parameter[1] : '';
                }


            }
            reset($values);

            $params = [];
            foreach ($values as $value) {
                $key = explode('=', $value);

                if (isset($key[0]) && isset($key[1])) {

                    $params[$key[0]] = $key[1];

                }

            }

            return [
                'controller' => isset($parameters['controller']) ? $parameters['controller'] : '',
                'action'     => isset($parameters['action']) ? $parameters['action'] : '',
                'params'     => $params
            ];
        }

        return [];
    }

    /**
     * @return array
     */
    public static function getRoute()
    {
        $route = self::parse($_SERVER['REQUEST_URI']);

        $cRoute = [];

        if (isset($route['controller']) && $route['controller'] !== '') {
            $action               = isset($route['action']) ? $route['action'] : 'index';
            $cRoute['controller'] = $route['controller'];
            $cRoute['action']     = $action;
            $cRoute['params']     = $route['params'];
        }

        return $cRoute;
    }
}