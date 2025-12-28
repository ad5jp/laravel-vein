<?php

declare(strict_types=1);

namespace AD5jp\Vein\Navigation;

use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\NodeManager;
use Exception;
use Illuminate\Database\Eloquent\Model;

class NavigationManager
{
    /**
     * @return Nav[]
     */
    public function generate(): array
    {
        // TODO キャッシュがあれば即返す

        // config 設定
        $namespaces = config('vein.model_namespaces');

        $node_manager = new NodeManager();

        $navs = [];

        foreach ($namespaces as $namespace) {
            $dirs = $this->resolveDirectories($namespace);

            foreach ($dirs as $dir) {
                foreach (glob("{$dir}/*.php") as $class_path) {
                    $class_basename = basename($class_path, '.php');
                    $class_name = $namespace . '\\' . $class_basename;

                    if (class_exists($class_name)) {
                        $model = new $class_name();

                        // TODO Taxonomy, Page
                        if ($model instanceof Model && $model instanceof Entry) {
                            $nav = new Nav();
                            $nav->label = $model->menuName();
                            $nav->link = route(
                                'vein.list',
                                ['node' => $node_manager->slug($model)]
                            );

                            $navs[] = $nav;
                        }
                    }
                }
            }
        }

        // TODO キャッシュに書き込み
        return $navs;
    }

    private function resolveDirectories(string $namespace): array
    {
        // composer の psr4 定義を取りに行く
        $psr4 = include base_path('vendor/composer/autoload_psr4.php');

        foreach ($psr4 as $base_namespace => $base_directories) {
            if (str_starts_with($namespace, '\\' . $base_namespace)) {
                $additional_namespace = substr($namespace, strlen($base_namespace) + 1); // $base_namespace には先頭のバックスラッシュがないので +1
                $additional_directory = str_replace('\\', '/', $additional_namespace);
                return array_map(fn (string $dir) => $dir . '/' . $additional_directory, $base_directories);
            }
        }

        throw new Exception('directory for namespace ' . $namespace . ' not found in vendor/composer/autoload_psr4.php');
    }
}
