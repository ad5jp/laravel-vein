<?php

declare(strict_types=1);

namespace AD5jp\Vein\Navigation;

use AD5jp\Vein\Node\Contracts\Page;
use AD5jp\Vein\Node\NodeManager;
use Composer\Autoload\ClassLoader;
use Exception;

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

        $node_manager = new NodeManager;

        $navs = [];

        foreach ($namespaces as $namespace) {
            $namespace = trim($namespace, '\\');
            $dirs = $this->resolveDirectories($namespace);

            foreach ($dirs as $dir) {
                foreach (glob("{$dir}/*.php") ?: [] as $class_path) {
                    $class_basename = basename($class_path, '.php');
                    $class_name = $namespace.'\\'.$class_basename;

                    $model = $node_manager->instantiate($class_name);

                    if ($model === null) {
                        continue;
                    }

                    $nav = new Nav;
                    $nav->label = $model->menuName();
                    $nav->icon = $model->menuIcon();
                    $nav->link = (
                        $model instanceof Page
                        ? route('vein.page', ['node' => $node_manager->slug($model)])
                        : route('vein.list', ['node' => $node_manager->slug($model)])
                    );
                    $nav->order = $model->menuOrder();

                    $navs[] = $nav;
                }
            }
        }

        usort($navs, fn (Nav $a, Nav $b) => $a->order <=> $b->order);

        // TODO キャッシュに書き込み
        return $navs;
    }

    /**
     * 名前空間に対応するディレクトリを Composer のオートローダから引く。
     *
     * vendor/composer/autoload_psr4.php を直接 include すると、パッケージ単体の
     * テストのように base_path() がアプリのルートを指さない環境で見つからない。
     * 登録済みの ClassLoader から引けば置き場所に依存しない。
     *
     * @return string[]
     */
    private function resolveDirectories(string $namespace): array
    {
        /** @var array<string, string[]> */
        $prefixes = [];

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
                $prefixes[$prefix] = array_merge($prefixes[$prefix] ?? [], $directories);
            }
        }

        // PSR-4 は最長プレフィックス一致。反復順の最初を採ると App\ が App\Models\ に勝つ
        $matched = null;
        $needle = $namespace.'\\';

        foreach (array_keys($prefixes) as $prefix) {
            if (! str_starts_with($needle, $prefix)) {
                continue;
            }

            if ($matched === null || strlen($prefix) > strlen($matched)) {
                $matched = $prefix;
            }
        }

        if ($matched === null) {
            throw new Exception('directory for namespace '.$namespace.' not found in the composer autoloader');
        }

        $relative = trim(str_replace('\\', '/', substr($needle, strlen($matched))), '/');

        return array_map(
            fn (string $dir) => $relative === '' ? $dir : $dir.'/'.$relative,
            $prefixes[$matched],
        );
    }
}
