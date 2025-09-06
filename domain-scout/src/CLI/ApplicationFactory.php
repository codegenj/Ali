<?php

namespace DomainScout\CLI;

use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    public static function create(): Application
    {
        $app = new Application('Domain Scout', '0.1.0');
        $app->add(new CheckCommand());
        return $app;
    }
}
