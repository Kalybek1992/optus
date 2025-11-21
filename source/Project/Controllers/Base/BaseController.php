<?php

namespace Source\Project\Controllers\Base;

use Source\Base\Core\Controller;
use Source\Project\DataContainers\RequestDC;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class BaseController extends Controller
{

    protected Environment $twig;

    private array $languages = [
        "Ru"
    ];

    public function __construct()
    {

        // Загружаем шаблоны из папки на уровень выше и язык
        $loader = new FilesystemLoader(__DIR__ . '/../../HtmlTemplates/');
        $this->twig = new Environment($loader);
    }

    #[\Override] public static function isController(string $name = null): bool
    {
        // TODO: Implement isController() method.
        return true;
    }

    #[\Override] public function default(): array
    {
        return ['value' => 'error'];
    }
}
