<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\PHPoole;

interface StepInterface
{
    public function __construct(PHPoole $PHPoole);

    public function init();

    public function process();
}
