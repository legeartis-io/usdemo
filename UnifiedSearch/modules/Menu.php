<?php

namespace UnifiedSearch\modules;

use UnifiedSearch\controllers\Controller;

class Menu
{
    public function getMenu(string $name, string $customDirectory = null, Controller $controller)
    {

        $str_data = file_get_contents($this->getFilePath($name, $customDirectory));
        $data = json_decode($str_data, true);
        $user = User::getUser();

        $result = [];

        foreach ($data['items'] as $key => $item) {
            if (@$item['access_service'] && !$user->isServiceAvailable($item['access_service'])) {
                continue;
            }

            $item['name'] = $controller->getLanguage()->t($item['name']);
            $item['href'] = $controller->createUrl($item['controller'], $item['action']);

            $result['items'][$key] = $item;
        }

        return $result;
    }

    private function getFilePath($name, $customDirectory = null) {

        return __DIR__ . ($customDirectory ? DIRECTORY_SEPARATOR . $customDirectory : '') . DIRECTORY_SEPARATOR . 'menus' . DIRECTORY_SEPARATOR . $name . '.json';
    }
}