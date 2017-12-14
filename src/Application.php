<?php

/*
 * This file is part of the PrestashopConsole package.
 *
 * (c) Matthieu Mota <matthieu@boxydev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Boxydev\Prestashop;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    const ROOT_DIR = __DIR__.'/../';
}
