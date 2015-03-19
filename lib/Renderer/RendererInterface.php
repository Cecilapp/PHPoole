<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

/**
 * Interface RendererInterface
 * @package PHPoole\Renderer
 */
interface RendererInterface
{
    /**
     * Constructor
     *
     * @param null $templatesPath
     */
    public function __construct($templatesPath = null);

    /**
     * @param $path
     * @return mixed
     */
    public function addPath($path);

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function addGlobal($name, $value);

    /**
     * @param $template
     * @param $variables
     * @return mixed
     */
    public function render($template, $variables);
}